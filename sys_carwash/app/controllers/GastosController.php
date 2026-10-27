<?php
class GastosController extends CrudController {
    protected function config(): array {
        return [
            'table' => 'gastos', 'module' => 'gastos', 'title' => 'Gastos', 'singular' => 'Gasto', 'icon' => 'bi-wallet2',
            'subtitle' => 'Egresos operativos del negocio', 'order' => 't.fecha DESC, t.id DESC',
            'columns' => ['fecha' => ['Fecha', 'date'], 'categoria' => ['Categoría'], 'concepto' => ['Concepto', 'strong'], 'monto' => ['Monto', 'money']],
            'fields' => [
                'fecha' => ['label' => 'Fecha', 'type' => 'date', 'required' => true, 'col' => 3, 'default' => date('Y-m-d')],
                'categoria' => ['label' => 'Categoría', 'type' => 'select', 'options' => ['Insumos' => 'Insumos', 'Servicios (agua/luz)' => 'Servicios (agua/luz)', 'Sueldos' => 'Sueldos', 'Alquiler' => 'Alquiler', 'Mantenimiento' => 'Mantenimiento', 'Publicidad' => 'Publicidad', 'Otros' => 'Otros'], 'required' => true, 'col' => 4],
                'monto' => ['label' => 'Monto', 'type' => 'money', 'required' => true, 'col' => 5],
                'concepto' => ['label' => 'Concepto / detalle', 'required' => true, 'col' => 12],
            ],
        ];
    }
    protected function beforeSave(array $d, ?int $id, array &$errors): array {
        if (!$id) $d['usuario_id'] = auth_user()['id'];
        return $d;
    }
}
