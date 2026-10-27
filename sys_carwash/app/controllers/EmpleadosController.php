<?php
class EmpleadosController extends CrudController {
    protected function config(): array {
        return [
            'table' => 'empleados', 'module' => 'empleados', 'title' => 'Empleados y comisiones', 'singular' => 'Empleado', 'icon' => 'bi-person-badge',
            'subtitle' => 'Personal operativo y porcentaje de comisión',
            'columns' => ['nombre' => ['Empleado', 'strong'], 'cargo' => ['Cargo'], 'documento' => ['Documento'], 'telefono' => ['Teléfono'], 'sueldo' => ['Sueldo', 'money'], 'comision_pct' => ['Comisión', 'pct'], 'estado' => ['Estado', 'estado']],
            'fields' => [
                'nombre' => ['label' => 'Nombre completo', 'required' => true, 'col' => 6], 'documento' => ['label' => 'Documento', 'col' => 3],
                'telefono' => ['label' => 'Teléfono', 'col' => 3],
                'cargo' => ['label' => 'Cargo', 'type' => 'select', 'options' => ['Lavador' => 'Lavador', 'Detallador' => 'Detallador', 'Supervisor' => 'Supervisor', 'Cajero' => 'Cajero', 'Administrador' => 'Administrador'], 'required' => true, 'col' => 3],
                'sueldo' => ['label' => 'Sueldo base', 'type' => 'money', 'col' => 3], 'comision_pct' => ['label' => 'Comisión por defecto (%)', 'type' => 'money', 'col' => 3],
                'fecha_ingreso' => ['label' => 'Fecha de ingreso', 'type' => 'date', 'col' => 3],
                'estado' => ['label' => 'Activo', 'type' => 'switch', 'col' => 12],
            ],
        ];
    }
}
