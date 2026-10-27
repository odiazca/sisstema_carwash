<?php
class UsuariosController extends CrudController {
    protected function config(): array {
        return [
            'table' => 'usuarios', 'module' => 'usuarios', 'title' => 'Usuarios y roles', 'singular' => 'Usuario', 'icon' => 'bi-shield-lock',
            'subtitle' => 'Accesos al sistema según rol',
            'select' => "SELECT t.*, r.nombre rol FROM usuarios t JOIN roles r ON r.id=t.rol_id",
            'columns' => ['nombre' => ['Nombre', 'strong'], 'usuario' => ['Usuario'], 'rol' => ['Rol'], 'email' => ['Correo'], 'ultimo_acceso' => ['Último acceso', 'datetime'], 'estado' => ['Estado', 'estado']],
            'fields' => [
                'nombre' => ['label' => 'Nombre completo', 'required' => true, 'col' => 6], 'usuario' => ['label' => 'Usuario', 'required' => true, 'col' => 3, 'maxlength' => 50],
                'rol_id' => ['label' => 'Rol', 'type' => 'select', 'table' => 'roles', 'value_col' => 'id', 'required' => true, 'col' => 3],
                'email' => ['label' => 'Correo', 'type' => 'email', 'col' => 6], 'password' => ['label' => 'Contraseña', 'type' => 'password', 'col' => 3],
                'estado' => ['label' => 'Activo', 'type' => 'switch', 'col' => 3],
            ],
        ];
    }
    protected function beforeSave(array $d, ?int $id, array &$errors): array {
        $dup = Database::val('SELECT id FROM usuarios WHERE usuario=? AND id<>?', [$d['usuario'], $id ?? 0]);
        if ($dup) $errors[] = 'Ese nombre de usuario ya existe.';
        if (isset($d['password'])) {
            if (strlen($d['password']) < 6) $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
            $d['password'] = password_hash($d['password'], PASSWORD_DEFAULT);
        }
        if ($id && $id == auth_user()['id']) $d['estado'] = 1;
        return $d;
    }
    public function eliminar() {
        if ($this->isPost() && (int)($_POST['id'] ?? 0) === (int)(auth_user()['id'] ?? -1)) {
            flash('warning', 'No puedes eliminar tu propio usuario.'); $this->redirect('usuarios');
        }
        parent::eliminar();
    }
}
