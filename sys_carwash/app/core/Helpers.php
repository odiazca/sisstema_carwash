<?php
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
function asset(string $path): string { return BASE_URL . '/public/' . ltrim($path, '/'); }

function empresa(bool $refresh = false): array {
    static $cfg = null;
    if ($cfg === null || $refresh) {
        try { $cfg = Database::row('SELECT * FROM empresa_config WHERE id=1') ?: []; }
        catch (Throwable $e) { $cfg = []; }
        $cfg += [
            'nombre' => 'Mi Carwash', 'moneda_simbolo' => 'S/', 'simbolo_posicion' => 'antes', 'decimales' => 2,
            'sep_decimal' => '.', 'sep_miles' => ',', 'formato_fecha' => 'd/m/Y', 'zona_horaria' => 'America/Lima',
            'impuesto_nombre' => 'IGV', 'impuesto_porcentaje' => 18, 'precios_incluyen_impuesto' => 1,
            'prefijo_orden' => 'OS-', 'logo' => null,
        ];
    }
    return $cfg;
}

function num($n, ?int $dec = null): string {
    $c = empresa();
    return number_format((float)$n, $dec ?? (int)$c['decimales'], $c['sep_decimal'], $c['sep_miles']);
}
function money($n): string {
    $c = empresa();
    $v = num($n);
    return $c['simbolo_posicion'] === 'antes' ? $c['moneda_simbolo'] . ' ' . $v : $v . ' ' . $c['moneda_simbolo'];
}
function fdate($d, bool $time = false): string {
    if (!$d) return '-';
    $t = strtotime($d);
    return date(empresa()['formato_fecha'] . ($time ? ' H:i' : ''), $t);
}
function flash(string $type, string $msg): void { $_SESSION['flash'][] = ['t' => $type, 'm' => $msg]; }
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['_csrf'] ?? '')) {
        http_response_code(419);
        exit('Sesión expirada o petición inválida (CSRF). Recarga la página.');
    }
}
function auth_user(): ?array { return $_SESSION['user'] ?? null; }
function can(string $modulo): bool {
    $u = auth_user();
    if (!$u) return false;
    $p = $u['permisos'] ?? '';
    if ($p === '*') return true;
    return in_array($modulo, array_map('trim', explode(',', $p)), true);
}
function log_action(string $accion, string $detalle = ''): void {
    try {
        $u = auth_user();
        Database::q('INSERT INTO bitacora (usuario_id,usuario,accion,detalle,ip) VALUES (?,?,?,?,?)',
            [$u['id'] ?? null, $u['usuario'] ?? null, $accion, mb_substr($detalle, 0, 250), $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Throwable $e) {}
}
function estado_badge(string $e): string {
    $map = ['pendiente' => 'warning', 'en_proceso' => 'info', 'terminado' => 'primary', 'entregado' => 'success', 'cancelado' => 'danger',
            'programada' => 'warning', 'confirmada' => 'info', 'atendida' => 'success', 'cancelada' => 'danger', 'abierta' => 'success', 'cerrada' => 'secondary'];
    $c = $map[$e] ?? 'secondary';
    return '<span class="badge badge-soft-' . $c . '">' . e(ucfirst(str_replace('_', ' ', $e))) . '</span>';
}
function format_bytes(int $b): string {
    $u = ['B', 'KB', 'MB', 'GB']; $i = 0;
    while ($b >= 1024 && $i < 3) { $b /= 1024; $i++; }
    return round($b, 1) . ' ' . $u[$i];
}
