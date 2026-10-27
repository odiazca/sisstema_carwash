<?php $hoy = date('Y-m-d'); $ant = date('Y-m-d', strtotime("$f -1 day")); $sig = date('Y-m-d', strtotime("$f +1 day")); ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <div><h1 class="page-title">Asistencia</h1><p class="page-sub">Marca la entrada y salida del personal · horario <?= substr($cfg['hora_entrada'], 0, 5) ?> – <?= substr($cfg['hora_salida'], 0, 5) ?> · tolerancia <?= (int)$cfg['tolerancia'] ?> min</p></div>
  <div class="d-flex gap-2 flex-wrap"><a class="btn btn-light" href="<?= url('asistencia/mes') ?>"><i class="bi bi-calendar3 me-1"></i> Resumen mensual</a><a class="btn btn-light" href="<?= url('asistencia/config') ?>"><i class="bi bi-gear"></i></a></div>
</div>
<div class="cw-card mb-3"><div class="card-b d-flex flex-wrap align-items-center gap-2 justify-content-between">
  <div class="d-flex align-items-center gap-2"><a class="btn btn-sm btn-light" href="?fecha=<?= $ant ?>"><i class="bi bi-chevron-left"></i></a>
  <form method="get" class="d-flex gap-2"><input type="date" name="fecha" value="<?= e($f) ?>" max="<?= $hoy ?>" class="form-control form-control-sm" onchange="this.form.submit()"></form>
  <a class="btn btn-sm btn-light <?= $f >= $hoy ? 'disabled' : '' ?>" href="?fecha=<?= $sig ?>"><i class="bi bi-chevron-right"></i></a>
  <?php if ($f !== $hoy): ?><a class="btn btn-sm btn-outline-primary" href="?fecha=<?= $hoy ?>">Hoy</a><?php endif; ?>
  <?php if (!$laboral): ?><span class="badge badge-soft-secondary">Día no laboral</span><?php endif; ?></div>
  <div class="d-flex gap-2 flex-wrap small"><?php foreach (AsistenciaController::ESTADOS as $k => $l): ?><span class="badge badge-soft-<?= AsistenciaController::COLOR[$k] ?>"><?= $res[$k] ?> <?= $l ?></span><?php endforeach; ?><span class="badge badge-soft-dark"><?= $res['sin'] ?> sin registro</span></div>
</div></div>
<div class="cw-card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Empleado</th><th>Estado</th><th>Entrada</th><th>Salida</th><th>Horas</th><th class="text-end">Registrar</th></tr></thead><tbody>
<?php foreach ($rows as $r): $h = ($r['entrada'] && $r['salida']) ? round((strtotime($r['salida']) - strtotime($r['entrada'])) / 3600, 1) : null; ?>
<tr><td><b><?= e($r['nombre']) ?></b><div class="small text-muted"><?= e($r['cargo']) ?></div></td>
<td><?= $r['estado'] ? '<span class="badge badge-soft-' . AsistenciaController::COLOR[$r['estado']] . '">' . AsistenciaController::ESTADOS[$r['estado']] . ($r['min_tarde'] ? ' · ' . (int)$r['min_tarde'] . ' min' : '') . '</span>' : '<span class="badge badge-soft-dark">Sin registro</span>' ?></td>
<td><?= $r['entrada'] ? substr($r['entrada'], 0, 5) : '-' ?></td><td><?= $r['salida'] ? substr($r['salida'], 0, 5) : '-' ?></td><td><?= $h !== null ? $h . ' h' : '-' ?></td>
<td class="text-end"><form method="post" action="<?= url('asistencia/marcar') ?>" class="d-inline-flex gap-1 flex-wrap justify-content-end align-items-center"><?= csrf_field() ?><input type="hidden" name="empleado_id" value="<?= $r['id'] ?>"><input type="hidden" name="fecha" value="<?= e($f) ?>">
<input type="time" name="hora" class="form-control form-control-sm" style="width:100px" title="Hora (vacío = ahora<?= $f !== $hoy ? ' / horario oficial' : '' ?>)">
<button name="accion" value="entrada" class="btn btn-sm btn-success" title="Entrada"><i class="bi bi-box-arrow-in-right"></i></button>
<button name="accion" value="salida" class="btn btn-sm btn-primary" title="Salida"><i class="bi bi-box-arrow-right"></i></button>
<button name="accion" value="falta" class="btn btn-sm btn-outline-danger" title="Falta">F</button><button name="accion" value="permiso" class="btn btn-sm btn-outline-info" title="Permiso">P</button><button name="accion" value="descanso" class="btn btn-sm btn-outline-secondary" title="Descanso">D</button>
<?php if ($r['estado']): ?><button name="accion" value="borrar" class="btn btn-sm btn-light text-danger" title="Borrar registro"><i class="bi bi-x-lg"></i></button><?php endif; ?></form></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-5">No hay empleados activos. Créalos en Personal → Empleados.</td></tr><?php endif; ?></tbody></table></div>
<?php if ($rows && $laboral && $res['sin']): ?><div class="card-b border-top d-flex justify-content-between align-items-center flex-wrap gap-2"><span class="small text-muted">Los <?= $res['sin'] ?> empleados sin registro pueden marcarse como falta al terminar el día.</span>
<form method="post" action="<?= url('asistencia/cerrar_dia') ?>" onsubmit="return confirm('¿Marcar como falta a quienes no tienen registro?')"><?= csrf_field() ?><input type="hidden" name="fecha" value="<?= e($f) ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-calendar-x me-1"></i> Cerrar día (marcar faltas)</button></form></div><?php endif; ?></div>
