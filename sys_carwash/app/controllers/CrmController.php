<?php
class CrmController extends Controller {
    const ETAPAS = ['nuevo' => ['Nuevos', '#0ea5e9'], 'contactado' => ['Contactados', '#8b5cf6'], 'propuesta' => ['Propuesta', '#f59e0b'], 'ganado' => ['Ganados', '#22c55e'], 'perdido' => ['Perdidos', '#f43f5e']];
    const TIPOS = ['llamada' => 'Llamada', 'whatsapp' => 'WhatsApp', 'email' => 'Correo', 'visita' => 'Visita', 'nota' => 'Nota'];

    private function guard(): void { $this->requireModule('clientes'); }
    private function post(): void { if (!$this->isPost()) $this->redirect('crm'); csrf_check(); }
    private function fecha($v): ?string { $v = trim((string)$v); return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null; }
    private function back(string $def): void {
        $b = (string)($_POST['volver'] ?? '');
        $this->redirect(preg_match('#^(clientes/ficha/\d+|crm[a-z_/0-9?=&]*)$#i', $b) ? $b : $def);
    }
    private function absUrl(string $path): string {
        $u = url($path);
        if (preg_match('#^https?://#', $u)) return $u;
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') == 443;
        return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $u;
    }

    /* ---------- Panel ---------- */
    public function index() {
        $this->guard();
        $cfg = crm_cfg(); $cl = crm_clientes();
        $segCount = array_fill_keys(array_keys(CRM_SEGS), 0);
        $con1 = $con2 = 0; $puntos = 0;
        foreach ($cl as $c) { $segCount[$c['segmento']]++; if ($c['visitas'] >= 1) $con1++; if ($c['visitas'] >= 2) $con2++; $puntos += $c['puntos']; }
        $activos = $segCount['vip'] + $segCount['frecuente'] + $segCount['nuevo'] + $segCount['ocasional'];
        $k = [
            'total' => count($cl), 'activos' => $activos, 'riesgo' => $segCount['en_riesgo'] + $segCount['inactivo'], 'vip' => $segCount['vip'],
            'nuevos_mes' => (int)Database::val("SELECT COUNT(*) FROM clientes WHERE estado=1 AND created_at >= ?", [date('Y-m-01')]),
            'recompra' => $con1 ? round($con2 * 100 / $con1) : 0, 'puntos' => $puntos,
            'seg_pend' => (int)Database::val('SELECT COUNT(*) FROM crm_interacciones WHERE seguimiento_fecha IS NOT NULL AND seguimiento_hecho=0 AND seguimiento_fecha <= CURDATE()'),
            'op_abiertas' => (int)Database::val("SELECT COUNT(*) FROM crm_oportunidades WHERE etapa IN ('nuevo','contactado','propuesta')"),
            'op_valor' => (float)Database::val("SELECT COALESCE(SUM(valor_est),0) FROM crm_oportunidades WHERE etapa IN ('nuevo','contactado','propuesta')"),
            'csat' => Database::val('SELECT ROUND(AVG(puntaje),1) FROM crm_encuestas WHERE puntaje IS NOT NULL'),
            'resp' => (int)Database::val('SELECT COUNT(*) FROM crm_encuestas WHERE puntaje IS NOT NULL'),
        ];
        $cumples = array_values(array_filter($cl, fn($c) => $c['cumple_en'] !== null && $c['cumple_en'] <= 30));
        usort($cumples, fn($a, $b) => $a['cumple_en'] <=> $b['cumple_en']);
        $riesgo = array_values(array_filter($cl, fn($c) => $c['segmento'] === 'en_riesgo'));
        usort($riesgo, fn($a, $b) => $b['gastado'] <=> $a['gastado']);
        $top = $cl; usort($top, fn($a, $b) => $b['gastado'] <=> $a['gastado']); $top = array_slice(array_filter($top, fn($c) => $c['gastado'] > 0), 0, 5);
        $pend = Database::all("SELECT i.*, c.nombre cliente, c.telefono FROM crm_interacciones i JOIN clientes c ON c.id=i.cliente_id
            WHERE i.seguimiento_fecha IS NOT NULL AND i.seguimiento_hecho=0 ORDER BY i.seguimiento_fecha LIMIT 6");
        $meses = []; for ($i = 5; $i >= 0; $i--) $meses[date('Y-m', strtotime("-$i month", strtotime(date('Y-m-01'))))] = 0;
        foreach (Database::all("SELECT DATE_FORMAT(created_at,'%Y-%m') m, COUNT(*) n FROM clientes WHERE created_at >= ? GROUP BY m", [array_key_first($meses) . '-01']) as $r) if (isset($meses[$r['m']])) $meses[$r['m']] = (int)$r['n'];
        $charts = ['seg' => ['labels' => array_map(fn($s) => CRM_SEGS[$s][0], array_keys($segCount)), 'data' => array_values($segCount), 'colors' => array_map(fn($s) => CRM_SEGS[$s][2], array_keys($segCount))],
                   'nuevos' => ['labels' => array_keys($meses), 'data' => array_values($meses)]];
        $this->view('crm/index', compact('k', 'cfg', 'cumples', 'riesgo', 'top', 'pend', 'charts', 'segCount') + ['title' => 'CRM · Panel']);
    }

    /* ---------- Segmentos ---------- */
    public function segmentos() {
        $this->guard();
        $cfg = crm_cfg(); $seg = $_GET['seg'] ?? 'todos'; $q = mb_strtolower(trim($_GET['q'] ?? ''));
        $all = crm_clientes();
        $count = array_fill_keys(array_keys(CRM_SEGS), 0);
        foreach ($all as $c) $count[$c['segmento']]++;
        $rows = array_values(array_filter($all, fn($c) => ($seg === 'todos' || $c['segmento'] === $seg) && ($q === '' || str_contains(mb_strtolower($c['nombre'] . ' ' . $c['telefono']), $q))));
        if (!empty($_GET['export'])) {
            header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="clientes_' . preg_replace('/\W/', '', $seg) . '.csv"');
            echo "\xEF\xBB\xBF" . "Cliente;Teléfono;Correo;Segmento;Visitas;Gastado;Última visita;Días sin venir;Puntos\n";
            $f = fn($v) => '"' . str_replace('"', '""', preg_match('/^[=+\-@]/', (string)$v) ? "'" . $v : (string)$v) . '"';
            foreach ($rows as $c) echo implode(';', [$f($c['nombre']), $f($c['telefono']), $f($c['email']), $f(CRM_SEGS[$c['segmento']][0]), $c['visitas'], $c['gastado'], $f($c['ultima']), $c['dias'] ?? '', $c['puntos']]) . "\n";
            exit;
        }
        $this->view('crm/segmentos', compact('rows', 'count', 'seg', 'q', 'cfg') + ['total' => count($all), 'title' => 'CRM · Segmentos']);
    }

    /* ---------- Seguimientos ---------- */
    public function seguimientos() {
        $this->guard();
        $pend = Database::all("SELECT i.*, c.nombre cliente, c.telefono FROM crm_interacciones i JOIN clientes c ON c.id=i.cliente_id
            WHERE i.seguimiento_fecha IS NOT NULL AND i.seguimiento_hecho=0 ORDER BY i.seguimiento_fecha");
        $hist = Database::all("SELECT i.*, c.nombre cliente, u.nombre usuario FROM crm_interacciones i JOIN clientes c ON c.id=i.cliente_id LEFT JOIN usuarios u ON u.id=i.usuario_id ORDER BY i.id DESC LIMIT 40");
        $clientes = Database::all('SELECT id,nombre,telefono FROM clientes WHERE estado=1 ORDER BY nombre');
        $this->view('crm/seguimientos', compact('pend', 'hist', 'clientes') + ['tipos' => self::TIPOS, 'title' => 'CRM · Seguimientos']);
    }
    public function interaccion_guardar() {
        $this->guard(); $this->post();
        $cid = (int)($_POST['cliente_id'] ?? 0); $tipo = $_POST['tipo'] ?? 'nota'; $asunto = trim($_POST['asunto'] ?? '');
        if (!Database::val('SELECT COUNT(*) FROM clientes WHERE id=?', [$cid]) || $asunto === '' || !isset(self::TIPOS[$tipo])) { flash('danger', 'Completa cliente y asunto.'); $this->back('crm/seguimientos'); }
        Database::q('INSERT INTO crm_interacciones (cliente_id,tipo,asunto,detalle,seguimiento_fecha,usuario_id) VALUES (?,?,?,?,?,?)',
            [$cid, $tipo, mb_substr($asunto, 0, 150), trim($_POST['detalle'] ?? '') ?: null, $this->fecha($_POST['seguimiento_fecha'] ?? ''), auth_user()['id']]);
        log_action('crm', "Interacción con cliente #$cid: $asunto");
        flash('success', 'Interacción registrada.'); $this->back('crm/seguimientos');
    }
    public function seguimiento_hecho() {
        $this->guard(); $this->post();
        Database::q('UPDATE crm_interacciones SET seguimiento_hecho=1 WHERE id=?', [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Seguimiento completado.'); $this->back('crm/seguimientos');
    }

    /* ---------- Oportunidades (pipeline) ---------- */
    public function pipeline() {
        $this->guard();
        $ops = [];
        foreach (self::ETAPAS as $e => $_) $ops[$e] = [];
        foreach (Database::all('SELECT * FROM crm_oportunidades ORDER BY updated_at DESC') as $o) $ops[$o['etapa']][] = $o;
        $this->view('crm/pipeline', ['ops' => $ops, 'etapas' => self::ETAPAS, 'title' => 'CRM · Oportunidades']);
    }
    public function op_guardar() {
        $this->guard(); $this->post();
        $id = (int)($_POST['id'] ?? 0); $nombre = trim($_POST['nombre'] ?? '');
        if ($nombre === '') { flash('danger', 'El nombre es obligatorio.'); $this->redirect('crm/pipeline'); }
        $d = [mb_substr($nombre, 0, 150), trim($_POST['telefono'] ?? '') ?: null, trim($_POST['email'] ?? '') ?: null, trim($_POST['interes'] ?? '') ?: null,
              trim($_POST['origen'] ?? '') ?: null, max(0, (float)($_POST['valor_est'] ?? 0)), trim($_POST['notas'] ?? '') ?: null];
        if ($id) Database::q('UPDATE crm_oportunidades SET nombre=?,telefono=?,email=?,interes=?,origen=?,valor_est=?,notas=? WHERE id=?', [...$d, $id]);
        else Database::q('INSERT INTO crm_oportunidades (nombre,telefono,email,interes,origen,valor_est,notas,usuario_id) VALUES (?,?,?,?,?,?,?,?)', [...$d, auth_user()['id']]);
        flash('success', 'Oportunidad guardada.'); $this->redirect('crm/pipeline');
    }
    public function op_mover() {
        $this->guard(); $this->post();
        $e = $_POST['etapa'] ?? '';
        if (isset(self::ETAPAS[$e])) Database::q('UPDATE crm_oportunidades SET etapa=? WHERE id=?', [$e, (int)($_POST['id'] ?? 0)]);
        if (!empty($_POST['ajax'])) $this->json(['ok' => true]);
        $this->redirect('crm/pipeline');
    }
    public function op_borrar() {
        $this->guard(); $this->post();
        Database::q('DELETE FROM crm_oportunidades WHERE id=?', [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Oportunidad eliminada.'); $this->redirect('crm/pipeline');
    }
    public function op_convertir() {
        $this->guard(); $this->post();
        $o = Database::row('SELECT * FROM crm_oportunidades WHERE id=?', [(int)($_POST['id'] ?? 0)]);
        if (!$o) $this->redirect('crm/pipeline');
        if (!$o['cliente_id']) {
            Database::q("INSERT INTO clientes (tipo_doc,nombre,telefono,email,notas) VALUES ('DNI',?,?,?,?)", [$o['nombre'], $o['telefono'], $o['email'], 'Origen CRM: ' . ($o['origen'] ?: 'oportunidad')]);
            $cid = (int)Database::id();
            Database::q("UPDATE crm_oportunidades SET cliente_id=?, etapa='ganado' WHERE id=?", [$cid, $o['id']]);
        } else $cid = (int)$o['cliente_id'];
        log_action('crm', "Oportunidad convertida a cliente #$cid");
        flash('success', 'Convertido en cliente. Completa sus datos.'); $this->redirect('clientes/ficha/' . $cid);
    }

    /* ---------- Fidelización ---------- */
    public function fidelidad() {
        $this->guard(); $cfg = crm_cfg(); crm_sync_puntos();
        $cl = crm_clientes(); usort($cl, fn($a, $b) => $b['puntos'] <=> $a['puntos']);
        $rank = array_slice(array_filter($cl, fn($c) => $c['puntos'] > 0), 0, 20);
        $mov = Database::all("SELECT p.*, c.nombre cliente FROM crm_puntos p JOIN clientes c ON c.id=p.cliente_id ORDER BY p.id DESC LIMIT 25");
        $this->view('crm/fidelidad', compact('cfg', 'rank', 'mov', 'cl') + ['title' => 'CRM · Fidelización']);
    }
    public function canjear() {
        $this->guard(); $this->post();
        $cfg = crm_cfg(); $cid = (int)($_POST['cliente_id'] ?? 0); $pts = (int)($_POST['puntos'] ?? 0);
        crm_sync_puntos(); $saldo = crm_saldos($cid)[$cid] ?? 0;
        if ($pts < (int)$cfg['puntos_minimo'] || $pts > $saldo || $pts <= 0) { flash('danger', "Puntos inválidos. Mínimo {$cfg['puntos_minimo']} y máximo el saldo ($saldo)."); $this->back('crm/fidelidad'); }
        $monto = round($pts * (float)$cfg['valor_punto'], 2);
        $pdo = Database::pdo(); $pdo->beginTransaction();
        try {
            $cod = crm_codigo('PTS');
            Database::q("INSERT INTO crm_cupones (codigo,descripcion,tipo,valor,cliente_id,vigente_hasta,usos_max,origen) VALUES (?,?, 'monto', ?, ?, DATE_ADD(CURDATE(), INTERVAL 90 DAY), 1, 'canje')", [$cod, "Canje de $pts puntos", $monto, $cid]);
            Database::q("INSERT INTO crm_puntos (cliente_id,tipo,puntos,detalle,usuario_id) VALUES (?, 'canjeado', ?, ?, ?)", [$cid, -$pts, "Canje → cupón $cod", auth_user()['id']]);
            $pdo->commit();
        } catch (Throwable $e) { $pdo->rollBack(); flash('danger', 'No se pudo canjear.'); $this->back('crm/fidelidad'); }
        log_action('crm', "Canje de $pts puntos (cliente #$cid) → $cod");
        flash('success', "Canje realizado: cupón <b>$cod</b> por " . money($monto) . ' (vigente 90 días).'); $this->back('crm/fidelidad');
    }
    public function ajustar() {
        $this->requireModule('configuracion'); $this->post();
        $cid = (int)($_POST['cliente_id'] ?? 0); $pts = (int)($_POST['puntos'] ?? 0);
        if (!$pts || !Database::val('SELECT COUNT(*) FROM clientes WHERE id=?', [$cid])) { flash('danger', 'Datos inválidos.'); $this->back('crm/fidelidad'); }
        Database::q("INSERT INTO crm_puntos (cliente_id,tipo,puntos,detalle,usuario_id) VALUES (?, 'ajuste', ?, ?, ?)", [$cid, $pts, mb_substr(trim($_POST['detalle'] ?? '') ?: 'Ajuste manual', 0, 200), auth_user()['id']]);
        log_action('crm', "Ajuste de puntos $pts (cliente #$cid)");
        flash('success', 'Ajuste registrado.'); $this->back('crm/fidelidad');
    }

    /* ---------- Cupones ---------- */
    public function cupones() {
        $this->guard();
        $rows = Database::all('SELECT k.*, c.nombre cliente FROM crm_cupones k LEFT JOIN clientes c ON c.id=k.cliente_id ORDER BY k.id DESC LIMIT 200');
        $clientes = Database::all('SELECT id,nombre FROM clientes WHERE estado=1 ORDER BY nombre');
        $this->view('crm/cupones', compact('rows', 'clientes') + ['title' => 'CRM · Cupones']);
    }
    public function cupon_guardar() {
        $this->guard(); $this->post();
        $tipo = ($_POST['tipo'] ?? '') === 'monto' ? 'monto' : 'porcentaje'; $valor = (float)($_POST['valor'] ?? 0);
        if ($valor <= 0 || ($tipo === 'porcentaje' && $valor > 100)) { flash('danger', 'Valor inválido.'); $this->redirect('crm/cupones'); }
        $cod = strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '', $_POST['codigo'] ?? '')) ?: crm_codigo('CW');
        if (Database::val('SELECT COUNT(*) FROM crm_cupones WHERE codigo=?', [$cod])) { flash('danger', 'Ese código ya existe.'); $this->redirect('crm/cupones'); }
        Database::q('INSERT INTO crm_cupones (codigo,descripcion,tipo,valor,cliente_id,vigente_hasta,usos_max) VALUES (?,?,?,?,?,?,?)',
            [mb_substr($cod, 0, 30), trim($_POST['descripcion'] ?? '') ?: null, $tipo, $valor, (int)($_POST['cliente_id'] ?? 0) ?: null, $this->fecha($_POST['vigente_hasta'] ?? ''), max(0, (int)($_POST['usos_max'] ?? 1))]);
        log_action('crm', "Cupón creado $cod");
        flash('success', "Cupón <b>$cod</b> creado."); $this->redirect('crm/cupones');
    }
    public function cupon_toggle() {
        $this->guard(); $this->post();
        Database::q('UPDATE crm_cupones SET estado=1-estado WHERE id=?', [(int)($_POST['id'] ?? 0)]);
        $this->redirect('crm/cupones');
    }
    public function cupon_validar() {
        $this->requireModule('ordenes');
        [$ok, $msg, $desc, $cp] = crm_validar_cupon((string)($_GET['codigo'] ?? ''), (int)($_GET['cliente_id'] ?? 0) ?: null, (float)($_GET['subtotal'] ?? 0));
        $this->json(['ok' => $ok, 'msg' => $msg, 'descuento' => $desc, 'tipo' => $cp['tipo'] ?? null, 'valor' => isset($cp['valor']) ? (float)$cp['valor'] : 0]);
    }

    /* ---------- Campañas ---------- */
    public function campanas() {
        $this->guard();
        $rows = Database::all('SELECT c.*, (SELECT COUNT(*) FROM crm_campana_envios e WHERE e.campana_id=c.id AND e.enviado=1) enviados FROM crm_campanas c ORDER BY c.id DESC LIMIT 50');
        $cl = crm_clientes(); $count = ['todos' => 0, 'cumple_mes' => 0] + array_fill_keys(array_keys(CRM_SEGS), 0);
        foreach ($cl as $c) { if (!$c['acepta_marketing']) continue; $count['todos']++; $count[$c['segmento']]++; if ($c['fecha_nacimiento'] && date('n', strtotime($c['fecha_nacimiento'])) == date('n')) $count['cumple_mes']++; }
        $this->view('crm/campanas', ['rows' => $rows, 'count' => $count, 'cfg' => crm_cfg(), 'title' => 'CRM · Campañas']);
    }
    public function campana_crear() {
        $this->guard(); $this->post();
        $seg = $_POST['segmento'] ?? 'todos'; $canal = ($_POST['canal'] ?? '') === 'email' ? 'email' : 'whatsapp';
        $nombre = trim($_POST['nombre'] ?? ''); $msg = trim($_POST['mensaje'] ?? '');
        if ($nombre === '' || $msg === '' || ($seg !== 'todos' && $seg !== 'cumple_mes' && !isset(CRM_SEGS[$seg]))) { flash('danger', 'Completa nombre, audiencia y mensaje.'); $this->redirect('crm/campanas'); }
        $dest = array_filter(crm_clientes(), function ($c) use ($seg, $canal) {
            if (!$c['acepta_marketing']) return false;
            if ($canal === 'whatsapp' ? crm_tel($c['telefono']) === '' : trim((string)$c['email']) === '') return false;
            if ($seg === 'todos') return true;
            if ($seg === 'cumple_mes') return $c['fecha_nacimiento'] && date('n', strtotime($c['fecha_nacimiento'])) == date('n');
            return $c['segmento'] === $seg;
        });
        if (!$dest) { flash('warning', 'No hay clientes con contacto disponible para esa audiencia.'); $this->redirect('crm/campanas'); }
        $pdo = Database::pdo(); $pdo->beginTransaction();
        try {
            $pct = (float)($_POST['cupon_pct'] ?? 0);
            if ($pct > 0 && $pct <= 100) {
                $cod = crm_codigo('PROMO');
                Database::q("INSERT INTO crm_cupones (codigo,descripcion,tipo,valor,vigente_hasta,usos_max,origen) VALUES (?,?, 'porcentaje', ?, DATE_ADD(CURDATE(), INTERVAL ? DAY), 0, 'cumple')", [$cod, "Campaña: $nombre", $pct, max(1, (int)($_POST['cupon_dias'] ?? 30))]);
                $msg = str_replace('{cupon}', $cod, $msg);
            }
            Database::q('INSERT INTO crm_campanas (nombre,segmento,canal,mensaje,total,usuario_id) VALUES (?,?,?,?,?,?)', [mb_substr($nombre, 0, 120), $seg, $canal, $msg, count($dest), auth_user()['id']]);
            $id = (int)Database::id();
            foreach ($dest as $c) Database::q('INSERT IGNORE INTO crm_campana_envios (campana_id,cliente_id) VALUES (?,?)', [$id, $c['id']]);
            $pdo->commit();
        } catch (Throwable $e) { $pdo->rollBack(); flash('danger', 'No se pudo crear la campaña.'); $this->redirect('crm/campanas'); }
        log_action('crm', "Campaña creada: $nombre (" . count($dest) . ')');
        flash('success', 'Campaña creada con ' . count($dest) . ' destinatarios.'); $this->redirect('crm/campana_ver/' . $id);
    }
    public function campana_ver($id = 0) {
        $this->guard();
        $c = Database::row('SELECT * FROM crm_campanas WHERE id=?', [(int)$id]);
        if (!$c) { flash('danger', 'Campaña no encontrada.'); $this->redirect('crm/campanas'); }
        $sal = crm_saldos();
        $env = Database::all('SELECT e.*, cl.nombre, cl.telefono, cl.email FROM crm_campana_envios e JOIN clientes cl ON cl.id=e.cliente_id WHERE e.campana_id=? ORDER BY e.enviado, cl.nombre', [$c['id']]);
        foreach ($env as &$e) {
            $e['puntos'] = $sal[(int)$e['cliente_id']] ?? 0;
            $txt = crm_msg($c['mensaje'], $e);
            $e['link'] = $c['canal'] === 'whatsapp' ? crm_wa($e['telefono'], $txt) : 'mailto:' . rawurlencode((string)$e['email']) . '?subject=' . rawurlencode($c['nombre']) . '&body=' . rawurlencode($txt);
        }
        unset($e);
        $this->view('crm/campana_ver', ['c' => $c, 'env' => $env, 'title' => 'CRM · Campaña']);
    }
    public function campana_marcar() {
        $this->guard(); $this->post();
        Database::q('UPDATE crm_campana_envios SET enviado=1, enviado_at=NOW() WHERE id=?', [(int)($_POST['id'] ?? 0)]);
        $this->json(['ok' => true]);
    }

    /* ---------- Encuestas ---------- */
    public function encuestas() {
        $this->guard();
        $res = Database::all("SELECT e.*, o.codigo, c.nombre cliente FROM crm_encuestas e JOIN ordenes o ON o.id=e.orden_id LEFT JOIN clientes c ON c.id=e.cliente_id WHERE e.puntaje IS NOT NULL ORDER BY e.respondido_at DESC LIMIT 50");
        $st = Database::row("SELECT COUNT(*) n, ROUND(AVG(puntaje),2) prom, SUM(puntaje>=4) sat FROM crm_encuestas WHERE puntaje IS NOT NULL");
        $dist = array_fill(1, 5, 0);
        foreach (Database::all('SELECT puntaje, COUNT(*) n FROM crm_encuestas WHERE puntaje IS NOT NULL GROUP BY puntaje') as $r) $dist[(int)$r['puntaje']] = (int)$r['n'];
        $cfg = crm_cfg();
        $cand = Database::all("SELECT o.id,o.codigo,o.total,o.fecha_ingreso,c.id cid,c.nombre,c.telefono FROM ordenes o JOIN clientes c ON c.id=o.cliente_id
            LEFT JOIN crm_encuestas e ON e.orden_id=o.id AND e.respondido_at IS NOT NULL
            WHERE o.pagado=1 AND o.estado IN ('terminado','entregado') AND o.fecha_ingreso >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND e.id IS NULL AND c.acepta_marketing=1 ORDER BY o.id DESC LIMIT 30");
        foreach ($cand as &$o) {
            $tok = Database::val('SELECT token FROM crm_encuestas WHERE orden_id=?', [$o['id']]);
            if (!$tok) { $tok = bin2hex(random_bytes(12)); Database::q('INSERT INTO crm_encuestas (orden_id,cliente_id,token) VALUES (?,?,?)', [$o['id'], $o['cid'], $tok]); }
            $o['enlace'] = $this->absUrl('encuesta/index/' . $tok);
            $o['wa'] = crm_wa($o['telefono'], crm_msg($cfg['msg_encuesta'], $o, ['enlace' => $o['enlace']]));
        }
        unset($o);
        $this->view('crm/encuestas', compact('res', 'st', 'dist', 'cand') + ['title' => 'CRM · Encuestas']);
    }

    /* ---------- Configuración ---------- */
    public function config() {
        $this->requireModule('configuracion');
        $this->view('crm/config', ['cfg' => crm_cfg(true), 'title' => 'CRM · Configuración']);
    }
    public function config_guardar() {
        $this->requireModule('configuracion'); $this->post();
        $f = fn($k, $d = 0) => max(0, (float)str_replace(',', '.', (string)($_POST[$k] ?? $d)));
        Database::q('INSERT INTO crm_config (id) VALUES (1) ON DUPLICATE KEY UPDATE id=id');
        Database::q('UPDATE crm_config SET programa_activo=?, monto_por_punto=?, valor_punto=?, puntos_minimo=?, puntos_desde=?, vip_visitas=?, vip_monto=?, dias_riesgo=?, dias_inactivo=?, prefijo_pais=?, msg_cumple=?, msg_riesgo=?, msg_encuesta=? WHERE id=1', [
            !empty($_POST['programa_activo']) ? 1 : 0, max(0.01, $f('monto_por_punto', 10)), $f('valor_punto'), (int)$f('puntos_minimo'), $this->fecha($_POST['puntos_desde'] ?? '') ?: date('Y-m-d'),
            max(1, (int)$f('vip_visitas', 10)), $f('vip_monto'), max(1, (int)$f('dias_riesgo', 45)), max(2, (int)$f('dias_inactivo', 90)), preg_replace('/\D/', '', $_POST['prefijo_pais'] ?? '51'),
            trim($_POST['msg_cumple'] ?? '') ?: null, trim($_POST['msg_riesgo'] ?? '') ?: null, trim($_POST['msg_encuesta'] ?? '') ?: null]);
        if ((int)$f('dias_inactivo') <= (int)$f('dias_riesgo')) Database::q('UPDATE crm_config SET dias_inactivo=dias_riesgo+30 WHERE id=1');
        crm_cfg(true); log_action('crm', 'Configuración CRM actualizada');
        flash('success', 'Configuración del CRM guardada.'); $this->redirect('crm/config');
    }
}
