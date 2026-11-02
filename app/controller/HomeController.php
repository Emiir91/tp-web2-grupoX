<?php
namespace app\controller;

class HomeController
{
    private $renderer;
    private $model;

    public function __construct($renderer) {
        $this->renderer = $renderer;
    }

    public function index()
    {
        // Si el usuario ya está logueado, lo mandamos derecho al lobby
        if (isset($_SESSION['usuario_logueado']) && $_SESSION['usuario_logueado'] === true) {
            header("Location: " . BASE_URL . "lobby");
            exit();
        }

        // Si no está logueado, recién ahí le muestra el home normal de invitado
        $this->renderer->renderiza('home');
    }
}