<?php
/** Recepción del vehículo: checklist, daños previos, nivel de combustible, fotos y firma del cliente. */
class RecepcionesController extends Controller {
    const CHECK = ['radio' => 'Radio / pantalla', 'gata' => 'Gata y llave de ruedas', 'repuesto' => 'Llanta de repuesto', 'tapas' => 'Tapas de llantas', 'alfombras' => 'Alfombras', 'extintor' => 'Extintor / botiquín', 'documentos' => 'Documentos en el vehículo', 'valores' => 'Objetos de valor a la vista', 'antena' => 'Antena', 'espejos' => 'Espejos completos'];
    const ZONAS = ['parag_del' => 'Parachoques delantero', 'capo' => 'Capó', 'parabrisas' => 'Parabrisas', 'techo' => 'Techo', 'puerta_izq' => 'Lado izquierdo', 'puerta_der' => 'Lado derecho', 'maletera' => 'Maletera / luneta', 'parag_tras' => 'Parachoques trasero', 'llantas' => 'Llantas / aros', 'interior' => 'Interior / tapizado'];
    const DANOS = ['' => 'Sin daños', 'rayon' => 'Rayón', 'abolladura' => 'Abolladura', 'roto' => 'Roto / agrietado', 'mancha' => 'Mancha / desgaste'];
    const MAXFOTOS = 8;

    public function index() {
        $this->requireModule('ordenes');
        $q = trim($_GET['q'] ?? '');
        $w = '1=1'; $p = [];
        if ($q !== '') { $w = '(r.placa LIKE ? OR c.nombre LIKE ?)'; $p = ["%$q%", "%$q%"]; }
        $rows = Database::all("SELECT r.*, c.nombre cliente, o.codigo orden FROM recepciones r LEFT JOIN clientes c ON c.id=r.cliente_id LEFT JOIN ordenes o ON o.id=r.orden_id WHERE $w ORDER BY r.id DESC LIMIT 300", $p);
        $this->view('recepciones/index', ['rows' => $rows, 'q' => $q, 'title' => 'Recepción de vehículos']);
    }

    public function nueva() {
        $this->requireModule('ordenes');
        $clientes = Database::all('SELECT id,nombre FROM clientes WHERE estado=1 ORDER BY nombre');
        $vehiculos = Database::all('SELECT id,cliente_id,placa,marca,modelo FROM vehiculos WHERE estado=1 ORDER BY placa');
        $this->view('recepciones/nueva', ['clientes' => $clientes, 'vehiculos' => $vehiculos, 'title' => 'Nueva recepción']);
    }

    private function guardarArchivo(string $tmp, string $ext): string {
        $dir = UPLOAD_PATH . '/recepciones';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $name = 'r_' . date('ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        return move_uploaded_file($tmp, "$dir/$name") || @rename($tmp, "$dir/$name") ? $name : '';
    }

    public function guardar() {
        $this->requireModule('ordenes');
        if (!$this->isPost()) $this->redirect('recepciones');
        csrf_check();
        $vid = (int)($_POST['vehiculo_id'] ?? 0); $cid = (int)($_POST['cliente_id'] ?? 0) ?: null;
        $placa = strtoupper(trim($_POST['placa'] ?? ''));
        if ($vid) { $v = Database::row('SELECT * FROM vehiculos WHERE id=?', [$vid]); if ($v) { $placa = $v['placa']; $cid = $cid ?: ($v['cliente_id'] ? (int)$v['cliente_id'] : null); } else $vid = 0; }
        if ($placa === '') { flash('danger', 'Indica la placa o elige un vehículo.'); $this->redirect('recepciones/nueva'); }
        $check = array_values(array_intersect(array_keys(self::CHECK), (array)($_POST['check'] ?? [])));
        $danos = [];
        foreach ((array)($_POST['dano'] ?? []) as $z => $t) if (isset(self::ZONAS[$z]) && $t !== '' && isset(self::DANOS[$t])) $danos[$z] = $t;
        // Fotos: solo imágenes reales
        $fotos = [];
        if (!empty($_FILES['fotos']['name'][0])) {
            foreach ($_FILES['fotos']['name'] as $i => $n) {
                if (count($fotos) >= self::MAXFOTOS) break;
                if ($_FILES['fotos']['error'][$i] !== UPLOAD_ERR_OK || $_FILES['fotos']['size'][$i] > 8 * 1024 * 1024) continue;
                $info = @getimagesize($_FILES['fotos']['tmp_name'][$i]);
                $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$info['mime'] ?? ''] ?? null;
                if (!$ext) continue;
                if ($f = $this->guardarArchivo($_FILES['fotos']['tmp_name'][$i], $ext)) $fotos[] = $f;
            }
        }
        // Firma (PNG en base64)
        $firma = null;
        if (preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', (string)($_POST['firma'] ?? ''), $m) && strlen($m[1]) < 400000) {
            $bin = base64_decode($m[1], true);
            if ($bin !== false && @getimagesizefromstring($bin)) {
                $dir = UPLOAD_PATH . '/recepciones'; if (!is_dir($dir)) mkdir($dir, 0777, true);
                $firma = 'f_' . date('ymd_His') . '_' . bin2hex(random_bytes(4)) . '.png'; file_put_contents("$dir/$firma", $bin);
            }
        }
        Database::q('INSERT INTO recepciones (cliente_id,vehiculo_id,placa,combustible,kilometraje,checklist,danos,fotos,firma,observaciones,usuario_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)', [
            $cid, $vid ?: null, $placa, max(0, min(100, (int)($_POST['combustible'] ?? 50))), ($k = (int)($_POST['kilometraje'] ?? 0)) > 0 ? $k : null,
            json_encode($check), json_encode($danos), json_encode($fotos), $firma, mb_substr(trim($_POST['observaciones'] ?? ''), 0, 500) ?: null, auth_user()['id']]);
        $id = (int)Database::id();
        log_action('ordenes', "Recepción #$id del vehículo $placa");
        flash('success', "Recepción <b>#$id</b> registrada. Ya puedes generar la orden de servicio.");
        $this->redirect('recepciones/ver/' . $id);
    }

    private function cargar(int $id): array {
        $r = Database::row('SELECT r.*, c.nombre cliente, c.telefono, v.marca, v.modelo, v.color, u.nombre usuario, o.codigo orden FROM recepciones r LEFT JOIN clientes c ON c.id=r.cliente_id
            LEFT JOIN vehiculos v ON v.id=r.vehiculo_id LEFT JOIN usuarios u ON u.id=r.usuario_id LEFT JOIN ordenes o ON o.id=r.orden_id WHERE r.id=?', [$id]);
        if (!$r) { flash('danger', 'Recepción no encontrada.'); $this->redirect('recepciones'); }
        $r['check_l'] = json_decode($r['checklist'] ?? '[]', true) ?: []; $r['danos_l'] = json_decode($r['danos'] ?? '{}', true) ?: []; $r['fotos_l'] = json_decode($r['fotos'] ?? '[]', true) ?: [];
        return $r;
    }

    public function ver($id = 0) {
        $this->requireModule('ordenes');
        $r = $this->cargar((int)$id);
        $this->view('recepciones/ver', ['r' => $r, 'title' => 'Recepción #' . $r['id']]);
    }

    public function acta($id = 0) {
        $this->requireModule('ordenes');
        $r = $this->cargar((int)$id); $emp = empresa();
        $this->view('recepciones/acta', ['r' => $r, 'emp' => $emp], 'none');
    }

    public function eliminar() {
        $this->requireModule('ordenes');
        if (!$this->isPost()) $this->redirect('recepciones');
        csrf_check();
        $r = $this->cargar((int)($_POST['id'] ?? 0));
        foreach (array_merge($r['fotos_l'], [$r['firma']]) as $f) if ($f && preg_match('/^[\w.]+$/', $f)) @unlink(UPLOAD_PATH . '/recepciones/' . $f);
        Database::q('DELETE FROM recepciones WHERE id=?', [$r['id']]);
        log_action('ordenes', "Eliminó recepción #{$r['id']}");
        flash('success', 'Recepción eliminada.');
        $this->redirect('recepciones');
    }
}
