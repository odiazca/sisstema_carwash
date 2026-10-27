<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Iniciar sesión - <?= e($emp['nombre']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= asset('css/app.css') ?>" rel="stylesheet"></head>
<body>
<div class="login-wrap">
  <div class="login-hero">
    <?php for ($i = 0; $i < 14; $i++): $s = rand(20, 90); ?>
      <span class="bubble" style="left:<?= rand(0, 95) ?>%;width:<?= $s ?>px;height:<?= $s ?>px;animation-duration:<?= rand(9, 22) ?>s;animation-delay:-<?= rand(0, 15) ?>s"></span>
    <?php endfor; ?>
    <div style="position:relative;z-index:2;max-width:520px">
      <div class="d-inline-flex align-items-center gap-2 bg-white bg-opacity-25 rounded-pill px-3 py-2 mb-4 fw-bold"><i class="bi bi-stars"></i> Gestión profesional de Carwash</div>
      <h1>Cada auto brilla,<br>cada venta cuenta.</h1>
      <p class="fs-6 opacity-75 mt-3">Controla órdenes de servicio, clientes, caja, inventario y reportes desde un solo lugar.</p>
      <div class="feat"><i class="bi bi-receipt"></i> Órdenes y tickets en segundos</div>
      <div class="feat"><i class="bi bi-graph-up-arrow"></i> Dashboard con estadísticas en vivo</div>
      <div class="feat"><i class="bi bi-shield-check"></i> Respaldos y restauración seguros</div>
    </div>
  </div>
  <div class="login-form-side">
    <div class="login-box">
      <div class="text-center mb-4">
        <div class="sb-logo mx-auto mb-3" style="width:70px;height:70px;border-radius:22px;font-size:2rem">
          <?php if (!empty($emp['logo'])): ?><img src="<?= asset('uploads/' . e($emp['logo'])) ?>" alt="logo"><?php else: ?><i class="bi bi-droplet-half"></i><?php endif; ?>
        </div>
        <h3 class="fw-800 fw-bold mb-1"><?= e($emp['nombre']) ?></h3>
        <div class="text-muted">Ingresa a tu cuenta para continuar</div>
      </div>
      <?php if ($error): ?><div class="alert alert-danger py-2"><i class="bi bi-exclamation-circle me-1"></i><?= e($error) ?></div><?php endif; ?>
      <form method="post" autocomplete="off">
        <?= csrf_field() ?>
        <div class="mb-3"><label class="form-label">Usuario</label>
          <div class="input-ico"><i class="bi bi-person"></i><input class="form-control" name="usuario" placeholder="Tu usuario" required autofocus></div></div>
        <div class="mb-3"><label class="form-label">Contraseña</label>
          <div class="input-ico"><i class="bi bi-lock"></i><input id="pw" type="password" class="form-control" name="password" placeholder="••••••••" required>
          <button type="button" class="toggle-pass" data-target="#pw"><i class="bi bi-eye"></i></button></div></div>
        <button class="btn btn-grad w-100 py-2 mt-2 fs-6">Iniciar sesión <i class="bi bi-arrow-right ms-1"></i></button>
      </form>
      <div class="text-center text-muted small mt-4">© <?= date('Y') ?> <?= e(APP_NAME) ?> · v<?= APP_VERSION ?></div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body></html>
