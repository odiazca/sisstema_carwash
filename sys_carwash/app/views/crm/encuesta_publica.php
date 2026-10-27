<?php $emp = empresa(); ?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">
<title>Tu opinión · <?= e($emp['nombre']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>body{font-family:'Plus Jakarta Sans',sans-serif;background:linear-gradient(160deg,#e0f2fe,#ccfbf1);min-height:100vh;display:grid;place-items:center;padding:1rem}
.box{background:#fff;border-radius:1.4rem;box-shadow:0 20px 50px rgba(14,116,144,.15);max-width:440px;width:100%;padding:2rem;text-align:center}
.stars{display:flex;flex-direction:row-reverse;justify-content:center;gap:.2rem}.stars input{display:none}.stars label{font-size:2.6rem;color:#cbd5e1;cursor:pointer;transition:.15s}
.stars input:checked~label,.stars label:hover,.stars label:hover~label{color:#f59e0b}.btn-g{background:linear-gradient(135deg,#0ea5e9,#14b8a6);color:#fff;border:0;font-weight:700;padding:.7rem 1.5rem;border-radius:.8rem}</style></head><body>
<div class="box">
<?php if (!$e): ?><i class="bi bi-link-45deg text-muted" style="font-size:3rem"></i><h4 class="fw-bold mt-2">Enlace no válido</h4><p class="text-muted">Este enlace de encuesta no existe o venció.</p>
<?php elseif ($e['respondido_at'] || $gracias): ?><i class="bi bi-heart-fill text-danger" style="font-size:3rem"></i><h4 class="fw-bold mt-2">¡Gracias por tu opinión!</h4><p class="text-muted">Nos ayuda a mejorar cada día. ¡Te esperamos pronto en <?= e($emp['nombre']) ?>!</p>
<?php else: ?><h4 class="fw-bold"><?= e($emp['nombre']) ?></h4><p class="text-muted">Hola <?= e(explode(' ', (string)$e['nombre'])[0] ?: '') ?>, ¿cómo calificas el servicio de tu orden <b><?= e($e['codigo']) ?></b>?</p>
<form method="post" action="<?= url('encuesta/responder/' . e($token)) ?>"><?= csrf_field() ?>
 <div class="stars mb-3"><?php for ($i = 5; $i >= 1; $i--): ?><input type="radio" name="puntaje" id="s<?= $i ?>" value="<?= $i ?>" required><label for="s<?= $i ?>" title="<?= $i ?>">★</label><?php endfor; ?></div>
 <textarea name="comentario" class="form-control mb-3" rows="3" maxlength="500" placeholder="Cuéntanos más (opcional)"></textarea>
 <button class="btn btn-g w-100">Enviar calificación</button></form>
<?php endif; ?></div></body></html>
