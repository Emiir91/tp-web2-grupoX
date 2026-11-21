<?php

namespace app\controller;

class JuegoController
{
    private $partidaModel;
    private $renderer;

    public function __construct($partidaModel, $renderer)
    {
        $this->partidaModel = $partidaModel;
        $this->renderer = $renderer;

        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    }

    public function iniciar()
    {
        if (!isset($_SESSION['id_usuario'])) {
            header("Location: " . BASE_URL . "/login/index");
            exit();
        }

        $id_usuario = $_SESSION['id_usuario'];
        $modo_juego = $_POST['modo_juego'] ?? 'progresivo';

        $id_partida = $this->partidaModel->iniciarPartida($id_usuario, $modo_juego);

        if (!$id_partida) {
            header("Location: " . BASE_URL . "/lobby");
            exit();
        }

        $_SESSION['id_partida'] = $id_partida;

        header("Location: " . BASE_URL . "/juego/mostrar");
        exit();
    }

    public function mostrar()
    {
        if (!isset($_SESSION['id_partida'])) {
            header("Location: " . BASE_URL . "/lobby");
            exit();
        }

        $id_partida = $_SESSION['id_partida'];

        if (!$this->partidaModel->verificarPartidaActiva($id_partida)) {
            header("Location: " . BASE_URL . "/juego/resultados");
            exit();
        }

        $vidas_actuales = $this->partidaModel->obtenerVidasActuales($id_partida);

        if (isset($_SESSION['id_pregunta_actual']) && isset($_SESSION['tiempo_inicio_pregunta'])) {
            $tiempo_transcurrido = time() - $_SESSION['tiempo_inicio_pregunta'];
            $tiempo_restante = 10 - $tiempo_transcurrido;

            if ($tiempo_restante <= 0) {
                $id_pregunta_actual = $_SESSION['id_pregunta_actual'];
                $this->limpiarSesionPregunta();
                $this->partidaModel->registrarRespuestaPartida($id_partida, $id_pregunta_actual, null, 0);
                $this->partidaModel->restarVida($id_partida);
                $vidas_restantes = $this->partidaModel->obtenerVidasActuales($id_partida);

                if ($vidas_restantes <= 0) {
                    $this->partidaModel->finalizarPartida($id_partida, 'sin_vidas');
                    header("Location: " . BASE_URL . "/juego/resultados");
                    exit();
                }

                header("Location: " . BASE_URL . "/juego/mostrar");
                exit();
            }

            $pregunta = $this->partidaModel->obtenerPreguntaPorId($_SESSION['id_pregunta_actual']);
        } else {
            $pregunta = $this->partidaModel->obtenerPreguntaAleatoria($id_partida);

            if (!$pregunta) {
                $this->partidaModel->finalizarPartida($id_partida, 'finalizada');
                header("Location: " . BASE_URL . "/juego/resultados");
                exit();
            }

            $asignada = $this->partidaModel->asignarPreguntaAPartida($id_partida, $pregunta['id_pregunta']);

            if (!$asignada) {
                header("Location: " . BASE_URL . "/lobby");
                exit();
            }

            $_SESSION['id_pregunta_actual'] = $pregunta['id_pregunta'];
            $_SESSION['tiempo_inicio_pregunta'] = time();

            $tiempo_restante = 10;
        }

        $respuestas = $this->partidaModel->obtenerRespuestasDePregunta($pregunta['id_pregunta']);

        $vidas_array = array_fill(0, $vidas_actuales, '❤️');

        $estiloCategoria = \EstiloCategoria::obtener($pregunta['categoria']);

        $datosParaVista = [
            'pregunta' => $pregunta['texto_pregunta'],
            'categoria' => $pregunta['categoria'],
            'color_categoria' => $estiloCategoria['color'],
            'icono_categoria' => $estiloCategoria['icono'],
            'respuestas' => $respuestas,
            'id_partida' => $id_partida,
            'tiempo_restante' => $tiempo_restante,
            'vidas' => $vidas_array,
            'ocultar_layout' => true
        ];

        $this->renderer->renderiza('juego', $datosParaVista);
    }

    public function procesarRespuesta()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/lobby");
            exit();
        }

        $id_respuesta = $_POST['id_respuesta'] ?? null;

        if (!$id_respuesta || !isset($_SESSION['id_partida'])) {
            header("Location: " . BASE_URL . "/lobby");
            exit();
        }

        $id_partida = $_SESSION['id_partida'];
        $id_pregunta_actual = $_SESSION['id_pregunta_actual'] ?? null;

        $this->limpiarSesionPregunta();

        if (!$this->partidaModel->verificarPartidaActiva($id_partida)) {
            header("Location: " . BASE_URL . "/juego/resultados");
            exit();
        }

        $tiempo_restante = $this->partidaModel->validarTiempo($id_partida);

        if ($tiempo_restante <= 0) {
            if ($id_pregunta_actual) {
                $this->partidaModel->registrarRespuestaPartida($id_partida, $id_pregunta_actual, null, 0);
            }
            $this->partidaModel->restarVida($id_partida);
            $vidas_restantes = $this->partidaModel->obtenerVidasActuales($id_partida);

            if ($vidas_restantes <= 0) {
                $this->partidaModel->finalizarPartida($id_partida, 'sin_vidas');
                header("Location: " . BASE_URL . "/juego/resultados");
                exit();
            }

            header("Location: " . BASE_URL . "/juego/mostrar");
            exit();
        }

        $es_correcta = $this->partidaModel->validarRespuesta($id_respuesta);

        if ($id_pregunta_actual) {
            $this->partidaModel->registrarRespuestaPartida($id_partida, $id_pregunta_actual, $id_respuesta, $es_correcta ? 1 : 0);
        }

        if ($es_correcta) {
            $datos_partida = $this->partidaModel->obtenerDatosPartida($id_partida);
            $nivel_actual = $datos_partida['nivel_actual'] ?? 'facil';

            $puntos_a_sumar = 10;
            if ($nivel_actual === 'intermedio') {
                $puntos_a_sumar = 15;
            } elseif ($nivel_actual === 'dificil') {
                $puntos_a_sumar = 20;
            }

            $nuevo_puntaje = $this->partidaModel->obtenerPuntajeActual($id_partida) + $puntos_a_sumar;
            $nueva_racha = $this->partidaModel->obtenerRachaActual($id_partida) + 1;

            $this->partidaModel->registrarRespuestaCorrecta($id_partida, $nuevo_puntaje, $nueva_racha);
        } else {
            $this->partidaModel->restarVida($id_partida);
            $vidas_restantes = $this->partidaModel->obtenerVidasActuales($id_partida);

            if ($vidas_restantes <= 0) {
                $this->partidaModel->finalizarPartida($id_partida, 'sin_vidas');
                header("Location: " . BASE_URL . "/juego/resultados");
                exit();
            }

            $nuevo_puntaje = $this->partidaModel->obtenerPuntajeActual($id_partida);
            $this->partidaModel->registrarRespuestaCorrecta($id_partida, $nuevo_puntaje, 0);
        }

        header("Location: " . BASE_URL . "/juego/mostrar");
        exit();
    }

    public function tiempoAgotado()
    {
        if (!isset($_SESSION['id_partida'])) {
            header("Location: " . BASE_URL . "/lobby");
            exit();
        }

        $id_partida = $_SESSION['id_partida'];
        $id_pregunta_actual = $_SESSION['id_pregunta_actual'] ?? null;

        // Si no hay una pregunta pendiente es porque ya se respondió (por ejemplo,
        // se eligió una respuesta en el último segundo). No se resta otra vida.
        if (!$id_pregunta_actual) {
            header("Location: " . BASE_URL . "/juego/mostrar");
            exit();
        }

        $this->limpiarSesionPregunta();

        $this->partidaModel->registrarRespuestaPartida($id_partida, $id_pregunta_actual, null, 0);

        $this->partidaModel->restarVida($id_partida);
        $vidas_restantes = $this->partidaModel->obtenerVidasActuales($id_partida);

        if ($vidas_restantes <= 0) {
            $this->partidaModel->finalizarPartida($id_partida, 'sin_vidas');
            header("Location: " . BASE_URL . "/juego/resultados");
            exit();
        }

        header("Location: " . BASE_URL . "/juego/mostrar");
        exit();
    }

    public function abandonar()
    {
        if (!isset($_SESSION['id_partida'])) {
            header("Location: " . BASE_URL . "/lobby");
            exit();
        }

        $id_partida = $_SESSION['id_partida'];

        $this->limpiarSesionPregunta();

        $this->partidaModel->finalizarPartida($id_partida, 'abandonada');

        header("Location: " . BASE_URL . "/juego/resultados");
        exit();
    }

    public function resultados()
    {
        if (!isset($_SESSION['id_partida'])) {
            header("Location: " . BASE_URL . "/lobby");
            exit();
        }

        $id_partida = $_SESSION['id_partida'];
        $datos_partida = $this->partidaModel->obtenerDatosPartida($id_partida);

        if (!$datos_partida) {
            unset($_SESSION['id_partida']);
            header("Location: " . BASE_URL . "/lobby");
            exit();
        }

        $_SESSION['id_partida_resultados'] = $id_partida;
        unset($_SESSION['id_partida']);
        $this->limpiarSesionPregunta();

        header("Location: " . BASE_URL . "/resultados/index");
        exit();
    }

    private function generarMensajeSegunPuntaje($puntaje)
    {
        if ($puntaje >= 150) return "¡Impresionante! Dominas todas las categorías al máximo.";
        if ($puntaje >= 100) return "¡Excelente! Eres un verdadero experto en Triviador.";
        if ($puntaje >= 50) return "¡Muy bien! Tienes buenos conocimientos.";
        if ($puntaje >= 20) return "¡Buen intento! Sigue practicando.";
        return "¡No te rindas! La próxima vez lo harás mejor.";
    }

    private function limpiarSesionPregunta()
    {
        unset($_SESSION['id_pregunta_actual']);
        unset($_SESSION['tiempo_inicio_pregunta']);
    }
}
