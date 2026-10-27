<?php
class PerfilController extends Controller {
    public function index() {
        $this->requireLogin();
        $u = Database::row('SELECT id,nombre,usuario,email,ultimo_acceso FROM usuarios WHERE id=?', [auth_user()['id']]);
        $this->view('perfil/index', ['u' => $u, 'title' => 'Mi perfil']);
    }
    public function guardar() {
        $this->requireLogin();
        if (!$this->isPost()) $this->redirect('perfil');
        csrf_check();
        $id = auth_user()['id'];
        $nombre = trim($_POST['nombre'] ?? ''); $email = trim($_POST['email'] ?? '');
        if ($nombre === '' || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) { flash('danger', 'Nombre o correo inválido.'); $this->redirect('perfil'); }
        Database::q('UPDATE usuarios SET nombre=?, email=? WHERE id=?', [$nombre, $email ?: null, $id]);
        $_SESSION['user']['nombre'] = $nombre;
        if (($_POST['nueva'] ?? '') !== '') {
            $hash = Database::val('SELECT password FROM usuarios WHERE id=?', [$id]);
            if (!password_verify($_POST['actual'] ?? '', $hash)) { flash('danger', 'La contraseña actual es incorrecta.'); $this->redirect('perfil'); }
            if (strlen($_POST['nueva']) < 6 || $_POST['nueva'] !== ($_POST['confirmar'] ?? '')) { flash('danger', 'La nueva contraseña debe tener 6+ caracteres y coincidir con la confirmación.'); $this->redirect('perfil'); }
            Database::q('UPDATE usuarios SET password=? WHERE id=?', [password_hash($_POST['nueva'], PASSWORD_DEFAULT), $id]);
            log_action('perfil', 'Cambió su contraseña');
        }
        flash('success', 'Perfil actualizado.');
        $this->redirect('perfil');
    }
}
