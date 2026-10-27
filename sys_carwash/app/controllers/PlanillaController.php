<?php
/** Planilla mensual: sueldo base, descuentos por faltas/tardanzas, adelantos y comisiones; el pago se carga a gastos (Sueldos). */
class PlanillaController extends Controller {
    private function mes(): string { return preg_match('/^\d{4}-\d{2}$/', $_GET['mes'] ?? $_POST['mes'] ?? '') ? ($_GET['mes'] ?? $_POST['mes']) : date('Y-m'); }

    /** Cálculo (sin guardar) para un empleado y mes. */
    private function calcular(array $e, string $mes, array $cfg): array {
        $d = "$mes-01"; $h = date('Y-m-t', strtotime($d)); $sueldo = (float)$e['sueldo'];
        $a = Database::row("SELECT COALESCE(SUM(estado='falta'),0) faltas, COALESCE(SUM(min_tarde),0) mt FROM asistencias WHERE empleado_id=? AND fecha BETWEEN ? AND ?", [$e['id'], $d, $h]);
        $dia = $sueldo / max(1, (int)$cfg['dias_mes']);
        $df = min($sueldo, round($dia * (int)$a['faltas'], 2));
        $dt = $cfg['desc_tardanza'] ? min($sueldo - $df, round($dia / 480 * (int)$a['mt'], 2)) : 0.0;
        $adel = Database::all('SELECT id,monto FROM planilla_adelantos WHERE empleado_id=? AND planilla_id IS NULL AND fecha<=? ORDER BY fecha,id', [$e['id'], $h]);
        $disp = round($sueldo - $df - $dt, 2); $apl = 0.0; $ids = []; $pend = 0.0;
        foreach ($adel as $x) { if ($apl + $x['monto'] <= $disp + 0.004) { $apl += $x['monto']; $ids[] = (int)$x['id']; } else $pend += $x['monto']; }
        $com = (float)Database::val('SELECT COALESCE(SUM(monto),0) FROM comisiones_pagos WHERE empleado_id=? AND hasta BETWEEN ? AND ?', [$e['id'], $d, $h]);
        return ['sueldo' => $sueldo, 'faltas' => (int)$a['faltas'], 'min_tarde' => (int)$a['mt'], 'desc_faltas' => $df, 'desc_tardanza' => $dt, 'adelantos' => round($apl, 2), 'adel_ids' => $ids,
                'adel_pend' => round($pend, 2), 'comisiones' => $com, 'neto' => max(0, round($disp - $apl, 2))];
    }

    public function index() {
        $this->requireModule('empleados');
        $mes = $this->mes(); $cfg = AsistenciaController::cfg();
        $emps = Database::all('SELECT id,nombre,cargo,sueldo FROM empleados WHERE estado=1 ORDER BY nombre');
        $pag = array_column(Database::all('SELECT * FROM planillas WHERE periodo=?', [$mes]), null, 'empleado_id');
        $rows = []; $tot = ['neto' => 0, 'pendiente' => 0, 'comisiones' => 0, 'pagado' => 0];
        foreach ($emps as $e) {
            if (isset($pag[$e['id']])) { $r = $pag[$e['id']] + ['pagado' => true, 'adel_pend' => 0, 'planilla_id' => $pag[$e['id']]['id']]; $tot['pagado'] += $r['neto']; }
            else { $r = $this->calcular($e, $mes, $cfg) + ['pagado' => false]; $tot['pendiente'] += $r['neto']; }
            $tot['neto'] += $r['neto']; $tot['comisiones'] += $r['comisiones'];
            $rows[] = $e + $r;
        }
        $adel = Database::all('SELECT a.*, e.nombre FROM planilla_adelantos a JOIN empleados e ON e.id=a.empleado_id ORDER BY a.id DESC LIMIT 15');
        $this->view('planilla/index', ['mes' => $mes, 'rows' => $rows, 'tot' => $tot, 'adel' => $adel, 'emps' => $emps, 'title' => 'Planilla']);
    }

    private function cerrar(array $e, string $mes, array $cfg): ?float {
        if (Database::val('SELECT COUNT(*) FROM planillas WHERE periodo=? AND empleado_id=?', [$mes, $e['id']])) return null;
        $c = $this->calcular($e, $mes, $cfg); $uid = (int)auth_user()['id'];
        Database::q('INSERT INTO planillas (periodo,empleado_id,sueldo,faltas,min_tarde,desc_faltas,desc_tardanza,adelantos,comisiones,neto,usuario_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            [$mes, $e['id'], $c['sueldo'], $c['faltas'], $c['min_tarde'], $c['desc_faltas'], $c['desc_tardanza'], $c['adelantos'], $c['comisiones'], $c['neto'], $uid]);
        $pid = (int)Database::id();
        foreach ($c['adel_ids'] as $aid) Database::q('UPDATE planilla_adelantos SET planilla_id=? WHERE id=?', [$pid, $aid]);
        if ($c['neto'] > 0) Database::q("INSERT INTO gastos (fecha,categoria,concepto,monto,usuario_id) VALUES (CURDATE(),'Sueldos',?,?,?)", ["Planilla $mes {$e['nombre']}", $c['neto'], $uid]);
        return $c['neto'];
    }

    public function pagar() {
        $this->requireModule('empleados');
        if (!$this->isPost()) $this->redirect('planilla');
        csrf_check();
        $mes = $this->mes(); $cfg = AsistenciaController::cfg();
        $pdo = Database::pdo(); $pdo->beginTransaction();
        try {
            if (($_POST['todos'] ?? '') === '1') {
                $n = 0; $t = 0.0;
                foreach (Database::all('SELECT * FROM empleados WHERE estado=1 AND sueldo>0') as $e) { $r = $this->cerrar($e, $mes, $cfg); if ($r !== null) { $n++; $t += $r; } }
                $msg = "Planilla $mes cerrada: $n empleado(s), total " . money($t) . ' cargado a gastos.';
            } else {
                $e = Database::row('SELECT * FROM empleados WHERE id=?', [(int)($_POST['empleado_id'] ?? 0)]);
                if (!$e) throw new RuntimeException('Empleado no encontrado.');
                $r = $this->cerrar($e, $mes, $cfg); if ($r === null) throw new RuntimeException('Ya está pagado en ese periodo.');
                $msg = 'Pago de ' . $e['nombre'] . ': ' . money($r) . ' cargado a gastos.';
            }
            $pdo->commit(); log_action('empleados', "Planilla $mes: $msg"); flash('success', $msg);
        } catch (Throwable $ex) { if ($pdo->inTransaction()) $pdo->rollBack(); flash('danger', e($ex->getMessage())); }
        $this->redirect('planilla?mes=' . $mes);
    }

    public function adelanto() {
        $this->requireModule('empleados');
        if (!$this->isPost()) $this->redirect('planilla');
        csrf_check();
        $e = Database::row('SELECT id,nombre FROM empleados WHERE id=?', [(int)($_POST['empleado_id'] ?? 0)]);
        $m = round((float)str_replace(',', '.', (string)($_POST['monto'] ?? 0)), 2);
        if (!$e || $m <= 0) { flash('danger', 'Elige un empleado e ingresa un monto válido.'); $this->redirect('planilla?mes=' . $this->mes()); }
        $uid = (int)auth_user()['id']; $mot = mb_substr(trim($_POST['motivo'] ?? ''), 0, 150) ?: null;
        Database::q('INSERT INTO planilla_adelantos (empleado_id,fecha,monto,motivo,usuario_id) VALUES (?,CURDATE(),?,?,?)', [$e['id'], $m, $mot, $uid]);
        Database::q("INSERT INTO gastos (fecha,categoria,concepto,monto,usuario_id) VALUES (CURDATE(),'Sueldos',?,?,?)", ["Adelanto {$e['nombre']}", $m, $uid]);
        log_action('empleados', "Adelanto a {$e['nombre']}: " . num($m));
        flash('success', 'Adelanto de ' . money($m) . ' registrado; se descontará en la próxima planilla.');
        $this->redirect('planilla?mes=' . $this->mes());
    }

    public function boleta($id = 0) {
        $this->requireModule('empleados');
        $p = Database::row('SELECT p.*, e.nombre, e.cargo, e.documento FROM planillas p JOIN empleados e ON e.id=p.empleado_id WHERE p.id=?', [(int)$id]);
        if (!$p) { flash('danger', 'Boleta no encontrada.'); $this->redirect('planilla'); }
        $this->view('planilla/boleta', ['p' => $p, 'emp' => empresa()], 'none');
    }
}
