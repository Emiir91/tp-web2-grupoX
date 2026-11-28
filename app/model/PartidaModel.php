<?php

namespace app\model;

class PartidaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function iniciarPartida($id_usuario, $modo_juego = 'progresivo')
    {
        $nivel_inicial = ($modo_juego === 'progresivo' || in_array($modo_juego, ['facil', 'intermedio', 'dificil']))
            ? ($modo_juego === 'progresivo' ? 'facil' : $modo_juego)
            : 'facil';

        $sql = "INSERT INTO partida (
                id_usuario,
                puntaje,
                racha,
                estado,
                modo_juego,
                nivel_actual,
                vidas,
                fecha_inicio
            ) VALUES (?, 0, 0, 'en_curso', ?, ?, 3, NOW())";

        $parametros = [$id_usuario, $modo_juego, $nivel_inicial];

        try {
            $this->database->execute($sql, $parametros);
            return $this->database->lastInsertId();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function restarVida($id_partida)
    {
        $sql = "UPDATE partida SET vidas = vidas - 1 WHERE id_partida = ?";
        return $this->database->execute($sql, [$id_partida]);
    }

    public function obtenerVidasActuales($id_partida)
    {
        $sql = "SELECT vidas FROM partida WHERE id_partida = ?";
        $resultado = $this->database->queryOne($sql, [$id_partida]);
        return $resultado ? $resultado['vidas'] : 0;
    }

    public function contarPreguntasPorNivel($categoria)
    {
        $sql = "SELECT dificultad, COUNT(*) AS cantidad
            FROM pregunta
            WHERE categoria = ?
              AND activa = 1
              AND estado = 'activa'
            GROUP BY dificultad";

        $filas = $this->database->queryAll($sql, [$categoria]);

        return array_column($filas, 'cantidad', 'dificultad');
    }


    public function obtenerPreguntaAleatoria($id_partida, $categoria = null)
    {
        $sql_partida = "SELECT id_usuario, modo_juego, nivel_actual, preguntas_usadas
                    FROM partida WHERE id_partida = ?";

        $partida = $this->database->queryOne($sql_partida, [$id_partida]);

        if (!$partida) {
            return false;
        }

        $modo_juego = $partida['modo_juego'];
        $nivel_actual = $partida['nivel_actual'];
        $preguntas_usadas = trim($partida['preguntas_usadas'] ?? '');
        $ids_usados = [];

        if ($preguntas_usadas !== '') {
            foreach (explode(',', $preguntas_usadas) as $id) {
                $id = (int) trim($id);

                if ($id > 0) {
                    $ids_usados[$id] = $id;
                }
            }
        }

        $cantidad_usadas = count($ids_usados);
        $limite = ($modo_juego === 'progresivo') ? 18 : 12;

        if ($cantidad_usadas >= $limite) {
            return false;
        }

        if ($modo_juego === 'progresivo') {
            if ($cantidad_usadas < 8) {
                $nivel_requerido = 'facil';
            } elseif ($cantidad_usadas < 14) {
                $nivel_requerido = 'intermedio';
            } else {
                $nivel_requerido = 'dificil';
            }

            if ($nivel_actual !== $nivel_requerido) {
                $this->actualizarNivelPartida($id_partida, $nivel_requerido);
                $nivel_actual = $nivel_requerido;
            }
        }

        $sql = "SELECT * FROM pregunta
            WHERE activa = 1
              AND estado = 'activa'
              AND dificultad = ?";

        if ($ids_usados) {
            $sql .= " AND id_pregunta NOT IN (" . implode(',', $ids_usados) . ")";
        }

        $parametros = [$nivel_actual];

        if ($categoria !== null) {
            $sql .= " AND pregunta.categoria = ?";
            $parametros[] = $categoria;
        }

        $parametros[] = $partida['id_usuario'];

        $sql .= " ORDER BY EXISTS (
                SELECT 1
                FROM partida_pregunta pp
                JOIN partida p ON p.id_partida = pp.id_partida
                WHERE p.id_usuario = ?
                  AND pp.id_pregunta = pregunta.id_pregunta
            ) ASC, RAND()
            LIMIT 1";

        return $this->database->queryOne($sql, $parametros) ?: false;
    }


    private function siguienteNivel($nivelActual)
    {
        if ($nivelActual === 'facil') return 'intermedio';
        if ($nivelActual === 'intermedio') return 'dificil';
        return null;
    }

    public function actualizarNivelPartida($id_partida, $nuevo_nivel)
    {
        $sql = "UPDATE partida SET nivel_actual = ? WHERE id_partida = ?";
        return $this->database->execute($sql, [$nuevo_nivel, $id_partida]);
    }

    public function obtenerPreguntaPorId($id_pregunta)
    {
        $sql = "SELECT * FROM pregunta WHERE id_pregunta = ?";
        $pregunta = $this->database->queryOne($sql, [$id_pregunta]);
        return $pregunta ?: false;
    }

    public function asignarPreguntaAPartida($id_partida, $id_pregunta)
    {
        $sql_actual = "SELECT ultima_pregunta_id, preguntas_usadas FROM partida WHERE id_partida = ?";
        $datos_actuales = $this->database->queryOne($sql_actual, [$id_partida]);

        if (!$datos_actuales) {
            return false;
        }

        $preguntas_usadas = $datos_actuales['preguntas_usadas'] ?? '';
        if (!empty($preguntas_usadas)) {
            $preguntas_usadas .= ',' . $id_pregunta;
        } else {
            $preguntas_usadas = (string)$id_pregunta;
        }

        if ($datos_actuales['ultima_pregunta_id'] == $id_pregunta) {
            $sql = "UPDATE partida
                    SET ultima_pregunta_id = ?,
                        preguntas_usadas = ?
                    WHERE id_partida = ?";

            $parametros = [$id_pregunta, $preguntas_usadas, $id_partida];
        } else {
            $sql = "UPDATE partida
                    SET ultima_pregunta_id = ?,
                        preguntas_usadas = ?,
                        tiempo_inicio_pregunta = NOW()
                    WHERE id_partida = ?";

            $parametros = [$id_pregunta, $preguntas_usadas, $id_partida];
        }

        return $this->database->execute($sql, $parametros);
    }

    public function obtenerRespuestasDePregunta($id_pregunta)
    {
        $sql = "SELECT * FROM respuesta
                WHERE id_pregunta = ?
                ORDER BY RAND()";

        $respuestas = $this->database->queryAll($sql, [$id_pregunta]);

        return $respuestas ?: [];
    }

    public function verificarPartidaActiva($id_partida)
    {
        $sql = "SELECT estado FROM partida WHERE id_partida = ?";
        $resultado = $this->database->queryOne($sql, [$id_partida]);

        if (!$resultado) {
            return false;
        }

        return $resultado['estado'] === 'en_curso';
    }

    public function validarTiempo($id_partida)
    {
        $sql = "SELECT TIMESTAMPDIFF(SECOND, tiempo_inicio_pregunta, NOW()) as segundos_transcurridos
                FROM partida
                WHERE id_partida = ?";

        $resultado = $this->database->queryOne($sql, [$id_partida]);

        if (!$resultado || $resultado['segundos_transcurridos'] === null) {
            return 0;
        }

        $segundos_transcurridos = $resultado['segundos_transcurridos'];

        if ($segundos_transcurridos >= 10) {
            return 0;
        }

        return 10 - $segundos_transcurridos;
    }

    // Anti-trampa (grilla 4.4): además de ver si es correcta, la respuesta tiene que ser
    // una de las 4 opciones de la pregunta que se le mostró al jugador.
    public function respuestaPerteneceAPregunta($id_respuesta, $id_pregunta)
    {
        $sql = "SELECT 1 FROM respuesta WHERE id_respuesta = ? AND id_pregunta = ?";
        return $this->database->queryOne($sql, [$id_respuesta, $id_pregunta]) !== null;
    }

    // Anti-trampa (grilla 4.6): una pregunta ya respondida en esta partida no se puede volver a responder
    public function preguntaYaRespondida($id_partida, $id_pregunta)
    {
        $sql = "SELECT 1 FROM partida_pregunta WHERE id_partida = ? AND id_pregunta = ?";
        return $this->database->queryOne($sql, [$id_partida, $id_pregunta]) !== null;
    }

    // Grilla 4.5: partida en curso del jugador (si la tiene), con la pregunta que se le estaba mostrando
    // y cuántos segundos pasaron desde que se le mostró (calculado por MySQL para no depender de la hora de PHP).
    public function obtenerPartidaEnCurso($id_usuario)
    {
        $sql = "SELECT p.id_partida,
                       p.ultima_pregunta_id,
                       TIMESTAMPDIFF(SECOND, p.tiempo_inicio_pregunta, NOW()) AS segundos_transcurridos,
                       EXISTS (SELECT 1 FROM partida_pregunta pp
                               WHERE pp.id_partida = p.id_partida AND pp.id_pregunta = p.ultima_pregunta_id) AS ya_respondida
                FROM partida p
                WHERE p.id_usuario = ? AND p.estado = 'en_curso'
                ORDER BY p.id_partida DESC
                LIMIT 1";
        return $this->database->queryOne($sql, [$id_usuario]);
    }

    public function validarRespuesta($id_respuesta)
    {
        $sql = "SELECT es_correcta FROM respuesta WHERE id_respuesta = ?";
        $resultado = $this->database->queryOne($sql, [$id_respuesta]);

        if (!$resultado) {
            return false;
        }

        return $resultado['es_correcta'] == 1;
    }

    public function registrarRespuestaCorrecta($id_partida, $nuevo_puntaje, $nueva_racha)
    {
        $sql = "UPDATE partida
                SET puntaje = ?,
                    racha = ?
                WHERE id_partida = ?";

        $parametros = [$nuevo_puntaje, $nueva_racha, $id_partida];

        return $this->database->execute($sql, $parametros);
    }

    public function registrarRespuestaPartida($id_partida, $id_pregunta, $id_respuesta, $es_correcta)
    {
        $sql = "INSERT INTO partida_pregunta (
                    id_partida,
                    id_pregunta,
                    id_respuesta_elegida,
                    es_correcta
                ) VALUES (?, ?, ?, ?)";

        $parametros = [$id_partida, $id_pregunta, $id_respuesta, $es_correcta];

        return $this->database->execute($sql, $parametros);
    }

    public function obtenerPreguntasRespondidas($id_partida)
    {
        $sql = "SELECT
                    pp.id_pregunta,
                    p.texto_pregunta,
                    p.categoria,
                    re.texto_respuesta AS respuesta_elegida,
                    rc.texto_respuesta AS respuesta_correcta,
                    pp.es_correcta
                FROM partida_pregunta pp
                JOIN pregunta p ON pp.id_pregunta = p.id_pregunta
                LEFT JOIN respuesta re ON pp.id_respuesta_elegida = re.id_respuesta
                JOIN respuesta rc ON rc.id_pregunta = p.id_pregunta AND rc.es_correcta = 1
                WHERE pp.id_partida = ?
                ORDER BY pp.id ASC";

        return $this->database->queryAll($sql, [$id_partida]);
    }

    public function registrarReporte($id_pregunta, $id_usuario, $motivo, $fecha, $tipo = 'otro')
    {
        $sql = "INSERT INTO reportes (
                    id_pregunta,
                    id_usuario,
                    motivo,
                    fecha,
                    estado,
                    tipo
                ) VALUES (?, ?, ?, ?, 'pendiente', ?)";

        $parametros = [$id_pregunta, $id_usuario, $motivo, $fecha, $tipo];

        return $this->database->execute($sql, $parametros);
    }

    public function finalizarPartida($id_partida, $estado)
    {
        $sql = "UPDATE partida
                SET estado = ?,
                    fecha_fin = NOW()
                WHERE id_partida = ?";

        $parametros = [$estado, $id_partida];

        return $this->database->execute($sql, $parametros);
    }

    public function obtenerPuntajeActual($id_partida)
    {
        $sql = "SELECT puntaje FROM partida WHERE id_partida = ?";
        $resultado = $this->database->queryOne($sql, [$id_partida]);
        return $resultado ? $resultado['puntaje'] : 0;
    }

    public function obtenerRachaActual($id_partida)
    {
        $sql = "SELECT racha FROM partida WHERE id_partida = ?";
        $resultado = $this->database->queryOne($sql, [$id_partida]);
        return $resultado ? $resultado['racha'] : 0;
    }

    // Modo de juego y nivel actual (para mostrar el distintivo de nivel en el progresivo)
    public function obtenerModoYNivel($id_partida)
    {
        return $this->database->queryOne(
            "SELECT modo_juego, nivel_actual FROM partida WHERE id_partida = ?",
            [$id_partida]
        );
    }

    public function obtenerDatosPartida($id_partida)
    {
        $sql = "SELECT puntaje, racha, estado, nivel_actual, vidas FROM partida WHERE id_partida = ?";
        $resultado = $this->database->queryOne($sql, [$id_partida]);
        return $resultado ?: false;
    }


    public function obtenerHistorialUsuario($id_usuario, $limite = 10)
    {
        $limite = (int) $limite;

        $sql = "SELECT puntaje AS puntos, fecha_fin AS fecha, estado
            FROM partida
            WHERE id_usuario = ? AND estado != 'en_curso'
            ORDER BY fecha_fin DESC
            LIMIT $limite";

        $partidas = $this->database->queryAll(
            $sql,
            [$id_usuario]
        );

        if ($partidas) {
            foreach ($partidas as &$partida) {

                $partida['fecha'] = $partida['fecha']
                    ? date('d/m/Y H:i', strtotime($partida['fecha']))
                    : 'Desconocida';

                if ($partida['estado'] === 'finalizada') {
                    $partida['resultado'] = 'victoria';
                } else {
                    $partida['resultado'] = 'derrota';
                }
            }

            unset($partida);
        }

        return $partidas ?: [];
    }


    public function obtenerPuntajeTotalUsuario($id_usuario)
    {
        $sql = "SELECT SUM(puntaje) as total FROM partida WHERE id_usuario = ? AND estado != 'en_curso'";
        $resultado = $this->database->queryOne($sql, [$id_usuario]);
        return $resultado ? ($resultado['total'] ?? 0) : 0;
    }

    // Puntaje del mes actual: mismo criterio que el ranking mensual
    public function obtenerPuntajeMensualUsuario($id_usuario)
    {
        $sql = "SELECT SUM(puntaje) AS total FROM partida
                WHERE id_usuario = ? AND estado != 'en_curso'
                  AND MONTH(fecha_fin) = MONTH(CURRENT_DATE())
                  AND YEAR(fecha_fin) = YEAR(CURRENT_DATE())";
        $resultado = $this->database->queryOne($sql, [$id_usuario]);
        return $resultado ? (int) ($resultado['total'] ?? 0) : 0;
    }

    public function obtenerCantidadPartidasUsuario($id_usuario)
    {
        $sql = "SELECT COUNT(*) as cantidad FROM partida WHERE id_usuario = ? AND estado != 'en_curso'";
        $resultado = $this->database->queryOne($sql, [$id_usuario]);
        return $resultado ? (int) $resultado['cantidad'] : 0;
    }

    public function obtenerRankingMensualGlobal($limite = 10)
    {
        $limite = (int) $limite;

        $sql = "SELECT u.nombre_usuario AS usuario, SUM(p.puntaje) AS puntaje_total
            FROM partida p
            JOIN usuarios u ON p.id_usuario = u.id_usuario
            WHERE p.estado != 'en_curso'
              AND MONTH(p.fecha_fin) = MONTH(CURRENT_DATE()) 
              AND YEAR(p.fecha_fin) = YEAR(CURRENT_DATE())
            GROUP BY p.id_usuario, u.nombre_usuario
            ORDER BY puntaje_total DESC
            LIMIT $limite";

        return $this->database->queryAll($sql);
    }

    public function crearSugerencia($datos)
    {
        try {
            $sql = "INSERT INTO pregunta (texto_pregunta, categoria, dificultad, activa, estado)
                    VALUES (?, ?, ?, 0, 'sugerida')";
            $this->database->execute($sql, [
                $datos['texto_pregunta'],
                $datos['categoria'],
                $datos['dificultad']
            ]);
            $id_pregunta = $this->database->lastInsertId();

            $respuestas = [
                [$datos['respuesta_correcta'], 1],
                [$datos['incorrecta_1'], 0],
                [$datos['incorrecta_2'], 0],
                [$datos['incorrecta_3'], 0]
            ];

            $sqlResp = "INSERT INTO respuesta (id_pregunta, texto_respuesta, es_correcta) VALUES (?, ?, ?)";
            foreach ($respuestas as $r) {
                $this->database->execute($sqlResp, [$id_pregunta, $r[0], $r[1]]);
            }

            return $id_pregunta;
        } catch (\Exception $e) {
            return false;
        }
    }

}
