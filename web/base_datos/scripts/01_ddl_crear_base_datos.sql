CREATE DATABASE IF NOT EXISTS `ingecon` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ingecon`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `administradores`;
CREATE TABLE `administradores` (
  `id_admin` bigint unsigned NOT NULL AUTO_INCREMENT,
  `correo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `intentos_fallidos` smallint NOT NULL DEFAULT '0',
  `bloqueado_hasta` datetime DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `perm_editar_contenido` tinyint(1) NOT NULL DEFAULT '0',
  `perm_exportar_consultas` tinyint(1) NOT NULL DEFAULT '0',
  `perm_eliminar_consultas` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_admin`),
  UNIQUE KEY `administradores_correo_unique` (`correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `certificados`;
CREATE TABLE `certificados` (
  `id_certificado` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_mime` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `archivo_pdf` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_emision` date NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `organismo` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url_organismo` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_admin` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_certificado`),
  UNIQUE KEY `certificados_codigo_unique` (`codigo`),
  KEY `certificados_id_admin_foreign` (`id_admin`),
  CONSTRAINT `certificados_id_admin_foreign` FOREIGN KEY (`id_admin`) REFERENCES `administradores` (`id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `colaboradores`;
CREATE TABLE `colaboradores` (
  `id_colaborador` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_comercial` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `logotipo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_mime` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sitio_web` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_admin` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_colaborador`),
  KEY `colaboradores_id_admin_foreign` (`id_admin`),
  CONSTRAINT `colaboradores_id_admin_foreign` FOREIGN KEY (`id_admin`) REFERENCES `administradores` (`id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `componentes_producto`;
CREATE TABLE `componentes_producto` (
  `id_componente` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_producto` bigint unsigned NOT NULL,
  `orden` smallint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_componente`),
  KEY `componentes_producto_id_producto_foreign` (`id_producto`),
  CONSTRAINT `componentes_producto_id_producto_foreign` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `consultas`;
CREATE TABLE `consultas` (
  `id_consulta` bigint unsigned NOT NULL AUTO_INCREMENT,
  `mensaje` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `prioridad` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acepta_terminos` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_cambio_estado` datetime NOT NULL,
  `id_visitante` bigint unsigned NOT NULL,
  `id_admin_responsable` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `notificacion_admin_pendiente` tinyint(1) NOT NULL DEFAULT '0',
  `notificacion_admin_ultimo_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_consulta`),
  KEY `consultas_id_visitante_foreign` (`id_visitante`),
  KEY `consultas_id_admin_responsable_foreign` (`id_admin_responsable`),
  CONSTRAINT `consultas_id_admin_responsable_foreign` FOREIGN KEY (`id_admin_responsable`) REFERENCES `administradores` (`id_admin`) ON DELETE SET NULL,
  CONSTRAINT `consultas_id_visitante_foreign` FOREIGN KEY (`id_visitante`) REFERENCES `visitantes` (`id_visitante`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `contenidos`;
CREATE TABLE `contenidos` (
  `id_contenido` bigint unsigned NOT NULL AUTO_INCREMENT,
  `seccion` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cuerpo` text COLLATE utf8mb4_unicode_ci,
  `archivo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_mime` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enlace` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `orden` smallint NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `id_admin` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_contenido`),
  KEY `contenidos_id_admin_foreign` (`id_admin`),
  CONSTRAINT `contenidos_id_admin_foreign` FOREIGN KEY (`id_admin`) REFERENCES `administradores` (`id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `historial_cambios`;
CREATE TABLE `historial_cambios` (
  `id_historial` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fecha_hora` datetime NOT NULL,
  `id_admin` bigint unsigned DEFAULT NULL,
  `correo_responsable` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accion` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modulo` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valores_anteriores` text COLLATE utf8mb4_unicode_ci,
  `valores_nuevos` text COLLATE utf8mb4_unicode_ci,
  `id_registro_afectado` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id_historial`),
  KEY `historial_cambios_id_admin_foreign` (`id_admin`),
  CONSTRAINT `historial_cambios_id_admin_foreign` FOREIGN KEY (`id_admin`) REFERENCES `administradores` (`id_admin`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `imagenes_proyecto`;
CREATE TABLE `imagenes_proyecto` (
  `id_imagen` bigint unsigned NOT NULL AUTO_INCREMENT,
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_archivo` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_mime` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_proyecto` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_imagen`),
  KEY `imagenes_proyecto_id_proyecto_foreign` (`id_proyecto`),
  CONSTRAINT `imagenes_proyecto_id_proyecto_foreign` FOREIGN KEY (`id_proyecto`) REFERENCES `proyectos` (`id_proyecto`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `productos`;
CREATE TABLE `productos` (
  `id_producto` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_mime` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `orden` smallint NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `id_admin` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_producto`),
  KEY `productos_id_admin_foreign` (`id_admin`),
  CONSTRAINT `productos_id_admin_foreign` FOREIGN KEY (`id_admin`) REFERENCES `administradores` (`id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `proyectos`;
CREATE TABLE `proyectos` (
  `id_proyecto` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_obra` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion_tecnica` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `region` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comuna` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitud` decimal(9,6) DEFAULT NULL,
  `longitud` decimal(9,6) DEFAULT NULL,
  `anio_ejecucion` smallint NOT NULL,
  `estado_publicacion` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `categoria` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_admin` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_proyecto`),
  KEY `proyectos_id_admin_foreign` (`id_admin`),
  CONSTRAINT `proyectos_id_admin_foreign` FOREIGN KEY (`id_admin`) REFERENCES `administradores` (`id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `recuperaciones_password`;
CREATE TABLE `recuperaciones_password` (
  `id_recuperacion` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_admin` bigint unsigned NOT NULL,
  `token_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expira_en` datetime NOT NULL,
  `usado_en` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id_recuperacion`),
  KEY `recuperaciones_password_id_admin_foreign` (`id_admin`),
  CONSTRAINT `recuperaciones_password_id_admin_foreign` FOREIGN KEY (`id_admin`) REFERENCES `administradores` (`id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `sesiones`;
CREATE TABLE `sesiones` (
  `id_sesion` bigint unsigned NOT NULL AUTO_INCREMENT,
  `token_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_inicio` datetime NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_admin` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_sesion`),
  KEY `sesiones_id_admin_foreign` (`id_admin`),
  CONSTRAINT `sesiones_id_admin_foreign` FOREIGN KEY (`id_admin`) REFERENCES `administradores` (`id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `visitantes`;
CREATE TABLE `visitantes` (
  `id_visitante` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_visitante`),
  UNIQUE KEY `visitantes_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
