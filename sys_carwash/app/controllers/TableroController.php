<?php
/** Tablero de trabajo en vivo: órdenes por estado, tiempos y asignación de lavador. */
class TableroController extends Controller {
    const COLS = ['pendiente' => 'En cola', 'en_proceso' => 'En proceso', 'terminado' => 'Listos para entregar', 'entregado' => 'Entregados hoy'];

    private function datos(): array {
        $rows = Database::all("SELECT o.id,o.codigo,o.estado,o.fecha_ingreso,o.fecha_fin,o.total,o.empleado_id, v.placa,v.marca,v.modelo,v.color, c.nombre cliente, c.telefono, e.nombre empleado,
            TIMESTAMPDIFF(MINUTE,o.fecha_ingreso,IF(o.estado IN ('terminado','entregado'), COALESCE(o.fecha_fin,NOW()), NOW())) mins,
            (SELECT COALESCE(SUM(s.duracion_min*d.cantidad),0) FROM orden_detalle d JOIN servicios s ON s.id=d.item_id WHERE d.orden_id=o.id AND d.tipo='servicio') dur,
            (SELECT GROUP_CONCAT(d.descripcion SEPARATOR ', ') FROM orden_detalle d WHERE d.orden_id=o.id AND d.tipo='servicio') servicios,
            o.total - (SELECT COALESCE(SUM(p.monto),0) FROM orden_pagos p WHERE p.orden_id=o.id AND p.anulado=0) saldo, o.pagado
            FROM ordenes o LEFT JOIN vehiculos v ON v.id=o.vehiculo_id LEFT JOIN clientes c ON c.id=o.cliente_id LEFT JOIN empleados e ON e.id=o.empleado_id
            WHERE o.tipo='servicio' AND ((o.estado IN ('pendiente','en_proceso') AND o.fecha_ingreso >= DATE_SUB(CURDATE(), INTERVAL 7 DAY))
               OR (o.estado='terminado' AND o.fecha_ingreso >= DATE_SUB(CURDATE(), INTERVAL 3 DAY))
               OR (o.estado='entregado' AND DATE(o.fecha_ingreso)=CURDATE()))
            ORDER BY o.fecha_ingreso ASC LIMIT 300");
        $cols = array_fill_keys(array_keys(self::COLS), []);
        $atrasadas = 0;
        foreach ($rows as $r) {
            $r['mins'] = (int)$r['mins']; $r['dur'] = (int)$r['dur'] ?: 30; $r['saldo'] = max(0, round((float)$r['saldo'], 2));
            $r['nivel'] = in_array($r['estado'], ['pendiente', 'en_proceso']) ? ($r['mins'] > $r['dur'] * 1.25 ? 'late' : ($r['mins'] > $r['dur'] * 0.8 ? 'warn' : 'ok')) : 'done';
            if ($r['nivel'] === 'late') $atrasadas++;
            $r['total_txt'] = money($r['total']); $r['saldo_txt'] = money($r['saldo']);
            $r['link'] = url('ordenes/ver/' . $r['id']);
            $cols[$r['estado']][] = $r;
        }
        $prom = Database::val("SELECT ROUND(AVG(TIMESTAMPDIFF(MINUTE,fecha_ingreso,fecha_fin))) FROM ordenes WHERE tipo='servicio' AND estado IN ('terminado','entregado') AND fecha_fin IS NOT NULL AND DATE(fecha_ingreso)=CURDATE()");
        return ['cols' => $cols, 'atrasadas' => $atrasadas, 'prom' => $prom !== null ? (int)$prom : null, 'ahora' => date('H:i:s')];
    }

    public function index() {
        $this->requireModule('ordenes');
        $emps = Database::all('SELECT id,nombre FROM empleados WHERE estado=1 ORDER BY nombre');
        $this->view('tablero/index', ['emps' => $emps, 'cols' => self::COLS, 'data' => $this->datos(), 'title' => 'Tablero en vivo']);
    }
    public function datos_json() { $this->requireModule('ordenes'); $this->json($this->datos()); }

    public function mover() {
        $this->requireModule('ordenes');
        if (!$this->isPost()) $this->json(['ok' => false], 405);
        csrf_check();
        $id = (int)($_POST['id'] ?? 0); $nuevo = $_POST['estado'] ?? '';
        $o = Database::row('SELECT * FROM ordenes WHERE id=?', [$id]);
        if (!$o || !isset(self::COLS[$nuevo]) || $o['estado'] === 'cancelado') $this->json(['ok' => false, 'msg' => 'Movimiento no permitido.'], 422);
        Database::q('UPDATE ordenes SET estado=?, fecha_fin=IF(? IN ("terminado","entregado"), COALESCE(fecha_fin,NOW()), IF(? IN ("pendiente","en_proceso"), NULL, fecha_fin)) WHERE id=?', [$nuevo, $nuevo, $nuevo, $id]);
        log_action('ordenes', "Orden {$o['codigo']} -> $nuevo (tablero)");
        $this->json(['ok' => true]);
    }
    public function asignar() {
        $this->requireModule('ordenes');
        if (!$this->isPost()) $this->json(['ok' => false], 405);
        csrf_check();
        $emp = (int)($_POST['empleado_id'] ?? 0) ?: null;
        if ($emp && !Database::val('SELECT COUNT(*) FROM empleados WHERE id=? AND estado=1', [$emp])) $this->json(['ok' => false], 422);
        Database::q("UPDATE ordenes SET empleado_id=? WHERE id=? AND estado<>'cancelado'", [$emp, (int)($_POST['id'] ?? 0)]);
        $this->json(['ok' => true]);
    }
}
