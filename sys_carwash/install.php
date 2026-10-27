<?php
/** Instalador: crea la base de datos sys_carwash, tablas, datos base y usuario administrador. */
require __DIR__ . '/app/config/config.php';
require __DIR__ . '/app/core/Sql.php';
$lock = ROOT_PATH . '/storage/installed.lock';
$msg = ''; $ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4', DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE `' . DB_NAME . '`');
        Sql::runFile($pdo, ROOT_PATH . '/database/schema.sql');
        Sql::runFile($pdo, ROOT_PATH . '/database/seed_base.sql');
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $st = $pdo->prepare("INSERT INTO usuarios (nombre,usuario,email,password,rol_id) VALUES ('Administrador','admin','admin@carwash.local',?,1)
                             ON DUPLICATE KEY UPDATE usuario=usuario");
        $st->execute([$hash]);
        if (!empty($_POST['demo'])) Sql::runFile($pdo, ROOT_PATH . '/database/seed_demo.sql');
        if (!is_dir(dirname($lock))) mkdir(dirname($lock), 0777, true);
        file_put_contents($lock, date('c'));
        $ok = true;
    } catch (Throwable $e) {
        $msg = $e->getMessage();
    }
}
?><!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Instalación - CarWash Pro</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>body{min-height:100vh;display:grid;place-items:center;background:linear-gradient(135deg,#e0f2fe,#ecfeff 60%,#f0fdfa);font-family:system-ui,sans-serif}
.card{border:0;border-radius:20px;box-shadow:0 20px 50px rgba(14,165,233,.15);max-width:520px;width:100%}
.logo{width:64px;height:64px;border-radius:18px;background:linear-gradient(135deg,#0ea5e9,#14b8a6);color:#fff;display:grid;place-items:center;font-size:30px}</style></head>
<body><div class="card p-4 p-md-5 m-3">
<div class="text-center mb-4"><div class="logo mx-auto mb-3"><i class="bi bi-droplet-half"></i></div>
<h4 class="fw-bold mb-1">Instalación de CarWash Pro</h4><div class="text-muted">Base de datos: <b><?= DB_NAME ?></b> en <?= DB_HOST ?>:<?= DB_PORT ?></div></div>
<?php if ($ok): ?>
  <div class="alert alert-success"><i class="bi bi-check-circle me-1"></i> Instalación completada.<br>Usuario: <b>admin</b> &nbsp; Contraseña: <b>admin123</b></div>
  <a class="btn btn-primary w-100 rounded-pill py-2" href="index.php">Ir al sistema</a>
<?php elseif (is_file($lock)): ?>
  <div class="alert alert-info">El sistema ya está instalado. Elimina <code>storage/installed.lock</code> para reinstalar (los datos existentes se conservan).</div>
  <a class="btn btn-primary w-100 rounded-pill py-2" href="index.php">Ir al sistema</a>
<?php else: ?>
  <?php if ($msg): ?><div class="alert alert-danger"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <p class="text-muted">Se creará la base de datos, las tablas, los roles, y el usuario administrador.</p>
  <form method="post">
    <div class="form-check form-switch mb-4"><input class="form-check-input" type="checkbox" name="demo" value="1" id="demo" checked>
      <label class="form-check-label" for="demo">Cargar datos de ejemplo (servicios, empleados, productos)</label></div>
    <button class="btn btn-primary w-100 rounded-pill py-2"><i class="bi bi-rocket-takeoff me-1"></i> Instalar ahora</button>
  </form>
<?php endif; ?>
</div></body></html>
