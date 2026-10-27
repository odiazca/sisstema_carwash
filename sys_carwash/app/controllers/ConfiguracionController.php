<?php
class ConfiguracionController extends Controller {
    public function index() {
        $this->requireModule('configuracion');
        $this->view('config/index', ['emp' => empresa(true), 'title' => 'Configuración de la empresa']);
    }

    public function guardar() {
        $this->requireModule('configuracion');
        if (!$this->isPost()) $this->redirect('configuracion');
        csrf_check();
        $p = $_POST;
        $dec = max(0, min(4, (int)($p['decimales'] ?? 2)));
        $sepD = in_array($p['sep_decimal'] ?? '.', ['.', ','], true) ? $p['sep_decimal'] : '.';
        $sepM = in_array($p['sep_miles'] ?? ',', [',', '.', ' ', "'", ''], true) ? $p['sep_miles'] : ',';
        if ($sepD === $sepM && $dec > 0) { flash('danger', 'El separador decimal y el de miles no pueden ser iguales.'); $this->redirect('configuracion'); }
        $zonas = timezone_identifiers_list();
        $zona = in_array($p['zona_horaria'] ?? '', $zonas, true) ? $p['zona_horaria'] : 'America/Lima';
        $fechas = ['d/m/Y', 'm/d/Y', 'Y-m-d', 'd-m-Y'];
        $ff = in_array($p['formato_fecha'] ?? '', $fechas, true) ? $p['formato_fecha'] : 'd/m/Y';

        $logo = empresa()['logo'];
        if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $info = @getimagesize($_FILES['logo']['tmp_name']);
            if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'], true) || !$info || $_FILES['logo']['size'] > 2 * 1024 * 1024) {
                flash('danger', 'Logo inválido: usa PNG/JPG/WEBP de máximo 2 MB.'); $this->redirect('configuracion');
            }
            if (!is_dir(UPLOAD_PATH)) mkdir(UPLOAD_PATH, 0777, true);
            $name = 'logo_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['logo']['tmp_name'], UPLOAD_PATH . '/' . $name);
            if ($logo && is_file(UPLOAD_PATH . '/' . $logo)) @unlink(UPLOAD_PATH . '/' . $logo);
            $logo = $name;
        } elseif (!empty($p['quitar_logo'])) {
            if ($logo && is_file(UPLOAD_PATH . '/' . $logo)) @unlink(UPLOAD_PATH . '/' . $logo);
            $logo = null;
        }

        Database::q('UPDATE empresa_config SET nombre=?,razon_social=?,ruc=?,direccion=?,telefono=?,email=?,web=?,logo=?,
            moneda_codigo=?,moneda_simbolo=?,simbolo_posicion=?,decimales=?,sep_decimal=?,sep_miles=?,
            impuesto_nombre=?,impuesto_porcentaje=?,precios_incluyen_impuesto=?,zona_horaria=?,formato_fecha=?,prefijo_orden=?,pie_ticket=? WHERE id=1', [
            trim($p['nombre'] ?? '') ?: 'Mi Carwash', trim($p['razon_social'] ?? ''), trim($p['ruc'] ?? ''), trim($p['direccion'] ?? ''),
            trim($p['telefono'] ?? ''), trim($p['email'] ?? ''), trim($p['web'] ?? ''), $logo,
            strtoupper(substr(trim($p['moneda_codigo'] ?? 'PEN'), 0, 5)), trim($p['moneda_simbolo'] ?? 'S/') ?: 'S/',
            ($p['simbolo_posicion'] ?? 'antes') === 'despues' ? 'despues' : 'antes', $dec, $sepD, $sepM,
            trim($p['impuesto_nombre'] ?? 'IGV'), max(0, min(100, (float)($p['impuesto_porcentaje'] ?? 0))),
            empty($p['precios_incluyen_impuesto']) ? 0 : 1, $zona, $ff, trim($p['prefijo_orden'] ?? 'OS-'), trim($p['pie_ticket'] ?? ''),
        ]);
        log_action('config', 'Configuración de empresa actualizada');
        flash('success', 'Configuración guardada correctamente.');
        $this->redirect('configuracion');
    }
}
