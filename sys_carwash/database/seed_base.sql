-- Datos mínimos (también usados al resetear el sistema para una empresa nueva)
INSERT INTO empresa_config (id,nombre) VALUES (1,'Mi Carwash') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO roles (id,nombre,permisos) VALUES
 (1,'Administrador','*'),
 (2,'Cajero','dashboard,ordenes,citas,caja,clientes,vehiculos'),
 (3,'Supervisor','dashboard,ordenes,citas,caja,clientes,vehiculos,servicios,productos,proveedores,empleados,gastos,reportes')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);
INSERT INTO tipos_vehiculo (id,nombre) VALUES (1,'Auto'),(2,'SUV / Camioneta'),(3,'Pickup'),(4,'Moto'),(5,'Furgoneta / Van')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);
INSERT INTO categorias_servicio (id,nombre) VALUES (1,'Lavado'),(2,'Detallado'),(3,'Mantenimiento')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);

INSERT INTO crm_config (id,puntos_desde,msg_cumple,msg_riesgo,msg_encuesta) VALUES (1,CURDATE(),
 'Hola {nombre} 🎂 ¡Feliz cumpleaños de parte de {empresa}! Te regalamos un descuento especial: usa el cupón {cupon} en tu próximo lavado.',
 'Hola {nombre}, te extrañamos en {empresa} 🚗✨ Hace tiempo que no vienes. Tu vehículo merece un buen lavado: tienes {puntos} puntos acumulados. ¡Te esperamos!',
 'Hola {nombre}, gracias por visitar {empresa}. ¿Nos regalas 1 minuto para calificar tu servicio? {enlace}')
ON DUPLICATE KEY UPDATE id=id;
