<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div><h1 class="page-title"><?= e($c['title']) ?></h1><p class="page-sub"><?= e($c['subtitle']) ?></p></div>
  <a href="<?= url($c['route'] . '/crear') ?>" class="btn btn-grad"><i class="bi bi-plus-lg me-1"></i> <?= e($c['new_label'] ?? 'Nuevo ' . $c['singular']) ?></a>
</div>
<div class="cw-card">
  <div class="card-h">
    <div class="input-ico" style="width:300px;max-width:100%"><i class="bi bi-search"></i><input class="form-control" placeholder="Buscar…" data-table-filter="#tbl"></div>
    <span class="text-muted small"><?= count($rows) ?> registro(s)</span>
  </div>
  <div class="table-responsive"><table class="table table-hover mb-0" id="tbl">
    <thead><tr><?php foreach ($c['columns'] as $k => $col): ?><th class="<?= in_array($col[1] ?? '', ['money', 'number']) ? 'text-end' : '' ?>"><?= e($col[0]) ?></th><?php endforeach; ?><th class="text-end">Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
      <?php foreach ($c['columns'] as $k => $col): $v = $r[$k] ?? null; $t = $col[1] ?? 'text'; ?>
        <td class="<?= in_array($t, ['money', 'number']) ? 'text-end' : '' ?>">
        <?php
          switch ($t) {
            case 'money': echo '<b>' . money($v) . '</b>'; break;
            case 'number': echo num($v, $col[2] ?? 0); break;
            case 'date': echo fdate($v); break;
            case 'datetime': echo fdate($v, true); break;
            case 'estado': echo $v ? '<span class="badge badge-soft-success">Activo</span>' : '<span class="badge badge-soft-secondary">Inactivo</span>'; break;
            case 'badge': echo estado_badge((string)$v); break;
            case 'strong': echo '<b>' . e($v) . '</b>'; break;
            case 'pct': echo num($v, 1) . '%'; break;
            case 'stock': $low = isset($r['stock_minimo']) && $v <= $r['stock_minimo']; echo '<span class="badge badge-soft-' . ($low ? 'danger' : 'success') . '">' . num($v, 2) . '</span>'; break;
            default: echo e($v ?? '-');
          }
        ?></td>
      <?php endforeach; ?>
        <td class="text-end text-nowrap">
          <?php foreach ($c['row_actions'] ?? [] as [$ai, $aurl, $atitle]): ?><a class="btn btn-icon btn-light text-success" title="<?= e($atitle) ?>" href="<?= url(str_replace('{id}', $r[$c['pk']], $aurl)) ?>"><i class="bi <?= $ai ?>"></i></a><?php endforeach; ?>
          <a class="btn btn-icon btn-light text-primary" title="Editar" href="<?= url($c['route'] . '/editar/' . $r[$c['pk']]) ?>"><i class="bi bi-pencil-square"></i></a>
          <?php if ($c['can_delete']): ?>
          <form method="post" action="<?= url($c['route'] . '/eliminar') ?>" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r[$c['pk']] ?>">
            <button class="btn btn-icon btn-light text-danger" title="Eliminar" data-confirm="¿Eliminar este registro?"><i class="bi bi-trash"></i></button></form>
          <?php endif; ?>
        </td></tr>
    <?php endforeach; if (!$rows): ?>
      <tr><td colspan="<?= count($c['columns']) + 1 ?>" class="text-center text-muted py-5"><i class="bi <?= e($c['icon']) ?> fs-1 d-block mb-2"></i>Sin registros todavía</td></tr>
    <?php endif; ?>
    </tbody></table></div>
</div>
