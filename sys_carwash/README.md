# CarWash Pro (PHP MVC + Bootstrap 5 + MySQL)

## Instalación (Laragon)
1. Copiar la carpeta a `C:\laragon\www\sys_carwash`
2. Iniciar Apache y MySQL en Laragon.
3. Abrir `http://localhost/sys_carwash/install.php` (crea la BD `sys_carwash`, tablas y el usuario admin).
4. Ingresar en `http://localhost/sys_carwash/` → usuario **admin** / contraseña **admin123** (cámbiala en Usuarios).

Conexión: `app/config/config.php` (localhost / root / sin clave / sys_carwash / 3306).

## Módulos
Dashboard · Nueva orden (POS) · Órdenes · Citas · Caja · Clientes · Vehículos · Servicios (precio por tipo de vehículo) ·
Categorías · Tipos de vehículo · Productos/Insumos · Proveedores · Empleados y comisiones · Gastos · Reportes (CSV/imprimir) ·
Usuarios y roles · Configuración de empresa (logo, moneda, separadores, impuesto) · Respaldo / Restauración / Reset · Bitácora.

## CRM avanzado (v1.2)
Menú **CRM** (permiso `clientes`): Panel, Segmentos (VIP/Frecuente/Nuevo/Ocasional/En riesgo/Inactivo/Sin visitas, calculados automáticamente), Seguimientos (tareas y llamadas con recordatorio), Oportunidades (pipeline kanban con arrastrar y soltar), Fidelización (puntos por compra, canje por cupón), Cupones, Campañas (WhatsApp/correo segmentadas) y Encuestas de satisfacción (enlace público por orden). Ajustes en CRM → Ajustes (solo administrador).
Tras copiar los archivos, abre una vez `update.php` (crea las tablas `crm_*` y agrega `fecha_nacimiento` / `acepta_marketing` a clientes). En **Nueva orden** hay un campo de cupón.

**Datos de ejemplo:** Respaldo y restauración → *Datos de ejemplo* (`/demo`) carga órdenes, clientes, gastos, compras, citas, cierres de caja y datos de CRM para poblar el dashboard; se pueden quitar (marcados [DEMO]).

## Pagos parciales, cuentas por cobrar y tablero (v1.3)
- **Pagos:** cada orden admite varios pagos (adelantos, métodos mixtos, propina y referencia). La orden pasa a *Pagado* cuando el saldo llega a 0. Tabla `orden_pagos` (se completa sola con `update.php` para las órdenes ya cobradas).
- **Cuentas por cobrar** (`/cobranzas`): deuda por cliente con antigüedad, estado de cuenta, recordatorio por WhatsApp y abonos que se aplican a las órdenes más antiguas.
- **Tablero en vivo** (`/tablero`): órdenes por estado con tiempo transcurrido vs. duración del servicio, alerta de retraso, asignación de lavador y arrastrar para cambiar de estado.
- Caja, Dashboard y Reportes (métodos de pago) ahora se calculan con los pagos reales.

## Venta rápida y recepción de vehículos (v1.4)
- **Venta rápida (POS)** (`/ventas`): vende productos de mostrador sin vehículo. Búsqueda o código de barras (Enter), cliente opcional, descuento, método de pago, recibido y vuelto. Se guarda como orden de tipo *venta* (ya entregada y pagada), por lo que descuenta stock y entra en Caja, Dashboard y Reportes. Se anula desde su orden (devuelve stock). No aparece en el Tablero.
- **Recepción de vehículos** (`/recepciones`): checklist de objetos, daños previos por zona, combustible, kilometraje, hasta 8 fotos (solo imágenes válidas) y firma del cliente en pantalla. Acta imprimible y botón *Generar orden de servicio* que precarga cliente y vehículo y enlaza la recepción con la orden.
- Para actualizar una instalación existente abre una vez `update.php` (agrega `ordenes.tipo` y la tabla `recepciones`).

## Cotizaciones y comprobantes (v1.5)
- **Cotizaciones** (`/cotizaciones`): presupuesto con cliente (o datos libres), placa, tipo de vehículo (precios por tipo), descuento, impuesto y validez en días. Se imprime/PDF, se envía por WhatsApp, se marca enviada/aceptada/rechazada y se convierte en orden de servicio con un clic (precarga cliente e ítems; la cotización queda *convertida* y enlazada).
- **Comprobantes** (`/comprobantes`): boleta (B001), factura (F001) y nota de venta (NV01) con correlativo atómico, emitidos desde el detalle de cada orden. Factura exige RUC de 11 dígitos; boleta desde S/ 700 exige documento. Un comprobante activo por orden; al anularlo el número no se reutiliza. **Son documentos internos: no reemplazan la facturación electrónica SUNAT** (requiere un OSE/PSE autorizado).
- Para actualizar una instalación existente abre una vez `update.php`.

## Cuentas por pagar y exportación a Excel (v1.6)
- **Compras a crédito**: al registrar una compra elige *Crédito* (requiere proveedor y días de crédito). No genera gasto hasta que se paga.
- **Cuentas por pagar** (`/porpagar`): deuda por proveedor, vencidos y próximos 7 días, estado de cuenta y abonos que se aplican primero a las compras que vencen antes. Cada pago se registra como gasto (categoría *Insumos*). También se puede pagar una compra individual desde su detalle.
- **Excel (.xlsx)** desde Reportes → *Excel*: órdenes, ventas por día, servicios, cuentas por cobrar, cuentas por pagar, inventario, clientes y gastos. Se genera sin librerías (requiere la extensión `zip` de PHP, activa en Laragon; si falta, descarga CSV).
- Para actualizar una instalación existente abre una vez `update.php`.

## Asistencia y planilla (v1.7)
- **Asistencia** (`/asistencia`): marcación de entrada/salida por empleado (hora actual o manual), falta, permiso y descanso; navegación por fecha para corregir días anteriores; *Cerrar día* marca como falta a quien no tenga registro (solo días laborales). Tardanza = entrada pasada la tolerancia, midiendo los minutos desde la hora oficial. Resumen mensual en `/asistencia/mes`; horario, tolerancia y días laborales en `/asistencia/config`.
- **Planilla** (`/planilla`): por mes y empleado calcula sueldo − faltas (sueldo ÷ días del mes) − tardanzas (por minuto, jornada de 8 h; opcional) − adelantos. *Pagar* cierra al empleado, carga el neto a Gastos (Sueldos) y genera su boleta imprimible; *Pagar todos* cierra el resto. Los adelantos salen como gasto al entregarse y se descuentan al cerrar (si exceden el neto, pasan al mes siguiente). Las comisiones se siguen liquidando en su módulo y solo se muestran como informativo.
- Para actualizar una instalación existente abre una vez `update.php`.
