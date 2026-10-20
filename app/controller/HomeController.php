<?php

namespace app\controller;

class HomeController
{
    private $renderer;

    public function __construct($renderer)
    {
        $this->renderer = $renderer;
    }

    public function index()
    {

        // Barrera de seguridad: si no hay sesión, lo mandamos al login
        if (!isset($_SESSION['usuario_logueado']) || $_SESSION['usuario_logueado'] !== true) {
            header("Location: " . BASE_URL . "/login/index");
            exit();
        }

        // Si pasó, le mandamos sus datos a la vista
        $datosParaVista = [
            'nombre_usuario' => $_SESSION['nombre_usuario']
        ];

        $this->renderer->renderiza('home', $datosParaVista);
    }
}