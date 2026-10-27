<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div><h1 class="page-title">Recepción de vehículos</h1><p class="page-sub">Estado del vehículo al ingresar: checklist, daños, fotos y firma del cliente</p></div>
  <a href="<?= url('recepciones/nueva') ?>" class="btn btn-grad"><i class="bi bi-plus-lg me-1"></i> Nueva recepción</a>
</div>
<div class="cw-card">
  <form class="card-h" method="get"><div class="input-ico" style="width:280px"><i class="bi bi-search"></i><input class="form-control form-control-sm" name="q" value="<?= e($q) ?>" placeholder="Buscar placa o cliente…"></div></form>
  <div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>N°</th><th>Fecha</th><th>Placa</th><th>Cliente</th><th>Combustible</th><th>Daños</th><th>Fotos</th><th>Orden</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $r): $d = json_decode($r['danos'] ?? '{}', true) ?: []; $f = json_decode($r['fotos'] ?? '[]', true) ?: []; ?>
      <tr><td><a class="fw-bold text-decoration-none" href="<?= url('recepciones/ver/' . $r['id']) ?>">#<?= $r['id'] ?></a></td><td><?= fdate($r['created_at'], true) ?></td><td><b><?= e($r['placa']) ?></b></td>
        <td><?= e($r['cliente'] ?? '-') ?></td><td><div class="progress" style="height:8px;width:90px"><div class="progress-bar bg-info" style="width:<?= (int)$r['combustible'] ?>%"></div></div></td>
        <td><?= $d ? '<span class="badge badge-soft-warning">' . count($d) . ' zona(s)</span>' : '<span class="badge badge-soft-success">Sin daños</span>' ?></td>
        <td><i class="bi bi-images text-muted"></i> <?= count($f) ?></td>
        <td><?= $r['orden'] ? '<a href="' . url('ordenes/ver/' . (int)$r['orden_id']) . '">' . e($r['orden']) . '</a>' : '<span class="text-muted small">—</span>' ?></td>
        <td class="text-end"><a class="btn btn-icon btn-light" href="<?= url('recepciones/ver/' . $r['id']) ?>"><i class="bi bi-eye"></i></a></td></tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="9" class="text-center text-muted py-5"><i class="bi bi-clipboard2-check fs-1 d-block"></i>Aún no hay recepciones registradas.</td></tr><?php endif; ?>
    </tbody></table></div></div>
