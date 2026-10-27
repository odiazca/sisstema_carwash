<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <div><h1 class="page-title">Venta rápida</h1><p class="page-sub">Mostrador: vende productos sin abrir una orden de servicio</p></div>
  <div class="d-flex gap-2"><span class="badge bg-primary-subtle text-primary-emphasis fs-6 py-2 px-3"><i class="bi bi-receipt me-1"></i><?= (int)$tot['n'] ?> <?= (int)$tot['n'] === 1 ? 'venta' : 'ventas' ?> hoy</span>
  <span class="badge bg-success-subtle text-success-emphasis fs-6 py-2 px-3"><?= money($tot['t']) ?></span></div>
</div>
<form method="post" action="<?= url('ventas/guardar') ?>" id="frm"><?= csrf_field() ?>
<div class="row g-3">
  <div class="col-xl-7">
    <div class="cw-card mb-3"><div class="card-h"><h6><i class="bi bi-box-seam text-primary me-2"></i>Productos</h6>
      <div class="input-ico" style="width:280px"><i class="bi bi-upc-scan"></i><input class="form-control form-control-sm" id="buscar" placeholder="Buscar o escanear código…" autofocus autocomplete="off"></div></div>
      <div class="card-b"><?php if (!$productos): ?><div class="text-muted text-center py-4">No hay productos a la venta. Créalos en Catálogo → Productos.</div><?php endif; ?>
      <div class="row g-2" id="lista"><?php foreach ($productos as $p): ?>
        <div class="col-md-4 col-6 it" data-n="<?= e(strtolower($p['nombre'] . ' ' . $p['codigo'])) ?>" data-c="<?= e(strtolower((string)$p['codigo'])) ?>">
          <div class="pos-item <?= $p['stock'] <= 0 ? 'opacity-50' : '' ?>" data-id="<?= $p['id'] ?>"><div class="fw-bold small"><?= e($p['nombre']) ?></div>
          <div class="d-flex justify-content-between"><span class="text-primary fw-bold"><?= money($p['precio_venta']) ?></span><span class="small <?= $p['stock'] <= 0 ? 'text-danger' : 'text-muted' ?>">Stock <?= num($p['stock'], 0) ?></span></div></div></div>
      <?php endforeach; ?></div></div></div>
    <div class="cw-card"><div class="card-h"><h6><i class="bi bi-clock-history text-primary me-2"></i>Ventas de hoy</h6></div>
      <div class="table-responsive"><table class="table table-hover align-middle mb-0"><tbody>
      <?php foreach ($hoy as $v): ?><tr><td><a class="fw-bold text-decoration-none" href="<?= url('ordenes/ver/' . $v['id']) ?>"><?= e($v['codigo']) ?></a></td><td class="text-muted small"><?= fdate($v['fecha_ingreso'], true) ?></td>
        <td><?= e($v['cliente'] ?? 'Cliente general') ?></td><td><?= estado_badge($v['estado']) ?></td><td class="text-end fw-bold"><?= money($v['total']) ?></td>
        <td class="text-end"><a class="btn btn-icon btn-light" target="_blank" href="<?= url('ordenes/ticket/' . $v['id']) ?>"><i class="bi bi-printer"></i></a></td></tr>
      <?php endforeach; if (!$hoy): ?><tr><td class="text-center text-muted py-4">Aún no hay ventas hoy.</td></tr><?php endif; ?></tbody></table></div></div>
  </div>
  <div class="col-xl-5"><div class="cw-card position-sticky" style="top:84px">
    <div class="card-h"><h6><i class="bi bi-basket3 text-primary me-2"></i>Carrito</h6></div><div class="card-b">
      <div id="lineas" class="mb-3"></div>
      <select class="form-select form-select-sm mb-2" name="cliente_id"><option value="">Cliente general</option><?php foreach ($clientes as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['nombre']) ?></option><?php endforeach; ?></select>
      <div class="d-flex justify-content-between"><span class="text-muted">Subtotal</span><b id="tSub"></b></div>
      <div class="d-flex justify-content-between align-items-center my-2"><span class="text-muted">Descuento</span><input type="number" step="0.01" min="0" name="descuento" id="desc" value="0" class="form-control form-control-sm text-end" style="width:110px"></div>
      <div class="d-flex justify-content-between"><span class="text-muted"><?= e(empresa()['impuesto_nombre']) ?> <?= empresa()['precios_incluyen_impuesto'] ? '(incluido)' : '' ?></span><b id="tImp"></b></div>
      <hr><div class="d-flex justify-content-between align-items-center"><span class="fs-5 fw-bold">TOTAL</span><span class="fs-3 fw-bold text-primary" id="tTot"></span></div>
      <div class="p-3 rounded-3 mt-3" style="background:#f2f9ff"><div class="row g-2">
        <div class="col-6"><label class="form-label small mb-0">Método de pago</label><select class="form-select form-select-sm" name="metodo_pago" id="metodo"><?php foreach (OrdenesController::PAGOS as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
        <div class="col-6" id="recBox"><label class="form-label small mb-0">Recibido</label><input type="number" step="0.01" min="0" name="recibido" id="rec" class="form-control form-control-sm"></div>
        <div class="col-6" id="refBox" style="display:none"><label class="form-label small mb-0">N° operación</label><input name="referencia" class="form-control form-control-sm"></div>
      </div><div class="fw-bold mt-2 text-success" id="vuelto"></div></div>
      <button class="btn btn-grad w-100 py-2 mt-3 fs-6"><i class="bi bi-check2-circle me-1"></i> Cobrar venta</button>
    </div></div></div>
</div></form>
<?php ob_start(); ?>
<script>
const CFG = <?= json_encode(['dec' => (int)empresa()['decimales'], 'sd' => empresa()['sep_decimal'], 'sm' => empresa()['sep_miles'], 'sim' => empresa()['moneda_simbolo'], 'pos' => empresa()['simbolo_posicion'], 'rate' => (float)empresa()['impuesto_porcentaje'] / 100, 'inc' => (bool)empresa()['precios_incluyen_impuesto']]) ?>;
const PROD = <?= json_encode(array_column($productos, null, 'id')) ?>;
const $ = s => document.querySelector(s);
const money = n => { let [a,b]=(+n).toFixed(CFG.dec).split('.'); a=a.replace(/\B(?=(\d{3})+(?!\d))/g,CFG.sm); const v=b?a+CFG.sd+b:a; return CFG.pos==='antes'?CFG.sim+' '+v:v+' '+CFG.sim; };
let lines=[];
function add(id){ const p=PROD[id]; if(!p) return; const f=lines.find(l=>l.id==id); const cur=f?f.cant:0; if(cur+1>+p.stock){ alert('Stock insuficiente de '+p.nombre); return; } if(f) f.cant++; else lines.push({id,cant:1}); render(); }
function render(){
  const box=$('#lineas'); box.innerHTML=lines.length?'':'<div class="text-center text-muted py-4"><i class="bi bi-cart3 fs-1 d-block"></i>Toca un producto o escanea su código</div>';
  let sub=0;
  lines.forEach((l,i)=>{ const p=PROD[l.id]; sub+=p.precio_venta*l.cant;
    box.insertAdjacentHTML('beforeend',`<div class="d-flex align-items-center gap-2 border-bottom py-2"><div class="flex-grow-1"><div class="fw-semibold small">${p.nombre}</div><div class="small text-muted">${money(p.precio_venta)} c/u</div></div>
      <input type="number" min="1" max="${p.stock}" step="1" value="${l.cant}" class="form-control form-control-sm text-center" style="width:64px" data-i="${i}"><b style="min-width:80px;text-align:right">${money(p.precio_venta*l.cant)}</b>
      <button type="button" class="btn btn-sm btn-light text-danger" data-del="${i}"><i class="bi bi-x-lg"></i></button>
      <input type="hidden" name="items[${i}][id]" value="${l.id}"><input type="hidden" name="items[${i}][cant]" value="${l.cant}"></div>`); });
  const d=Math.min(sub,Math.max(0,+$('#desc').value||0)), base=sub-d; let imp,tot;
  if(CFG.inc){tot=base;imp=base-base/(1+CFG.rate);}else{imp=base*CFG.rate;tot=base+imp;}
  $('#tSub').textContent=money(sub); $('#tImp').textContent=money(imp); $('#tTot').textContent=money(tot); $('#tTot').dataset.v=tot; vuelto();
}
function vuelto(){ const t=+($('#tTot').dataset.v||0), r=+$('#rec').value||0; $('#vuelto').textContent = $('#metodo').value==='efectivo' && r>t ? 'Vuelto: '+money(r-t) : ''; }
document.querySelectorAll('.pos-item').forEach(el=>el.addEventListener('click',()=>add(el.dataset.id)));
$('#lineas').addEventListener('change',e=>{ if(e.target.dataset.i!==undefined){ const l=lines[e.target.dataset.i], p=PROD[l.id]; l.cant=Math.min(+p.stock,Math.max(1,+e.target.value||1)); render(); } });
$('#lineas').addEventListener('click',e=>{ const b=e.target.closest('[data-del]'); if(b){ lines.splice(b.dataset.del,1); render(); } });
$('#desc').addEventListener('input',render); $('#rec').addEventListener('input',vuelto);
$('#metodo').addEventListener('change',()=>{ const ef=$('#metodo').value==='efectivo'; $('#recBox').style.display=ef?'':'none'; $('#refBox').style.display=ef?'none':''; vuelto(); });
$('#buscar').addEventListener('input',e=>{ const q=e.target.value.toLowerCase(); document.querySelectorAll('.it').forEach(x=>x.style.display=x.dataset.n.includes(q)?'':'none'); });
$('#buscar').addEventListener('keydown',e=>{ if(e.key==='Enter'){ e.preventDefault(); const q=e.target.value.trim().toLowerCase(); if(!q) return;
  const ex=[...document.querySelectorAll('.it')].find(x=>x.dataset.c===q) || (()=>{const v=[...document.querySelectorAll('.it')].filter(x=>x.style.display!=='none'); return v.length===1?v[0]:null;})();
  if(ex){ add(ex.querySelector('.pos-item').dataset.id); e.target.value=''; e.target.dispatchEvent(new Event('input')); } } });
$('#frm').addEventListener('submit',e=>{ if(!lines.length){ e.preventDefault(); alert('Agrega al menos un producto.'); } });
render();
</script>
<?php $scripts = ob_get_clean(); ?>
