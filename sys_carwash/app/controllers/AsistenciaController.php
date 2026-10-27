<?php
/** Asistencia del personal: marcación de entrada/salida, tardanzas, faltas, permisos y resumen mensual. */
class AsistenciaController extends Controller {
    const ESTADOS = ['presente' => 'Presente', 'tardanza' => 'Tardanza', 'falta' => 'Falta', 'permiso' => 'Permiso', 'descanso' => 'Descanso'];
    const COLOR = ['presente' => 'success', 'tardanza' => 'warning', 'falta' => 'danger', 'permiso' => 'info', 'descanso' => 'secondary'];

    public static function cfg(): array {
        Database::q('INSERT IGNORE INTO asistencia_config (id) VALUES (1)');
        return Database::row('SELECT * FROM asistencia_config WHERE id=1');
    }
    private function fecha(?string $f): string { return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$f) ? $f : date('Y-m-d'); }
    private function hora(?string $h, string $def): string { return preg_match('/^\d{2}:\d{2}(:\d{2})?$/', (string)$h) ? (strlen($h) === 5 ? "$h:00" : $h) : $def; }
    private function minutos(string $hhmmss): int { [$a, $b] = explode(':', $hhmmss); return (int)$a * 60 + (int)$b; }

    public function index() {
        $this->requireModule('empleados');
        $f = $this->fecha($_GET['fecha'] ?? null); $cfg = self::cfg();
        $rows = Database::all('SELECT e.id,e.nombre,e.cargo, a.entrada,a.salida,a.estado,a.min_tarde FROM empleados e LEFT JOIN asistencias a ON a.empleado_id=e.id AND a.fecha=? WHERE e.estado=1 ORDER BY e.nombre', [$f]);
        $res = array_fill_keys(array_keys(self::ESTADOS), 0); $res['sin'] = 0;
        foreach ($rows as $r) { if ($r['estado']) $res[$r['estado']]++; else $res['sin']++; }
        $laboral = in_array((string)date('N', strtotime($f)), explode(',', $cfg['dias_laborales']));
        $this->view('asistencia/index', ['rows' => $rows, 'f' => $f, 'cfg' => $cfg, 'res' => $res, 'laboral' => $laboral, 'title' => 'Asistencia']);
    }

    public function marcar() {
        $this->requireModule('empleados');
        if (!$this->isPost()) $this->redirect('asistencia');
        csrf_check();
        $f = $this->fecha($_POST['fecha'] ?? null); $emp = (int)($_POST['empleado_id'] ?? 0); $acc = $_POST['accion'] ?? '';
        $e = Database::row('SELECT id,nombre FROM empleados WHERE id=?', [$emp]);
        if (!$e) { flash('danger', 'Empleado no encontrado.'); $this->redirect('asistencia?fecha=' . $f); }
        if ($f > date('Y-m-d')) { flash('danger', 'No se puede registrar asistencia en fechas futuras.'); $this->redirect('asistencia?fecha=' . $f); }
        $cfg = self::cfg(); $hoy = $f === date('Y-m-d'); $uid = (int)auth_user()['id'];
        $a = Database::row('SELECT * FROM asistencias WHERE empleado_id=? AND fecha=?', [$emp, $f]);
        if ($acc === 'entrada') {
            $h = $this->hora($_POST['hora'] ?? '', $hoy ? date('H:i:s') : $cfg['hora_entrada']);
            $late = max(0, $this->minutos($h) - $this->minutos($cfg['hora_entrada']));
            $est = $late > (int)$cfg['tolerancia'] ? 'tardanza' : 'presente'; $late = $est === 'tardanza' ? $late : 0;
            Database::q('INSERT INTO asistencias (empleado_id,fecha,entrada,estado,min_tarde,usuario_id) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE entrada=VALUES(entrada), estado=VALUES(estado), min_tarde=VALUES(min_tarde), usuario_id=VALUES(usuario_id)', [$emp, $f, $h, $est, $late, $uid]);
            flash($est === 'tardanza' ? 'warning' : 'success', e($e['nombre']) . ': entrada ' . substr($h, 0, 5) . ($est === 'tardanza' ? " (tardanza de $late min)" : ''));
        } elseif ($acc === 'salida') {
            if (!$a || !$a['entrada']) { flash('danger', 'Primero registra la entrada de ' . e($e['nombre']) . '.'); $this->redirect('asistencia?fecha=' . $f); }
            $h = $this->hora($_POST['hora'] ?? '', $hoy ? date('H:i:s') : $cfg['hora_salida']);
            if ($h <= $a['entrada']) { flash('danger', 'La salida debe ser posterior a la entrada (' . substr($a['entrada'], 0, 5) . ').'); $this->redirect('asistencia?fecha=' . $f); }
            Database::q('UPDATE asistencias SET salida=?, usuario_id=? WHERE id=?', [$h, $uid, $a['id']]);
            flash('success', e($e['nombre']) . ': salida ' . substr($h, 0, 5));
        } elseif (in_array($acc, ['falta', 'permiso', 'descanso'], true)) {
            Database::q('INSERT INTO asistencias (empleado_id,fecha,entrada,salida,estado,min_tarde,usuario_id) VALUES (?,?,NULL,NULL,?,0,?) ON DUPLICATE KEY UPDATE entrada=NULL, salida=NULL, estado=VALUES(estado), min_tarde=0, usuario_id=VALUES(usuario_id)', [$emp, $f, $acc, $uid]);
            flash('success', e($e['nombre']) . ': ' . strtolower(self::ESTADOS[$acc]) . ' registrada.');
        } elseif ($acc === 'borrar') {
            Database::q('DELETE FROM asistencias WHERE empleado_id=? AND fecha=?', [$emp, $f]); flash('success', 'Registro eliminado.');
        }
        log_action('empleados', "Asistencia $acc: {$e['nombre']} ($f)");
        $this->redirect('asistencia?fecha=' . $f);
    }

    /** Marca como falta a quien no tenga registro ese día (solo días laborales y fechas pasadas o de hoy). */
    public function cerrar_dia() {
        $this->requireModule('empleados');
        if (!$this->isPost()) $this->redirect('asistencia');
        csrf_check();
        $f = $this->fecha($_POST['fecha'] ?? null); $cfg = self::cfg();
        if ($f > date('Y-m-d') || !in_array((string)date('N', strtotime($f)), explode(',', $cfg['dias_laborales']))) { flash('warning', 'Ese día no es laboral o aún no ocurre.'); $this->redirect('asistencia?fecha=' . $f); }
        $n = Database::q("INSERT INTO asistencias (empleado_id,fecha,estado,usuario_id) SELECT e.id, ?, 'falta', ? FROM empleados e WHERE e.estado=1 AND (e.fecha_ingreso IS NULL OR e.fecha_ingreso<=?) AND NOT EXISTS (SELECT 1 FROM asistencias a WHERE a.empleado_id=e.id AND a.fecha=?)", [$f, (int)auth_user()['id'], $f, $f])->rowCount();
        log_action('empleados', "Cerró asistencia $f: $n faltas");
        flash($n ? 'warning' : 'info', $n ? "$n empleado(s) sin registro quedaron como <b>falta</b>." : 'Todos tenían registro.');
        $this->redirect('asistencia?fecha=' . $f);
    }

    public static function resumenMes(string $mes): array {
        $d = "$mes-01"; $h = date('Y-m-t', strtotime($d));
        $rows = Database::all("SELECT e.id,e.nombre,e.cargo,
            COALESCE(SUM(a.estado IN ('presente','tardanza')),0) asistio, COALESCE(SUM(a.estado='tardanza'),0) tardanzas, COALESCE(SUM(a.estado='falta'),0) faltas,
            COALESCE(SUM(a.estado='permiso'),0) permisos, COALESCE(SUM(a.estado='descanso'),0) descansos, COALESCE(SUM(a.min_tarde),0) min_tarde,
            COALESCE(SUM(IF(a.entrada IS NOT NULL AND a.salida IS NOT NULL, TIME_TO_SEC(TIMEDIFF(a.salida,a.entrada)),0)),0)/3600 horas
            FROM empleados e LEFT JOIN asistencias a ON a.empleado_id=e.id AND a.fecha BETWEEN ? AND ? WHERE e.estado=1 GROUP BY e.id ORDER BY e.nombre", [$d, $h]);
        return $rows;
    }

    public function mes() {
        $this->requireModule('empleados');
        $mes = preg_match('/^\d{4}-\d{2}$/', $_GET['mes'] ?? '') ? $_GET['mes'] : date('Y-m');
        $this->view('asistencia/mes', ['mes' => $mes, 'rows' => self::resumenMes($mes), 'title' => 'Asistencia mensual']);
    }

    public function config() {
        $this->requireModule('configuracion');
        $this->view('asistencia/config', ['cfg' => self::cfg(), 'title' => 'Configurar asistencia']);
    }

    public function config_guardar() {
        $this->requireModule('configuracion');
        if (!$this->isPost()) $this->redirect('asistencia/config');
        csrf_check();
        $in = $this->hora($_POST['hora_entrada'] ?? '', '08:00:00'); $out = $this->hora($_POST['hora_salida'] ?? '', '17:00:00');
        $dias = array_values(array_unique(array_filter((array)($_POST['dias'] ?? []), fn($d) => in_array($d, ['1', '2', '3', '4', '5', '6', '7'], true))));
        sort($dias);
        Database::q('UPDATE asistencia_config SET hora_entrada=?, hora_salida=?, tolerancia=?, dias_laborales=?, dias_mes=?, desc_tardanza=? WHERE id=1',
            [$in, $out, max(0, min(120, (int)($_POST['tolerancia'] ?? 10))), implode(',', $dias) ?: '1,2,3,4,5,6', max(26, min(31, (int)($_POST['dias_mes'] ?? 30))), empty($_POST['desc_tardanza']) ? 0 : 1]);
        flash('success', 'Configuración de asistencia guardada.'); $this->redirect('asistencia/config');
    }
}
