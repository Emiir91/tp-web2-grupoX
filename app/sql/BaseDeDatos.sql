-- Primero creamos la tabla de roles porque vamos a depender de ella
CREATE TABLE roles (
                       id_rol INT AUTO_INCREMENT PRIMARY KEY,
                       nombre_rol VARCHAR(50) NOT NULL
);

-- Insertamos los 3 roles básicos que pide el enunciado para ya tenerlos listos
INSERT INTO roles (nombre_rol) VALUES ('Jugador'), ('Editor'), ('Administrador');

-- Ahora creamos la tabla principal de usuarios
CREATE TABLE usuarios (
                          id_usuario INT AUTO_INCREMENT PRIMARY KEY,
                          nombre_completo VARCHAR(150) NOT NULL,
                          anio_nacimiento INT NOT NULL,
                          sexo VARCHAR(50) NOT NULL,
                          pais VARCHAR(100) NOT NULL,
                          ciudad VARCHAR(100) NOT NULL,
                          mail VARCHAR(150) NOT NULL UNIQUE,
                          contrasenia VARCHAR(255) NOT NULL, -- VARCHAR(255) es ideal para guardar hashes de contraseñas
                          nombre_usuario VARCHAR(50) NOT NULL UNIQUE,
                          foto_perfil VARCHAR(255) DEFAULT 'public/uploads/default.png', -- Ruta por defecto
                          id_rol INT NOT NULL DEFAULT 1, -- Por defecto todos nacen siendo 'Jugador' (id 1)
                          cuenta_validada TINYINT(1) DEFAULT 0, -- 0 = No validada, 1 = Validada
                          token_validacion VARCHAR(100) NULL,
                          FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
);

CREATE TABLE pregunta (
    id_pregunta INT AUTO_INCREMENT PRIMARY KEY,
    texto_pregunta VARCHAR(500) NOT NULL,
    categoria VARCHAR(100) NOT NULL,
    activa TINYINT(1) DEFAULT 1
);

CREATE TABLE respuesta (
    id_respuesta INT AUTO_INCREMENT PRIMARY KEY,
    id_pregunta INT NOT NULL,
    texto_respuesta VARCHAR(300) NOT NULL,
    es_correcta TINYINT(1) NOT NULL,
    FOREIGN KEY (id_pregunta) REFERENCES pregunta(id_pregunta)
);

CREATE TABLE partida (
    id_partida INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    puntaje INT DEFAULT 0,
    racha INT DEFAULT 0,
    estado VARCHAR(20) DEFAULT 'en_curso',
    fecha_inicio DATETIME NOT NULL,
    ultima_pregunta_id INT NULL,
    tiempo_inicio_pregunta DATETIME NULL,
    fecha_fin DATETIME NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    FOREIGN KEY (ultima_pregunta_id) REFERENCES pregunta(id_pregunta)
);

ALTER TABLE partida ADD COLUMN preguntas_usadas TEXT NULL;