<?php
class ComisionesController extends Controller {
    private function calcular(int $emp, string $d, string $h): array {
        return Database::row("SELECT COUNT(DISTINCT o.id) ordenes, COALESCE(SUM(dt.subtotal),0) ventas,
            COALESCE(SUM(dt.subtotal * IF(COALESCE(s.comision_pct,0)>0, s.comision_pct, e.comision_pct)/100),0) comision
            FROM ordenes o JOIN empleados e ON e.id=o.empleado_id JOIN orden_detalle dt ON dt.orden_id=o.id AND dt.tipo='servicio' LEFT JOIN servicios s ON s.id=dt.item_id
            WHERE o.empleado_id=? AND o.pagado=1 AND o.estado<>'cancelado' AND DATE(o.fecha_pago) BETWEEN ? AND ?", [$emp, $d, $h]);
    }
    public function index() {
        $this->requireModule('empleados');
        $d = $_GET['desde'] ?? date('Y-m-01'); $h = $_GET['hasta'] ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $h)) { $d = date('Y-m-01'); $h = date('Y-m-d'); }
        $emps = Database::all('SELECT id,nombre,cargo,comision_pct FROM empleados WHERE estado=1 ORDER BY nombre');
        foreach ($emps as &$e) {
            $e += $this->calcular((int)$e['id'], $d, $h);
            $e['pagado'] = (float)Database::val('SELECT COALESCE(SUM(monto),0) FROM comisiones_pagos WHERE empleado_id=? AND desde<=? AND hasta>=?', [$e['id'], $h, $d]);
        }
        unset($e);
        $hist = Database::all('SELECT cp.*, e.nombre empleado FROM comisiones_pagos cp JOIN empleados e ON e.id=cp.empleado_id ORDER BY cp.id DESC LIMIT 30');
        $this->view('comisiones/index', compact('emps', 'hist', 'd', 'h') + ['title' => 'Liquidar comisiones']);
    }
    public function pagar() {
        $this->requireModule('empleados');
        if (!$this->isPost()) $this->redirect('comisiones');
        csrf_check();
        $emp = (int)($_POST['empleado_id'] ?? 0); $d = $_POST['desde'] ?? ''; $h = $_POST['hasta'] ?? '';
        $e = Database::row('SELECT * FROM empleados WHERE id=?', [$emp]);
        if (!$e || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $h)) { flash('danger', 'Datos inválidos.'); $this->redirect('comisiones'); }
        if (Database::val('SELECT COUNT(*) FROM comisiones_pagos WHERE empleado_id=? AND desde<=? AND hasta>=?', [$emp, $h, $d])) {
            flash('warning', 'Ya existe un pago de comisiones que se cruza con ese periodo para ' . e($e['nombre']) . '.'); $this->redirect("comisiones?desde=$d&hasta=$h");
        }
        $r = $this->calcular($emp, $d, $h);
        $monto = round((float)$r['comision'], 2);
        if ($monto <= 0) { flash('warning', 'No hay comisión por pagar en ese periodo.'); $this->redirect("comisiones?desde=$d&hasta=$h"); }
        Database::q('INSERT INTO comisiones_pagos (empleado_id,desde,hasta,ventas,monto,usuario_id) VALUES (?,?,?,?,?,?)', [$emp, $d, $h, $r['ventas'], $monto, auth_user()['id']]);
        Database::q("INSERT INTO gastos (fecha,categoria,concepto,monto,usuario_id) VALUES (CURDATE(),'Sueldos',?,?,?)", ["Comisiones {$e['nombre']} ($d a $h)", $monto, auth_user()['id']]);
        log_action('comisiones', "Pago comisiones {$e['nombre']}: " . num($monto));
        flash('success', 'Comisión de <b>' . e($e['nombre']) . '</b> registrada (' . money($monto) . ') y cargada a gastos.');
        $this->redirect("comisiones?desde=$d&hasta=$h");
    }
}
