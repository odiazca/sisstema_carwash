<div class="mb-4"><h1 class="page-title">Mi perfil</h1><p class="page-sub">Datos personales y contraseña</p></div>
<form method="post" action="<?= url('perfil/guardar') ?>" class="cw-card" style="max-width:720px" autocomplete="off"><?= csrf_field() ?>
<div class="card-b pt-4"><div class="row g-3">
  <div class="col-md-6"><label class="form-label">Nombre</label><input class="form-control" name="nombre" value="<?= e($u['nombre']) ?>" required></div>
  <div class="col-md-6"><label class="form-label">Usuario</label><input class="form-control" value="<?= e($u['usuario']) ?>" disabled></div>
  <div class="col-md-12"><label class="form-label">Correo</label><input type="email" class="form-control" name="email" value="<?= e($u['email'] ?? '') ?>"></div>
  <div class="col-12"><hr class="my-1"><h6 class="fw-bold mt-3">Cambiar contraseña <span class="text-muted fw-normal small">(opcional)</span></h6></div>
  <div class="col-md-4"><label class="form-label">Contraseña actual</label><input type="password" class="form-control" name="actual" autocomplete="current-password"></div>
  <div class="col-md-4"><label class="form-label">Nueva contraseña</label><input type="password" class="form-control" name="nueva" autocomplete="new-password"></div>
  <div class="col-md-4"><label class="form-label">Confirmar</label><input type="password" class="form-control" name="confirmar" autocomplete="new-password"></div>
</div></div><div class="card-b border-top text-end"><button class="btn btn-grad px-4">Guardar cambios</button></div></form>
