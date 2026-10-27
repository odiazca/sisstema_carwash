<?php
class PaquetesController extends CrudController {
    protected function config(): array {
        return [
            'table' => 'paquetes', 'module' => 'clientes', 'route' => 'paquetes', 'title' => 'Paquetes y membresías', 'singular' => 'Paquete', 'icon' => 'bi-gift',
            'subtitle' => 'Paquetes prepagados: el cliente compra varios lavados con descuento',
            'select' => "SELECT t.*, s.nombre servicio FROM paquetes t JOIN servicios s ON s.id=t.servicio_id",
            'row_actions' => [['bi-bag-plus', 'paquetes/vender/{id}', 'Vender paquete']],
            'new_label' => 'Nuevo paquete',
            'columns' => ['nombre' => ['Paquete', 'strong'], 'servicio' => ['Servicio incluido'], 'usos' => ['Usos', 'number'], 'precio' => ['Precio', 'money'], 'vigencia_dias' => ['Vigencia (días)', 'number'], 'estado' => ['Estado', 'estado']],
            'fields' => [
                'nombre' => ['label' => 'Nombre del paquete', 'required' => true, 'col' => 6, 'default' => ''],
                'servicio_id' => ['label' => 'Servicio incluido', 'type' => 'select', 'table' => 'servicios', 'value_col' => 'id', 'where' => 'WHERE estado=1', 'required' => true, 'col' => 6],
                'usos' => ['label' => 'Cantidad de usos', 'type' => 'number', 'required' => true, 'col' => 3, 'default' => 4, 'min' => 1],
                'precio' => ['label' => 'Precio del paquete', 'type' => 'money', 'required' => true, 'col' => 3],
                'vigencia_dias' => ['label' => 'Vigencia (días)', 'type' => 'number', 'col' => 3, 'default' => 90, 'min' => 1],
                'estado' => ['label' => 'Activo', 'type' => 'switch', 'col' => 3],
                'descripcion' => ['label' => 'Descripción', 'col' => 12],
            ],
        ];
    }

    public function vender($id = 0) {
        $this->requireModule('clientes');
        $p = Database::row('SELECT p.*, s.nombre servicio FROM paquetes p JOIN servicios s ON s.id=p.servicio_id WHERE p.id=? AND p.estado=1', [(int)$id]);
        if (!$p) { flash('danger', 'Paquete no disponible.'); $this->redirect('paquetes'); }
        $clientes = Database::all('SELECT id,nombre,nro_doc FROM clientes WHERE estado=1 ORDER BY nombre');
        $this->view('paquetes/vender', ['p' => $p, 'clientes' => $clientes, 'title' => 'Vender paquete']);
    }

    public function vender_guardar() {
        $this->requireModule('clientes');
        if (!$this->isPost()) $this->redirect('paquetes');
        csrf_check();
        $p = Database::row('SELECT * FROM paquetes WHERE id=? AND estado=1', [(int)($_POST['paquete_id'] ?? 0)]);
        $cid = (int)($_POST['cliente_id'] ?? 0);
        if (!$p || !Database::val('SELECT id FROM clientes WHERE id=?', [$cid])) { flash('danger', 'Selecciona un cliente y un paquete válidos.'); $this->redirect('paquetes'); }
        $metodo = isset(OrdenesController::PAGOS[$_POST['metodo_pago'] ?? '']) ? $_POST['metodo_pago'] : 'efectivo';
        $precio = isset($_POST['precio']) && $_POST['precio'] !== '' ? max(0, (float)$_POST['precio']) : (float)$p['precio'];
        Database::q('INSERT INTO cliente_paquetes (cliente_id,paquete_id,servicio_id,nombre,usos_total,usos_restantes,precio_pagado,metodo_pago,fecha_vence,usuario_id) VALUES (?,?,?,?,?,?,?,?,DATE_ADD(CURDATE(), INTERVAL ? DAY),?)',
            [$cid, $p['id'], $p['servicio_id'], $p['nombre'], $p['usos'], $p['usos'], $precio, $metodo, (int)$p['vigencia_dias'], auth_user()['id']]);
        log_action('paquetes', "Venta paquete {$p['nombre']} a cliente #$cid");
        flash('success', 'Paquete vendido correctamente.');
        $this->redirect('paquetes/vendidos');
    }

    public function vendidos() {
        $this->requireModule('clientes');
        $rows = Database::all("SELECT cp.*, c.nombre cliente, s.nombre servicio FROM cliente_paquetes cp JOIN clientes c ON c.id=cp.cliente_id JOIN servicios s ON s.id=cp.servicio_id ORDER BY cp.id DESC LIMIT 500");
        $this->view('paquetes/vendidos', ['rows' => $rows, 'title' => 'Paquetes vendidos']);
    }

    /** JSON: paquetes activos de un cliente (usados por Nueva orden) */
    public function de_cliente($cid = 0) {
        $this->requireModule('ordenes');
        $this->json(Database::all("SELECT id, nombre, servicio_id, usos_restantes, fecha_vence FROM cliente_paquetes WHERE cliente_id=? AND usos_restantes>0 AND (fecha_vence IS NULL OR fecha_vence>=CURDATE()) ORDER BY id", [(int)$cid]));
    }
}
