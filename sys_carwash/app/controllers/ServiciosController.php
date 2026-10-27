<?php
class ServiciosController extends CrudController {
    protected function config(): array {
        return [
            'table' => 'servicios', 'module' => 'servicios', 'title' => 'Servicios', 'singular' => 'Servicio', 'icon' => 'bi-droplet-half',
            'subtitle' => 'Catálogo de servicios y precios por tipo de vehículo',
            'select' => "SELECT t.*, c.nombre categoria FROM servicios t LEFT JOIN categorias_servicio c ON c.id=t.categoria_id",
            'extra_form' => 'crud/servicio_precios',
            'columns' => ['nombre' => ['Servicio', 'strong'], 'categoria' => ['Categoría'], 'precio' => ['Precio base', 'money'], 'duracion_min' => ['Duración (min)', 'number'], 'comision_pct' => ['Comisión', 'pct'], 'estado' => ['Estado', 'estado']],
            'fields' => [
                'nombre' => ['label' => 'Nombre del servicio', 'required' => true, 'col' => 6],
                'categoria_id' => ['label' => 'Categoría', 'type' => 'select', 'table' => 'categorias_servicio', 'value_col' => 'id', 'col' => 6],
                'descripcion' => ['label' => 'Descripción', 'col' => 12],
                'precio' => ['label' => 'Precio base', 'type' => 'money', 'col' => 3, 'required' => true],
                'duracion_min' => ['label' => 'Duración (minutos)', 'type' => 'number', 'col' => 3, 'default' => 30],
                'comision_pct' => ['label' => 'Comisión empleado (%)', 'type' => 'money', 'col' => 3],
                'estado' => ['label' => 'Servicio activo', 'type' => 'switch', 'col' => 3],
            ],
        ];
    }
    protected function formData(?array $row): array {
        $tipos = Database::all('SELECT id,nombre FROM tipos_vehiculo WHERE estado=1 ORDER BY id');
        $precios = $row ? array_column(Database::all('SELECT tipo_vehiculo_id t, precio p FROM servicio_precios WHERE servicio_id=?', [$row['id']]), 'p', 't') : [];
        return compact('tipos', 'precios');
    }
    protected function afterSave(int $id, bool $isNew): void {
        Database::q('DELETE FROM servicio_precios WHERE servicio_id=?', [$id]);
        foreach ($_POST['precio_tipo'] ?? [] as $tid => $p) {
            if ($p === '' || !is_numeric($p)) continue;
            Database::q('INSERT INTO servicio_precios (servicio_id,tipo_vehiculo_id,precio) VALUES (?,?,?)', [$id, (int)$tid, (float)$p]);
        }
    }
}
