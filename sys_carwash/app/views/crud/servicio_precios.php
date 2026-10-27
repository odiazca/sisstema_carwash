<hr class="my-4">
<h6 class="fw-bold"><i class="bi bi-tags text-primary me-1"></i> Precio por tipo de vehículo <span class="text-muted fw-normal small">(opcional · si lo dejas vacío se usa el precio base)</span></h6>
<div class="row g-3 mt-1">
<?php foreach ($extra['tipos'] as $t): ?>
  <div class="col-md-3 col-6"><label class="form-label"><?= e($t['nombre']) ?></label>
  <input type="number" step="0.01" min="0" class="form-control" name="precio_tipo[<?= $t['id'] ?>]" value="<?= e($extra['precios'][$t['id']] ?? '') ?>" placeholder="Precio base"></div>
<?php endforeach; ?>
</div>
