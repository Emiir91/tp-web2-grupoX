<?php

namespace app\service;

use app\model\CategoriaModel;
use app\model\PartidaModel;

class LobbyService
{
    public const CATEGORIA_INVALIDA = 'categoria_invalida';
    private const SUGERENCIA_INVALIDA = 'sugerencia_invalida';
    private const CATEGORIA_NUEVA = '__nueva__';
    private const DIFICULTADES = ['facil', 'intermedio', 'dificil'];
    private const CAMPOS_REQUERIDOS = [
        'texto_pregunta', 'categoria', 'respuesta_correcta',
        'incorrecta_1', 'incorrecta_2', 'incorrecta_3'
    ];

    private PartidaModel $partidaModel;
    private CategoriaModel $categoriaModel;

    public function __construct(PartidaModel $partidaModel, CategoriaModel $categoriaModel)
    {
        $this->partidaModel = $partidaModel;
        $this->categoriaModel = $categoriaModel;
    }

    public function obtenerDatosLobby($id_usuario): array
    {
        return [
            'partidas' => $this->partidaModel->obtenerHistorialUsuario($id_usuario),
            'puntaje_total' => $this->partidaModel->obtenerPuntajeTotalUsuario($id_usuario),
            'puntaje_mensual' => $this->partidaModel->obtenerPuntajeMensualUsuario($id_usuario),
            'categorias_animacion' => $this->categoriaModel->obtenerCategoriasJugables()
        ];
    }

    public function sortearCategoria(): ?string
    {
        $categorias = $this->categoriaModel->obtenerCategoriasJugables();

        return $categorias ? $categorias[array_rand($categorias)]['nombre'] : null;
    }

    public function obtenerCategoriasParaSugerir(): array
    {
        return $this->categoriaModel->obtenerActivas();
    }

    public function guardarSugerencia(array $formulario): ?string
    {
        $datos = [];
        foreach (self::CAMPOS_REQUERIDOS as $campo) {
            $valor = $formulario[$campo] ?? '';
            if (!is_string($valor) || trim($valor) === '') {
                return self::SUGERENCIA_INVALIDA;
            }
            $datos[$campo] = trim($valor);
        }

        $dificultad = $formulario['dificultad'] ?? 'facil';
        $datos['dificultad'] = in_array($dificultad, self::DIFICULTADES, true) ? $dificultad : 'facil';

        if ($datos['categoria'] === self::CATEGORIA_NUEVA) {
            $nombre = $formulario['categoria_nueva'] ?? '';
            if (!is_string($nombre)) {
                return self::CATEGORIA_INVALIDA;
            }

            $nombre = CategoriaModel::normalizarNombre($nombre);
            if (!CategoriaModel::nombreValido($nombre)) {
                return self::CATEGORIA_INVALIDA;
            }

            $datos['categoria'] = $this->categoriaModel->sugerir($nombre);
        } elseif (!in_array($datos['categoria'], $this->categoriaModel->obtenerNombresActivos(), true)) {
            return self::SUGERENCIA_INVALIDA;
        }

        return $this->partidaModel->crearSugerencia($datos) ? null : self::SUGERENCIA_INVALIDA;
    }
}
