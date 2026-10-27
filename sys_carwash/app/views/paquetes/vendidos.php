<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2"><div><h1 class="page-title">Paquetes vendidos</h1><p class="page-sub">Saldo de usos por cliente</p></div>
<a href="<?= url('paquetes') ?>" class="btn btn-outline-primary"><i class="bi bi-gift me-1"></i> Catálogo de paquetes</a></div>
<div class="cw-card"><div class="card-h"><div class="input-ico" style="width:260px"><i class="bi bi-search"></i><input class="form-control form-control-sm" placeholder="Buscar…" data-table-filter="#tbl"></div></div>
<div class="table-responsive"><table class="table table-hover mb-0" id="tbl"><thead><tr><th>Fecha</th><th>Cliente</th><th>Paquete</th><th>Servicio</th><th class="text-center">Usos</th><th>Vence</th><th>Estado</th><th class="text-end">Pagado</th></tr></thead><tbody>
<?php foreach ($rows as $r): $venc = $r['fecha_vence'] && $r['fecha_vence'] < date('Y-m-d'); $ok = $r['usos_restantes'] > 0 && !$venc; ?>
<tr><td><?= fdate($r['fecha_compra']) ?></td><td><b><?= e($r['cliente']) ?></b></td><td><?= e($r['nombre']) ?></td><td><?= e($r['servicio']) ?></td>
<td class="text-center"><b><?= $r['usos_restantes'] ?></b> / <?= $r['usos_total'] ?></td><td><?= fdate($r['fecha_vence']) ?></td>
<td><?= $ok ? '<span class="badge badge-soft-success">Vigente</span>' : ($r['usos_restantes'] <= 0 ? '<span class="badge badge-soft-secondary">Agotado</span>' : '<span class="badge badge-soft-danger">Vencido</span>') ?></td>
<td class="text-end fw-bold"><?= money($r['precio_pagado']) ?></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="8" class="text-center text-muted py-5">Aún no se vendieron paquetes</td></tr><?php endif; ?></tbody></table></div></div>
