<?php

namespace app\model;

class PartidaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function iniciarPartida($id_usuario)
    {
        $sql = "INSERT INTO partida (
                    id_usuario,
                    puntaje,
                    racha,
                    estado,
                    fecha_inicio
                ) VALUES (?, 0, 0, 'en_curso', NOW())";

        $parametros = [$id_usuario];

        try {
            $this->database->execute($sql, $parametros);
            return $this->database->lastInsertId();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function obtenerPreguntaAleatoria($id_partida)
    {
        $sql_partida = "SELECT preguntas_usadas FROM partida WHERE id_partida = ?";
        $resultado_partida = $this->database->queryOne($sql_partida, [$id_partida]);

        $preguntas_usadas = '';
        if ($resultado_partida && !empty($resultado_partida['preguntas_usadas'])) {
            $preguntas_usadas = $resultado_partida['preguntas_usadas'];
        }

        if (!empty($preguntas_usadas)) {
            $sql = "SELECT * FROM pregunta
                    WHERE activa = 1
                    AND id_pregunta NOT IN ($preguntas_usadas)
                    ORDER BY RAND()
                    LIMIT 1";
        } else {
            $sql = "SELECT * FROM pregunta
                    WHERE activa = 1
                    ORDER BY RAND()
                    LIMIT 1";
        }

        $pregunta = $this->database->queryOne($sql);

        return $pregunta ?: false;
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

    public function obtenerDatosPartida($id_partida)
    {
        $sql = "SELECT puntaje, racha, estado FROM partida WHERE id_partida = ?";
        $resultado = $this->database->queryOne($sql, [$id_partida]);
        return $resultado ?: false;
    }

    public function obtenerHistorialUsuario($id_usuario)
    {
        $sql = "SELECT puntaje as puntos, fecha_fin as fecha, estado
                FROM partida
                WHERE id_usuario = ? AND estado != 'en_curso'
                ORDER BY fecha_fin DESC
                LIMIT 10"; 

        $partidas = $this->database->queryAll($sql, [$id_usuario]);

        if ($partidas) {
            foreach ($partidas as &$partida) {
                
                $partida['fecha'] = $partida['fecha'] ? date('d/m/Y H:i', strtotime($partida['fecha'])) : 'Desconocida';

                if ($partida['puntos'] >= 50) {
                    $partida['resultado'] = 'victoria';
                } else {
                    $partida['resultado'] = 'derrota';
                }
            }
        }

        return $partidas ?: [];
    }

    public function obtenerPuntajeTotalUsuario($id_usuario)
    {
        $sql = "SELECT SUM(puntaje) as total FROM partida WHERE id_usuario = ? AND estado != 'en_curso'";
        $resultado = $this->database->queryOne($sql, [$id_usuario]);
        return $resultado ? ($resultado['total'] ?? 0) : 0;
    }
}
