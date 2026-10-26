<?php

namespace app\controller;

class PerfilController
{
    private $renderer;
    private $model;

    public function __construct($model, $renderer)
    {
        $this->model = $model;
        $this->renderer = $renderer;
    }

    public function index()
    {
        // Si no está logueado, lo patea al login
        if (empty($_SESSION['usuario_logueado'])) {
            header("Location: " . BASE_URL . "/login");
            exit();
        }

        // Busca los datos completos del usuario
        $nombre_usuario = $_SESSION['nombre_usuario'];
        $datosUsuario = $this->model->buscarUsuario($nombre_usuario);

        // Manda los datos a la vista
        $this->renderer->renderiza('perfil', $datosUsuario);
    }
}
