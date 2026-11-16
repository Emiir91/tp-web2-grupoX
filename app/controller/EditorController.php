<?php

namespace app\controller;

class EditorController
{
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
        }

        $this->renderer->renderiza('editor', $datosParaVista);
    }

    public function preguntas()
    {
        $datosParaVista = [
            'preguntas' => $this->editorModel->obtenerTodasLasPreguntas()
        ];

        $mensaje = $_GET['mensaje'] ?? '';
        if ($mensaje === 'edicion_enviada') {
            $datosParaVista['alerta_exito'] = 'La edición fue enviada y queda pendiente de aprobación del administrador.';
        } elseif ($mensaje === 'baja_enviada') {
            $datosParaVista['alerta_exito'] = 'La baja fue enviada y queda pendiente de aprobación del administrador.';
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

        if (!$pregunta) {
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

        $datosParaVista = [
            'id_pregunta' => $id_pregunta,
            'texto_pregunta' => $pregunta['texto_pregunta'],
            'categoria' => $pregunta['categoria'],
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

        if ($id_pregunta) {
            $this->editorModel->marcarPendienteBaja($id_pregunta);
        }

        header("Location: " . BASE_URL . "/editor/preguntas?mensaje=baja_enviada");
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

        $id_pregunta = $this->editorModel->crearPreguntaPendiente($datos);

        if ($id_pregunta) {
            header("Location: " . BASE_URL . "/editor/index?mensaje=alta_enviada");
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
                $this->editorModel->marcarPendienteBaja($reporte['id_pregunta']);
                $this->editorModel->desestimarReporte($id_reporte);
            }
        }

        header("Location: " . BASE_URL . "/editor/index?mensaje=baja_enviada");
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
            $es_admin = (($_SESSION['id_rol'] ?? 0) == 3);
            $this->editorModel->aprobarSugerencia($id_pregunta, $es_admin);
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
