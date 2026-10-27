<?php
class ClientesController extends CrudController {
    protected function config(): array {
        return [
            'table' => 'clientes', 'module' => 'clientes', 'title' => 'Clientes', 'singular' => 'Cliente', 'icon' => 'bi-people',
            'subtitle' => 'Base de clientes del carwash', 'row_actions' => [['bi-person-lines-fill', 'clientes/ficha/{id}', 'Ficha e historial']],
            'select' => "SELECT t.*, (SELECT COUNT(*) FROM vehiculos v WHERE v.cliente_id=t.id) vehiculos FROM clientes t",
            'columns' => ['nombre' => ['Cliente', 'strong'], 'nro_doc' => ['Documento'], 'telefono' => ['Teléfono'], 'email' => ['Correo'], 'vehiculos' => ['Vehículos', 'number'], 'estado' => ['Estado', 'estado']],
            'fields' => [
                'tipo_doc' => ['label' => 'Tipo de documento', 'type' => 'select', 'options' => ['DNI' => 'DNI', 'RUC' => 'RUC', 'CE' => 'Carné de extranjería', 'PAS' => 'Pasaporte', 'OTRO' => 'Otro'], 'col' => 3, 'required' => true],
                'nro_doc' => ['label' => 'N° de documento', 'col' => 3, 'maxlength' => 20],
                'nombre' => ['label' => 'Nombre / Razón social', 'required' => true, 'col' => 6],
                'telefono' => ['label' => 'Teléfono / WhatsApp', 'col' => 4],
                'email' => ['label' => 'Correo', 'type' => 'email', 'col' => 4],
                'direccion' => ['label' => 'Dirección', 'col' => 4],
                'fecha_nacimiento' => ['label' => 'Fecha de nacimiento', 'type' => 'date', 'col' => 4],
                'acepta_marketing' => ['label' => 'Acepta promociones (WhatsApp/correo)', 'type' => 'switch', 'col' => 4],
                'estado' => ['label' => 'Cliente activo', 'type' => 'switch', 'col' => 4],
                'notas' => ['label' => 'Notas', 'type' => 'textarea', 'col' => 12],
            ],
        ];
    }

    public function ficha($id = 0) {
        $this->requireModule('clientes');
        $c = Database::row('SELECT * FROM clientes WHERE id=?', [(int)$id]);
        if (!$c) { flash('danger', 'Cliente no encontrado.'); $this->redirect('clientes'); }
        $veh = Database::all('SELECT v.*, t.nombre tipo FROM vehiculos v LEFT JOIN tipos_vehiculo t ON t.id=v.tipo_vehiculo_id WHERE v.cliente_id=?', [$c['id']]);
        $ord = Database::all('SELECT o.*, v.placa FROM ordenes o LEFT JOIN vehiculos v ON v.id=o.vehiculo_id WHERE o.cliente_id=? ORDER BY o.id DESC LIMIT 50', [$c['id']]);
        $tot = Database::row("SELECT COUNT(*) n, COALESCE(SUM(total),0) t, MAX(fecha_ingreso) ult FROM ordenes WHERE cliente_id=? AND estado<>'cancelado'", [$c['id']]);
        $cfg = crm_cfg(); crm_sync_puntos();
        $dias = $tot['ult'] ? (int)Database::val('SELECT DATEDIFF(CURDATE(), DATE(?))', [$tot['ult']]) : null;
        $seg = crm_segmento(['visitas' => $tot['n'], 'gastado' => $tot['t'], 'dias' => $dias], $cfg);
        $puntos = crm_saldos((int)$c['id'])[(int)$c['id']] ?? 0;
        $inter = Database::all('SELECT i.*, u.nombre usuario FROM crm_interacciones i LEFT JOIN usuarios u ON u.id=i.usuario_id WHERE i.cliente_id=? ORDER BY i.id DESC LIMIT 30', [$c['id']]);
        $cupones = Database::all("SELECT * FROM crm_cupones WHERE cliente_id=? AND estado=1 AND (vigente_hasta IS NULL OR vigente_hasta>=CURDATE()) AND (usos_max=0 OR usos<usos_max)", [$c['id']]);
        $csat = Database::val('SELECT ROUND(AVG(puntaje),1) FROM crm_encuestas WHERE cliente_id=? AND puntaje IS NOT NULL', [$c['id']]);
        $this->view('clientes/ficha', ['c' => $c, 'veh' => $veh, 'ord' => $ord, 'tot' => $tot, 'seg' => $seg, 'puntos' => $puntos, 'inter' => $inter, 'cupones' => $cupones, 'csat' => $csat, 'cfg' => $cfg, 'title' => 'Ficha de cliente']);
    }
}
