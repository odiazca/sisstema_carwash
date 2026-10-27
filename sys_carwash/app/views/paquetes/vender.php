<div class="mb-4"><a href="<?= url('paquetes') ?>" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Paquetes</a><h1 class="page-title mt-1">Vender paquete</h1></div>
<div class="row g-3"><div class="col-lg-5"><div class="cw-card"><div class="card-b">
  <div class="sb-logo mb-3" style="width:56px;height:56px"><i class="bi bi-gift"></i></div>
  <h4 class="fw-bold"><?= e($p['nombre']) ?></h4><p class="text-muted"><?= e($p['descripcion'] ?? '') ?></p>
  <div class="d-flex justify-content-between border-top py-2"><span>Servicio</span><b><?= e($p['servicio']) ?></b></div>
  <div class="d-flex justify-content-between border-top py-2"><span>Usos incluidos</span><b><?= (int)$p['usos'] ?></b></div>
  <div class="d-flex justify-content-between border-top py-2"><span>Vigencia</span><b><?= (int)$p['vigencia_dias'] ?> días</b></div>
  <div class="d-flex justify-content-between border-top py-2"><span>Precio</span><b class="text-primary fs-5"><?= money($p['precio']) ?></b></div>
</div></div></div>
<div class="col-lg-7"><form method="post" action="<?= url('paquetes/vender_guardar') ?>" class="cw-card"><?= csrf_field() ?><input type="hidden" name="paquete_id" value="<?= $p['id'] ?>">
  <div class="card-b pt-4"><div class="row g-3">
    <div class="col-12"><label class="form-label">Cliente *</label><select class="form-select" name="cliente_id" required><option value="">— Seleccionar —</option>
      <?php foreach ($clientes as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['nombre']) ?><?= $c['nro_doc'] ? ' · ' . e($c['nro_doc']) : '' ?></option><?php endforeach; ?></select></div>
    <div class="col-md-6"><label class="form-label">Precio cobrado</label><input type="number" step="0.01" min="0" class="form-control" name="precio" value="<?= e($p['precio']) ?>"></div>
    <div class="col-md-6"><label class="form-label">Método de pago</label><select class="form-select" name="metodo_pago"><?php foreach (OrdenesController::PAGOS as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
  </div></div><div class="card-b border-top text-end"><button class="btn btn-grad px-4"><i class="bi bi-bag-check me-1"></i> Confirmar venta</button></div></form></div></div>
