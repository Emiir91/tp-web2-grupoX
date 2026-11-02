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
        $id_partida = $this->partidaModel->iniciarPartida($id_usuario);

        if (!$id_partida) {
            header("Location: " . BASE_URL . "/home/index");
            exit();
        }

        $_SESSION['id_partida'] = $id_partida;

        header("Location: " . BASE_URL . "/juego/mostrar");
        exit();
    }

    public function mostrar()
    {
        if (!isset($_SESSION['id_partida'])) {
            header("Location: " . BASE_URL . "/home/index");
            exit();
        }

        $id_partida = $_SESSION['id_partida'];

        if (!$this->partidaModel->verificarPartidaActiva($id_partida)) {
            header("Location: " . BASE_URL . "/juego/resultados");
            exit();
        }

        if (isset($_SESSION['id_pregunta_actual']) && isset($_SESSION['tiempo_inicio_pregunta'])) {
            $tiempo_transcurrido = time() - $_SESSION['tiempo_inicio_pregunta'];
            $tiempo_restante = 10 - $tiempo_transcurrido;

            if ($tiempo_restante <= 0) {
                $this->limpiarSesionPregunta();
                $this->partidaModel->finalizarPartida($id_partida, 'tiempo_agotado');
                header("Location: " . BASE_URL . "/juego/resultados");
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
                header("Location: " . BASE_URL . "/home/index");
                exit();
            }

            $_SESSION['id_pregunta_actual'] = $pregunta['id_pregunta'];
            $_SESSION['tiempo_inicio_pregunta'] = time();

            $tiempo_restante = 10;
        }

        $respuestas = $this->partidaModel->obtenerRespuestasDePregunta($pregunta['id_pregunta']);

        $datosParaVista = [
            'pregunta' => $pregunta['texto_pregunta'],
            'categoria' => $pregunta['categoria'],
            'respuestas' => $respuestas,
            'id_partida' => $id_partida,
            'tiempo_restante' => $tiempo_restante,
            'ocultar_layout' => true
        ];

        $this->renderer->renderiza('juego', $datosParaVista);
    }

    public function procesarRespuesta()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/home/index");
            exit();
        }

        $id_respuesta = $_POST['id_respuesta'] ?? null;

        if (!$id_respuesta || !isset($_SESSION['id_partida'])) {
            header("Location: " . BASE_URL . "/home/index");
            exit();
        }

        $id_partida = $_SESSION['id_partida'];

        $this->limpiarSesionPregunta();

        if (!$this->partidaModel->verificarPartidaActiva($id_partida)) {
            header("Location: " . BASE_URL . "/juego/resultados");
            exit();
        }

        $tiempo_restante = $this->partidaModel->validarTiempo($id_partida);

        if ($tiempo_restante <= 0) {
            $this->partidaModel->finalizarPartida($id_partida, 'tiempo_agotado');
            header("Location: " . BASE_URL . "/juego/resultados");
            exit();
        }

        $es_correcta = $this->partidaModel->validarRespuesta($id_respuesta);

        if ($es_correcta) {
            $nuevo_puntaje = $this->partidaModel->obtenerPuntajeActual($id_partida) + 10;
            $nueva_racha = $this->partidaModel->obtenerRachaActual($id_partida) + 1;

            $this->partidaModel->registrarRespuestaCorrecta($id_partida, $nuevo_puntaje, $nueva_racha);

            header("Location: " . BASE_URL . "/juego/mostrar");
            exit();
        } else {
            $this->partidaModel->finalizarPartida($id_partida, 'finalizada');
            header("Location: " . BASE_URL . "/juego/resultados");
            exit();
        }
    }

    public function tiempoAgotado()
    {
        if (!isset($_SESSION['id_partida'])) {
            header("Location: " . BASE_URL . "/home/index");
            exit();
        }

        $id_partida = $_SESSION['id_partida'];

        $this->limpiarSesionPregunta();
        $this->partidaModel->finalizarPartida($id_partida, 'tiempo_agotado');

        header("Location: " . BASE_URL . "/juego/resultados");
        exit();
    }

    public function abandonar()
    {
        if (!isset($_SESSION['id_partida'])) {
            header("Location: " . BASE_URL . "/home/index");
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
            header("Location: " . BASE_URL . "/home/index");
            exit();
        }

        $id_partida = $_SESSION['id_partida'];
        $datos_partida = $this->partidaModel->obtenerDatosPartida($id_partida);

        if (!$datos_partida) {
            unset($_SESSION['id_partida']);
            header("Location: " . BASE_URL . "/home/index");
            exit();
        }

        $mensaje = $this->generarMensajeSegunPuntaje($datos_partida['puntaje']);

        $datosParaVista = [
            'puntaje' => $datos_partida['puntaje'],
            'racha' => $datos_partida['racha'],
            'estado' => $datos_partida['estado'],
            'mensaje' => $mensaje,
            'ocultar_layout' => true
        ];

        unset($_SESSION['id_partida']);
        $this->limpiarSesionPregunta();

        $this->renderer->renderiza('resultados', $datosParaVista);
    }

    private function generarMensajeSegunPuntaje($puntaje)
    {
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