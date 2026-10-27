INSERT INTO servicios (categoria_id,nombre,descripcion,precio,duracion_min,comision_pct) VALUES
 (1,'Lavado Express','Lavado exterior y secado',15.00,20,10),
 (1,'Lavado Completo','Exterior, interior y aspirado',30.00,45,10),
 (1,'Lavado de Motor','Desengrase y lavado de motor',25.00,30,10),
 (2,'Encerado','Cera protectora y brillo',45.00,40,12),
 (2,'Pulido de Faros','Restauración de faros opacos',40.00,35,12),
 (2,'Lavado de Tapicería','Limpieza profunda de asientos',80.00,90,12),
 (3,'Tratamiento Cerámico','Protección cerámica de pintura',250.00,180,8);
INSERT INTO servicio_precios (servicio_id,tipo_vehiculo_id,precio) VALUES
 (1,1,15),(1,2,20),(1,3,22),(1,4,10),(1,5,25),
 (2,1,30),(2,2,40),(2,3,45),(2,4,20),(2,5,50);
INSERT INTO empleados (nombre,documento,telefono,cargo,sueldo,comision_pct,fecha_ingreso) VALUES
 ('Carlos Ramírez','45123456','987654321','Lavador',1200,10,CURDATE()),
 ('Luis Torres','46234567','976543210','Lavador',1200,10,CURDATE()),
 ('Miguel Flores','47345678','965432109','Detallador',1500,12,CURDATE());
INSERT INTO clientes (tipo_doc,nro_doc,nombre,telefono,email) VALUES
 ('DNI','12345678','Cliente Ejemplo','999888777','cliente@correo.com');
INSERT INTO vehiculos (cliente_id,placa,marca,modelo,color,anio,tipo_vehiculo_id) VALUES
 (1,'ABC-123','Toyota','Corolla','Blanco',2020,1);
INSERT INTO proveedores (nombre,ruc,contacto,telefono) VALUES ('Distribuidora AutoQuímica','20123456789','Ventas','014567890');
INSERT INTO productos (codigo,nombre,proveedor_id,unidad,precio_compra,precio_venta,stock,stock_minimo,se_vende) VALUES
 ('SH-001','Shampoo automotriz (galón)',1,'GL',35,0,10,3,0),
 ('CE-001','Cera líquida 500ml',1,'UND',18,35,12,4,1),
 ('AM-001','Ambientador de auto',1,'UND',3,8,40,10,1);
