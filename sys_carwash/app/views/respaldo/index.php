<div class="mb-4"><h1 class="page-title">Respaldo y restauración</h1><p class="page-sub">Protege la información de tu negocio: crea copias, restáuralas o inicia una empresa nueva</p></div>
<div class="alert alert-light border d-flex align-items-center gap-2 py-2"><i class="bi bi-magic text-primary"></i><span class="flex-grow-1 small">¿Quieres ver el dashboard con información? Carga registros de ejemplo.</span><a href="<?= url('demo') ?>" class="btn btn-sm btn-outline-primary">Datos de ejemplo</a></div>

<div class="row g-3 mb-4">
  <?php foreach ([
    ['Tablas', $stats['tablas'], 'bi-table', 'blue'],
    ['Registros totales', number_format($stats['registros']), 'bi-collection', 'teal'],
    ['Tamaño de la base de datos', format_bytes($stats['tam']), 'bi-hdd', 'violet'],
    ['Último respaldo', $stats['ultimo'] ? date(empresa()['formato_fecha'] . ' H:i', $stats['ultimo']) : 'Nunca', 'bi-shield-check', $stats['ultimo'] ? 'green' : 'rose'],
  ] as [$l, $v, $i, $c]): ?>
  <div class="col-sm-6 col-xl-3"><div class="cw-card stat t-<?= $c ?>"><div class="d-flex align-items-center gap-3">
    <div class="ico bg-<?= $c ?>"><i class="bi <?= $i ?>"></i></div><div><div class="l"><?= $l ?></div><div class="fs-5 fw-bold text-dark"><?= $v ?></div></div></div></div></div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-4"><div class="opt-card">
    <div class="big bg-blue t-blue"><i class="bi bi-cloud-arrow-down"></i></div>
    <h5 class="fw-bold">Crear respaldo</h5>
    <p class="text-muted">Genera un archivo .sql con toda la información del sistema (clientes, órdenes, caja, inventario, configuración y más).</p>
    <form method="post" action="<?= url('respaldo/crear') ?>"><?= csrf_field() ?><button class="btn btn-grad w-100"><i class="bi bi-plus-circle me-1"></i> Generar respaldo ahora</button></form>
  </div></div>
  <div class="col-lg-4"><div class="opt-card">
    <div class="big bg-teal t-teal"><i class="bi bi-arrow-counterclockwise"></i></div>
    <h5 class="fw-bold">Restaurar respaldo</h5>
    <p class="text-muted">Recupera el sistema desde un respaldo guardado o desde un archivo .sql. Antes se crea una copia automática de seguridad.</p>
    <button class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#mRestore" data-file=""><i class="bi bi-upload me-1"></i> Restaurar desde archivo</button>
  </div></div>
  <div class="col-lg-4"><div class="opt-card danger-zone" style="border-color:#fecdd3">
    <div class="big bg-rose t-rose"><i class="bi bi-exclamation-octagon"></i></div>
    <h5 class="fw-bold text-danger">Resetear sistema</h5>
    <p class="text-muted">Borra clientes, órdenes, caja y demás datos para comenzar con una <b>empresa nueva</b>. Acción irreversible (se guarda una copia previa).</p>
    <button class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#mReset"><i class="bi bi-trash3 me-1"></i> Resetear para empresa nueva</button>
  </div></div>
</div>

<div class="cw-card">
  <div class="card-h"><h6><i class="bi bi-archive text-primary me-2"></i>Respaldos guardados <span class="badge badge-soft-info ms-1"><?= count($files) ?></span></h6></div>
  <div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>Archivo</th><th>Fecha</th><th>Tamaño</th><th class="text-end">Acciones</th></tr></thead><tbody>
    <?php foreach ($files as $f): ?>
      <tr><td><i class="bi bi-filetype-sql text-primary me-2 fs-5"></i><b><?= e($f['name']) ?></b>
        <?php if (str_starts_with($f['name'], 'pre')): ?><span class="badge badge-soft-secondary ms-1">automático</span><?php endif; ?></td>
        <td><?= date(empresa()['formato_fecha'] . ' H:i', $f['time']) ?></td><td><?= format_bytes($f['size']) ?></td>
        <td class="text-end text-nowrap">
          <a class="btn btn-icon btn-light" title="Descargar" href="<?= url('respaldo/descargar/' . rawurlencode($f['name'])) ?>"><i class="bi bi-download"></i></a>
          <button class="btn btn-icon btn-light text-success" title="Restaurar" data-bs-toggle="modal" data-bs-target="#mRestore" data-file="<?= e($f['name']) ?>"><i class="bi bi-arrow-counterclockwise"></i></button>
          <form method="post" action="<?= url('respaldo/eliminar') ?>" class="d-inline"><?= csrf_field() ?><input type="hidden" name="archivo" value="<?= e($f['name']) ?>">
            <button class="btn btn-icon btn-light text-danger" title="Eliminar" data-confirm="¿Eliminar este respaldo?"><i class="bi bi-trash"></i></button></form>
        </td></tr>
    <?php endforeach; if (!$files): ?><tr><td colspan="4" class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block"></i>Aún no hay respaldos. ¡Crea el primero!</td></tr><?php endif; ?>
    </tbody></table></div>
</div>

<!-- Modal restaurar -->
<div class="modal fade" id="mRestore" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content border-0 rounded-4" method="post" action="<?= url('respaldo/restaurar') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?><input type="hidden" name="archivo" id="rf">
  <div class="modal-header border-0"><h5 class="modal-title fw-bold"><i class="bi bi-arrow-counterclockwise text-success me-2"></i>Restaurar respaldo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="alert alert-warning small"><i class="bi bi-exclamation-triangle me-1"></i> Se reemplazarán <b>todos los datos actuales</b> por los del respaldo. Se guardará antes una copia automática.</div>
    <div id="rfSel" class="mb-3 d-none"><label class="form-label">Respaldo seleccionado</label><div class="form-control bg-light" id="rfName"></div></div>
    <div id="rfUp" class="mb-3"><label class="form-label">Archivo .sql <span class="text-muted fw-normal">(máx. <?= e($maxUpload) ?>)</span></label><input type="file" name="archivo_sql" accept=".sql" class="form-control"></div>
    <label class="form-label">Confirma con tu contraseña</label><input type="password" name="password" class="form-control" required>
  </div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-success">Restaurar ahora</button></div>
</form></div></div>

<!-- Modal reset -->
<div class="modal fade" id="mReset" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content border-0 rounded-4" method="post" action="<?= url('respaldo/resetear') ?>">
  <?= csrf_field() ?>
  <div class="modal-header border-0"><h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-octagon me-2"></i>Resetear sistema</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="alert alert-danger small">Se eliminarán <b>clientes, vehículos, órdenes, citas, caja, gastos, movimientos y bitácora</b>, y todos los usuarios excepto el tuyo. Se crea una copia previa automáticamente.</div>
    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="conservar_empresa" value="1" id="kc"><label class="form-check-label" for="kc">Conservar datos y logo de la empresa</label></div>
    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="conservar_catalogo" value="1" id="kk"><label class="form-check-label" for="kk">Conservar catálogos (servicios, productos, proveedores, empleados)</label></div>
    <label class="form-label">Escribe <b class="text-danger">RESETEAR</b> para confirmar</label><input class="form-control mb-3" name="confirmacion" autocomplete="off" required>
    <label class="form-label">Tu contraseña</label><input type="password" name="password" class="form-control" required>
  </div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-danger">Resetear definitivamente</button></div>
</form></div></div>
<?php ob_start(); ?>
<script>
document.getElementById('mRestore').addEventListener('show.bs.modal', e => {
  const f = e.relatedTarget?.dataset.file || '';
  document.getElementById('rf').value = f;
  document.getElementById('rfSel').classList.toggle('d-none', !f);
  document.getElementById('rfUp').classList.toggle('d-none', !!f);
  document.getElementById('rfName').textContent = f;
});
</script>
<?php $scripts = ob_get_clean(); ?>
