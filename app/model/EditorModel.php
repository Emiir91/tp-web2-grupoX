<?php

namespace app\model;

class EditorModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    // Trae las preguntas del catálogo: solo las que fueron validadas en algún momento
    // (activas, pendientes de baja y eliminadas). Las sugeridas y las pendientes de alta
    // no aparecen porque todavía nadie las aprobó; tienen su propia sección.
    // Se pueden filtrar por categoría, dificultad y/o estado.
    // Orden: activas arriba y eliminadas abajo; después por dificultad (fácil, intermedio, difícil)
    // y por último por categoría (A-Z).
    private function armarWhereCatalogo($filtros)
    {
        $condiciones = ["estado IN ('activa', 'pendiente_baja', 'pendiente_reactivacion', 'eliminada')"];
        $parametros = [];

        $categoria = $filtros['categoria'] ?? null;
        $dificultad = $filtros['dificultad'] ?? null;
        $estado = $filtros['estado'] ?? null;

        if ($categoria) {
            $condiciones[] = "categoria = ?";
            $parametros[] = $categoria;
        }

        if ($dificultad) {
            $condiciones[] = "dificultad = ?";
            $parametros[] = $dificultad;
        }

        if ($estado) {
            $condiciones[] = "estado = ?";
            $parametros[] = $estado;
        }

        return [implode(" AND ", $condiciones), $parametros];
    }

    public function contarPreguntas($filtros = [])
    {
        [$where, $parametros] = $this->armarWhereCatalogo($filtros);

        $sql = "SELECT COUNT(*) AS total FROM pregunta WHERE " . $where;
        $resultado = $this->database->queryOne($sql, $parametros);

        return $resultado ? (int) $resultado['total'] : 0;
    }

    public function obtenerTodasLasPreguntas($filtros = [], $limit = null, $offset = 0)
    {
        [$where, $parametros] = $this->armarWhereCatalogo($filtros);

        $sql = "SELECT id_pregunta, texto_pregunta, categoria, dificultad, activa, estado
                FROM pregunta
                WHERE " . $where . "
                ORDER BY id_pregunta DESC";

        $tipos = array_fill(0, count($parametros), \PDO::PARAM_STR);
        if ($limit !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $parametros[] = (int) $limit;
            $parametros[] = (int) $offset;
            $tipos[] = \PDO::PARAM_INT;
            $tipos[] = \PDO::PARAM_INT;
        }

        $preguntas = $this->database->queryAll($sql, $parametros, $tipos);

        foreach ($preguntas as &$p) {
            $p['pendiente_baja'] = ($p['estado'] === 'pendiente_baja');
            // Qué botones se muestran según el estado
            $p['puede_editar'] = ($p['estado'] === 'activa');
            $p['puede_eliminar'] = ($p['estado'] === 'activa');
            $p['puede_reactivar'] = ($p['estado'] === 'eliminada');
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
        // Solo se puede pedir la baja de una pregunta activa
        $sql = "UPDATE pregunta SET estado = 'pendiente_baja' WHERE id_pregunta = ? AND estado = 'activa'";
        return $this->database->execute($sql, [$id_pregunta]);
    }

    // Admin: aplica la edición directamente sobre la pregunta y reemplaza sus respuestas
    public function aplicarEdicion($id_pregunta, $datos)
    {
        $this->database->execute(
            "UPDATE pregunta SET texto_pregunta = ?, categoria = ?, dificultad = ? WHERE id_pregunta = ?",
            [$datos['texto_pregunta'], $datos['categoria'], $datos['dificultad'], $id_pregunta]
        );

        $this->database->execute("DELETE FROM respuesta WHERE id_pregunta = ?", [$id_pregunta]);

        $respuestas = [
            [$datos['respuesta_correcta'], 1],
            [$datos['incorrecta_1'], 0],
            [$datos['incorrecta_2'], 0],
            [$datos['incorrecta_3'], 0]
        ];

        foreach ($respuestas as $r) {
            $this->database->execute(
                "INSERT INTO respuesta (id_pregunta, texto_respuesta, es_correcta) VALUES (?, ?, ?)",
                [$id_pregunta, $r[0], $r[1]]
            );
        }

        return true;
    }

    // Reactiva una pregunta eliminada.
    // Admin: vuelve a activa en el momento. Editor: queda pendiente de reactivación para el admin.
    public function reactivar($id_pregunta, $directa = false)
    {
        if ($directa) {
            $sql = "UPDATE pregunta SET activa = 1, estado = 'activa'
                    WHERE id_pregunta = ? AND estado = 'eliminada'";
        } else {
            $sql = "UPDATE pregunta SET estado = 'pendiente_reactivacion'
                    WHERE id_pregunta = ? AND estado = 'eliminada'";
        }
        return $this->database->execute($sql, [$id_pregunta]);
    }

    // Admin: da de baja la pregunta directamente (sin pasar por pendiente de baja)
    public function eliminarDirecto($id_pregunta)
    {
        $sql = "UPDATE pregunta SET activa = 0, estado = 'eliminada'
                WHERE id_pregunta = ? AND estado IN ('activa', 'pendiente_baja')";
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

    // Crea la pregunta con sus 4 respuestas.
    // Si la crea un editor queda pendiente de alta; si la crea el admin queda activa directamente.
    public function crearPreguntaPendiente($datos, $directa = false)
    {
        try {
            if ($directa) {
                $sql = "INSERT INTO pregunta (texto_pregunta, categoria, dificultad, activa, estado)
                        VALUES (?, ?, ?, 1, 'activa')";
            } else {
                $sql = "INSERT INTO pregunta (texto_pregunta, categoria, dificultad, activa, estado)
                        VALUES (?, ?, ?, 0, 'pendiente_alta')";
            }
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
        $sql = "SELECT r.id_reporte, r.id_pregunta, r.motivo, r.fecha, r.tipo,
                       p.texto_pregunta, p.categoria, u.nombre_usuario
                FROM reportes r
                JOIN pregunta p ON p.id_pregunta = r.id_pregunta
                JOIN usuarios u ON u.id_usuario = r.id_usuario
                WHERE r.estado = 'pendiente'
                ORDER BY r.fecha DESC";
        $reportes = $this->database->queryAll($sql);

        // Texto del motivo rápido elegido por el jugador
        $nombresTipo = [
            'respuesta_incorrecta' => '❌ Respuesta correcta mal',
            'categoria_incorrecta' => '🏷️ Categoría equivocada',
            'mal_redactada' => '✏️ Mal escrita',
            'otro' => '💬 Otro'
        ];
        foreach ($reportes as &$r) {
            $r['nombre_tipo'] = $nombresTipo[$r['tipo']] ?? $nombresTipo['otro'];
            $r['tiene_motivo'] = trim($r['motivo']) !== '';
        }
        unset($r);

        return $reportes;
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
        // Se trae también el estado de la categoría: si no está activa, la sugerencia propone una categoría nueva
        $sql = "SELECT p.id_pregunta, p.texto_pregunta, p.categoria, p.dificultad, p.estado,
                       c.estado AS estado_categoria
                FROM pregunta p
                LEFT JOIN categorias c ON c.nombre = p.categoria
                WHERE p.estado = 'sugerida'
                ORDER BY p.id_pregunta ASC";
        $sugeridas = $this->database->queryAll($sql);

        foreach ($sugeridas as &$s) {
            $s['respuestas'] = $this->obtenerRespuestasDePregunta($s['id_pregunta']);
            $s['es_categoria_nueva'] = ($s['estado_categoria'] !== 'activa');
        }
        unset($s);

        return $sugeridas;
    }

    // Pasa la pregunta a otra categoría (al aprobar una sugerencia en una categoría existente)
    public function cambiarCategoria($id_pregunta, $categoria)
    {
        $sql = "UPDATE pregunta SET categoria = ? WHERE id_pregunta = ?";
        return $this->database->execute($sql, [$categoria, $id_pregunta]);
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
        // Primero las respuestas (la base no siempre las borra en cascada) y después la pregunta
        $this->database->execute(
            "DELETE FROM respuesta WHERE id_pregunta IN
                (SELECT id_pregunta FROM pregunta WHERE id_pregunta = ? AND estado = 'sugerida')",
            [$id_pregunta]
        );
        $sql = "DELETE FROM pregunta WHERE id_pregunta = ? AND estado = 'sugerida'";
        return $this->database->execute($sql, [$id_pregunta]);
    }
}
