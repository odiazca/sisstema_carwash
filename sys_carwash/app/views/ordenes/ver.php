<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div><a href="<?= url('ordenes') ?>" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Órdenes</a>
  <h1 class="page-title mt-1"><?= e($o['codigo']) ?> <?= estado_badge($o['estado']) ?></h1><p class="page-sub">Ingreso: <?= fdate($o['fecha_ingreso'], true) ?><?= $o['fecha_fin'] ? ' · Fin: ' . fdate($o['fecha_fin'], true) : '' ?></p></div>
  <a href="<?= url('ordenes/ticket/' . $o['id']) ?>" target="_blank" class="btn btn-outline-primary"><i class="bi bi-printer me-1"></i> Imprimir ticket</a>
</div>
<div class="row g-3">
 <div class="col-lg-8">
  <div class="cw-card mb-3"><div class="card-h"><h6>Detalle</h6></div>
   <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Descripción</th><th class="text-end">Cant.</th><th class="text-end">Precio</th><th class="text-end">Importe</th></tr></thead><tbody>
   <?php foreach ($o['items'] as $d): ?><tr><td><?= e($d['descripcion']) ?> <span class="badge badge-soft-secondary"><?= e($d['tipo']) ?></span></td><td class="text-end"><?= num($d['cantidad'], 0) ?></td><td class="text-end"><?= money($d['precio']) ?></td><td class="text-end fw-bold"><?= money($d['subtotal']) ?></td></tr><?php endforeach; ?>
   </tbody></table></div>
   <div class="card-b ms-auto" style="max-width:320px">
     <div class="d-flex justify-content-between"><span class="text-muted">Subtotal</span><span><?= money($o['subtotal']) ?></span></div>
     <div class="d-flex justify-content-between"><span class="text-muted">Descuento</span><span>- <?= money($o['descuento']) ?></span></div>
     <div class="d-flex justify-content-between"><span class="text-muted"><?= e(empresa()['impuesto_nombre']) ?></span><span><?= money($o['impuesto']) ?></span></div>
     <hr class="my-2"><div class="d-flex justify-content-between fs-4 fw-bold text-primary"><span>Total</span><span><?= money($o['total']) ?></span></div>
   </div></div>
 </div>
 <div class="col-lg-4">
  <div class="cw-card mb-3"><div class="card-b">
    <h6 class="fw-bold mb-3">Cliente y vehículo</h6>
    <div class="mb-2"><i class="bi bi-person text-primary me-2"></i><b><?= e($o['cliente'] ?? 'Cliente general') ?></b><div class="small text-muted ms-4"><?= e($o['telefono'] ?? '') ?></div></div>
    <div class="mb-2"><i class="bi bi-car-front text-primary me-2"></i><b><?= e($o['placa'] ?? '-') ?></b> <span class="text-muted small"><?= e(trim(($o['marca'] ?? '') . ' ' . ($o['modelo'] ?? ''))) ?></span></div>
    <div class="mb-2"><i class="bi bi-person-badge text-primary me-2"></i>Lavador: <b><?= e($o['empleado'] ?? 'Sin asignar') ?></b></div>
    <div class="mb-0"><i class="bi bi-person-workspace text-primary me-2"></i>Registró: <?= e($o['cajero'] ?? '-') ?></div>
    <?php if ($o['observaciones']): ?><div class="alert alert-light border mt-3 mb-0 small"><?= e($o['observaciones']) ?></div><?php endif; ?>
  </div></div>
  <?php if ($o['estado'] !== 'cancelado'): ?>
  <?php if ($o['estado'] !== 'cancelado'): ?><div class="cw-card mb-3"><div class="card-b"><h6 class="fw-bold mb-2"><i class="bi bi-file-earmark-ruled text-primary me-1"></i> Comprobante</h6>
    <?php if ($comp): ?><div class="d-flex justify-content-between align-items-center"><div><b><?= e($comp['serie']) ?>-<?= str_pad((string)$comp['numero'], 8, '0', STR_PAD_LEFT) ?></b><div class="small text-muted"><?= e(ComprobantesController::TIPOS[$comp['tipo']]) ?></div></div>
      <div class="d-flex gap-1"><a class="btn btn-sm btn-outline-primary" target="_blank" href="<?= url('comprobantes/imprimir/' . $comp['id']) ?>"><i class="bi bi-printer"></i></a>
      <form method="post" action="<?= url('comprobantes/anular') ?>" onsubmit="return confirm('¿Anular este comprobante?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $comp['id'] ?>"><input type="hidden" name="volver" value="orden"><button class="btn btn-sm btn-light text-danger" title="Anular"><i class="bi bi-x-circle"></i></button></form></div></div>
    <?php else: ?><form method="post" action="<?= url('comprobantes/emitir') ?>" class="row g-2"><?= csrf_field() ?><input type="hidden" name="orden_id" value="<?= $o['id'] ?>">
      <div class="col-12"><select name="tipo" class="form-select form-select-sm" id="cmpTipo"><?php foreach (ComprobantesController::TIPOS as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
      <div class="col-5"><input name="doc_numero" class="form-control form-control-sm" placeholder="DNI / RUC" maxlength="11" value="<?= e($cli['nro_doc'] ?? '') ?>"></div>
      <div class="col-7"><input name="razon_social" class="form-control form-control-sm" placeholder="Nombre / razón social" maxlength="150" value="<?= e($cli['nombre'] ?? '') ?>"></div>
      <div class="col-12"><input name="direccion" class="form-control form-control-sm" placeholder="Dirección (opcional)" maxlength="200" value="<?= e($cli['direccion'] ?? '') ?>"></div>
      <div class="col-12"><button class="btn btn-sm btn-grad w-100">Emitir comprobante</button></div></form><div class="form-text">Documento interno; no reemplaza la facturación electrónica SUNAT.</div><?php endif; ?></div></div><?php endif; ?>
  <div class="cw-card mb-3"><div class="card-b"><h6 class="fw-bold mb-3">Cambiar estado</h6>
    <form method="post" action="<?= url('ordenes/estado') ?>" class="d-flex gap-2"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $o['id'] ?>">
      <select name="estado" class="form-select"><?php foreach (OrdenesController::ESTADOS as $k => $l): ?><option value="<?= $k ?>" <?= $o['estado'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
      <button class="btn btn-primary" data-confirm="¿Actualizar estado? (Cancelar devuelve el stock)">OK</button></form></div></div>
  <?php $saldo = max(0, round($o['total'] - $o['abonado'], 2)); ?>
  <div class="cw-card"><div class="card-b"><div class="d-flex justify-content-between align-items-center mb-2"><h6 class="fw-bold mb-0">Pagos</h6>
    <?= $o['pagado'] ? '<span class="badge badge-soft-success">Pagado</span>' : ($o['abonado'] > 0 ? '<span class="badge badge-soft-info">Abono parcial</span>' : '<span class="badge badge-soft-warning">Pendiente</span>') ?></div>
    <?php if ($o['pagos']): ?><div class="mb-3"><?php foreach ($o['pagos'] as $x): ?><div class="d-flex justify-content-between small py-1 border-bottom <?= $x['anulado'] ? 'text-decoration-line-through text-muted' : '' ?>"><span><?= fdate($x['fecha'], true) ?> · <?= e(OrdenesController::PAGOS[$x['metodo']] ?? $x['metodo']) ?><?= $x['referencia'] ? ' · ' . e($x['referencia']) : '' ?><?= $x['propina'] > 0 ? ' · propina ' . money($x['propina']) : '' ?></span><b><?= money($x['monto']) ?></b></div><?php endforeach; ?></div><?php endif; ?>
    <div class="d-flex justify-content-between"><span class="text-muted">Abonado</span><b class="text-success"><?= money($o['abonado']) ?></b></div>
    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Saldo</span><b class="<?= $saldo > 0 ? 'text-danger' : 'text-success' ?>"><?= money($saldo) ?></b></div>
    <?php if (!$o['pagado']): ?>
      <form method="post" action="<?= url('ordenes/cobrar') ?>" class="row g-2"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $o['id'] ?>">
        <div class="col-6"><label class="form-label small mb-0">Monto</label><input type="number" step="0.01" min="0.01" name="monto" value="<?= e(number_format($saldo, 2, '.', '')) ?>" class="form-control form-control-sm"></div>
        <div class="col-6"><label class="form-label small mb-0">Método</label><select name="metodo_pago" class="form-select form-select-sm"><?php foreach (OrdenesController::PAGOS as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
        <div class="col-6"><input name="referencia" class="form-control form-control-sm" placeholder="Referencia / N° operación" maxlength="80"></div>
        <div class="col-6"><input type="number" step="0.01" min="0" name="propina" class="form-control form-control-sm" placeholder="Propina"></div>
        <div class="col-12"><button class="btn btn-grad w-100"><i class="bi bi-cash-coin me-1"></i> Registrar pago</button></div></form>
    <?php endif; ?>
  </div></div>
  <?php endif; ?>
 </div>
</div>
