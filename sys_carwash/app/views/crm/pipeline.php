<?php $crmTab = 'pipeline'; include __DIR__ . '/_nav.php'; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2"><div><h1 class="page-title">Oportunidades</h1><p class="page-sub">Prospectos y ventas potenciales (empresas, flotas, planes). Arrastra las tarjetas entre columnas.</p></div>
<button class="btn btn-grad" data-bs-toggle="modal" data-bs-target="#opModal" onclick="opForm()"><i class="bi bi-plus-lg me-1"></i> Nueva oportunidad</button></div>
<div class="kanban">
<?php foreach ($etapas as $e => [$lbl, $col]): $sum = array_sum(array_column($ops[$e], 'valor_est')); ?>
  <div class="kb-col" data-etapa="<?= $e ?>"><div class="kb-h" style="--c:<?= $col ?>"><b><?= $lbl ?></b><span><?= count($ops[$e]) ?> · <?= money($sum) ?></span></div>
  <div class="kb-body">
  <?php foreach ($ops[$e] as $o): ?>
    <div class="kb-card" draggable="true" data-id="<?= $o['id'] ?>">
      <div class="d-flex justify-content-between"><b><?= e($o['nombre']) ?></b><button class="btn btn-sm p-0 text-muted" data-bs-toggle="modal" data-bs-target="#opModal" onclick='opForm(<?= json_encode($o, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i class="bi bi-pencil"></i></button></div>
      <?php if ($o['interes']): ?><div class="small"><?= e($o['interes']) ?></div><?php endif; ?>
      <div class="d-flex justify-content-between align-items-center mt-2"><span class="fw-bold text-primary"><?= money($o['valor_est']) ?></span><span class="small text-muted"><?= e($o['origen'] ?? '') ?></span></div>
      <div class="d-flex gap-1 mt-2 flex-wrap">
        <?php if ($o['telefono'] && ($w = crm_wa($o['telefono'], 'Hola ' . explode(' ', $o['nombre'])[0] . ', te saludamos de ' . empresa()['nombre']))): ?><a class="btn btn-sm btn-success py-0" target="_blank" href="<?= e($w) ?>"><i class="bi bi-whatsapp"></i></a><?php endif; ?>
        <?php if ($o['cliente_id']): ?><a class="btn btn-sm btn-outline-primary py-0" href="<?= url('clientes/ficha/' . $o['cliente_id']) ?>">Cliente</a>
        <?php elseif ($e !== 'perdido'): ?><form method="post" action="<?= url('crm/op_convertir') ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $o['id'] ?>"><button class="btn btn-sm btn-outline-success py-0" data-confirm="¿Convertir en cliente?">→ Cliente</button></form><?php endif; ?>
        <form method="post" action="<?= url('crm/op_borrar') ?>" class="ms-auto"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $o['id'] ?>"><button class="btn btn-sm text-danger py-0" data-confirm="¿Eliminar oportunidad?"><i class="bi bi-trash"></i></button></form>
      </div></div>
  <?php endforeach; ?></div></div>
<?php endforeach; ?></div>
<div class="modal fade" id="opModal" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="post" action="<?= url('crm/op_guardar') ?>"><?= csrf_field() ?><input type="hidden" name="id" id="op_id">
 <div class="modal-header"><h5 class="modal-title" id="opT">Nueva oportunidad</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
 <div class="modal-body row g-2">
  <div class="col-12"><label class="form-label small">Nombre / Empresa</label><input name="nombre" id="op_nombre" class="form-control" required></div>
  <div class="col-6"><label class="form-label small">Teléfono</label><input name="telefono" id="op_telefono" class="form-control"></div>
  <div class="col-6"><label class="form-label small">Correo</label><input name="email" id="op_email" type="email" class="form-control"></div>
  <div class="col-12"><label class="form-label small">Interés</label><input name="interes" id="op_interes" class="form-control" placeholder="Ej: Lavado de flota de 8 camionetas"></div>
  <div class="col-6"><label class="form-label small">Origen</label><select name="origen" id="op_origen" class="form-select"><option value="">—</option><?php foreach (['Referido','Facebook','Instagram','WhatsApp','Pasó por el local','Empresa / Flota','Otro'] as $x): ?><option><?= $x ?></option><?php endforeach; ?></select></div>
  <div class="col-6"><label class="form-label small">Valor estimado</label><input name="valor_est" id="op_valor" type="number" step="0.01" min="0" class="form-control" value="0"></div>
  <div class="col-12"><label class="form-label small">Notas</label><textarea name="notas" id="op_notas" class="form-control" rows="2"></textarea></div></div>
 <div class="modal-footer"><button class="btn btn-grad">Guardar</button></div></form></div></div>
<?php ob_start(); ?>
<script>
function opForm(o){ o=o||{}; document.getElementById('opT').textContent=o.id?'Editar oportunidad':'Nueva oportunidad';
 for (const [k,f] of [['id','op_id'],['nombre','op_nombre'],['telefono','op_telefono'],['email','op_email'],['interes','op_interes'],['origen','op_origen'],['valor_est','op_valor'],['notas','op_notas']]) document.getElementById(f).value = o[k] ?? (k==='valor_est'?0:''); }
const csrf = <?= json_encode(csrf_token()) ?>; let drag = null;
document.querySelectorAll('.kb-card').forEach(c => { c.addEventListener('dragstart', () => { drag = c; c.classList.add('drag'); }); c.addEventListener('dragend', () => c.classList.remove('drag')); });
document.querySelectorAll('.kb-col').forEach(col => {
  col.addEventListener('dragover', e => { e.preventDefault(); col.classList.add('over'); }); col.addEventListener('dragleave', () => col.classList.remove('over'));
  col.addEventListener('drop', async e => { e.preventDefault(); col.classList.remove('over'); if (!drag) return;
    col.querySelector('.kb-body').prepend(drag);
    const fd = new FormData(); fd.append('_csrf', csrf); fd.append('id', drag.dataset.id); fd.append('etapa', col.dataset.etapa); fd.append('ajax', 1);
    try { await fetch(<?= json_encode(url('crm/op_mover')) ?>, {method:'POST', body:fd}); location.reload(); } catch (err) { location.reload(); } });
});
</script>
<?php $scripts = ob_get_clean(); ?>
