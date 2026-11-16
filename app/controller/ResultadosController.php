<?php

namespace app\controller;

class ResultadosController
{
    private $partidaModel;
    private $renderer;

    public function __construct($partidaModel, $renderer)
    {
        $this->partidaModel = $partidaModel;
        $this->renderer = $renderer;
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
        $motivo = trim($data['motivo'] ?? '');

        if (!$id_pregunta || $motivo === '') {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
            return;
        }

        $fecha = date('Y-m-d H:i:s');
        $this->partidaModel->registrarReporte($id_pregunta, $_SESSION['id_usuario'], $motivo, $fecha);

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
