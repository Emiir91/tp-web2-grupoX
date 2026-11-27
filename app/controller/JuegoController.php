<?php

namespace app\controller;

class JuegoController
{
    private $partidaModel;
    private $renderer;
    private $categoriaModel;

    public function __construct($partidaModel, $renderer, $categoriaModel)
    {
        $this->partidaModel = $partidaModel;
        $this->renderer = $renderer;
        $this->categoriaModel = $categoriaModel;

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

        // Grilla 4.5: si ya tiene una partida en curso (otra pestaña, F5, cerró el navegador...),
        // se retoma esa en lugar de crear otra, con la misma pregunta y el tiempo que le quedaba.
        $enCurso = $this->partidaModel->obtenerPartidaEnCurso($id_usuario);
        if ($enCurso) {
            $this->retomarPartida($enCurso);
            header("Location: " . BASE_URL . "/juego/mostrar");
            exit();
        }

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
                }

                $this->irAFeedback($id_partida, $id_pregunta_actual, null);
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
            $preguntaNueva = true;
        }

        $respuestas = $this->partidaModel->obtenerRespuestasDePregunta($pregunta['id_pregunta']);
        // Se guarda el orden en que se mostraron, para que el feedback las muestre en el mismo lugar
        $_SESSION['orden_respuestas'] = array_column($respuestas, 'id_respuesta');

        $vidas_array = array_fill(0, $vidas_actuales, '❤️');

        // Color de la categoría (guardado en la tabla categorias)
        $colorCategoria = $this->categoriaModel->obtenerColor($pregunta['categoria']);

        $datosParaVista = [
            'nivel' => $this->datosNivel($id_partida, !empty($preguntaNueva)),
            'pregunta' => $pregunta['texto_pregunta'],
            'categoria' => $pregunta['categoria'],
            'color_categoria' => $colorCategoria,
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

        // Grilla 4.6: si no hay una pregunta pendiente (ya se respondió) o esa pregunta ya figura
        // como respondida en la partida, es un reenvío: no se procesa ni suma nada.
        if (!$id_pregunta_actual || $this->partidaModel->preguntaYaRespondida($id_partida, $id_pregunta_actual)) {
            $this->limpiarSesionPregunta();
            header("Location: " . BASE_URL . "/juego/mostrar");
            exit();
        }

        // Grilla 4.4: la respuesta tiene que ser una de las opciones de la pregunta que se mostró.
        // Si no lo es (id cambiado a mano), se ignora y la pregunta sigue pendiente.
        if (!$this->partidaModel->respuestaPerteneceAPregunta($id_respuesta, $id_pregunta_actual)) {
            header("Location: " . BASE_URL . "/juego/mostrar");
            exit();
        }

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
            }

            $this->irAFeedback($id_partida, $id_pregunta_actual, null);
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
            } else {
                $nuevo_puntaje = $this->partidaModel->obtenerPuntajeActual($id_partida);
                $this->partidaModel->registrarRespuestaCorrecta($id_partida, $nuevo_puntaje, 0);
            }
        }

        $this->irAFeedback($id_partida, $id_pregunta_actual, $id_respuesta);
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
        }

        $this->irAFeedback($id_partida, $id_pregunta_actual, null);
    }

    // Pantalla de feedback: muestra la pregunta recién respondida con la correcta en verde
    // y la elegida en rojo (si estaba mal). Se muestra una sola vez y después sigue sola.
    // Anti-cheat: solo existe después de que el servidor procesó la respuesta y cerró el turno.
    public function feedback()
    {
        $datos = $_SESSION['feedback'] ?? null;
        unset($_SESSION['feedback']);

        if (!$datos || ($datos['id_partida'] ?? null) != ($_SESSION['id_partida'] ?? null)) {
            header("Location: " . BASE_URL . "/juego/mostrar");
            exit();
        }

        $pregunta = $this->partidaModel->obtenerPreguntaPorId($datos['id_pregunta']);
        if (!$pregunta) {
            header("Location: " . BASE_URL . "/juego/mostrar");
            exit();
        }

        // Mismo orden en que se mostraron las respuestas
        $respuestas = $this->partidaModel->obtenerRespuestasDePregunta($datos['id_pregunta']);
        $orden = array_flip($datos['orden'] ?? []);
        usort($respuestas, function ($a, $b) use ($orden) {
            return ($orden[$a['id_respuesta']] ?? 99) <=> ($orden[$b['id_respuesta']] ?? 99);
        });

        $acerto = false;
        foreach ($respuestas as &$r) {
            $r['es_la_correcta'] = ((int) $r['es_correcta'] === 1);
            $r['es_la_elegida'] = ((string) $r['id_respuesta'] === (string) $datos['id_elegida']);
            $r['es_error'] = $r['es_la_elegida'] && !$r['es_la_correcta'];
            if ($r['es_la_elegida'] && $r['es_la_correcta']) {
                $acerto = true;
            }
        }
        unset($r);

        if ($acerto) {
            $mensaje = '✓ ¡Correcto!';
        } elseif ($datos['id_elegida'] === null) {
            $mensaje = '⏱ ¡Se acabó el tiempo!';
        } else {
            $mensaje = '✗ Incorrecto';
        }

        $vidas = $this->partidaModel->obtenerVidasActuales($datos['id_partida']);

        $this->renderer->renderiza('juego', [
            'es_feedback' => true,
            'acerto' => $acerto,
            'mensaje_feedback' => $mensaje,
            'nivel' => $this->datosNivel($datos['id_partida'], false),
            'pregunta' => $pregunta['texto_pregunta'],
            'categoria' => $pregunta['categoria'],
            'color_categoria' => $this->categoriaModel->obtenerColor($pregunta['categoria']),
            'respuestas' => $respuestas,
            'vidas' => array_fill(0, max(0, (int) $vidas), '❤️'),
            'ocultar_layout' => true
        ]);
    }

    // Guarda en sesión el resultado de la pregunta recién respondida y va a la pantalla de feedback
    private function irAFeedback($id_partida, $id_pregunta, $id_elegida)
    {
        $_SESSION['feedback'] = [
            'id_partida' => $id_partida,
            'id_pregunta' => $id_pregunta,
            'id_elegida' => $id_elegida,
            'orden' => $_SESSION['orden_respuestas'] ?? []
        ];
        header("Location: " . BASE_URL . "/juego/feedback");
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

    // Deja la sesión apuntando a la partida en curso. Si la pregunta que se le estaba mostrando
    // todavía no se respondió, se recupera con su tiempo original (no se reinicia).
    private function retomarPartida($enCurso)
    {
        $mismaPartida = (($_SESSION['id_partida'] ?? null) == $enCurso['id_partida']);
        $_SESSION['id_partida'] = $enCurso['id_partida'];

        // Misma pestaña/sesión: la sesión ya tiene la pregunta y su tiempo, no se toca
        if ($mismaPartida && isset($_SESSION['id_pregunta_actual'])) {
            return;
        }

        $this->limpiarSesionPregunta();

        if ($enCurso['ultima_pregunta_id'] && !$enCurso['ya_respondida'] && $enCurso['segundos_transcurridos'] !== null) {
            $_SESSION['id_pregunta_actual'] = $enCurso['ultima_pregunta_id'];
            // Se arma la hora de inicio a partir de los segundos que ya pasaron, así el tiempo sigue corriendo
            $_SESSION['tiempo_inicio_pregunta'] = time() - (int) $enCurso['segundos_transcurridos'];
        }
    }

    // Distintivo de nivel para el modo progresivo (solo visual: el cambio de nivel lo decide el modelo).
    // Devuelve null en los otros modos. 'subio_nivel' es true solo en la primera pregunta de un nivel nuevo.
    private function datosNivel($id_partida, $preguntaNueva)
    {
        $partida = $this->partidaModel->obtenerModoYNivel($id_partida);
        if (!$partida || $partida['modo_juego'] !== 'progresivo') {
            return null;
        }

        $niveles = [
            'facil' => ['numero' => 1, 'nombre' => 'Fácil', 'icono' => '🟢'],
            'intermedio' => ['numero' => 2, 'nombre' => 'Medio', 'icono' => '🟡'],
            'dificil' => ['numero' => 3, 'nombre' => 'Difícil', 'icono' => '🔴']
        ];
        $clave = $partida['nivel_actual'];
        if (!isset($niveles[$clave])) {
            return null;
        }

        // Se recuerda el último nivel mostrado de esta partida para detectar cuándo sube
        $anterior = $_SESSION['nivel_partida'] ?? null;
        $subio = $preguntaNueva && $anterior
            && $anterior['id_partida'] == $id_partida
            && $anterior['nivel'] !== $clave;

        if ($preguntaNueva) {
            $_SESSION['nivel_partida'] = ['id_partida' => $id_partida, 'nivel' => $clave];
        }

        return $niveles[$clave] + ['clase' => $clave, 'subio_nivel' => $subio];
    }

    private function limpiarSesionPregunta()
    {
        unset($_SESSION['id_pregunta_actual']);
        unset($_SESSION['tiempo_inicio_pregunta']);
    }
}
