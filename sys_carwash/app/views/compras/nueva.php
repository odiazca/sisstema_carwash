<div class="mb-4"><a href="<?= url('compras') ?>" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Compras</a><h1 class="page-title mt-1">Nueva compra</h1></div>
<form method="post" action="<?= url('compras/guardar') ?>" class="cw-card"><?= csrf_field() ?>
<div class="card-b pt-4"><div class="row g-3">
  <div class="col-md-4"><label class="form-label">Proveedor</label><select class="form-select" name="proveedor_id"><option value="">— Sin proveedor —</option><?php foreach ($proveedores as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['nombre']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><label class="form-label">Fecha</label><input type="date" name="fecha" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
  <div class="col-md-3"><label class="form-label">N° de documento</label><input name="nro_doc" class="form-control"></div>
  <div class="col-md-2"><label class="form-label">Condición</label><select class="form-select" name="condicion" id="cond"><option value="contado">Contado</option><option value="credito">Crédito</option></select></div>
  <div class="col-md-3" id="credBox" style="display:none"><label class="form-label">Días de crédito</label><input type="number" name="dias_credito" min="0" max="365" value="30" class="form-control"><div class="form-text">Requiere proveedor. Se paga luego con abonos.</div></div>
  <div class="col-md-3 d-flex align-items-end" id="rgBox"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="registrar_gasto" value="1" id="rg" checked><label class="form-check-label" for="rg">Registrar como gasto</label></div></div>
</div>
<hr><table class="table align-middle" id="tb"><thead><tr><th>Producto</th><th style="width:130px">Cantidad</th><th style="width:150px">Costo unit.</th><th style="width:130px" class="text-end">Subtotal</th><th></th></tr></thead><tbody></tbody>
<tfoot><tr><td colspan="3" class="text-end fw-bold">TOTAL</td><td class="text-end fs-5 fw-bold text-primary" id="tot"></td><td></td></tr></tfoot></table>
<button type="button" class="btn btn-outline-primary btn-sm" id="add"><i class="bi bi-plus-lg"></i> Agregar producto</button>
<div class="mt-3"><label class="form-label">Notas</label><input name="notas" class="form-control"></div></div>
<div class="card-b border-top text-end"><button class="btn btn-grad px-4"><i class="bi bi-check2 me-1"></i> Registrar compra</button></div></form>
<?php ob_start(); ?><script>
const P = <?= json_encode($productos) ?>; let n = 0;
const C = <?= json_encode(['dec' => (int)empresa()['decimales'], 'sd' => empresa()['sep_decimal'], 'sm' => empresa()['sep_miles'], 'sim' => empresa()['moneda_simbolo'], 'pos' => empresa()['simbolo_posicion']]) ?>;
const money = v => { let [a,b]=(+v).toFixed(C.dec).split('.'); a=a.replace(/\B(?=(\d{3})+(?!\d))/g,C.sm); const t=b?a+C.sd+b:a; return C.pos==='antes'?C.sim+' '+t:t+' '+C.sim; };
function row(){ const i=n++, tr=document.createElement('tr');
  tr.innerHTML=`<td><select class="form-select" name="items[${i}][producto_id]" required><option value="">— Seleccionar —</option>${P.map(p=>`<option value="${p.id}" data-c="${p.precio_compra}">${p.nombre} (${p.unidad})</option>`).join('')}</select></td>
  <td><input type="number" step="0.01" min="0.01" class="form-control q" name="items[${i}][cantidad]" value="1"></td>
  <td><input type="number" step="0.01" min="0" class="form-control c" name="items[${i}][costo]" value="0"></td><td class="text-end fw-bold s">0</td>
  <td><button type="button" class="btn btn-sm btn-light text-danger x"><i class="bi bi-x-lg"></i></button></td>`;
  document.querySelector('#tb tbody').appendChild(tr); }
function calc(){ let t=0; document.querySelectorAll('#tb tbody tr').forEach(tr=>{ const s=(+tr.querySelector('.q').value||0)*(+tr.querySelector('.c').value||0); tr.querySelector('.s').textContent=money(s); t+=s; }); document.getElementById('tot').textContent=money(t); }
document.getElementById('add').onclick=()=>{row();calc();};
document.querySelector('#tb').addEventListener('input',calc);
document.querySelector('#tb').addEventListener('change',e=>{ if(e.target.tagName==='SELECT'){ const o=e.target.selectedOptions[0]; e.target.closest('tr').querySelector('.c').value=o.dataset.c||0; calc(); }});
document.querySelector('#tb').addEventListener('click',e=>{ const b=e.target.closest('.x'); if(b){ b.closest('tr').remove(); calc(); }});
document.getElementById('cond').addEventListener('change',e=>{ const c=e.target.value==='credito'; document.getElementById('credBox').style.display=c?'':'none'; document.getElementById('rgBox').style.display=c?'none':''; });
row(); calc();
</script><?php $scripts = ob_get_clean(); ?>
