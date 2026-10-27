<?php
class BitacoraController extends Controller {
    public function index() {
        $this->requireModule('bitacora');
        $rows = Database::all('SELECT * FROM bitacora ORDER BY id DESC LIMIT 500');
        $this->view('crud/bitacora', ['rows' => $rows, 'title' => 'Bitácora']);
    }
}
