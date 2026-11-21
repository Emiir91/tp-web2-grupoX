<?php

namespace app\controller;

class EditorController
{
    private const CATEGORIAS = [
        'Geografía', 'Ciencias', 'Matemáticas', 'Cultura General', 'Animales',
        'Literatura', 'Historia', 'Física', 'Deportes', 'Arte',
        'Música', 'Cine', 'Tecnología', 'Videojuegos'
    ];

    private $renderer;
    private $editorModel;

    public function __construct($renderer, $editorModel)
    {
        if (empty($_SESSION['usuario_logueado'])) {
            header("Location: " . BASE_URL . "/login");
            exit();
        }

        if (!in_array($_SESSION['id_rol'] ?? 0, [2, 3])) {
            header("Location: " . BASE_URL . "/lobby");
            exit();
        }

        $this->renderer = $renderer;
        $this->editorModel = $editorModel;
    }

    public function index()
    {
        $datosParaVista = [
            'reportes' => $this->editorModel->obtenerReportesPendientes(),
            'sugeridas' => $this->editorModel->obtenerSugeridas()
        ];

        $mensaje = $_GET['mensaje'] ?? '';
        if ($mensaje === 'alta_enviada') {
            $datosParaVista['alerta_exito'] = 'La pregunta fue enviada y queda pendiente de aprobación del administrador.';
        } elseif ($mensaje === 'reporte_desestimado') {
            $datosParaVista['alerta_exito'] = 'El reporte fue desestimado.';
        } elseif ($mensaje === 'baja_enviada') {
            $datosParaVista['alerta_exito'] = 'La baja fue enviada y queda pendiente de aprobación del administrador.';
        } elseif ($mensaje === 'sugerencia_aprobada') {
            $datosParaVista['alerta_exito'] = 'La sugerencia fue aprobada.';
        } elseif ($mensaje === 'sugerencia_rechazada') {
            $datosParaVista['alerta_exito'] = 'La sugerencia fue rechazada y eliminada.';
        } elseif ($mensaje === 'alta_aplicada') {
            $datosParaVista['alerta_exito'] = 'La pregunta fue creada y ya está activa en el juego.';
        } elseif ($mensaje === 'baja_aplicada') {
            $datosParaVista['alerta_exito'] = 'La pregunta fue eliminada.';
        }

        $this->renderer->renderiza('editor', $datosParaVista);
    }

    public function preguntas()
    {
        // Filtros que llegan por la URL (?categoria=...&dificultad=...&estado=...).
        // Solo se aceptan valores válidos; cualquier otro se ignora.
        $categoria = trim($_GET['categoria'] ?? '');
        $dificultad = $_GET['dificultad'] ?? '';
        $estado = $_GET['estado'] ?? '';

        // Solo los estados de preguntas que ya fueron validadas alguna vez
        $estadosValidos = [
            'activa' => 'Activa',
            'pendiente_baja' => 'Pendiente de baja',
            'pendiente_reactivacion' => 'Pendiente de reactivación',
            'eliminada' => 'Eliminada'
        ];

        if (!in_array($categoria, self::CATEGORIAS, true)) {
            $categoria = '';
        }
        if (!in_array($dificultad, ['facil', 'intermedio', 'dificil'], true)) {
            $dificultad = '';
        }
        if (!array_key_exists($estado, $estadosValidos)) {
            $estado = '';
        }

        $preguntas = $this->editorModel->obtenerTodasLasPreguntas($categoria ?: null, $dificultad ?: null, $estado ?: null);

        // Categorías ordenadas alfabéticamente para el desplegable del filtro
        $nombresCategorias = self::CATEGORIAS;
        sort($nombresCategorias);
        $categorias = array_map(function ($cat) use ($categoria) {
            return ['nombre' => $cat, 'seleccionada' => ($cat === $categoria)];
        }, $nombresCategorias);

        $dificultades = [];
        foreach (['facil' => 'Fácil', 'intermedio' => 'Intermedio', 'dificil' => 'Difícil'] as $valor => $nombre) {
            $dificultades[] = ['valor' => $valor, 'nombre' => $nombre, 'seleccionada' => ($valor === $dificultad)];
        }

        $estados = [];
        foreach ($estadosValidos as $valor => $nombre) {
            $estados[] = ['valor' => $valor, 'nombre' => $nombre, 'seleccionada' => ($valor === $estado)];
        }

        $datosParaVista = [
            'preguntas' => $preguntas,
            'total_preguntas' => count($preguntas),
            'categorias' => $categorias,
            'dificultades' => $dificultades,
            'estados' => $estados,
            'hay_filtro' => ($categoria !== '' || $dificultad !== '' || $estado !== '')
        ];

        $mensaje = $_GET['mensaje'] ?? '';
        if ($mensaje === 'edicion_enviada') {
            $datosParaVista['alerta_exito'] = 'La edición fue enviada y queda pendiente de aprobación del administrador.';
        } elseif ($mensaje === 'baja_enviada') {
            $datosParaVista['alerta_exito'] = 'La baja fue enviada y queda pendiente de aprobación del administrador.';
        } elseif ($mensaje === 'edicion_aplicada') {
            $datosParaVista['alerta_exito'] = 'Los cambios se guardaron y ya están aplicados.';
        } elseif ($mensaje === 'baja_aplicada') {
            $datosParaVista['alerta_exito'] = 'La pregunta fue eliminada.';
        } elseif ($mensaje === 'reactivacion_enviada') {
            $datosParaVista['alerta_exito'] = 'La reactivación fue enviada y queda pendiente de aprobación del administrador.';
        } elseif ($mensaje === 'reactivacion_aplicada') {
            $datosParaVista['alerta_exito'] = 'La pregunta fue reactivada y vuelve a estar en el juego.';
        }

        $this->renderer->renderiza('editorPreguntas', $datosParaVista);
    }

    public function editar()
    {
        $id_pregunta = $_GET['id'] ?? null;

        if (!$id_pregunta) {
            header("Location: " . BASE_URL . "/editor/preguntas");
            exit();
        }

        $pregunta = $this->editorModel->obtenerPreguntaPorId($id_pregunta);
        $respuestas = $this->editorModel->obtenerRespuestasDePregunta($id_pregunta);

        // Solo se editan preguntas activas (no las eliminadas, pendientes de baja/reactivación ni las no aprobadas)
        if (!$pregunta || $pregunta['estado'] !== 'activa') {
            header("Location: " . BASE_URL . "/editor/preguntas");
            exit();
        }

        $correcta = null;
        $incorrectas = [];
        foreach ($respuestas as $r) {
            if ($r['es_correcta'] == 1) {
                $correcta = $r['texto_respuesta'];
            } else {
                $incorrectas[] = $r['texto_respuesta'];
            }
        }

        $categorias = array_map(function ($cat) use ($pregunta) {
            return [
                'nombre' => $cat,
                'seleccionada' => ($pregunta['categoria'] === $cat)
            ];
        }, self::CATEGORIAS);

        $datosParaVista = [
            'id_pregunta' => $id_pregunta,
            'texto_pregunta' => $pregunta['texto_pregunta'],
            'categoria' => $pregunta['categoria'],
            'categorias' => $categorias,
            'dificultad' => $pregunta['dificultad'],
            'respuesta_correcta' => $correcta ?? '',
            'incorrecta_1' => $incorrectas[0] ?? '',
            'incorrecta_2' => $incorrectas[1] ?? '',
            'incorrecta_3' => $incorrectas[2] ?? '',
            'es_facil' => ($pregunta['dificultad'] === 'facil'),
            'es_intermedio' => ($pregunta['dificultad'] === 'intermedio'),
            'es_dificil' => ($pregunta['dificultad'] === 'dificil')
        ];

        $this->renderer->renderiza('editorEditar', $datosParaVista);
    }

    public function guardarEdicion()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/editor/preguntas");
            exit();
        }

        $id_pregunta = $_POST['id_pregunta'] ?? null;
        $datos = $this->extraerDatosPregunta();

        if (!$id_pregunta || !$datos) {
            header("Location: " . BASE_URL . "/editor/preguntas");
            exit();
        }

        $pregunta = $this->editorModel->obtenerPreguntaPorId($id_pregunta);
        if (!$pregunta || $pregunta['estado'] !== 'activa') {
            header("Location: " . BASE_URL . "/editor/preguntas");
            exit();
        }

        // El admin no necesita aprobación: sus cambios se aplican en el momento
        if ($this->esAdmin()) {
            $this->editorModel->aplicarEdicion($id_pregunta, $datos);
            header("Location: " . BASE_URL . "/editor/preguntas?mensaje=edicion_aplicada");
            exit();
        }

        $this->editorModel->guardarEdicionPendiente($id_pregunta, $datos);

        header("Location: " . BASE_URL . "/editor/preguntas?mensaje=edicion_enviada");
        exit();
    }

    public function eliminar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/editor/preguntas");
            exit();
        }

        $id_pregunta = $_POST['id_pregunta'] ?? null;

        if ($id_pregunta && $this->esAdmin()) {
            // El admin da de baja directamente
            $this->editorModel->eliminarDirecto($id_pregunta);
            header("Location: " . BASE_URL . "/editor/preguntas?mensaje=baja_aplicada");
            exit();
        }

        if ($id_pregunta) {
            $this->editorModel->marcarPendienteBaja($id_pregunta);
        }

        header("Location: " . BASE_URL . "/editor/preguntas?mensaje=baja_enviada");
        exit();
    }

    public function reactivar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/editor/preguntas");
            exit();
        }

        $id_pregunta = $_POST['id_pregunta'] ?? null;

        if ($id_pregunta) {
            $this->editorModel->reactivar($id_pregunta, $this->esAdmin());
        }

        $mensaje = $this->esAdmin() ? 'reactivacion_aplicada' : 'reactivacion_enviada';
        header("Location: " . BASE_URL . "/editor/preguntas?mensaje=" . $mensaje);
        exit();
    }

    public function agregar()
    {
        $datosParaVista = [];
        if (($_GET['mensaje'] ?? '') === 'error') {
            $datosParaVista['alerta_error'] = 'Completá todos los campos correctamente.';
        }
        $this->renderer->renderiza('editorAgregar', $datosParaVista);
    }

    public function guardarPregunta()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/editor/agregar");
            exit();
        }

        $datos = $this->extraerDatosPregunta();

        if (!$datos) {
            header("Location: " . BASE_URL . "/editor/agregar?mensaje=error");
            exit();
        }

        // Si la crea el admin queda activa directamente; si la crea un editor, pendiente de alta
        $id_pregunta = $this->editorModel->crearPreguntaPendiente($datos, $this->esAdmin());

        if ($id_pregunta) {
            $mensaje = $this->esAdmin() ? 'alta_aplicada' : 'alta_enviada';
            header("Location: " . BASE_URL . "/editor/index?mensaje=" . $mensaje);
        } else {
            header("Location: " . BASE_URL . "/editor/agregar?mensaje=error");
        }
        exit();
    }

    public function desestimarReporte()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/editor/index");
            exit();
        }

        $id_reporte = $_POST['id_reporte'] ?? null;

        if ($id_reporte) {
            $this->editorModel->desestimarReporte($id_reporte);
        }

        header("Location: " . BASE_URL . "/editor/index?mensaje=reporte_desestimado");
        exit();
    }

    public function editarDesdeReporte()
    {
        $id_reporte = $_GET['id'] ?? null;

        if (!$id_reporte) {
            header("Location: " . BASE_URL . "/editor/index");
            exit();
        }

        $reporte = $this->editorModel->obtenerReportePorId($id_reporte);

        if (!$reporte || $reporte['estado'] !== 'pendiente') {
            header("Location: " . BASE_URL . "/editor/index");
            exit();
        }

        header("Location: " . BASE_URL . "/editor/editar?id=" . $reporte['id_pregunta']);
        exit();
    }

    public function eliminarDesdeReporte()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/editor/index");
            exit();
        }

        $id_reporte = $_POST['id_reporte'] ?? null;

        if ($id_reporte) {
            $reporte = $this->editorModel->obtenerReportePorId($id_reporte);
            if ($reporte && $reporte['estado'] === 'pendiente') {
                if ($this->esAdmin()) {
                    $this->editorModel->eliminarDirecto($reporte['id_pregunta']);
                } else {
                    $this->editorModel->marcarPendienteBaja($reporte['id_pregunta']);
                }
                $this->editorModel->desestimarReporte($id_reporte);
            }
        }

        $mensaje = $this->esAdmin() ? 'baja_aplicada' : 'baja_enviada';
        header("Location: " . BASE_URL . "/editor/index?mensaje=" . $mensaje);
        exit();
    }

    public function aprobarSugerencia()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/editor/index");
            exit();
        }

        $id_pregunta = $_POST['id_pregunta'] ?? null;

        if ($id_pregunta) {
            $this->editorModel->aprobarSugerencia($id_pregunta, $this->esAdmin());
        }

        header("Location: " . BASE_URL . "/editor/index?mensaje=sugerencia_aprobada");
        exit();
    }

    public function rechazarSugerencia()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/editor/index");
            exit();
        }

        $id_pregunta = $_POST['id_pregunta'] ?? null;

        if ($id_pregunta) {
            $this->editorModel->rechazarSugerencia($id_pregunta);
        }

        header("Location: " . BASE_URL . "/editor/index?mensaje=sugerencia_rechazada");
        exit();
    }

    // El admin también usa el panel de editor, pero sus acciones no necesitan aprobación
    private function esAdmin()
    {
        return (($_SESSION['id_rol'] ?? 0) == 3);
    }

    private function extraerDatosPregunta()
    {
        $texto_pregunta = trim($_POST['texto_pregunta'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $dificultad = $_POST['dificultad'] ?? 'facil';
        $respuesta_correcta = trim($_POST['respuesta_correcta'] ?? '');
        $incorrecta_1 = trim($_POST['incorrecta_1'] ?? '');
        $incorrecta_2 = trim($_POST['incorrecta_2'] ?? '');
        $incorrecta_3 = trim($_POST['incorrecta_3'] ?? '');

        if ($texto_pregunta === '' || $categoria === '' || $respuesta_correcta === '' ||
            $incorrecta_1 === '' || $incorrecta_2 === '' || $incorrecta_3 === '') {
            return null;
        }

        if (!in_array($dificultad, ['facil', 'intermedio', 'dificil'])) {
            $dificultad = 'facil';
        }

        if (!in_array($categoria, self::CATEGORIAS, true)) {
            return null;
        }

        return [
            'texto_pregunta' => $texto_pregunta,
            'categoria' => $categoria,
            'dificultad' => $dificultad,
            'respuesta_correcta' => $respuesta_correcta,
            'incorrecta_1' => $incorrecta_1,
            'incorrecta_2' => $incorrecta_2,
            'incorrecta_3' => $incorrecta_3
        ];
    }
}
