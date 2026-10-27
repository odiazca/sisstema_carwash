<?php
class CategoriasController extends CrudController {
    protected function config(): array {
        return ['table' => 'categorias_servicio', 'module' => 'servicios', 'route' => 'categorias', 'title' => 'Categorías de servicio', 'singular' => 'Categoría', 'icon' => 'bi-bookmarks',
            'subtitle' => 'Agrupa tus servicios (lavado, detallado, etc.)',
            'columns' => ['nombre' => ['Categoría', 'strong'], 'estado' => ['Estado', 'estado']],
            'fields' => ['nombre' => ['label' => 'Nombre', 'required' => true, 'col' => 8], 'estado' => ['label' => 'Activo', 'type' => 'switch', 'col' => 4]]];
    }
}
