<?php
/** Controlador CRUD genérico dirigido por configuración. */
abstract class CrudController extends Controller {
    abstract protected function config(): array;

    protected function cfg(): array {
        $cfg = $this->config();
        $cfg['route'] = $cfg['route'] ?? $cfg['module'];
        return $cfg + [
            'pk' => 'id', 'icon' => 'bi-list', 'order' => 't.id DESC', 'select' => null, 'search' => true,
            'extra_form' => null, 'can_delete' => true, 'subtitle' => '', 'new_label' => null,
        ];
    }
    protected function lookup(array $f): array {
        if (isset($f['options'])) return $f['options'];
        if (isset($f['table'])) {
            $lab = $f['label_col'] ?? 'nombre';
            $rows = Database::all("SELECT {$f['value_col']} v, $lab l FROM {$f['table']} " . ($f['where'] ?? '') . " ORDER BY l", []);
            return array_column($rows, 'l', 'v');
        }
        return [];
    }
    protected function beforeSave(array $data, ?int $id, array &$errors): array { return $data; }
    protected function afterSave(int $id, bool $isNew): void {}
    protected function formData(?array $row): array { return []; }

    public function index() {
        $c = $this->cfg();
        $this->requireModule($c['module']);
        $sql = $c['select'] ?: "SELECT t.* FROM {$c['table']} t";
        $rows = Database::all("$sql ORDER BY {$c['order']} LIMIT 1000");
        $this->view('crud/index', ['c' => $c, 'rows' => $rows, 'title' => $c['title']]);
    }

    public function crear() { $this->form(null); }
    public function editar($id = 0) {
        $c = $this->cfg();
        $this->requireModule($c['module']);
        $row = Database::row("SELECT * FROM {$c['table']} WHERE {$c['pk']}=?", [(int)$id]);
        if (!$row) { flash('danger', 'Registro no encontrado.'); $this->redirect($c['route']); }
        $this->form($row);
    }
    private function form(?array $row) {
        $c = $this->cfg();
        $this->requireModule($c['module']);
        $opts = [];
        foreach ($c['fields'] as $n => $f) if (($f['type'] ?? '') === 'select') $opts[$n] = $this->lookup($f);
        $old = $_SESSION['old'] ?? null; unset($_SESSION['old']);
        if ($old) $row = array_merge($row ?? [], $old);
        $this->view('crud/form', ['c' => $c, 'row' => $row, 'opts' => $opts, 'extra' => $this->formData($row),
            'title' => ($row && isset($row[$c['pk']]) ? 'Editar ' : 'Nuevo ') . $c['singular']]);
    }

    public function guardar() {
        $c = $this->cfg();
        $this->requireModule($c['module']);
        if (!$this->isPost()) $this->redirect($c['route']);
        csrf_check();
        $id = (int)($_POST['_id'] ?? 0);
        $data = []; $errors = [];
        foreach ($c['fields'] as $n => $f) {
            $t = $f['type'] ?? 'text';
            if ($t === 'static') continue;
            $v = $_POST[$n] ?? null;
            if ($t === 'switch') $v = empty($v) ? 0 : 1;
            elseif ($t === 'number' || $t === 'money') $v = ($v === '' || $v === null) ? 0 : (float)str_replace(',', '.', $v);
            elseif ($t === 'datetime') $v = $v ? str_replace('T', ' ', $v) . ':00' : null;
            elseif ($t === 'password') { if ($v === '' || $v === null) continue; }
            else { $v = trim((string)$v); if ($v === '' && empty($f['required'])) $v = null; }
            if (!empty($f['required']) && ($v === null || $v === '')) $errors[] = $f['label'] . ' es obligatorio.';
            if ($t === 'select' && $v !== null && $v !== '') {
                if (!array_key_exists($v, $this->lookup($f))) $errors[] = $f['label'] . ' no es válido.';
            }
            if ($t === 'email' && $v && !filter_var($v, FILTER_VALIDATE_EMAIL)) $errors[] = 'Correo inválido.';
            $data[$n] = $v;
        }
        $data = $this->beforeSave($data, $id ?: null, $errors);
        if ($errors) {
            $_SESSION['old'] = $_POST;
            flash('danger', implode('<br>', array_map('e', $errors)));
            $this->redirect($c['route'] . ($id ? '/editar/' . $id : '/crear'));
        }
        try {
            if ($id) {
                $set = implode(',', array_map(fn($k) => "`$k`=?", array_keys($data)));
                Database::q("UPDATE {$c['table']} SET $set WHERE {$c['pk']}=?", array_merge(array_values($data), [$id]));
                $this->afterSave($id, false);
                log_action($c['module'], "Editó {$c['singular']} #$id");
            } else {
                $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($data)));
                $ph = implode(',', array_fill(0, count($data), '?'));
                Database::q("INSERT INTO {$c['table']} ($cols) VALUES ($ph)", array_values($data));
                $id = (int)Database::id();
                $this->afterSave($id, true);
                log_action($c['module'], "Creó {$c['singular']} #$id");
            }
        } catch (PDOException $e) {
            $_SESSION['old'] = $_POST;
            flash('danger', 'No se pudo guardar: ' . e($e->getMessage()));
            $this->redirect($c['route'] . ($id ? '/editar/' . $id : '/crear'));
        }
        flash('success', $c['singular'] . ' guardado correctamente.');
        $this->redirect($c['route']);
    }

    public function eliminar() {
        $c = $this->cfg();
        $this->requireModule($c['module']);
        if (!$this->isPost() || !$c['can_delete']) $this->redirect($c['route']);
        csrf_check();
        $id = (int)($_POST['id'] ?? 0);
        try {
            Database::q("DELETE FROM {$c['table']} WHERE {$c['pk']}=?", [$id]);
            log_action($c['module'], "Eliminó {$c['singular']} #$id");
            flash('success', $c['singular'] . ' eliminado.');
        } catch (PDOException $e) {
            flash('warning', 'No se puede eliminar porque tiene registros relacionados. Puedes desactivarlo editando el registro.');
        }
        $this->redirect($c['route']);
    }
}
