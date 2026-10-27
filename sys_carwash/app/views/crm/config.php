<?php $crmTab = 'config'; include __DIR__ . '/_nav.php'; ?>
<div class="mb-3"><h1 class="page-title">Ajustes del CRM</h1><p class="page-sub">Reglas de fidelización, segmentación y plantillas de mensajes</p></div>
<form method="post" action="<?= url('crm/config_guardar') ?>" class="row g-3"><?= csrf_field() ?>
 <div class="col-lg-6"><div class="cw-card h-100"><div class="card-h"><h6><i class="bi bi-award text-primary me-2"></i>Programa de puntos</h6></div><div class="card-b row g-2">
  <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="programa_activo" value="1" id="pa" <?= $cfg['programa_activo'] ? 'checked' : '' ?>><label class="form-check-label fw-semibold" for="pa">Programa de puntos activo</label></div></div>
  <div class="col-6"><label class="form-label small">1 punto por cada (monto)</label><input type="number" step="0.01" min="0.01" name="monto_por_punto" value="<?= e($cfg['monto_por_punto']) ?>" class="form-control"></div>
  <div class="col-6"><label class="form-label small">Valor de 1 punto al canjear</label><input type="number" step="0.01" min="0" name="valor_punto" value="<?= e($cfg['valor_punto']) ?>" class="form-control"></div>
  <div class="col-6"><label class="form-label small">Puntos mínimos para canjear</label><input type="number" min="0" name="puntos_minimo" value="<?= e($cfg['puntos_minimo']) ?>" class="form-control"></div>
  <div class="col-6"><label class="form-label small">Acumular desde (fecha)</label><input type="date" name="puntos_desde" value="<?= e($cfg['puntos_desde']) ?>" class="form-control"></div>
  <div class="form-text">Las órdenes cobradas desde esa fecha generan puntos automáticamente. Las órdenes anuladas restan sus puntos.</div></div></div></div>
 <div class="col-lg-6"><div class="cw-card h-100"><div class="card-h"><h6><i class="bi bi-pie-chart text-primary me-2"></i>Reglas de segmentación</h6></div><div class="card-b row g-2">
  <div class="col-6"><label class="form-label small">VIP: visitas mínimas</label><input type="number" min="1" name="vip_visitas" value="<?= e($cfg['vip_visitas']) ?>" class="form-control"></div>
  <div class="col-6"><label class="form-label small">VIP: o gasto total desde</label><input type="number" step="0.01" min="0" name="vip_monto" value="<?= e($cfg['vip_monto']) ?>" class="form-control"></div>
  <div class="col-6"><label class="form-label small">«En riesgo» tras (días sin venir)</label><input type="number" min="1" name="dias_riesgo" value="<?= e($cfg['dias_riesgo']) ?>" class="form-control"></div>
  <div class="col-6"><label class="form-label small">«Inactivo» tras (días)</label><input type="number" min="2" name="dias_inactivo" value="<?= e($cfg['dias_inactivo']) ?>" class="form-control"></div>
  <div class="col-6"><label class="form-label small">Prefijo telefónico del país</label><input name="prefijo_pais" value="<?= e($cfg['prefijo_pais']) ?>" class="form-control" maxlength="5"><div class="form-text">Perú 51, México 52, Colombia 57…</div></div></div></div></div>
 <div class="col-12"><div class="cw-card"><div class="card-h"><h6><i class="bi bi-chat-text text-primary me-2"></i>Plantillas de mensaje</h6><small class="text-muted">{nombre} {empresa} {puntos} {cupon} {enlace}</small></div><div class="card-b row g-2">
  <div class="col-lg-4"><label class="form-label small">Cumpleaños</label><textarea name="msg_cumple" rows="4" class="form-control"><?= e($cfg['msg_cumple']) ?></textarea></div>
  <div class="col-lg-4"><label class="form-label small">Recuperación (en riesgo / inactivo)</label><textarea name="msg_riesgo" rows="4" class="form-control"><?= e($cfg['msg_riesgo']) ?></textarea></div>
  <div class="col-lg-4"><label class="form-label small">Encuesta de satisfacción</label><textarea name="msg_encuesta" rows="4" class="form-control"><?= e($cfg['msg_encuesta']) ?></textarea></div></div></div></div>
 <div class="col-12 text-end"><button class="btn btn-grad px-4"><i class="bi bi-check2-circle me-1"></i> Guardar ajustes</button></div>
</form>
