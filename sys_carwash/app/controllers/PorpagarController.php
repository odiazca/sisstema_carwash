<?php
/** Cuentas por pagar: compras a crédito a proveedores, vencimientos y abonos (cada pago se registra como gasto). */
class PorpagarController extends Controller {
    private function pendientes(?int $pid = null): array {
        $w = "c.condicion='credito' AND c.total-c.pagado>0.004"; $p = [];
        if ($pid) { $w .= ' AND c.proveedor_id=?'; $p[] = $pid; }
        return Database::all("SELECT c.id,c.proveedor_id,c.fecha,c.vence,c.nro_doc,c.total,c.pagado, ROUND(c.total-c.pagado,2) saldo, p.nombre proveedor, p.telefono,
            DATEDIFF(CURDATE(), c.vence) atraso FROM compras c LEFT JOIN proveedores p ON p.id=c.proveedor_id WHERE $w ORDER BY c.vence, c.id", $p);
    }

    /** Registra un pago (limitado al saldo) y lo contabiliza como gasto de Insumos. */
    public static function registrar(array $c, float $monto, string $metodo, ?string $ref): float {
        if (!isset(OrdenesController::PAGOS[$metodo])) $metodo = 'efectivo';
        $saldo = round((float)$c['total'] - (float)$c['pagado'], 2);
        $monto = round(min(max(0, $monto), $saldo), 2);
        if ($monto <= 0) return 0;
        $uid = (int)auth_user()['id'];
        Database::q('INSERT INTO compra_pagos (compra_id,monto,metodo,referencia,usuario_id) VALUES (?,?,?,?,?)', [$c['id'], $monto, $metodo, $ref ? mb_substr($ref, 0, 80) : null, $uid]);
        Database::q('UPDATE compras SET pagado=pagado+? WHERE id=?', [$monto, $c['id']]);
        Database::q("INSERT INTO gastos (fecha,categoria,concepto,monto,usuario_id) VALUES (CURDATE(), 'Insumos', ?, ?, ?)", ["Pago compra #{$c['id']}" . (!empty($c['nro_doc']) ? ' · ' . $c['nro_doc'] : ''), $monto, $uid]);
        return $monto;
    }

    public function index() {
        $this->requireModule('productos');
        $all = $this->pendientes();
        $k = ['total' => 0, 'vencido' => 0, 'prox7' => 0, 'compras' => count($all)]; $por = [];
        foreach ($all as $r) {
            $k['total'] += $r['saldo'];
            if ($r['vence'] && $r['atraso'] > 0) $k['vencido'] += $r['saldo']; elseif ($r['vence'] && $r['atraso'] >= -7) $k['prox7'] += $r['saldo'];
            $key = (int)$r['proveedor_id'];
            $por[$key] ??= ['id' => $key, 'proveedor' => $r['proveedor'] ?? 'Sin proveedor', 'telefono' => $r['telefono'], 'n' => 0, 'deuda' => 0, 'vence' => null, 'atraso' => -9999];
            $por[$key]['n']++; $por[$key]['deuda'] += $r['saldo']; $por[$key]['atraso'] = max($por[$key]['atraso'], (int)$r['atraso']);
            if ($r['vence'] && (!$por[$key]['vence'] || $r['vence'] < $por[$key]['vence'])) $por[$key]['vence'] = $r['vence'];
        }
        usort($por, fn($a, $b) => $b['atraso'] <=> $a['atraso'] ?: $b['deuda'] <=> $a['deuda']);
        $this->view('porpagar/index', ['k' => $k, 'por' => $por, 'title' => 'Cuentas por pagar']);
    }

    public function proveedor($id = 0) {
        $this->requireModule('productos');
        $p = Database::row('SELECT * FROM proveedores WHERE id=?', [(int)$id]);
        if (!$p) { flash('danger', 'Proveedor no encontrado.'); $this->redirect('porpagar'); }
        $ord = $this->pendientes((int)$p['id']);
        $hist = Database::all('SELECT x.*, c.nro_doc FROM compra_pagos x JOIN compras c ON c.id=x.compra_id WHERE c.proveedor_id=? ORDER BY x.id DESC LIMIT 20', [$p['id']]);
        $this->view('porpagar/proveedor', ['p' => $p, 'ord' => $ord, 'hist' => $hist, 'deuda' => array_sum(array_column($ord, 'saldo')), 'title' => 'Estado de cuenta']);
    }

    public function pagar() {
        $this->requireModule('productos');
        if (!$this->isPost()) $this->redirect('porpagar');
        csrf_check();
        $c = Database::row("SELECT * FROM compras WHERE id=? AND condicion='credito'", [(int)($_POST['compra_id'] ?? 0)]);
        if (!$c) { flash('danger', 'Compra no encontrada.'); $this->redirect('porpagar'); }
        $pdo = Database::pdo(); $pdo->beginTransaction();
        try {
            $m = self::registrar($c, (float)str_replace(',', '.', (string)($_POST['monto'] ?? 0)), $_POST['metodo'] ?? 'efectivo', trim($_POST['referencia'] ?? '') ?: null);
            if ($m <= 0) throw new RuntimeException('Monto inválido o la compra ya está pagada.');
            $pdo->commit(); log_action('compras', "Pago de compra #{$c['id']}: " . num($m)); flash('success', 'Pago de ' . money($m) . ' registrado.');
        } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); flash('danger', e($e->getMessage())); }
        $this->redirect('compras/ver/' . $c['id']);
    }

    /** Abono a cuenta del proveedor: se aplica primero a las compras que vencen antes. */
    public function abonar() {
        $this->requireModule('productos');
        if (!$this->isPost()) $this->redirect('porpagar');
        csrf_check();
        $pid = (int)($_POST['proveedor_id'] ?? 0); $monto = round((float)str_replace(',', '.', (string)($_POST['monto'] ?? 0)), 2);
        $ord = $this->pendientes($pid); $deuda = array_sum(array_column($ord, 'saldo'));
        if ($monto <= 0 || !$ord) { flash('danger', 'Ingresa un monto válido.'); $this->redirect('porpagar/proveedor/' . $pid); }
        if ($monto > $deuda + 0.004) { flash('danger', 'El abono (' . money($monto) . ') supera la deuda (' . money($deuda) . ').'); $this->redirect('porpagar/proveedor/' . $pid); }
        $pdo = Database::pdo(); $pdo->beginTransaction(); $rest = $monto; $n = 0;
        try {
            foreach ($ord as $c) {
                if ($rest <= 0.004) break;
                $m = self::registrar(['id' => $c['id'], 'total' => $c['total'], 'pagado' => $c['pagado'], 'nro_doc' => $c['nro_doc']], min($rest, $c['saldo']), $_POST['metodo'] ?? 'efectivo', trim($_POST['referencia'] ?? '') ?: null);
                $rest = round($rest - $m, 2); if ($m > 0) $n++;
            }
            $pdo->commit(); log_action('compras', "Abono a proveedor #$pid: " . num($monto)); flash('success', 'Abono de ' . money($monto) . " aplicado a $n compra(s).");
        } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); flash('danger', e($e->getMessage())); }
        $this->redirect('porpagar/proveedor/' . $pid);
    }
}
