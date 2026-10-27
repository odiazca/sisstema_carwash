<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <div><h1 class="page-title">Cotizaciones</h1><p class="page-sub">Presupuestos para clientes, con validez y conversión a orden</p></div>
  <a href="<?= url('cotizaciones/nueva') ?>" class="btn btn-grad"><i class="bi bi-plus-lg me-1"></i> Nueva cotización</a>
</div>
<div class="row g-2 mb-3"><?php foreach (CotizacionesController::ESTADOS as $k => $l): ?>
  <div class="col-6 col-md"><a href="?estado=<?= $k ?>" class="text-decoration-none"><div class="cw-card p-3 text-center <?= $estado === $k ? 'border-primary' : '' ?>"><div class="fs-3 fw-bold text-dark"><?= (int)($res[$k]['n'] ?? 0) ?></div><span class="badge badge-soft-<?= CotizacionesController::COLOR[$k] ?>"><?= $l ?></span><div class="small text-muted mt-1"><?= money($res[$k]['t'] ?? 0) ?></div></div></a></div>
<?php endforeach; ?></div>
<div class="cw-card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Código</th><th>Fecha</th><th>Cliente</th><th>Placa</th><th>Vence</th><th>Estado</th><th class="text-end">Total</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): $venc = $r['vence'] && $r['vence'] < date('Y-m-d') && in_array($r['estado'], ['borrador', 'enviada']); ?>
<tr><td><a class="fw-bold text-decoration-none" href="<?= url('cotizaciones/ver/' . $r['id']) ?>"><?= e($r['codigo']) ?></a></td><td><?= fdate($r['created_at']) ?></td><td><?= e($r['cliente'] ?? $r['nombre'] ?? 'Sin nombre') ?></td><td><b><?= e($r['placa'] ?? '-') ?></b></td>
<td class="<?= $venc ? 'text-danger fw-bold' : '' ?>"><?= $r['vence'] ? fdate($r['vence']) : '-' ?><?= $venc ? ' <small>(vencida)</small>' : '' ?></td>
<td><span class="badge badge-soft-<?= CotizacionesController::COLOR[$r['estado']] ?>"><?= CotizacionesController::ESTADOS[$r['estado']] ?></span></td><td class="text-end fw-bold"><?= money($r['total']) ?></td>
<td class="text-end"><a class="btn btn-icon btn-light" href="<?= url('cotizaciones/ver/' . $r['id']) ?>"><i class="bi bi-eye"></i></a></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="8" class="text-center text-muted py-5"><i class="bi bi-file-earmark-text fs-1 d-block"></i>No hay cotizaciones.</td></tr><?php endif; ?></tbody></table></div></div>
