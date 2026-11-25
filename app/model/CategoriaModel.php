<?php

namespace app\model;

// Categorías de las preguntas (nombre y color). Estados:
//   activa         -> se usa en el juego y en los formularios
//   sugerida       -> la propuso un jugador junto con una pregunta (la revisa el editor)
//   pendiente_alta -> la aprobó o creó un editor (la revisa el admin)
//   inactiva               -> dada de baja (no se borra: las partidas pasadas la siguen mostrando)
//   pendiente_baja         -> un editor pidió darla de baja (la revisa el admin)
//   pendiente_reactivacion -> un editor pidió reactivarla (la revisa el admin)
// Una categoría nueva siempre llega atada a una pregunta.
class CategoriaModel
{
    public const COLOR_POR_DEFECTO = '#ff6a00';

    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    // Categorías activas ordenadas por nombre (para los desplegables)
    public function obtenerActivas()
    {
        return $this->database->queryAll(
            "SELECT nombre, color FROM categorias WHERE estado = 'activa' ORDER BY nombre ASC"
        );
    }

    // Solo los nombres de las categorías activas (para validar)
    public function obtenerNombresActivos()
    {
        return array_column($this->obtenerActivas(), 'nombre');
    }

    // Busca una categoría por nombre en cualquier estado.
    // La comparación de MySQL no distingue mayúsculas ni tildes ("historia" = "Historia").
    public function buscarPorNombre($nombre)
    {
        return $this->database->queryOne(
            "SELECT id_categoria, nombre, color, estado FROM categorias WHERE nombre = ?",
            [$nombre]
        );
    }

    // Color de todas las categorías: ['Historia' => '#c8813a', ...]
    public function obtenerMapaColores()
    {
        $mapa = [];
        foreach ($this->database->queryAll("SELECT nombre, color FROM categorias") as $fila) {
            $mapa[$fila['nombre']] = $fila['color'];
        }
        return $mapa;
    }

    public function obtenerColor($nombre)
    {
        $categoria = $this->buscarPorNombre($nombre);
        return $categoria ? $categoria['color'] : self::COLOR_POR_DEFECTO;
    }

    // Jugador: sugiere una categoría nueva. Si ya existe (en cualquier estado) no se duplica.
    // Devuelve el nombre con el que quedó guardada.
    public function sugerir($nombre)
    {
        $existente = $this->buscarPorNombre($nombre);
        if ($existente) {
            return $existente['nombre'];
        }

        $this->database->execute(
            "INSERT INTO categorias (nombre, estado) VALUES (?, 'sugerida')",
            [$nombre]
        );
        return $nombre;
    }

    // Editor/admin: crea una categoría nueva o completa una sugerida (con su color).
    // $directa = true (admin): queda activa. false (editor): queda pendiente de alta para el admin.
    // Devuelve el nombre con el que quedó guardada.
    public function crearOAprobar($nombre, $color, $directa)
    {
        $estado = $directa ? 'activa' : 'pendiente_alta';
        $existente = $this->buscarPorNombre($nombre);

        if ($existente) {
            // Si ya está activa no se toca; si está sugerida o pendiente, se le asigna color y estado
            if ($existente['estado'] !== 'activa') {
                $this->database->execute(
                    "UPDATE categorias SET color = ?, estado = ? WHERE nombre = ?",
                    [$color, $estado, $existente['nombre']]
                );
            }
            return $existente['nombre'];
        }

        $this->database->execute(
            "INSERT INTO categorias (nombre, color, estado) VALUES (?, ?, ?)",
            [$nombre, $color, $estado]
        );
        return $nombre;
    }

    // Borra una categoría que no está activa si ya no la usa ninguna pregunta
    // (por ejemplo, al rechazar la única pregunta que la traía).
    public function eliminarSiNoSeUsa($nombre)
    {
        return $this->database->execute(
            "DELETE FROM categorias
             WHERE nombre = ? AND estado IN ('sugerida', 'pendiente_alta')
               AND NOT EXISTS (SELECT 1 FROM pregunta WHERE pregunta.categoria = categorias.nombre)",
            [$nombre]
        );
    }

    // ---------- Validaciones (las usan Lobby y Editor) ----------

    // Saca espacios de más y deja la primera letra en mayúscula ("  astronomía " -> "Astronomía")
    public static function normalizarNombre($nombre)
    {
        $nombre = preg_replace('/\s+/u', ' ', trim((string) $nombre));
        return mb_strtoupper(mb_substr($nombre, 0, 1)) . mb_substr($nombre, 1);
    }

    // Entre 3 y 40 caracteres: letras (con tildes), números y espacios
    public static function nombreValido($nombre)
    {
        $largo = mb_strlen($nombre);
        return $largo >= 3 && $largo <= 40 && preg_match('/^[\p{L}0-9 ]+$/u', $nombre) === 1;
    }

    // Color en formato #rrggbb (lo que manda el selector de color)
    public static function colorValido($color)
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', (string) $color) === 1;
    }

    // ---------- ABM de categorías (pantalla "Ver categorías" del editor) ----------

    // Todas las categorías (menos las propuestas sin aprobar) con cuántas preguntas tiene cada una
    // $estado: si viene, solo trae las categorías en ese estado (filtro de "Ver categorías")
    public function obtenerTodasConConteo($estado = null)
    {
        $filtro = $estado ? " AND c.estado = ?" : "";
        $parametros = $estado ? [$estado] : [];

        $categorias = $this->database->queryAll(
            "SELECT c.id_categoria, c.nombre, c.color, c.estado,
                    (SELECT COUNT(*) FROM pregunta p WHERE p.categoria = c.nombre AND p.estado = 'activa') AS preguntas_activas,
                    (SELECT COUNT(*) FROM pregunta p WHERE p.categoria = c.nombre) AS preguntas_total,
                    (SELECT COUNT(*) FROM pregunta p WHERE p.categoria = c.nombre AND p.estado != 'eliminada') AS preguntas_vigentes
             FROM categorias c
             WHERE c.estado IN ('activa', 'pendiente_baja', 'pendiente_reactivacion', 'inactiva')" . $filtro . "
             ORDER BY FIELD(c.estado, 'activa', 'pendiente_baja', 'pendiente_reactivacion', 'inactiva'), c.nombre ASC",
            $parametros
        );

        foreach ($categorias as &$c) {
            $c['preguntas_activas'] = (int) $c['preguntas_activas'];
            $c['preguntas_total'] = (int) $c['preguntas_total'];
            $c['preguntas_vigentes'] = (int) $c['preguntas_vigentes'];
            $c['texto_activas'] = $c['preguntas_activas'] === 1 ? '1 pregunta activa' : $c['preguntas_activas'] . ' preguntas activas';
            $c['es_activa'] = ($c['estado'] === 'activa');
            $c['es_inactiva'] = ($c['estado'] === 'inactiva');
            $c['esta_pendiente'] = in_array($c['estado'], ['pendiente_baja', 'pendiente_reactivacion'], true);
            $nombresEstado = [
                'activa' => 'activa',
                'inactiva' => 'dada de baja',
                'pendiente_baja' => 'baja pendiente',
                'pendiente_reactivacion' => 'reactivación pendiente'
            ];
            $c['nombre_estado'] = $nombresEstado[$c['estado']] ?? $c['estado'];
            // Solo se puede dar de baja si no tiene preguntas vigentes (activas, pendientes o sugeridas).
            // Las eliminadas no cuentan: ya no están en el juego y la categoría no se borra.
            $c['puede_dar_baja'] = $c['es_activa'] && $c['preguntas_vigentes'] === 0;
        }
        unset($c);

        return $categorias;
    }

    public function obtenerPorId($id_categoria)
    {
        return $this->database->queryOne(
            "SELECT id_categoria, nombre, color, estado FROM categorias WHERE id_categoria = ?",
            [$id_categoria]
        );
    }

    // Cambia nombre y color. Como las preguntas guardan el nombre de la categoría,
    // si cambia el nombre también se actualizan las preguntas y las ediciones pendientes.
    public function actualizar($id_categoria, $nombreNuevo, $color)
    {
        $actual = $this->obtenerPorId($id_categoria);
        if (!$actual) {
            return false;
        }

        $this->database->execute(
            "UPDATE categorias SET nombre = ?, color = ? WHERE id_categoria = ?",
            [$nombreNuevo, $color, $id_categoria]
        );

        if ($actual['nombre'] !== $nombreNuevo) {
            $this->database->execute(
                "UPDATE pregunta SET categoria = ? WHERE categoria = ?",
                [$nombreNuevo, $actual['nombre']]
            );
            $this->database->execute(
                "UPDATE preguntas_ediciones_pendientes SET categoria = ? WHERE categoria = ?",
                [$nombreNuevo, $actual['nombre']]
            );
        }

        return true;
    }

    // Baja: solo si está activa y no tiene preguntas vigentes (las eliminadas no cuentan).
    // No se borra la fila: queda "inactiva".
    // $directa = true (admin): queda inactiva. false (editor): queda pendiente de baja para el admin.
    public function darDeBaja($id_categoria, $directa)
    {
        $estado = $directa ? 'inactiva' : 'pendiente_baja';
        return $this->database->execute(
            "UPDATE categorias c SET c.estado = ?
             WHERE c.id_categoria = ? AND c.estado = 'activa'
               AND NOT EXISTS (SELECT 1 FROM pregunta p WHERE p.categoria = c.nombre AND p.estado != 'eliminada')",
            [$estado, $id_categoria]
        );
    }

    // $directa = true (admin): vuelve a activa. false (editor): queda pendiente de reactivación para el admin.
    public function reactivar($id_categoria, $directa)
    {
        $estado = $directa ? 'activa' : 'pendiente_reactivacion';
        return $this->database->execute(
            "UPDATE categorias SET estado = ? WHERE id_categoria = ? AND estado = 'inactiva'",
            [$estado, $id_categoria]
        );
    }
}
