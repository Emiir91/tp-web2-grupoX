<?php

namespace app\controller;

class LobbyController
{
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

        $this->renderer->renderiza('lobby', $datosParaVista);
    }
}
