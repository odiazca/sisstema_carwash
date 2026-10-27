<?php $tel = preg_replace('/\D+/', '', $c['telefono'] ?? ''); ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div><a href="<?= url('clientes') ?>" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Clientes</a>
  <h1 class="page-title mt-1"><?= e($c['nombre']) ?> <?php [$sl, $si, $sc] = CRM_SEGS[$seg]; ?><span class="seg-badge ms-2 align-middle" style="--c:<?= $sc ?>"><i class="bi <?= $si ?>"></i> <?= $sl ?></span></h1><p class="page-sub"><?= e($c['tipo_doc']) ?> <?= e($c['nro_doc'] ?? '') ?> · <?= e($c['email'] ?? '') ?></p></div>
  <div class="d-flex gap-2">
    <?php if ($tel): ?><a class="btn btn-success" target="_blank" href="https://wa.me/<?= $tel ?>?text=<?= rawurlencode('Hola ' . $c['nombre'] . ', le saludamos de ' . empresa()['nombre'] . '.') ?>"><i class="bi bi-whatsapp me-1"></i> WhatsApp</a><?php endif; ?>
    <a class="btn btn-outline-primary" href="<?= url('clientes/editar/' . $c['id']) ?>"><i class="bi bi-pencil"></i> Editar</a>
  </div>
</div>
<div class="row g-3 mb-3">
<?php foreach ([['Visitas', $tot['n'], 'bi-receipt', 'blue'], ['Total gastado', money($tot['t']), 'bi-cash-stack', 'green'], ['Última visita', $tot['ult'] ? fdate($tot['ult']) : '-', 'bi-calendar-event', 'violet'], ['Puntos de fidelidad', num($puntos, 0) . ($puntos ? ' (' . money($puntos * $cfg['valor_punto']) . ')' : ''), 'bi-award', 'teal']] as [$l, $v, $i, $col]): ?>
  <div class="col-sm-6 col-xl-3"><div class="cw-card stat t-<?= $col ?>"><div class="d-flex align-items-center gap-3"><div class="ico bg-<?= $col ?>"><i class="bi <?= $i ?>"></i></div><div><div class="l"><?= $l ?></div><div class="fs-5 fw-bold text-dark"><?= $v ?></div></div></div></div></div>
<?php endforeach; ?></div>
<div class="row g-3">
  <div class="col-lg-4"><div class="cw-card"><div class="card-h"><h6>Vehículos</h6><a href="<?= url('vehiculos/crear') ?>" class="small fw-bold text-decoration-none">+ Agregar</a></div><div class="card-b">
    <?php foreach ($veh as $v): ?><div class="border-bottom py-2"><b><?= e($v['placa']) ?></b> <span class="text-muted small"><?= e(trim($v['marca'] . ' ' . $v['modelo'] . ' ' . $v['color'])) ?></span><div class="small text-muted"><?= e($v['tipo'] ?? '') ?></div></div><?php endforeach; ?>
    <?php if (!$veh): ?><div class="text-muted text-center py-3">Sin vehículos</div><?php endif; ?></div></div></div>
  <div class="col-lg-8"><div class="cw-card"><div class="card-h"><h6>Historial de órdenes</h6></div>
    <div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Código</th><th>Fecha</th><th>Placa</th><th>Estado</th><th class="text-end">Total</th></tr></thead><tbody>
    <?php foreach ($ord as $o): ?><tr><td><a class="fw-bold text-decoration-none" href="<?= url('ordenes/ver/' . $o['id']) ?>"><?= e($o['codigo']) ?></a></td><td><?= fdate($o['fecha_ingreso'], true) ?></td><td><?= e($o['placa'] ?? '-') ?></td><td><?= estado_badge($o['estado']) ?></td><td class="text-end fw-bold"><?= money($o['total']) ?></td></tr><?php endforeach; ?>
    <?php if (!$ord): ?><tr><td colspan="5" class="text-center text-muted py-4">Sin órdenes</td></tr><?php endif; ?></tbody></table></div></div></div>
</div>

<div class="row g-3 mt-1">
  <div class="col-lg-5"><div class="cw-card"><div class="card-h"><h6><i class="bi bi-journal-plus text-primary me-2"></i>Registrar contacto / seguimiento</h6></div><div class="card-b">
    <form method="post" action="<?= url('crm/interaccion_guardar') ?>" class="row g-2"><?= csrf_field() ?><input type="hidden" name="cliente_id" value="<?= $c['id'] ?>"><input type="hidden" name="volver" value="clientes/ficha/<?= $c['id'] ?>">
      <div class="col-5"><select name="tipo" class="form-select form-select-sm"><?php foreach (['llamada' => 'Llamada', 'whatsapp' => 'WhatsApp', 'email' => 'Correo', 'visita' => 'Visita', 'nota' => 'Nota'] as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
      <div class="col-7"><input type="date" name="seguimiento_fecha" class="form-control form-control-sm" title="Recordar el"></div>
      <div class="col-12"><input name="asunto" class="form-control form-control-sm" placeholder="Asunto" maxlength="150" required></div>
      <div class="col-12"><textarea name="detalle" class="form-control form-control-sm" rows="2" placeholder="Detalle (opcional)"></textarea></div>
      <div class="col-12"><button class="btn btn-sm btn-grad w-100">Guardar</button></div></form>
    <?php if ($cupones): ?><hr><div class="small fw-bold mb-1"><i class="bi bi-ticket-perforated text-success"></i> Cupones disponibles</div>
      <?php foreach ($cupones as $k): ?><div class="d-flex justify-content-between small"><code><?= e($k['codigo']) ?></code><b><?= $k['tipo'] === 'porcentaje' ? num($k['valor'], 0) . '%' : money($k['valor']) ?></b></div><?php endforeach; ?><?php endif; ?>
    <?php if ($csat): ?><hr><div class="small"><i class="bi bi-star-fill text-warning"></i> Satisfacción promedio: <b><?= $csat ?>/5</b></div><?php endif; ?>
    <?php if ($c['fecha_nacimiento']): ?><div class="small mt-1"><i class="bi bi-cake2 text-danger"></i> Cumpleaños: <b><?= date('d/m', strtotime($c['fecha_nacimiento'])) ?></b></div><?php endif; ?>
    <?php if (!$c['acepta_marketing']): ?><div class="small text-danger mt-1"><i class="bi bi-slash-circle"></i> No desea recibir promociones</div><?php endif; ?>
  </div></div></div>
  <div class="col-lg-7"><div class="cw-card"><div class="card-h"><h6><i class="bi bi-clock-history text-primary me-2"></i>Línea de tiempo CRM</h6></div><div class="card-b"><div class="tl">
    <?php foreach ($inter as $i): ?><div class="tl-i"><div class="d-flex justify-content-between"><b><?= e($i['asunto']) ?></b><span class="small text-muted"><?= fdate($i['created_at'], true) ?></span></div>
      <div class="small text-muted"><?= e(ucfirst($i['tipo'])) ?> · <?= e($i['usuario'] ?? '') ?></div><?php if ($i['detalle']): ?><div class="small"><?= nl2br(e($i['detalle'])) ?></div><?php endif; ?>
      <?php if ($i['seguimiento_fecha']): ?><span class="badge badge-soft-<?= $i['seguimiento_hecho'] ? 'success' : 'warning' ?>"><?= $i['seguimiento_hecho'] ? 'Hecho' : 'Seguimiento ' . fdate($i['seguimiento_fecha']) ?></span><?php endif; ?></div>
    <?php endforeach; if (!$inter): ?><div class="text-muted text-center py-3">Sin interacciones registradas</div><?php endif; ?></div></div></div></div>
</div>
