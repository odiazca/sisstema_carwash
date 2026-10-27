<?php
/** Actualizador: aplica database/schema.sql (idempotente, solo crea tablas faltantes). Seguro de ejecutar varias veces. */
require __DIR__ . '/app/config/config.php';
require __DIR__ . '/app/core/Sql.php';
header('Content-Type: text/html; charset=utf-8');
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $n = Sql::runFile($pdo, ROOT_PATH . '/database/schema.sql');
    $idx = [['ordenes', 'idx_fecha_pago', 'fecha_pago'], ['ordenes', 'idx_cliente', 'cliente_id'], ['gastos', 'idx_fecha', 'fecha'], ['citas', 'idx_fecha_hora', 'fecha_hora'],
            ['clientes', 'idx_nombre', 'nombre'], ['vehiculos', 'idx_placa', 'placa'], ['bitacora', 'idx_created', 'created_at'], ['movimientos_inventario', 'idx_created', 'created_at']];
    foreach ($idx as [$t, $name, $col]) {
        $ex = $pdo->prepare('SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=? AND table_name=? AND index_name=?');
        $ex->execute([DB_NAME, $t, $name]);
        if (!$ex->fetchColumn()) $pdo->exec("ALTER TABLE `$t` ADD INDEX `$name` (`$col`)");
    }
    foreach ([['clientes', 'fecha_nacimiento', 'DATE NULL'], ['clientes', 'acepta_marketing', 'TINYINT(1) NOT NULL DEFAULT 1'], ['ordenes', 'tipo', "ENUM('servicio','venta') NOT NULL DEFAULT 'servicio'"], ['compras', 'condicion', "ENUM('contado','credito') NOT NULL DEFAULT 'contado'"], ['compras', 'vence', 'DATE NULL'], ['compras', 'pagado', 'DECIMAL(12,2) NOT NULL DEFAULT 0']] as [$t, $col, $def]) {
        $ex = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=? AND table_name=? AND column_name=?');
        $ex->execute([DB_NAME, $t, $col]);
        if (!$ex->fetchColumn()) $pdo->exec("ALTER TABLE `$t` ADD COLUMN `$col` $def");
    }
    $pdo->exec("INSERT INTO orden_pagos (orden_id,monto,metodo,fecha)
        SELECT o.id, o.total, COALESCE(o.metodo_pago,'efectivo'), COALESCE(o.fecha_pago,o.fecha_ingreso) FROM ordenes o
        WHERE o.pagado=1 AND o.total>0 AND NOT EXISTS (SELECT 1 FROM orden_pagos p WHERE p.orden_id=o.id)");
    $pdo->exec("UPDATE compras SET pagado=total WHERE condicion='contado' AND pagado<total");
    $pdo->exec('INSERT INTO crm_config (id,puntos_desde) VALUES (1,CURDATE()) ON DUPLICATE KEY UPDATE id=id');
    echo "<p style='font-family:sans-serif'>✅ Base de datos actualizada ($n sentencias). Tus datos no se modificaron.</p><p><a href='index.php'>Ir al sistema</a></p>";
} catch (Throwable $e) { echo '<pre>Error: ' . htmlspecialchars($e->getMessage()) . '</pre>'; }
