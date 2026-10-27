<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Boleta de pago <?= e($p['periodo']) ?> - <?= e($p['nombre']) ?></title>
<style>body{font-family:Arial,sans-serif;font-size:13px;max-width:640px;margin:20px auto;color:#111}h1{font-size:18px;margin:0}table{width:100%;border-collapse:collapse;margin-top:10px}td,th{padding:6px 8px;border:1px solid #ccc;text-align:left}.r{text-align:right}th{background:#e0f2fe}.tot td{font-weight:bold;font-size:15px;background:#f0f9ff}.sig{display:flex;justify-content:space-between;margin-top:60px;text-align:center}.sig div{width:44%;border-top:1px solid #000;padding-top:4px}@media print{@page{margin:12mm}}</style></head>
<body onload="window.print()"><h1><?= e($emp['nombre']) ?></h1><div><?= e($emp['ruc'] ?? '') ? 'RUC: ' . e($emp['ruc']) : '' ?></div><h3 style="margin:14px 0 4px">BOLETA DE PAGO · <?= e($p['periodo']) ?></h3>
<div><b>Empleado:</b> <?= e($p['nombre']) ?> · <b>Cargo:</b> <?= e($p['cargo']) ?><?= $p['documento'] ? ' · <b>Doc.:</b> ' . e($p['documento']) : '' ?></div>
<table><tr><th>Concepto</th><th class="r">Importe</th></tr>
<tr><td>Sueldo base</td><td class="r"><?= money($p['sueldo']) ?></td></tr>
<tr><td>Descuento por faltas (<?= (int)$p['faltas'] ?> día/s)</td><td class="r">- <?= money($p['desc_faltas']) ?></td></tr>
<tr><td>Descuento por tardanzas (<?= (int)$p['min_tarde'] ?> min)</td><td class="r">- <?= money($p['desc_tardanza']) ?></td></tr>
<tr><td>Adelantos descontados</td><td class="r">- <?= money($p['adelantos']) ?></td></tr>
<tr class="tot"><td>NETO PAGADO</td><td class="r"><?= money($p['neto']) ?></td></tr></table>
<?php if ($p['comisiones'] > 0): ?><p style="font-size:12px">Comisiones liquidadas en el periodo (pagadas por separado): <b><?= money($p['comisiones']) ?></b></p><?php endif; ?>
<p style="font-size:11px;color:#555">Emitida el <?= fdate($p['created_at'], true) ?></p>
<div class="sig"><div>Firma del empleado</div><div>Empleador</div></div></body></html>
