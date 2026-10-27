<?php $crmTab = ''; include __DIR__ . '/_nav.php'; $fmtDias = fn($n) => $n === 0 ? 'hoy' : ($n === 1 ? 'mañana' : "en $n días"); ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <div><h1 class="page-title">CRM · Relación con clientes</h1><p class="page-sub">Conoce, fideliza y recupera a tus clientes</p></div>
  <a href="<?= url('crm/campanas') ?>" class="btn btn-grad"><i class="bi bi-megaphone me-1"></i> Nueva campaña</a>
</div>
<div class="row g-3 mb-3">
<?php foreach ([
  ['Clientes activos', num($k['total'], 0), 'bi-people', 'blue', $k['activos'] . ' con actividad reciente'],
  ['Clientes VIP', num($k['vip'], 0), 'bi-gem', 'violet', 'Alto valor'],
  ['A recuperar', num($k['riesgo'], 0), 'bi-exclamation-triangle', 'amber', 'En riesgo o inactivos'],
  ['Recompra', $k['recompra'] . '%', 'bi-arrow-repeat', 'green', 'Con 2 o más visitas'],
  ['Nuevos este mes', num($k['nuevos_mes'], 0), 'bi-person-plus', 'teal', 'Registrados'],
  ['Puntos en circulación', num($k['puntos'], 0), 'bi-award', 'blue', 'Saldo de fidelidad'],
  ['Seguimientos pendientes', num($k['seg_pend'], 0), 'bi-telephone-outbound', 'rose', 'Vencidos o de hoy'],
  ['Satisfacción', $k['csat'] ? $k['csat'] . ' / 5' : '—', 'bi-emoji-smile', 'green', $k['resp'] . ' respuestas'],
] as [$l, $v, $i, $col, $sub]): ?>
  <div class="col-6 col-xl-3"><div class="cw-card stat t-<?= $col ?>"><div class="d-flex align-items-center gap-3"><div class="ico bg-<?= $col ?>"><i class="bi <?= $i ?>"></i></div><div><div class="l"><?= $l ?></div><div class="fs-4 fw-bold text-dark lh-1"><?= $v ?></div><div class="small text-muted mt-1"><?= $sub ?></div></div></div></div></div>
<?php endforeach; ?></div>

<div class="row g-3 mb-3">
  <div class="col-lg-4"><div class="cw-card h-100"><div class="card-h"><h6>Segmentos de clientes</h6><a class="small fw-bold text-decoration-none" href="<?= url('crm/segmentos') ?>">Ver todo</a></div><div class="card-b"><canvas id="chSeg" height="230"></canvas></div></div></div>
  <div class="col-lg-4"><div class="cw-card h-100"><div class="card-h"><h6>Clientes nuevos por mes</h6></div><div class="card-b"><canvas id="chNue" height="230"></canvas></div></div></div>
  <div class="col-lg-4"><div class="cw-card h-100"><div class="card-h"><h6>Mejores clientes</h6></div><div class="card-b">
    <?php foreach ($top as $i => $c): ?><div class="d-flex align-items-center gap-3 py-2 border-bottom"><span class="rank"><?= $i + 1 ?></span><div class="flex-grow-1"><a class="fw-bold text-decoration-none text-dark" href="<?= url('clientes/ficha/' . $c['id']) ?>"><?= e($c['nombre']) ?></a><div class="small text-muted"><?= $c['visitas'] ?> visitas</div></div><b><?= money($c['gastado']) ?></b></div><?php endforeach; ?>
    <?php if (!$top): ?><div class="text-muted text-center py-4">Aún no hay ventas registradas</div><?php endif; ?></div></div></div>
</div>

<div class="row g-3">
  <div class="col-lg-4"><div class="cw-card h-100"><div class="card-h"><h6><i class="bi bi-cake2 text-danger me-2"></i>Cumpleaños próximos (30 días)</h6></div><div class="card-b">
    <?php foreach (array_slice($cumples, 0, 6) as $c): $w = crm_wa($c['telefono'], crm_msg($cfg['msg_cumple'], $c)); ?>
      <div class="d-flex align-items-center gap-2 py-2 border-bottom"><div class="flex-grow-1"><a class="fw-semibold text-decoration-none text-dark" href="<?= url('clientes/ficha/' . $c['id']) ?>"><?= e($c['nombre']) ?></a><div class="small text-muted"><?= date('d/m', strtotime($c['fecha_nacimiento'])) ?> · <?= $fmtDias($c['cumple_en']) ?></div></div>
      <?php if ($w): ?><a class="btn btn-sm btn-success" target="_blank" href="<?= e($w) ?>"><i class="bi bi-whatsapp"></i></a><?php endif; ?></div>
    <?php endforeach; if (!$cumples): ?><div class="text-muted text-center py-4">Sin cumpleaños próximos.<br><small>Registra la fecha de nacimiento en la ficha del cliente.</small></div><?php endif; ?></div></div></div>
  <div class="col-lg-4"><div class="cw-card h-100"><div class="card-h"><h6><i class="bi bi-exclamation-triangle text-warning me-2"></i>Clientes en riesgo</h6><a class="small fw-bold text-decoration-none" href="<?= url('crm/segmentos?seg=en_riesgo') ?>">Ver todos</a></div><div class="card-b">
    <?php foreach (array_slice($riesgo, 0, 6) as $c): $w = crm_wa($c['telefono'], crm_msg($cfg['msg_riesgo'], $c)); ?>
      <div class="d-flex align-items-center gap-2 py-2 border-bottom"><div class="flex-grow-1"><a class="fw-semibold text-decoration-none text-dark" href="<?= url('clientes/ficha/' . $c['id']) ?>"><?= e($c['nombre']) ?></a><div class="small text-muted">Hace <?= (int)$c['dias'] ?> días · <?= money($c['gastado']) ?></div></div>
      <?php if ($w): ?><a class="btn btn-sm btn-success" target="_blank" href="<?= e($w) ?>"><i class="bi bi-whatsapp"></i></a><?php endif; ?></div>
    <?php endforeach; if (!$riesgo): ?><div class="text-muted text-center py-4">¡Ningún cliente en riesgo! 🎉</div><?php endif; ?></div></div></div>
  <div class="col-lg-4"><div class="cw-card h-100"><div class="card-h"><h6><i class="bi bi-telephone-outbound text-primary me-2"></i>Seguimientos pendientes</h6><a class="small fw-bold text-decoration-none" href="<?= url('crm/seguimientos') ?>">Gestionar</a></div><div class="card-b">
    <?php foreach ($pend as $p): $venc = $p['seguimiento_fecha'] < date('Y-m-d'); ?>
      <div class="py-2 border-bottom"><div class="d-flex justify-content-between"><b><?= e($p['cliente']) ?></b><span class="badge badge-soft-<?= $venc ? 'danger' : 'warning' ?>"><?= fdate($p['seguimiento_fecha']) ?></span></div><div class="small text-muted"><?= e($p['asunto']) ?></div></div>
    <?php endforeach; if (!$pend): ?><div class="text-muted text-center py-4">Sin seguimientos pendientes</div><?php endif; ?></div></div></div>
</div>
<?php if ($k['op_abiertas']): ?><div class="cw-card mt-3 p-3 d-flex align-items-center gap-3"><div class="ico bg-blue stat-ico"><i class="bi bi-kanban"></i></div><div class="flex-grow-1"><b><?= $k['op_abiertas'] ?> oportunidades abiertas</b> por un valor estimado de <b><?= money($k['op_valor']) ?></b></div><a href="<?= url('crm/pipeline') ?>" class="btn btn-outline-primary btn-sm">Ver pipeline</a></div><?php endif; ?>
<?php ob_start(); ?>
<script>
const CH = <?= json_encode($charts, JSON_UNESCAPED_UNICODE) ?>;
Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif"; Chart.defaults.color = '#64748b';
new Chart(document.getElementById('chSeg'), {type:'doughnut', data:{labels:CH.seg.labels, datasets:[{data:CH.seg.data, backgroundColor:CH.seg.colors, borderWidth:2, borderColor:'#fff'}]}, options:{cutout:'62%', plugins:{legend:{position:'bottom', labels:{usePointStyle:true, boxWidth:8}}}}});
new Chart(document.getElementById('chNue'), {type:'bar', data:{labels:CH.nuevos.labels, datasets:[{label:'Nuevos clientes', data:CH.nuevos.data, backgroundColor:'#38bdf8', borderRadius:8}]}, options:{plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true, ticks:{precision:0}, grid:{color:'#eef3f8'}}, x:{grid:{display:false}}}}});
</script>
<?php $scripts = ob_get_clean(); ?>
