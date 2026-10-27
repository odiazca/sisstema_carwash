<?php $crmTab = 'seguimientos'; include __DIR__ . '/_nav.php'; ?>
<div class="row g-3">
 <div class="col-lg-5"><div class="cw-card"><div class="card-h"><h6><i class="bi bi-plus-circle text-primary me-2"></i>Registrar interacción / tarea</h6></div><div class="card-b">
  <form method="post" action="<?= url('crm/interaccion_guardar') ?>" class="row g-2"><?= csrf_field() ?>
   <div class="col-12"><label class="form-label small">Cliente</label><select name="cliente_id" class="form-select" required><option value="">— Seleccionar —</option><?php foreach ($clientes as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['nombre']) ?></option><?php endforeach; ?></select></div>
   <div class="col-5"><label class="form-label small">Tipo</label><select name="tipo" class="form-select"><?php foreach ($tipos as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
   <div class="col-7"><label class="form-label small">Recordar el</label><input type="date" name="seguimiento_fecha" class="form-control"></div>
   <div class="col-12"><label class="form-label small">Asunto</label><input name="asunto" class="form-control" maxlength="150" required placeholder="Ej: Ofrecer plan mensual"></div>
   <div class="col-12"><label class="form-label small">Detalle</label><textarea name="detalle" class="form-control" rows="3"></textarea></div>
   <div class="col-12"><button class="btn btn-grad w-100"><i class="bi bi-check2 me-1"></i> Guardar</button></div></form></div></div></div>
 <div class="col-lg-7">
  <div class="cw-card mb-3"><div class="card-h"><h6><i class="bi bi-alarm text-warning me-2"></i>Seguimientos pendientes (<?= count($pend) ?>)</h6></div><div class="card-b pt-1">
   <?php foreach ($pend as $p): $venc = $p['seguimiento_fecha'] < date('Y-m-d'); $w = crm_wa($p['telefono'], 'Hola ' . explode(' ', $p['cliente'])[0] . ', te escribimos de ' . empresa()['nombre'] . '.'); ?>
   <div class="d-flex gap-3 align-items-start py-2 border-bottom"><span class="badge badge-soft-<?= $venc ? 'danger' : ($p['seguimiento_fecha'] == date('Y-m-d') ? 'warning' : 'info') ?> mt-1"><?= fdate($p['seguimiento_fecha']) ?></span>
    <div class="flex-grow-1"><a href="<?= url('clientes/ficha/' . $p['cliente_id']) ?>" class="fw-bold text-decoration-none text-dark"><?= e($p['cliente']) ?></a> <span class="text-muted small">· <?= e($tipos[$p['tipo']]) ?></span><div><?= e($p['asunto']) ?></div><?php if ($p['detalle']): ?><div class="small text-muted"><?= nl2br(e($p['detalle'])) ?></div><?php endif; ?></div>
    <div class="text-nowrap"><?php if ($w): ?><a class="btn btn-sm btn-success" target="_blank" href="<?= e($w) ?>"><i class="bi bi-whatsapp"></i></a><?php endif; ?>
    <form method="post" action="<?= url('crm/seguimiento_hecho') ?>" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="btn btn-sm btn-outline-success" title="Marcar como hecho"><i class="bi bi-check-lg"></i></button></form></div></div>
   <?php endforeach; if (!$pend): ?><div class="text-center text-muted py-4">Todo al día ✅</div><?php endif; ?></div></div>
  <div class="cw-card"><div class="card-h"><h6>Historial reciente</h6></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Fecha</th><th>Cliente</th><th>Tipo</th><th>Asunto</th><th>Por</th></tr></thead><tbody>
   <?php foreach ($hist as $h): ?><tr><td><?= fdate($h['created_at'], true) ?></td><td><b><?= e($h['cliente']) ?></b></td><td><?= e($tipos[$h['tipo']]) ?></td><td><?= e($h['asunto']) ?></td><td class="small text-muted"><?= e($h['usuario'] ?? '') ?></td></tr><?php endforeach; if (!$hist): ?><tr><td colspan="5" class="text-center text-muted py-4">Sin registros</td></tr><?php endif; ?></tbody></table></div></div>
 </div></div>
