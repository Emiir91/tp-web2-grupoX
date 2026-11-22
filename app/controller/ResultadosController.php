<?php

namespace app\controller;

class ResultadosController
{
    private $partidaModel;
    private $renderer;
    private $categoriaModel;

    // Motivos rápidos que puede elegir el jugador al reportar una pregunta
    private const TIPOS_REPORTE = ['respuesta_incorrecta', 'categoria_incorrecta', 'mal_redactada', 'otro'];

    public function __construct($partidaModel, $renderer, $categoriaModel)
    {
        $this->partidaModel = $partidaModel;
        $this->renderer = $renderer;
        $this->categoriaModel = $categoriaModel;
    }

    public function index()
    {
        if (!isset($_SESSION['id_partida_resultados'])) {
            header("Location: " . BASE_URL . "/lobby");
            exit();
        }

        $id_partida = $_SESSION['id_partida_resultados'];
        $datos_partida = $this->partidaModel->obtenerDatosPartida($id_partida);

        if (!$datos_partida) {
            unset($_SESSION['id_partida_resultados']);
            header("Location: " . BASE_URL . "/lobby");
            exit();
        }

        $preguntas = $this->partidaModel->obtenerPreguntasRespondidas($id_partida);

        // Color de la categoría de cada pregunta (el mismo que en el juego)
        $colores = $this->categoriaModel->obtenerMapaColores();
        foreach ($preguntas as &$p) {
            $p['color_categoria'] = $colores[$p['categoria']] ?? \app\model\CategoriaModel::COLOR_POR_DEFECTO;
        }
        unset($p);
        $mensaje = $this->generarMensajeSegunPuntaje($datos_partida['puntaje']);

        $datosParaVista = [
            'puntaje' => $datos_partida['puntaje'],
            'racha' => $datos_partida['racha'],
            'estado' => $datos_partida['estado'],
            'mensaje' => $mensaje,
            'preguntas_partida' => $preguntas,
            'ocultar_layout' => true
        ];

        unset($_SESSION['id_partida_resultados']);

        $this->renderer->renderiza('resultados', $datosParaVista);
    }

    public function reportarPregunta()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_SESSION['id_usuario'])) {
            echo json_encode(['success' => false, 'mensaje' => 'Sesión no válida']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $id_pregunta = $data['id_pregunta'] ?? null;
        $tipo = $data['tipo'] ?? 'otro';
        $motivo = trim($data['motivo'] ?? '');

        if (!in_array($tipo, self::TIPOS_REPORTE, true)) {
            $tipo = 'otro';
        }

        // El detalle es obligatorio solo si el motivo es "Otro"
        if (!$id_pregunta || ($tipo === 'otro' && $motivo === '')) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
            return;
        }

        $fecha = date('Y-m-d H:i:s');
        $this->partidaModel->registrarReporte($id_pregunta, $_SESSION['id_usuario'], $motivo, $fecha, $tipo);

        echo json_encode(['success' => true]);
    }

    private function generarMensajeSegunPuntaje($puntaje)
    {
        if ($puntaje >= 150) return "¡Impresionante! Dominas todas las categorías al máximo.";
        if ($puntaje >= 100) return "¡Excelente! Eres un verdadero experto en Triviador.";
        if ($puntaje >= 50) return "¡Muy bien! Tienes buenos conocimientos.";
        if ($puntaje >= 20) return "¡Buen intento! Sigue practicando.";
        return "¡No te rindas! La próxima vez lo harás mejor.";
    }
}
