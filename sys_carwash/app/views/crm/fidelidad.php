<?php $crmTab = 'fidelidad'; include __DIR__ . '/_nav.php'; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2"><div><h1 class="page-title">Programa de fidelización</h1>
<p class="page-sub"><?= $cfg['programa_activo'] ? '1 punto por cada <b>' . money($cfg['monto_por_punto']) . '</b> pagados · 1 punto = <b>' . money($cfg['valor_punto']) . '</b> · canje mínimo <b>' . (int)$cfg['puntos_minimo'] . '</b> pts' : '<span class="text-danger">Programa desactivado</span>' ?></p></div>
<?php if (can('configuracion')): ?><a href="<?= url('crm/config') ?>" class="btn btn-outline-primary"><i class="bi bi-sliders me-1"></i> Configurar reglas</a><?php endif; ?></div>
<div class="row g-3">
 <div class="col-lg-4">
  <div class="cw-card mb-3"><div class="card-h"><h6><i class="bi bi-gift text-success me-2"></i>Canjear puntos</h6></div><div class="card-b">
   <form method="post" action="<?= url('crm/canjear') ?>" class="row g-2"><?= csrf_field() ?><input type="hidden" name="volver" value="crm/fidelidad">
    <div class="col-12"><select name="cliente_id" class="form-select" required><option value="">— Cliente —</option><?php foreach ($cl as $c): if ($c['puntos'] > 0): ?><option value="<?= $c['id'] ?>"><?= e($c['nombre']) ?> (<?= $c['puntos'] ?> pts)</option><?php endif; endforeach; ?></select></div>
    <div class="col-12"><input type="number" name="puntos" min="<?= (int)$cfg['puntos_minimo'] ?>" class="form-control" placeholder="Puntos a canjear" required></div>
    <div class="col-12"><button class="btn btn-grad w-100">Generar cupón de descuento</button></div>
    <div class="form-text">Se crea un cupón de un solo uso (90 días) que se aplica en <b>Nueva orden</b>.</div></form></div></div>
  <?php if (can('configuracion')): ?><div class="cw-card"><div class="card-h"><h6><i class="bi bi-pencil-square text-primary me-2"></i>Ajuste manual</h6></div><div class="card-b">
   <form method="post" action="<?= url('crm/ajustar') ?>" class="row g-2"><?= csrf_field() ?><input type="hidden" name="volver" value="crm/fidelidad">
    <div class="col-12"><select name="cliente_id" class="form-select" required><option value="">— Cliente —</option><?php foreach ($cl as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['nombre']) ?></option><?php endforeach; ?></select></div>
    <div class="col-5"><input type="number" name="puntos" class="form-control" placeholder="+/- pts" required></div><div class="col-7"><input name="detalle" class="form-control" placeholder="Motivo"></div>
    <div class="col-12"><button class="btn btn-outline-primary w-100">Aplicar ajuste</button></div></form></div></div><?php endif; ?>
 </div>
 <div class="col-lg-8">
  <div class="cw-card mb-3"><div class="card-h"><h6><i class="bi bi-trophy text-warning me-2"></i>Ranking de puntos</h6></div><div class="table-responsive"><table class="table table-hover mb-0 align-middle"><thead><tr><th>#</th><th>Cliente</th><th>Segmento</th><th class="text-end">Puntos</th><th class="text-end">Equivale a</th></tr></thead><tbody>
  <?php foreach ($rank as $i => $c): [$sl, $si, $sc] = CRM_SEGS[$c['segmento']]; ?><tr><td><span class="rank"><?= $i + 1 ?></span></td><td><a class="fw-bold text-decoration-none text-dark" href="<?= url('clientes/ficha/' . $c['id']) ?>"><?= e($c['nombre']) ?></a></td><td><span class="seg-badge" style="--c:<?= $sc ?>"><i class="bi <?= $si ?>"></i> <?= $sl ?></span></td><td class="text-end fw-bold"><?= num($c['puntos'], 0) ?></td><td class="text-end text-success"><?= money($c['puntos'] * $cfg['valor_punto']) ?></td></tr><?php endforeach; if (!$rank): ?><tr><td colspan="5" class="text-center text-muted py-4">Aún no hay puntos acumulados</td></tr><?php endif; ?></tbody></table></div></div>
  <div class="cw-card"><div class="card-h"><h6>Últimos movimientos</h6></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Fecha</th><th>Cliente</th><th>Detalle</th><th class="text-end">Puntos</th></tr></thead><tbody>
  <?php foreach ($mov as $m): ?><tr><td><?= fdate($m['created_at'], true) ?></td><td><?= e($m['cliente']) ?></td><td class="small"><?= e($m['detalle']) ?></td><td class="text-end fw-bold <?= $m['puntos'] < 0 ? 'text-danger' : 'text-success' ?>"><?= ($m['puntos'] > 0 ? '+' : '') . $m['puntos'] ?></td></tr><?php endforeach; if (!$mov): ?><tr><td colspan="4" class="text-center text-muted py-4">Sin movimientos</td></tr><?php endif; ?></tbody></table></div></div>
 </div></div>
