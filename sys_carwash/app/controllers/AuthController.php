<?php
class AuthController extends Controller {
    public function index() { $this->login(); }

    public function login() {
        if (auth_user()) $this->redirect('dashboard');
        $error = null;
        if ($this->isPost()) {
            csrf_check();
            $_SESSION['intentos'] = $_SESSION['intentos'] ?? 0;
            if (($_SESSION['bloqueo'] ?? 0) > time()) {
                $error = 'Demasiados intentos. Espera ' . ($_SESSION['bloqueo'] - time()) . ' segundos.';
            } else {
                $u = Database::row('SELECT u.*, r.nombre rol, r.permisos FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE u.usuario=? LIMIT 1', [trim($_POST['usuario'] ?? '')]);
                if ($u && $u['estado'] && password_verify($_POST['password'] ?? '', $u['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['intentos'] = 0;
                    $_SESSION['user'] = ['id' => $u['id'], 'nombre' => $u['nombre'], 'usuario' => $u['usuario'], 'rol' => $u['rol'], 'permisos' => $u['permisos']];
                    Database::q('UPDATE usuarios SET ultimo_acceso=NOW() WHERE id=?', [$u['id']]);
                    log_action('login', 'Inicio de sesión');
                    $this->redirect('dashboard');
                }
                $_SESSION['intentos']++;
                if ($_SESSION['intentos'] >= 5) { $_SESSION['bloqueo'] = time() + 60; $_SESSION['intentos'] = 0; }
                $error = $u && !$u['estado'] ? 'Usuario desactivado.' : 'Usuario o contraseña incorrectos.';
            }
        }
        $this->view('auth/login', ['error' => $error, 'emp' => empresa()], 'none');
    }

    public function logout() {
        log_action('logout', 'Cierre de sesión');
        $_SESSION = [];
        session_destroy();
        header('Location: ' . url('auth/login'));
        exit;
    }
}
