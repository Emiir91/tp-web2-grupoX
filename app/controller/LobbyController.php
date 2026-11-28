<?php

namespace app\controller;

use app\service\LobbyService;

class LobbyController
{
    private $renderer;
    private LobbyService $lobbyService;

    public function __construct($renderer, LobbyService $lobbyService)
    {
        $this->renderer = $renderer;
        $this->lobbyService = $lobbyService;
    }

    public function index()
    {
        $this->requerirSesion();

        $mensaje = $_GET['mensaje'] ?? '';
        $datosParaVista = $this->lobbyService->obtenerDatosLobby($_SESSION['id_usuario']);
        $datosParaVista['categoria_sorteada'] = $_SESSION['categoria_sorteada'] ?? null;
        $datosParaVista['sin_categorias'] = $mensaje === 'sin_categorias';
        $datosParaVista['requiere_lobby'] = true;

        if ($mensaje === 'sugerencia_enviada') {
            $datosParaVista['alerta_exito'] = '¡Gracias! Tu sugerencia quedó pendiente de revisión por el equipo editor.';
        }

        $this->renderer->renderiza('lobby', $datosParaVista);
    }

    public function sortearCategoria()
    {
        $this->requerirSesion();
        $this->requerirPost();

        $categoria = $this->lobbyService->sortearCategoria();

        if ($categoria === null) {
            unset($_SESSION['categoria_sorteada']);
            header("Location: " . BASE_URL . "/lobby?mensaje=sin_categorias");
            exit();
        }

        $_SESSION['categoria_sorteada'] = $categoria;
        header("Location: " . BASE_URL . "/lobby");
        exit();
    }

    public function sugerir()
    {
        $this->requerirSesion();

        $datosParaVista = [
            'categorias' => $this->lobbyService->obtenerCategoriasParaSugerir(),
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
        $this->requerirSesion();
        $this->requerirPost();

        $error = $this->lobbyService->guardarSugerencia($_POST);

        if ($error === null) {
            header("Location: " . BASE_URL . "/lobby?mensaje=sugerencia_enviada");
        } elseif ($error === LobbyService::CATEGORIA_INVALIDA) {
            header("Location: " . BASE_URL . "/lobby/sugerir?mensaje=categoria_invalida");
        } else {
            header("Location: " . BASE_URL . "/lobby/sugerir?mensaje=error");
        }
        exit();
    }

    private function requerirSesion()
    {
        if (empty($_SESSION['usuario_logueado'])) {
            header("Location: " . BASE_URL . "/login");
            exit();
        }
    }

    private function requerirPost()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/lobby");
            exit();
        }
    }
}
