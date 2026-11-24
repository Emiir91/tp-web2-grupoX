<?php

namespace app\model;

// Cuenta las tareas pendientes de editores y admins para mostrar el globito de la barra superior.
class NotificacionModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    // Editor: reportes de jugadores sin revisar + preguntas sugeridas por jugadores
    public function contarPendientesEditor()
    {
        $fila = $this->database->queryOne(
            "SELECT (SELECT COUNT(*) FROM reportes WHERE estado = 'pendiente')
                  + (SELECT COUNT(*) FROM pregunta WHERE estado = 'sugerida') AS cantidad"
        );
        return $fila ? (int) $fila['cantidad'] : 0;
    }

    // Admin: altas, bajas, reactivaciones y ediciones que mandaron los editores
    public function contarPendientesAdmin()
    {
        $fila = $this->database->queryOne(
            "SELECT (SELECT COUNT(*) FROM pregunta
                     WHERE estado IN ('pendiente_alta', 'pendiente_baja', 'pendiente_reactivacion'))
                  + (SELECT COUNT(*) FROM preguntas_ediciones_pendientes) AS cantidad"
        );
        return $fila ? (int) $fila['cantidad'] : 0;
    }
}
