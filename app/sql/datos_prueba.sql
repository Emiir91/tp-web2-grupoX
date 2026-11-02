-- Usuario de prueba para probar el juego
-- Usuario: test / Contraseña: 123456
INSERT INTO usuarios (nombre_completo, anio_nacimiento, sexo, pais, ciudad, mail, contrasenia, nombre_usuario, foto_perfil, id_rol, cuenta_validada, token_validacion)
VALUES ('Usuario de Prueba', 2000, 'Prefiero no cargarlo', 'Argentina', 'Buenos Aires', 'test@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'test', 'public/uploads/default.png', 1, 1, NULL);

-- Preguntas de prueba
INSERT INTO pregunta (texto_pregunta, categoria, activa) VALUES
('¿Cuál es la capital de Francia?', 'Geografía', 1),
('¿Cuánto es 2 + 2?', 'Matemáticas', 1),
('¿Quién escribió Don Quijote?', 'Literatura', 1),
('¿En qué año llegó el hombre a la luna?', 'Historia', 1),
('¿Cuál es el elemento químico con símbolo H?', 'Ciencias', 1);

-- Respuestas de prueba (1 correcta por pregunta)
-- Pregunta 1: Capital de Francia
INSERT INTO respuesta (id_pregunta, texto_respuesta, es_correcta) VALUES
(1, 'París', 1),
(1, 'Londres', 0),
(1, 'Berlín', 0),
(1, 'Madrid', 0);

-- Pregunta 2: 2 + 2
INSERT INTO respuesta (id_pregunta, texto_respuesta, es_correcta) VALUES
(2, '4', 1),
(2, '5', 0),
(2, '3', 0),
(2, '6', 0);

-- Pregunta 3: Don Quijote
INSERT INTO respuesta (id_pregunta, texto_respuesta, es_correcta) VALUES
(3, 'Miguel de Cervantes', 1),
(3, 'William Shakespeare', 0),
(3, 'Gabriel García Márquez', 0),
(3, 'Jorge Luis Borges', 0);

-- Pregunta 4: Hombre a la luna
INSERT INTO respuesta (id_pregunta, texto_respuesta, es_correcta) VALUES
(4, '1969', 1),
(4, '1965', 0),
(4, '1972', 0),
(4, '1959', 0);

-- Pregunta 5: Elemento H
INSERT INTO respuesta (id_pregunta, texto_respuesta, es_correcta) VALUES
(5, 'Hidrógeno', 1),
(5, 'Helio', 0),
(5, 'Oxígeno', 0),
(5, 'Carbono', 0);
