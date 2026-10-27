<?php
require __DIR__ . '/app/config/config.php';
ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
// Cierre de sesión por inactividad
if (!empty($_SESSION['user'])) {
    if (time() - ($_SESSION['last'] ?? time()) > SESSION_IDLE) { $_SESSION = []; session_destroy(); session_start(); $_SESSION['flash'][] = ['t' => 'warning', 'm' => 'Tu sesión expiró por inactividad.']; }
    $_SESSION['last'] = time();
}
require APP_PATH . '/core/Database.php';
require APP_PATH . '/core/Helpers.php';
require APP_PATH . '/core/Crm.php';
require APP_PATH . '/core/Pagos.php';
require APP_PATH . '/core/Xlsx.php';
require APP_PATH . '/core/Controller.php';
require APP_PATH . '/core/CrudController.php';
require APP_PATH . '/core/App.php';
spl_autoload_register(function ($c) { $f = APP_PATH . '/controllers/' . $c . '.php'; if (preg_match('/^[A-Za-z]+Controller$/', $c) && is_file($f)) require_once $f; });

if (!is_file(ROOT_PATH . '/storage/installed.lock') && !str_starts_with(trim($_GET['url'] ?? '', '/'), 'install')) {
    header('Location: ' . BASE_URL . '/install.php'); exit;
}
date_default_timezone_set(empresa()['zona_horaria'] ?? 'America/Lima');
try {
    (new App())->run();
} catch (PDOException $e) {
    http_response_code(500);
    echo '<pre style="padding:20px;font-family:monospace">Error de base de datos' . (APP_DEBUG ? ': ' . e($e->getMessage()) : '. Revisa la conexión en app/config/config.php') . '</pre>';
}
