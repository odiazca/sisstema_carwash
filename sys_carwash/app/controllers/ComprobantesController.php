<?php
/** Comprobantes internos (boleta, factura, nota de venta) con serie y correlativo. No sustituyen la facturación electrónica SUNAT. */
class ComprobantesController extends Controller {
    const TIPOS = ['boleta' => 'Boleta de venta', 'factura' => 'Factura', 'nota_venta' => 'Nota de venta'];
    const SERIES = ['boleta' => 'B001', 'factura' => 'F001', 'nota_venta' => 'NV01'];

    public function index() {
        $this->requireModule('caja');
        $desde = $_GET['desde'] ?? date('Y-m-01'); $hasta = $_GET['hasta'] ?? date('Y-m-d');
        $rows = Database::all('SELECT k.*, o.codigo orden FROM comprobantes k JOIN ordenes o ON o.id=k.orden_id WHERE DATE(k.fecha) BETWEEN ? AND ? ORDER BY k.id DESC LIMIT 1000', [$desde, $hasta]);
        $tot = ['n' => 0, 'total' => 0, 'anulados' => 0];
        foreach ($rows as $r) { if ($r['anulado']) $tot['anulados']++; else { $tot['n']++; $tot['total'] += $r['total']; } }
        $series = Database::all('SELECT * FROM comprobante_series');
        $this->view('comprobantes/index', ['rows' => $rows, 'desde' => $desde, 'hasta' => $hasta, 'tot' => $tot, 'series' => array_column($series, null, 'tipo'), 'title' => 'Comprobantes']);
    }

    public function emitir() {
        $this->requireModule('caja');
        if (!$this->isPost()) $this->redirect('comprobantes');
        csrf_check();
        $oid = (int)($_POST['orden_id'] ?? 0);
        $o = Database::row('SELECT * FROM ordenes WHERE id=?', [$oid]);
        $back = 'ordenes/ver/' . $oid;
        if (!$o || $o['estado'] === 'cancelado') { flash('danger', 'La orden no existe o está cancelada.'); $this->redirect('ordenes'); }
        $tipo = isset(self::TIPOS[$_POST['tipo'] ?? '']) ? $_POST['tipo'] : 'boleta';
        $doc = preg_replace('/\D/', '', $_POST['doc_numero'] ?? ''); $razon = trim($_POST['razon_social'] ?? ''); $dir = trim($_POST['direccion'] ?? '');
        if ($tipo === 'factura' && (strlen($doc) !== 11 || $razon === '')) { flash('danger', 'La factura requiere RUC de 11 dígitos y razón social.'); $this->redirect($back); }
        if ($tipo === 'boleta' && $o['total'] >= 700 && strlen($doc) < 8) { flash('danger', 'Boletas desde S/ 700 requieren documento del cliente.'); $this->redirect($back); }
        $pdo = Database::pdo(); $pdo->beginTransaction();
        try {
            if (Database::val('SELECT COUNT(*) FROM comprobantes WHERE orden_id=? AND anulado=0 FOR UPDATE', [$oid])) throw new RuntimeException('Esta orden ya tiene un comprobante emitido. Anúlalo para emitir otro.');
            Database::q('INSERT IGNORE INTO comprobante_series (tipo,serie,correlativo) VALUES (?,?,0)', [$tipo, self::SERIES[$tipo]]);
            $s = Database::row('SELECT * FROM comprobante_series WHERE tipo=? FOR UPDATE', [$tipo]);
            $num = (int)$s['correlativo'] + 1;
            Database::q('UPDATE comprobante_series SET correlativo=? WHERE tipo=?', [$num, $tipo]);
            Database::q('INSERT INTO comprobantes (orden_id,tipo,serie,numero,doc_tipo,doc_numero,razon_social,direccion,subtotal,impuesto,total,usuario_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)', [
                $oid, $tipo, $s['serie'], $num, $tipo === 'factura' ? 'RUC' : ($doc ? (strlen($doc) === 8 ? 'DNI' : 'DOC') : null), $doc ?: null, $razon ?: null, $dir ?: null,
                round($o['total'] - $o['impuesto'], 2), $o['impuesto'], $o['total'], auth_user()['id']]);
            $id = (int)Database::id();
            $pdo->commit();
            log_action('caja', "Emitió {$s['serie']}-" . str_pad((string)$num, 8, '0', STR_PAD_LEFT));
            flash('success', 'Comprobante <b>' . $s['serie'] . '-' . str_pad((string)$num, 8, '0', STR_PAD_LEFT) . "</b> emitido. <a target='_blank' class='fw-bold' href='" . url('comprobantes/imprimir/' . $id) . "'>Imprimir</a>");
        } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); flash('danger', e($e->getMessage())); }
        $this->redirect($back);
    }

    public function imprimir($id = 0) {
        $this->requireModule('caja');
        $k = Database::row('SELECT * FROM comprobantes WHERE id=?', [(int)$id]);
        if (!$k) { flash('danger', 'Comprobante no encontrado.'); $this->redirect('comprobantes'); }
        $o = Database::row('SELECT o.*, c.nombre cliente FROM ordenes o LEFT JOIN clientes c ON c.id=o.cliente_id WHERE o.id=?', [$k['orden_id']]);
        $items = Database::all('SELECT * FROM orden_detalle WHERE orden_id=? ORDER BY id', [$k['orden_id']]);
        $this->view('comprobantes/imprimir', ['k' => $k, 'o' => $o, 'items' => $items, 'emp' => empresa()], 'none');
    }

    public function anular() {
        $this->requireModule('caja');
        if (!$this->isPost()) $this->redirect('comprobantes');
        csrf_check();
        $k = Database::row('SELECT * FROM comprobantes WHERE id=?', [(int)($_POST['id'] ?? 0)]);
        if ($k && !$k['anulado']) { Database::q('UPDATE comprobantes SET anulado=1 WHERE id=?', [$k['id']]); log_action('caja', "Anuló comprobante {$k['serie']}-{$k['numero']}"); flash('success', 'Comprobante anulado. Su número no se reutiliza.'); }
        $this->redirect(($_POST['volver'] ?? '') === 'orden' ? 'ordenes/ver/' . (int)$k['orden_id'] : 'comprobantes');
    }
}
