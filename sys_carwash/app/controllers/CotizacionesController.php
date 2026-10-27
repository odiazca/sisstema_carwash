<?php
/** Cotizaciones: presupuesto con validez, envío por WhatsApp/impresión y conversión a orden de servicio. */
class CotizacionesController extends Controller {
    const ESTADOS = ['borrador' => 'Borrador', 'enviada' => 'Enviada', 'aceptada' => 'Aceptada', 'rechazada' => 'Rechazada', 'convertida' => 'Convertida'];
    const COLOR = ['borrador' => 'secondary', 'enviada' => 'info', 'aceptada' => 'success', 'rechazada' => 'danger', 'convertida' => 'primary'];

    public function index() {
        $this->requireModule('ordenes');
        $estado = $_GET['estado'] ?? '';
        $w = '1=1'; $p = [];
        if (isset(self::ESTADOS[$estado])) { $w = 'q.estado=?'; $p[] = $estado; }
        $rows = Database::all("SELECT q.*, c.nombre cliente FROM cotizaciones q LEFT JOIN clientes c ON c.id=q.cliente_id WHERE $w ORDER BY q.id DESC LIMIT 500", $p);
        $res = array_column(Database::all('SELECT estado, COUNT(*) n, SUM(total) t FROM cotizaciones GROUP BY estado'), null, 'estado');
        $this->view('cotizaciones/index', ['rows' => $rows, 'estado' => $estado, 'res' => $res, 'title' => 'Cotizaciones']);
    }

    public function nueva() {
        $this->requireModule('ordenes');
        $pr = Database::all('SELECT servicio_id s, tipo_vehiculo_id t, precio p FROM servicio_precios'); $precios = [];
        foreach ($pr as $r) $precios[$r['s']][$r['t']] = (float)$r['p'];
        $this->view('cotizaciones/nueva', [
            'servicios' => Database::all('SELECT id,nombre,precio FROM servicios WHERE estado=1 ORDER BY nombre'), 'precios' => $precios,
            'productos' => Database::all('SELECT id,nombre,precio_venta FROM productos WHERE estado=1 AND se_vende=1 ORDER BY nombre'),
            'clientes' => Database::all('SELECT id,nombre,telefono FROM clientes WHERE estado=1 ORDER BY nombre'),
            'tipos' => Database::all('SELECT id,nombre FROM tipos_vehiculo WHERE estado=1 ORDER BY id'), 'title' => 'Nueva cotización']);
    }

    public function guardar() {
        $this->requireModule('ordenes');
        if (!$this->isPost()) $this->redirect('cotizaciones');
        csrf_check();
        $items = $_POST['items'] ?? [];
        if (!is_array($items) || !$items) { flash('danger', 'Agrega al menos un servicio o producto.'); $this->redirect('cotizaciones/nueva'); }
        $cid = (int)($_POST['cliente_id'] ?? 0) ?: null; $tipoV = (int)($_POST['tipo_vehiculo_id'] ?? 0) ?: null;
        $nombre = trim($_POST['nombre'] ?? ''); $tel = trim($_POST['telefono'] ?? '');
        if ($cid) { $c = Database::row('SELECT nombre,telefono FROM clientes WHERE id=?', [$cid]); if ($c) { $nombre = $nombre ?: $c['nombre']; $tel = $tel ?: (string)$c['telefono']; } else $cid = null; }
        $pdo = Database::pdo(); $pdo->beginTransaction();
        try {
            $lineas = []; $sub = 0;
            foreach ($items as $it) {
                $cant = max(0.01, (float)($it['cant'] ?? 1)); $id = (int)($it['id'] ?? 0);
                if (($it['tipo'] ?? '') === 'servicio') {
                    $s = Database::row('SELECT * FROM servicios WHERE id=?', [$id]); if (!$s) continue; $precio = (float)$s['precio'];
                    if ($tipoV) { $pt = Database::val('SELECT precio FROM servicio_precios WHERE servicio_id=? AND tipo_vehiculo_id=?', [$id, $tipoV]); if ($pt !== null) $precio = (float)$pt; }
                    $lineas[] = ['servicio', $id, $s['nombre'], $cant, $precio];
                } else {
                    $pr = Database::row('SELECT * FROM productos WHERE id=?', [$id]); if (!$pr) continue;
                    $lineas[] = ['producto', $id, $pr['nombre'], $cant, (float)$pr['precio_venta']];
                }
            }
            if (!$lineas) throw new RuntimeException('No hay líneas válidas.');
            foreach ($lineas as $l) $sub += round($l[3] * $l[4], 2);
            $desc = min($sub, max(0, (float)($_POST['descuento'] ?? 0)));
            $cfg = empresa(); $rate = (float)$cfg['impuesto_porcentaje'] / 100; $base = $sub - $desc;
            if ($cfg['precios_incluyen_impuesto']) { $total = $base; $imp = round($base - $base / (1 + $rate), 2); } else { $imp = round($base * $rate, 2); $total = round($base + $imp, 2); }
            $dias = max(1, min(365, (int)($_POST['validez'] ?? 7)));
            Database::q('INSERT INTO cotizaciones (codigo,cliente_id,nombre,telefono,placa,tipo_vehiculo_id,vence,subtotal,descuento,impuesto,total,observaciones,usuario_id) VALUES (?,?,?,?,?,?,DATE_ADD(CURDATE(), INTERVAL ? DAY),?,?,?,?,?,?)', [
                uniqid('C'), $cid, mb_substr($nombre, 0, 150) ?: null, mb_substr($tel, 0, 30) ?: null, strtoupper(mb_substr(trim($_POST['placa'] ?? ''), 0, 20)) ?: null, $tipoV, $dias, $sub, $desc, $imp, $total, mb_substr(trim($_POST['observaciones'] ?? ''), 0, 500) ?: null, auth_user()['id']]);
            $qid = (int)Database::id(); $cod = 'COT-' . str_pad((string)$qid, 5, '0', STR_PAD_LEFT);
            Database::q('UPDATE cotizaciones SET codigo=? WHERE id=?', [$cod, $qid]);
            foreach ($lineas as [$t, $iid, $n, $c, $pre]) Database::q('INSERT INTO cotizacion_detalle (cotizacion_id,tipo,item_id,descripcion,cantidad,precio,subtotal) VALUES (?,?,?,?,?,?,?)', [$qid, $t, $iid, $n, $c, $pre, round($c * $pre, 2)]);
            $pdo->commit(); log_action('ordenes', "Creó cotización $cod");
            flash('success', "Cotización <b>$cod</b> creada."); $this->redirect('cotizaciones/ver/' . $qid);
        } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); flash('danger', e($e->getMessage())); $this->redirect('cotizaciones/nueva'); }
    }

    private function cargar(int $id): array {
        $q = Database::row('SELECT q.*, t.nombre tipo_veh, o.codigo orden FROM cotizaciones q LEFT JOIN tipos_vehiculo t ON t.id=q.tipo_vehiculo_id LEFT JOIN ordenes o ON o.id=q.orden_id WHERE q.id=?', [$id]);
        if (!$q) { flash('danger', 'Cotización no encontrada.'); $this->redirect('cotizaciones'); }
        $q['items'] = Database::all('SELECT * FROM cotizacion_detalle WHERE cotizacion_id=? ORDER BY id', [$id]);
        $q['vencida'] = $q['vence'] && $q['vence'] < date('Y-m-d') && in_array($q['estado'], ['borrador', 'enviada']);
        return $q;
    }

    public function ver($id = 0) { $this->requireModule('ordenes'); $q = $this->cargar((int)$id); $this->view('cotizaciones/ver', ['q' => $q, 'title' => $q['codigo']]); }

    public function imprimir($id = 0) { $this->requireModule('ordenes'); $q = $this->cargar((int)$id); $this->view('cotizaciones/imprimir', ['q' => $q, 'emp' => empresa()], 'none'); }

    public function estado() {
        $this->requireModule('ordenes');
        if (!$this->isPost()) $this->redirect('cotizaciones');
        csrf_check();
        $q = $this->cargar((int)($_POST['id'] ?? 0)); $n = $_POST['estado'] ?? '';
        if (in_array($n, ['enviada', 'aceptada', 'rechazada'], true) && $q['estado'] !== 'convertida') { Database::q('UPDATE cotizaciones SET estado=? WHERE id=?', [$n, $q['id']]); log_action('ordenes', "Cotización {$q['codigo']} -> $n"); flash('success', 'Estado actualizado.'); }
        $this->redirect('cotizaciones/ver/' . $q['id']);
    }

    /** Datos para precargar "Nueva orden". */
    public static function paraOrden(int $id): ?array {
        $q = Database::row("SELECT id,cliente_id,placa,tipo_vehiculo_id FROM cotizaciones WHERE id=? AND estado<>'convertida'", [$id]);
        if (!$q) return null;
        $q['items'] = array_map(fn($d) => ['tipo' => $d['tipo'], 'id' => (int)$d['item_id'], 'cant' => (float)$d['cantidad']], Database::all('SELECT tipo,item_id,cantidad FROM cotizacion_detalle WHERE cotizacion_id=?', [$id]));
        return $q;
    }

    public function eliminar() {
        $this->requireModule('ordenes');
        if (!$this->isPost()) $this->redirect('cotizaciones');
        csrf_check();
        Database::q("DELETE FROM cotizaciones WHERE id=? AND estado<>'convertida'", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Cotización eliminada.'); $this->redirect('cotizaciones');
    }
}
