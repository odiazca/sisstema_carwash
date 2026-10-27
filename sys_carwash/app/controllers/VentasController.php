<?php
/** Venta rápida de mostrador (POS): productos sin vehículo. Se registra como orden tipo 'venta' ya entregada. */
class VentasController extends Controller {
    public function index() {
        $this->requireModule('ordenes');
        $productos = Database::all('SELECT id,codigo,nombre,precio_venta,stock,unidad FROM productos WHERE estado=1 AND se_vende=1 ORDER BY nombre');
        $clientes = Database::all('SELECT id,nombre,nro_doc FROM clientes WHERE estado=1 ORDER BY nombre');
        $hoy = Database::all("SELECT o.id,o.codigo,o.total,o.estado,o.fecha_ingreso,o.metodo_pago,c.nombre cliente FROM ordenes o LEFT JOIN clientes c ON c.id=o.cliente_id
            WHERE o.tipo='venta' AND DATE(o.fecha_ingreso)=CURDATE() ORDER BY o.id DESC LIMIT 15");
        $tot = Database::row("SELECT COUNT(*) n, COALESCE(SUM(total),0) t FROM ordenes WHERE tipo='venta' AND estado<>'cancelado' AND DATE(fecha_ingreso)=CURDATE()");
        $this->view('ventas/index', compact('productos', 'clientes', 'hoy', 'tot') + ['title' => 'Venta rápida']);
    }

    public function guardar() {
        $this->requireModule('ordenes');
        if (!$this->isPost()) $this->redirect('ventas');
        csrf_check();
        $items = $_POST['items'] ?? [];
        if (!is_array($items) || !$items) { flash('danger', 'Agrega al menos un producto.'); $this->redirect('ventas'); }
        $pdo = Database::pdo(); $pdo->beginTransaction();
        try {
            $lineas = []; $subtotal = 0;
            foreach ($items as $it) {
                $cant = max(0.01, (float)($it['cant'] ?? 1)); $id = (int)($it['id'] ?? 0);
                $pr = Database::row('SELECT * FROM productos WHERE id=? AND estado=1 AND se_vende=1 FOR UPDATE', [$id]); if (!$pr) continue;
                if ($pr['stock'] < $cant) throw new RuntimeException("Stock insuficiente de {$pr['nombre']} (disponible: " . num($pr['stock'], 2) . ').');
                $lineas[] = [$id, $pr['nombre'], $cant, (float)$pr['precio_venta']];
                $subtotal += round($cant * (float)$pr['precio_venta'], 2);
            }
            if (!$lineas) throw new RuntimeException('No hay productos válidos.');
            $desc = min($subtotal, max(0, (float)($_POST['descuento'] ?? 0)));
            $cfg = empresa(); $rate = (float)$cfg['impuesto_porcentaje'] / 100; $base = $subtotal - $desc;
            if ($cfg['precios_incluyen_impuesto']) { $total = $base; $imp = round($base - $base / (1 + $rate), 2); }
            else { $imp = round($base * $rate, 2); $total = round($base + $imp, 2); }
            $metodo = isset(OrdenesController::PAGOS[$_POST['metodo_pago'] ?? '']) ? $_POST['metodo_pago'] : 'efectivo';
            $cid = (int)($_POST['cliente_id'] ?? 0) ?: null;
            Database::q("INSERT INTO ordenes (codigo,tipo,cliente_id,usuario_id,estado,subtotal,descuento,impuesto,total,pagado,fecha_fin,observaciones)
                VALUES (?, 'venta', ?, ?, 'entregado', ?, ?, ?, ?, 0, NOW(), ?)", [uniqid('V'), $cid, auth_user()['id'], $subtotal, $desc, $imp, $total, trim($_POST['observaciones'] ?? '') ?: null]);
            $oid = (int)Database::id(); $codigo = 'V' . str_pad((string)$oid, 6, '0', STR_PAD_LEFT);
            Database::q('UPDATE ordenes SET codigo=? WHERE id=?', [$codigo, $oid]);
            foreach ($lineas as [$iid, $nom, $cant, $precio]) {
                Database::q("INSERT INTO orden_detalle (orden_id,tipo,item_id,descripcion,cantidad,precio,subtotal) VALUES (?, 'producto', ?, ?, ?, ?, ?)", [$oid, $iid, $nom, $cant, $precio, round($cant * $precio, 2)]);
                Database::q('UPDATE productos SET stock=stock-? WHERE id=?', [$cant, $iid]);
                Database::q("INSERT INTO movimientos_inventario (producto_id,tipo,cantidad,motivo,usuario_id) VALUES (?, 'salida', ?, ?, ?)", [$iid, $cant, "Venta $codigo", auth_user()['id']]);
            }
            if ($total <= 0.004) Database::q('UPDATE ordenes SET pagado=1, fecha_pago=NOW() WHERE id=?', [$oid]);
            else orden_registrar_pago(['id' => $oid, 'total' => $total], $total, $metodo, 0, trim($_POST['referencia'] ?? '') ?: null, (int)auth_user()['id']);
            $pdo->commit();
            log_action('ordenes', "Venta rápida $codigo (" . num($total) . ')');
            $rec = (float)($_POST['recibido'] ?? 0);
            $vuelto = ($metodo === 'efectivo' && $rec > $total) ? ' Vuelto: <b>' . money($rec - $total) . '</b>.' : '';
            flash('success', "Venta <b>$codigo</b> registrada por <b>" . money($total) . "</b>.$vuelto <a href='" . url('ordenes/ticket/' . $oid) . "' target='_blank' class='fw-bold'>Imprimir ticket</a>");
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('danger', e($e->getMessage()));
        }
        $this->redirect('ventas');
    }
}
