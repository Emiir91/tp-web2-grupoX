<?php

namespace app\service;

use app\model\CategoriaModel;
use app\model\PartidaModel;

class ResultadosService
{
    private const TIPOS_REPORTE = ['respuesta_incorrecta', 'categoria_incorrecta', 'mal_redactada', 'otro'];

    private PartidaModel $partidaModel;
    private CategoriaModel $categoriaModel;

    public function __construct(PartidaModel $partidaModel, CategoriaModel $categoriaModel)
    {
        $this->partidaModel = $partidaModel;
        $this->categoriaModel = $categoriaModel;
    }

    public function obtenerResultados($id_partida): ?array
    {
        $partida = $this->partidaModel->obtenerDatosPartida($id_partida);

        if (!$partida) {
            return null;
        }

        $preguntas = $this->partidaModel->obtenerPreguntasRespondidas($id_partida);
        $colores = $this->categoriaModel->obtenerMapaColores();
        $modo = $this->partidaModel->obtenerModoYNivel($id_partida);

        return [
            'partida' => $partida,
            'preguntas' => $preguntas,
            'colores' => $colores,
            'modo_juego' => $modo['modo_juego'] ?? 'progresivo'
        ];
    }

    public function reportarPregunta($id_usuario, array $datos): ?string
    {
        $id_pregunta = filter_var($datos['id_pregunta'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1]
        ]);
        $tipo = $datos['tipo'] ?? 'otro';
        $motivo = $datos['motivo'] ?? '';
        $motivo = is_string($motivo) ? trim($motivo) : '';

        if (!in_array($tipo, self::TIPOS_REPORTE, true)) {
            $tipo = 'otro';
        }

        if ($id_pregunta === false || ($tipo === 'otro' && $motivo === '')) {
            return 'Datos incompletos';
        }

        $guardado = $this->partidaModel->registrarReporte(
            $id_pregunta,
            $id_usuario,
            $motivo,
            date('Y-m-d H:i:s'),
            $tipo
        );

        return $guardado ? null : 'No se pudo guardar el reporte.';
    }
}
