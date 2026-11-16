<?php

namespace app\model;

class EditorModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function obtenerTodasLasPreguntas()
    {
        $sql = "SELECT id_pregunta, texto_pregunta, categoria, dificultad, activa, estado
                FROM pregunta
                ORDER BY id_pregunta ASC";
        $preguntas = $this->database->queryAll($sql);

        foreach ($preguntas as &$p) {
            $p['pendiente_baja'] = ($p['estado'] === 'pendiente_baja');
        }
        unset($p);

        return $preguntas;
    }

    public function obtenerPreguntaPorId($id_pregunta)
    {
        $sql = "SELECT * FROM pregunta WHERE id_pregunta = ?";
        return $this->database->queryOne($sql, [$id_pregunta]);
    }

    public function obtenerRespuestasDePregunta($id_pregunta)
    {
        $sql = "SELECT * FROM respuesta WHERE id_pregunta = ?";
        return $this->database->queryAll($sql, [$id_pregunta]);
    }

    public function marcarPendienteBaja($id_pregunta)
    {
        $sql = "UPDATE pregunta SET estado = 'pendiente_baja' WHERE id_pregunta = ?";
        return $this->database->execute($sql, [$id_pregunta]);
    }

    public function guardarEdicionPendiente($id_pregunta, $datos)
    {
        $sql = "INSERT INTO preguntas_ediciones_pendientes
                    (id_pregunta, texto_pregunta, categoria, dificultad,
                     respuesta_correcta, incorrecta_1, incorrecta_2, incorrecta_3, fecha)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        return $this->database->execute($sql, [
            $id_pregunta,
            $datos['texto_pregunta'],
            $datos['categoria'],
            $datos['dificultad'],
            $datos['respuesta_correcta'],
            $datos['incorrecta_1'],
            $datos['incorrecta_2'],
            $datos['incorrecta_3']
        ]);
    }

    public function crearPreguntaPendiente($datos)
    {
        try {
            $sql = "INSERT INTO pregunta (texto_pregunta, categoria, dificultad, activa, estado)
                    VALUES (?, ?, ?, 0, 'pendiente_alta')";
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

    public function obtenerReportesPendientes()
    {
        $sql = "SELECT r.id_reporte, r.id_pregunta, r.motivo, r.fecha,
                       p.texto_pregunta, u.nombre_usuario
                FROM reportes r
                JOIN pregunta p ON p.id_pregunta = r.id_pregunta
                JOIN usuarios u ON u.id_usuario = r.id_usuario
                WHERE r.estado = 'pendiente'
                ORDER BY r.fecha DESC";
        return $this->database->queryAll($sql);
    }

    public function obtenerReportePorId($id_reporte)
    {
        $sql = "SELECT * FROM reportes WHERE id_reporte = ?";
        return $this->database->queryOne($sql, [$id_reporte]);
    }

    public function desestimarReporte($id_reporte)
    {
        $sql = "UPDATE reportes SET estado = 'resuelto' WHERE id_reporte = ?";
        return $this->database->execute($sql, [$id_reporte]);
    }

    public function obtenerSugeridas()
    {
        $sql = "SELECT p.id_pregunta, p.texto_pregunta, p.categoria, p.dificultad, p.estado
                FROM pregunta p
                WHERE p.estado = 'sugerida'
                ORDER BY p.id_pregunta ASC";
        $sugeridas = $this->database->queryAll($sql);

        foreach ($sugeridas as &$s) {
            $s['respuestas'] = $this->obtenerRespuestasDePregunta($s['id_pregunta']);
        }
        unset($s);

        return $sugeridas;
    }

    public function aprobarSugerencia($id_pregunta, $es_admin)
    {
        if ($es_admin) {
            $sql = "UPDATE pregunta SET activa = 1, estado = 'activa' WHERE id_pregunta = ? AND estado = 'sugerida'";
        } else {
            $sql = "UPDATE pregunta SET activa = 0, estado = 'pendiente_alta' WHERE id_pregunta = ? AND estado = 'sugerida'";
        }
        return $this->database->execute($sql, [$id_pregunta]);
    }

    public function rechazarSugerencia($id_pregunta)
    {
        $sql = "DELETE FROM pregunta WHERE id_pregunta = ? AND estado = 'sugerida'";
        return $this->database->execute($sql, [$id_pregunta]);
    }
}
