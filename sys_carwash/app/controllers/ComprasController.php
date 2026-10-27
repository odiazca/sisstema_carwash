<?php
class ComprasController extends Controller {
    public function index() {
        $this->requireModule('productos');
        $rows = Database::all('SELECT c.*, p.nombre proveedor, u.nombre usuario, (SELECT COUNT(*) FROM compra_detalle d WHERE d.compra_id=c.id) items, c.total-c.pagado saldo FROM compras c LEFT JOIN proveedores p ON p.id=c.proveedor_id LEFT JOIN usuarios u ON u.id=c.usuario_id ORDER BY c.id DESC LIMIT 300');
        $this->view('compras/index', ['rows' => $rows, 'title' => 'Compras']);
    }
    public function nueva() {
        $this->requireModule('productos');
        $this->view('compras/nueva', [
            'proveedores' => Database::all('SELECT id,nombre FROM proveedores WHERE estado=1 ORDER BY nombre'),
            'productos' => Database::all('SELECT id,nombre,unidad,precio_compra FROM productos WHERE estado=1 ORDER BY nombre'),
            'title' => 'Nueva compra']);
    }
    public function ver($id = 0) {
        $this->requireModule('productos');
        $c = Database::row('SELECT c.*, p.nombre proveedor FROM compras c LEFT JOIN proveedores p ON p.id=c.proveedor_id WHERE c.id=?', [(int)$id]);
        if (!$c) $this->redirect('compras');
        $items = Database::all('SELECT d.*, p.nombre, p.unidad FROM compra_detalle d JOIN productos p ON p.id=d.producto_id WHERE d.compra_id=?', [$c['id']]);
        $pagos = Database::all('SELECT x.*, u.nombre usuario FROM compra_pagos x LEFT JOIN usuarios u ON u.id=x.usuario_id WHERE x.compra_id=? ORDER BY x.id', [$c['id']]);
        $this->view('compras/ver', ['pagos' => $pagos, 'c' => $c, 'items' => $items, 'title' => 'Compra #' . $c['id']]);
    }
    public function guardar() {
        $this->requireModule('productos');
        if (!$this->isPost()) $this->redirect('compras');
        csrf_check();
        $items = array_values(array_filter($_POST['items'] ?? [], fn($i) => (int)($i['producto_id'] ?? 0) > 0 && (float)($i['cantidad'] ?? 0) > 0));
        if (!$items) { flash('danger', 'Agrega al menos un producto con cantidad.'); $this->redirect('compras/nueva'); }
        $fecha = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['fecha'] ?? '') ? $_POST['fecha'] : date('Y-m-d');
        $pdo = Database::pdo(); $pdo->beginTransaction();
        try {
            $total = 0; $lineas = [];
            foreach ($items as $i) {
                $pr = Database::row('SELECT * FROM productos WHERE id=?', [(int)$i['producto_id']]);
                if (!$pr) continue;
                $cant = (float)$i['cantidad']; $costo = max(0, (float)($i['costo'] ?? 0));
                $lineas[] = [$pr, $cant, $costo]; $total += round($cant * $costo, 2);
            }
            if (!$lineas) throw new RuntimeException('Productos inválidos.');
            $credito = ($_POST['condicion'] ?? '') === 'credito' && $total > 0;
            if ($credito && !(int)($_POST['proveedor_id'] ?? 0)) throw new RuntimeException('Una compra a crédito requiere proveedor.');
            $dias = max(0, min(365, (int)($_POST['dias_credito'] ?? 30)));
            Database::q('INSERT INTO compras (proveedor_id,fecha,nro_doc,condicion,vence,pagado,total,notas,usuario_id) VALUES (?,?,?,?,?,?,?,?,?)',
                [(int)($_POST['proveedor_id'] ?? 0) ?: null, $fecha, trim($_POST['nro_doc'] ?? '') ?: null, $credito ? 'credito' : 'contado', $credito ? date('Y-m-d', strtotime($fecha . " +$dias days")) : null, $credito ? 0 : $total, $total, trim($_POST['notas'] ?? '') ?: null, auth_user()['id']]);
            $cid = (int)Database::id();
            foreach ($lineas as [$pr, $cant, $costo]) {
                Database::q('INSERT INTO compra_detalle (compra_id,producto_id,cantidad,costo,subtotal) VALUES (?,?,?,?,?)', [$cid, $pr['id'], $cant, $costo, round($cant * $costo, 2)]);
                Database::q('UPDATE productos SET stock=stock+?, precio_compra=IF(?>0,?,precio_compra) WHERE id=?', [$cant, $costo, $costo, $pr['id']]);
                Database::q("INSERT INTO movimientos_inventario (producto_id,tipo,cantidad,motivo,usuario_id) VALUES (?, 'entrada', ?, ?, ?)", [$pr['id'], $cant, "Compra #$cid", auth_user()['id']]);
            }
            if (!$credito && !empty($_POST['registrar_gasto']) && $total > 0)
                Database::q("INSERT INTO gastos (fecha,categoria,concepto,monto,usuario_id) VALUES (?, 'Insumos', ?, ?, ?)", [$fecha, "Compra #$cid" . (!empty($_POST['nro_doc']) ? ' · ' . trim($_POST['nro_doc']) : ''), $total, auth_user()['id']]);
            $pdo->commit();
            log_action('compras', "Compra #$cid por " . num($total));
            flash('success', "Compra #$cid registrada: stock actualizado." . ($credito ? ' Quedó como <b>cuenta por pagar</b> (vence ' . fdate(date('Y-m-d', strtotime($fecha . " +$dias days"))) . ').' : ''));
            $this->redirect('compras/ver/' . $cid);
        } catch (Throwable $e) { $pdo->rollBack(); flash('danger', e($e->getMessage())); $this->redirect('compras/nueva'); }
    }
}
