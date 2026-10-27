<?php
class DashboardController extends Controller {
    public function index() {
        $this->requireModule('dashboard');
        $dias_p = (int)($_GET['dias'] ?? 7);
        if (!in_array($dias_p, [7, 15, 30, 90], true)) $dias_p = 7;
        $pagadas = "pagado=1 AND estado<>'cancelado'";
        $k = [];
        $k['ventas_hoy']   = (float)Database::val("SELECT COALESCE(SUM(total),0) FROM ordenes WHERE $pagadas AND DATE(fecha_pago)=CURDATE()");
        $k['ventas_ayer']  = (float)Database::val("SELECT COALESCE(SUM(total),0) FROM ordenes WHERE $pagadas AND DATE(fecha_pago)=DATE_SUB(CURDATE(),INTERVAL 1 DAY)");
        $k['ordenes_hoy']  = (int)Database::val("SELECT COUNT(*) FROM ordenes WHERE DATE(fecha_ingreso)=CURDATE() AND estado<>'cancelado'");
        $k['en_proceso']   = (int)Database::val("SELECT COUNT(*) FROM ordenes WHERE estado IN ('pendiente','en_proceso')");
        $k['ventas_mes']   = (float)Database::val("SELECT COALESCE(SUM(total),0) FROM ordenes WHERE $pagadas AND YEAR(fecha_pago)=YEAR(CURDATE()) AND MONTH(fecha_pago)=MONTH(CURDATE())");
        $k['gastos_mes']   = (float)Database::val("SELECT COALESCE(SUM(monto),0) FROM gastos WHERE YEAR(fecha)=YEAR(CURDATE()) AND MONTH(fecha)=MONTH(CURDATE())");
        $k['clientes']     = (int)Database::val("SELECT COUNT(*) FROM clientes WHERE estado=1");
        $k['ventas_mes_ant'] = (float)Database::val("SELECT COALESCE(SUM(total),0) FROM ordenes WHERE $pagadas AND DATE_FORMAT(fecha_pago,'%Y-%m')=DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m')");
        $k['cobradas_hoy'] = (int)Database::val("SELECT COUNT(*) FROM ordenes WHERE $pagadas AND DATE(fecha_pago)=CURDATE()");
        $k['por_cobrar']   = (float)Database::val("SELECT COALESCE(SUM(o.total - (SELECT COALESCE(SUM(p.monto),0) FROM orden_pagos p WHERE p.orden_id=o.id AND p.anulado=0)),0) FROM ordenes o WHERE o.pagado=0 AND o.estado NOT IN ('cancelado')");

        // Ventas últimos 7 días
        $dias = []; $ventas7 = []; $ordenes7 = [];
        $rows = Database::all("SELECT DATE(fecha_pago) d, SUM(total) t, COUNT(*) n FROM ordenes WHERE $pagadas AND fecha_pago >= DATE_SUB(CURDATE(), INTERVAL " . ($dias_p - 1) . " DAY) GROUP BY DATE(fecha_pago)");
        $map = array_column($rows, null, 'd');
        $nombres = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        for ($i = $dias_p - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i day"));
            $dias[] = $nombres[(int)date('w', strtotime($d))] . ' ' . date('d', strtotime($d));
            $ventas7[] = (float)($map[$d]['t'] ?? 0);
            $ordenes7[] = (int)($map[$d]['n'] ?? 0);
        }
        // Ventas por mes (12 meses)
        $meses = []; $ventasM = []; $gastosM = [];
        $mn = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $vm = array_column(Database::all("SELECT DATE_FORMAT(fecha_pago,'%Y-%m') m, SUM(total) t FROM ordenes WHERE $pagadas AND fecha_pago >= DATE_SUB(DATE_FORMAT(CURDATE(),'%Y-%m-01'), INTERVAL 11 MONTH) GROUP BY m"), 't', 'm');
        $gm = array_column(Database::all("SELECT DATE_FORMAT(fecha,'%Y-%m') m, SUM(monto) t FROM gastos WHERE fecha >= DATE_SUB(DATE_FORMAT(CURDATE(),'%Y-%m-01'), INTERVAL 11 MONTH) GROUP BY m"), 't', 'm');
        for ($i = 11; $i >= 0; $i--) {
            $ts = strtotime(date('Y-m-01') . " -$i month");
            $key = date('Y-m', $ts);
            $meses[] = $mn[(int)date('n', $ts) - 1];
            $ventasM[] = (float)($vm[$key] ?? 0);
            $gastosM[] = (float)($gm[$key] ?? 0);
        }
        $topServ = Database::all("SELECT d.descripcion n, SUM(d.subtotal) t, SUM(d.cantidad) c FROM orden_detalle d JOIN ordenes o ON o.id=d.orden_id WHERE d.tipo='servicio' AND o.estado<>'cancelado' AND o.fecha_ingreso >= DATE_SUB(CURDATE(), INTERVAL $dias_p DAY) GROUP BY d.item_id, d.descripcion ORDER BY t DESC LIMIT 6");
        $pagos = Database::all("SELECT p.metodo m, SUM(p.monto) t FROM orden_pagos p JOIN ordenes o ON o.id=p.orden_id WHERE p.anulado=0 AND o.estado<>'cancelado' AND p.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY p.metodo");
        $emps = Database::all("SELECT e.nombre n, COUNT(o.id) c, COALESCE(SUM(o.total),0) t FROM ordenes o JOIN empleados e ON e.id=o.empleado_id WHERE o.estado<>'cancelado' AND o.fecha_ingreso >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY e.id ORDER BY t DESC LIMIT 5");
        $ultimas = Database::all("SELECT o.*, c.nombre cliente, v.placa FROM ordenes o LEFT JOIN clientes c ON c.id=o.cliente_id LEFT JOIN vehiculos v ON v.id=o.vehiculo_id ORDER BY o.id DESC LIMIT 7");
        $stockBajo = Database::all("SELECT nombre, stock, stock_minimo, unidad FROM productos WHERE estado=1 AND stock <= stock_minimo ORDER BY stock ASC LIMIT 6");
        $citasHoy = Database::all("SELECT ci.*, c.nombre cliente, s.nombre servicio FROM citas ci JOIN clientes c ON c.id=ci.cliente_id LEFT JOIN servicios s ON s.id=ci.servicio_id WHERE DATE(ci.fecha_hora)=CURDATE() AND ci.estado IN ('programada','confirmada') ORDER BY ci.fecha_hora LIMIT 6");

        $charts = compact('dias', 'ventas7', 'ordenes7', 'meses', 'ventasM', 'gastosM', 'topServ', 'pagos', 'emps');
        $this->view('dashboard/index', compact('dias_p', 'k', 'ultimas', 'stockBajo', 'citasHoy', 'charts') + ['title' => 'Dashboard']);
    }
}
