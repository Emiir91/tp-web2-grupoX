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