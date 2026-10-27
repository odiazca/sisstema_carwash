<?php
class ProveedoresController extends CrudController {
    protected function config(): array {
        return [
            'table' => 'proveedores', 'module' => 'proveedores', 'title' => 'Proveedores', 'singular' => 'Proveedor', 'icon' => 'bi-truck',
            'subtitle' => 'Proveedores de productos e insumos',
            'columns' => ['nombre' => ['Proveedor', 'strong'], 'ruc' => ['RUC'], 'contacto' => ['Contacto'], 'telefono' => ['Teléfono'], 'email' => ['Correo'], 'estado' => ['Estado', 'estado']],
            'fields' => [
                'nombre' => ['label' => 'Nombre / Razón social', 'required' => true, 'col' => 6], 'ruc' => ['label' => 'RUC', 'col' => 3, 'maxlength' => 20],
                'contacto' => ['label' => 'Persona de contacto', 'col' => 3], 'telefono' => ['label' => 'Teléfono', 'col' => 4],
                'email' => ['label' => 'Correo', 'type' => 'email', 'col' => 4], 'direccion' => ['label' => 'Dirección', 'col' => 4],
                'estado' => ['label' => 'Activo', 'type' => 'switch', 'col' => 3],
            ],
        ];
    }
}
