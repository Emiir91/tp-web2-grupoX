<?php

namespace app\controller;

class LobbyController
{
    private $renderer;

    public function __construct($renderer)
    {
        $this->renderer = $renderer;
    }

    public function index()
    {
        // Barrera de seguridad
        if (!isset($_SESSION['usuario_logueado']) || $_SESSION['usuario_logueado'] !== true) {
            header("Location: " . BASE_URL . "/login/index");
            exit();
        }

        $datosParaVista = [
            'nombre_usuario' => $_SESSION['nombre_usuario']
        ];

        $this->renderer->renderiza("lobby", $datosParaVista);
    }
}