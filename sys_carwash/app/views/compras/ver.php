<div class="mb-4"><a href="<?= url('compras') ?>" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Compras</a><h1 class="page-title mt-1">Compra #<?= $c['id'] ?></h1>
<p class="page-sub"><?= fdate($c['fecha']) ?> · <?= e($c['proveedor'] ?? 'Sin proveedor') ?> · Doc: <?= e($c['nro_doc'] ?? '-') ?></p></div>
<div class="cw-card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Producto</th><th class="text-end">Cantidad</th><th class="text-end">Costo</th><th class="text-end">Subtotal</th></tr></thead><tbody>
<?php foreach ($items as $i): ?><tr><td><?= e($i['nombre']) ?></td><td class="text-end"><?= num($i['cantidad'], 2) ?> <?= e($i['unidad']) ?></td><td class="text-end"><?= money($i['costo']) ?></td><td class="text-end fw-bold"><?= money($i['subtotal']) ?></td></tr><?php endforeach; ?>
</tbody><tfoot><tr><th colspan="3" class="text-end">Total</th><th class="text-end text-primary fs-5"><?= money($c['total']) ?></th></tr></tfoot></table></div>
<?php if ($c['notas']): ?><div class="card-b border-top small text-muted"><?= e($c['notas']) ?></div><?php endif; ?></div>
<?php $saldo = max(0, round($c['total'] - $c['pagado'], 2)); if ($c['condicion'] === 'credito'): ?>
<div class="cw-card mt-3"><div class="card-b"><div class="d-flex justify-content-between align-items-center mb-2"><h6 class="fw-bold mb-0"><i class="bi bi-credit-card-2-front text-primary me-1"></i> Crédito del proveedor</h6>
<?= $saldo > 0.004 ? '<span class="badge badge-soft-warning">Saldo ' . e(money($saldo)) . '</span>' : '<span class="badge badge-soft-success">Pagada</span>' ?></div>
<div class="small text-muted mb-2">Vence: <b class="<?= $saldo > 0.004 && $c['vence'] && $c['vence'] < date('Y-m-d') ? 'text-danger' : '' ?>"><?= $c['vence'] ? fdate($c['vence']) : '-' ?></b> · Pagado: <b><?= money($c['pagado']) ?></b></div>
<?php foreach ($pagos as $x): ?><div class="d-flex justify-content-between border-bottom py-1 small"><span><?= fdate($x['fecha'], true) ?> · <?= e(OrdenesController::PAGOS[$x['metodo']] ?? $x['metodo']) ?> <?= $x['referencia'] ? '· ' . e($x['referencia']) : '' ?></span><b><?= money($x['monto']) ?></b></div><?php endforeach; ?>
<?php if ($saldo > 0.004): ?><form method="post" action="<?= url('porpagar/pagar') ?>" class="row g-2 mt-2"><?= csrf_field() ?><input type="hidden" name="compra_id" value="<?= $c['id'] ?>">
<div class="col-md-3"><input type="number" step="0.01" min="0.01" max="<?= e(number_format($saldo, 2, '.', '')) ?>" name="monto" value="<?= e(number_format($saldo, 2, '.', '')) ?>" class="form-control form-control-sm"></div>
<div class="col-md-3"><select name="metodo" class="form-select form-select-sm"><?php foreach (OrdenesController::PAGOS as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><input name="referencia" class="form-control form-control-sm" placeholder="Referencia" maxlength="80"></div>
<div class="col-md-3"><button class="btn btn-sm btn-grad w-100">Registrar pago</button></div></form><?php endif; ?></div></div>
<?php endif; ?>
