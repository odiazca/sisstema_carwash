<?php $isNew = !($row && isset($row[$c['pk']])); ?>
<div class="mb-4"><a href="<?= url($c['route']) ?>" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Volver a <?= e($c['title']) ?></a>
  <h1 class="page-title mt-1"><?= e($title) ?></h1></div>
<form method="post" action="<?= url($c['route'] . '/guardar') ?>" class="cw-card" autocomplete="off">
  <?= csrf_field() ?><input type="hidden" name="_id" value="<?= $isNew ? 0 : (int)$row[$c['pk']] ?>">
  <div class="card-b pt-4"><div class="row g-3">
  <?php foreach ($c['fields'] as $n => $f):
    $t = $f['type'] ?? 'text'; $col = $f['col'] ?? 6; $val = $row[$n] ?? ($f['default'] ?? ''); $req = !empty($f['required']) ? 'required' : ''; ?>
    <div class="col-md-<?= $col ?>">
    <?php if ($t === 'switch'): ?>
      <div class="form-check form-switch mt-4 pt-2"><input class="form-check-input" type="checkbox" name="<?= $n ?>" value="1" id="f_<?= $n ?>" <?= ($isNew ? ($f['default'] ?? 1) : $val) ? 'checked' : '' ?>><label class="form-check-label fw-semibold" for="f_<?= $n ?>"><?= e($f['label']) ?></label></div>
    <?php else: ?>
      <label class="form-label" for="f_<?= $n ?>"><?= e($f['label']) ?><?= $req ? ' *' : '' ?></label>
      <?php if ($t === 'select'): ?>
        <select class="form-select" name="<?= $n ?>" id="f_<?= $n ?>" <?= $req ?>><option value="">— Seleccionar —</option>
        <?php foreach ($opts[$n] as $ov => $ol): ?><option value="<?= e($ov) ?>" <?= (string)$val === (string)$ov ? 'selected' : '' ?>><?= e($ol) ?></option><?php endforeach; ?></select>
      <?php elseif ($t === 'textarea'): ?>
        <textarea class="form-control" rows="3" name="<?= $n ?>" id="f_<?= $n ?>" <?= $req ?>><?= e($val) ?></textarea>
      <?php elseif ($t === 'datetime'): ?>
        <input type="datetime-local" class="form-control" name="<?= $n ?>" id="f_<?= $n ?>" value="<?= $val ? e(date('Y-m-d\TH:i', strtotime($val))) : '' ?>" <?= $req ?>>
      <?php elseif ($t === 'password'): ?>
        <input type="password" class="form-control" name="<?= $n ?>" id="f_<?= $n ?>" autocomplete="new-password" <?= $isNew ? 'required' : '' ?> placeholder="<?= $isNew ? '' : 'Dejar vacío para no cambiar' ?>">
      <?php elseif ($t === 'number' || $t === 'money'): ?>
        <input type="number" step="<?= $f['step'] ?? ($t === 'money' ? '0.01' : '1') ?>" min="<?= $f['min'] ?? 0 ?>" class="form-control" name="<?= $n ?>" id="f_<?= $n ?>" value="<?= e($val) ?>" <?= $req ?>>
      <?php else: ?>
        <input type="<?= in_array($t, ['email', 'date']) ? $t : 'text' ?>" class="form-control" name="<?= $n ?>" id="f_<?= $n ?>" value="<?= e($val) ?>" <?= $req ?> <?= isset($f['maxlength']) ? 'maxlength="' . $f['maxlength'] . '"' : '' ?>>
      <?php endif; ?>
    <?php endif; ?>
    </div>
  <?php endforeach; ?>
  </div>
  <?php if ($c['extra_form']) require APP_PATH . '/views/' . $c['extra_form'] . '.php'; ?>
  </div>
  <div class="card-b border-top d-flex gap-2 justify-content-end">
    <a href="<?= url($c['route']) ?>" class="btn btn-light">Cancelar</a>
    <button class="btn btn-grad px-4"><i class="bi bi-check2 me-1"></i> Guardar</button>
  </div>
</form>
