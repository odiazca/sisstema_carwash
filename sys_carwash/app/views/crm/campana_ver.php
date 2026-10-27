<?php $crmTab = 'campanas'; include __DIR__ . '/_nav.php'; $env_n = count(array_filter($env, fn($e) => $e['enviado'])); ?>
<div class="mb-3"><a href="<?= url('crm/campanas') ?>" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Campañas</a><h1 class="page-title mt-1"><?= e($c['nombre']) ?></h1>
<p class="page-sub"><?= count($env) ?> destinatarios · <span id="enviados"><?= $env_n ?></span> enviados · Canal: <?= $c['canal'] === 'whatsapp' ? 'WhatsApp' : 'Correo' ?></p></div>
<div class="cw-card mb-3 p-3"><div class="small text-muted mb-1">Mensaje base</div><div style="white-space:pre-wrap"><?= e($c['mensaje']) ?></div></div>
<div class="cw-card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Cliente</th><th><?= $c['canal'] === 'whatsapp' ? 'Teléfono' : 'Correo' ?></th><th>Estado</th><th class="text-end">Acción</th></tr></thead><tbody>
<?php foreach ($env as $e): ?><tr id="r<?= $e['id'] ?>"><td><b><?= e($e['nombre']) ?></b></td><td><?= e($c['canal'] === 'whatsapp' ? $e['telefono'] : $e['email']) ?></td>
<td class="st"><?= $e['enviado'] ? '<span class="badge badge-soft-success">Enviado ' . fdate($e['enviado_at'], true) . '</span>' : '<span class="badge badge-soft-warning">Pendiente</span>' ?></td>
<td class="text-end"><a class="btn btn-sm <?= $c['canal'] === 'whatsapp' ? 'btn-success' : 'btn-primary' ?>" target="_blank" rel="noopener" href="<?= e($e['link']) ?>" data-mark="<?= $e['id'] ?>"><i class="bi <?= $c['canal'] === 'whatsapp' ? 'bi-whatsapp' : 'bi-envelope' ?> me-1"></i> <?= $e['enviado'] ? 'Reenviar' : 'Enviar' ?></a></td></tr>
<?php endforeach; ?></tbody></table></div></div>
<?php ob_start(); ?>
<script>
document.querySelectorAll('[data-mark]').forEach(a => a.addEventListener('click', async () => {
  const fd = new FormData(); fd.append('_csrf', <?= json_encode(csrf_token()) ?>); fd.append('id', a.dataset.mark);
  try { const r = await (await fetch(<?= json_encode(url('crm/campana_marcar')) ?>, {method:'POST', body:fd})).json();
    if (r.ok) { const tr = document.getElementById('r' + a.dataset.mark); const st = tr.querySelector('.st'); if (!st.textContent.includes('Enviado')) { st.innerHTML = '<span class="badge badge-soft-success">Enviado ahora</span>'; const n = document.getElementById('enviados'); n.textContent = +n.textContent + 1; } } } catch (e) {}
}));
</script>
<?php $scripts = ob_get_clean(); ?>
