<?php
class TiposvehiculoController extends CrudController {
    protected function config(): array {
        return ['table' => 'tipos_vehiculo', 'module' => 'servicios', 'route' => 'tiposvehiculo', 'title' => 'Tipos de vehículo', 'singular' => 'Tipo de vehículo', 'icon' => 'bi-truck-front',
            'subtitle' => 'Se usan para definir precios por tamaño de vehículo',
            'columns' => ['nombre' => ['Tipo', 'strong'], 'estado' => ['Estado', 'estado']],
            'fields' => ['nombre' => ['label' => 'Nombre', 'required' => true, 'col' => 8], 'estado' => ['label' => 'Activo', 'type' => 'switch', 'col' => 4]]];
    }
}
