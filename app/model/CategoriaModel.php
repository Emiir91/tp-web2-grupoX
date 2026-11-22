<?php

namespace app\model;

// Categorías de las preguntas (nombre y color). Estados:
//   activa         -> se usa en el juego y en los formularios
//   sugerida       -> la propuso un jugador junto con una pregunta (la revisa el editor)
//   pendiente_alta -> la aprobó o creó un editor (la revisa el admin)
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
            "SELECT nombre, color, estado FROM categorias WHERE nombre = ?",
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
             WHERE nombre = ? AND estado != 'activa'
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
}
