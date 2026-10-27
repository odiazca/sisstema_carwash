<div class="mb-3"><h1 class="page-title">Nueva orden de servicio</h1><p class="page-sub">Registra el vehículo, elige servicios y cobra</p></div>
<form method="post" action="<?= url('ordenes/guardar') ?>" id="frm">
<?= csrf_field() ?>
<?php if ($cotizacion): ?><input type="hidden" name="cotizacion_id" value="<?= (int)$cotizacion['id'] ?>"><div class="alert alert-info py-2"><i class="bi bi-file-earmark-text me-1"></i> Orden generada desde una cotización. Revisa los ítems y registra la orden.</div><?php endif; ?>
<?php if ($recepcion): ?><input type="hidden" name="recepcion_id" value="<?= (int)$recepcion['id'] ?>"><div class="alert alert-info py-2"><i class="bi bi-clipboard2-check me-1"></i> Orden generada desde la recepción #<?= (int)$recepcion['id'] ?> (<?= e($recepcion['placa']) ?>)</div><?php endif; ?>
<?php if ($cita): ?><input type="hidden" name="cita_id" value="<?= (int)$cita['id'] ?>"><div class="alert alert-info py-2"><i class="bi bi-calendar-check me-1"></i> Orden generada desde la cita #<?= (int)$cita['id'] ?></div><?php endif; ?>
<div class="row g-3">
  <div class="col-xl-7">
    <div class="cw-card mb-3"><div class="card-h"><h6><i class="bi bi-person-vcard text-primary me-2"></i>Cliente y vehículo</h6></div><div class="card-b"><div class="row g-3">
      <div class="col-md-6"><label class="form-label">Cliente registrado</label>
        <select class="form-select" name="cliente_id" id="cliente"><option value="">— Cliente nuevo / general —</option>
        <?php foreach ($clientes as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['nombre']) ?><?= $c['nro_doc'] ? ' · ' . e($c['nro_doc']) : '' ?></option><?php endforeach; ?></select></div>
      <div class="col-md-6"><label class="form-label">Vehículo registrado</label>
        <select class="form-select" name="vehiculo_id" id="vehiculo"><option value="">— Nuevo vehículo —</option></select></div>
      <div id="nuevoBox" class="col-12"><div class="row g-3 p-3 rounded-3" style="background:#f2f9ff">
        <div class="col-md-4"><label class="form-label">Placa *</label><input class="form-control text-uppercase" name="nueva_placa" id="placa" placeholder="ABC-123"></div>
        <div class="col-md-4"><label class="form-label">Marca / modelo</label><input class="form-control" name="nueva_marca"></div>
        <div class="col-md-4"><label class="form-label">Tipo de vehículo</label><select class="form-select" name="tipo_vehiculo_id" id="tipoV"><option value="">—</option>
          <?php foreach ($tipos as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['nombre']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6 cliNuevo"><label class="form-label">Nombre del cliente (opcional)</label><input class="form-control" name="nuevo_cliente"></div>
        <div class="col-md-6 cliNuevo"><label class="form-label">Teléfono</label><input class="form-control" name="nuevo_telefono"></div>
      </div></div>
      <div class="col-12" id="pkBox" style="display:none"><label class="form-label"><i class="bi bi-gift text-primary"></i> Usar paquete prepagado del cliente</label>
        <select class="form-select" name="cliente_paquete_id" id="pkSel"><option value="">— No usar paquete —</option></select><div class="form-text">Debes agregar el servicio incluido; 1 unidad se descuenta del paquete.</div></div>
      <div class="col-md-6"><label class="form-label">Lavador asignado</label><select class="form-select" name="empleado_id"><option value="">— Sin asignar —</option>
        <?php foreach ($empleados as $e): ?><option value="<?= $e['id'] ?>"><?= e($e['nombre']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-6"><label class="form-label">Observaciones</label><input class="form-control" name="observaciones" placeholder="Rayones, objetos de valor…"></div>
    </div></div></div>

    <div class="cw-card"><div class="card-h"><h6><i class="bi bi-droplet-half text-primary me-2"></i>Servicios y productos</h6>
      <div class="input-ico" style="width:220px"><i class="bi bi-search"></i><input class="form-control form-control-sm" id="buscar" placeholder="Buscar…"></div></div>
      <div class="card-b">
        <div class="small fw-bold text-muted mb-2">SERVICIOS</div>
        <div class="row g-2 mb-3" id="listaServ"><?php foreach ($servicios as $s): ?>
          <div class="col-md-4 col-6 it" data-n="<?= e(strtolower($s['nombre'])) ?>"><div class="pos-item" data-tipo="servicio" data-id="<?= $s['id'] ?>"><div class="fw-bold small"><?= e($s['nombre']) ?></div><div class="text-primary fw-bold price-lbl" data-base="<?= $s['precio'] ?>"><?= money($s['precio']) ?></div></div></div>
        <?php endforeach; ?></div>
        <?php if ($productos): ?><div class="small fw-bold text-muted mb-2">PRODUCTOS</div><div class="row g-2" id="listaProd"><?php foreach ($productos as $p): ?>
          <div class="col-md-4 col-6 it" data-n="<?= e(strtolower($p['nombre'])) ?>"><div class="pos-item" data-tipo="producto" data-id="<?= $p['id'] ?>"><div class="fw-bold small"><?= e($p['nombre']) ?></div><div class="d-flex justify-content-between"><span class="text-primary fw-bold"><?= money($p['precio_venta']) ?></span><span class="small text-muted">Stock <?= num($p['stock'], 0) ?></span></div></div></div>
        <?php endforeach; ?></div><?php endif; ?>
      </div></div>
  </div>

  <div class="col-xl-5"><div class="cw-card position-sticky" style="top:84px">
    <div class="card-h"><h6><i class="bi bi-receipt text-primary me-2"></i>Detalle de la orden</h6></div>
    <div class="card-b">
      <div id="lineas" class="mb-3"><div class="text-center text-muted py-4" id="vacio"><i class="bi bi-cart3 fs-1 d-block"></i>Toca un servicio para agregarlo</div></div>
      <div class="d-flex justify-content-between"><span class="text-muted">Subtotal</span><b id="tSub"></b></div>
      <div class="d-flex justify-content-between align-items-center my-2"><span class="text-muted">Descuento</span><input type="number" step="0.01" min="0" name="descuento" id="desc" value="0" class="form-control form-control-sm text-end" style="width:110px"></div>
      <div class="input-group input-group-sm mb-2"><span class="input-group-text"><i class="bi bi-ticket-perforated"></i></span><input type="text" name="cupon" id="cupon" class="form-control text-uppercase" placeholder="Cupón de descuento" maxlength="30" autocomplete="off"><button type="button" class="btn btn-outline-primary" id="btnCupon">Aplicar</button></div>
      <div class="small mb-2" id="cuponMsg"></div>
      <div class="d-flex justify-content-between"><span class="text-muted"><?= e(empresa()['impuesto_nombre']) ?> <?= empresa()['precios_incluyen_impuesto'] ? '(incluido)' : '' ?></span><b id="tImp"></b></div>
      <hr><div class="d-flex justify-content-between align-items-center"><span class="fs-5 fw-bold">TOTAL</span><span class="fs-3 fw-bold text-primary" id="tTot"></span></div>
      <div class="p-3 rounded-3 mt-3" style="background:#f2f9ff">
        <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="pagado" value="1" id="pagado"><label class="form-check-label fw-semibold" for="pagado">Cobrar ahora (total o adelanto)</label></div>
        <div id="payBox" style="display:none">
          <select class="form-select mb-2" name="metodo_pago" id="metodo"><?php foreach (OrdenesController::PAGOS as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select>
          <div class="row g-2"><div class="col-7"><label class="form-label small mb-0">Monto a cobrar</label><input type="number" step="0.01" min="0" name="monto_pago" id="montoPago" class="form-control form-control-sm" placeholder="Total"></div>
          <div class="col-5"><label class="form-label small mb-0">Propina</label><input type="number" step="0.01" min="0" name="propina" class="form-control form-control-sm" value="0"></div></div>
          <div class="form-text" id="saldoTxt">Deja el monto vacío para cobrar el total. Si es menor, queda como adelanto.</div>
        </div>
      </div>
      <button class="btn btn-grad w-100 py-2 mt-3 fs-6"><i class="bi bi-check2-circle me-1"></i> Registrar orden</button>
    </div></div></div>
</div></form>
<?php ob_start(); ?>
<script>
const CFG = <?= json_encode(['dec' => (int)empresa()['decimales'], 'sd' => empresa()['sep_decimal'], 'sm' => empresa()['sep_miles'], 'sim' => empresa()['moneda_simbolo'], 'pos' => empresa()['simbolo_posicion'], 'rate' => (float)empresa()['impuesto_porcentaje'] / 100, 'inc' => (bool)empresa()['precios_incluyen_impuesto']]) ?>;
const SERV = <?= json_encode(array_column($servicios, null, 'id')) ?>, PREC = <?= json_encode($precios ?: new stdClass) ?>, PROD = <?= json_encode(array_column($productos, null, 'id')) ?>, VEH = <?= json_encode($vehiculos) ?>;
const $ = s => document.querySelector(s);
const money = n => { let [a,b]=(+n).toFixed(CFG.dec).split('.'); a=a.replace(/\B(?=(\d{3})+(?!\d))/g,CFG.sm); const v=b?a+CFG.sd+b:a; return CFG.pos==='antes'?CFG.sim+' '+v:v+' '+CFG.sim; };
let lines = [];
const tipoActual = () => $('#tipoV').value || (VEH.find(v=>v.id==$('#vehiculo').value)||{}).tipo_vehiculo_id || '';
function precioServ(id){ const t=tipoActual(); return (t && PREC[id] && PREC[id][t]!=null) ? +PREC[id][t] : +SERV[id].precio; }
function render(){
  const box=$('#lineas'); box.innerHTML='';
  if(!lines.length){ box.innerHTML='<div class="text-center text-muted py-4"><i class="bi bi-cart3 fs-1 d-block"></i>Toca un servicio para agregarlo</div>'; }
  let sub=0;
  lines.forEach((l,i)=>{
    const p = l.tipo==='servicio'?precioServ(l.id):+PROD[l.id].precio_venta, nom = l.tipo==='servicio'?SERV[l.id].nombre:PROD[l.id].nombre;
    sub += p*l.cant;
    box.insertAdjacentHTML('beforeend',`<div class="d-flex align-items-center gap-2 border-bottom py-2">
      <div class="flex-grow-1"><div class="fw-semibold small">${nom}</div><div class="small text-muted">${money(p)} c/u</div></div>
      <input type="number" min="1" step="1" value="${l.cant}" class="form-control form-control-sm text-center" style="width:64px" data-i="${i}">
      <b style="min-width:80px;text-align:right">${money(p*l.cant)}</b>
      <button type="button" class="btn btn-sm btn-light text-danger" data-del="${i}"><i class="bi bi-x-lg"></i></button>
      <input type="hidden" name="items[${i}][tipo]" value="${l.tipo}"><input type="hidden" name="items[${i}][id]" value="${l.id}"><input type="hidden" name="items[${i}][cant]" value="${l.cant}"></div>`);
  });
  window.curSub=sub;
  if(window.cp){ const v=cp.tipo==='porcentaje'?Math.round(sub*Math.min(100,cp.valor))/100:Math.min(sub,cp.valor); $('#desc').value=v.toFixed(2); $('#desc').readOnly=true; }
  const d=Math.min(sub,Math.max(0,+$('#desc').value||0)), base=sub-d;
  let imp,tot; if(CFG.inc){tot=base;imp=base-base/(1+CFG.rate);}else{imp=base*CFG.rate;tot=base+imp;}
  $('#tSub').textContent=money(sub); $('#tImp').textContent=money(imp); $('#tTot').textContent=money(tot); $('#tTot').dataset.v=tot;
  document.querySelectorAll('#listaServ .price-lbl').forEach(el=>{ const id=el.closest('.pos-item').dataset.id; el.textContent=money(precioServ(id)); });
}
document.querySelectorAll('.pos-item').forEach(el=>el.addEventListener('click',()=>{
  const f=lines.find(l=>l.tipo===el.dataset.tipo&&l.id==el.dataset.id); if(f) f.cant++; else lines.push({tipo:el.dataset.tipo,id:el.dataset.id,cant:1}); render(); }));
$('#lineas').addEventListener('input',e=>{ if(e.target.dataset.i!==undefined){ lines[e.target.dataset.i].cant=Math.max(1,+e.target.value||1); render(); const n=document.querySelector(`[data-i="${e.target.dataset.i}"]`); n&&n.focus(); } });
$('#lineas').addEventListener('click',e=>{ const b=e.target.closest('[data-del]'); if(b){ lines.splice(b.dataset.del,1); render(); } });
const BASE_CRM=<?= json_encode(url('crm/cupon_validar')) ?>;
$('#btnCupon').addEventListener('click',async()=>{
  const m=$('#cuponMsg'), cod=$('#cupon').value.trim();
  if(window.cp){ window.cp=null; $('#desc').readOnly=false; $('#desc').value=0; $('#cupon').value=''; $('#cupon').readOnly=false; $('#btnCupon').textContent='Aplicar'; m.textContent=''; render(); return; }
  if(!cod){ m.innerHTML='<span class="text-danger">Ingresa un código.</span>'; return; }
  const sub=window.curSub||0;
  try{ const r=await (await fetch(`${BASE_CRM}?codigo=${encodeURIComponent(cod)}&cliente_id=${$('#cliente').value||0}&subtotal=${sub}`)).json();
    m.innerHTML=`<span class="${r.ok?'text-success':'text-danger'}">${r.msg}</span>`;
    if(r.ok){ window.cp={tipo:r.tipo,valor:+r.valor}; $('#cupon').readOnly=true; $('#btnCupon').textContent='Quitar'; render(); }
  }catch(e){ m.innerHTML='<span class="text-danger">No se pudo validar.</span>'; } });
$('#desc').addEventListener('input',render); $('#tipoV').addEventListener('change',render);
$('#pagado').addEventListener('change',e=>{ $('#payBox').style.display=e.target.checked?'block':'none'; });
$('#montoPago').addEventListener('input',()=>{ const t=+(($('#tTot').dataset.v)||0), m=+$('#montoPago').value||0; $('#saldoTxt').textContent = m>0&&m<t ? 'Adelanto. Saldo pendiente: '+money(t-m) : 'Deja el monto vacío para cobrar el total. Si es menor, queda como adelanto.'; });
$('#buscar').addEventListener('input',e=>{ const q=e.target.value.toLowerCase(); document.querySelectorAll('.it').forEach(x=>x.style.display=x.dataset.n.includes(q)?'':'none'); });
function cambioCliente(){
  const cid=$('#cliente').value, sel=$('#vehiculo'); sel.innerHTML='<option value="">— Nuevo vehículo —</option>';
  VEH.filter(v=>v.cliente_id==cid).forEach(v=>sel.insertAdjacentHTML('beforeend',`<option value="${v.id}">${v.placa} · ${v.marca||''} ${v.modelo||''}</option>`));
  document.querySelectorAll('.cliNuevo').forEach(x=>x.style.display=cid?'none':''); cargarPaquetes(); cambioVeh();
}
async function cargarPaquetes(){
  const cid=$('#cliente').value, sel=$('#pkSel'); sel.innerHTML='<option value="">— No usar paquete —</option>'; $('#pkBox').style.display='none';
  if(!cid) return;
  try{ const r=await (await fetch('<?= url('paquetes/de_cliente/') ?>'+cid)).json();
    r.forEach(p=>sel.insertAdjacentHTML('beforeend',`<option value="${p.id}">${p.nombre} · ${p.usos_restantes} uso(s) · ${SERV[p.servicio_id]?SERV[p.servicio_id].nombre:''}</option>`));
    if(r.length) $('#pkBox').style.display=''; }catch(e){}
}
function cambioVeh(){ const has=!!$('#vehiculo').value; $('#nuevoBox').querySelectorAll('input,select').forEach(i=>{ if(!i.closest('.cliNuevo')) i.disabled=has; }); render(); }
$('#cliente').addEventListener('change',cambioCliente); $('#vehiculo').addEventListener('change',cambioVeh);
$('#frm').addEventListener('submit',e=>{ if(!lines.length){ e.preventDefault(); alert('Agrega al menos un servicio o producto.'); } });
cambioCliente(); render();
const COT = <?= json_encode($cotizacion) ?>;
if (COT) { if (COT.cliente_id) { $('#cliente').value = COT.cliente_id; cambioCliente(); } else if (COT.placa) $('#placa').value = COT.placa;
  if (COT.tipo_vehiculo_id) { $('#tipoV').value = COT.tipo_vehiculo_id; }
  COT.items.forEach(i => { if ((i.tipo === 'servicio' ? SERV : PROD)[i.id]) lines.push({tipo: i.tipo, id: String(i.id), cant: i.cant}); }); render(); }
const REC = <?= json_encode($recepcion) ?>;
if (REC) { if (REC.cliente_id) { $('#cliente').value = REC.cliente_id; cambioCliente(); }
  if (REC.vehiculo_id) { $('#vehiculo').value = REC.vehiculo_id; cambioVeh(); } else $('#placa').value = REC.placa; }
const CITA = <?= json_encode($cita) ?>;
if (CITA) { if (CITA.cliente_id) { $('#cliente').value = CITA.cliente_id; cambioCliente(); }
  if (CITA.vehiculo_id) { $('#vehiculo').value = CITA.vehiculo_id; cambioVeh(); }
  if (CITA.servicio_id && SERV[CITA.servicio_id]) { lines.push({tipo:'servicio', id:CITA.servicio_id, cant:1}); render(); } }
</script>
<?php $scripts = ob_get_clean(); ?>
