<div class="mb-3"><a href="<?= url('porpagar') ?>" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Cuentas por pagar</a>
<h1 class="page-title mt-1"><?= e($p['nombre']) ?></h1><p class="page-sub">Estado de cuenta · <?= e($p['telefono'] ?? '') ?></p></div>
<div class="row g-3">
 <div class="col-lg-8">
  <div class="cw-card mb-3"><div class="card-h"><h6>Compras con saldo</h6><b class="text-danger"><?= money($deuda) ?></b></div><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Compra</th><th>Fecha</th><th>Vence</th><th class="text-end">Total</th><th class="text-end">Pagado</th><th class="text-end">Saldo</th></tr></thead><tbody>
  <?php foreach ($ord as $o): ?><tr><td><a class="fw-bold text-decoration-none" href="<?= url('compras/ver/' . $o['id']) ?>">#<?= $o['id'] ?></a> <span class="small text-muted"><?= e($o['nro_doc'] ?? '') ?></span></td><td><?= fdate($o['fecha']) ?></td>
  <td><?= $o['vence'] ? '<span class="badge badge-soft-' . ($o['atraso'] > 0 ? 'danger' : ($o['atraso'] >= -7 ? 'warning' : 'success')) . '">' . e(fdate($o['vence'])) . '</span>' : '-' ?></td><td class="text-end"><?= money($o['total']) ?></td><td class="text-end text-success"><?= money($o['pagado']) ?></td><td class="text-end fw-bold text-danger"><?= money($o['saldo']) ?></td></tr><?php endforeach; ?>
  <?php if (!$ord): ?><tr><td colspan="6" class="text-center text-muted py-4">Sin deudas pendientes ✅</td></tr><?php endif; ?></tbody></table></div></div>
  <div class="cw-card"><div class="card-h"><h6>Últimos pagos</h6></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Fecha</th><th>Compra</th><th>Método</th><th class="text-end">Monto</th></tr></thead><tbody>
  <?php foreach ($hist as $h): ?><tr><td><?= fdate($h['fecha'], true) ?></td><td>#<?= $h['compra_id'] ?></td><td><?= e(OrdenesController::PAGOS[$h['metodo']] ?? $h['metodo']) ?></td><td class="text-end fw-bold"><?= money($h['monto']) ?></td></tr><?php endforeach; if (!$hist): ?><tr><td colspan="4" class="text-center text-muted py-4">Sin pagos</td></tr><?php endif; ?></tbody></table></div></div>
 </div>
 <div class="col-lg-4"><div class="cw-card"><div class="card-h"><h6><i class="bi bi-cash-coin text-success me-2"></i>Registrar abono</h6></div><div class="card-b">
  <?php if ($ord): ?><form method="post" action="<?= url('porpagar/abonar') ?>" class="row g-2"><?= csrf_field() ?><input type="hidden" name="proveedor_id" value="<?= $p['id'] ?>">
   <div class="col-12"><label class="form-label small">Monto (máx. <?= money($deuda) ?>)</label><input type="number" step="0.01" min="0.01" max="<?= e(number_format($deuda, 2, '.', '')) ?>" name="monto" value="<?= e(number_format($deuda, 2, '.', '')) ?>" class="form-control" required></div>
   <div class="col-12"><label class="form-label small">Método</label><select name="metodo" class="form-select"><?php foreach (OrdenesController::PAGOS as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
   <div class="col-12"><input name="referencia" class="form-control" placeholder="Referencia / N° operación (opcional)" maxlength="80"></div>
   <div class="col-12"><button class="btn btn-grad w-100" data-confirm="¿Registrar el abono?">Aplicar abono</button><div class="form-text">Se aplica primero a las compras que vencen antes y se registra como gasto.</div></div></form>
  <?php else: ?><div class="text-muted text-center py-3">No hay deuda con este proveedor.</div><?php endif; ?></div></div></div>
</div>
