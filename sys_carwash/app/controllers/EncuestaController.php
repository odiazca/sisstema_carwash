<?php
/** Encuesta pública de satisfacción (acceso por token, sin iniciar sesión). */
class EncuestaController extends Controller {
    private function cargar(string $token): ?array {
        if (!preg_match('/^[a-f0-9]{16,40}$/', $token)) return null;
        return Database::row('SELECT e.*, o.codigo, c.nombre FROM crm_encuestas e JOIN ordenes o ON o.id=e.orden_id LEFT JOIN clientes c ON c.id=e.cliente_id WHERE e.token=?', [$token]);
    }
    public function index($token = '') {
        $e = $this->cargar((string)$token);
        $this->view('crm/encuesta_publica', ['e' => $e, 'token' => $token, 'gracias' => !empty($_GET['ok']), 'title' => 'Tu opinión'], 'none');
    }
    public function responder($token = '') {
        if (!$this->isPost()) $this->redirect('encuesta/index/' . $token);
        csrf_check();
        $e = $this->cargar((string)$token);
        $p = (int)($_POST['puntaje'] ?? 0);
        if ($e && !$e['respondido_at'] && $p >= 1 && $p <= 5) {
            Database::q('UPDATE crm_encuestas SET puntaje=?, comentario=?, respondido_at=NOW() WHERE id=?', [$p, mb_substr(trim($_POST['comentario'] ?? ''), 0, 500) ?: null, $e['id']]);
        }
        $this->redirect('encuesta/index/' . $token . '?ok=1');
    }
}
