<?php
$dif = $k['ventas_ayer'] > 0 ? (($k['ventas_hoy'] - $k['ventas_ayer']) / $k['ventas_ayer']) * 100 : null;
$sim = e(empresa()['moneda_simbolo']);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div><h1 class="page-title">¡Hola, <?= e(explode(' ', auth_user()['nombre'])[0]) ?>! 👋</h1>
  <p class="page-sub">Resumen de tu negocio al <?= fdate(date('Y-m-d')) ?></p></div>
  <a href="<?= url('ordenes/nueva') ?>" class="btn btn-grad"><i class="bi bi-plus-lg me-1"></i> Nueva orden de servicio</a>
</div>

<?php
$difMes = $k['ventas_mes_ant'] > 0 ? (($k['ventas_mes'] - $k['ventas_mes_ant']) / $k['ventas_mes_ant']) * 100 : null;
$ticket = $k['cobradas_hoy'] > 0 ? $k['ventas_hoy'] / $k['cobradas_hoy'] : 0;
$utilidad = $k['ventas_mes'] - $k['gastos_mes'];
$badge = fn($d) => $d === null ? ['bi-dash', 'Sin base'] : [$d >= 0 ? 'bi-arrow-up' : 'bi-arrow-down', ($d >= 0 ? '+' : '') . num($d, 1) . '%'];
[$bi1, $bt1] = $badge($dif); [$bi3, $bt3] = $badge($difMes);
$kpis = [
  ['kpi-teal', 'Ventas de hoy', money($k['ventas_hoy']), 'bi-currency-dollar', [$bi1, $bt1], 'vs. ayer'],
  ['kpi-amber', 'Órdenes de hoy', $k['ordenes_hoy'], 'bi-receipt', ['bi-hourglass-split', $k['en_proceso'] . ' en cola'], 'Ticket promedio ' . money($ticket)],
  ['kpi-sky', 'Ventas del mes', money($k['ventas_mes']), 'bi-calendar2-check', [$bi3, $bt3], 'vs. mes anterior'],
  ['kpi-rose', 'Gastos del mes', money($k['gastos_mes']), 'bi-wallet2', [$utilidad >= 0 ? 'bi-graph-up-arrow' : 'bi-graph-down-arrow', money($utilidad)], 'Utilidad aprox.'],
];
?>
<div class="row g-3 mb-3">
  <?php foreach ($kpis as [$cls, $lbl, $val, $ico, [$bic, $btx], $sub]): ?>
  <div class="col-sm-6 col-xl-3"><div class="kpi <?= $cls ?>">
    <div class="kpi-ico"><i class="bi <?= $ico ?>"></i></div>
    <div class="kpi-l"><?= $lbl ?></div>
    <div class="kpi-v"><?= $val ?></div>
    <div class="kpi-f"><span class="kpi-b"><i class="bi <?= $bic ?>"></i> <?= e($btx) ?></span><span class="kpi-s"><?= e($sub) ?></span></div>
  </div></div>
  <?php endforeach; ?>
</div>
<div class="row g-3 mb-3">
  <?php foreach ([['Por cobrar', money($k['por_cobrar']), 'bi-hourglass-split', 'amber', 'Órdenes pendientes de pago', 'ordenes'], ['Clientes activos', num($k['clientes'], 0), 'bi-people', 'blue', 'Base de clientes', 'crm'], ['En cola o en proceso', $k['en_proceso'], 'bi-droplet-half', 'teal', 'Vehículos en el local', 'ordenes']] as [$l, $v, $i, $c, $s, $lnk]): ?>
  <div class="col-md-4"><a href="<?= url($lnk) ?>" class="mini-stat text-decoration-none"><div class="ico bg-<?= $c ?> t-<?= $c ?>"><i class="bi <?= $i ?>"></i></div><div class="flex-grow-1"><div class="l" title="<?= e($s) ?>"><?= $l ?></div><div class="fw-bold fs-5 text-dark lh-1 text-nowrap"><?= $v ?></div></div><i class="bi bi-chevron-right text-muted"></i></a></div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mb-3">
  <div class="col-xl-8"><div class="cw-card h-100">
    <div class="card-h"><h6>Ventas de los últimos <?= $dias_p ?> días</h6>
      <div class="btn-group btn-group-sm"><?php foreach ([7, 15, 30, 90] as $d): ?><a href="?dias=<?= $d ?>" class="btn btn-<?= $dias_p === $d ? 'primary' : 'light' ?>"><?= $d ?>d</a><?php endforeach; ?></div></div>
    <div class="card-b"><div class="chart-box"><canvas id="ch7"></canvas></div></div></div></div>
  <div class="col-xl-4"><div class="cw-card h-100">
    <div class="card-h"><h6>Servicios más vendidos (<?= $dias_p ?> días)</h6></div>
    <div class="card-b"><div class="chart-box"><canvas id="chServ"></canvas></div></div></div></div>
</div>

<div class="row g-3 mb-3">
  <div class="col-xl-8"><div class="cw-card h-100">
    <div class="card-h"><h6>Ingresos vs. gastos (12 meses)</h6></div>
    <div class="card-b"><div class="chart-box"><canvas id="chMes"></canvas></div></div></div></div>
  <div class="col-xl-4"><div class="cw-card h-100">
    <div class="card-h"><h6>Métodos de pago (30 días)</h6></div>
    <div class="card-b"><div class="chart-box"><canvas id="chPago"></canvas></div></div></div></div>
</div>

<div class="row g-3 mb-3">
  <div class="col-xl-7"><div class="cw-card h-100">
    <div class="card-h"><h6>Últimas órdenes</h6><a href="<?= url('ordenes') ?>" class="small fw-bold text-decoration-none">Ver todas</a></div>
    <div class="table-responsive"><table class="table table-hover mb-0">
      <thead><tr><th>Código</th><th>Cliente</th><th>Placa</th><th>Estado</th><th class="text-end">Total</th></tr></thead><tbody>
      <?php foreach ($ultimas as $o): ?>
        <tr><td><a class="fw-bold text-decoration-none" href="<?= url('ordenes/ver/' . $o['id']) ?>"><?= e($o['codigo']) ?></a></td>
        <td><?= e($o['cliente'] ?? 'Cliente general') ?></td><td><?= e($o['placa'] ?? '-') ?></td>
        <td><?= estado_badge($o['estado']) ?></td><td class="text-end fw-bold"><?= money($o['total']) ?></td></tr>
      <?php endforeach; if (!$ultimas): ?><tr><td colspan="5" class="text-center text-muted py-4">Aún no hay órdenes registradas</td></tr><?php endif; ?>
      </tbody></table></div></div></div>
  <div class="col-xl-5"><div class="cw-card h-100 mb-3">
    <div class="card-h"><h6>Rendimiento de empleados (30 días)</h6></div>
    <div class="card-b"><div style="height:220px;position:relative"><canvas id="chEmp"></canvas></div></div></div></div>
</div>

<div class="row g-3">
  <div class="col-md-6"><div class="cw-card h-100">
    <div class="card-h"><h6><i class="bi bi-calendar-event text-primary me-1"></i> Citas de hoy</h6><a href="<?= url('citas') ?>" class="small fw-bold text-decoration-none">Ver agenda</a></div>
    <div class="card-b">
      <?php foreach ($citasHoy as $c): ?>
        <div class="d-flex justify-content-between border-bottom py-2"><div><b><?= e($c['cliente']) ?></b><div class="small text-muted"><?= e($c['servicio'] ?? 'Servicio por definir') ?></div></div>
        <div class="text-end"><span class="fw-bold"><?= date('H:i', strtotime($c['fecha_hora'])) ?></span><div><?= estado_badge($c['estado']) ?></div></div></div>
      <?php endforeach; if (!$citasHoy): ?><div class="text-muted text-center py-3">No hay citas pendientes para hoy</div><?php endif; ?>
    </div></div></div>
  <div class="col-md-6"><div class="cw-card h-100">
    <div class="card-h"><h6><i class="bi bi-exclamation-triangle text-warning me-1"></i> Stock bajo</h6><a href="<?= url('productos') ?>" class="small fw-bold text-decoration-none">Inventario</a></div>
    <div class="card-b">
      <?php foreach ($stockBajo as $p): ?>
        <div class="d-flex justify-content-between border-bottom py-2"><b><?= e($p['nombre']) ?></b>
        <span class="badge badge-soft-danger"><?= num($p['stock'], 0) ?> / mín <?= num($p['stock_minimo'], 0) ?> <?= e($p['unidad']) ?></span></div>
      <?php endforeach; if (!$stockBajo): ?><div class="text-muted text-center py-3">Todo el inventario está en niveles normales ✔</div><?php endif; ?>
    </div></div></div>
</div>

<?php ob_start(); ?>
<script>
const C = <?= json_encode($charts, JSON_UNESCAPED_UNICODE) ?>;
const SIM = <?= json_encode(empresa()['moneda_simbolo']) ?>;
const pal = ['#0ea5e9','#14b8a6','#8b5cf6','#f59e0b','#f43f5e','#22c55e'];
Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
Chart.defaults.color = '#64748b';
const grid = {color:'#eef3f8'};
const mk = (id, cfg) => new Chart(document.getElementById(id), cfg);
const g = document.getElementById('ch7').getContext('2d').createLinearGradient(0,0,0,300);
g.addColorStop(0,'rgba(14,165,233,.35)'); g.addColorStop(1,'rgba(14,165,233,0)');
mk('ch7',{type:'line',data:{labels:C.dias,datasets:[{label:'Ventas ('+SIM+')',data:C.ventas7,borderColor:'#0ea5e9',backgroundColor:g,fill:true,tension:.4,borderWidth:3,pointRadius:4,pointBackgroundColor:'#fff',pointBorderWidth:2},
 {label:'Órdenes',data:C.ordenes7,borderColor:'#14b8a6',tension:.4,borderWidth:2,borderDash:[5,5],pointRadius:3,yAxisID:'y1'}]},
 options:{maintainAspectRatio:false,interaction:{mode:'index',intersect:false},plugins:{legend:{position:'bottom',labels:{usePointStyle:true}}},scales:{y:{grid,beginAtZero:true},y1:{position:'right',grid:{display:false},beginAtZero:true,ticks:{precision:0}},x:{grid:{display:false}}}}});
const empty = (a)=>!a.length;
mk('chServ',{type:'doughnut',data:{labels:empty(C.topServ)?['Sin datos']:C.topServ.map(x=>x.n),datasets:[{data:empty(C.topServ)?[1]:C.topServ.map(x=>+x.t),backgroundColor:empty(C.topServ)?['#e2e8f0']:pal,borderWidth:3,borderColor:'#fff'}]},
 options:{maintainAspectRatio:false,cutout:'66%',plugins:{legend:{position:'bottom',labels:{usePointStyle:true}}}}});
mk('chMes',{type:'bar',data:{labels:C.meses,datasets:[{label:'Ingresos',data:C.ventasM,backgroundColor:'#0ea5e9',borderRadius:8,maxBarThickness:26},{label:'Gastos',data:C.gastosM,backgroundColor:'#fda4af',borderRadius:8,maxBarThickness:26}]},
 options:{maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{usePointStyle:true}}},scales:{y:{grid,beginAtZero:true},x:{grid:{display:false}}}}});
const lbl={efectivo:'Efectivo',tarjeta:'Tarjeta',yape_plin:'Yape / Plin',transferencia:'Transferencia'};
mk('chPago',{type:'pie',data:{labels:empty(C.pagos)?['Sin datos']:C.pagos.map(x=>lbl[x.m]||x.m),datasets:[{data:empty(C.pagos)?[1]:C.pagos.map(x=>+x.t),backgroundColor:empty(C.pagos)?['#e2e8f0']:pal,borderWidth:3,borderColor:'#fff'}]},
 options:{maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{usePointStyle:true}}}}});
mk('chEmp',{type:'bar',data:{labels:empty(C.emps)?['Sin datos']:C.emps.map(x=>x.n),datasets:[{label:'Ventas',data:empty(C.emps)?[0]:C.emps.map(x=>+x.t),backgroundColor:'#14b8a6',borderRadius:8,maxBarThickness:22}]},
 options:{indexAxis:'y',maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid,beginAtZero:true},y:{grid:{display:false}}}}});
</script>
<?php $scripts = ob_get_clean(); ?>
