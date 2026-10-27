<?php
require_once APP_PATH . '/core/Ia.php';

/** Chat con IA para consultar el sistema (solo administrador). Historial en la sesión. */
class ChatiaController extends Controller {
    public function index() {
        $this->requireModule('ia');
        $this->view('chatia/index', ['title' => 'Chat IA']);
    }

    public function api() {
        $a = $_GET['a'] ?? (ia_body()['a'] ?? 'estado');
        ia_guard($a !== 'estado');
        $b = ia_body();
        $hist = $_SESSION['chat'] ?? [];
        try { ia_instalar(); } catch (Throwable $e) { ia_fail('No se pudieron preparar las vistas: ' . $e->getMessage(), 500); }

        switch ($a) {
        case 'estado':
            $c = ia_cfg();
            ia_out(['listo' => ia_listo($c), 'proveedor' => IA_PRESETS[$c['proveedor']]['nombre'], 'empresa' => empresa()['nombre'],
                'historial' => array_map(fn($h) => ['q' => $h['q'], 'a' => $h['a']], $hist)]);

        case 'enviar':
            $msg = trim((string)($b['mensaje'] ?? ''));
            if (mb_strlen($msg) < 2) ia_fail('Escribe tu mensaje.');
            if (mb_strlen($msg) > 400) ia_fail('El mensaje es demasiado largo (máx. 400 caracteres).');
            $now = time(); $_SESSION['chat_t'] = array_values(array_filter($_SESSION['chat_t'] ?? [], fn($t) => $t > $now - 600));
            if (count($_SESSION['chat_t']) >= 40) ia_fail('Demasiados mensajes seguidos. Espera unos minutos.', 429);
            $_SESSION['chat_t'][] = $now;
            session_write_close();
            try { $r = ia_chat($msg, $hist); } catch (Throwable $e) { ia_fail($e->getMessage()); }
            session_start();
            $hist[] = ['q' => $msg, 'sql' => $r['sql'] ?? null, 'a' => mb_substr($r['texto'], 0, 800)];
            $_SESSION['chat'] = array_slice($hist, -12);
            session_write_close();
            if (!empty($r['sql'])) log_action('ia', 'Chat: ' . mb_substr($msg, 0, 200));
            ia_out(['texto' => $r['texto'], 'tabla' => $r['tabla'] ?? null, 'sql' => $r['sql'] ?? null]);

        case 'limpiar':
            $_SESSION['chat'] = []; ia_out([]);
        }
        ia_fail('Acción no válida');
    }
}
