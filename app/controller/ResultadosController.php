<?php

namespace app\controller;

use app\model\CategoriaModel;
use app\service\ResultadosService;

class ResultadosController
{
    private $renderer;
    private ResultadosService $resultadosService;

    public function __construct(ResultadosService $resultadosService, $renderer)
    {
        $this->resultadosService = $resultadosService;
        $this->renderer = $renderer;
    }

    public function index()
    {
        if (!isset($_SESSION['id_partida_resultados'])) {
            header("Location: " . BASE_URL . "/lobby");
            exit();
        }

        $idPartida = (int) $_SESSION['id_partida_resultados'];
        $resultados = $this->resultadosService->obtenerResultados($idPartida);
        unset($_SESSION['id_partida_resultados']);

        if ($resultados === null) {
            header("Location: " . BASE_URL . "/lobby");
            exit();
        }

        $rondaAnterior = $_SESSION['ronda_categoria'] ?? [];
        $datosParaVista = $this->prepararVista($resultados);

        if (($rondaAnterior['id_partida'] ?? null) === $idPartida) {
            $datosParaVista['id_partida_anterior'] = $idPartida;
            $datosParaVista['categoria_anterior'] = $rondaAnterior['categoria'] ?? null;
        }

        $this->renderer->renderiza('resultados', $datosParaVista);
    }

    private function prepararVista(array $resultados): array
    {
        $datos_partida = $resultados['partida'];
        $preguntas = $resultados['preguntas'];
        $colores = $resultados['colores'];

        foreach ($preguntas as $indice => $pregunta) {
            $preguntas[$indice]['color_categoria'] = $colores[$pregunta['categoria']]
                ?? CategoriaModel::COLOR_POR_DEFECTO;
        }

        $estado = $datos_partida['estado'];

        if ($estado === 'finalizada') {
            $estado_visible = 'Victoria';
            $mensaje = '¡Completaste la ronda! ' .
                $this->generarMensajeSegunPuntaje($datos_partida['puntaje']);

        } elseif ($estado === 'sin_vidas') {
            $estado_visible = 'Derrota';
            $mensaje = 'Te quedaste sin vidas. ¡Podés volver a intentarlo!';

        } elseif ($estado === 'abandonada') {
            $estado_visible = 'Abandonada';
            $mensaje = 'Abandonaste la partida.';

        } else {
            $estado_visible = 'Finalizada';
            $mensaje = 'La partida terminó.';
        }

        // "Nueva partida" repite el modo de la partida recién jugada.
        // "Cambiar modo" abre una ventanita con los 4 modos (el actual queda marcado).
        $nombresModos = ['progresivo' => '⚡ Progresivo', 'facil' => '🟢 Fácil', 'intermedio' => '🟡 Intermedio', 'dificil' => '🔴 Difícil'];
        $modoActual = $resultados['modo_juego'];
        if (!isset($nombresModos[$modoActual])) {
            $modoActual = 'progresivo';
        }
        $modos = [];
        foreach ($nombresModos as $valor => $texto) {
            $modos[] = ['valor' => $valor, 'texto' => $texto, 'activo' => ($valor === $modoActual)];
        }

        return [
            'modo_juego' => $modoActual,
            'nombre_modo' => $nombresModos[$modoActual],
            'modos' => $modos,
            'puntaje' => $datos_partida['puntaje'],
            'racha' => $datos_partida['racha'],
            'estado' => $estado_visible,
            // Para el sonido del final: victoria, derrota o nada (abandonada ya sonó el apagón)
            'sonido_final' => $estado === 'finalizada' ? 'victoria' : ($estado === 'sin_vidas' ? 'derrota' : ''),
            'mensaje' => $mensaje,
            'preguntas_partida' => $preguntas,
            'ocultar_layout' => true
        ];
    }

    public function reportarPregunta()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_SESSION['id_usuario'])) {
            echo json_encode(['success' => false, 'mensaje' => 'Sesión no válida']);
            return;
        }

        $datos = json_decode(file_get_contents('php://input'), true);
        $error = $this->resultadosService->reportarPregunta(
            $_SESSION['id_usuario'],
            is_array($datos) ? $datos : []
        );

        echo json_encode($error === null
            ? ['success' => true]
            : ['success' => false, 'mensaje' => $error]);
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
