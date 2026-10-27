<?php
/** Cuentas por cobrar: saldos pendientes por cliente, antigüedad y abonos a cuenta. */
class CobranzasController extends Controller {
    private function pendientes(?int $cid = null, bool $sinCliente = false): array {
        $w = "o.pagado=0 AND o.estado<>'cancelado'"; $p = [];
        if ($cid) { $w .= ' AND o.cliente_id=?'; $p[] = $cid; }
        if ($sinCliente) $w .= ' AND o.cliente_id IS NULL';
        $rows = Database::all("SELECT o.id,o.codigo,o.cliente_id,o.total,o.estado,o.fecha_ingreso, v.placa, c.nombre cliente, c.telefono,
            (SELECT COALESCE(SUM(p.monto),0) FROM orden_pagos p WHERE p.orden_id=o.id AND p.anulado=0) abonado, DATEDIFF(CURDATE(), DATE(o.fecha_ingreso)) dias
            FROM ordenes o LEFT JOIN clientes c ON c.id=o.cliente_id LEFT JOIN vehiculos v ON v.id=o.vehiculo_id WHERE $w ORDER BY o.fecha_ingreso", $p);
        $out = [];
        foreach ($rows as $r) { $r['saldo'] = round($r['total'] - $r['abonado'], 2); if ($r['saldo'] > 0.004) $out[] = $r; }
        return $out;
    }

    public function index() {
        $this->requireModule('caja');
        $all = $this->pendientes();
        $k = ['total' => 0, 'ordenes' => count($all), 'v0' => 0, 'v7' => 0, 'v30' => 0];
        $por = [];
        foreach ($all as $r) {
            $k['total'] += $r['saldo'];
            if ($r['dias'] <= 7) $k['v0'] += $r['saldo']; elseif ($r['dias'] <= 30) $k['v7'] += $r['saldo']; else $k['v30'] += $r['saldo'];
            $key = (int)$r['cliente_id'];
            $por[$key] ??= ['id' => $key, 'cliente' => $r['cliente'] ?? 'Sin cliente registrado', 'telefono' => $r['telefono'], 'n' => 0, 'deuda' => 0, 'dias' => 0, 'ordenes' => []];
            $por[$key]['n']++; $por[$key]['deuda'] += $r['saldo']; $por[$key]['dias'] = max($por[$key]['dias'], (int)$r['dias']); $por[$key]['ordenes'][] = $r;
        }
        usort($por, fn($a, $b) => $b['deuda'] <=> $a['deuda']);
        $k['clientes'] = count(array_filter($por, fn($x) => $x['id'] > 0));
        $this->view('cobranzas/index', ['k' => $k, 'por' => $por, 'title' => 'Cuentas por cobrar']);
    }

    public function cliente($id = 0) {
        $this->requireModule('caja');
        $c = Database::row('SELECT * FROM clientes WHERE id=?', [(int)$id]);
        if (!$c) { flash('danger', 'Cliente no encontrado.'); $this->redirect('cobranzas'); }
        $ord = $this->pendientes((int)$c['id']);
        $hist = Database::all("SELECT p.*, o.codigo FROM orden_pagos p JOIN ordenes o ON o.id=p.orden_id WHERE o.cliente_id=? AND p.anulado=0 ORDER BY p.id DESC LIMIT 20", [$c['id']]);
        $this->view('cobranzas/cliente', ['c' => $c, 'ord' => $ord, 'hist' => $hist, 'deuda' => array_sum(array_column($ord, 'saldo')), 'title' => 'Estado de cuenta']);
    }

    /** Abono a cuenta: se aplica a las órdenes más antiguas primero. */
    public function abonar() {
        $this->requireModule('caja');
        if (!$this->isPost()) $this->redirect('cobranzas');
        csrf_check();
        $cid = (int)($_POST['cliente_id'] ?? 0); $monto = round((float)str_replace(',', '.', (string)($_POST['monto'] ?? 0)), 2);
        $metodo = isset(OrdenesController::PAGOS[$_POST['metodo_pago'] ?? '']) ? $_POST['metodo_pago'] : 'efectivo';
        $ref = trim($_POST['referencia'] ?? '') ?: null;
        $ord = $this->pendientes($cid);
        $deuda = array_sum(array_column($ord, 'saldo'));
        if ($monto <= 0 || !$ord) { flash('danger', 'Ingresa un monto válido.'); $this->redirect('cobranzas/cliente/' . $cid); }
        if ($monto > $deuda + 0.004) { flash('danger', 'El abono (' . money($monto) . ') supera la deuda (' . money($deuda) . ').'); $this->redirect('cobranzas/cliente/' . $cid); }
        $pdo = Database::pdo(); $pdo->beginTransaction(); $rest = $monto; $cerradas = 0;
        try {
            foreach ($ord as $o) {
                if ($rest <= 0.004) break;
                $r = orden_registrar_pago(['id' => $o['id'], 'total' => $o['total']], min($rest, $o['saldo']), $metodo, 0, $ref, (int)auth_user()['id']);
                $rest = round($rest - $r['monto'], 2); if ($r['pagado']) $cerradas++;
            }
            $pdo->commit();
        } catch (Throwable $e) { $pdo->rollBack(); flash('danger', 'No se pudo registrar el abono.'); $this->redirect('cobranzas/cliente/' . $cid); }
        log_action('cobranzas', 'Abono de ' . num($monto) . " cliente #$cid");
        flash('success', 'Abono de <b>' . money($monto) . "</b> aplicado ($cerradas orden(es) saldada(s)).");
        $this->redirect('cobranzas/cliente/' . $cid);
    }
}
