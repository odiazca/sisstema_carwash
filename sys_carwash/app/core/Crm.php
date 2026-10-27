<?php
/** Núcleo del CRM: configuración, segmentación, puntos de fidelidad, cupones y utilidades de mensajería. */
const CRM_SEGS = [
    'vip'        => ['VIP', 'bi-gem', '#8b5cf6', 'Clientes de alto valor (muchas visitas o gasto elevado)'],
    'frecuente'  => ['Frecuente', 'bi-heart-fill', '#14b8a6', 'Vienen con regularidad'],
    'nuevo'      => ['Nuevo', 'bi-stars', '#0ea5e9', 'Primera visita reciente'],
    'ocasional'  => ['Ocasional', 'bi-person', '#64748b', 'Visitan de vez en cuando'],
    'en_riesgo'  => ['En riesgo', 'bi-exclamation-triangle-fill', '#f59e0b', 'Llevan tiempo sin venir: conviene contactarlos'],
    'inactivo'   => ['Inactivo', 'bi-moon-stars-fill', '#f43f5e', 'Muy tiempo sin visitas'],
    'sin_visitas'=> ['Sin visitas', 'bi-person-dash', '#94a3b8', 'Registrados que aún no han comprado'],
];
const CRM_MSG_DEF = [
    'msg_cumple' => 'Hola {nombre} 🎂 ¡Feliz cumpleaños de parte de {empresa}! Te regalamos un descuento especial: usa el cupón {cupon} en tu próximo lavado.',
    'msg_riesgo' => 'Hola {nombre}, te extrañamos en {empresa} 🚗✨ Hace tiempo que no vienes. Tienes {puntos} puntos acumulados. ¡Te esperamos!',
    'msg_encuesta' => 'Hola {nombre}, gracias por visitar {empresa}. ¿Nos regalas 1 minuto para calificar tu servicio? {enlace}',
];

function crm_cfg(bool $refresh = false): array {
    static $c = null;
    if ($c === null || $refresh) {
        try { $c = Database::row('SELECT * FROM crm_config WHERE id=1') ?: []; } catch (Throwable $e) { $c = []; }
        $c += ['programa_activo' => 1, 'monto_por_punto' => 10, 'valor_punto' => 0.5, 'puntos_minimo' => 50, 'puntos_desde' => date('Y-m-d'), 'vip_visitas' => 10,
               'vip_monto' => 1000, 'dias_riesgo' => 45, 'dias_inactivo' => 90, 'prefijo_pais' => '51'];
        foreach (CRM_MSG_DEF as $k => $v) if (empty($c[$k])) $c[$k] = $v;
    }
    return $c;
}

function crm_segmento(array $r, array $cfg): string {
    $n = (int)$r['visitas'];
    if ($n === 0) return 'sin_visitas';
    $dias = (int)$r['dias'];
    if ($dias > (int)$cfg['dias_inactivo']) return 'inactivo';
    if ($dias > (int)$cfg['dias_riesgo']) return 'en_riesgo';
    if ($n >= (int)$cfg['vip_visitas'] || (float)$r['gastado'] >= (float)$cfg['vip_monto']) return 'vip';
    if ($n >= 3) return 'frecuente';
    if ($n === 1 && $dias <= 30) return 'nuevo';
    return 'ocasional';
}

/** Otorga puntos por órdenes cobradas aún no registradas (idempotente). */
function crm_sync_puntos(): void {
    $c = crm_cfg();
    if (!$c['programa_activo'] || (float)$c['monto_por_punto'] <= 0) return;
    try {
        Database::q("INSERT IGNORE INTO crm_puntos (cliente_id,orden_id,tipo,puntos,detalle)
            SELECT o.cliente_id, o.id, 'ganado', FLOOR(o.total / ?), CONCAT('Orden ', o.codigo)
            FROM ordenes o WHERE o.cliente_id IS NOT NULL AND o.pagado=1 AND o.estado<>'cancelado' AND DATE(o.fecha_pago) >= ? AND FLOOR(o.total / ?) > 0",
            [$c['monto_por_punto'], $c['puntos_desde'] ?: '2000-01-01', $c['monto_por_punto']]);
    } catch (Throwable $e) {}
}

/** Saldo de puntos por cliente (excluye puntos de órdenes anuladas). */
function crm_saldos(?int $cid = null): array {
    $sql = "SELECT p.cliente_id, SUM(p.puntos) s FROM crm_puntos p LEFT JOIN ordenes o ON o.id=p.orden_id
            WHERE (p.orden_id IS NULL OR (o.estado<>'cancelado' AND o.pagado=1))" . ($cid ? ' AND p.cliente_id=' . (int)$cid : '') . ' GROUP BY p.cliente_id';
    $out = [];
    foreach (Database::all($sql) as $r) $out[(int)$r['cliente_id']] = max(0, (int)$r['s']);
    return $out;
}

/** Todos los clientes activos con métricas, segmento, puntos y próximo cumpleaños. */
function crm_clientes(?string $seg = null): array {
    $cfg = crm_cfg();
    crm_sync_puntos();
    $rows = Database::all("SELECT c.id,c.nombre,c.telefono,c.email,c.fecha_nacimiento,c.acepta_marketing,c.created_at,
        COALESCE(x.visitas,0) visitas, COALESCE(x.gastado,0) gastado, x.ultima,
        IF(x.ultima IS NULL, NULL, DATEDIFF(CURDATE(), DATE(x.ultima))) dias
        FROM clientes c LEFT JOIN (SELECT cliente_id, COUNT(*) visitas, SUM(total) gastado, MAX(fecha_ingreso) ultima FROM ordenes WHERE estado<>'cancelado' GROUP BY cliente_id) x ON x.cliente_id=c.id
        WHERE c.estado=1 ORDER BY c.nombre");
    $sal = crm_saldos();
    $out = [];
    foreach ($rows as $r) {
        $r['segmento'] = crm_segmento($r, $cfg);
        if ($seg && $seg !== 'todos' && $r['segmento'] !== $seg) continue;
        $r['puntos'] = $sal[(int)$r['id']] ?? 0;
        $r['cumple_en'] = crm_dias_cumple($r['fecha_nacimiento']);
        $out[] = $r;
    }
    return $out;
}

function crm_dias_cumple(?string $fn): ?int {
    if (!$fn || $fn === '0000-00-00') return null;
    $t = strtotime($fn); if (!$t) return null;
    $hoy = new DateTime('today');
    $y = (int)$hoy->format('Y');
    $m = (int)date('n', $t); $d = (int)date('j', $t);
    if ($m === 2 && $d === 29 && !checkdate(2, 29, $y)) $d = 28;
    $next = new DateTime(sprintf('%04d-%02d-%02d', $y, $m, $d));
    if ($next < $hoy) { $y++; $dd = ($m === 2 && $d === 29 && !checkdate(2, 29, $y)) ? 28 : $d; $next = new DateTime(sprintf('%04d-%02d-%02d', $y, $m, $dd)); }
    return (int)$hoy->diff($next)->days;
}

function crm_tel(?string $tel): string {
    $d = preg_replace('/\D+/', '', (string)$tel);
    if ($d === '') return '';
    $p = preg_replace('/\D+/', '', crm_cfg()['prefijo_pais'] ?? '');
    if ($p !== '' && strlen($d) <= 9) $d = $p . ltrim($d, '0');
    return $d;
}
function crm_wa(?string $tel, string $msg): string {
    $d = crm_tel($tel);
    return $d === '' ? '' : 'https://wa.me/' . $d . '?text=' . rawurlencode($msg);
}
function crm_msg(string $tpl, array $c, array $extra = []): string {
    $rep = ['{nombre}' => explode(' ', trim($c['nombre'] ?? ''))[0] ?: 'cliente', '{empresa}' => empresa()['nombre'], '{puntos}' => (string)($c['puntos'] ?? 0),
            '{ultima_visita}' => !empty($c['ultima']) ? fdate($c['ultima']) : '-', '{cupon}' => '', '{enlace}' => ''] + [];
    foreach ($extra as $k => $v) $rep['{' . $k . '}'] = $v;
    return strtr($tpl, $rep);
}

function crm_codigo(string $pref = 'CW'): string {
    $abc = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $c = $pref . '-';
        for ($i = 0; $i < 6; $i++) $c .= $abc[random_int(0, strlen($abc) - 1)];
    } while (Database::val('SELECT COUNT(*) FROM crm_cupones WHERE codigo=?', [$c]));
    return $c;
}

/** Valida un cupón para un cliente y subtotal. Devuelve [ok, mensaje, descuento, cupon]. */
function crm_validar_cupon(string $codigo, ?int $clienteId, float $subtotal): array {
    $codigo = strtoupper(trim($codigo));
    if ($codigo === '') return [false, 'Ingresa un código.', 0, null];
    $c = Database::row('SELECT * FROM crm_cupones WHERE codigo=?', [$codigo]);
    if (!$c || !$c['estado']) return [false, 'Cupón no válido.', 0, null];
    if ($c['vigente_hasta'] && $c['vigente_hasta'] < date('Y-m-d')) return [false, 'El cupón está vencido.', 0, null];
    if ((int)$c['usos_max'] > 0 && (int)$c['usos'] >= (int)$c['usos_max']) return [false, 'El cupón ya fue utilizado.', 0, null];
    if ($c['cliente_id'] && (int)$c['cliente_id'] !== (int)$clienteId) return [false, 'Este cupón pertenece a otro cliente (elige al cliente en la orden).', 0, null];
    $d = $c['tipo'] === 'porcentaje' ? round($subtotal * min(100, (float)$c['valor']) / 100, 2) : min($subtotal, (float)$c['valor']);
    return [true, 'Cupón aplicado: -' . ($c['tipo'] === 'porcentaje' ? num($c['valor'], 0) . '%' : money($c['valor'])), $d, $c];
}
