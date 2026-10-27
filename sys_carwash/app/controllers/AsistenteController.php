<?php
require_once APP_PATH . '/core/Ia.php';

/** Asistente de reportes con IA (solo administrador) */
class AsistenteController extends Controller {
    public function index() {
        $this->requireModule('ia');
        $this->view('asistente/index', ['title' => 'Asistente IA']);
    }

    private function consulta(int $id, int $uid): array {
        $c = Database::row('SELECT * FROM ia_consultas WHERE id=? AND usuario_id=?', [$id, $uid]);
        if (!$c) ia_fail('Consulta no encontrada.', 404);
        return $c;
    }

    public function api() {
        $a = $_GET['a'] ?? (ia_body()['a'] ?? 'estado');
        ia_guard(!in_array($a, ['estado', 'csv', 'esquema'], true));
        $uid = (int)auth_user()['id'];
        try { ia_instalar(); } catch (Throwable $e) { ia_fail('No se pudieron preparar las vistas del asistente: ' . $e->getMessage(), 500); }
        $b = ia_body();
        session_write_close();   // no bloquear otras pestañas durante las llamadas a la IA

        switch ($a) {
        case 'estado':
            $c = ia_cfg();
            ia_out(['cfg' => ['proveedor' => $c['proveedor'], 'url' => $c['url'], 'modelo' => $c['modelo'], 'clave_puesta' => $c['clave'] !== '', 'resumen' => $c['resumen']],
                'listo' => ia_listo($c), 'presets' => IA_PRESETS,
                'favoritos' => Database::all('SELECT id,pregunta,titulo FROM ia_consultas WHERE usuario_id=? AND favorito=1 ORDER BY ultimo_uso DESC', [$uid]),
                'historial' => Database::all('SELECT id,pregunta,titulo,favorito FROM ia_consultas WHERE usuario_id=? AND favorito=0 ORDER BY ultimo_uso DESC LIMIT 15', [$uid])]);

        case 'guardar_config':
            $p = (string)($b['proveedor'] ?? '');
            if (!isset(IA_PRESETS[$p])) ia_fail('Proveedor no válido.');
            $url = trim((string)($b['url'] ?? '')); $modelo = trim((string)($b['modelo'] ?? ''));
            if (!preg_match('#^https?://[^\s]+$#i', $url)) ia_fail('La URL debe empezar por http:// o https://');
            if ($modelo === '' || mb_strlen($modelo) > 100) ia_fail('Indica el nombre del modelo.');
            ia_set('ia_proveedor', $p); ia_set('ia_url', $url); ia_set('ia_modelo', $modelo);
            ia_set('ia_resumen', !empty($b['resumen']) ? '1' : '0');
            $k = trim((string)($b['clave'] ?? ''));
            if ($k === '__borrar__') ia_set('ia_clave', '');
            elseif ($k !== '') { if (mb_strlen($k) < 8 || mb_strlen($k) > 200 || preg_match('/\s/', $k)) ia_fail('La clave no parece válida.'); ia_set('ia_clave', ia_cifrar($k)); }
            log_action('ia', "Configuró IA: $p / $modelo"); ia_out([]);

        case 'probar':
            try { $t = ia_llamar([['role' => 'user', 'content' => 'Responde solo con la palabra OK.']], 20); } catch (Throwable $e) { ia_fail($e->getMessage()); }
            ia_out(['respuesta' => mb_substr(trim($t), 0, 60)]);

        case 'preguntar':
            $pregunta = trim((string)($b['pregunta'] ?? ''));
            if (mb_strlen($pregunta) < 4) ia_fail('Escribe tu pregunta (mínimo 4 caracteres).');
            if (mb_strlen($pregunta) > 500) ia_fail('La pregunta es demasiado larga (máx. 500 caracteres).');
            session_start(); $now = time();
            $_SESSION['ia_hist'] = array_values(array_filter($_SESSION['ia_hist'] ?? [], fn($t) => $t > $now - 600));
            if (count($_SESSION['ia_hist']) >= 30) { session_write_close(); ia_fail('Demasiadas consultas seguidas. Espera unos minutos.', 429); }
            $_SESSION['ia_hist'][] = $now; session_write_close();
            $previo = is_array($b['previo'] ?? null) ? $b['previo'] : null;
            try { $r = ia_preguntar($pregunta, $previo); } catch (Throwable $e) { ia_fail($e->getMessage()); }
            if (isset($r['mensaje'])) ia_out(['mensaje' => $r['mensaje']]);
            Database::q('INSERT INTO ia_consultas (usuario_id,pregunta,titulo,sql_texto,grafico) VALUES (?,?,?,?,?)', [$uid, $pregunta, $r['titulo'], $r['sql'], json_encode($r['grafico'])]);
            $id = (int)Database::id();
            Database::q('DELETE FROM ia_consultas WHERE usuario_id=? AND favorito=0 AND id NOT IN (SELECT id FROM (SELECT id FROM ia_consultas WHERE usuario_id=? AND favorito=0 ORDER BY id DESC LIMIT 60) x)', [$uid, $uid]);
            log_action('ia', 'Consulta: ' . mb_substr($pregunta, 0, 200));
            ia_out(['id' => $id] + $r);

        case 'ejecutar':    // volver a correr una consulta guardada, sin gastar IA
            $c = $this->consulta((int)($b['id'] ?? 0), $uid);
            try { $r = ia_ejecutar($c['sql_texto']); } catch (Throwable $e) { ia_fail($e->getMessage()); }
            Database::q('UPDATE ia_consultas SET veces=veces+1, ultimo_uso=NOW() WHERE id=?', [$c['id']]);
            ia_out(['id' => (int)$c['id'], 'pregunta' => $c['pregunta'], 'titulo' => $c['titulo'] ?: $c['pregunta'], 'grafico' => json_decode((string)$c['grafico'], true) ?: ['tipo' => 'none'], 'favorito' => (int)$c['favorito']] + $r);

        case 'resumir':
            if (!ia_cfg()['resumen']) ia_fail('El resumen está desactivado.');
            $c = $this->consulta((int)($b['id'] ?? 0), $uid);
            try {
                $r = ia_ejecutar($c['sql_texto']);
                if (!$r['filas']) ia_out(['resumen' => 'La consulta no devolvió resultados para ese período.']);
                $t = ia_resumir($c['pregunta'], $r['columnas'], $r['filas']);
            } catch (Throwable $e) { ia_fail($e->getMessage()); }
            ia_out(['resumen' => $t]);

        case 'favorito':
            $c = $this->consulta((int)($b['id'] ?? 0), $uid); $v = !empty($b['valor']) ? 1 : 0;
            Database::q('UPDATE ia_consultas SET favorito=? WHERE id=?', [$v, $c['id']]); ia_out(['favorito' => $v]);

        case 'borrar':
            $c = $this->consulta((int)($b['id'] ?? 0), $uid); Database::q('DELETE FROM ia_consultas WHERE id=?', [$c['id']]); ia_out([]);

        case 'csv':
            $c = $this->consulta((int)($_GET['id'] ?? 0), $uid);
            try { $r = ia_ejecutar($c['sql_texto']); } catch (Throwable $e) { http_response_code(400); exit($e->getMessage()); }
            $name = 'consulta_' . preg_replace('/[^a-z0-9]+/i', '_', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string)($c['titulo'] ?: 'reporte'))) . '_' . date('Ymd') . '.csv';
            header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="' . $name . '"');
            $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
            $safe = fn($v) => is_string($v) && $v !== '' && strpos("=+-@\t\r", $v[0]) !== false ? "'" . $v : $v;   // evita fórmulas en Excel
            fputcsv($o, $r['columnas'], ';'); foreach ($r['filas'] as $f) fputcsv($o, array_map($safe, $f), ';');
            exit;

        case 'esquema':
            header('Content-Type: text/plain; charset=utf-8'); echo ia_esquema_texto(); exit;
        }
        ia_fail('Acción no válida');
    }
}
