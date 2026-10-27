<?php
class ReportesController extends Controller {
    private function rango(): array {
        $d = $_GET['desde'] ?? date('Y-m-01'); $h = $_GET['hasta'] ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) $d = date('Y-m-01');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $h)) $h = date('Y-m-d');
        return [$d, $h];
    }
    public function index() {
        $this->requireModule('reportes');
        [$d, $h] = $this->rango(); $p = [$d, $h];
        $base = "o.pagado=1 AND o.estado<>'cancelado' AND DATE(o.fecha_pago) BETWEEN ? AND ?";
        $r = Database::row("SELECT COALESCE(SUM(total),0) ingresos, COUNT(*) ordenes, COALESCE(AVG(total),0) promedio, COALESCE(SUM(impuesto),0) impuestos FROM ordenes o WHERE $base", $p);
        $r['paquetes'] = (float)Database::val('SELECT COALESCE(SUM(precio_pagado),0) FROM cliente_paquetes WHERE DATE(fecha_compra) BETWEEN ? AND ?', $p);
        $r['ingresos'] += $r['paquetes'];
        $r['gastos'] = (float)Database::val('SELECT COALESCE(SUM(monto),0) FROM gastos WHERE fecha BETWEEN ? AND ?', $p);
        $r['utilidad'] = $r['ingresos'] - $r['gastos'];
        $porDia = Database::all("SELECT DATE(o.fecha_pago) d, SUM(o.total) t, COUNT(*) n FROM ordenes o WHERE $base GROUP BY DATE(o.fecha_pago) ORDER BY d", $p);
        $servicios = Database::all("SELECT dt.descripcion n, SUM(dt.cantidad) c, SUM(dt.subtotal) t FROM orden_detalle dt JOIN ordenes o ON o.id=dt.orden_id WHERE $base GROUP BY dt.tipo, dt.item_id, dt.descripcion ORDER BY t DESC LIMIT 15", $p);
        $empleados = Database::all("SELECT e.nombre, COUNT(DISTINCT o.id) ordenes, COALESCE(SUM(dt.subtotal),0) ventas,
            COALESCE(SUM(dt.subtotal * IF(COALESCE(s.comision_pct,0)>0, s.comision_pct, e.comision_pct)/100),0) comision
            FROM ordenes o JOIN empleados e ON e.id=o.empleado_id JOIN orden_detalle dt ON dt.orden_id=o.id AND dt.tipo='servicio' LEFT JOIN servicios s ON s.id=dt.item_id
            WHERE $base GROUP BY e.id ORDER BY ventas DESC", $p);
        $pagos = Database::all("SELECT p.metodo m, COUNT(*) n, SUM(p.monto) t FROM orden_pagos p JOIN ordenes o ON o.id=p.orden_id WHERE p.anulado=0 AND o.estado<>'cancelado' AND DATE(p.fecha) BETWEEN ? AND ? GROUP BY p.metodo", $p);
        $gastosCat = Database::all('SELECT categoria n, SUM(monto) t FROM gastos WHERE fecha BETWEEN ? AND ? GROUP BY categoria ORDER BY t DESC', $p);
        $this->view('reportes/index', compact('d', 'h', 'r', 'porDia', 'servicios', 'empleados', 'pagos', 'gastosCat') + ['title' => 'Reportes']);
    }
    public function imprimir() {
        $this->requireModule('reportes');
        [$d, $h] = $this->rango(); $p = [$d, $h];
        $base = "o.pagado=1 AND o.estado<>'cancelado' AND DATE(o.fecha_pago) BETWEEN ? AND ?";
        $r = Database::row("SELECT COALESCE(SUM(total),0) ingresos, COUNT(*) ordenes, COALESCE(AVG(total),0) promedio FROM ordenes o WHERE $base", $p);
        $r['paquetes'] = (float)Database::val('SELECT COALESCE(SUM(precio_pagado),0) FROM cliente_paquetes WHERE DATE(fecha_compra) BETWEEN ? AND ?', $p);
        $r['ingresos'] += $r['paquetes'];
        $r['gastos'] = (float)Database::val('SELECT COALESCE(SUM(monto),0) FROM gastos WHERE fecha BETWEEN ? AND ?', $p);
        $r['utilidad'] = $r['ingresos'] - $r['gastos'];
        $porDia = Database::all("SELECT DATE(o.fecha_pago) d, COUNT(*) n, SUM(o.total) t FROM ordenes o WHERE $base GROUP BY DATE(o.fecha_pago) ORDER BY d", $p);
        $servicios = Database::all("SELECT dt.descripcion n, SUM(dt.cantidad) c, SUM(dt.subtotal) t FROM orden_detalle dt JOIN ordenes o ON o.id=dt.orden_id WHERE $base GROUP BY dt.tipo, dt.item_id, dt.descripcion ORDER BY t DESC LIMIT 20", $p);
        $pagos = Database::all("SELECT p.metodo m, COUNT(*) n, SUM(p.monto) t FROM orden_pagos p JOIN ordenes o ON o.id=p.orden_id WHERE p.anulado=0 AND o.estado<>'cancelado' AND DATE(p.fecha) BETWEEN ? AND ? GROUP BY p.metodo", $p);
        $gastos = Database::all('SELECT categoria n, SUM(monto) t FROM gastos WHERE fecha BETWEEN ? AND ? GROUP BY categoria ORDER BY t DESC', $p);
        $this->view('reportes/imprimir', ['emp' => empresa(), 'd' => $d, 'h' => $h, 'r' => $r, 'porDia' => $porDia, 'servicios' => $servicios, 'pagos' => $pagos, 'gastos' => $gastos], 'none');
    }
    /** Exportación a Excel (.xlsx) de varios reportes. */
    const EXCEL = ['ordenes' => 'Órdenes', 'ventas_dia' => 'Ventas por día', 'servicios' => 'Servicios vendidos', 'cobrar' => 'Cuentas por cobrar', 'pagar' => 'Cuentas por pagar', 'inventario' => 'Inventario', 'clientes' => 'Clientes', 'gastos' => 'Gastos'];
    public function excel() {
        $this->requireModule('reportes');
        [$d, $h] = $this->rango(); $p = [$d, $h]; $t = $_GET['tipo'] ?? 'ordenes';
        if (!isset(self::EXCEL[$t])) $t = 'ordenes';
        $base = "o.pagado=1 AND o.estado<>'cancelado' AND DATE(o.fecha_pago) BETWEEN ? AND ?";
        switch ($t) {
            case 'ventas_dia':
                $cols = [['Fecha', 's'], ['Órdenes', 'n'], ['Total vendido', 'm']];
                $rows = Database::all("SELECT DATE(o.fecha_pago), COUNT(*), SUM(o.total) FROM ordenes o WHERE $base GROUP BY DATE(o.fecha_pago) ORDER BY 1", $p); break;
            case 'servicios':
                $cols = [['Concepto', 's'], ['Tipo', 's'], ['Cantidad', 'n'], ['Importe', 'm']];
                $rows = Database::all("SELECT dt.descripcion, dt.tipo, SUM(dt.cantidad), SUM(dt.subtotal) FROM orden_detalle dt JOIN ordenes o ON o.id=dt.orden_id WHERE $base GROUP BY dt.tipo, dt.item_id, dt.descripcion ORDER BY 4 DESC", $p); break;
            case 'cobrar':
                $cols = [['Orden', 's'], ['Fecha', 's'], ['Cliente', 's'], ['Teléfono', 's'], ['Placa', 's'], ['Total', 'm'], ['Abonado', 'm'], ['Saldo', 'm'], ['Días', 'n']];
                $rows = Database::all("SELECT * FROM (SELECT o.codigo, DATE(o.fecha_ingreso) f, c.nombre, c.telefono, v.placa, o.total, COALESCE((SELECT SUM(x.monto) FROM orden_pagos x WHERE x.orden_id=o.id AND x.anulado=0),0) ab, 0 sal, DATEDIFF(CURDATE(), DATE(o.fecha_ingreso)) dias
                    FROM ordenes o LEFT JOIN clientes c ON c.id=o.cliente_id LEFT JOIN vehiculos v ON v.id=o.vehiculo_id WHERE o.pagado=0 AND o.estado<>'cancelado') q ORDER BY f");
                foreach ($rows as &$r) { $r['sal'] = round($r['total'] - $r['ab'], 2); } unset($r);
                $rows = array_values(array_filter($rows, fn($r) => $r['sal'] > 0.004)); break;
            case 'pagar':
                $cols = [['Compra', 's'], ['Proveedor', 's'], ['Documento', 's'], ['Fecha', 's'], ['Vence', 's'], ['Total', 'm'], ['Pagado', 'm'], ['Saldo', 'm']];
                $rows = Database::all("SELECT CONCAT('#',c.id), p.nombre, c.nro_doc, c.fecha, c.vence, c.total, c.pagado, ROUND(c.total-c.pagado,2) FROM compras c LEFT JOIN proveedores p ON p.id=c.proveedor_id WHERE c.condicion='credito' AND c.total-c.pagado>0.004 ORDER BY c.vence"); break;
            case 'inventario':
                $cols = [['Código', 's'], ['Producto', 's'], ['Unidad', 's'], ['Stock', 'n'], ['Stock mínimo', 'n'], ['Costo', 'm'], ['Precio venta', 'm'], ['Valor en stock', 'm'], ['A la venta', 's']];
                $rows = Database::all("SELECT codigo, nombre, unidad, stock, stock_minimo, precio_compra, precio_venta, ROUND(stock*precio_compra,2), IF(se_vende,'Sí','Insumo') FROM productos WHERE estado=1 ORDER BY nombre"); break;
            case 'clientes':
                $cols = [['Nombre', 's'], ['Documento', 's'], ['Teléfono', 's'], ['Email', 's'], ['Dirección', 's'], ['Órdenes', 'n'], ['Total comprado', 'm'], ['Última visita', 's']];
                $rows = Database::all("SELECT c.nombre, CONCAT(c.tipo_doc,' ',COALESCE(c.nro_doc,'')), c.telefono, c.email, c.direccion, COUNT(o.id), COALESCE(SUM(o.total),0), MAX(DATE(o.fecha_ingreso))
                    FROM clientes c LEFT JOIN ordenes o ON o.cliente_id=c.id AND o.estado<>'cancelado' WHERE c.estado=1 GROUP BY c.id ORDER BY c.nombre"); break;
            case 'gastos':
                $cols = [['Fecha', 's'], ['Categoría', 's'], ['Concepto', 's'], ['Monto', 'm']];
                $rows = Database::all('SELECT fecha, categoria, concepto, monto FROM gastos WHERE fecha BETWEEN ? AND ? ORDER BY fecha, id', $p); break;
            default:
                $cols = [['Código', 's'], ['Fecha', 's'], ['Cliente', 's'], ['Placa', 's'], ['Lavador', 's'], ['Estado', 's'], ['Método', 's'], ['Pagado', 's'], ['Subtotal', 'm'], ['Descuento', 'm'], ['Impuesto', 'm'], ['Total', 'm']];
                $rows = Database::all("SELECT o.codigo, o.fecha_ingreso, c.nombre, v.placa, e.nombre, o.estado, o.metodo_pago, IF(o.pagado,'Sí','No'), o.subtotal, o.descuento, o.impuesto, o.total
                    FROM ordenes o LEFT JOIN clientes c ON c.id=o.cliente_id LEFT JOIN vehiculos v ON v.id=o.vehiculo_id LEFT JOIN empleados e ON e.id=o.empleado_id WHERE DATE(o.fecha_ingreso) BETWEEN ? AND ? ORDER BY o.id", $p);
        }
        $rows = array_map(fn($r) => array_values($r), $rows);
        $sufijo = in_array($t, ['ordenes', 'ventas_dia', 'servicios', 'gastos']) ? "_{$d}_{$h}" : '_' . date('Y-m-d');
        log_action('reportes', 'Exportó Excel: ' . self::EXCEL[$t]);
        Xlsx::download($t . $sufijo, self::EXCEL[$t], $cols, $rows);
    }
    public function exportar() {
        $this->requireModule('reportes');
        [$d, $h] = $this->rango();
        $rows = Database::all("SELECT o.codigo, o.fecha_ingreso, c.nombre cliente, v.placa, e.nombre empleado, o.estado, o.metodo_pago, o.pagado, o.subtotal, o.descuento, o.impuesto, o.total
            FROM ordenes o LEFT JOIN clientes c ON c.id=o.cliente_id LEFT JOIN vehiculos v ON v.id=o.vehiculo_id LEFT JOIN empleados e ON e.id=o.empleado_id
            WHERE DATE(o.fecha_ingreso) BETWEEN ? AND ? ORDER BY o.id", [$d, $h]);
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=ordenes_{$d}_{$h}.csv");
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Código', 'Fecha', 'Cliente', 'Placa', 'Empleado', 'Estado', 'Método', 'Pagado', 'Subtotal', 'Descuento', 'Impuesto', 'Total'], ';');
        foreach ($rows as $r) fputcsv($out, array_values($r), ';');
        fclose($out); exit;
    }
}
