<?php
$zonas = ['America/Lima', 'America/Bogota', 'America/Guayaquil', 'America/Mexico_City', 'America/Santiago', 'America/Argentina/Buenos_Aires', 'America/La_Paz', 'America/Caracas', 'America/Montevideo', 'America/Panama', 'America/Costa_Rica', 'America/Guatemala', 'America/Santo_Domingo', 'America/New_York', 'Europe/Madrid', 'UTC'];
if (!in_array($emp['zona_horaria'], $zonas)) $zonas[] = $emp['zona_horaria'];
$monedas = [
  ['PEN','S/','Sol peruano'],['USD','$','Dólar estadounidense'],['COP','$','Peso colombiano'],['MXN','$','Peso mexicano'],['CLP','$','Peso chileno'],
  ['ARS','$','Peso argentino'],['BOB','Bs','Boliviano'],['EUR','€','Euro'],['UYU','$U','Peso uruguayo'],['GTQ','Q','Quetzal'],['CRC','₡','Colón'],['DOP','RD$','Peso dominicano'],['VES','Bs.S','Bolívar'],['PYG','₲','Guaraní'],
];
?>
<div class="mb-4"><h1 class="page-title">Configuración de la empresa</h1><p class="page-sub">Datos del negocio, logo, moneda y formatos del sistema</p></div>
<form method="post" action="<?= url('configuracion/guardar') ?>" enctype="multipart/form-data">
<?= csrf_field() ?>
<div class="row g-3">
 <div class="col-lg-8">
  <div class="cw-card mb-3"><div class="card-h"><h6><i class="bi bi-building text-primary me-2"></i>Datos de la empresa</h6></div><div class="card-b"><div class="row g-3">
    <div class="col-md-6"><label class="form-label">Nombre comercial *</label><input class="form-control" name="nombre" value="<?= e($emp['nombre']) ?>" required></div>
    <div class="col-md-6"><label class="form-label">Razón social</label><input class="form-control" name="razon_social" value="<?= e($emp['razon_social'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-label">RUC / NIT / RFC</label><input class="form-control" name="ruc" value="<?= e($emp['ruc'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-label">Teléfono</label><input class="form-control" name="telefono" value="<?= e($emp['telefono'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-label">Correo</label><input type="email" class="form-control" name="email" value="<?= e($emp['email'] ?? '') ?>"></div>
    <div class="col-md-8"><label class="form-label">Dirección</label><input class="form-control" name="direccion" value="<?= e($emp['direccion'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-label">Sitio web</label><input class="form-control" name="web" value="<?= e($emp['web'] ?? '') ?>"></div>
  </div></div></div>

  <div class="cw-card mb-3"><div class="card-h"><h6><i class="bi bi-currency-exchange text-primary me-2"></i>Moneda y formato numérico</h6></div><div class="card-b"><div class="row g-3">
    <div class="col-md-6"><label class="form-label">Moneda predefinida</label>
      <select class="form-select" id="preset"><option value="">Personalizada…</option>
      <?php foreach ($monedas as $m): ?><option value="<?= $m[0] ?>|<?= e($m[1]) ?>" <?= $emp['moneda_codigo'] === $m[0] ? 'selected' : '' ?>><?= $m[0] ?> - <?= $m[2] ?> (<?= e($m[1]) ?>)</option><?php endforeach; ?></select></div>
    <div class="col-md-3"><label class="form-label">Código</label><input class="form-control" id="m_cod" name="moneda_codigo" value="<?= e($emp['moneda_codigo']) ?>" maxlength="5"></div>
    <div class="col-md-3"><label class="form-label">Símbolo</label><input class="form-control" id="m_sim" name="moneda_simbolo" value="<?= e($emp['moneda_simbolo']) ?>" maxlength="8"></div>
    <div class="col-md-3"><label class="form-label">Posición del símbolo</label>
      <select class="form-select" id="m_pos" name="simbolo_posicion"><option value="antes" <?= $emp['simbolo_posicion'] === 'antes' ? 'selected' : '' ?>>Antes (S/ 10.00)</option><option value="despues" <?= $emp['simbolo_posicion'] === 'despues' ? 'selected' : '' ?>>Después (10.00 S/)</option></select></div>
    <div class="col-md-3"><label class="form-label">Decimales</label>
      <select class="form-select" id="m_dec" name="decimales"><?php for ($i = 0; $i <= 4; $i++): ?><option <?= (int)$emp['decimales'] === $i ? 'selected' : '' ?>><?= $i ?></option><?php endfor; ?></select></div>
    <div class="col-md-3"><label class="form-label">Separador decimal</label>
      <select class="form-select" id="m_sd" name="sep_decimal"><option value="." <?= $emp['sep_decimal'] === '.' ? 'selected' : '' ?>>Punto ( . )</option><option value="," <?= $emp['sep_decimal'] === ',' ? 'selected' : '' ?>>Coma ( , )</option></select></div>
    <div class="col-md-3"><label class="form-label">Separador de miles</label>
      <select class="form-select" id="m_sm" name="sep_miles"><option value="," <?= $emp['sep_miles'] === ',' ? 'selected' : '' ?>>Coma ( , )</option><option value="." <?= $emp['sep_miles'] === '.' ? 'selected' : '' ?>>Punto ( . )</option><option value=" " <?= $emp['sep_miles'] === ' ' ? 'selected' : '' ?>>Espacio</option><option value="'" <?= $emp['sep_miles'] === "'" ? 'selected' : '' ?>>Apóstrofe ( ' )</option><option value="" <?= $emp['sep_miles'] === '' ? 'selected' : '' ?>>Ninguno</option></select></div>
    <div class="col-12"><div class="p-3 rounded-3" style="background:#eef7fe"><span class="text-muted small">Vista previa:</span> <b class="fs-5 ms-2" id="preview"></b></div></div>
  </div></div></div>

  <div class="cw-card mb-3"><div class="card-h"><h6><i class="bi bi-sliders text-primary me-2"></i>Impuestos y otras opciones</h6></div><div class="card-b"><div class="row g-3">
    <div class="col-md-3"><label class="form-label">Nombre del impuesto</label><input class="form-control" name="impuesto_nombre" value="<?= e($emp['impuesto_nombre']) ?>"></div>
    <div class="col-md-3"><label class="form-label">Porcentaje (%)</label><input type="number" step="0.01" min="0" max="100" class="form-control" name="impuesto_porcentaje" value="<?= e($emp['impuesto_porcentaje']) ?>"></div>
    <div class="col-md-6 d-flex align-items-end"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="precios_incluyen_impuesto" value="1" id="inc" <?= $emp['precios_incluyen_impuesto'] ? 'checked' : '' ?>><label class="form-check-label" for="inc">Los precios ya incluyen impuesto</label></div></div>
    <div class="col-md-4"><label class="form-label">Zona horaria</label><select class="form-select" name="zona_horaria"><?php foreach ($zonas as $z): ?><option <?= $emp['zona_horaria'] === $z ? 'selected' : '' ?>><?= e($z) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="form-label">Formato de fecha</label><select class="form-select" name="formato_fecha"><?php foreach (['d/m/Y' => 'dd/mm/aaaa', 'm/d/Y' => 'mm/dd/aaaa', 'Y-m-d' => 'aaaa-mm-dd', 'd-m-Y' => 'dd-mm-aaaa'] as $k => $v): ?><option value="<?= $k ?>" <?= $emp['formato_fecha'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="form-label">Prefijo de órdenes</label><input class="form-control" name="prefijo_orden" value="<?= e($emp['prefijo_orden']) ?>" maxlength="10"></div>
    <div class="col-12"><label class="form-label">Mensaje al pie del ticket</label><input class="form-control" name="pie_ticket" value="<?= e($emp['pie_ticket'] ?? '') ?>"></div>
  </div></div></div>
 </div>

 <div class="col-lg-4">
  <div class="cw-card mb-3"><div class="card-h"><h6><i class="bi bi-image text-primary me-2"></i>Logo de la empresa</h6></div><div class="card-b text-center">
    <div class="logo-preview mx-auto mb-3" id="logoBox"><?php if (!empty($emp['logo'])): ?><img id="logoImg" src="<?= asset('uploads/' . e($emp['logo'])) ?>"><?php else: ?><i class="bi bi-droplet-half fs-1 text-primary" id="logoPh"></i><?php endif; ?></div>
    <input type="file" class="form-control" name="logo" id="logoIn" accept="image/png,image/jpeg,image/webp">
    <div class="form-text">PNG, JPG o WEBP · máx. 2 MB · se usa en login, menú y tickets</div>
    <?php if (!empty($emp['logo'])): ?><div class="form-check mt-2 text-start"><input class="form-check-input" type="checkbox" name="quitar_logo" value="1" id="ql"><label class="form-check-label" for="ql">Quitar logo actual</label></div><?php endif; ?>
  </div></div>
  <button class="btn btn-grad w-100 py-2"><i class="bi bi-check2-circle me-1"></i> Guardar configuración</button>
 </div>
</div>
</form>
<?php ob_start(); ?>
<script>
const $ = id => document.getElementById(id);
function fmt(){
  const dec=+$('m_dec').value, sd=$('m_sd').value, sm=$('m_sm').value;
  let n=(1234567.891).toFixed(dec).split('.');
  n[0]=n[0].replace(/\B(?=(\d{3})+(?!\d))/g, sm);
  const v=n.join(dec>0?sd:'');
  $('preview').textContent = $('m_pos').value==='antes' ? $('m_sim').value+' '+v : v+' '+$('m_sim').value;
}
['m_dec','m_sd','m_sm','m_pos','m_sim'].forEach(i=>$(i).addEventListener('input',fmt)); fmt();
$('preset').addEventListener('change',e=>{ if(!e.target.value) return; const [c,s]=e.target.value.split('|'); $('m_cod').value=c; $('m_sim').value=s; fmt(); });
$('logoIn').addEventListener('change',e=>{ const f=e.target.files[0]; if(!f) return; const r=new FileReader();
  r.onload=ev=>{ $('logoBox').innerHTML='<img src="'+ev.target.result+'">'; }; r.readAsDataURL(f); });
</script>
<?php $scripts = ob_get_clean(); ?>
