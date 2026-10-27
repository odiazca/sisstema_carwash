<?php
$emp = empresa();
$cur = strtolower(trim($_GET['url'] ?? '', '/')) ?: 'dashboard';
$curCtrl = explode('/', $cur)[0] ?: 'dashboard';
$menu = [
  'Principal' => [['dashboard', 'dashboard', 'bi-speedometer2', 'Dashboard']],
  'Operaciones' => [
    ['ordenes', 'ordenes/nueva', 'bi-plus-circle-fill', 'Nueva orden'],
    ['ordenes', 'ventas', 'bi-basket3', 'Venta rápida (POS)'],
    ['ordenes', 'cotizaciones', 'bi-file-earmark-text', 'Cotizaciones'],
    ['ordenes', 'recepciones', 'bi-clipboard2-check', 'Recepción de vehículos'],
    ['ordenes', 'tablero', 'bi-activity', 'Tablero en vivo'],
    ['ordenes', 'ordenes', 'bi-card-checklist', 'Órdenes de servicio'],
    ['citas', 'citas', 'bi-calendar-check', 'Citas / Reservas'],
    ['citas', 'citas/calendario', 'bi-calendar3', 'Calendario'],
    ['caja', 'caja', 'bi-cash-coin', 'Caja'],
  ],
  'Clientes' => [
    ['clientes', 'clientes', 'bi-people', 'Clientes'],
    ['vehiculos', 'vehiculos', 'bi-car-front', 'Vehículos'],
    ['clientes', 'paquetes', 'bi-gift', 'Paquetes y membresías'],
    ['clientes', 'paquetes/vendidos', 'bi-bag-check', 'Paquetes vendidos'],
  ],
  'CRM' => [
    ['clientes', 'crm', 'bi-heart-pulse', 'Panel CRM'],
    ['clientes', 'crm/segmentos', 'bi-pie-chart', 'Segmentos'],
    ['clientes', 'crm/seguimientos', 'bi-telephone-outbound', 'Seguimientos'],
    ['clientes', 'crm/pipeline', 'bi-kanban', 'Oportunidades'],
    ['clientes', 'crm/fidelidad', 'bi-award', 'Fidelización'],
    ['clientes', 'crm/cupones', 'bi-ticket-perforated', 'Cupones'],
    ['clientes', 'crm/campanas', 'bi-megaphone', 'Campañas'],
    ['clientes', 'crm/encuestas', 'bi-emoji-smile', 'Encuestas'],
  ],
  'Catálogo e inventario' => [
    ['servicios', 'servicios', 'bi-droplet-half', 'Servicios'],
    ['servicios', 'categorias', 'bi-bookmarks', 'Categorías de servicio'],
    ['servicios', 'tiposvehiculo', 'bi-truck-front', 'Tipos de vehículo'],
    ['productos', 'productos', 'bi-box-seam', 'Productos / Insumos'],
    ['productos', 'inventario', 'bi-arrow-left-right', 'Movimientos de inventario'],
    ['proveedores', 'proveedores', 'bi-truck', 'Proveedores'],
    ['productos', 'compras', 'bi-bag-plus', 'Compras'],
  ],
  'Personal' => [['empleados', 'empleados', 'bi-person-badge', 'Empleados'], ['empleados', 'comisiones', 'bi-percent', 'Liquidar comisiones'], ['empleados', 'asistencia', 'bi-clock-history', 'Asistencia'], ['empleados', 'planilla', 'bi-cash-stack', 'Planilla']],
  'Finanzas' => [
    ['caja', 'comprobantes', 'bi-file-earmark-ruled', 'Comprobantes'],
    ['caja', 'cobranzas', 'bi-receipt-cutoff', 'Cuentas por cobrar'],
    ['productos', 'porpagar', 'bi-credit-card-2-front', 'Cuentas por pagar'],
    ['gastos', 'gastos', 'bi-wallet2', 'Gastos'],
    ['reportes', 'reportes', 'bi-bar-chart-line', 'Reportes'],
  ],
  'Inteligencia' => [
    ['ia', 'asistente', 'bi-stars', 'Asistente IA'],
    ['ia', 'chatia', 'bi-chat-dots', 'Chat IA'],
  ],
  'Sistema' => [
    ['usuarios', 'usuarios', 'bi-shield-lock', 'Usuarios y roles'],
    ['configuracion', 'configuracion', 'bi-gear', 'Configuración'],
    ['respaldo', 'respaldo', 'bi-database-check', 'Respaldo y restauración'],
    ['bitacora', 'bitacora', 'bi-clock-history', 'Bitácora'],
  ],
];
$bestPath = '';
foreach ($menu as $items) foreach ($items as $it) {
    if (($cur === $it[1] || str_starts_with($cur, $it[1] . '/')) && strlen($it[1]) > strlen($bestPath)) $bestPath = $it[1];
}
$u = auth_user();
?><!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title ?? 'Panel') ?> · <?= e($emp['nombre']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= asset('css/app.css') ?>" rel="stylesheet">
</head><body>
<script>try{if(localStorage.getItem("sbMini")==="1")document.body.classList.add("sb-mini")}catch(e){}</script>
<aside class="sidebar" id="sidebar">
  <div class="sb-brand">
    <div class="sb-logo"><?php if (!empty($emp['logo'])): ?><img src="<?= asset('uploads/' . e($emp['logo'])) ?>" alt=""><?php else: ?><i class="bi bi-droplet-half"></i><?php endif; ?></div>
    <div class="sb-name"><div class="t"><?= e($emp['nombre']) ?></div><small>Sistema de Carwash</small></div>
    <button class="sb-collapse d-none d-lg-grid" type="button" data-sb-collapse title="Contraer / expandir menú" aria-label="Contraer menú"><i class="bi bi-chevron-double-left"></i></button>
  </div>
  <div class="sb-search"><i class="bi bi-search"></i><input id="sbSearch" type="search" placeholder="Buscar módulo…" autocomplete="off" aria-label="Buscar módulo"><kbd>/</kbd></div>
  <nav class="sb-scroll" aria-label="Menú principal">
    <?php
    $gColor = ['Principal' => '#0ea5e9', 'Operaciones' => '#14b8a6', 'Clientes' => '#8b5cf6', 'CRM' => '#f43f5e', 'Catálogo e inventario' => '#f59e0b', 'Personal' => '#6366f1', 'Finanzas' => '#16a34a', 'Inteligencia' => '#a855f7', 'Sistema' => '#64748b'];
    foreach ($menu as $group => $items):
      $vis = array_filter($items, fn($i) => can($i[0]));
      if (!$vis) continue;
      $hasActive = false; foreach ($vis as $v) if ($v[1] === $bestPath) $hasActive = true; ?>
      <section class="sb-group" data-g="<?= e(strtolower(preg_replace('/\W+/', '-', $group))) ?>" data-active="<?= $hasActive ? 1 : 0 ?>" style="--g:<?= $gColor[$group] ?? '#0ea5e9' ?>">
        <button class="sb-title" type="button" aria-expanded="true"><span class="dot"></span><span class="tx"><?= e($group) ?></span><i class="bi bi-chevron-down"></i></button>
        <div class="sb-items"><div class="sb-inner">
        <?php foreach ($vis as [$mod, $path, $icon, $label]): $active = ($path === $bestPath); ?>
          <a href="<?= url($path) ?>" class="sb-link <?= $active ? 'active' : '' ?>" title="<?= e($label) ?>" data-label="<?= e(mb_strtolower($label)) ?>" <?= $active ? 'aria-current="page"' : '' ?>><span class="ic"><i class="bi <?= $icon ?>"></i></span><span class="tx"><?= e($label) ?></span><?= $mod === 'ia' && $path === 'asistente' ? '<span class="pill">IA</span>' : '' ?></a>
        <?php endforeach; ?>
        </div></div>
      </section>
    <?php endforeach; ?>
    <div class="sb-empty" id="sbEmpty" hidden>Ningún módulo coincide con tu búsqueda.</div>
  </nav>
  <div class="sb-foot">
    <div class="sb-user"><div class="avatar"><?= e(mb_strtoupper(mb_substr($u['nombre'], 0, 1))) ?></div><div class="tx"><b><?= e($u['nombre']) ?></b><small><?= e($u['rol']) ?></small></div>
      <a href="<?= url('auth/logout') ?>" class="sb-out" title="Cerrar sesión" aria-label="Cerrar sesión"><i class="bi bi-box-arrow-right"></i></a></div>
  </div>
</aside>
<div class="main">
  <header class="topbar">
    <button class="btn btn-light d-lg-none" data-toggle-sidebar><i class="bi bi-list fs-5"></i></button>
    <div class="d-none d-md-block">
      <div class="fw-bold"><?= e($title ?? '') ?></div>
      <div class="text-muted small"><i class="bi bi-calendar3 me-1"></i><?= fdate(date('Y-m-d')) ?></div>
    </div>
    <div class="ms-auto d-flex align-items-center gap-3">
      <?php if (can('ordenes')): ?><a href="<?= url('ordenes/nueva') ?>" class="btn btn-grad btn-sm px-3 d-none d-sm-inline-flex"><i class="bi bi-plus-lg me-1"></i> Nueva orden</a><?php endif; ?>
      <div class="dropdown">
        <a class="d-flex align-items-center gap-2 text-decoration-none text-dark" data-bs-toggle="dropdown" href="#">
          <div class="avatar"><?= e(mb_strtoupper(mb_substr($u['nombre'], 0, 1))) ?></div>
          <div class="d-none d-md-block lh-1"><div class="fw-bold small"><?= e($u['nombre']) ?></div><small class="text-muted"><?= e($u['rol']) ?></small></div>
          <i class="bi bi-chevron-down small text-muted"></i>
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
          <?php if (can('configuracion')): ?><li><a class="dropdown-item" href="<?= url('configuracion') ?>"><i class="bi bi-gear me-2"></i>Configuración</a></li><?php endif; ?>
          <li><a class="dropdown-item" href="<?= url('perfil') ?>"><i class="bi bi-person-gear me-2"></i>Mi perfil</a></li>
          <li><a class="dropdown-item text-danger" href="<?= url('auth/logout') ?>"><i class="bi bi-box-arrow-left me-2"></i>Cerrar sesión</a></li>
        </ul>
      </div>
    </div>
  </header>
  <main class="content">
    <?php foreach ($_SESSION['flash'] ?? [] as $f): ?>
      <div class="alert alert-<?= e($f['t']) ?> alert-dismissible fade show alert-auto"><?= $f['m'] ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endforeach; unset($_SESSION['flash']); ?>
    <?php require APP_PATH . '/views/' . $view . '.php'; ?>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
<?php if (can('ia')): $e = empresa(); ?>
<link rel="stylesheet" href="<?= asset('css/ia.css') ?>">
<script>window.IA_CFG = <?= json_encode(['base' => BASE_URL, 'csrf' => csrf_token(), 'dec' => (int)$e['decimales'], 'sd' => $e['sep_decimal'], 'sm' => $e['sep_miles'], 'sim' => $e['moneda_simbolo'], 'pos' => $e['simbolo_posicion']]) ?>;</script>
<script src="<?= asset('js/ia-common.js') ?>"></script>
<script src="<?= asset('js/chat.js') ?>"></script>
<?php endif; ?>
<?= $scripts ?? '' ?>
</body></html>
