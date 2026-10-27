<?php
require_once APP_PATH . '/core/Sql.php';

class RespaldoController extends Controller {
    private function verifyPassword(): bool {
        $hash = Database::val('SELECT password FROM usuarios WHERE id=?', [auth_user()['id']]);
        return $hash && password_verify($_POST['password'] ?? '', $hash);
    }
    private function safeName(string $n): ?string {
        $n = basename($n);
        return preg_match('/^[A-Za-z0-9_\-]+\.sql$/', $n) && is_file(BACKUP_PATH . '/' . $n) ? $n : null;
    }
    private function makeBackup(string $prefix = 'backup'): string {
        if (!is_dir(BACKUP_PATH)) mkdir(BACKUP_PATH, 0777, true);
        $name = $prefix . '_' . date('Ymd_His') . '.sql';
        Sql::dump(Database::pdo(), BACKUP_PATH . '/' . $name);
        return $name;
    }

    public function index() {
        $this->requireModule('respaldo');
        $pdo = Database::pdo();
        $tablas = Sql::tables($pdo);
        $registros = 0;
        foreach ($tablas as $t) $registros += (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        $tam = (int)Database::val('SELECT COALESCE(SUM(data_length+index_length),0) FROM information_schema.tables WHERE table_schema=?', [DB_NAME]);
        $files = [];
        foreach (glob(BACKUP_PATH . '/*.sql') ?: [] as $f) {
            $files[] = ['name' => basename($f), 'size' => filesize($f), 'time' => filemtime($f)];
        }
        usort($files, fn($a, $b) => $b['time'] <=> $a['time']);
        $this->view('respaldo/index', [
            'title' => 'Respaldo y restauración', 'files' => $files,
            'stats' => ['tablas' => count($tablas), 'registros' => $registros, 'tam' => $tam, 'ultimo' => $files[0]['time'] ?? null],
            'maxUpload' => ini_get('upload_max_filesize'),
        ]);
    }

    public function crear() {
        $this->requireModule('respaldo');
        if (!$this->isPost()) $this->redirect('respaldo');
        csrf_check();
        try {
            $n = $this->makeBackup();
            log_action('backup', "Respaldo creado: $n");
            flash('success', "Respaldo <b>$n</b> creado correctamente.");
        } catch (Throwable $e) { flash('danger', 'Error al crear el respaldo: ' . e($e->getMessage())); }
        $this->redirect('respaldo');
    }

    public function descargar($name = '') {
        $this->requireModule('respaldo');
        $n = $this->safeName($name);
        if (!$n) { http_response_code(404); exit('Archivo no encontrado'); }
        log_action('backup', "Descarga: $n");
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $n . '"');
        header('Content-Length: ' . filesize(BACKUP_PATH . '/' . $n));
        readfile(BACKUP_PATH . '/' . $n);
        exit;
    }

    public function eliminar() {
        $this->requireModule('respaldo');
        if (!$this->isPost()) $this->redirect('respaldo');
        csrf_check();
        $n = $this->safeName($_POST['archivo'] ?? '');
        if ($n) { unlink(BACKUP_PATH . '/' . $n); log_action('backup', "Respaldo eliminado: $n"); flash('success', 'Respaldo eliminado.'); }
        $this->redirect('respaldo');
    }

    public function restaurar() {
        $this->requireModule('respaldo');
        if (!$this->isPost()) $this->redirect('respaldo');
        csrf_check();
        if (!$this->verifyPassword()) { flash('danger', 'Contraseña incorrecta. No se restauró nada.'); $this->redirect('respaldo'); }

        $path = null; $tmpUploaded = false;
        if (!empty($_FILES['archivo_sql']['name']) && $_FILES['archivo_sql']['error'] === UPLOAD_ERR_OK) {
            if (strtolower(pathinfo($_FILES['archivo_sql']['name'], PATHINFO_EXTENSION)) !== 'sql') { flash('danger', 'El archivo debe ser .sql'); $this->redirect('respaldo'); }
            $path = $_FILES['archivo_sql']['tmp_name']; $tmpUploaded = true;
        } else {
            $n = $this->safeName($_POST['archivo'] ?? '');
            if ($n) $path = BACKUP_PATH . '/' . $n;
        }
        if (!$path) { flash('danger', 'Selecciona un respaldo válido.'); $this->redirect('respaldo'); }
        $head = file_get_contents($path, false, null, 0, 200);
        if (strpos($head, 'CarWash Pro - respaldo') === false) { flash('danger', 'El archivo no es un respaldo válido de este sistema.'); $this->redirect('respaldo'); }

        try {
            $pre = $this->makeBackup('prerestauracion');
            $pdo = Database::pdo();
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            $n = Sql::runFile($pdo, $path);
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            log_action('restore', "Restauración completada ($n sentencias). Copia previa: $pre");
            $_SESSION = []; session_destroy();
            session_start();
            flash('success', 'Respaldo restaurado correctamente. Inicia sesión nuevamente.');
            $this->redirect('auth/login');
        } catch (Throwable $e) {
            flash('danger', 'Error al restaurar: ' . e($e->getMessage()) . '. Se guardó una copia previa por seguridad.');
            $this->redirect('respaldo');
        }
    }

    public function resetear() {
        $this->requireModule('respaldo');
        if (!$this->isPost()) $this->redirect('respaldo');
        csrf_check();
        if (trim($_POST['confirmacion'] ?? '') !== 'RESETEAR') { flash('danger', 'Debes escribir RESETEAR para confirmar.'); $this->redirect('respaldo'); }
        if (!$this->verifyPassword()) { flash('danger', 'Contraseña incorrecta. No se reseteó nada.'); $this->redirect('respaldo'); }
        $keepCfg = !empty($_POST['conservar_empresa']);
        $keepCat = !empty($_POST['conservar_catalogo']);
        try {
            $pre = $this->makeBackup('prereset');
            $pdo = Database::pdo();
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            $tabs = ['planilla_adelantos', 'planillas', 'asistencias', 'comprobantes', 'comprobante_series', 'cotizacion_detalle', 'cotizaciones', 'orden_pagos', 'recepciones', 'crm_campana_envios', 'crm_campanas', 'crm_encuestas', 'crm_cupones', 'crm_interacciones', 'crm_oportunidades', 'crm_puntos', 'ia_consultas', 'orden_paquete_uso', 'cliente_paquetes', 'compra_detalle', 'compra_pagos', 'compras', 'comisiones_pagos', 'orden_detalle', 'ordenes', 'citas', 'cajas', 'gastos', 'movimientos_inventario', 'bitacora', 'vehiculos', 'clientes'];
            if (!$keepCat) $tabs = array_merge($tabs, ['paquetes', 'servicio_precios', 'servicios', 'categorias_servicio', 'tipos_vehiculo', 'productos', 'proveedores', 'empleados']);
            if (!$keepCfg) $tabs[] = 'empresa_config';
            foreach ($tabs as $t) $pdo->exec("TRUNCATE TABLE `$t`");
            $pdo->prepare('DELETE FROM usuarios WHERE id<>?')->execute([auth_user()['id']]);
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            if (!$keepCfg) {
                foreach (glob(UPLOAD_PATH . '/logo_*') ?: [] as $f) @unlink($f);
            }
            foreach (glob(UPLOAD_PATH . '/recepciones/*') ?: [] as $f) @unlink($f);
            Sql::runFile($pdo, ROOT_PATH . '/database/seed_base.sql');
            empresa(true);
            log_action('reset', 'Sistema reseteado para nueva empresa. Copia previa: ' . $pre);
            flash('success', 'Sistema reseteado. Se creó una copia previa: <b>' . e($pre) . '</b>. Configura los datos de la nueva empresa.');
            $this->redirect($keepCfg ? 'respaldo' : 'configuracion');
        } catch (Throwable $e) {
            try { Database::pdo()->exec('SET FOREIGN_KEY_CHECKS=1'); } catch (Throwable $x) {}
            flash('danger', 'Error al resetear: ' . e($e->getMessage()));
            $this->redirect('respaldo');
        }
    }
}
