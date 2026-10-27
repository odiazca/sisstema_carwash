<?php $crmTab = 'campanas'; include __DIR__ . '/_nav.php'; ?>
<div class="mb-3"><h1 class="page-title">Campañas de marketing</h1><p class="page-sub">Envía promociones segmentadas por WhatsApp o correo. Respeta a clientes que no desean recibir publicidad.</p></div>
<div class="row g-3">
 <div class="col-lg-5"><div class="cw-card"><div class="card-h"><h6><i class="bi bi-megaphone text-primary me-2"></i>Nueva campaña</h6></div><div class="card-b">
  <form method="post" action="<?= url('crm/campana_crear') ?>" class="row g-2"><?= csrf_field() ?>
   <div class="col-12"><label class="form-label small">Nombre</label><input name="nombre" class="form-control" required placeholder="Ej: Recuperación de octubre"></div>
   <div class="col-7"><label class="form-label small">Audiencia</label><select name="segmento" id="cmSeg" class="form-select">
     <option value="todos">Todos (<?= $count['todos'] ?>)</option><option value="cumple_mes">🎂 Cumpleañeros del mes (<?= $count['cumple_mes'] ?>)</option>
     <?php foreach (CRM_SEGS as $k => [$l]): ?><option value="<?= $k ?>"><?= $l ?> (<?= $count[$k] ?>)</option><?php endforeach; ?></select></div>
   <div class="col-5"><label class="form-label small">Canal</label><select name="canal" class="form-select"><option value="whatsapp">WhatsApp</option><option value="email">Correo</option></select></div>
   <div class="col-12"><label class="form-label small">Mensaje <span class="text-muted">— variables: {nombre} {empresa} {puntos} {cupon}</span></label><textarea name="mensaje" id="cmMsg" rows="5" class="form-control" required></textarea>
    <div class="d-flex gap-1 mt-1 flex-wrap"><button type="button" class="btn btn-sm btn-light" data-tpl="cumple">Cumpleaños</button><button type="button" class="btn btn-sm btn-light" data-tpl="riesgo">Recuperación</button><button type="button" class="btn btn-sm btn-light" data-tpl="promo">Promoción</button></div></div>
   <div class="col-6"><label class="form-label small">Cupón % (opcional)</label><input name="cupon_pct" type="number" min="0" max="100" step="1" class="form-control" placeholder="Ej: 15"></div>
   <div class="col-6"><label class="form-label small">Vigencia (días)</label><input name="cupon_dias" type="number" min="1" value="30" class="form-control"></div>
   <div class="form-text">Si indicas un cupón, usa <b>{cupon}</b> en el mensaje para insertar el código.</div>
   <div class="col-12"><button class="btn btn-grad w-100"><i class="bi bi-send me-1"></i> Crear campaña y generar envíos</button></div></form></div></div></div>
 <div class="col-lg-7"><div class="cw-card"><div class="card-h"><h6>Campañas</h6></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Campaña</th><th>Audiencia</th><th>Canal</th><th class="text-center">Enviados</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $c): $pct = $c['total'] ? round($c['enviados'] * 100 / $c['total']) : 0; ?><tr><td><b><?= e($c['nombre']) ?></b><div class="small text-muted"><?= fdate($c['created_at']) ?></div></td><td><?= e(CRM_SEGS[$c['segmento']][0] ?? ($c['segmento'] === 'cumple_mes' ? 'Cumpleaños' : 'Todos')) ?></td>
   <td><i class="bi <?= $c['canal'] === 'whatsapp' ? 'bi-whatsapp text-success' : 'bi-envelope text-primary' ?>"></i></td>
   <td class="text-center" style="min-width:120px"><div class="progress" style="height:8px"><div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div></div><small><?= $c['enviados'] ?>/<?= $c['total'] ?></small></td>
   <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= url('crm/campana_ver/' . $c['id']) ?>">Abrir</a></td></tr><?php endforeach; if (!$rows): ?><tr><td colspan="5" class="text-center text-muted py-5">Aún no hay campañas</td></tr><?php endif; ?></tbody></table></div></div></div>
</div>
<?php ob_start(); ?>
<script>
const TPL = {cumple: <?= json_encode($cfg['msg_cumple'], JSON_UNESCAPED_UNICODE) ?>, riesgo: <?= json_encode($cfg['msg_riesgo'], JSON_UNESCAPED_UNICODE) ?>, promo: 'Hola {nombre} 🚗✨ En {empresa} tenemos una promoción para ti: usa el cupón {cupon} en tu próximo lavado. ¡Te esperamos!'};
document.querySelectorAll('[data-tpl]').forEach(b => b.addEventListener('click', () => { document.getElementById('cmMsg').value = TPL[b.dataset.tpl]; if (b.dataset.tpl === 'cumple') document.getElementById('cmSeg').value = 'cumple_mes'; if (b.dataset.tpl === 'riesgo') document.getElementById('cmSeg').value = 'en_riesgo'; }));
</script>
<?php $scripts = ob_get_clean(); ?>
