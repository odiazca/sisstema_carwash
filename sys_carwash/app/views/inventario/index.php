<div class="mb-4"><h1 class="page-title">Movimientos de inventario</h1><p class="page-sub">Kardex: entradas, salidas y ajustes de stock</p></div>
<div class="row g-3">
  <div class="col-lg-4"><div class="cw-card"><div class="card-h"><h6><i class="bi bi-plus-slash-minus text-primary me-2"></i>Registrar movimiento</h6></div><div class="card-b">
    <form method="post" action="<?= url('inventario/registrar') ?>"><?= csrf_field() ?>
      <label class="form-label">Producto</label><select class="form-select mb-3" name="producto_id" required><option value="">— Seleccionar —</option>
        <?php foreach ($productos as $p): ?><option value="<?= $p['id'] ?>" <?= $pid == $p['id'] ? 'selected' : '' ?>><?= e($p['nombre']) ?> (stock <?= num($p['stock'], 2) ?> <?= e($p['unidad']) ?>)</option><?php endforeach; ?></select>
      <label class="form-label">Tipo</label><select class="form-select mb-3" name="tipo" id="tp"><?php foreach (InventarioController::TIPOS as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select>
      <label class="form-label">Cantidad</label><input type="number" step="0.01" min="0" class="form-control mb-3" name="cantidad" required>
      <div id="pc"><label class="form-label">Nuevo precio de compra (opcional)</label><input type="number" step="0.01" min="0" class="form-control mb-3" name="precio_compra"></div>
      <label class="form-label">Motivo / referencia</label><input class="form-control mb-3" name="motivo" placeholder="Factura, merma, conteo…">
      <button class="btn btn-grad w-100">Registrar</button></form></div></div></div>
  <div class="col-lg-8"><div class="cw-card"><div class="card-h"><h6>Últimos movimientos</h6><div class="input-ico" style="width:220px"><i class="bi bi-search"></i><input class="form-control form-control-sm" data-table-filter="#tbl" placeholder="Buscar…"></div></div>
    <div class="table-responsive"><table class="table table-hover mb-0" id="tbl"><thead><tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th class="text-end">Cant.</th><th>Motivo</th><th>Usuario</th></tr></thead><tbody>
    <?php foreach ($rows as $m): $c = ['entrada' => 'success', 'salida' => 'danger', 'ajuste' => 'info'][$m['tipo']]; ?>
      <tr><td class="text-nowrap"><?= fdate($m['created_at'], true) ?></td><td><b><?= e($m['producto']) ?></b></td><td><span class="badge badge-soft-<?= $c ?>"><?= ucfirst($m['tipo']) ?></span></td><td class="text-end"><?= num($m['cantidad'], 2) ?> <?= e($m['unidad']) ?></td><td><?= e($m['motivo']) ?></td><td class="text-muted"><?= e($m['usuario'] ?? '-') ?></td></tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-5">Sin movimientos</td></tr><?php endif; ?></tbody></table></div></div></div>
</div>
<?php ob_start(); ?><script>const tp=document.getElementById('tp'),pc=document.getElementById('pc');tp.addEventListener('change',()=>pc.style.display=tp.value==='entrada'?'':'none');</script><?php $scripts = ob_get_clean(); ?>
