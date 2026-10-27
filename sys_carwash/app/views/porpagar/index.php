<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2"><div><h1 class="page-title">Cuentas por pagar</h1><p class="page-sub">Compras a crédito a proveedores, vencimientos y abonos</p></div>
<a href="<?= url('compras/nueva') ?>" class="btn btn-outline-primary"><i class="bi bi-bag-plus me-1"></i> Nueva compra</a></div>
<div class="row g-3 mb-3">
<?php foreach ([['Total por pagar', money($k['total']), 'bi-credit-card-2-front', 'amber', $k['compras'] . ' compras con saldo'], ['Vencido', money($k['vencido']), 'bi-exclamation-octagon', 'rose', 'Pasó la fecha de pago'], ['Vence en 7 días', money($k['prox7']), 'bi-alarm', 'blue', 'Próximos pagos'], ['Proveedores', count($por), 'bi-truck', 'green', 'Con deuda pendiente']] as [$l, $v, $i, $c, $s]): ?>
  <div class="col-6 col-xl-3"><div class="cw-card stat t-<?= $c ?>"><div class="d-flex align-items-center gap-3"><div class="ico bg-<?= $c ?>"><i class="bi <?= $i ?>"></i></div><div><div class="l"><?= $l ?></div><div class="fs-4 fw-bold text-dark lh-1"><?= $v ?></div><div class="small text-muted mt-1"><?= $s ?></div></div></div></div></div>
<?php endforeach; ?></div>
<div class="cw-card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Proveedor</th><th class="text-center">Compras</th><th>Próximo vencimiento</th><th class="text-end">Deuda</th><th></th></tr></thead><tbody>
<?php foreach ($por as $x): $b = $x['atraso'] > 0 ? 'danger' : ($x['atraso'] >= -7 ? 'warning' : 'success'); ?>
<tr><td><b><?= e($x['proveedor']) ?></b><div class="small text-muted"><?= e($x['telefono'] ?? '') ?></div></td><td class="text-center"><?= $x['n'] ?></td>
<td><?php if ($x['vence']): ?><span class="badge badge-soft-<?= $b ?>"><?= fdate($x['vence']) ?><?= $x['atraso'] > 0 ? ' · ' . $x['atraso'] . ' d de atraso' : '' ?></span><?php else: ?>-<?php endif; ?></td>
<td class="text-end fw-bold text-danger"><?= money($x['deuda']) ?></td>
<td class="text-end"><?php if ($x['id'] > 0): ?><a class="btn btn-sm btn-grad" href="<?= url('porpagar/proveedor/' . $x['id']) ?>"><i class="bi bi-cash-coin me-1"></i> Estado de cuenta</a><?php endif; ?></td></tr>
<?php endforeach; if (!$por): ?><tr><td colspan="5" class="text-center text-muted py-5"><i class="bi bi-check-circle fs-1 d-block text-success"></i>No tienes deudas con proveedores.<div class="small mt-1">Al registrar una compra con condición <b>Crédito</b> aparecerá aquí.</div></td></tr><?php endif; ?></tbody></table></div></div>
