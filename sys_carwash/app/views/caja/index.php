<div class="mb-4"><h1 class="page-title">Caja</h1><p class="page-sub">Apertura, control de ingresos y cierre de turno</p></div>
<?php if (!$abierta): ?>
<div class="row justify-content-center"><div class="col-md-6 col-lg-5"><div class="cw-card text-center p-4">
  <div class="sb-logo mx-auto mb-3" style="width:70px;height:70px;font-size:2rem"><i class="bi bi-unlock"></i></div>
  <h4 class="fw-bold">Caja cerrada</h4><p class="text-muted">Ingresa el efectivo inicial para abrir el turno.</p>
  <form method="post" action="<?= url('caja/abrir') ?>"><?= csrf_field() ?>
    <input type="number" step="0.01" min="0" name="monto_inicial" class="form-control form-control-lg text-center mb-3" placeholder="Monto inicial" value="0" required>
    <button class="btn btn-grad w-100 py-2">Abrir caja</button></form></div></div></div>
<?php else: ?>
<div class="row g-3 mb-3">
  <?php foreach ([['Monto inicial', $abierta['monto_inicial'], 'bi-box-arrow-in-down', 'blue'], ['Ventas en efectivo', $res['efectivo'], 'bi-cash', 'green'], ['Gastos del turno', $res['gastos'], 'bi-arrow-up-right-circle', 'rose'], ['Efectivo esperado', $res['esperado'], 'bi-safe', 'violet']] as [$l, $v, $i, $c]): ?>
  <div class="col-sm-6 col-xl-3"><div class="cw-card stat t-<?= $c ?>"><div class="d-flex align-items-center gap-3"><div class="ico bg-<?= $c ?>"><i class="bi <?= $i ?>"></i></div><div><div class="l"><?= $l ?></div><div class="fs-5 fw-bold text-dark"><?= money($v) ?></div></div></div></div></div>
  <?php endforeach; ?>
</div>
<div class="row g-3">
  <div class="col-lg-7"><div class="cw-card"><div class="card-h"><h6>Ventas del turno por método de pago</h6><span class="badge badge-soft-success">Abierta desde <?= fdate($abierta['fecha_apertura'], true) ?></span></div>
    <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Método</th><th class="text-end">Operaciones</th><th class="text-end">Total</th></tr></thead><tbody>
    <?php foreach ($res['porMetodo'] as $m => $p): ?><tr><td><?= e(OrdenesController::PAGOS[$m] ?? 'Otro') ?></td><td class="text-end"><?= $p['n'] ?></td><td class="text-end fw-bold"><?= money($p['t']) ?></td></tr><?php endforeach; ?>
    <?php if (!$res['porMetodo']): ?><tr><td colspan="3" class="text-center text-muted py-4">Sin ventas cobradas aún</td></tr><?php endif; ?>
    </tbody><tfoot><tr><th colspan="2">Total vendido</th><th class="text-end"><?= money($res['total']) ?></th></tr></tfoot></table></div></div></div>
  <div class="col-lg-5"><div class="cw-card"><div class="card-h"><h6>Cerrar caja</h6></div><div class="card-b">
    <form method="post" action="<?= url('caja/cerrar') ?>"><?= csrf_field() ?>
      <label class="form-label">Efectivo contado en caja</label><input type="number" step="0.01" min="0" name="monto_final" class="form-control form-control-lg mb-3" required>
      <label class="form-label">Observaciones</label><input name="observaciones" class="form-control mb-3">
      <button class="btn btn-danger w-100" data-confirm="¿Cerrar la caja del turno?"><i class="bi bi-lock me-1"></i> Cerrar caja</button></form></div></div></div>
</div>
<?php endif; ?>
<div class="cw-card mt-3"><div class="card-h"><h6>Historial de cierres</h6></div>
 <div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Apertura</th><th>Cierre</th><th>Cajero</th><th class="text-end">Inicial</th><th class="text-end">Esperado</th><th class="text-end">Contado</th><th class="text-end">Diferencia</th></tr></thead><tbody>
 <?php foreach ($hist as $h): $dif = $h['monto_final'] - $h['monto_esperado']; ?>
  <tr><td><?= fdate($h['fecha_apertura'], true) ?></td><td><?= fdate($h['fecha_cierre'], true) ?></td><td><?= e($h['usuario']) ?></td><td class="text-end"><?= money($h['monto_inicial']) ?></td><td class="text-end"><?= money($h['monto_esperado']) ?></td><td class="text-end"><?= money($h['monto_final']) ?></td>
  <td class="text-end"><span class="badge badge-soft-<?= abs($dif) < 0.005 ? 'success' : ($dif < 0 ? 'danger' : 'warning') ?>"><?= money($dif) ?></span></td></tr>
 <?php endforeach; if (!$hist): ?><tr><td colspan="7" class="text-center text-muted py-4">Sin cierres registrados</td></tr><?php endif; ?></tbody></table></div></div>
