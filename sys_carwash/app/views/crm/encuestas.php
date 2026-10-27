<?php $crmTab = 'encuestas'; include __DIR__ . '/_nav.php'; $sat = $st['n'] ? round($st['sat'] * 100 / $st['n']) : 0; ?>
<div class="mb-3"><h1 class="page-title">Encuestas de satisfacción</h1><p class="page-sub">Pide a tus clientes que califiquen el servicio con un enlace por WhatsApp</p></div>
<div class="row g-3 mb-3">
 <div class="col-md-4"><div class="cw-card stat t-green"><div class="d-flex align-items-center gap-3"><div class="ico bg-green"><i class="bi bi-star-fill"></i></div><div><div class="l">Calificación promedio</div><div class="fs-3 fw-bold text-dark"><?= $st['n'] ? $st['prom'] : '—' ?><small class="text-muted fs-6"> / 5</small></div></div></div></div></div>
 <div class="col-md-4"><div class="cw-card stat t-blue"><div class="d-flex align-items-center gap-3"><div class="ico bg-blue"><i class="bi bi-emoji-smile"></i></div><div><div class="l">Clientes satisfechos (4–5★)</div><div class="fs-3 fw-bold text-dark"><?= $sat ?>%</div></div></div></div></div>
 <div class="col-md-4"><div class="cw-card p-3"><?php for ($s = 5; $s >= 1; $s--): $p = $st['n'] ? round($dist[$s] * 100 / $st['n']) : 0; ?><div class="d-flex align-items-center gap-2 small"><span style="width:28px"><?= $s ?>★</span><div class="progress flex-grow-1" style="height:7px"><div class="progress-bar" style="width:<?= $p ?>%;background:#f59e0b"></div></div><span style="width:28px" class="text-end"><?= $dist[$s] ?></span></div><?php endfor; ?></div></div>
</div>
<div class="row g-3">
 <div class="col-lg-6"><div class="cw-card"><div class="card-h"><h6><i class="bi bi-send text-success me-2"></i>Pedir opinión (últimos 14 días)</h6></div><div class="card-b pt-1">
  <?php foreach ($cand as $o): ?><div class="d-flex align-items-center gap-2 py-2 border-bottom"><div class="flex-grow-1"><b><?= e($o['nombre']) ?></b><div class="small text-muted"><?= e($o['codigo']) ?> · <?= fdate($o['fecha_ingreso']) ?> · <?= money($o['total']) ?></div></div>
   <?php if ($o['wa']): ?><a class="btn btn-sm btn-success" target="_blank" href="<?= e($o['wa']) ?>"><i class="bi bi-whatsapp me-1"></i> Enviar</a><?php endif; ?>
   <button class="btn btn-sm btn-outline-secondary" data-copy="<?= e($o['enlace']) ?>" title="Copiar enlace"><i class="bi bi-link-45deg"></i></button></div>
  <?php endforeach; if (!$cand): ?><div class="text-center text-muted py-4">No hay órdenes cobradas y terminadas pendientes de encuesta</div><?php endif; ?></div></div></div>
 <div class="col-lg-6"><div class="cw-card"><div class="card-h"><h6><i class="bi bi-chat-quote text-primary me-2"></i>Respuestas recientes</h6></div><div class="card-b pt-1">
  <?php foreach ($res as $r): ?><div class="py-2 border-bottom"><div class="d-flex justify-content-between"><b><?= e($r['cliente'] ?? 'Cliente') ?> <span class="text-muted small">· <?= e($r['codigo']) ?></span></b><span class="text-warning"><?= str_repeat('★', (int)$r['puntaje']) . '<span class="text-muted opacity-50">' . str_repeat('★', 5 - (int)$r['puntaje']) . '</span>' ?></span></div>
   <?php if ($r['comentario']): ?><div class="small">“<?= e($r['comentario']) ?>”</div><?php endif; ?><div class="small text-muted"><?= fdate($r['respondido_at'], true) ?></div></div>
  <?php endforeach; if (!$res): ?><div class="text-center text-muted py-4">Aún no hay respuestas</div><?php endif; ?></div></div></div>
</div>
<?php ob_start(); ?><script>document.querySelectorAll('[data-copy]').forEach(b => b.addEventListener('click', () => { navigator.clipboard && navigator.clipboard.writeText(b.dataset.copy); b.innerHTML = '<i class="bi bi-check2"></i>'; }));</script><?php $scripts = ob_get_clean(); ?>
