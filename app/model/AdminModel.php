<?php

namespace app\model;

class AdminModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function obtenerPendientesAltas()
    {
        $sql = "SELECT id_pregunta, texto_pregunta, categoria, dificultad
                FROM pregunta
                WHERE estado = 'pendiente_alta'
                ORDER BY id_pregunta ASC";
        return $this->database->queryAll($sql);
    }

    public function obtenerPendientesBajas()
    {
        $sql = "SELECT id_pregunta, texto_pregunta, categoria, dificultad
                FROM pregunta
                WHERE estado = 'pendiente_baja'
                ORDER BY id_pregunta ASC";
        return $this->database->queryAll($sql);
    }

    public function obtenerEdicionesPendientes()
    {
        $sql = "SELECT e.id_edicion, e.id_pregunta, e.texto_pregunta, e.categoria, e.dificultad,
                       e.respuesta_correcta, e.incorrecta_1, e.incorrecta_2, e.incorrecta_3,
                       p.texto_pregunta AS texto_original
                FROM preguntas_ediciones_pendientes e
                JOIN pregunta p ON p.id_pregunta = e.id_pregunta
                ORDER BY e.id_edicion ASC";
        return $this->database->queryAll($sql);
    }

    public function obtenerRespuestasDePregunta($id_pregunta)
    {
        $sql = "SELECT * FROM respuesta WHERE id_pregunta = ?";
        return $this->database->queryAll($sql, [$id_pregunta]);
    }

    public function aprobarAlta($id_pregunta)
    {
        $sql = "UPDATE pregunta SET activa = 1, estado = 'activa' WHERE id_pregunta = ?";
        return $this->database->execute($sql, [$id_pregunta]);
    }

    public function rechazarAlta($id_pregunta)
    {
        $sql = "DELETE FROM pregunta WHERE id_pregunta = ? AND estado = 'pendiente_alta'";
        return $this->database->execute($sql, [$id_pregunta]);
    }

    public function aprobarBaja($id_pregunta)
    {
        $sql = "UPDATE pregunta SET activa = 0, estado = 'eliminada' WHERE id_pregunta = ?";
        return $this->database->execute($sql, [$id_pregunta]);
    }

    public function rechazarBaja($id_pregunta)
    {
        $sql = "UPDATE pregunta SET estado = 'activa' WHERE id_pregunta = ? AND estado = 'pendiente_baja'";
        return $this->database->execute($sql, [$id_pregunta]);
    }

    public function aprobarEdicion($id_edicion)
    {
        $edicion = $this->database->queryOne(
            "SELECT * FROM preguntas_ediciones_pendientes WHERE id_edicion = ?",
            [$id_edicion]
        );

        if (!$edicion) {
            return false;
        }

        $id_pregunta = $edicion['id_pregunta'];

        $this->database->execute(
            "UPDATE pregunta SET texto_pregunta = ?, categoria = ?, dificultad = ? WHERE id_pregunta = ?",
            [$edicion['texto_pregunta'], $edicion['categoria'], $edicion['dificultad'], $id_pregunta]
        );

        $this->database->execute("DELETE FROM respuesta WHERE id_pregunta = ?", [$id_pregunta]);

        $respuestas = [
            [$edicion['respuesta_correcta'], 1],
            [$edicion['incorrecta_1'], 0],
            [$edicion['incorrecta_2'], 0],
            [$edicion['incorrecta_3'], 0]
        ];

        foreach ($respuestas as $r) {
            $this->database->execute(
                "INSERT INTO respuesta (id_pregunta, texto_respuesta, es_correcta) VALUES (?, ?, ?)",
                [$id_pregunta, $r[0], $r[1]]
            );
        }

        $this->database->execute("DELETE FROM preguntas_ediciones_pendientes WHERE id_edicion = ?", [$id_edicion]);

        return true;
    }

    public function rechazarEdicion($id_edicion)
    {
        $sql = "DELETE FROM preguntas_ediciones_pendientes WHERE id_edicion = ?";
        return $this->database->execute($sql, [$id_edicion]);
    }
}
