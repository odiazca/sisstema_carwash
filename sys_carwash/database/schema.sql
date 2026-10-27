-- Sistema CARWASH - esquema inicial
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS empresa_config (
  id TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
  nombre VARCHAR(150) NOT NULL DEFAULT 'Mi Carwash',
  razon_social VARCHAR(180) NULL,
  ruc VARCHAR(30) NULL,
  direccion VARCHAR(255) NULL,
  telefono VARCHAR(40) NULL,
  email VARCHAR(120) NULL,
  web VARCHAR(150) NULL,
  logo VARCHAR(255) NULL,
  moneda_codigo VARCHAR(5) NOT NULL DEFAULT 'PEN',
  moneda_simbolo VARCHAR(8) NOT NULL DEFAULT 'S/',
  simbolo_posicion ENUM('antes','despues') NOT NULL DEFAULT 'antes',
  decimales TINYINT NOT NULL DEFAULT 2,
  sep_decimal CHAR(1) NOT NULL DEFAULT '.',
  sep_miles CHAR(1) NOT NULL DEFAULT ',',
  impuesto_nombre VARCHAR(20) NOT NULL DEFAULT 'IGV',
  impuesto_porcentaje DECIMAL(5,2) NOT NULL DEFAULT 18.00,
  precios_incluyen_impuesto TINYINT(1) NOT NULL DEFAULT 1,
  zona_horaria VARCHAR(50) NOT NULL DEFAULT 'America/Lima',
  formato_fecha VARCHAR(20) NOT NULL DEFAULT 'd/m/Y',
  prefijo_orden VARCHAR(10) NOT NULL DEFAULT 'OS-',
  pie_ticket VARCHAR(255) NULL DEFAULT 'Gracias por su preferencia',
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL UNIQUE,
  permisos TEXT NOT NULL COMMENT '* = todo, o lista de modulos separados por coma'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  usuario VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(120) NULL,
  password VARCHAR(255) NOT NULL,
  rol_id INT NOT NULL,
  estado TINYINT(1) NOT NULL DEFAULT 1,
  ultimo_acceso DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipo_doc VARCHAR(10) NOT NULL DEFAULT 'DNI',
  nro_doc VARCHAR(20) NULL,
  nombre VARCHAR(150) NOT NULL,
  telefono VARCHAR(30) NULL,
  email VARCHAR(120) NULL,
  direccion VARCHAR(200) NULL,
  notas TEXT NULL,
  fecha_nacimiento DATE NULL,
  acepta_marketing TINYINT(1) NOT NULL DEFAULT 1,
  estado TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tipos_vehiculo (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(60) NOT NULL,
  estado TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS vehiculos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  placa VARCHAR(15) NOT NULL,
  marca VARCHAR(50) NULL,
  modelo VARCHAR(50) NULL,
  color VARCHAR(30) NULL,
  anio SMALLINT NULL,
  tipo_vehiculo_id INT NULL,
  notas VARCHAR(255) NULL,
  estado TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (cliente_id) REFERENCES clientes(id),
  FOREIGN KEY (tipo_vehiculo_id) REFERENCES tipos_vehiculo(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categorias_servicio (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL,
  estado TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS servicios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  categoria_id INT NULL,
  nombre VARCHAR(120) NOT NULL,
  descripcion VARCHAR(255) NULL,
  precio DECIMAL(12,2) NOT NULL DEFAULT 0,
  duracion_min INT NOT NULL DEFAULT 30,
  comision_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
  estado TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (categoria_id) REFERENCES categorias_servicio(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS servicio_precios (
  servicio_id INT NOT NULL,
  tipo_vehiculo_id INT NOT NULL,
  precio DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (servicio_id, tipo_vehiculo_id),
  FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE CASCADE,
  FOREIGN KEY (tipo_vehiculo_id) REFERENCES tipos_vehiculo(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS empleados (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  documento VARCHAR(20) NULL,
  telefono VARCHAR(30) NULL,
  cargo VARCHAR(60) NOT NULL DEFAULT 'Lavador',
  sueldo DECIMAL(12,2) NOT NULL DEFAULT 0,
  comision_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
  fecha_ingreso DATE NULL,
  estado TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS proveedores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  ruc VARCHAR(20) NULL,
  contacto VARCHAR(100) NULL,
  telefono VARCHAR(30) NULL,
  email VARCHAR(120) NULL,
  direccion VARCHAR(200) NULL,
  estado TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS productos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(30) NULL,
  nombre VARCHAR(150) NOT NULL,
  proveedor_id INT NULL,
  unidad VARCHAR(20) NOT NULL DEFAULT 'UND',
  precio_compra DECIMAL(12,2) NOT NULL DEFAULT 0,
  precio_venta DECIMAL(12,2) NOT NULL DEFAULT 0,
  stock DECIMAL(12,2) NOT NULL DEFAULT 0,
  stock_minimo DECIMAL(12,2) NOT NULL DEFAULT 0,
  se_vende TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0 = insumo interno',
  estado TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS movimientos_inventario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  producto_id INT NOT NULL,
  tipo ENUM('entrada','salida','ajuste') NOT NULL,
  cantidad DECIMAL(12,2) NOT NULL,
  motivo VARCHAR(150) NULL,
  usuario_id INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ordenes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(20) NOT NULL UNIQUE,
  tipo ENUM('servicio','venta') NOT NULL DEFAULT 'servicio',
  cliente_id INT NULL,
  vehiculo_id INT NULL,
  empleado_id INT NULL,
  usuario_id INT NULL,
  estado ENUM('pendiente','en_proceso','terminado','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  descuento DECIMAL(12,2) NOT NULL DEFAULT 0,
  impuesto DECIMAL(12,2) NOT NULL DEFAULT 0,
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  metodo_pago ENUM('efectivo','tarjeta','yape_plin','transferencia') NULL,
  pagado TINYINT(1) NOT NULL DEFAULT 0,
  fecha_pago DATETIME NULL,
  observaciones VARCHAR(255) NULL,
  fecha_ingreso DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_fin DATETIME NULL,
  FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
  FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE SET NULL,
  FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE SET NULL,
  INDEX (estado), INDEX (fecha_ingreso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orden_detalle (
  id INT AUTO_INCREMENT PRIMARY KEY,
  orden_id INT NOT NULL,
  tipo ENUM('servicio','producto') NOT NULL,
  item_id INT NOT NULL,
  descripcion VARCHAR(180) NOT NULL,
  cantidad DECIMAL(10,2) NOT NULL DEFAULT 1,
  precio DECIMAL(12,2) NOT NULL DEFAULT 0,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (orden_id) REFERENCES ordenes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS citas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  vehiculo_id INT NULL,
  servicio_id INT NULL,
  fecha_hora DATETIME NOT NULL,
  estado ENUM('programada','confirmada','atendida','cancelada') NOT NULL DEFAULT 'programada',
  notas VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
  FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE SET NULL,
  FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cajas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  monto_inicial DECIMAL(12,2) NOT NULL DEFAULT 0,
  monto_final DECIMAL(12,2) NULL,
  monto_esperado DECIMAL(12,2) NULL,
  fecha_apertura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_cierre DATETIME NULL,
  estado ENUM('abierta','cerrada') NOT NULL DEFAULT 'abierta',
  observaciones VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gastos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fecha DATE NOT NULL,
  categoria VARCHAR(60) NOT NULL DEFAULT 'Otros',
  concepto VARCHAR(180) NOT NULL,
  monto DECIMAL(12,2) NOT NULL DEFAULT 0,
  usuario_id INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS bitacora (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NULL,
  usuario VARCHAR(50) NULL,
  accion VARCHAR(60) NOT NULL,
  detalle VARCHAR(255) NULL,
  ip VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS paquetes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  descripcion VARCHAR(255) NULL,
  servicio_id INT NOT NULL,
  usos INT NOT NULL DEFAULT 4,
  precio DECIMAL(12,2) NOT NULL DEFAULT 0,
  vigencia_dias INT NOT NULL DEFAULT 90,
  estado TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (servicio_id) REFERENCES servicios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cliente_paquetes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  paquete_id INT NOT NULL,
  servicio_id INT NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  usos_total INT NOT NULL,
  usos_restantes INT NOT NULL,
  precio_pagado DECIMAL(12,2) NOT NULL DEFAULT 0,
  metodo_pago VARCHAR(20) NOT NULL DEFAULT 'efectivo',
  fecha_compra DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_vence DATE NULL,
  usuario_id INT NULL,
  FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
  FOREIGN KEY (paquete_id) REFERENCES paquetes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orden_paquete_uso (
  orden_id INT NOT NULL PRIMARY KEY,
  cliente_paquete_id INT NOT NULL,
  FOREIGN KEY (orden_id) REFERENCES ordenes(id) ON DELETE CASCADE,
  FOREIGN KEY (cliente_paquete_id) REFERENCES cliente_paquetes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS compras (
  id INT AUTO_INCREMENT PRIMARY KEY,
  proveedor_id INT NULL,
  fecha DATE NOT NULL,
  nro_doc VARCHAR(40) NULL,
  condicion ENUM('contado','credito') NOT NULL DEFAULT 'contado',
  vence DATE NULL,
  pagado DECIMAL(12,2) NOT NULL DEFAULT 0,
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  notas VARCHAR(255) NULL,
  usuario_id INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS compra_detalle (
  id INT AUTO_INCREMENT PRIMARY KEY,
  compra_id INT NOT NULL,
  producto_id INT NOT NULL,
  cantidad DECIMAL(12,2) NOT NULL,
  costo DECIMAL(12,2) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (compra_id) REFERENCES compras(id) ON DELETE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS comisiones_pagos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  empleado_id INT NOT NULL,
  desde DATE NOT NULL,
  hasta DATE NOT NULL,
  ventas DECIMAL(12,2) NOT NULL DEFAULT 0,
  monto DECIMAL(12,2) NOT NULL,
  usuario_id INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ia_config (
  clave VARCHAR(40) PRIMARY KEY,
  valor TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ia_consultas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  pregunta VARCHAR(500) NOT NULL,
  titulo VARCHAR(160) NULL,
  sql_texto TEXT NOT NULL,
  grafico VARCHAR(255) NULL,
  favorito TINYINT(1) NOT NULL DEFAULT 0,
  veces INT NOT NULL DEFAULT 1,
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  ultimo_uso DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (usuario_id, favorito)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS=1;

-- ===== CRM avanzado =====
CREATE TABLE IF NOT EXISTS crm_config (
  id TINYINT PRIMARY KEY,
  programa_activo TINYINT(1) NOT NULL DEFAULT 1,
  monto_por_punto DECIMAL(10,2) NOT NULL DEFAULT 10,
  valor_punto DECIMAL(10,2) NOT NULL DEFAULT 0.50,
  puntos_minimo INT NOT NULL DEFAULT 50,
  puntos_desde DATE NULL,
  vip_visitas INT NOT NULL DEFAULT 10,
  vip_monto DECIMAL(12,2) NOT NULL DEFAULT 1000,
  dias_riesgo INT NOT NULL DEFAULT 45,
  dias_inactivo INT NOT NULL DEFAULT 90,
  prefijo_pais VARCHAR(5) NOT NULL DEFAULT '51',
  msg_cumple TEXT NULL,
  msg_riesgo TEXT NULL,
  msg_encuesta TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crm_puntos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  orden_id INT NULL,
  tipo ENUM('ganado','canjeado','ajuste') NOT NULL,
  puntos INT NOT NULL,
  detalle VARCHAR(200) NULL,
  usuario_id INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_orden_tipo (orden_id, tipo),
  INDEX idx_cliente (cliente_id),
  FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crm_cupones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(30) NOT NULL UNIQUE,
  descripcion VARCHAR(150) NULL,
  tipo ENUM('porcentaje','monto') NOT NULL DEFAULT 'porcentaje',
  valor DECIMAL(10,2) NOT NULL,
  cliente_id INT NULL,
  vigente_hasta DATE NULL,
  usos_max INT NOT NULL DEFAULT 1,
  usos INT NOT NULL DEFAULT 0,
  estado TINYINT(1) NOT NULL DEFAULT 1,
  origen ENUM('manual','canje','cumple') NOT NULL DEFAULT 'manual',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crm_interacciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  tipo ENUM('llamada','whatsapp','email','visita','nota') NOT NULL DEFAULT 'nota',
  asunto VARCHAR(150) NOT NULL,
  detalle TEXT NULL,
  seguimiento_fecha DATE NULL,
  seguimiento_hecho TINYINT(1) NOT NULL DEFAULT 0,
  usuario_id INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_cliente (cliente_id), INDEX idx_seg (seguimiento_hecho, seguimiento_fecha),
  FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crm_oportunidades (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  telefono VARCHAR(30) NULL,
  email VARCHAR(120) NULL,
  interes VARCHAR(150) NULL,
  origen VARCHAR(40) NULL,
  valor_est DECIMAL(12,2) NOT NULL DEFAULT 0,
  etapa ENUM('nuevo','contactado','propuesta','ganado','perdido') NOT NULL DEFAULT 'nuevo',
  cliente_id INT NULL,
  notas TEXT NULL,
  usuario_id INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_etapa (etapa),
  FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crm_campanas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  segmento VARCHAR(30) NOT NULL,
  canal ENUM('whatsapp','email') NOT NULL DEFAULT 'whatsapp',
  mensaje TEXT NOT NULL,
  total INT NOT NULL DEFAULT 0,
  usuario_id INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crm_campana_envios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  campana_id INT NOT NULL,
  cliente_id INT NOT NULL,
  enviado TINYINT(1) NOT NULL DEFAULT 0,
  enviado_at DATETIME NULL,
  UNIQUE KEY uq_camp_cli (campana_id, cliente_id),
  FOREIGN KEY (campana_id) REFERENCES crm_campanas(id) ON DELETE CASCADE,
  FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crm_encuestas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  orden_id INT NOT NULL UNIQUE,
  cliente_id INT NULL,
  token VARCHAR(40) NOT NULL UNIQUE,
  puntaje TINYINT NULL,
  recomendaria TINYINT NULL,
  comentario VARCHAR(500) NULL,
  respondido_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (orden_id) REFERENCES ordenes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== Pagos parciales / cuentas por cobrar =====
CREATE TABLE IF NOT EXISTS orden_pagos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  orden_id INT NOT NULL,
  monto DECIMAL(12,2) NOT NULL,
  propina DECIMAL(12,2) NOT NULL DEFAULT 0,
  metodo ENUM('efectivo','tarjeta','yape_plin','transferencia') NOT NULL DEFAULT 'efectivo',
  referencia VARCHAR(80) NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  usuario_id INT NULL,
  anulado TINYINT(1) NOT NULL DEFAULT 0,
  INDEX idx_orden (orden_id), INDEX idx_fecha (fecha),
  FOREIGN KEY (orden_id) REFERENCES ordenes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS recepciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  orden_id INT NULL,
  cliente_id INT NULL,
  vehiculo_id INT NULL,
  placa VARCHAR(20) NOT NULL,
  combustible TINYINT NOT NULL DEFAULT 50,
  kilometraje INT NULL,
  checklist TEXT NULL,
  danos TEXT NULL,
  fotos TEXT NULL,
  firma VARCHAR(120) NULL,
  observaciones VARCHAR(500) NULL,
  usuario_id INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_placa (placa), INDEX idx_orden (orden_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cotizaciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(20) NOT NULL UNIQUE,
  cliente_id INT NULL,
  nombre VARCHAR(150) NULL,
  telefono VARCHAR(30) NULL,
  placa VARCHAR(20) NULL,
  tipo_vehiculo_id INT NULL,
  estado ENUM('borrador','enviada','aceptada','rechazada','convertida') NOT NULL DEFAULT 'borrador',
  vence DATE NULL,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  descuento DECIMAL(12,2) NOT NULL DEFAULT 0,
  impuesto DECIMAL(12,2) NOT NULL DEFAULT 0,
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  observaciones VARCHAR(500) NULL,
  orden_id INT NULL,
  usuario_id INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_estado (estado), INDEX idx_fecha (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cotizacion_detalle (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cotizacion_id INT NOT NULL,
  tipo ENUM('servicio','producto') NOT NULL,
  item_id INT NOT NULL,
  descripcion VARCHAR(180) NOT NULL,
  cantidad DECIMAL(10,2) NOT NULL DEFAULT 1,
  precio DECIMAL(12,2) NOT NULL DEFAULT 0,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS comprobante_series (
  tipo ENUM('boleta','factura','nota_venta') PRIMARY KEY,
  serie VARCHAR(6) NOT NULL,
  correlativo INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS comprobantes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  orden_id INT NOT NULL,
  tipo ENUM('boleta','factura','nota_venta') NOT NULL,
  serie VARCHAR(6) NOT NULL,
  numero INT NOT NULL,
  doc_tipo VARCHAR(10) NULL,
  doc_numero VARCHAR(20) NULL,
  razon_social VARCHAR(150) NULL,
  direccion VARCHAR(200) NULL,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  impuesto DECIMAL(12,2) NOT NULL DEFAULT 0,
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  anulado TINYINT(1) NOT NULL DEFAULT 0,
  usuario_id INT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_num (tipo, serie, numero), INDEX idx_orden (orden_id), INDEX idx_fecha (fecha),
  FOREIGN KEY (orden_id) REFERENCES ordenes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS compra_pagos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  compra_id INT NOT NULL,
  monto DECIMAL(12,2) NOT NULL,
  metodo ENUM('efectivo','tarjeta','yape_plin','transferencia') NOT NULL DEFAULT 'efectivo',
  referencia VARCHAR(80) NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  usuario_id INT NULL,
  INDEX idx_compra (compra_id),
  FOREIGN KEY (compra_id) REFERENCES compras(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS asistencia_config (
  id TINYINT PRIMARY KEY,
  hora_entrada TIME NOT NULL DEFAULT '08:00:00',
  hora_salida TIME NOT NULL DEFAULT '17:00:00',
  tolerancia INT NOT NULL DEFAULT 10,
  dias_laborales VARCHAR(20) NOT NULL DEFAULT '1,2,3,4,5,6',
  dias_mes INT NOT NULL DEFAULT 30,
  desc_tardanza TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS asistencias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  empleado_id INT NOT NULL,
  fecha DATE NOT NULL,
  entrada TIME NULL,
  salida TIME NULL,
  estado ENUM('presente','tardanza','falta','permiso','descanso') NOT NULL DEFAULT 'presente',
  min_tarde INT NOT NULL DEFAULT 0,
  usuario_id INT NULL,
  UNIQUE KEY uq_emp_fecha (empleado_id, fecha),
  INDEX idx_fecha (fecha),
  FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS planilla_adelantos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  empleado_id INT NOT NULL,
  fecha DATE NOT NULL,
  monto DECIMAL(12,2) NOT NULL,
  motivo VARCHAR(150) NULL,
  planilla_id INT NULL,
  usuario_id INT NULL,
  INDEX idx_emp (empleado_id),
  FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS planillas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  periodo CHAR(7) NOT NULL,
  empleado_id INT NOT NULL,
  sueldo DECIMAL(12,2) NOT NULL DEFAULT 0,
  faltas INT NOT NULL DEFAULT 0,
  min_tarde INT NOT NULL DEFAULT 0,
  desc_faltas DECIMAL(12,2) NOT NULL DEFAULT 0,
  desc_tardanza DECIMAL(12,2) NOT NULL DEFAULT 0,
  adelantos DECIMAL(12,2) NOT NULL DEFAULT 0,
  comisiones DECIMAL(12,2) NOT NULL DEFAULT 0,
  neto DECIMAL(12,2) NOT NULL DEFAULT 0,
  usuario_id INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_periodo_emp (periodo, empleado_id),
  FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
