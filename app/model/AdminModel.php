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
        // Incluye las reactivaciones pedidas por un editor (preguntas eliminadas que quieren volver al juego)
        // También trae los datos de la categoría: si no está activa, la pregunta llega con una categoría nueva
        $sql = "SELECT p.id_pregunta, p.texto_pregunta, p.categoria, p.dificultad,
                       (p.estado = 'pendiente_reactivacion') AS es_reactivacion,
                       c.estado AS estado_categoria, c.color AS color_categoria
                FROM pregunta p
                LEFT JOIN categorias c ON c.nombre = p.categoria
                WHERE p.estado IN ('pendiente_alta', 'pendiente_reactivacion')
                ORDER BY p.id_pregunta ASC";
        $altas = $this->database->queryAll($sql);

        // MySQL devuelve "0"/"1": se pasa a true/false para usarlo en la vista
        foreach ($altas as &$a) {
            $a['es_reactivacion'] = ($a['es_reactivacion'] == 1);
            $a['es_categoria_nueva'] = ($a['estado_categoria'] !== null && $a['estado_categoria'] !== 'activa');
        }
        unset($a);

        return $altas;
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
        // Si la pregunta trae una categoría nueva, se aprueba junto con ella
        $this->database->execute(
            "UPDATE categorias SET estado = 'activa'
             WHERE estado != 'activa'
               AND nombre = (SELECT categoria FROM pregunta WHERE id_pregunta = ?)",
            [$id_pregunta]
        );

        $sql = "UPDATE pregunta SET activa = 1, estado = 'activa' WHERE id_pregunta = ?";
        return $this->database->execute($sql, [$id_pregunta]);
    }

    public function rechazarAlta($id_pregunta)
    {
        // Si era una reactivación, la pregunta vuelve a quedar eliminada (no se borra: ya fue jugada)
        $this->database->execute(
            "UPDATE pregunta SET estado = 'eliminada' WHERE id_pregunta = ? AND estado = 'pendiente_reactivacion'",
            [$id_pregunta]
        );

        // Si era un alta nueva, se borra como antes
        $pregunta = $this->database->queryOne("SELECT categoria FROM pregunta WHERE id_pregunta = ?", [$id_pregunta]);
        // Primero las respuestas (la base no siempre las borra en cascada) y después la pregunta
        $this->database->execute(
            "DELETE FROM respuesta WHERE id_pregunta IN
                (SELECT id_pregunta FROM pregunta WHERE id_pregunta = ? AND estado = 'pendiente_alta')",
            [$id_pregunta]
        );
        $sql = "DELETE FROM pregunta WHERE id_pregunta = ? AND estado = 'pendiente_alta'";
        $resultado = $this->database->execute($sql, [$id_pregunta]);

        // Si traía una categoría nueva que ya no usa ninguna pregunta, también se descarta
        if ($pregunta) {
            $this->database->execute(
                "DELETE FROM categorias
                 WHERE nombre = ? AND estado != 'activa'
                   AND NOT EXISTS (SELECT 1 FROM pregunta WHERE pregunta.categoria = categorias.nombre)",
                [$pregunta['categoria']]
            );
        }

        return $resultado;
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

    // =====================================================================
    // ESTADÍSTICAS
    // =====================================================================

    // Devuelve la condición SQL para filtrar una columna de fecha por el período elegido.
    // $columna siempre la escribe el código (nunca viene del usuario) y $periodo ya llega validado.
    private function condicionPeriodo($columna, $periodo)
    {
        switch ($periodo) {
            case 'dia':
                return "DATE($columna) = CURDATE()";
            case 'semana':
                return "YEARWEEK($columna, 1) = YEARWEEK(CURDATE(), 1)";
            case 'mes':
                return "YEAR($columna) = YEAR(CURDATE()) AND MONTH($columna) = MONTH(CURDATE())";
            case 'anio':
                return "YEAR($columna) = YEAR(CURDATE())";
            default:
                return "1 = 1"; // 'todo': sin filtro
        }
    }

    private function contar($sql)
    {
        $fila = $this->database->queryOne($sql);
        return $fila ? (int) $fila['cantidad'] : 0;
    }

    public function obtenerEstadisticas($periodo)
    {
        $fechaPartida = $this->condicionPeriodo('p.fecha_inicio', $periodo);
        $fechaRegistro = $this->condicionPeriodo('fecha_registro', $periodo);
        $fechaCreacion = $this->condicionPeriodo('fecha_creacion', $periodo);

        // ---------- Indicadores generales ----------
        $indicadores = [
            // Total de jugadores de la aplicación (no depende del período)
            'jugadores' => $this->contar(
                "SELECT COUNT(*) AS cantidad FROM usuarios WHERE id_rol = 1 AND cuenta_validada = 1"
            ),
            // Jugadores que jugaron al menos una partida en el período
            'jugadores_activos' => $this->contar(
                "SELECT COUNT(DISTINCT p.id_usuario) AS cantidad FROM partida p
                 WHERE p.estado != 'en_curso' AND $fechaPartida"
            ),
            'partidas' => $this->contar(
                "SELECT COUNT(*) AS cantidad FROM partida p
                 WHERE p.estado != 'en_curso' AND $fechaPartida"
            ),
            // Preguntas que hoy están en el juego (es una foto del momento actual)
            'preguntas_en_juego' => $this->contar(
                "SELECT COUNT(*) AS cantidad FROM pregunta WHERE estado = 'activa'"
            ),
            'preguntas_creadas' => $this->contar(
                "SELECT COUNT(*) AS cantidad FROM pregunta WHERE $fechaCreacion"
            ),
            'usuarios_nuevos' => $this->contar(
                "SELECT COUNT(*) AS cantidad FROM usuarios WHERE $fechaRegistro"
            )
        ];

        // ---------- % de respuestas correctas por usuario ----------
        $aciertos = $this->database->queryAll(
            "SELECT u.nombre_usuario AS etiqueta,
                    COUNT(*) AS respondidas,
                    SUM(pp.es_correcta) AS correctas,
                    ROUND(100 * SUM(pp.es_correcta) / COUNT(*), 1) AS porcentaje
             FROM partida_pregunta pp
             JOIN partida p ON p.id_partida = pp.id_partida
             JOIN usuarios u ON u.id_usuario = p.id_usuario
             WHERE $fechaPartida
             GROUP BY u.id_usuario, u.nombre_usuario
             ORDER BY porcentaje DESC, respondidas DESC"
        );

        // ---------- Usuarios registrados en el período, por país / sexo / edad ----------
        $porPais = $this->database->queryAll(
            "SELECT pais AS etiqueta, COUNT(*) AS cantidad
             FROM usuarios
             WHERE cuenta_validada = 1 AND $fechaRegistro
             GROUP BY pais
             ORDER BY cantidad DESC, pais ASC"
        );

        $porSexo = $this->database->queryAll(
            "SELECT sexo AS etiqueta, COUNT(*) AS cantidad
             FROM usuarios
             WHERE cuenta_validada = 1 AND $fechaRegistro
             GROUP BY sexo
             ORDER BY cantidad DESC"
        );

        // Grupos de edad: menores (< 18), medio (18 a 64) y jubilados (65 o más)
        $porEdad = $this->database->queryAll(
            "SELECT CASE
                        WHEN YEAR(CURDATE()) - anio_nacimiento < 18 THEN 'Menores (-18)'
                        WHEN YEAR(CURDATE()) - anio_nacimiento >= 65 THEN 'Jubilados (65 o más)'
                        ELSE 'Medio (18 a 64)'
                    END AS etiqueta,
                    COUNT(*) AS cantidad
             FROM usuarios
             WHERE cuenta_validada = 1 AND $fechaRegistro
             GROUP BY etiqueta"
        );

        // Se muestran siempre los 3 grupos, en orden y aunque alguno tenga 0
        $edades = ['Menores (-18)' => 0, 'Medio (18 a 64)' => 0, 'Jubilados (65 o más)' => 0];
        foreach ($porEdad as $fila) {
            $edades[$fila['etiqueta']] = (int) $fila['cantidad'];
        }
        $porEdad = [];
        foreach ($edades as $etiqueta => $cantidad) {
            $porEdad[] = ['etiqueta' => $etiqueta, 'cantidad' => $cantidad];
        }

        // MySQL devuelve los números como texto: se pasan a número para el JSON
        foreach ($aciertos as &$a) {
            $a['respondidas'] = (int) $a['respondidas'];
            $a['correctas'] = (int) $a['correctas'];
            $a['porcentaje'] = (float) $a['porcentaje'];
        }
        unset($a);
        foreach ($porPais as &$fila) {
            $fila['cantidad'] = (int) $fila['cantidad'];
        }
        unset($fila);
        foreach ($porSexo as &$fila) {
            $fila['cantidad'] = (int) $fila['cantidad'];
        }
        unset($fila);

        return [
            'periodo' => $periodo,
            'indicadores' => $indicadores,
            'aciertos_por_usuario' => $aciertos,
            'usuarios_por_pais' => $porPais,
            'usuarios_por_sexo' => $porSexo,
            'usuarios_por_edad' => $porEdad
        ];
    }
}
