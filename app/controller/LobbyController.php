<?php

namespace app\controller;

class LobbyController
{
    private const CATEGORIAS = [
        'Geografía', 'Ciencias', 'Matemáticas', 'Cultura General', 'Animales',
        'Literatura', 'Historia', 'Física', 'Deportes', 'Arte',
        'Música', 'Cine', 'Tecnología', 'Videojuegos'
    ];

    private $renderer;
    private $partidaModel;

    public function __construct($renderer, $partidaModel)
    {
        $this->renderer = $renderer;
        $this->partidaModel = $partidaModel;
    }

    public function index()
    {
        // El lobby es solo para usuarios logueados
        if (empty($_SESSION['usuario_logueado'])) {
            header("Location: " . BASE_URL . "/login");
            exit();
        }

        $id_usuario = $_SESSION['id_usuario'];

        $datosParaVista = [
            'partidas' => $this->partidaModel->obtenerHistorialUsuario($id_usuario),
            'puntaje_total' => $this->partidaModel->obtenerPuntajeTotalUsuario($id_usuario)
        ];

        if (($_GET['mensaje'] ?? '') === 'sugerencia_enviada') {
            $datosParaVista['alerta_exito'] = '¡Gracias! Tu sugerencia quedó pendiente de revisión por el equipo editor.';
        }

        $this->renderer->renderiza('lobby', $datosParaVista);
    }

    public function sugerir()
    {
        if (empty($_SESSION['usuario_logueado'])) {
            header("Location: " . BASE_URL . "/login");
            exit();
        }

        $datosParaVista = [];
        if (($_GET['mensaje'] ?? '') === 'error') {
            $datosParaVista['alerta_error'] = 'Completá todos los campos correctamente.';
        }

        $this->renderer->renderiza('lobbySugerir', $datosParaVista);
    }

    public function guardarSugerencia()
    {
        if (empty($_SESSION['usuario_logueado'])) {
            header("Location: " . BASE_URL . "/login");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/lobby");
            exit();
        }

        $texto_pregunta = trim($_POST['texto_pregunta'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $dificultad = $_POST['dificultad'] ?? 'facil';
        $respuesta_correcta = trim($_POST['respuesta_correcta'] ?? '');
        $incorrecta_1 = trim($_POST['incorrecta_1'] ?? '');
        $incorrecta_2 = trim($_POST['incorrecta_2'] ?? '');
        $incorrecta_3 = trim($_POST['incorrecta_3'] ?? '');

        if ($texto_pregunta === '' || $categoria === '' || $respuesta_correcta === '' ||
            $incorrecta_1 === '' || $incorrecta_2 === '' || $incorrecta_3 === '') {
            header("Location: " . BASE_URL . "/lobby/sugerir?mensaje=error");
            exit();
        }

        if (!in_array($dificultad, ['facil', 'intermedio', 'dificil'])) {
            $dificultad = 'facil';
        }

        if (!in_array($categoria, self::CATEGORIAS, true)) {
            header("Location: " . BASE_URL . "/lobby/sugerir?mensaje=error");
            exit();
        }

        $id_pregunta = $this->partidaModel->crearSugerencia([
            'texto_pregunta' => $texto_pregunta,
            'categoria' => $categoria,
            'dificultad' => $dificultad,
            'respuesta_correcta' => $respuesta_correcta,
            'incorrecta_1' => $incorrecta_1,
            'incorrecta_2' => $incorrecta_2,
            'incorrecta_3' => $incorrecta_3
        ]);

        if ($id_pregunta) {
            header("Location: " . BASE_URL . "/lobby?mensaje=sugerencia_enviada");
        } else {
            header("Location: " . BASE_URL . "/lobby/sugerir?mensaje=error");
        }
        exit();
    }
}
