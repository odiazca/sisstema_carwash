<div class="mb-3"><h1 class="page-title">Nueva cotización</h1><p class="page-sub">Arma el presupuesto; el cliente puede aceptarlo y lo conviertes en orden</p></div>
<form method="post" action="<?= url('cotizaciones/guardar') ?>" id="frm"><?= csrf_field() ?>
<div class="row g-3"><div class="col-xl-7">
  <div class="cw-card mb-3"><div class="card-h"><h6><i class="bi bi-person-vcard text-primary me-2"></i>Cliente</h6></div><div class="card-b"><div class="row g-3">
    <div class="col-md-6"><label class="form-label">Cliente registrado</label><select class="form-select" name="cliente_id" id="cliente"><option value="">— Cliente nuevo / sin registrar —</option><?php foreach ($clientes as $c): ?><option value="<?= $c['id'] ?>" data-t="<?= e($c['telefono'] ?? '') ?>"><?= e($c['nombre']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-6"><label class="form-label">Nombre (si no está registrado)</label><input class="form-control" name="nombre" maxlength="150"></div>
    <div class="col-md-4"><label class="form-label">Teléfono</label><input class="form-control" name="telefono" id="tel" maxlength="30"></div>
    <div class="col-md-4"><label class="form-label">Placa</label><input class="form-control text-uppercase" name="placa" maxlength="20"></div>
    <div class="col-md-4"><label class="form-label">Tipo de vehículo</label><select class="form-select" name="tipo_vehiculo_id" id="tipoV"><option value="">—</option><?php foreach ($tipos as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['nombre']) ?></option><?php endforeach; ?></select></div>
  </div></div></div>
  <div class="cw-card"><div class="card-h"><h6><i class="bi bi-droplet-half text-primary me-2"></i>Servicios y productos</h6><div class="input-ico" style="width:220px"><i class="bi bi-search"></i><input class="form-control form-control-sm" id="buscar" placeholder="Buscar…"></div></div><div class="card-b">
    <div class="small fw-bold text-muted mb-2">SERVICIOS</div><div class="row g-2 mb-3"><?php foreach ($servicios as $s): ?><div class="col-md-4 col-6 it" data-n="<?= e(strtolower($s['nombre'])) ?>"><div class="pos-item" data-tipo="servicio" data-id="<?= $s['id'] ?>"><div class="fw-bold small"><?= e($s['nombre']) ?></div><div class="text-primary fw-bold price-lbl"><?= money($s['precio']) ?></div></div></div><?php endforeach; ?></div>
    <?php if ($productos): ?><div class="small fw-bold text-muted mb-2">PRODUCTOS</div><div class="row g-2"><?php foreach ($productos as $p): ?><div class="col-md-4 col-6 it" data-n="<?= e(strtolower($p['nombre'])) ?>"><div class="pos-item" data-tipo="producto" data-id="<?= $p['id'] ?>"><div class="fw-bold small"><?= e($p['nombre']) ?></div><div class="text-primary fw-bold"><?= money($p['precio_venta']) ?></div></div></div><?php endforeach; ?></div><?php endif; ?>
  </div></div></div>
<div class="col-xl-5"><div class="cw-card position-sticky" style="top:84px"><div class="card-h"><h6><i class="bi bi-receipt text-primary me-2"></i>Detalle</h6></div><div class="card-b">
  <div id="lineas" class="mb-3"></div>
  <div class="d-flex justify-content-between"><span class="text-muted">Subtotal</span><b id="tSub"></b></div>
  <div class="d-flex justify-content-between align-items-center my-2"><span class="text-muted">Descuento</span><input type="number" step="0.01" min="0" name="descuento" id="desc" value="0" class="form-control form-control-sm text-end" style="width:110px"></div>
  <div class="d-flex justify-content-between"><span class="text-muted"><?= e(empresa()['impuesto_nombre']) ?> <?= empresa()['precios_incluyen_impuesto'] ? '(incluido)' : '' ?></span><b id="tImp"></b></div>
  <hr><div class="d-flex justify-content-between align-items-center"><span class="fs-5 fw-bold">TOTAL</span><span class="fs-3 fw-bold text-primary" id="tTot"></span></div>
  <div class="row g-2 mt-2"><div class="col-5"><label class="form-label small mb-0">Validez (días)</label><input type="number" min="1" max="365" name="validez" value="7" class="form-control form-control-sm"></div>
  <div class="col-7"><label class="form-label small mb-0">Observaciones</label><input name="observaciones" maxlength="500" class="form-control form-control-sm"></div></div>
  <button class="btn btn-grad w-100 py-2 mt-3"><i class="bi bi-check2-circle me-1"></i> Crear cotización</button></div></div></div></div></form>
<?php ob_start(); ?>
<script>
const CFG = <?= json_encode(['dec' => (int)empresa()['decimales'], 'sd' => empresa()['sep_decimal'], 'sm' => empresa()['sep_miles'], 'sim' => empresa()['moneda_simbolo'], 'pos' => empresa()['simbolo_posicion'], 'rate' => (float)empresa()['impuesto_porcentaje'] / 100, 'inc' => (bool)empresa()['precios_incluyen_impuesto']]) ?>;
const SERV=<?= json_encode(array_column($servicios, null, 'id')) ?>, PREC=<?= json_encode($precios ?: new stdClass) ?>, PROD=<?= json_encode(array_column($productos, null, 'id')) ?>;
const $=s=>document.querySelector(s);
const money=n=>{ let [a,b]=(+n).toFixed(CFG.dec).split('.'); a=a.replace(/\B(?=(\d{3})+(?!\d))/g,CFG.sm); const v=b?a+CFG.sd+b:a; return CFG.pos==='antes'?CFG.sim+' '+v:v+' '+CFG.sim; };
let lines=[];
const pServ=id=>{ const t=$('#tipoV').value; return (t&&PREC[id]&&PREC[id][t]!=null)?+PREC[id][t]:+SERV[id].precio; };
function render(){ const box=$('#lineas'); box.innerHTML=lines.length?'':'<div class="text-center text-muted py-4"><i class="bi bi-file-earmark-text fs-1 d-block"></i>Toca un servicio para agregarlo</div>'; let sub=0;
  lines.forEach((l,i)=>{ const p=l.tipo==='servicio'?pServ(l.id):+PROD[l.id].precio_venta, n=l.tipo==='servicio'?SERV[l.id].nombre:PROD[l.id].nombre; sub+=p*l.cant;
    box.insertAdjacentHTML('beforeend',`<div class="d-flex align-items-center gap-2 border-bottom py-2"><div class="flex-grow-1"><div class="fw-semibold small">${n}</div><div class="small text-muted">${money(p)} c/u</div></div>
    <input type="number" min="1" step="1" value="${l.cant}" class="form-control form-control-sm text-center" style="width:64px" data-i="${i}"><b style="min-width:80px;text-align:right">${money(p*l.cant)}</b>
    <button type="button" class="btn btn-sm btn-light text-danger" data-del="${i}"><i class="bi bi-x-lg"></i></button><input type="hidden" name="items[${i}][tipo]" value="${l.tipo}"><input type="hidden" name="items[${i}][id]" value="${l.id}"><input type="hidden" name="items[${i}][cant]" value="${l.cant}"></div>`); });
  const d=Math.min(sub,Math.max(0,+$('#desc').value||0)), base=sub-d; let imp,tot; if(CFG.inc){tot=base;imp=base-base/(1+CFG.rate);}else{imp=base*CFG.rate;tot=base+imp;}
  $('#tSub').textContent=money(sub); $('#tImp').textContent=money(imp); $('#tTot').textContent=money(tot); }
document.querySelectorAll('.pos-item').forEach(el=>el.addEventListener('click',()=>{ const f=lines.find(l=>l.tipo===el.dataset.tipo&&l.id==el.dataset.id); if(f) f.cant++; else lines.push({tipo:el.dataset.tipo,id:el.dataset.id,cant:1}); render(); }));
$('#lineas').addEventListener('change',e=>{ if(e.target.dataset.i!==undefined){ lines[e.target.dataset.i].cant=Math.max(1,+e.target.value||1); render(); } });
$('#lineas').addEventListener('click',e=>{ const b=e.target.closest('[data-del]'); if(b){ lines.splice(b.dataset.del,1); render(); } });
$('#desc').addEventListener('input',render); $('#tipoV').addEventListener('change',render);
$('#cliente').addEventListener('change',e=>{ const o=e.target.selectedOptions[0]; if(o&&o.dataset.t) $('#tel').value=o.dataset.t; });
$('#buscar').addEventListener('input',e=>{ const q=e.target.value.toLowerCase(); document.querySelectorAll('.it').forEach(x=>x.style.display=x.dataset.n.includes(q)?'':'none'); });
$('#frm').addEventListener('submit',e=>{ if(!lines.length){ e.preventDefault(); alert('Agrega al menos un ítem.'); } });
render();
</script>
<?php $scripts = ob_get_clean(); ?>
