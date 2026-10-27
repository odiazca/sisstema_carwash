<?php
class ProductosController extends CrudController {
    protected function config(): array {
        return [
            'table' => 'productos', 'module' => 'productos', 'title' => 'Productos e insumos', 'singular' => 'Producto', 'icon' => 'bi-box-seam',
            'subtitle' => 'Inventario de insumos de lavado y productos de venta',
            'select' => "SELECT t.*, p.nombre proveedor FROM productos t LEFT JOIN proveedores p ON p.id=t.proveedor_id",
            'columns' => ['codigo' => ['Código'], 'nombre' => ['Producto', 'strong'], 'proveedor' => ['Proveedor'], 'precio_compra' => ['P. compra', 'money'], 'precio_venta' => ['P. venta', 'money'], 'stock' => ['Stock', 'stock'], 'estado' => ['Estado', 'estado']],
            'fields' => [
                'codigo' => ['label' => 'Código', 'col' => 3], 'nombre' => ['label' => 'Nombre', 'required' => true, 'col' => 6],
                'unidad' => ['label' => 'Unidad', 'type' => 'select', 'options' => ['UND' => 'Unidad', 'GL' => 'Galón', 'LT' => 'Litro', 'KG' => 'Kilogramo', 'CJ' => 'Caja'], 'col' => 3, 'required' => true],
                'proveedor_id' => ['label' => 'Proveedor', 'type' => 'select', 'table' => 'proveedores', 'value_col' => 'id', 'col' => 4],
                'precio_compra' => ['label' => 'Precio de compra', 'type' => 'money', 'col' => 4], 'precio_venta' => ['label' => 'Precio de venta', 'type' => 'money', 'col' => 4],
                'stock' => ['label' => 'Stock actual', 'type' => 'money', 'col' => 4], 'stock_minimo' => ['label' => 'Stock mínimo', 'type' => 'money', 'col' => 4],
                'se_vende' => ['label' => 'Se vende al cliente (desmarcar = insumo interno)', 'type' => 'switch', 'col' => 4],
                'estado' => ['label' => 'Activo', 'type' => 'switch', 'col' => 12],
            ],
        ];
    }
    protected function beforeSave(array $d, ?int $id, array &$errors): array {
        if (empty($d['proveedor_id'])) $d['proveedor_id'] = null;
        return $d;
    }
}
