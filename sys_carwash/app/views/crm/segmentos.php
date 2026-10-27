<?php $crmTab = 'segmentos'; include __DIR__ . '/_nav.php'; ?>
<div class="d-flex flex-wrap justify-content-between align-items-end mb-3 gap-2"><div><h1 class="page-title">Segmentos de clientes</h1><p class="page-sub">Clasificación automática por frecuencia, gasto y tiempo sin visitas</p></div>
<a class="btn btn-outline-primary" href="<?= url('crm/segmentos?' . http_build_query(['seg' => $seg, 'q' => $q, 'export' => 1])) ?>"><i class="bi bi-download me-1"></i> Exportar CSV</a></div>
<div class="row g-2 mb-3">
  <div class="col-6 col-md-3 col-xl"><a href="<?= url('crm/segmentos') ?>" class="seg-pill <?= $seg === 'todos' ? 'on' : '' ?>" style="--c:#0ea5e9"><i class="bi bi-people-fill"></i><b><?= $total ?></b><span>Todos</span></a></div>
<?php foreach (CRM_SEGS as $k => [$l, $i, $col]): ?>
  <div class="col-6 col-md-3 col-xl"><a href="<?= url('crm/segmentos?seg=' . $k) ?>" class="seg-pill <?= $seg === $k ? 'on' : '' ?>" style="--c:<?= $col ?>"><i class="bi <?= $i ?>"></i><b><?= $count[$k] ?></b><span><?= $l ?></span></a></div>
<?php endforeach; ?></div>
<div class="cw-card"><div class="card-h"><h6><?= $seg === 'todos' ? 'Todos los clientes' : e(CRM_SEGS[$seg][0] ?? '') ?> <span class="text-muted fw-normal">(<?= count($rows) ?>)</span></h6>
<form class="d-flex gap-2" method="get"><input type="hidden" name="seg" value="<?= e($seg) ?>"><input class="form-control form-control-sm" name="q" value="<?= e($q) ?>" placeholder="Buscar cliente o teléfono"><button class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button></form></div>
<?php if (isset(CRM_SEGS[$seg])): ?><div class="px-3 pb-2 small text-muted"><?= e(CRM_SEGS[$seg][3]) ?></div><?php endif; ?>
<div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Cliente</th><th>Segmento</th><th class="text-end">Visitas</th><th class="text-end">Gastado</th><th>Última visita</th><th class="text-end">Puntos</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $c): [$sl, $si, $sc] = CRM_SEGS[$c['segmento']];
  $tpl = in_array($c['segmento'], ['en_riesgo', 'inactivo']) ? $cfg['msg_riesgo'] : 'Hola {nombre}, te saludamos de {empresa}. Tienes {puntos} puntos acumulados 🚗✨';
  $w = crm_wa($c['telefono'], crm_msg($tpl, $c)); ?>
<tr><td><a class="fw-bold text-decoration-none text-dark" href="<?= url('clientes/ficha/' . $c['id']) ?>"><?= e($c['nombre']) ?></a><div class="small text-muted"><?= e($c['telefono'] ?: 'Sin teléfono') ?></div></td>
<td><span class="seg-badge" style="--c:<?= $sc ?>"><i class="bi <?= $si ?>"></i> <?= $sl ?></span></td>
<td class="text-end"><?= $c['visitas'] ?></td><td class="text-end fw-bold"><?= money($c['gastado']) ?></td>
<td><?= $c['ultima'] ? fdate($c['ultima']) . ' <span class="text-muted small">(' . (int)$c['dias'] . ' d)</span>' : '—' ?></td><td class="text-end"><?= num($c['puntos'], 0) ?></td>
<td class="text-end text-nowrap"><?php if ($w): ?><a class="btn btn-sm btn-success" target="_blank" href="<?= e($w) ?>" title="WhatsApp"><i class="bi bi-whatsapp"></i></a><?php endif; ?> <a class="btn btn-sm btn-outline-primary" href="<?= url('clientes/ficha/' . $c['id']) ?>"><i class="bi bi-person-lines-fill"></i></a></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-5">No hay clientes en este segmento</td></tr><?php endif; ?></tbody></table></div></div>
