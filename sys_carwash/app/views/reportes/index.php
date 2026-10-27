<div class="d-flex flex-wrap justify-content-between align-items-end mb-4 gap-2">
  <div><h1 class="page-title">Reportes</h1><p class="page-sub">Ventas pagadas del <?= fdate($d) ?> al <?= fdate($h) ?></p></div>
  <form class="d-flex gap-2 align-items-end no-print" method="get">
    <div><label class="form-label mb-0 small">Desde</label><input type="date" name="desde" value="<?= e($d) ?>" class="form-control form-control-sm"></div>
    <div><label class="form-label mb-0 small">Hasta</label><input type="date" name="hasta" value="<?= e($h) ?>" class="form-control form-control-sm"></div>
    <button class="btn btn-sm btn-primary">Aplicar</button>
    <div class="dropdown d-inline-block"><button class="btn btn-sm btn-outline-success dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-file-earmark-excel"></i> Excel</button><ul class="dropdown-menu dropdown-menu-end">
      <?php foreach (ReportesController::EXCEL as $k => $l): ?><li><a class="dropdown-item" href="<?= url('reportes/excel?tipo=' . $k . '&desde=' . $d . '&hasta=' . $h) ?>"><?= e($l) ?></a></li><?php endforeach; ?>
      <li><hr class="dropdown-divider"></li><li><a class="dropdown-item" href="<?= url('reportes/exportar?desde=' . $d . '&hasta=' . $h) ?>">Órdenes (CSV)</a></li></ul></div>
    <a class="btn btn-sm btn-outline-danger" target="_blank" href="<?= url('reportes/imprimir?desde=' . $d . '&hasta=' . $h) ?>"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
  </form>
</div>
<div class="row g-3 mb-3">
<?php foreach ([['Ingresos', money($r['ingresos']), 'bi-cash-stack', 'blue'], ['Órdenes cobradas', $r['ordenes'], 'bi-receipt', 'teal'], ['Ticket promedio', money($r['promedio']), 'bi-tag', 'violet'], ['Gastos', money($r['gastos']), 'bi-wallet2', 'rose'], ['Utilidad', money($r['utilidad']), 'bi-piggy-bank', $r['utilidad'] >= 0 ? 'green' : 'rose']] as [$l, $v, $i, $c]): ?>
  <div class="col-sm-6 col-xl"><div class="cw-card stat t-<?= $c ?>"><div class="ico bg-<?= $c ?>"><i class="bi <?= $i ?>"></i></div><div class="v text-dark fs-4"><?= $v ?></div><div class="l"><?= $l ?></div></div></div>
<?php endforeach; ?>
</div>
<div class="row g-3 mb-3">
  <div class="col-xl-8"><div class="cw-card h-100"><div class="card-h"><h6>Ventas por día</h6></div><div class="card-b"><div class="chart-box"><canvas id="cDia"></canvas></div></div></div></div>
  <div class="col-xl-4"><div class="cw-card h-100"><div class="card-h"><h6>Gastos por categoría</h6></div><div class="card-b"><div class="chart-box"><canvas id="cGas"></canvas></div></div></div></div>
</div>
<div class="row g-3">
  <div class="col-xl-6"><div class="cw-card h-100"><div class="card-h"><h6>Servicios y productos más vendidos</h6></div>
    <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Ítem</th><th class="text-end">Cant.</th><th class="text-end">Ventas</th></tr></thead><tbody>
    <?php foreach ($servicios as $s): ?><tr><td><?= e($s['n']) ?></td><td class="text-end"><?= num($s['c'], 0) ?></td><td class="text-end fw-bold"><?= money($s['t']) ?></td></tr><?php endforeach; if (!$servicios): ?><tr><td colspan="3" class="text-center text-muted py-4">Sin datos</td></tr><?php endif; ?></tbody></table></div></div></div>
  <div class="col-xl-6"><div class="cw-card h-100"><div class="card-h"><h6>Producción y comisiones por empleado</h6></div>
    <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Empleado</th><th class="text-end">Órdenes</th><th class="text-end">Ventas</th><th class="text-end">Comisión</th></tr></thead><tbody>
    <?php foreach ($empleados as $e): ?><tr><td><b><?= e($e['nombre']) ?></b></td><td class="text-end"><?= $e['ordenes'] ?></td><td class="text-end"><?= money($e['ventas']) ?></td><td class="text-end fw-bold text-success"><?= money($e['comision']) ?></td></tr><?php endforeach; if (!$empleados): ?><tr><td colspan="4" class="text-center text-muted py-4">Sin datos</td></tr><?php endif; ?></tbody></table></div></div></div>
</div>
<?php ob_start(); ?>
<script>
const D = <?= json_encode($porDia) ?>, G = <?= json_encode($gastosCat) ?>;
Chart.defaults.font.family="'Plus Jakarta Sans',sans-serif";
new Chart(document.getElementById('cDia'),{type:'bar',data:{labels:D.map(x=>x.d),datasets:[{label:'Ventas',data:D.map(x=>+x.t),backgroundColor:'#0ea5e9',borderRadius:8,maxBarThickness:34}]},options:{maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{display:false}},y:{beginAtZero:true,grid:{color:'#eef3f8'}}}}});
new Chart(document.getElementById('cGas'),{type:'doughnut',data:{labels:G.length?G.map(x=>x.n):['Sin datos'],datasets:[{data:G.length?G.map(x=>+x.t):[1],backgroundColor:G.length?['#f43f5e','#f59e0b','#8b5cf6','#14b8a6','#0ea5e9','#22c55e','#94a3b8']:['#e2e8f0'],borderWidth:3,borderColor:'#fff'}]},options:{maintainAspectRatio:false,cutout:'62%',plugins:{legend:{position:'bottom',labels:{usePointStyle:true}}}}});
</script>
<?php $scripts = ob_get_clean(); ?>
