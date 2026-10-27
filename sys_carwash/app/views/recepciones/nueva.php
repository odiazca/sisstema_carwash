<div class="mb-3"><h1 class="page-title">Nueva recepción</h1><p class="page-sub">Registra cómo llega el vehículo para evitar reclamos</p></div>
<form method="post" action="<?= url('recepciones/guardar') ?>" enctype="multipart/form-data" id="frm"><?= csrf_field() ?>
<div class="row g-3">
  <div class="col-xl-7">
    <div class="cw-card mb-3"><div class="card-h"><h6><i class="bi bi-car-front text-primary me-2"></i>Vehículo y cliente</h6></div><div class="card-b"><div class="row g-3">
      <div class="col-md-6"><label class="form-label">Cliente</label><select class="form-select" name="cliente_id" id="cliente"><option value="">— Sin registrar —</option>
        <?php foreach ($clientes as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['nombre']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-6"><label class="form-label">Vehículo registrado</label><select class="form-select" name="vehiculo_id" id="vehiculo"><option value="">— Otro / nuevo —</option></select></div>
      <div class="col-md-4"><label class="form-label">Placa *</label><input class="form-control text-uppercase" name="placa" id="placa" placeholder="ABC-123"></div>
      <div class="col-md-4"><label class="form-label">Kilometraje</label><input type="number" min="0" class="form-control" name="kilometraje"></div>
      <div class="col-md-4"><label class="form-label">Combustible: <b id="combTxt">50%</b></label><input type="range" class="form-range" name="combustible" min="0" max="100" step="25" value="50" id="comb"></div>
    </div></div></div>
    <div class="cw-card mb-3"><div class="card-h"><h6><i class="bi bi-exclamation-triangle text-warning me-2"></i>Daños previos por zona</h6></div><div class="card-b"><div class="row g-2">
      <?php foreach (RecepcionesController::ZONAS as $z => $l): ?><div class="col-md-6"><div class="input-group input-group-sm"><span class="input-group-text" style="width:175px"><?= $l ?></span>
        <select class="form-select" name="dano[<?= $z ?>]"><?php foreach (RecepcionesController::DANOS as $k => $t): ?><option value="<?= $k ?>"><?= $t ?></option><?php endforeach; ?></select></div></div><?php endforeach; ?>
    </div></div></div>
    <div class="cw-card"><div class="card-h"><h6><i class="bi bi-camera text-primary me-2"></i>Fotos del vehículo</h6></div><div class="card-b">
      <input type="file" class="form-control" name="fotos[]" id="fotos" accept="image/jpeg,image/png,image/webp" capture="environment" multiple>
      <div class="form-text">Hasta <?= RecepcionesController::MAXFOTOS ?> fotos (JPG, PNG o WEBP, máx. 8 MB c/u). Desde el celular puedes usar la cámara.</div>
      <div class="d-flex flex-wrap gap-2 mt-2" id="prev"></div></div></div>
  </div>
  <div class="col-xl-5">
    <div class="cw-card mb-3"><div class="card-h"><h6><i class="bi bi-ui-checks text-primary me-2"></i>Checklist de objetos</h6></div><div class="card-b">
      <?php foreach (RecepcionesController::CHECK as $k => $l): ?><div class="form-check"><input class="form-check-input" type="checkbox" name="check[]" value="<?= $k ?>" id="ck<?= $k ?>"><label class="form-check-label" for="ck<?= $k ?>"><?= $l ?></label></div><?php endforeach; ?>
      <div class="form-text">Marca lo que el vehículo trae al ingresar.</div>
      <label class="form-label mt-3">Observaciones</label><textarea class="form-control" name="observaciones" rows="3" maxlength="500" placeholder="Detalles que el cliente pidió tener en cuenta…"></textarea></div></div>
    <div class="cw-card"><div class="card-h"><h6><i class="bi bi-pen text-primary me-2"></i>Firma del cliente</h6><button type="button" class="btn btn-sm btn-light" id="limpiar">Limpiar</button></div><div class="card-b">
      <canvas id="firma" width="520" height="180" style="width:100%;border:1px dashed #9cc7e6;border-radius:.8rem;background:#f8fcff;touch-action:none"></canvas>
      <input type="hidden" name="firma" id="firmaData"><div class="form-text">El cliente firma conforme al estado registrado (opcional).</div>
      <button class="btn btn-grad w-100 py-2 mt-3"><i class="bi bi-check2-circle me-1"></i> Guardar recepción</button></div></div>
  </div>
</div></form>
<?php ob_start(); ?>
<script>
const $=s=>document.querySelector(s), VEH=<?= json_encode($vehiculos) ?>;
function vehs(){ const cid=$('#cliente').value, sel=$('#vehiculo'); sel.innerHTML='<option value="">— Otro / nuevo —</option>';
  VEH.filter(v=>!cid||v.cliente_id==cid).forEach(v=>sel.insertAdjacentHTML('beforeend',`<option value="${v.id}" data-p="${v.placa}" data-c="${v.cliente_id||''}">${v.placa} · ${v.marca||''} ${v.modelo||''}</option>`)); }
$('#cliente').addEventListener('change',vehs); vehs();
$('#vehiculo').addEventListener('change',e=>{ const o=e.target.selectedOptions[0]; if(o&&o.dataset.p){ $('#placa').value=o.dataset.p; if(o.dataset.c) $('#cliente').value=o.dataset.c; } });
$('#comb').addEventListener('input',e=>$('#combTxt').textContent=e.target.value+'%');
$('#fotos').addEventListener('change',e=>{ const b=$('#prev'); b.innerHTML=''; [...e.target.files].slice(0,<?= RecepcionesController::MAXFOTOS ?>).forEach(f=>{ const i=document.createElement('img'); i.src=URL.createObjectURL(f); i.style.cssText='height:70px;border-radius:.6rem'; b.appendChild(i); }); });
const cv=$('#firma'), cx=cv.getContext('2d'); let dr=false, dirty=false; cx.lineWidth=2.5; cx.lineCap='round'; cx.strokeStyle='#0c4a6e';
const pos=e=>{ const r=cv.getBoundingClientRect(); return [(e.clientX-r.left)*cv.width/r.width,(e.clientY-r.top)*cv.height/r.height]; };
cv.addEventListener('pointerdown',e=>{ dr=true; const [x,y]=pos(e); cx.beginPath(); cx.moveTo(x,y); cv.setPointerCapture(e.pointerId); });
cv.addEventListener('pointermove',e=>{ if(!dr) return; const [x,y]=pos(e); cx.lineTo(x,y); cx.stroke(); dirty=true; });
['pointerup','pointercancel'].forEach(t=>cv.addEventListener(t,()=>dr=false));
$('#limpiar').addEventListener('click',()=>{ cx.clearRect(0,0,cv.width,cv.height); dirty=false; });
$('#frm').addEventListener('submit',e=>{ if(!$('#placa').value.trim()&&!$('#vehiculo').value){ e.preventDefault(); alert('Indica la placa.'); return; } $('#firmaData').value=dirty?cv.toDataURL('image/png'):''; });
</script>
<?php $scripts = ob_get_clean(); ?>
