CREATE TABLE visitante (
    id_visitante BIGSERIAL,
    nombre VARCHAR(80) NOT NULL,
    apellido VARCHAR(80) NOT NULL,
    email VARCHAR(150) NOT NULL,
    PRIMARY KEY (id_visitante)
);

CREATE TABLE administrador (
    id_admin BIGSERIAL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    intentos_fallidos SMALLINT DEFAULT 0,
    bloqueado_hasta TIMESTAMP NULL,
    activo BOOLEAN DEFAULT TRUE,
    PRIMARY KEY (id_admin)
);

CREATE TABLE consulta (
    id_consulta BIGSERIAL,
    mensaje TEXT NOT NULL,
    fecha_consulta TIMESTAMP NOT NULL,
    estado VARCHAR(20) NOT NULL,
    prioridad VARCHAR(10) NOT NULL,
    id_visitante BIGINT NOT NULL,
    id_admin_responsable BIGINT NULL,
    PRIMARY KEY (id_consulta),
    FOREIGN KEY (id_visitante)
        REFERENCES visitante(id_visitante)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    FOREIGN KEY (id_admin_responsable)
        REFERENCES administrador(id_admin)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CHECK (estado IN ('Pendiente', 'En Proceso', 'Finalizada')),
    CHECK (prioridad IN ('Baja', 'Media', 'Alta'))
);

CREATE TABLE archivo_adjunto (
    id_adjunto BIGSERIAL,
    archivo_pdf BYTEA NOT NULL,
    nombre_archivo VARCHAR(180) NOT NULL,
    tipo_mime VARCHAR(80) NOT NULL,
    id_consulta BIGINT NOT NULL UNIQUE,
    PRIMARY KEY (id_adjunto),
    FOREIGN KEY (id_consulta)
        REFERENCES consulta(id_consulta)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE TABLE sesion (
    id_sesion BIGSERIAL,
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    fecha_inicio TIMESTAMP NOT NULL,
    estado VARCHAR(20) NOT NULL,
    id_admin BIGINT NOT NULL,
    PRIMARY KEY (id_sesion),
    FOREIGN KEY (id_admin)
        REFERENCES administrador(id_admin)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CHECK (estado IN ('Activa', 'Cerrada', 'Expirada'))
);

CREATE TABLE proyecto (
    id_proyecto BIGSERIAL,
    nombre_obra VARCHAR(150) NOT NULL,
    descripcion_tecnica TEXT NOT NULL,
    region VARCHAR(80) NOT NULL,
    ubicacion_geografica VARCHAR(150) NOT NULL,
    anio_ejecucion SMALLINT NOT NULL,
    estado_publicacion VARCHAR(20) NOT NULL CHECK (
        estado_publicacion IN ('borrador', 'publicado')
    ),
    categoria VARCHAR(50) NOT NULL CHECK (
        categoria IN ('Habitacional', 'Industrial', 'Agricola')
    ),
    id_admin BIGINT NOT NULL,

    PRIMARY KEY (id_proyecto),

    FOREIGN KEY (id_admin)
        REFERENCES administrador(id_admin)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE imagen_proyecto (
    id_imagen BIGSERIAL,
    imagen BYTEA NOT NULL,
    nombre_archivo VARCHAR(180) NOT NULL,
    tipo_mime VARCHAR(80) NOT NULL,
    id_proyecto BIGINT NOT NULL,
    PRIMARY KEY (id_imagen),
    FOREIGN KEY (id_proyecto)
        REFERENCES proyecto(id_proyecto)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE TABLE certificado (
    id_certificado BIGSERIAL,
    codigo_lote VARCHAR(80) NOT NULL UNIQUE,
    archivo_pdf BYTEA NOT NULL,
    fecha_emision DATE NOT NULL,
    estado VARCHAR(20) NOT NULL,
    id_proyecto BIGINT NOT NULL,
    PRIMARY KEY (id_certificado),
    FOREIGN KEY (id_proyecto)
        REFERENCES proyecto(id_proyecto)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CHECK (estado IN ('Vigente', 'Vencido', 'Reemplazado'))
);

CREATE TABLE colaborador (
    id_colaborador BIGSERIAL,
    nombre_comercial VARCHAR(120) NOT NULL,
    logotipo BYTEA NOT NULL,
    id_admin BIGINT NOT NULL,
    PRIMARY KEY (id_colaborador),
    FOREIGN KEY (id_admin)
        REFERENCES administrador(id_admin)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);