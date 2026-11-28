<?php

namespace app\controller;

use app\service\PartidaService;

class JuegoController
{
    private $partidaModel;
    private $renderer;
    private $categoriaModel;
    private $partidaService;

    public function __construct($partidaModel, $renderer, $categoriaModel, $partidaService)
    {
        $this->partidaModel = $partidaModel;
        $this->renderer = $renderer;
        $this->categoriaModel = $categoriaModel;
        $this->partidaService = $partidaService;

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

        $esTematica = ($_POST['alcance'] ?? '') === 'categoria';
        $categoria = $esTematica ? ($_SESSION['categoria_sorteada'] ?? null) : null;

        if (isset($_POST['id_partida_anterior'])) {
            $rondaAnterior = $_SESSION['ronda_categoria'] ?? [];
            $idAnterior = $_POST['id_partida_anterior'];

            // Recuperar la categoría usada en esa ronda, aunque el sorteo haya cambiado.
            if (!isset($rondaAnterior['id_partida']) || !is_string($idAnterior)
                || $idAnterior !== (string) $rondaAnterior['id_partida']) {
                header("Location: " . BASE_URL . "/lobby");
                exit();
            }

            $categoria = $rondaAnterior['categoria'] ?? null;
            $esTematica = $categoria !== null;
        }

        $modo_juego = $_POST['modo_juego'] ?? 'progresivo';

        $inicio = $this->partidaService->iniciarORecuperar(
            $_SESSION['id_usuario'],
            $modo_juego,
            $esTematica,
            $categoria
        );

        if ($inicio === null) {
            header("Location: " . BASE_URL . "/lobby");
            exit();
        }

        if (isset($inicio['en_curso'])) {
            $this->retomarPartida($inicio['en_curso']);
            header("Location: " . BASE_URL . "/juego/mostrar");
            exit();
        }

        $id_partida = $inicio['id_nueva'];
        $_SESSION['id_partida'] = $id_partida;
        $_SESSION['ronda_categoria'] = [
            'id_partida' => (int) $id_partida,
            'categoria' => $categoria
        ];

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
        $preguntaNueva = false;

        if (isset($_SESSION['id_pregunta_actual'], $_SESSION['tiempo_inicio_pregunta'])) {
            $id_pregunta_actual = $_SESSION['id_pregunta_actual'];
            $tiempo_restante = $this->partidaService->tiempoRestanteDesde(
                $_SESSION['tiempo_inicio_pregunta']
            );

            if ($this->partidaService->registrarVencimientoSiCorresponde(
                $id_partida,
                $id_pregunta_actual,
                $tiempo_restante
            )) {
                $this->limpiarSesionPregunta();
                $this->irAFeedback($id_partida, $id_pregunta_actual, null);
            }

            $pregunta = $this->partidaModel->obtenerPreguntaPorId($id_pregunta_actual);
        } else {
            $rondaCategoria = $_SESSION['ronda_categoria'] ?? [];
            $categoria = ($rondaCategoria['id_partida'] ?? null) === (int) $id_partida
                ? $rondaCategoria['categoria']
                : null;

            $pregunta = $this->partidaService->prepararSiguientePregunta($id_partida, $categoria);

            if ($pregunta === PartidaService::SIN_PREGUNTAS) {
                header("Location: " . BASE_URL . "/juego/resultados");
                exit();
            }

            if ($pregunta === PartidaService::ASIGNACION_FALLIDA) {
                header("Location: " . BASE_URL . "/lobby");
                exit();
            }

            $_SESSION['id_pregunta_actual'] = $pregunta['id_pregunta'];
            $_SESSION['tiempo_inicio_pregunta'] = time();

            $tiempo_restante = PartidaService::DURACION_TURNO;
            $preguntaNueva = true;
        }

        $respuestas = $this->partidaModel->obtenerRespuestasDePregunta(
            $pregunta['id_pregunta']
        );
        $_SESSION['orden_respuestas'] = array_column($respuestas, 'id_respuesta');

        $respuestasParaVista = [];

        foreach ($respuestas as $respuesta) {
            $idRespuesta = (int) $respuesta['id_respuesta'];

            $_SESSION['codigos_respuestas'][$idRespuesta] ??= bin2hex(random_bytes(16));

            $respuestasParaVista[] = [
                'codigo_respuesta' => $_SESSION['codigos_respuestas'][$idRespuesta],
                'texto_respuesta' => $respuesta['texto_respuesta']
            ];
        }

        $vidas_array = array_fill(0, $vidas_actuales, '❤️');
        $colorCategoria = $this->categoriaModel->obtenerColor($pregunta['categoria']);

        $datosParaVista = [
            'nivel' => $this->datosNivel($id_partida, $preguntaNueva),
            'pregunta' => $pregunta['texto_pregunta'],
            'categoria' => $pregunta['categoria'],
            'color_categoria' => $colorCategoria,
            'respuestas' => $respuestasParaVista,
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

        $codigoRespuesta = $_POST['codigo_respuesta'] ?? null;

        $id_respuesta = is_string($codigoRespuesta)
            ? array_search($codigoRespuesta, $_SESSION['codigos_respuestas'] ?? [], true)
            : false;

        if ($id_respuesta === false || !isset($_SESSION['id_partida'])) {
            header("Location: " . BASE_URL . "/juego/mostrar");
            exit();
        }

        $id_partida = $_SESSION['id_partida'];
        $id_pregunta_actual = $_SESSION['id_pregunta_actual'] ?? null;

        if (!$id_pregunta_actual) {
            $this->limpiarSesionPregunta();
            header("Location: " . BASE_URL . "/juego/mostrar");
            exit();
        }

        $resultado = $this->partidaService->resolverRespuesta(
            $id_partida,
            $id_pregunta_actual,
            $id_respuesta
        );

        if ($resultado === PartidaService::RESPUESTA_INVALIDA) {
            header("Location: " . BASE_URL . "/juego/mostrar");
            exit();
        }

        $this->limpiarSesionPregunta();

        if ($resultado === PartidaService::PREGUNTA_YA_RESPONDIDA) {
            header("Location: " . BASE_URL . "/juego/mostrar");
            exit();
        }

        if ($resultado === PartidaService::PARTIDA_CERRADA) {
            header("Location: " . BASE_URL . "/juego/resultados");
            exit();
        }

        $id_elegida = $resultado === PartidaService::TIEMPO_AGOTADO ? null : $id_respuesta;
        $this->irAFeedback($id_partida, $id_pregunta_actual, $id_elegida);
    }

    public function tiempoAgotado()
    {
        if (!isset($_SESSION['id_partida'])) {
            header("Location: " . BASE_URL . "/lobby");
            exit();
        }

        $id_partida = $_SESSION['id_partida'];
        $id_pregunta_actual = $_SESSION['id_pregunta_actual'] ?? null;

        if (!$id_pregunta_actual || !$this->partidaService->registrarVencimientoSiCorresponde(
                $id_partida,
                $id_pregunta_actual
            )) {
            header("Location: " . BASE_URL . "/juego/mostrar");
            exit();
        }

        $this->limpiarSesionPregunta();

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
            // Si no acertó, perdió una vida: se muestra el corazón rompiéndose
            'perdio_vida' => !$acerto,
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

        $this->partidaService->abandonarPartida($id_partida);

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

    // Distintivo de nivel (solo visual: el cambio de nivel lo decide el modelo).
    // Progresivo: "Nivel 1 · Fácil" y avisa al subir. Modos fijos: "Modo Fácil" (no cambia nunca).
    // 'subio_nivel' es true solo en la primera pregunta de un nivel nuevo del progresivo.
    private function datosNivel($id_partida, $preguntaNueva)
    {
        $partida = $this->partidaModel->obtenerModoYNivel($id_partida);
        if (!$partida) {
            return null;
        }
        $esProgresivo = ($partida['modo_juego'] === 'progresivo');

        $niveles = [
            'facil' => ['numero' => 1, 'nombre' => 'Fácil', 'icono' => '🟢'],
            'intermedio' => ['numero' => 2, 'nombre' => 'Medio', 'icono' => '🟡'],
            'dificil' => ['numero' => 3, 'nombre' => 'Difícil', 'icono' => '🔴']
        ];
        // En los modos fijos, el nivel es el propio modo
        $clave = $esProgresivo ? $partida['nivel_actual'] : $partida['modo_juego'];
        if (!isset($niveles[$clave])) {
            return null;
        }

        $etiqueta = $esProgresivo
            ? 'Nivel ' . $niveles[$clave]['numero'] . ' · ' . $niveles[$clave]['nombre']
            : 'Modo ' . $niveles[$clave]['nombre'];

        if (!$esProgresivo) {
            return $niveles[$clave] + ['clase' => $clave, 'etiqueta' => $etiqueta, 'subio_nivel' => false];
        }

        // Se recuerda el último nivel mostrado de esta partida para detectar cuándo sube
        $anterior = $_SESSION['nivel_partida'] ?? null;
        $subio = $preguntaNueva && $anterior
            && $anterior['id_partida'] == $id_partida
            && $anterior['nivel'] !== $clave;

        if ($preguntaNueva) {
            $_SESSION['nivel_partida'] = ['id_partida' => $id_partida, 'nivel' => $clave];
        }

        return $niveles[$clave] + ['clase' => $clave, 'etiqueta' => $etiqueta, 'subio_nivel' => $subio];
    }

    private function limpiarSesionPregunta()
    {
        unset($_SESSION['id_pregunta_actual']);
        unset($_SESSION['tiempo_inicio_pregunta']);
        unset($_SESSION['codigos_respuestas']);
    }
}
