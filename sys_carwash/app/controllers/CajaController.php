<?php
class CajaController extends Controller {
    private function resumen(array $caja): array {
        $ini = $caja['fecha_apertura']; $fin = $caja['fecha_cierre'] ?? date('Y-m-d H:i:s');
        $pagos = Database::all("SELECT p.metodo m, COUNT(*) n, SUM(p.monto+p.propina) t, SUM(p.propina) prop FROM orden_pagos p JOIN ordenes o ON o.id=p.orden_id WHERE p.anulado=0 AND o.estado<>'cancelado' AND p.fecha BETWEEN ? AND ? GROUP BY p.metodo", [$ini, $fin]);
        $porMetodo = array_column($pagos, null, 'm');
        $efectivo = (float)($porMetodo['efectivo']['t'] ?? 0);
        $gastos = (float)Database::val('SELECT COALESCE(SUM(monto),0) FROM gastos WHERE created_at BETWEEN ? AND ?', [$ini, $fin]);
        $total = array_sum(array_map(fn($p) => (float)$p['t'], $pagos));
        return ['porMetodo' => $porMetodo, 'efectivo' => $efectivo, 'gastos' => $gastos, 'total' => $total,
                'esperado' => (float)$caja['monto_inicial'] + $efectivo - $gastos];
    }

    public function index() {
        $this->requireModule('caja');
        $abierta = Database::row("SELECT c.*, u.nombre usuario FROM cajas c JOIN usuarios u ON u.id=c.usuario_id WHERE c.estado='abierta' ORDER BY c.id DESC LIMIT 1");
        $res = $abierta ? $this->resumen($abierta) : null;
        $hist = Database::all("SELECT c.*, u.nombre usuario FROM cajas c JOIN usuarios u ON u.id=c.usuario_id WHERE c.estado='cerrada' ORDER BY c.id DESC LIMIT 30");
        $this->view('caja/index', ['abierta' => $abierta, 'res' => $res, 'hist' => $hist, 'title' => 'Caja']);
    }

    public function abrir() {
        $this->requireModule('caja');
        if (!$this->isPost()) $this->redirect('caja');
        csrf_check();
        if (Database::val("SELECT COUNT(*) FROM cajas WHERE estado='abierta'")) { flash('warning', 'Ya existe una caja abierta.'); $this->redirect('caja'); }
        Database::q('INSERT INTO cajas (usuario_id,monto_inicial) VALUES (?,?)', [auth_user()['id'], max(0, (float)($_POST['monto_inicial'] ?? 0))]);
        log_action('caja', 'Apertura de caja');
        flash('success', 'Caja abierta. ¡Buen turno!');
        $this->redirect('caja');
    }

    public function cerrar() {
        $this->requireModule('caja');
        if (!$this->isPost()) $this->redirect('caja');
        csrf_check();
        $caja = Database::row("SELECT * FROM cajas WHERE estado='abierta' ORDER BY id DESC LIMIT 1");
        if (!$caja) $this->redirect('caja');
        $res = $this->resumen($caja);
        Database::q("UPDATE cajas SET estado='cerrada', fecha_cierre=NOW(), monto_final=?, monto_esperado=?, observaciones=? WHERE id=?",
            [(float)($_POST['monto_final'] ?? 0), $res['esperado'], trim($_POST['observaciones'] ?? '') ?: null, $caja['id']]);
        log_action('caja', 'Cierre de caja #' . $caja['id']);
        flash('success', 'Caja cerrada correctamente.');
        $this->redirect('caja');
    }
}
