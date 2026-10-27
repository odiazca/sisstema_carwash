<?php
class CitasController extends CrudController {
    protected function config(): array {
        return [
            'table' => 'citas', 'module' => 'citas', 'title' => 'Citas y reservas', 'singular' => 'Cita', 'icon' => 'bi-calendar-check',
            'subtitle' => 'Agenda de reservas de clientes', 'row_actions' => [['bi-play-circle', 'ordenes/nueva?cita={id}', 'Crear orden desde la cita']], 'order' => 't.fecha_hora DESC',
            'select' => "SELECT t.*, c.nombre cliente, v.placa, s.nombre servicio FROM citas t JOIN clientes c ON c.id=t.cliente_id LEFT JOIN vehiculos v ON v.id=t.vehiculo_id LEFT JOIN servicios s ON s.id=t.servicio_id",
            'columns' => ['fecha_hora' => ['Fecha y hora', 'datetime'], 'cliente' => ['Cliente', 'strong'], 'placa' => ['Placa'], 'servicio' => ['Servicio'], 'estado' => ['Estado', 'badge']],
            'fields' => [
                'cliente_id' => ['label' => 'Cliente', 'type' => 'select', 'table' => 'clientes', 'value_col' => 'id', 'where' => 'WHERE estado=1', 'required' => true, 'col' => 6],
                'vehiculo_id' => ['label' => 'Vehículo', 'type' => 'select', 'table' => 'vehiculos', 'value_col' => 'id', 'label_col' => 'placa', 'col' => 6],
                'servicio_id' => ['label' => 'Servicio', 'type' => 'select', 'table' => 'servicios', 'value_col' => 'id', 'where' => 'WHERE estado=1', 'col' => 6],
                'fecha_hora' => ['label' => 'Fecha y hora', 'type' => 'datetime', 'required' => true, 'col' => 3],
                'estado' => ['label' => 'Estado', 'type' => 'select', 'options' => ['programada' => 'Programada', 'confirmada' => 'Confirmada', 'atendida' => 'Atendida', 'cancelada' => 'Cancelada'], 'required' => true, 'col' => 3, 'default' => 'programada'],
                'notas' => ['label' => 'Notas', 'col' => 12],
            ],
        ];
    }
    protected function beforeSave(array $d, ?int $id, array &$errors): array {
        foreach (['vehiculo_id', 'servicio_id'] as $k) if (empty($d[$k])) $d[$k] = null;
        return $d;
    }

    public function calendario() {
        $this->requireModule('citas');
        $this->view('citas/calendario', ['title' => 'Calendario de citas']);
    }
    public function eventos() {
        $this->requireModule('citas');
        $m = preg_match('/^\d{4}-\d{2}$/', $_GET['mes'] ?? '') ? $_GET['mes'] : date('Y-m');
        $this->json(Database::all("SELECT ci.id, ci.estado, DATE(ci.fecha_hora) d, TIME_FORMAT(ci.fecha_hora,'%H:%i') h, c.nombre cliente, s.nombre servicio FROM citas ci JOIN clientes c ON c.id=ci.cliente_id LEFT JOIN servicios s ON s.id=ci.servicio_id WHERE DATE_FORMAT(ci.fecha_hora,'%Y-%m')=? ORDER BY ci.fecha_hora", [$m]));
    }
}
