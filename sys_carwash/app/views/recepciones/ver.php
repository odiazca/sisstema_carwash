<?php $ZN = RecepcionesController::ZONAS; $DN = RecepcionesController::DANOS; $CK = RecepcionesController::CHECK; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <div><h1 class="page-title">Recepción #<?= $r['id'] ?> · <?= e($r['placa']) ?></h1><p class="page-sub"><?= fdate($r['created_at'], true) ?> · por <?= e($r['usuario'] ?? '-') ?></p></div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="<?= url('recepciones/acta/' . $r['id']) ?>" target="_blank" class="btn btn-light"><i class="bi bi-printer me-1"></i> Imprimir acta</a>
    <?php if ($r['orden_id']): ?><a href="<?= url('ordenes/ver/' . $r['orden_id']) ?>" class="btn btn-light"><i class="bi bi-card-checklist me-1"></i> Orden <?= e($r['orden']) ?></a>
    <?php else: ?><a href="<?= url('ordenes/nueva?recepcion=' . $r['id']) ?>" class="btn btn-grad"><i class="bi bi-plus-circle me-1"></i> Generar orden de servicio</a><?php endif; ?>
    <form method="post" action="<?= url('recepciones/eliminar') ?>" onsubmit="return confirm('¿Eliminar esta recepción y sus fotos?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-light text-danger"><i class="bi bi-trash"></i></button></form>
  </div>
</div>
<div class="row g-3">
  <div class="col-lg-5">
    <div class="cw-card mb-3"><div class="card-h"><h6>Datos</h6></div><div class="card-b small">
      <div class="d-flex justify-content-between py-1"><span class="text-muted">Cliente</span><b><?= e($r['cliente'] ?? '-') ?></b></div>
      <div class="d-flex justify-content-between py-1"><span class="text-muted">Vehículo</span><b><?= e(trim(($r['marca'] ?? '') . ' ' . ($r['modelo'] ?? '') . ' ' . ($r['color'] ?? '')) ?: '-') ?></b></div>
      <div class="d-flex justify-content-between py-1"><span class="text-muted">Kilometraje</span><b><?= $r['kilometraje'] ? num($r['kilometraje'], 0) . ' km' : '-' ?></b></div>
      <div class="py-1"><span class="text-muted">Combustible</span><div class="progress mt-1" style="height:10px"><div class="progress-bar bg-info" style="width:<?= (int)$r['combustible'] ?>%"></div></div></div>
      <?php if ($r['observaciones']): ?><div class="mt-2 p-2 rounded-3" style="background:#f2f9ff"><?= e($r['observaciones']) ?></div><?php endif; ?></div></div>
    <div class="cw-card"><div class="card-h"><h6>Checklist</h6></div><div class="card-b">
      <?php foreach ($CK as $k => $l): $on = in_array($k, $r['check_l']); ?><div class="py-1 <?= $on ? '' : 'text-muted' ?>"><i class="bi <?= $on ? 'bi-check-circle-fill text-success' : 'bi-dash-circle' ?> me-2"></i><?= $l ?></div><?php endforeach; ?></div></div>
  </div>
  <div class="col-lg-7">
    <div class="cw-card mb-3"><div class="card-h"><h6>Daños previos</h6></div><div class="card-b">
      <?php if (!$r['danos_l']): ?><span class="badge badge-soft-success">Sin daños declarados</span><?php endif; ?>
      <?php foreach ($r['danos_l'] as $z => $t): ?><div class="d-flex justify-content-between border-bottom py-1"><span><?= e($ZN[$z] ?? $z) ?></span><span class="badge badge-soft-warning"><?= e($DN[$t] ?? $t) ?></span></div><?php endforeach; ?></div></div>
    <div class="cw-card mb-3"><div class="card-h"><h6>Fotos (<?= count($r['fotos_l']) ?>)</h6></div><div class="card-b"><div class="row g-2">
      <?php foreach ($r['fotos_l'] as $f): ?><div class="col-4 col-md-3"><a href="<?= asset('uploads/recepciones/' . e($f)) ?>" target="_blank"><img src="<?= asset('uploads/recepciones/' . e($f)) ?>" class="img-fluid rounded-3" style="aspect-ratio:1;object-fit:cover;width:100%"></a></div><?php endforeach; ?>
      <?php if (!$r['fotos_l']): ?><div class="text-muted small">Sin fotos.</div><?php endif; ?></div></div></div>
    <?php if ($r['firma']): ?><div class="cw-card"><div class="card-h"><h6>Firma del cliente</h6></div><div class="card-b"><img src="<?= asset('uploads/recepciones/' . e($r['firma'])) ?>" style="max-height:110px"></div></div><?php endif; ?>
  </div>
</div>
