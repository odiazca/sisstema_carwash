<?php
/** Pagos de órdenes: abonos parciales, varios métodos, propina y saldo. */
function orden_abonado(int $oid): float {
    return (float)Database::val('SELECT COALESCE(SUM(monto),0) FROM orden_pagos WHERE orden_id=? AND anulado=0', [$oid]);
}
function orden_saldo(array $o): float {
    return max(0, round((float)$o['total'] - orden_abonado((int)$o['id']), 2));
}
/** Registra un pago (se limita al saldo). Cierra la orden como pagada cuando el saldo llega a 0. */
function orden_registrar_pago(array $o, float $monto, string $metodo, float $propina = 0, ?string $ref = null, ?int $uid = null): array {
    $oid = (int)$o['id'];
    if (!in_array($metodo, ['efectivo', 'tarjeta', 'yape_plin', 'transferencia'], true)) $metodo = 'efectivo';
    $saldo = orden_saldo($o);
    $monto = round(min(max(0, $monto), $saldo), 2); $propina = round(max(0, $propina), 2);
    if ($monto <= 0 && $propina <= 0) return ['monto' => 0, 'saldo' => $saldo, 'pagado' => $saldo <= 0.004];
    Database::q('INSERT INTO orden_pagos (orden_id,monto,propina,metodo,referencia,usuario_id) VALUES (?,?,?,?,?,?)', [$oid, $monto, $propina, $metodo, $ref ? mb_substr($ref, 0, 80) : null, $uid]);
    $nuevo = max(0, round($saldo - $monto, 2));
    if ($nuevo <= 0.004) {
        $princ = Database::val('SELECT metodo FROM orden_pagos WHERE orden_id=? AND anulado=0 GROUP BY metodo ORDER BY SUM(monto) DESC LIMIT 1', [$oid]) ?: $metodo;
        Database::q('UPDATE ordenes SET pagado=1, metodo_pago=?, fecha_pago=NOW() WHERE id=?', [$princ, $oid]);
    }
    return ['monto' => $monto, 'saldo' => $nuevo, 'pagado' => $nuevo <= 0.004];
}
