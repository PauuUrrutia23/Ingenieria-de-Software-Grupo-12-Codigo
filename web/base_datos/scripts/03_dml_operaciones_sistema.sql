-- =====================================================================
-- Plataforma Web Ingecon · Script DML 2: operaciones del sistema
--
-- Ejemplos de las operaciones INSERT, UPDATE, DELETE y SELECT que realiza
-- la plataforma, ordenados por requerimiento. Se ejecutan sobre la base
-- creada con 01_ddl y cargada con 02_dml. En la aplicación, estas
-- operaciones pasan por DBRouterController.
-- =====================================================================

USE `ingecon`;
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- RF01 · Registro de una consulta desde el Formulario de Contacto
-- ---------------------------------------------------------------------
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

-- Límite de cinco consultas pendientes en 24 horas por correo (CU 1.1, excepción 3)
SELECT COUNT(*) AS consultas_pendientes_24h
FROM consultas c
JOIN visitantes v ON v.id_visitante = c.id_visitante
WHERE v.email = 'camila.rojas@gmail.com'
  AND c.estado = 'pendiente'
  AND c.created_at >= NOW() - INTERVAL 1 DAY;

-- ---------------------------------------------------------------------
-- RF36 a RF41 · Módulo comercial: listar, buscar y atender consultas
-- ---------------------------------------------------------------------
-- Listado paginado, de la más reciente a la más antigua (RF36, RF37)
SELECT c.id_consulta, v.nombre, v.apellido, v.email, c.estado, c.prioridad, c.created_at
FROM consultas c
JOIN visitantes v ON v.id_visitante = c.id_visitante
ORDER BY c.created_at DESC
LIMIT 15 OFFSET 0;

-- Búsqueda por texto libre (RF38)
SELECT c.id_consulta, v.nombre, v.email, c.mensaje
FROM consultas c
JOIN visitantes v ON v.id_visitante = c.id_visitante
WHERE v.nombre LIKE '%cerchas%' OR v.email LIKE '%cerchas%' OR c.mensaje LIKE '%cerchas%';

-- Asignar prioridad, cambiar estado y registrar responsable (RF40, RF41)
UPDATE consultas
SET prioridad = 'alta',
    estado = 'en_proceso',
    fecha_cambio_estado = NOW(),
    id_admin_responsable = (SELECT id_admin FROM administradores WHERE correo = 'contacto@ingeconltda.cl')
WHERE id_consulta = (SELECT id FROM (SELECT MIN(id_consulta) AS id FROM consultas) t);

-- ---------------------------------------------------------------------
-- RF57 y RF58 · Eliminar consultas seleccionadas
-- ---------------------------------------------------------------------
DELETE FROM consultas
WHERE id_consulta IN (SELECT id FROM (SELECT MAX(id_consulta) AS id FROM consultas) t);

-- RNF24 · Depuración automática: consultas finalizadas hace 30 días
-- o sin cambios de estado en 6 meses
DELETE FROM consultas
WHERE (estado = 'finalizada' AND fecha_cambio_estado < NOW() - INTERVAL 30 DAY)
   OR fecha_cambio_estado < NOW() - INTERVAL 6 MONTH;

-- ---------------------------------------------------------------------
-- RF48 a RF51 · Gestión de proyectos
-- ---------------------------------------------------------------------
-- Registrar un proyecto: siempre nace en estado borrador (CU 48.1)
INSERT INTO proyectos (nombre_obra, descripcion_tecnica, region, comuna, latitud, longitud, anio_ejecucion,
                       categoria, estado_publicacion, id_admin, created_at, updated_at)
VALUES ('Galpón Industrial Buin', 'Galpón de 600 m2 con cerchas de madera impregnada y placas conectoras galvanizadas.',
        'Metropolitana', 'Buin', -33.7320, -70.7430, 2026, 'industrial', 'borrador',
        (SELECT id_admin FROM administradores WHERE correo = 'contacto@ingeconltda.cl'), NOW(), NOW());

INSERT INTO imagenes_proyecto (id_proyecto, imagen, nombre_archivo, tipo_mime, created_at, updated_at)
VALUES ((SELECT id FROM (SELECT MAX(id_proyecto) AS id FROM proyectos) t),
        'proyectos/galpon-buin.jpg', 'galpon-buin.jpg', 'image/jpeg', NOW(), NOW());

-- Publicar el proyecto, solo si tiene al menos una fotografía (CU 48.2)
UPDATE proyectos p
SET p.estado_publicacion = 'publicado', p.updated_at = NOW()
WHERE p.nombre_obra = 'Galpón Industrial Buin'
  AND EXISTS (SELECT 1 FROM imagenes_proyecto i WHERE i.id_proyecto = p.id_proyecto);

-- ---------------------------------------------------------------------
-- RF18 a RF22 · Galería pública con filtros combinados
-- ---------------------------------------------------------------------
SELECT p.id_proyecto, p.nombre_obra, p.categoria, p.region, p.comuna, p.anio_ejecucion
FROM proyectos p
WHERE p.estado_publicacion = 'publicado'
  AND p.region = 'Metropolitana'
  AND p.categoria = 'industrial'
  AND (p.nombre_obra LIKE '%Buin%' OR p.comuna LIKE '%Buin%')
ORDER BY p.anio_ejecucion DESC;

-- Marcadores del mapa interactivo (RF22)
SELECT id_proyecto, nombre_obra, latitud, longitud
FROM proyectos
WHERE estado_publicacion = 'publicado' AND latitud IS NOT NULL AND longitud IS NOT NULL;

-- ---------------------------------------------------------------------
-- RF24 y RNF20 · Certificaciones vigentes y por vencer
-- ---------------------------------------------------------------------
SELECT * FROM certificados
WHERE fecha_vencimiento IS NULL OR fecha_vencimiento >= CURDATE()
ORDER BY nombre;

SELECT nombre, fecha_vencimiento
FROM certificados
WHERE fecha_vencimiento BETWEEN CURDATE() AND CURDATE() + INTERVAL 30 DAY;

-- ---------------------------------------------------------------------
-- RF52, RF53 y RF59 · Cuentas del personal y permisos
-- ---------------------------------------------------------------------
-- Crear una cuenta (la contraseña se guarda solo como hash Argon2id; este
-- hash es un ejemplo y no corresponde a una contraseña real)
INSERT INTO administradores (correo, password_hash, rol, activo, created_at, updated_at)
VALUES ('ventas@ingeconltda.cl', '$argon2id$v=19$m=65536,t=4,p=1$ejemplo$ejemploDeHashNoValido', 'admin', 1, NOW(), NOW());

-- Asignar el permiso Editar Contenido (RF59)
UPDATE administradores SET perm_editar_contenido = 1, updated_at = NOW()
WHERE correo = 'ventas@ingeconltda.cl';

-- Registro en el Historial de Cambios (RF15)
INSERT INTO historial_cambios (fecha_hora, id_admin, correo_responsable, accion, modulo, valores_anteriores, valores_nuevos, id_registro_afectado)
SELECT NOW(), j.id_admin, j.correo, 'asignacion_permiso', 'administradores',
       '{"perm_editar_contenido":0}', '{"perm_editar_contenido":1}', a.id_admin
FROM administradores j, administradores a
WHERE j.correo = 'contacto@ingeconltda.cl' AND a.correo = 'ventas@ingeconltda.cl';

-- Consultar el Historial de Cambios (RF15)
SELECT fecha_hora, correo_responsable, accion, modulo, id_registro_afectado
FROM historial_cambios
ORDER BY fecha_hora DESC;

-- Eliminar la cuenta (RF53)
DELETE FROM administradores WHERE correo = 'ventas@ingeconltda.cl';
