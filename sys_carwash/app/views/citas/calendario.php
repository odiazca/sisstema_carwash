<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2"><div><h1 class="page-title">Calendario de citas</h1><p class="page-sub">Vista mensual de reservas</p></div>
<div class="d-flex gap-2"><a href="<?= url('citas') ?>" class="btn btn-outline-primary"><i class="bi bi-list-ul"></i> Lista</a><a href="<?= url('citas/crear') ?>" class="btn btn-grad"><i class="bi bi-plus-lg"></i> Nueva cita</a></div></div>
<div class="cw-card"><div class="card-h"><button class="btn btn-light btn-sm" id="prev"><i class="bi bi-chevron-left"></i></button><h5 class="m-0 fw-bold text-capitalize" id="ttl"></h5><button class="btn btn-light btn-sm" id="next"><i class="bi bi-chevron-right"></i></button></div>
<div class="card-b"><div class="cal-head"><?php foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $d): ?><div><?= $d ?></div><?php endforeach; ?></div><div class="cal-grid" id="grid"></div></div></div>
<style>.cal-head,.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:6px}.cal-head div{text-align:center;font-weight:700;color:#64748b;font-size:.8rem;padding:6px}
.cal-cell{min-height:104px;border:1px solid var(--cw-border);border-radius:.8rem;padding:6px;background:#fff;overflow:hidden}.cal-cell.out{background:#f8fafc;opacity:.55}.cal-cell.today{border-color:var(--cw-primary);box-shadow:0 0 0 2px rgba(14,165,233,.2)}
.cal-cell .n{font-weight:700;font-size:.8rem;color:#475569}.ev{display:block;font-size:.7rem;padding:2px 6px;border-radius:6px;margin-top:3px;text-decoration:none;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ev.programada{background:#fef3c7;color:#92400e}.ev.confirmada{background:#e0f2fe;color:#075985}.ev.atendida{background:#dcfce7;color:#166534}.ev.cancelada{background:#ffe4e6;color:#9f1239;text-decoration:line-through}
@media(max-width:768px){.cal-cell{min-height:70px}.ev{font-size:.6rem}}</style>
<?php ob_start(); ?><script>
let cur=new Date(); cur.setDate(1);
const pad=n=>String(n).padStart(2,'0'), BASE='<?= url('citas/editar/') ?>';
async function draw(){
  const y=cur.getFullYear(), m=cur.getMonth(), key=y+'-'+pad(m+1);
  document.getElementById('ttl').textContent=cur.toLocaleDateString('es',{month:'long',year:'numeric'});
  let ev=[]; try{ ev=await (await fetch('<?= url('citas/eventos') ?>?mes='+key)).json(); }catch(e){}
  const by={}; ev.forEach(e=>(by[e.d]=by[e.d]||[]).push(e));
  const first=(new Date(y,m,1).getDay()+6)%7, days=new Date(y,m+1,0).getDate(), g=document.getElementById('grid'); g.innerHTML='';
  const todayK=new Date().toISOString().slice(0,10);
  for(let i=0;i<first;i++) g.insertAdjacentHTML('beforeend','<div class="cal-cell out"></div>');
  for(let d=1;d<=days;d++){ const k=key+'-'+pad(d);
    g.insertAdjacentHTML('beforeend',`<div class="cal-cell ${k===todayK?'today':''}"><div class="n">${d}</div>${(by[k]||[]).map(e=>`<a class="ev ${e.estado}" href="${BASE}${e.id}" title="${e.cliente} · ${e.servicio||''}">${e.h} ${e.cliente}</a>`).join('')}</div>`); }
}
document.getElementById('prev').onclick=()=>{cur.setMonth(cur.getMonth()-1);draw();};
document.getElementById('next').onclick=()=>{cur.setMonth(cur.getMonth()+1);draw();};
draw();
</script><?php $scripts = ob_get_clean(); ?>
