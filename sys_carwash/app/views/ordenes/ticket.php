<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Ticket <?= e($o['codigo']) ?></title>
<style>body{font-family:'Courier New',monospace;font-size:12px;width:76mm;margin:0 auto;padding:6px;color:#000}
h2,h3,p{margin:2px 0;text-align:center}hr{border:0;border-top:1px dashed #000;margin:6px 0}table{width:100%;border-collapse:collapse}td{padding:1px 0;vertical-align:top}.r{text-align:right}.b{font-weight:bold}
img{max-width:60px;display:block;margin:0 auto 4px}@media print{@page{margin:0}}</style></head>
<body onload="window.print()">
<?php if (!empty($emp['logo'])): ?><img src="<?= asset('uploads/' . e($emp['logo'])) ?>"><?php endif; ?>
<h2><?= e($emp['nombre']) ?></h2>
<?php if (!empty($emp['ruc'])): ?><p>RUC: <?= e($emp['ruc']) ?></p><?php endif; ?>
<p><?= e($emp['direccion'] ?? '') ?></p><p><?= e($emp['telefono'] ?? '') ?></p><hr>
<p class="b"><?= e($o['codigo']) ?></p><p><?= fdate($o['fecha_ingreso'], true) ?></p><hr>
<div>Cliente: <?= e($o['cliente'] ?? 'Cliente general') ?></div><div>Placa: <?= e($o['placa'] ?? '-') ?></div><hr>
<table><?php foreach ($o['items'] as $d): ?><tr><td colspan="2"><?= e($d['descripcion']) ?></td></tr><tr><td><?= num($d['cantidad'], 0) ?> x <?= num($d['precio']) ?></td><td class="r"><?= num($d['subtotal']) ?></td></tr><?php endforeach; ?></table><hr>
<table><tr><td>Subtotal</td><td class="r"><?= money($o['subtotal']) ?></td></tr>
<?php if ($o['descuento'] > 0): ?><tr><td>Descuento</td><td class="r">-<?= money($o['descuento']) ?></td></tr><?php endif; ?>
<tr><td><?= e($emp['impuesto_nombre']) ?></td><td class="r"><?= money($o['impuesto']) ?></td></tr>
<tr class="b"><td>TOTAL</td><td class="r"><?= money($o['total']) ?></td></tr></table><hr>
<?php foreach ($o['pagos'] as $x): if ($x['anulado']) continue; ?><div><?= e(strtoupper(str_replace('_', '/', $x['metodo']))) ?>: <?= money($x['monto']) ?></div><?php endforeach; ?>
<p><?= $o['pagado'] ? 'PAGADO' : ($o['abonado'] > 0 ? 'ABONADO ' . money($o['abonado']) . ' - SALDO ' . money($o['total'] - $o['abonado']) : 'PENDIENTE DE PAGO') ?></p>
<p><?= e($emp['pie_ticket'] ?? '') ?></p>
</body></html>
