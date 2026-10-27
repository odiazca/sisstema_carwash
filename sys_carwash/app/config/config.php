<?php
// Configuración general
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sys_carwash');
define('DB_PORT', 3306);

define('APP_NAME', 'CarWash Pro');
define('APP_VERSION', '1.7');
define('APP_DEBUG', true);          // poner en false en producción
define('SESSION_IDLE', 7200);       // segundos de inactividad (2 h)
define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH', ROOT_PATH . '/app');
define('BACKUP_PATH', ROOT_PATH . '/storage/backups');
define('UPLOAD_PATH', ROOT_PATH . '/public/uploads');

$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
define('BASE_URL', $base);
