<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Reporte <?= e($d) ?> a <?= e($h) ?></title>
<style>*{box-sizing:border-box}body{font-family:'Segoe UI',Arial,sans-serif;color:#1e293b;margin:0;padding:28px;font-size:13px}
.hd{display:flex;align-items:center;gap:16px;border-bottom:3px solid #0ea5e9;padding-bottom:14px;margin-bottom:18px}.hd img{height:56px}.hd h1{margin:0;font-size:22px}.hd small{color:#64748b}
.kpis{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:18px}.kpi{border:1px solid #e2e8f0;border-radius:10px;padding:10px}.kpi b{display:block;font-size:17px;margin-top:3px;color:#0284c7}
h2{font-size:14px;margin:18px 0 6px;color:#0284c7;text-transform:uppercase;letter-spacing:.04em}table{width:100%;border-collapse:collapse}th,td{padding:6px 8px;border-bottom:1px solid #e2e8f0;text-align:left}th{background:#f1f7fd;font-size:11px;text-transform:uppercase}.r{text-align:right}
.cols{display:grid;grid-template-columns:1fr 1fr;gap:20px}.bar{height:8px;background:linear-gradient(90deg,#0ea5e9,#14b8a6);border-radius:4px}
.noprint{margin-bottom:14px}@media print{.noprint{display:none}body{padding:10px}@page{margin:12mm}}</style></head>
<body onload="setTimeout(()=>window.print(),400)">
<div class="noprint"><button onclick="print()">🖨 Imprimir / Guardar como PDF</button></div>
<div class="hd"><?php if (!empty($emp['logo'])): ?><img src="<?= asset('uploads/' . e($emp['logo'])) ?>"><?php endif; ?>
<div><h1><?= e($emp['nombre']) ?></h1><small><?= e($emp['ruc'] ? 'RUC ' . $emp['ruc'] . ' · ' : '') ?><?= e($emp['direccion'] ?? '') ?></small><br><small>Reporte de ventas del <?= fdate($d) ?> al <?= fdate($h) ?> · Generado <?= fdate(date('Y-m-d H:i:s'), true) ?></small></div></div>
<div class="kpis"><?php foreach ([['Ingresos', $r['ingresos']], ['Órdenes cobradas', null, $r['ordenes']], ['Ticket promedio', $r['promedio']], ['Gastos', $r['gastos']], ['Utilidad', $r['utilidad']]] as $k): ?>
<div class="kpi"><?= $k[0] ?><b><?= $k[1] !== null ? money($k[1]) : $k[2] ?></b></div><?php endforeach; ?></div>
<h2>Ventas por día</h2><table><thead><tr><th>Fecha</th><th class="r">Órdenes</th><th class="r">Total</th><th style="width:30%"></th></tr></thead><tbody>
<?php $max = max(array_merge([1], array_map(fn($x) => (float)$x['t'], $porDia))); foreach ($porDia as $x): ?><tr><td><?= fdate($x['d']) ?></td><td class="r"><?= $x['n'] ?></td><td class="r"><b><?= money($x['t']) ?></b></td><td><div class="bar" style="width:<?= round($x['t'] / $max * 100) ?>%"></div></td></tr><?php endforeach; ?>
<?php if (!$porDia): ?><tr><td colspan="4">Sin ventas en el periodo</td></tr><?php endif; ?></tbody></table>
<div class="cols"><div><h2>Servicios y productos</h2><table><thead><tr><th>Ítem</th><th class="r">Cant.</th><th class="r">Ventas</th></tr></thead><tbody><?php foreach ($servicios as $s): ?><tr><td><?= e($s['n']) ?></td><td class="r"><?= num($s['c'], 0) ?></td><td class="r"><?= money($s['t']) ?></td></tr><?php endforeach; ?></tbody></table></div>
<div><h2>Métodos de pago</h2><table><thead><tr><th>Método</th><th class="r">Operaciones</th><th class="r">Total</th></tr></thead><tbody><?php foreach ($pagos as $x): ?><tr><td><?= e(OrdenesController::PAGOS[$x['m']] ?? $x['m']) ?></td><td class="r"><?= $x['n'] ?></td><td class="r"><?= money($x['t']) ?></td></tr><?php endforeach; ?></tbody></table>
<h2>Gastos por categoría</h2><table><tbody><?php foreach ($gastos as $x): ?><tr><td><?= e($x['n']) ?></td><td class="r"><?= money($x['t']) ?></td></tr><?php endforeach; if (!$gastos): ?><tr><td>Sin gastos</td></tr><?php endif; ?></tbody></table></div></div>
</body></html>
