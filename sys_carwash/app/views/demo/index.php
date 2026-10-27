<div class="mb-4"><h1 class="page-title">Datos de ejemplo</h1><p class="page-sub">Llena el sistema con registros de muestra para ver el dashboard, los gráficos y los reportes en acción</p></div>
<div class="row g-3">
 <div class="col-lg-7"><div class="cw-card"><div class="card-h"><h6><i class="bi bi-magic text-primary me-2"></i>Cargar registros de ejemplo</h6></div><div class="card-b">
  <p class="text-muted">Genera automáticamente, usando tus servicios, empleados y productos actuales:</p>
  <div class="row g-2 mb-3">
   <?php foreach ([['bi-receipt', 'Órdenes de servicio', 'con detalle, cobros y estados (la de hoy en distintos estados)'], ['bi-people', 'Clientes y vehículos', 'con fechas de nacimiento y puntos de fidelidad'], ['bi-wallet2', 'Gastos mensuales', 'alquiler, servicios, sueldos, marketing…'], ['bi-bag-plus', 'Compras de insumos', 'que también suben el stock'], ['bi-calendar-check', 'Citas', 'de hoy, mañana y días pasados'], ['bi-cash-coin', 'Cierres de caja', 'de los últimos 10 días'], ['bi-heart-pulse', 'CRM', 'seguimientos, oportunidades, cupón y encuestas']] as [$i, $t, $s]): ?>
   <div class="col-md-6"><div class="d-flex gap-2"><i class="bi <?= $i ?> text-primary fs-5"></i><div><b class="small"><?= $t ?></b><div class="small text-muted"><?= $s ?></div></div></div></div><?php endforeach; ?></div>
  <?php if (!$st['catalogo']): ?><div class="alert alert-warning">Primero registra al menos un servicio, un empleado y un tipo de vehículo.</div>
  <?php elseif ($st['ordenes']): ?><div class="alert alert-info mb-0">Ya hay datos de ejemplo cargados. Quítalos para volver a generarlos.</div>
  <?php else: ?>
  <form method="post" action="<?= url('demo/cargar') ?>" class="d-flex flex-wrap gap-2 align-items-end"><?= csrf_field() ?>
   <div><label class="form-label small mb-0">Periodo a generar</label><select name="dias" class="form-select"><option value="60">Últimos 2 meses</option><option value="120" selected>Últimos 4 meses</option><option value="180">Últimos 6 meses</option><option value="365">Último año</option></select></div>
   <button class="btn btn-grad" data-confirm="¿Cargar los datos de ejemplo? Se agregarán al sistema (podrás quitarlos después)."><i class="bi bi-database-add me-1"></i> Cargar datos de ejemplo</button></form>
  <?php endif; ?>
  <?php if ($st['reales'] > 0 && !$st['ordenes']): ?><div class="form-text mt-2">Ya tienes <?= $st['reales'] ?> órdenes reales: los datos de ejemplo se sumarán a ellas.</div><?php endif; ?>
 </div></div></div>
 <div class="col-lg-5"><div class="cw-card"><div class="card-h"><h6><i class="bi bi-trash3 text-danger me-2"></i>Datos de ejemplo actuales</h6></div><div class="card-b">
  <?php foreach (['ordenes' => 'Órdenes', 'clientes' => 'Clientes', 'gastos' => 'Gastos', 'compras' => 'Compras', 'citas' => 'Citas'] as $k => $l): ?><div class="d-flex justify-content-between py-1 border-bottom"><span><?= $l ?></span><b><?= $st[$k] ?></b></div><?php endforeach; ?>
  <form method="post" action="<?= url('demo/quitar') ?>" class="mt-3"><?= csrf_field() ?><button class="btn btn-outline-danger w-100" <?= $st['ordenes'] + $st['clientes'] ? '' : 'disabled' ?> data-confirm="¿Quitar todos los datos de ejemplo? Tus datos reales no se tocan."><i class="bi bi-trash3 me-1"></i> Quitar datos de ejemplo</button></form>
  <div class="form-text mt-2">Solo se eliminan los registros marcados como [DEMO].</div></div></div></div>
</div>
