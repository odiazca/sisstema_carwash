<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div><h1 class="page-title">Órdenes de servicio</h1><p class="page-sub">Seguimiento de vehículos en lavado y cobros</p></div>
  <a href="<?= url('ordenes/nueva') ?>" class="btn btn-grad"><i class="bi bi-plus-lg me-1"></i> Nueva orden</a>
</div>
<div class="row g-2 mb-3">
<?php foreach (OrdenesController::ESTADOS as $k => $lbl): ?>
  <div class="col-6 col-md"><a href="?estado=<?= $k ?>&desde=<?= date('Y-m-d') ?>&hasta=<?= date('Y-m-d') ?>" class="text-decoration-none"><div class="cw-card p-3 text-center">
    <div class="fs-3 fw-bold text-dark"><?= (int)($resumen[$k] ?? 0) ?></div><?= estado_badge($k) ?><div class="small text-muted mt-1">hoy</div></div></a></div>
<?php endforeach; ?>
</div>
<div class="cw-card">
  <form class="card-h flex-wrap gap-2" method="get">
    <div class="d-flex gap-2 flex-wrap align-items-center">
      <select name="estado" class="form-select form-select-sm" style="width:150px"><option value="">Todos</option>
        <?php foreach (OrdenesController::ESTADOS as $k => $l): ?><option value="<?= $k ?>" <?= $estado === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
      <input type="date" name="desde" value="<?= e($desde) ?>" class="form-control form-control-sm" style="width:150px">
      <input type="date" name="hasta" value="<?= e($hasta) ?>" class="form-control form-control-sm" style="width:150px">
      <button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
    </div>
    <div class="input-ico" style="width:240px"><i class="bi bi-search"></i><input class="form-control form-control-sm" placeholder="Buscar placa, cliente…" data-table-filter="#tbl"></div>
  </form>
  <div class="table-responsive"><table class="table table-hover mb-0" id="tbl">
    <thead><tr><th>Código</th><th>Fecha</th><th>Cliente</th><th>Placa</th><th>Lavador</th><th>Estado</th><th>Pago</th><th class="text-end">Total</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $o): ?>
      <tr><td><a href="<?= url('ordenes/ver/' . $o['id']) ?>" class="fw-bold text-decoration-none"><?= e($o['codigo']) ?></a><?= $o['tipo'] === 'venta' ? ' <span class="badge badge-soft-info">Venta</span>' : '' ?></td>
        <td><?= fdate($o['fecha_ingreso'], true) ?></td><td><?= e($o['cliente'] ?? 'Cliente general') ?></td><td><b><?= e($o['placa'] ?? '-') ?></b></td><td><?= e($o['empleado'] ?? '-') ?></td>
        <td><?= estado_badge($o['estado']) ?></td>
        <td><?= $o['pagado'] ? '<span class="badge badge-soft-success">Pagado</span>' : ($o['abonado'] > 0 ? '<span class="badge badge-soft-info" title="Saldo ' . e(money($o['total'] - $o['abonado'])) . '">Parcial</span>' : '<span class="badge badge-soft-warning">Pendiente</span>') ?></td>
        <td class="text-end fw-bold"><?= money($o['total']) ?></td>
        <td class="text-end"><a class="btn btn-icon btn-light" href="<?= url('ordenes/ver/' . $o['id']) ?>"><i class="bi bi-eye"></i></a></td></tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="9" class="text-center text-muted py-5"><i class="bi bi-card-checklist fs-1 d-block"></i>No hay órdenes en este rango</td></tr><?php endif; ?>
    </tbody></table></div>
</div>
