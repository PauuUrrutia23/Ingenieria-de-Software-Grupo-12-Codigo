USE `ingecon`;
SET NAMES utf8mb4;

INSERT INTO visitantes (nombre, apellido, email, created_at, updated_at)
VALUES ('Camila', 'Rojas', 'camila.rojas@gmail.com', NOW(), NOW());

INSERT INTO consultas (mensaje, estado, acepta_terminos, fecha_cambio_estado, id_visitante, created_at, notificacion_admin_pendiente)
VALUES ('Necesito cotizar cerchas de techumbre para un galpón de 600 m2 en Buin.', 'pendiente', 1, NOW(),
        (SELECT id_visitante FROM visitantes WHERE email = 'camila.rojas@gmail.com'), NOW(), 1);

INSERT INTO visitantes (nombre, apellido, email, created_at, updated_at)
VALUES ('Jorge', 'Muñoz', 'jorge.munoz@outlook.com', NOW(), NOW());

INSERT INTO consultas (mensaje, estado, acepta_terminos, fecha_cambio_estado, id_visitante, created_at, notificacion_admin_pendiente)
VALUES ('Quisiera información sobre viviendas industrializadas para un proyecto DS19.', 'pendiente', 1, NOW(),
        (SELECT id_visitante FROM visitantes WHERE email = 'jorge.munoz@outlook.com'), NOW(), 1);

SELECT COUNT(*) AS consultas_pendientes_24h
FROM consultas c
JOIN visitantes v ON v.id_visitante = c.id_visitante
WHERE v.email = 'camila.rojas@gmail.com'
  AND c.estado = 'pendiente'
  AND c.created_at >= NOW() - INTERVAL 1 DAY;

SELECT c.id_consulta, v.nombre, v.apellido, v.email, c.estado, c.prioridad, c.created_at
FROM consultas c
JOIN visitantes v ON v.id_visitante = c.id_visitante
ORDER BY c.created_at DESC
LIMIT 15 OFFSET 0;

SELECT c.id_consulta, v.nombre, v.email, c.mensaje
FROM consultas c
JOIN visitantes v ON v.id_visitante = c.id_visitante
WHERE v.nombre LIKE '%cerchas%' OR v.email LIKE '%cerchas%' OR c.mensaje LIKE '%cerchas%';

UPDATE consultas
SET prioridad = 'alta',
    estado = 'en_proceso',
    fecha_cambio_estado = NOW(),
    id_admin_responsable = (SELECT id_admin FROM administradores WHERE correo = 'contacto@ingeconltda.cl')
WHERE id_consulta = (SELECT id FROM (SELECT MIN(id_consulta) AS id FROM consultas) t);

DELETE FROM consultas
WHERE id_consulta IN (SELECT id FROM (SELECT MAX(id_consulta) AS id FROM consultas) t);

DELETE FROM consultas
WHERE (estado = 'finalizada' AND fecha_cambio_estado < NOW() - INTERVAL 30 DAY)
   OR fecha_cambio_estado < NOW() - INTERVAL 6 MONTH;

INSERT INTO proyectos (nombre_obra, descripcion_tecnica, region, comuna, latitud, longitud, anio_ejecucion,
                       categoria, estado_publicacion, id_admin, created_at, updated_at)
VALUES ('Galpón Industrial Buin', 'Galpón de 600 m2 con cerchas de madera impregnada y placas conectoras galvanizadas.',
        'Metropolitana', 'Buin', -33.7320, -70.7430, 2026, 'industrial', 'borrador',
        (SELECT id_admin FROM administradores WHERE correo = 'contacto@ingeconltda.cl'), NOW(), NOW());

INSERT INTO imagenes_proyecto (id_proyecto, imagen, nombre_archivo, tipo_mime, created_at, updated_at)
VALUES ((SELECT id FROM (SELECT MAX(id_proyecto) AS id FROM proyectos) t),
        'proyectos/galpon-buin.jpg', 'galpon-buin.jpg', 'image/jpeg', NOW(), NOW());

UPDATE proyectos p
SET p.estado_publicacion = 'publicado', p.updated_at = NOW()
WHERE p.nombre_obra = 'Galpón Industrial Buin'
  AND EXISTS (SELECT 1 FROM imagenes_proyecto i WHERE i.id_proyecto = p.id_proyecto);

SELECT p.id_proyecto, p.nombre_obra, p.categoria, p.region, p.comuna, p.anio_ejecucion
FROM proyectos p
WHERE p.estado_publicacion = 'publicado'
  AND p.region = 'Metropolitana'
  AND p.categoria = 'industrial'
  AND (p.nombre_obra LIKE '%Buin%' OR p.comuna LIKE '%Buin%')
ORDER BY p.anio_ejecucion DESC;

SELECT id_proyecto, nombre_obra, latitud, longitud
FROM proyectos
WHERE estado_publicacion = 'publicado' AND latitud IS NOT NULL AND longitud IS NOT NULL;

SELECT * FROM certificados
WHERE fecha_vencimiento IS NULL OR fecha_vencimiento >= CURDATE()
ORDER BY nombre;

SELECT nombre, fecha_vencimiento
FROM certificados
WHERE fecha_vencimiento BETWEEN CURDATE() AND CURDATE() + INTERVAL 30 DAY;

INSERT INTO administradores (correo, password_hash, rol, activo, created_at, updated_at)
VALUES ('ventas@ingeconltda.cl', '$argon2id$v=19$m=65536,t=4,p=1$ejemplo$ejemploDeHashNoValido', 'admin', 1, NOW(), NOW());

UPDATE administradores SET perm_editar_contenido = 1, updated_at = NOW()
WHERE correo = 'ventas@ingeconltda.cl';

INSERT INTO historial_cambios (fecha_hora, id_admin, correo_responsable, accion, modulo, valores_anteriores, valores_nuevos, id_registro_afectado)
SELECT NOW(), j.id_admin, j.correo, 'asignacion_permiso', 'administradores',
       '{"perm_editar_contenido":0}', '{"perm_editar_contenido":1}', a.id_admin
FROM administradores j, administradores a
WHERE j.correo = 'contacto@ingeconltda.cl' AND a.correo = 'ventas@ingeconltda.cl';

SELECT fecha_hora, correo_responsable, accion, modulo, id_registro_afectado
FROM historial_cambios
ORDER BY fecha_hora DESC;

DELETE FROM administradores WHERE correo = 'ventas@ingeconltda.cl';
