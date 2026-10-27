<div class="mb-4"><h1 class="page-title">Bitácora del sistema</h1><p class="page-sub">Últimas 500 acciones registradas</p></div>
<div class="cw-card"><div class="card-h"><div class="input-ico" style="width:300px"><i class="bi bi-search"></i><input class="form-control" placeholder="Buscar…" data-table-filter="#tbl"></div></div>
<div class="table-responsive"><table class="table table-hover mb-0" id="tbl"><thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Detalle</th><th>IP</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td class="text-nowrap"><?= fdate($r['created_at'], true) ?></td><td><b><?= e($r['usuario'] ?? '-') ?></b></td><td><span class="badge badge-soft-info"><?= e($r['accion']) ?></span></td><td><?= e($r['detalle']) ?></td><td class="text-muted"><?= e($r['ip']) ?></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="5" class="text-center text-muted py-5">Sin registros</td></tr><?php endif; ?>
</tbody></table></div></div>
