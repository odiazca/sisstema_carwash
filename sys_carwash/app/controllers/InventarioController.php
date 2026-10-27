<?php
class InventarioController extends Controller {
    const TIPOS = ['entrada' => 'Entrada (compra / reposición)', 'salida' => 'Salida (consumo / merma)', 'ajuste' => 'Ajuste (fijar stock real)'];

    public function index() {
        $this->requireModule('productos');
        $pid = (int)($_GET['producto'] ?? 0);
        $w = $pid ? 'WHERE m.producto_id=' . $pid : '';
        $rows = Database::all("SELECT m.*, p.nombre producto, p.unidad, u.nombre usuario FROM movimientos_inventario m JOIN productos p ON p.id=m.producto_id LEFT JOIN usuarios u ON u.id=m.usuario_id $w ORDER BY m.id DESC LIMIT 300");
        $productos = Database::all('SELECT id,nombre,stock,unidad FROM productos WHERE estado=1 ORDER BY nombre');
        $this->view('inventario/index', ['rows' => $rows, 'productos' => $productos, 'pid' => $pid, 'title' => 'Movimientos de inventario']);
    }

    public function registrar() {
        $this->requireModule('productos');
        if (!$this->isPost()) $this->redirect('inventario');
        csrf_check();
        $id = (int)($_POST['producto_id'] ?? 0); $tipo = $_POST['tipo'] ?? '';
        $cant = (float)str_replace(',', '.', $_POST['cantidad'] ?? 0);
        $p = Database::row('SELECT * FROM productos WHERE id=?', [$id]);
        if (!$p || !isset(self::TIPOS[$tipo]) || $cant < 0 || ($tipo !== 'ajuste' && $cant <= 0)) { flash('danger', 'Datos del movimiento inválidos.'); $this->redirect('inventario'); }
        if ($tipo === 'salida' && $cant > $p['stock']) { flash('danger', 'La salida supera el stock disponible (' . num($p['stock'], 2) . ').'); $this->redirect('inventario'); }
        $delta = $tipo === 'entrada' ? $cant : ($tipo === 'salida' ? -$cant : $cant - (float)$p['stock']);
        $pdo = Database::pdo(); $pdo->beginTransaction();
        try {
            Database::q('UPDATE productos SET stock=stock+? WHERE id=?', [$delta, $id]);
            if ($tipo === 'entrada' && !empty($_POST['precio_compra']) && (float)$_POST['precio_compra'] > 0)
                Database::q('UPDATE productos SET precio_compra=? WHERE id=?', [(float)$_POST['precio_compra'], $id]);
            Database::q('INSERT INTO movimientos_inventario (producto_id,tipo,cantidad,motivo,usuario_id) VALUES (?,?,?,?,?)',
                [$id, $tipo, $tipo === 'ajuste' ? abs($delta) : $cant, trim($_POST['motivo'] ?? '') ?: ucfirst($tipo) . ' manual', auth_user()['id']]);
            $pdo->commit();
        } catch (Throwable $e) { $pdo->rollBack(); flash('danger', e($e->getMessage())); $this->redirect('inventario'); }
        log_action('inventario', ucfirst($tipo) . " {$p['nombre']}: " . num($delta, 2));
        flash('success', 'Movimiento registrado. Nuevo stock de <b>' . e($p['nombre']) . '</b>: ' . num($p['stock'] + $delta, 2));
        $this->redirect('inventario');
    }
}
