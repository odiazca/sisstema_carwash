<?php
class OrdenesController extends Controller {
    const ESTADOS = ['pendiente' => 'Pendiente', 'en_proceso' => 'En proceso', 'terminado' => 'Terminado', 'entregado' => 'Entregado', 'cancelado' => 'Cancelado'];
    const PAGOS = ['efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'yape_plin' => 'Yape / Plin', 'transferencia' => 'Transferencia'];

    public function index() {
        $this->requireModule('ordenes');
        $estado = $_GET['estado'] ?? '';
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        $w = ['DATE(o.fecha_ingreso) BETWEEN ? AND ?']; $p = [$desde, $hasta];
        if (isset(self::ESTADOS[$estado])) { $w[] = 'o.estado=?'; $p[] = $estado; }
        $rows = Database::all('SELECT o.*, (SELECT COALESCE(SUM(p.monto),0) FROM orden_pagos p WHERE p.orden_id=o.id AND p.anulado=0) abonado, c.nombre cliente, v.placa, e.nombre empleado FROM ordenes o
            LEFT JOIN clientes c ON c.id=o.cliente_id LEFT JOIN vehiculos v ON v.id=o.vehiculo_id LEFT JOIN empleados e ON e.id=o.empleado_id
            WHERE ' . implode(' AND ', $w) . ' ORDER BY o.id DESC LIMIT 1000', $p);
        $tot = Database::all("SELECT estado, COUNT(*) n FROM ordenes WHERE DATE(fecha_ingreso)=CURDATE() GROUP BY estado");
        $this->view('ordenes/index', ['rows' => $rows, 'estado' => $estado, 'desde' => $desde, 'hasta' => $hasta,
            'resumen' => array_column($tot, 'n', 'estado'), 'title' => 'Órdenes de servicio']);
    }

    public function nueva() {
        $this->requireModule('ordenes');
        $servicios = Database::all('SELECT id,nombre,precio FROM servicios WHERE estado=1 ORDER BY nombre');
        $pr = Database::all('SELECT servicio_id s, tipo_vehiculo_id t, precio p FROM servicio_precios');
        $precios = [];
        foreach ($pr as $r) $precios[$r['s']][$r['t']] = (float)$r['p'];
        $productos = Database::all('SELECT id,nombre,precio_venta,stock FROM productos WHERE estado=1 AND se_vende=1 ORDER BY nombre');
        $clientes = Database::all('SELECT id,nombre,nro_doc FROM clientes WHERE estado=1 ORDER BY nombre');
        $vehiculos = Database::all('SELECT id,cliente_id,placa,marca,modelo,tipo_vehiculo_id FROM vehiculos WHERE estado=1 ORDER BY placa');
        $empleados = Database::all('SELECT id,nombre FROM empleados WHERE estado=1 ORDER BY nombre');
        $tipos = Database::all('SELECT id,nombre FROM tipos_vehiculo WHERE estado=1 ORDER BY id');
        $cita = !empty($_GET['cita']) ? Database::row('SELECT id,cliente_id,vehiculo_id,servicio_id FROM citas WHERE id=?', [(int)$_GET['cita']]) : null;
        $cotizacion = !empty($_GET['cotizacion']) ? CotizacionesController::paraOrden((int)$_GET['cotizacion']) : null;
        $recepcion = !empty($_GET['recepcion']) ? Database::row('SELECT id,cliente_id,vehiculo_id,placa FROM recepciones WHERE id=? AND orden_id IS NULL', [(int)$_GET['recepcion']]) : null;
        $this->view('ordenes/nueva', compact('cotizacion', 'recepcion', 'cita', 'servicios', 'precios', 'productos', 'clientes', 'vehiculos', 'empleados', 'tipos') + ['title' => 'Nueva orden de servicio']);
    }

    public function guardar() {
        $this->requireModule('ordenes');
        if (!$this->isPost()) $this->redirect('ordenes');
        csrf_check();
        $items = $_POST['items'] ?? [];
        if (!$items) { flash('danger', 'Agrega al menos un servicio o producto.'); $this->redirect('ordenes/nueva'); }
        $pdo = Database::pdo();
        try {
            $pdo->beginTransaction();
            // Cliente / vehículo (con alta rápida)
            $clienteId = (int)($_POST['cliente_id'] ?? 0) ?: null;
            if (!$clienteId && trim($_POST['nuevo_cliente'] ?? '') !== '') {
                Database::q('INSERT INTO clientes (nombre,telefono) VALUES (?,?)', [trim($_POST['nuevo_cliente']), trim($_POST['nuevo_telefono'] ?? '') ?: null]);
                $clienteId = (int)Database::id();
            }
            $vehId = (int)($_POST['vehiculo_id'] ?? 0) ?: null;
            $placa = strtoupper(preg_replace('/\s+/', '', $_POST['nueva_placa'] ?? ''));
            $tipoV = (int)($_POST['tipo_vehiculo_id'] ?? 0) ?: null;
            if (!$vehId && $placa !== '') {
                if (!$clienteId) {
                    $clienteId = (int)Database::val("SELECT id FROM clientes WHERE nombre='Cliente general' LIMIT 1");
                    if (!$clienteId) { Database::q("INSERT INTO clientes (nombre) VALUES ('Cliente general')"); $clienteId = (int)Database::id(); }
                }
                $ex = Database::row('SELECT id FROM vehiculos WHERE placa=? AND cliente_id=?', [$placa, $clienteId]);
                if ($ex) $vehId = (int)$ex['id'];
                else {
                    Database::q('INSERT INTO vehiculos (cliente_id,placa,marca,tipo_vehiculo_id) VALUES (?,?,?,?)', [$clienteId, $placa, trim($_POST['nueva_marca'] ?? '') ?: null, $tipoV]);
                    $vehId = (int)Database::id();
                }
            }
            if ($vehId && !$tipoV) $tipoV = (int)Database::val('SELECT tipo_vehiculo_id FROM vehiculos WHERE id=?', [$vehId]) ?: null;

            // Líneas: precios SIEMPRE desde la base de datos
            $lineas = []; $subtotal = 0;
            foreach ($items as $it) {
                $cant = max(0.01, (float)($it['cant'] ?? 1)); $id = (int)($it['id'] ?? 0);
                if (($it['tipo'] ?? '') === 'servicio') {
                    $s = Database::row('SELECT * FROM servicios WHERE id=?', [$id]); if (!$s) continue;
                    $precio = (float)$s['precio'];
                    if ($tipoV) { $pt = Database::val('SELECT precio FROM servicio_precios WHERE servicio_id=? AND tipo_vehiculo_id=?', [$id, $tipoV]); if ($pt !== null) $precio = (float)$pt; }
                    $lineas[] = ['servicio', $id, $s['nombre'], $cant, $precio];
                } else {
                    $pr = Database::row('SELECT * FROM productos WHERE id=?', [$id]); if (!$pr) continue;
                    if ($pr['stock'] < $cant) throw new RuntimeException("Stock insuficiente de {$pr['nombre']} (disponible: " . num($pr['stock'], 2) . ').');
                    $lineas[] = ['producto', $id, $pr['nombre'], $cant, (float)$pr['precio_venta']];
                }
            }
            if (!$lineas) throw new RuntimeException('No hay líneas válidas.');
            // Uso de paquete prepagado: 1 unidad del servicio sin costo
            $usoPaquete = null;
            if ($cpid = (int)($_POST['cliente_paquete_id'] ?? 0)) {
                $cp = Database::row('SELECT * FROM cliente_paquetes WHERE id=? AND cliente_id=? AND usos_restantes>0 AND (fecha_vence IS NULL OR fecha_vence>=CURDATE()) FOR UPDATE', [$cpid, (int)$clienteId]);
                if (!$cp) throw new RuntimeException('El paquete seleccionado no está disponible para este cliente.');
                $aplicado = false;
                foreach ($lineas as $i => $l) {
                    if ($l[0] === 'servicio' && $l[1] == $cp['servicio_id']) {
                        if ($l[3] > 1) { $lineas[$i][3] = $l[3] - 1; array_splice($lineas, $i + 1, 0, [['servicio', $l[1], $l[2] . ' (paquete)', 1, 0.0]]); }
                        else { $lineas[$i][2] = $l[2] . ' (paquete)'; $lineas[$i][4] = 0.0; }
                        $aplicado = true; break;
                    }
                }
                if (!$aplicado) throw new RuntimeException('Agrega a la orden el servicio incluido en el paquete.');
                $usoPaquete = $cpid;
            }
            foreach ($lineas as $l) $subtotal += round($l[3] * $l[4], 2);
            $desc = min($subtotal, max(0, (float)($_POST['descuento'] ?? 0)));
            $cuponUsado = null;
            if (trim($_POST['cupon'] ?? '') !== '') {
                [$okc, $msgc, $dc, $cuponUsado] = crm_validar_cupon((string)$_POST['cupon'], $clienteId ?: null, $subtotal);
                if (!$okc) throw new RuntimeException($msgc);
                $desc = min($subtotal, $dc);
            }
            $cfg = empresa(); $rate = (float)$cfg['impuesto_porcentaje'] / 100;
            $base = $subtotal - $desc;
            if ($cfg['precios_incluyen_impuesto']) { $total = $base; $imp = round($base - $base / (1 + $rate), 2); }
            else { $imp = round($base * $rate, 2); $total = $base + $imp; }

            $cobrarAhora = !empty($_POST['pagado']);
            $metodo = isset(self::PAGOS[$_POST['metodo_pago'] ?? '']) ? $_POST['metodo_pago'] : 'efectivo';
            $gratis = $total <= 0.004;
            Database::q('INSERT INTO ordenes (codigo,cliente_id,vehiculo_id,empleado_id,usuario_id,estado,subtotal,descuento,impuesto,total,metodo_pago,pagado,fecha_pago,observaciones)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                uniqid('T'), $clienteId, $vehId, (int)($_POST['empleado_id'] ?? 0) ?: null, auth_user()['id'], 'pendiente',
                $subtotal, $desc, $imp, $total, null, $gratis ? 1 : 0, $gratis ? date('Y-m-d H:i:s') : null,
                trim($_POST['observaciones'] ?? '') ?: null]);
            $oid = (int)Database::id();
            $pagoMsg = '';
            if ($cobrarAhora && !$gratis) {
                $montoIn = trim((string)($_POST['monto_pago'] ?? ''));
                $r = orden_registrar_pago(['id' => $oid, 'total' => $total], $montoIn === '' ? $total : (float)str_replace(',', '.', $montoIn), $metodo, (float)($_POST['propina'] ?? 0), trim($_POST['referencia'] ?? '') ?: null, (int)auth_user()['id']);
                if (!$r['pagado']) $pagoMsg = ' Adelanto registrado; saldo pendiente <b>' . money($r['saldo']) . '</b>.';
            }
            if ($cuponUsado) { Database::q('UPDATE crm_cupones SET usos=usos+1 WHERE id=?', [$cuponUsado['id']]); }
            $codigo = $cfg['prefijo_orden'] . str_pad((string)$oid, 6, '0', STR_PAD_LEFT);
            Database::q('UPDATE ordenes SET codigo=? WHERE id=?', [$codigo, $oid]);
            foreach ($lineas as [$tipo, $iid, $nom, $cant, $precio]) {
                Database::q('INSERT INTO orden_detalle (orden_id,tipo,item_id,descripcion,cantidad,precio,subtotal) VALUES (?,?,?,?,?,?,?)',
                    [$oid, $tipo, $iid, $nom, $cant, $precio, round($cant * $precio, 2)]);
                if ($tipo === 'producto') {
                    Database::q('UPDATE productos SET stock=stock-? WHERE id=?', [$cant, $iid]);
                    Database::q("INSERT INTO movimientos_inventario (producto_id,tipo,cantidad,motivo,usuario_id) VALUES (?, 'salida', ?, ?, ?)", [$iid, $cant, "Venta $codigo", auth_user()['id']]);
                }
            }
            if ($usoPaquete) {
                Database::q('UPDATE cliente_paquetes SET usos_restantes=usos_restantes-1 WHERE id=?', [$usoPaquete]);
                Database::q('INSERT INTO orden_paquete_uso (orden_id,cliente_paquete_id) VALUES (?,?)', [$oid, $usoPaquete]);
            }
            if ($qid = (int)($_POST['cotizacion_id'] ?? 0)) Database::q("UPDATE cotizaciones SET estado='convertida', orden_id=? WHERE id=? AND estado<>'convertida'", [$oid, $qid]);
            if ($rid = (int)($_POST['recepcion_id'] ?? 0)) Database::q('UPDATE recepciones SET orden_id=? WHERE id=? AND orden_id IS NULL', [$oid, $rid]);
            if ($cid = (int)($_POST['cita_id'] ?? 0)) Database::q("UPDATE citas SET estado='atendida' WHERE id=?", [$cid]);
            $pdo->commit();
            log_action('ordenes', "Creó orden $codigo");
            flash('success', "Orden <b>$codigo</b> registrada correctamente." . $pagoMsg);
            $this->redirect('ordenes/ver/' . $oid);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('danger', e($e->getMessage()));
            $this->redirect('ordenes/nueva');
        }
    }

    private function cargar(int $id): array {
        $o = Database::row('SELECT o.*, c.nombre cliente, c.telefono, c.nro_doc, v.placa, v.marca, v.modelo, v.color, e.nombre empleado, u.nombre cajero
            FROM ordenes o LEFT JOIN clientes c ON c.id=o.cliente_id LEFT JOIN vehiculos v ON v.id=o.vehiculo_id
            LEFT JOIN empleados e ON e.id=o.empleado_id LEFT JOIN usuarios u ON u.id=o.usuario_id WHERE o.id=?', [$id]);
        if (!$o) { flash('danger', 'Orden no encontrada.'); $this->redirect('ordenes'); }
        $o['items'] = Database::all('SELECT * FROM orden_detalle WHERE orden_id=? ORDER BY id', [$id]);
        $o['pagos'] = Database::all('SELECT p.*, u.nombre usuario FROM orden_pagos p LEFT JOIN usuarios u ON u.id=p.usuario_id WHERE p.orden_id=? ORDER BY p.id', [$id]);
        $o['abonado'] = array_sum(array_map(fn($x) => $x['anulado'] ? 0 : (float)$x['monto'], $o['pagos']));
        return $o;
    }

    public function ver($id = 0) {
        $this->requireModule('ordenes');
        $o = $this->cargar((int)$id);
        $comp = Database::row('SELECT k.* FROM comprobantes k WHERE k.orden_id=? AND k.anulado=0 ORDER BY k.id DESC LIMIT 1', [$o['id']]);
        $cli = $o['cliente_id'] ? Database::row('SELECT tipo_doc,nro_doc,nombre,direccion FROM clientes WHERE id=?', [$o['cliente_id']]) : null;
        $this->view('ordenes/ver', ['comp' => $comp, 'cli' => $cli, 'o' => $o, 'title' => 'Orden ' . $o['codigo']]);
    }

    public function ticket($id = 0) {
        $this->requireModule('ordenes');
        $o = $this->cargar((int)$id);
        $this->view('ordenes/ticket', ['o' => $o, 'emp' => empresa()], 'none');
    }

    public function estado() {
        $this->requireModule('ordenes');
        if (!$this->isPost()) $this->redirect('ordenes');
        csrf_check();
        $id = (int)($_POST['id'] ?? 0); $nuevo = $_POST['estado'] ?? '';
        $o = Database::row('SELECT * FROM ordenes WHERE id=?', [$id]);
        if (!$o || !isset(self::ESTADOS[$nuevo])) $this->redirect('ordenes');
        if ($o['estado'] === 'cancelado') { flash('warning', 'Una orden cancelada no puede cambiar de estado.'); $this->redirect('ordenes/ver/' . $id); }
        $pdo = Database::pdo(); $pdo->beginTransaction();
        try {
            if ($nuevo === 'cancelado') {
                foreach (Database::all("SELECT * FROM orden_detalle WHERE orden_id=? AND tipo='producto'", [$id]) as $d) {
                    Database::q('UPDATE productos SET stock=stock+? WHERE id=?', [$d['cantidad'], $d['item_id']]);
                    Database::q("INSERT INTO movimientos_inventario (producto_id,tipo,cantidad,motivo,usuario_id) VALUES (?, 'entrada', ?, ?, ?)", [$d['item_id'], $d['cantidad'], 'Anulación ' . $o['codigo'], auth_user()['id']]);
                }
                Database::q('UPDATE ordenes SET pagado=0, fecha_pago=NULL, metodo_pago=NULL WHERE id=?', [$id]);
                Database::q('UPDATE orden_pagos SET anulado=1 WHERE orden_id=?', [$id]);
                if ($up = Database::val('SELECT cliente_paquete_id FROM orden_paquete_uso WHERE orden_id=?', [$id])) {
                    Database::q('UPDATE cliente_paquetes SET usos_restantes=usos_restantes+1 WHERE id=?', [$up]);
                    Database::q('DELETE FROM orden_paquete_uso WHERE orden_id=?', [$id]);
                }
            }
            Database::q('UPDATE ordenes SET estado=?, fecha_fin=IF(? IN ("terminado","entregado"), COALESCE(fecha_fin,NOW()), fecha_fin) WHERE id=?', [$nuevo, $nuevo, $id]);
            $pdo->commit();
        } catch (Throwable $e) { $pdo->rollBack(); flash('danger', e($e->getMessage())); $this->redirect('ordenes/ver/' . $id); }
        log_action('ordenes', "Orden {$o['codigo']} -> $nuevo");
        flash('success', 'Estado actualizado a ' . self::ESTADOS[$nuevo] . '.');
        $this->redirect('ordenes/ver/' . $id);
    }

    public function cobrar() {
        $this->requireModule('ordenes');
        if (!$this->isPost()) $this->redirect('ordenes');
        csrf_check();
        $id = (int)($_POST['id'] ?? 0);
        $metodo = isset(self::PAGOS[$_POST['metodo_pago'] ?? '']) ? $_POST['metodo_pago'] : 'efectivo';
        $o = Database::row('SELECT * FROM ordenes WHERE id=?', [$id]);
        if ($o && !$o['pagado'] && $o['estado'] !== 'cancelado') {
            $saldo = orden_saldo($o); $in = trim((string)($_POST['monto'] ?? ''));
            $monto = $in === '' ? $saldo : (float)str_replace(',', '.', $in);
            if ($monto <= 0 && (float)($_POST['propina'] ?? 0) <= 0) { flash('danger', 'Ingresa un monto mayor a cero.'); $this->redirect('ordenes/ver/' . $id); }
            $r = orden_registrar_pago($o, $monto, $metodo, (float)($_POST['propina'] ?? 0), trim($_POST['referencia'] ?? '') ?: null, (int)auth_user()['id']);
            log_action('ordenes', "Pago de {$o['codigo']}: " . num($r['monto']) . " ($metodo)");
            flash('success', $r['pagado'] ? 'Pago registrado. La orden quedó <b>pagada</b>.' : 'Abono registrado. Saldo pendiente: <b>' . money($r['saldo']) . '</b>.');
        }
        $this->redirect('ordenes/ver/' . $id);
    }
}
