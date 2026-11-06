
SET foreign_key_checks = 0;

DROP TABLE IF EXISTS partida;
DROP TABLE IF EXISTS respuesta;
DROP TABLE IF EXISTS pregunta;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS roles;

SET foreign_key_checks = 1;

-- Tabla de roles
CREATE TABLE roles (
                       id_rol INT AUTO_INCREMENT PRIMARY KEY,
                       nombre_rol VARCHAR(50) NOT NULL
);

-- Tabla de usuarios
CREATE TABLE usuarios (
                          id_usuario INT AUTO_INCREMENT PRIMARY KEY,
                          nombre_completo VARCHAR(150) NOT NULL,
                          anio_nacimiento INT NOT NULL,
                          sexo VARCHAR(50) NOT NULL,
                          pais VARCHAR(100) NOT NULL,
                          ciudad VARCHAR(100) NOT NULL,
                          mail VARCHAR(150) NOT NULL UNIQUE,
                          contrasenia VARCHAR(255) NOT NULL,
                          nombre_usuario VARCHAR(50) NOT NULL UNIQUE,
                          foto_perfil VARCHAR(255) DEFAULT 'public/uploads/default.png',
                          id_rol INT NOT NULL DEFAULT 1,
                          cuenta_validada TINYINT(1) DEFAULT 0,
                          token_validacion VARCHAR(100) NULL,
                          FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
);

-- Tabla de preguntas (con la columna dificultad integrada)
CREATE TABLE pregunta (
                          id_pregunta INT AUTO_INCREMENT PRIMARY KEY,
                          texto_pregunta VARCHAR(500) NOT NULL,
                          categoria VARCHAR(100) NOT NULL,
                          dificultad ENUM('facil', 'intermedio', 'dificil') DEFAULT 'facil',
                          activa TINYINT(1) DEFAULT 1
);

-- Tabla de respuestas
CREATE TABLE respuesta (
                           id_respuesta INT AUTO_INCREMENT PRIMARY KEY,
                           id_pregunta INT NOT NULL,
                           texto_respuesta VARCHAR(300) NOT NULL,
                           es_correcta TINYINT(1) NOT NULL,
                           FOREIGN KEY (id_pregunta) REFERENCES pregunta(id_pregunta) ON DELETE CASCADE
);

-- Tabla de partidas (con soporte para modo de juego y niveles)
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
                         preguntas_usadas TEXT NULL,
                         modo_juego VARCHAR(20) DEFAULT 'progresivo',
                         nivel_actual VARCHAR(20) DEFAULT 'facil',
                         FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
                         FOREIGN KEY (ultima_pregunta_id) REFERENCES pregunta(id_pregunta)
);


INSERT INTO roles (nombre_rol) VALUES ('Jugador'), ('Editor'), ('Administrador');

-- Usuario: test / Contraseña: 123456
INSERT INTO usuarios (nombre_completo, anio_nacimiento, sexo, pais, ciudad, mail, contrasenia, nombre_usuario, foto_perfil, id_rol, cuenta_validada, token_validacion)
VALUES ('Usuario de Prueba', 2000, 'Prefiero no cargarlo', 'Argentina', 'Buenos Aires', 'test@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'test', 'public/uploads/default.png', 1, 1, NULL);


-- NIVEL FÁCIL (IDs 1 al 5)
INSERT INTO pregunta (texto_pregunta, categoria, dificultad, activa) VALUES
                                                                         ('¿Cuál es la capital de Francia?', 'Geografía', 'facil', 1),
                                                                         ('¿Cuánto es 2 + 2?', 'Matemáticas', 'facil', 1),
                                                                         ('¿De qué color es el cielo en un día despejado?', 'Ciencias', 'facil', 1),
                                                                         ('¿Cuántos días tiene una semana?', 'Cultura General', 'facil', 1),
                                                                         ('¿Cuál es el animal conocido como el mejor amigo del hombre?', 'Animales', 'facil', 1);

INSERT INTO respuesta (id_pregunta, texto_respuesta, es_correcta) VALUES
                                                                      (1, 'París', 1), (1, 'Londres', 0), (1, 'Berlín', 0), (1, 'Madrid', 0),
                                                                      (2, '4', 1), (2, '5', 0), (2, '3', 0), (2, '6', 0),
                                                                      (3, 'Azul', 1), (3, 'Verde', 0), (3, 'Rojo', 0), (3, 'Amarillo', 0),
                                                                      (4, '7', 1), (4, '5', 0), (4, '6', 0), (4, '8', 0),
                                                                      (5, 'Perro', 1), (5, 'Gato', 0), (5, 'Caballo', 0), (5, 'Conejo', 0);


-- NIVEL INTERMEDIO (IDs 6 al 10)
INSERT INTO pregunta (texto_pregunta, categoria, dificultad, activa) VALUES
                                                                         ('¿Quién escribió Don Quijote de la Mancha?', 'Literatura', 'intermedio', 1),
                                                                         ('¿En qué año llegó el hombre a la luna?', 'Historia', 'intermedio', 1),
                                                                         ('¿Cuál es el planeta más grande del sistema solar?', 'Ciencia', 'intermedio', 1),
                                                                         ('¿Cuál es el país más grande del mundo por superficie?', 'Geografía', 'intermedio', 1),
                                                                         ('¿En qué continente se encuentra Egipto?', 'Geografía', 'intermedio', 1);

INSERT INTO respuesta (id_pregunta, texto_respuesta, es_correcta) VALUES
                                                                      (6, 'Miguel de Cervantes', 1), (6, 'William Shakespeare', 0), (6, 'Gabriel García Márquez', 0), (6, 'Jorge Luis Borges', 0),
                                                                      (7, '1969', 1), (7, '1965', 0), (7, '1972', 0), (7, '1959', 0),
                                                                      (8, 'Júpiter', 1), (8, 'Saturno', 0), (8, 'Marte', 0), (8, 'Tierra', 0),
                                                                      (9, 'Rusia', 1), (9, 'Canadá', 0), (9, 'China', 0), (9, 'Estados Unidos', 0),
                                                                      (10, 'África', 1), (10, 'Asia', 0), (10, 'América', 0), (10, 'Europa', 0);


-- NIVEL DIFÍCIL (IDs 11 al 15)
INSERT INTO pregunta (texto_pregunta, categoria, dificultad, activa) VALUES
                                                                         ('¿Cuál es el elemento químico con símbolo H?', 'Ciencias', 'dificil', 1),
                                                                         ('¿En qué año comenzó la Revolución Francesa?', 'Historia', 'dificil', 1),
                                                                         ('¿Quién pintó La última cena?', 'Arte', 'dificil', 1),
                                                                         ('¿Cuál es la velocidad de la luz en el vacío (aprox. km/s)?', 'Física', 'dificil', 1),
                                                                         ('¿En qué año se fundó la Organización de las Naciones Unidas (ONU)?', 'Historia', 'dificil', 1);

INSERT INTO respuesta (id_pregunta, texto_respuesta, es_correcta) VALUES
                                                                      (11, 'Hidrógeno', 1), (11, 'Helio', 0), (11, 'Oxígeno', 0), (11, 'Carbono', 0),
                                                                      (12, '1789', 1), (12, '1492', 0), (12, '1810', 0), (12, '1914', 0),
                                                                      (13, 'Leonardo da Vinci', 1), (13, 'Miguel Ángel', 0), (13, 'Vincent van Gogh', 0), (13, 'Pablo Picasso', 0),
                                                                      (14, '300,000', 1), (14, '150,000', 0), (14, '1,000,000', 0), (14, '3,000', 0),
                                                                      (15, '1945', 1), (15, '1918', 0), (15, '1939', 0), (15, '1950', 0);