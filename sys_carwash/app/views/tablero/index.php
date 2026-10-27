<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <div><h1 class="page-title">Tablero en vivo</h1><p class="page-sub">Arrastra cada vehículo entre columnas. Se actualiza solo cada 30 segundos.</p></div>
  <div class="d-flex align-items-center gap-2"><span class="small text-muted"><i class="bi bi-arrow-repeat"></i> <span id="tbHora"><?= e($data['ahora']) ?></span></span>
  <a href="<?= url('ordenes/nueva') ?>" class="btn btn-grad"><i class="bi bi-plus-lg me-1"></i> Nueva orden</a></div>
</div>
<div class="row g-3 mb-3" id="tbKpis"></div>
<div class="board" id="board">
<?php $cc = ['pendiente' => '#f59e0b', 'en_proceso' => '#0ea5e9', 'terminado' => '#14b8a6', 'entregado' => '#94a3b8']; foreach ($cols as $k => $l): ?>
  <div class="bd-col" data-estado="<?= $k ?>"><div class="bd-h" style="--c:<?= $cc[$k] ?>"><b><?= $l ?></b><span class="bd-n" id="n_<?= $k ?>">0</span></div><div class="bd-body" id="col_<?= $k ?>"></div></div>
<?php endforeach; ?></div>
<?php ob_start(); ?>
<script>
const INIT = <?= json_encode($data, JSON_UNESCAPED_UNICODE) ?>, EMPS = <?= json_encode($emps, JSON_UNESCAPED_UNICODE) ?>, CSRF = <?= json_encode(csrf_token()) ?>;
const URLS = {datos: <?= json_encode(url('tablero/datos_json')) ?>, mover: <?= json_encode(url('tablero/mover')) ?>, asignar: <?= json_encode(url('tablero/asignar')) ?>};
const NEXT = {pendiente:'en_proceso', en_proceso:'terminado', terminado:'entregado'}, NEXTL = {pendiente:'Iniciar', en_proceso:'Terminar', terminado:'Entregar'};
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
let dragging = false, busy = false;
const fmtMin = m => m >= 60 ? Math.floor(m/60) + ' h ' + (m%60) + ' min' : m + ' min';
function card(o) {
  const pct = Math.min(100, Math.round(o.mins * 100 / o.dur));
  const emp = '<select class="form-select form-select-sm bd-emp" data-id="'+o.id+'"><option value="">Sin asignar</option>' + EMPS.map(e => '<option value="'+e.id+'"'+(e.id==o.empleado_id?' selected':'')+'>'+esc(e.nombre)+'</option>').join('') + '</select>';
  const tiempo = o.nivel === 'done' ? '<span class="text-muted">'+fmtMin(o.mins)+' en total</span>' : '<span class="bd-t '+o.nivel+'"><i class="bi bi-stopwatch"></i> '+fmtMin(o.mins)+' / '+fmtMin(o.dur)+'</span>';
  return '<div class="bd-card lv-'+o.nivel+'" draggable="true" data-id="'+o.id+'">' +
    '<div class="d-flex justify-content-between align-items-start"><div><div class="bd-placa">'+esc(o.placa || 'SIN PLACA')+'</div><div class="small text-muted">'+esc([o.marca,o.modelo,o.color].filter(Boolean).join(' '))+'</div></div><a href="'+o.link+'" class="small fw-bold text-decoration-none">'+esc(o.codigo)+'</a></div>' +
    '<div class="small mt-1">'+esc(o.servicios || 'Sin servicios')+'</div>' +
    '<div class="small text-muted mb-1"><i class="bi bi-person"></i> '+esc(o.cliente || 'Cliente general')+'</div>' +
    (o.nivel === 'done' ? '' : '<div class="progress my-1" style="height:6px"><div class="progress-bar" style="width:'+pct+'%"></div></div>') +
    '<div class="d-flex justify-content-between align-items-center small mb-2">'+tiempo+(o.saldo > 0.004 && o.estado !== 'pendiente' ? '<span class="badge badge-soft-warning">Debe '+esc(o.saldo_txt)+'</span>' : '<b>'+esc(o.total_txt)+'</b>')+'</div>' +
    emp + (NEXT[o.estado] ? '<button class="btn btn-sm btn-grad w-100 mt-2" data-next="'+o.id+'" data-to="'+NEXT[o.estado]+'">'+NEXTL[o.estado]+' <i class="bi bi-arrow-right"></i></button>' : '') + '</div>';
}
function render(d) {
  for (const k in d.cols) { document.getElementById('col_'+k).innerHTML = d.cols[k].map(card).join('') || '<div class="bd-empty">Sin vehículos</div>'; document.getElementById('n_'+k).textContent = d.cols[k].length; }
  document.getElementById('tbHora').textContent = d.ahora;
  const kp = [['En cola', d.cols.pendiente.length, 'bi-hourglass-split', 'amber'], ['En proceso', d.cols.en_proceso.length, 'bi-droplet-half', 'blue'], ['Listos para entregar', d.cols.terminado.length, 'bi-check2-circle', 'teal'], ['Con retraso', d.atrasadas, 'bi-exclamation-triangle', 'rose'], ['Tiempo promedio hoy', d.prom == null ? '—' : fmtMin(d.prom), 'bi-stopwatch', 'violet']];
  document.getElementById('tbKpis').innerHTML = kp.map(k => '<div class="col-6 col-lg"><div class="mini-stat"><div class="ico bg-'+k[3]+' t-'+k[3]+'"><i class="bi '+k[2]+'"></i></div><div><div class="l">'+k[0]+'</div><div class="fw-bold fs-5 text-dark lh-1">'+k[1]+'</div></div></div></div>').join('');
  bind();
}
async function post(url, data) { const fd = new FormData(); fd.append('_csrf', CSRF); for (const k in data) fd.append(k, data[k]); const r = await fetch(url, {method:'POST', body:fd}); return r.json().catch(() => ({ok:false})); }
async function refresh() { if (dragging || busy) return; try { render(await (await fetch(URLS.datos)).json()); } catch (e) {} }
async function mover(id, estado) { busy = true; const r = await post(URLS.mover, {id, estado}); busy = false; if (!r.ok) alert(r.msg || 'No se pudo mover.'); refresh(); }
function bind() {
  document.querySelectorAll('[data-next]').forEach(b => b.onclick = () => mover(b.dataset.next, b.dataset.to));
  document.querySelectorAll('.bd-emp').forEach(s => s.onchange = async () => { busy = true; await post(URLS.asignar, {id: s.dataset.id, empleado_id: s.value}); busy = false; });
  document.querySelectorAll('.bd-card').forEach(c => { c.ondragstart = e => { dragging = true; c.classList.add('drag'); e.dataTransfer.setData('text/plain', c.dataset.id); }; c.ondragend = () => { dragging = false; c.classList.remove('drag'); }; });
}
document.querySelectorAll('.bd-col').forEach(col => {
  col.addEventListener('dragover', e => { e.preventDefault(); col.classList.add('over'); });
  col.addEventListener('dragleave', () => col.classList.remove('over'));
  col.addEventListener('drop', e => { e.preventDefault(); col.classList.remove('over'); const id = e.dataTransfer.getData('text/plain'); dragging = false; if (id) mover(id, col.dataset.estado); });
});
render(INIT); setInterval(refresh, 30000);
</script>
<?php $scripts = ob_get_clean(); ?>
