<?php
/** Carga / elimina registros de ejemplo (marcados con [DEMO]) para que el dashboard y los reportes muestren información. Solo administrador. */
class DemoController extends Controller {
    const TAG = '[DEMO]';

    public function index() {
        $this->requireModule('configuracion');
        $n = fn($sql) => (int)Database::val($sql);
        $stats = [
            'clientes' => $n("SELECT COUNT(*) FROM clientes WHERE notas LIKE '%[DEMO]%'"), 'ordenes' => $n("SELECT COUNT(*) FROM ordenes WHERE observaciones LIKE '%[DEMO]%'"),
            'gastos' => $n("SELECT COUNT(*) FROM gastos WHERE concepto LIKE '%[DEMO]%'"), 'compras' => $n("SELECT COUNT(*) FROM compras WHERE notas LIKE '%[DEMO]%'"),
            'citas' => $n("SELECT COUNT(*) FROM citas WHERE notas LIKE '%[DEMO]%'"),
            'catalogo' => $n('SELECT COUNT(*) FROM servicios') > 0 && $n('SELECT COUNT(*) FROM empleados') > 0 && $n('SELECT COUNT(*) FROM tipos_vehiculo') > 0,
            'reales' => $n("SELECT COUNT(*) FROM ordenes WHERE observaciones IS NULL OR observaciones NOT LIKE '%[DEMO]%'"),
        ];
        $this->view('demo/index', ['st' => $stats, 'title' => 'Datos de ejemplo']);
    }

    public function cargar() {
        $this->requireModule('configuracion');
        if (!$this->isPost()) $this->redirect('demo');
        csrf_check();
        if (Database::val("SELECT COUNT(*) FROM ordenes WHERE observaciones LIKE '%[DEMO]%'")) { flash('warning', 'Ya hay datos de ejemplo cargados. Quítalos primero si deseas regenerarlos.'); $this->redirect('demo'); }
        $dias = max(30, min(365, (int)($_POST['dias'] ?? 120)));
        $pdo = Database::pdo(); $pdo->beginTransaction();
        try { $res = $this->generar($dias); $pdo->commit(); }
        catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); flash('danger', 'No se pudo cargar: ' . e($e->getMessage())); $this->redirect('demo'); }
        log_action('demo', 'Datos de ejemplo cargados');
        flash('success', 'Datos de ejemplo cargados: ' . implode(', ', array_map(fn($k, $v) => "$v $k", array_keys($res), $res)) . '.');
        $this->redirect('dashboard');
    }

    public function quitar() {
        $this->requireModule('configuracion');
        if (!$this->isPost()) $this->redirect('demo');
        csrf_check();
        $pdo = Database::pdo(); $pdo->beginTransaction();
        try {
            foreach (Database::all("SELECT d.producto_id, SUM(d.cantidad) q FROM compra_detalle d JOIN compras c ON c.id=d.compra_id WHERE c.notas LIKE '%[DEMO]%' GROUP BY d.producto_id") as $r)
                Database::q('UPDATE productos SET stock=GREATEST(0, stock-?) WHERE id=?', [$r['q'], $r['producto_id']]);
            Database::q("DELETE FROM movimientos_inventario WHERE motivo LIKE '%[DEMO]%'");
            Database::q("DELETE FROM compras WHERE notas LIKE '%[DEMO]%'");
            Database::q("DELETE FROM cajas WHERE observaciones LIKE '%[DEMO]%'");
            Database::q("DELETE FROM citas WHERE notas LIKE '%[DEMO]%'");
            Database::q("DELETE FROM gastos WHERE concepto LIKE '%[DEMO]%'");
            Database::q("DELETE FROM ordenes WHERE observaciones LIKE '%[DEMO]%'");
            Database::q("DELETE FROM crm_oportunidades WHERE notas LIKE '%[DEMO]%'");
            Database::q("DELETE FROM crm_cupones WHERE descripcion LIKE '%[DEMO]%'");
            Database::q("DELETE FROM vehiculos WHERE cliente_id IN (SELECT id FROM clientes WHERE notas LIKE '%[DEMO]%')");
            Database::q("DELETE FROM clientes WHERE notas LIKE '%[DEMO]%'");
            $pdo->commit();
        } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); flash('danger', 'No se pudo quitar: ' . e($e->getMessage())); $this->redirect('demo'); }
        log_action('demo', 'Datos de ejemplo eliminados');
        flash('success', 'Datos de ejemplo eliminados. Tus datos reales no se tocaron.'); $this->redirect('demo');
    }

    private function generar(int $dias): array {
        mt_srand(2024);
        $T = self::TAG; $uid = (int)auth_user()['id'];
        $serv = Database::all('SELECT id,nombre,precio FROM servicios WHERE estado=1');
        $emps = Database::all('SELECT id FROM empleados WHERE estado=1');
        $tipos = Database::all('SELECT id FROM tipos_vehiculo WHERE estado=1');
        $prods = Database::all('SELECT id,precio_compra FROM productos WHERE estado=1');
        $provs = Database::all('SELECT id FROM proveedores WHERE estado=1');
        if (!$serv || !$emps || !$tipos) throw new RuntimeException('Primero registra servicios, empleados y tipos de vehículo (o instala el sistema con datos de demostración).');
        $precio = [];
        foreach (Database::all('SELECT servicio_id s, tipo_vehiculo_id t, precio p FROM servicio_precios') as $r) $precio[$r['s']][$r['t']] = (float)$r['p'];
        $cfg = empresa(); $rate = (float)$cfg['impuesto_porcentaje'] / 100; $inc = (bool)$cfg['precios_incluyen_impuesto'];
        $pick = fn(array $a) => $a[mt_rand(0, count($a) - 1)];

        // ---- Clientes y vehículos
        $pn = ['Luis','María','Jorge','Rosa','Carlos','Lucía','Miguel','Patricia','Andrés','Valeria','Héctor','Sofía','Raúl','Daniela','Fernando','Camila','Óscar','Julia','Ricardo','Elena'];
        $ap = ['Fernández','Quispe','Ramos','Mendoza','Paredes','Torres','Castillo','Rojas','Salazar','Núñez','Villanueva','Herrera','Campos','Ortiz','Silva','Reyes','Medina','Cabrera','Vargas','Flores'];
        $nom = [];
        for ($i = 0; $i < 57; $i++) $nom[] = $pn[$i % 20] . ' ' . $ap[(int)(($i * 7 + intdiv($i, 20) * 3) % 20)];
        array_push($nom, 'Transportes Andes SAC', 'Inversiones Pacífico SRL', 'Constructora Del Sur SAC');
        $marcas = [['Toyota','Yaris'],['Toyota','Hilux'],['Hyundai','Tucson'],['Kia','Rio'],['Nissan','Sentra'],['Chevrolet','Spark'],['Suzuki','Swift'],['Mazda','CX-5'],['Honda','CR-V'],['Ford','Ranger'],['Mitsubishi','L200'],['Honda','XR150']];
        $colores = ['Blanco','Negro','Plata','Rojo','Azul','Gris'];
        $cl = [];
        foreach ($nom as $i => $nombre) {
            $cre = date('Y-m-d H:i:s', strtotime('-' . mt_rand(1, $dias + 60) . ' days'));
            $nac = mt_rand(0, 3) ? date('Y-m-d', strtotime('-' . mt_rand(22 * 365, 60 * 365) . ' days')) : null;
            if ($i < 4) $nac = date('Y-m-d', strtotime('-' . (28 + $i) . ' years +' . (2 + $i * 6) . ' days'));
            $comp = $i >= 57;
            Database::q('INSERT INTO clientes (tipo_doc,nro_doc,nombre,telefono,email,notas,fecha_nacimiento,acepta_marketing,created_at) VALUES (?,?,?,?,?,?,?,?,?)',
                [$comp ? 'RUC' : 'DNI', $comp ? '20' . mt_rand(10000000, 99999999) : (string)mt_rand(10000000, 79999999), $nombre, '9' . mt_rand(10000000, 99999999), mt_rand(0, 2) ? null : 'cliente' . $i . '@correo.com', $T, $nac, mt_rand(0, 9) ? 1 : 0, $cre]);
            $cid = (int)Database::id(); $veh = [];
            for ($v = 0, $nv = $comp ? 3 : mt_rand(1, 2); $v < $nv; $v++) {
                [$ma, $mo] = $pick($marcas); $tv = $mo === 'XR150' ? 4 : $pick($tipos)['id'];
                Database::q('INSERT INTO vehiculos (cliente_id,placa,marca,modelo,color,anio,tipo_vehiculo_id) VALUES (?,?,?,?,?,?,?)',
                    [$cid, chr(mt_rand(65, 90)) . chr(mt_rand(65, 90)) . chr(mt_rand(65, 90)) . '-' . mt_rand(100, 999), $ma, $mo, $pick($colores), mt_rand(2012, 2024), $tv]);
                $veh[] = ['id' => (int)Database::id(), 'tipo' => (int)$tv];
            }
            $cl[] = ['id' => $cid, 'veh' => $veh, 'peso' => $comp ? 4 : mt_rand(1, 4), 'hasta' => mt_rand(0, 9) < 3 ? mt_rand(50, 130) : 0];
        }
        $bag = []; foreach ($cl as $i => $c) for ($k = 0; $k < $c['peso']; $k++) $bag[] = $i;

        // ---- Órdenes por día
        $ins = Database::pdo()->prepare('INSERT INTO ordenes (codigo,cliente_id,vehiculo_id,empleado_id,usuario_id,estado,subtotal,descuento,impuesto,total,metodo_pago,pagado,fecha_pago,observaciones,fecha_ingreso,fecha_fin) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $det = Database::pdo()->prepare('INSERT INTO orden_detalle (orden_id,tipo,item_id,descripcion,cantidad,precio,subtotal) VALUES (?,?,?,?,?,?,?)');
        $metodos = ['efectivo', 'efectivo', 'efectivo', 'yape_plin', 'yape_plin', 'tarjeta', 'transferencia'];
        $nOrd = 0; $efectivoDia = [];
        for ($d = $dias; $d >= 0; $d--) {
            $f = strtotime("-$d days"); $dow = (int)date('N', $f);
            $n = $d === 0 ? mt_rand(5, 8) : ($dow >= 6 ? mt_rand(8, 14) : ($dow === 7 ? mt_rand(3, 6) : mt_rand(4, 10)));
            $n = (int)round($n * (0.75 + 0.25 * (($dias - $d) / max(1, $dias)) + 0.1)); // leve tendencia de crecimiento
            for ($i = 0; $i < $n; $i++) {
                $hora = mt_rand(8, 18); $min = mt_rand(0, 59);
                $ing = date('Y-m-d', $f) . sprintf(' %02d:%02d:00', $hora, $min);
                if ($d === 0 && $ing > date('Y-m-d H:i:s')) $ing = date('Y-m-d H:i:s', strtotime('-' . mt_rand(20, 200) . ' minutes'));
                $c = null;
                if (mt_rand(0, 9) < 4) { for ($try = 0; $try < 6; $try++) { $cc = $cl[$pick($bag)]; if ($cc['hasta'] <= $d) { $c = $cc; break; } } } $vh = $c ? $pick($c['veh']) : null; $tv = $vh ? $vh['tipo'] : (int)$pick($tipos)['id'];
                $lineas = []; $sub = 0;
                for ($s = 0, $ns = mt_rand(0, 3) === 0 ? 2 : 1; $s < $ns; $s++) {
                    $sv = $pick($serv); if (isset($lineas[$sv['id']])) continue;
                    $p = $precio[$sv['id']][$tv] ?? (float)$sv['precio']; $lineas[$sv['id']] = [$sv['nombre'], $p]; $sub += $p;
                }
                $desc = mt_rand(0, 9) === 0 ? round($sub * 0.1, 2) : 0; $base = $sub - $desc;
                if ($inc) { $tot = $base; $imp = round($base - $base / (1 + $rate), 2); } else { $imp = round($base * $rate, 2); $tot = $base + $imp; }
                if ($d === 0) { $est = $pick(['pendiente', 'en_proceso', 'en_proceso', 'terminado', 'entregado', 'entregado']); }
                elseif ($d === 1) { $est = mt_rand(0, 9) === 0 ? 'terminado' : 'entregado'; }
                else $est = mt_rand(0, 24) === 0 ? 'cancelado' : 'entregado';
                $pag = in_array($est, ['entregado']) || ($est === 'terminado' && mt_rand(0, 1)) ? 1 : 0;
                $met = $pag ? $pick($metodos) : null; $fp = $pag ? date('Y-m-d H:i:s', strtotime($ing) + mt_rand(30, 90) * 60) : null;
                if ($fp && $fp > date('Y-m-d H:i:s')) $fp = date('Y-m-d H:i:s');
                $ff = in_array($est, ['terminado', 'entregado']) ? ($fp ?: date('Y-m-d H:i:s', strtotime($ing) + 3600)) : null;
                $ins->execute([uniqid('D'), $c['id'] ?? null, $vh['id'] ?? null, $pick($emps)['id'], $uid, $est, $sub, $desc, $imp, $tot, $met, $pag, $fp, $T, $ing, $ff]);
                $oid = (int)Database::id();
                Database::q('UPDATE ordenes SET codigo=? WHERE id=?', [$cfg['prefijo_orden'] . str_pad((string)$oid, 6, '0', STR_PAD_LEFT), $oid]);
                foreach ($lineas as $sid => [$sn, $sp]) $det->execute([$oid, 'servicio', $sid, $sn, 1, $sp, $sp]);
                $nOrd++;
                if ($pag && $met === 'efectivo') $efectivoDia[date('Y-m-d', strtotime($fp))] = ($efectivoDia[date('Y-m-d', strtotime($fp))] ?? 0) + $tot;
            }
        }

        // ---- Pagos (uno por orden cobrada), propinas y algunas cuentas por cobrar (crédito / abonos parciales)
        Database::q("INSERT INTO orden_pagos (orden_id,monto,propina,metodo,fecha) SELECT id, total, IF(RAND()<0.12, ROUND(1+RAND()*4), 0), COALESCE(metodo_pago,'efectivo'), COALESCE(fecha_pago,fecha_ingreso) FROM ordenes WHERE observaciones LIKE '%[DEMO]%' AND pagado=1 AND total>0");
        $cred = Database::all("SELECT id,total FROM ordenes WHERE observaciones LIKE '%[DEMO]%' AND estado='entregado' AND pagado=1 AND cliente_id IS NOT NULL AND total>10 AND fecha_ingreso < DATE_SUB(CURDATE(), INTERVAL 1 DAY) ORDER BY fecha_ingreso DESC LIMIT 60");
        $nCred = 0;
        foreach ($cred as $i => $o) {
            if (mt_rand(0, 3) !== 0) continue;
            Database::q('DELETE FROM orden_pagos WHERE orden_id=?', [$o['id']]);
            Database::q('UPDATE ordenes SET pagado=0, fecha_pago=NULL, metodo_pago=NULL WHERE id=?', [$o['id']]);
            if (mt_rand(0, 1)) Database::q('INSERT INTO orden_pagos (orden_id,monto,metodo,fecha) SELECT id, ROUND(total*0.4,2), ?, fecha_ingreso FROM ordenes WHERE id=?', [$pick(['efectivo', 'yape_plin']), $o['id']]);
            $nCred++;
        }

        // ---- Cajas cerradas (últimos 10 días)
        $nCaj = 0;
        for ($d = 10; $d >= 1; $d--) {
            $dia = date('Y-m-d', strtotime("-$d days")); $esp = 100 + ($efectivoDia[$dia] ?? 0); $dif = mt_rand(0, 4) === 0 ? -mt_rand(1, 10) : 0;
            Database::q("INSERT INTO cajas (usuario_id,monto_inicial,monto_final,monto_esperado,fecha_apertura,fecha_cierre,estado,observaciones) VALUES (?,?,?,?,?,?, 'cerrada', ?)", [$uid, 100, $esp + $dif, $esp, "$dia 08:00:00", "$dia 19:30:00", $T]);
            $nCaj++;
        }

        // ---- Gastos
        $nGas = 0; $g = fn($f, $cat, $con, $m) => Database::q('INSERT INTO gastos (fecha,categoria,concepto,monto,usuario_id) VALUES (?,?,?,?,?)', [$f, $cat, "$con $T", $m, $uid]);
        $meses = (int)ceil($dias / 30);
        for ($m = $meses; $m >= 0; $m--) {
            $b = strtotime("first day of -$m month");
            $f = fn($dd) => date('Y-m-', $b) . sprintf('%02d', $dd);
            $cand = [[$f(1), 'Alquiler', 'Alquiler del local', 1800], [$f(5), 'Servicios', 'Agua y alcantarillado', mt_rand(280, 420)], [$f(8), 'Servicios', 'Energía eléctrica', mt_rand(350, 560)],
                     [$f(10), 'Servicios', 'Internet y teléfono', 120], [$f(28), 'Sueldos', 'Planilla del personal', 2400], [$f(15), 'Marketing', 'Publicidad en redes sociales', mt_rand(80, 200)],
                     [$f(12), 'Mantenimiento', 'Mantenimiento de hidrolavadoras', mt_rand(60, 250)], [$f(20), 'Insumos', 'Reposición de insumos varios', mt_rand(150, 400)]];
            foreach ($cand as [$fe, $cat, $con, $mo]) { if ($fe <= date('Y-m-d') && $fe >= date('Y-m-d', strtotime("-$dias days -1 month"))) { $g($fe, $cat, $con, $mo); $nGas++; } }
        }

        // ---- Compras (con ingreso a inventario)
        $nCom = 0;
        if ($prods) for ($m = $meses; $m >= 0; $m--) for ($k = 0; $k < 2; $k++) {
            $fe = date('Y-m-d', strtotime("-" . ($m * 30 + $k * 14 + 3) . ' days')); if ($fe > date('Y-m-d') || $fe < date('Y-m-d', strtotime("-$dias days"))) continue;
            $tot = 0; $its = [];
            foreach (array_slice($prods, 0, mt_rand(1, min(3, count($prods)))) as $p) { $q = mt_rand(5, 20); $co = (float)$p['precio_compra'] ?: 10; $its[] = [$p['id'], $q, $co]; $tot += $q * $co; }
            Database::q('INSERT INTO compras (proveedor_id,fecha,nro_doc,total,notas,usuario_id) VALUES (?,?,?,?,?,?)', [$provs ? $pick($provs)['id'] : null, $fe, 'F001-' . mt_rand(100, 999), $tot, $T, $uid]);
            $cid = (int)Database::id();
            foreach ($its as [$pid, $q, $co]) {
                Database::q('INSERT INTO compra_detalle (compra_id,producto_id,cantidad,costo,subtotal) VALUES (?,?,?,?,?)', [$cid, $pid, $q, $co, $q * $co]);
                Database::q('UPDATE productos SET stock=stock+? WHERE id=?', [$q, $pid]);
                Database::q("INSERT INTO movimientos_inventario (producto_id,tipo,cantidad,motivo,usuario_id) VALUES (?, 'entrada', ?, ?, ?)", [$pid, $q, "Compra $T", $uid]);
            }
            $nCom++;
        }

        // ---- Citas
        $nCit = 0;
        $citas = [[0, 9], [0, 11], [0, 14], [0, 16], [1, 10], [1, 15], [2, 9], [3, 11], [4, 14], [5, 10], [-1, 10], [-1, 15], [-3, 11], [-6, 9]];
        foreach ($citas as [$off, $h]) {
            $c = $cl[mt_rand(0, count($cl) - 1)]; $vh = $pick($c['veh']);
            $est = $off < 0 ? 'atendida' : ($off === 0 ? $pick(['programada', 'confirmada']) : $pick(['programada', 'confirmada', 'confirmada']));
            if ($off === 0 && $h * 60 < (int)date('G') * 60 - 120) $est = 'atendida';
            Database::q('INSERT INTO citas (cliente_id,vehiculo_id,servicio_id,fecha_hora,estado,notas) VALUES (?,?,?,?,?,?)', [$c['id'], $vh['id'], $pick($serv)['id'], date('Y-m-d', strtotime("$off days")) . sprintf(' %02d:00:00', $h), $est, $T]);
            $nCit++;
        }

        // ---- CRM: seguimientos, oportunidades, cupones y encuestas
        foreach ([[0, 'llamada', 'Ofrecer plan mensual de lavado', 2], [3, 'whatsapp', 'Confirmar cita de detallado', 1], [7, 'nota', 'Cliente pidió factura a nombre de empresa', null], [11, 'llamada', 'Recordar pago pendiente', -1]] as [$i, $tp, $as, $fd]) {
            Database::q('INSERT INTO crm_interacciones (cliente_id,tipo,asunto,seguimiento_fecha,usuario_id) VALUES (?,?,?,?,?)', [$cl[$i]['id'], $tp, "$as", $fd === null ? null : date('Y-m-d', strtotime("$fd days")), $uid]);
        }
        foreach ([['Flota Constructora Norte', 'Lavado de 8 camionetas semanales', 'Empresa / Flota', 2400, 'propuesta'], ['Hotel Mirador', 'Servicio para autos de huéspedes', 'Referido', 900, 'contactado'], ['Taxis Express', 'Convenio 15 taxis', 'Facebook', 1500, 'nuevo'], ['Colegio San José', 'Lavado de buses escolares', 'Referido', 1800, 'ganado'], ['Rent a Car Sol', 'Detallado mensual', 'Instagram', 2100, 'perdido']] as [$n, $in, $or, $va, $et])
            Database::q('INSERT INTO crm_oportunidades (nombre,telefono,interes,origen,valor_est,etapa,notas,usuario_id) VALUES (?,?,?,?,?,?,?,?)', [$n, '9' . mt_rand(10000000, 99999999), $in, $or, $va, $et, $T, $uid]);
        Database::q("INSERT INTO crm_cupones (codigo,descripcion,tipo,valor,vigente_hasta,usos_max) VALUES (?,?, 'porcentaje', 10, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 0)", ['DEMO10', "Promo de bienvenida $T"]);
        $nEnc = 0;
        foreach (Database::all("SELECT id,cliente_id FROM ordenes WHERE observaciones LIKE '%[DEMO]%' AND estado='entregado' AND cliente_id IS NOT NULL ORDER BY id DESC LIMIT 30") as $o) {
            $p = $pick([5, 5, 5, 4, 4, 4, 3, 5, 2]);
            Database::q('INSERT INTO crm_encuestas (orden_id,cliente_id,token,puntaje,comentario,respondido_at) VALUES (?,?,?,?,?,NOW() - INTERVAL ? DAY)', [$o['id'], $o['cliente_id'], bin2hex(random_bytes(12)), $p,
                $p >= 4 ? $pick(['Excelente atención', 'Quedó impecable mi auto', 'Rápido y buen precio', null]) : $pick(['Demoraron más de lo previsto', null]), mt_rand(0, 10)]);
            $nEnc++;
        }
        crm_sync_puntos();
        return ['órdenes' => $nOrd, 'clientes' => count($cl), 'gastos' => $nGas, 'compras' => $nCom, 'citas' => $nCit, 'cierres de caja' => $nCaj, 'cuentas por cobrar' => $nCred, 'encuestas' => $nEnc];
    }
}
