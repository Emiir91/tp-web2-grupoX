<?php

namespace app\service;

class PartidaService
{
    public const DURACION_TURNO = 10;
    public const SIN_PREGUNTAS = 'sin_preguntas';
    public const ASIGNACION_FALLIDA = 'asignacion_fallida';
    public const PREGUNTA_YA_RESPONDIDA = 'pregunta_ya_respondida';
    public const RESPUESTA_INVALIDA = 'respuesta_invalida';
    public const PARTIDA_CERRADA = 'partida_cerrada';
    public const TIEMPO_AGOTADO = 'tiempo_agotado';
    public const RESPUESTA_REGISTRADA = 'respuesta_registrada';

    private $partidaModel;

    public function __construct($partidaModel)
    {
        $this->partidaModel = $partidaModel;
    }

    public function iniciarORecuperar($id_usuario, $modo_juego, $esTematica, $categoria)
    {
        $enCurso = $this->partidaModel->obtenerPartidaEnCurso($id_usuario);

        if ($enCurso) {
            return ['en_curso' => $enCurso];
        }

        if ($esTematica && !$categoria) {
            return null;
        }

        $id_partida = $this->partidaModel->iniciarPartida($id_usuario, $modo_juego);

        return $id_partida ? ['id_nueva' => $id_partida] : null;
    }

    public function prepararSiguientePregunta($id_partida, $categoria)
    {
        $pregunta = $this->partidaModel->obtenerPreguntaAleatoria($id_partida, $categoria);

        if (!$pregunta) {
            $this->partidaModel->finalizarPartida($id_partida, 'finalizada');
            return self::SIN_PREGUNTAS;
        }

        if (!$this->partidaModel->asignarPreguntaAPartida($id_partida, $pregunta['id_pregunta'])) {
            return self::ASIGNACION_FALLIDA;
        }

        return $pregunta;
    }

    public function tiempoRestanteDesde($tiempoInicio)
    {
        return self::DURACION_TURNO - (time() - $tiempoInicio);
    }

    public function resolverRespuesta($id_partida, $id_pregunta, $id_respuesta)
    {
        if ($this->partidaModel->preguntaYaRespondida($id_partida, $id_pregunta)) {
            return self::PREGUNTA_YA_RESPONDIDA;
        }

        if (!$this->partidaModel->respuestaPerteneceAPregunta($id_respuesta, $id_pregunta)) {
            return self::RESPUESTA_INVALIDA;
        }

        if (!$this->partidaModel->verificarPartidaActiva($id_partida)) {
            return self::PARTIDA_CERRADA;
        }

        if ($this->registrarVencimientoSiCorresponde($id_partida, $id_pregunta)) {
            return self::TIEMPO_AGOTADO;
        }

        $esCorrecta = $this->partidaModel->validarRespuesta($id_respuesta);
        $this->partidaModel->registrarRespuestaPartida(
            $id_partida,
            $id_pregunta,
            $id_respuesta,
            $esCorrecta ? 1 : 0
        );

        if ($esCorrecta) {
            $this->registrarAcierto($id_partida);
        } elseif (!$this->descontarVidaYFinalizarSiCorresponde($id_partida)) {
            $puntaje = $this->partidaModel->obtenerPuntajeActual($id_partida);
            $this->partidaModel->registrarRespuestaCorrecta($id_partida, $puntaje, 0);
        }

        return self::RESPUESTA_REGISTRADA;
    }

    public function registrarVencimientoSiCorresponde($id_partida, $id_pregunta, $tiempoRestante = null)
    {
        $tiempoRestante = $tiempoRestante ?? $this->partidaModel->validarTiempo($id_partida);

        if ($tiempoRestante > 0) {
            return false;
        }

        $this->partidaModel->registrarRespuestaPartida($id_partida, $id_pregunta, null, 0);
        $this->descontarVidaYFinalizarSiCorresponde($id_partida);

        return true;
    }

    public function abandonarPartida($id_partida)
    {
        $this->partidaModel->finalizarPartida($id_partida, 'abandonada');
    }

    private function registrarAcierto($id_partida)
    {
        $partida = $this->partidaModel->obtenerDatosPartida($id_partida);
        $nivel = $partida['nivel_actual'] ?? 'facil';
        $puntosPorNivel = ['facil' => 10, 'intermedio' => 15, 'dificil' => 20];

        $puntaje = $this->partidaModel->obtenerPuntajeActual($id_partida) + ($puntosPorNivel[$nivel] ?? 10);
        $racha = $this->partidaModel->obtenerRachaActual($id_partida) + 1;

        $this->partidaModel->registrarRespuestaCorrecta($id_partida, $puntaje, $racha);
    }

    private function descontarVidaYFinalizarSiCorresponde($id_partida)
    {
        $this->partidaModel->restarVida($id_partida);

        if ($this->partidaModel->obtenerVidasActuales($id_partida) > 0) {
            return false;
        }

        $this->partidaModel->finalizarPartida($id_partida, 'sin_vidas');
        return true;
    }
}
