<?php
class VehiculosController extends CrudController {
    protected function config(): array {
        return [
            'table' => 'vehiculos', 'module' => 'vehiculos', 'title' => 'Vehículos', 'singular' => 'Vehículo', 'icon' => 'bi-car-front',
            'subtitle' => 'Vehículos registrados por cliente',
            'select' => "SELECT t.*, c.nombre cliente, tv.nombre tipo FROM vehiculos t JOIN clientes c ON c.id=t.cliente_id LEFT JOIN tipos_vehiculo tv ON tv.id=t.tipo_vehiculo_id",
            'columns' => ['placa' => ['Placa', 'strong'], 'cliente' => ['Cliente'], 'marca' => ['Marca'], 'modelo' => ['Modelo'], 'color' => ['Color'], 'tipo' => ['Tipo'], 'estado' => ['Estado', 'estado']],
            'fields' => [
                'cliente_id' => ['label' => 'Cliente', 'type' => 'select', 'table' => 'clientes', 'value_col' => 'id', 'where' => 'WHERE estado=1', 'required' => true, 'col' => 6],
                'placa' => ['label' => 'Placa', 'required' => true, 'col' => 3, 'maxlength' => 15],
                'tipo_vehiculo_id' => ['label' => 'Tipo de vehículo', 'type' => 'select', 'table' => 'tipos_vehiculo', 'value_col' => 'id', 'where' => 'WHERE estado=1', 'col' => 3],
                'marca' => ['label' => 'Marca', 'col' => 3], 'modelo' => ['label' => 'Modelo', 'col' => 3],
                'color' => ['label' => 'Color', 'col' => 3], 'anio' => ['label' => 'Año', 'type' => 'number', 'col' => 3, 'min' => 1950],
                'notas' => ['label' => 'Observaciones (rayones, detalles)', 'col' => 9],
                'estado' => ['label' => 'Activo', 'type' => 'switch', 'col' => 3],
            ],
        ];
    }
    protected function beforeSave(array $d, ?int $id, array &$errors): array {
        $d['placa'] = strtoupper(preg_replace('/\s+/', '', $d['placa'] ?? ''));
        if (empty($d['anio'])) $d['anio'] = null;
        return $d;
    }
}
