<?php $crmTab = 'cupones'; include __DIR__ . '/_nav.php'; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2"><div><h1 class="page-title">Cupones y promociones</h1><p class="page-sub">Descuentos que el cajero aplica en <b>Nueva orden</b> con el código</p></div>
<button class="btn btn-grad" data-bs-toggle="modal" data-bs-target="#cpModal"><i class="bi bi-plus-lg me-1"></i> Nuevo cupón</button></div>
<div class="cw-card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Código</th><th>Descuento</th><th>Cliente</th><th>Vigencia</th><th class="text-center">Usos</th><th>Origen</th><th>Estado</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $k): $venc = $k['vigente_hasta'] && $k['vigente_hasta'] < date('Y-m-d'); $agot = $k['usos_max'] > 0 && $k['usos'] >= $k['usos_max']; ?>
<tr><td><code class="fs-6 fw-bold"><?= e($k['codigo']) ?></code><div class="small text-muted"><?= e($k['descripcion']) ?></div></td>
<td class="fw-bold"><?= $k['tipo'] === 'porcentaje' ? num($k['valor'], 0) . '%' : money($k['valor']) ?></td><td><?= e($k['cliente'] ?? 'Cualquiera') ?></td>
<td><?= $k['vigente_hasta'] ? fdate($k['vigente_hasta']) : 'Sin vencimiento' ?></td><td class="text-center"><?= $k['usos'] ?>/<?= $k['usos_max'] ?: '∞' ?></td><td class="small"><?= e(ucfirst($k['origen'])) ?></td>
<td><?= !$k['estado'] ? '<span class="badge badge-soft-secondary">Desactivado</span>' : ($venc ? '<span class="badge badge-soft-danger">Vencido</span>' : ($agot ? '<span class="badge badge-soft-warning">Agotado</span>' : '<span class="badge badge-soft-success">Vigente</span>')) ?></td>
<td class="text-end"><form method="post" action="<?= url('crm/cupon_toggle') ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $k['id'] ?>"><button class="btn btn-sm btn-outline-secondary"><?= $k['estado'] ? 'Desactivar' : 'Activar' ?></button></form></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="8" class="text-center text-muted py-5">Aún no hay cupones</td></tr><?php endif; ?></tbody></table></div></div>
<div class="modal fade" id="cpModal" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="post" action="<?= url('crm/cupon_guardar') ?>"><?= csrf_field() ?>
 <div class="modal-header"><h5 class="modal-title">Nuevo cupón</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
 <div class="modal-body row g-2">
  <div class="col-6"><label class="form-label small">Código (vacío = automático)</label><input name="codigo" class="form-control text-uppercase" maxlength="30"></div>
  <div class="col-6"><label class="form-label small">Descripción</label><input name="descripcion" class="form-control" placeholder="Ej: Día de la madre"></div>
  <div class="col-6"><label class="form-label small">Tipo</label><select name="tipo" class="form-select"><option value="porcentaje">Porcentaje (%)</option><option value="monto">Monto fijo</option></select></div>
  <div class="col-6"><label class="form-label small">Valor</label><input name="valor" type="number" step="0.01" min="0.01" class="form-control" required></div>
  <div class="col-6"><label class="form-label small">Vigente hasta</label><input name="vigente_hasta" type="date" class="form-control"></div>
  <div class="col-6"><label class="form-label small">Usos máximos (0 = ilimitado)</label><input name="usos_max" type="number" min="0" value="1" class="form-control"></div>
  <div class="col-12"><label class="form-label small">Solo para el cliente (opcional)</label><select name="cliente_id" class="form-select"><option value="">Cualquier cliente</option><?php foreach ($clientes as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['nombre']) ?></option><?php endforeach; ?></select></div></div>
 <div class="modal-footer"><button class="btn btn-grad">Crear cupón</button></div></form></div></div>
