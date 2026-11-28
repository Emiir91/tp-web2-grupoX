<?php

namespace app\controller;

class LobbyController
{
    // Valor especial del desplegable de categoría: "➕ Otra (sugerir nueva)"
    private const CATEGORIA_NUEVA = '__nueva__';

    private $renderer;
    private $partidaModel;
    private $categoriaModel;

    public function __construct($renderer, $partidaModel, $categoriaModel)
    {
        $this->renderer = $renderer;
        $this->partidaModel = $partidaModel;
        $this->categoriaModel = $categoriaModel;
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
            'puntaje_total' => $this->partidaModel->obtenerPuntajeTotalUsuario($id_usuario),
            'puntaje_mensual' => $this->partidaModel->obtenerPuntajeMensualUsuario($id_usuario)
        ];

        if (($_GET['mensaje'] ?? '') === 'sugerencia_enviada') {
            $datosParaVista['alerta_exito'] = '¡Gracias! Tu sugerencia quedó pendiente de revisión por el equipo editor.';
        }

        $datosParaVista['categoria_sorteada'] = $_SESSION['categoria_sorteada'] ?? null;
        $datosParaVista['sin_categorias'] = ($_GET['mensaje'] ?? '') === 'sin_categorias';

        $datosParaVista['categorias_animacion'] = $this->categoriaModel->obtenerCategoriasJugables();

        $datosParaVista['requiere_lobby'] = true;

        $this->renderer->renderiza('lobby', $datosParaVista);
    }

    public function sortearCategoria()
    {
        if (empty($_SESSION['usuario_logueado'])) {
            header("Location: " . BASE_URL . "/login");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/lobby");
            exit();
        }

        $categorias = $this->categoriaModel->obtenerCategoriasJugables();

        if (!$categorias) {
            unset($_SESSION['categoria_sorteada']);
            header("Location: " . BASE_URL . "/lobby?mensaje=sin_categorias");
            exit();
        }

        $_SESSION['categoria_sorteada'] = $categorias[array_rand($categorias)]['nombre'];

        header("Location: " . BASE_URL . "/lobby");
        exit();
    }

    public function sugerir()
    {
        if (empty($_SESSION['usuario_logueado'])) {
            header("Location: " . BASE_URL . "/login");
            exit();
        }

        $datosParaVista = [
            'categorias' => $this->categoriaModel->obtenerActivas(),
            'requiere_categoria_nueva' => true
        ];

        $mensaje = $_GET['mensaje'] ?? '';
        if ($mensaje === 'error') {
            $datosParaVista['alerta_error'] = 'Completá todos los campos correctamente.';
        } elseif ($mensaje === 'categoria_invalida') {
            $datosParaVista['alerta_error'] = 'El nombre de la categoría nueva tiene que tener entre 3 y 40 caracteres (letras, números y espacios).';
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

        if ($categoria === self::CATEGORIA_NUEVA) {
            // El jugador propone una categoría nueva junto con su pregunta
            $nombreNueva = \app\model\CategoriaModel::normalizarNombre($_POST['categoria_nueva'] ?? '');

            if (!\app\model\CategoriaModel::nombreValido($nombreNueva)) {
                header("Location: " . BASE_URL . "/lobby/sugerir?mensaje=categoria_invalida");
                exit();
            }

            // Si ya existe (aunque esté escrita distinto, ej. "historia") se usa la existente; si no, queda sugerida
            $categoria = $this->categoriaModel->sugerir($nombreNueva);
        } elseif (!in_array($categoria, $this->categoriaModel->obtenerNombresActivos(), true)) {
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
