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

        // Nos aseguramos de pasar explícitamente el nombre de usuario para el QR
        if ($datosUsuario) {
            $datosUsuario['nombre_usuario'] = $nombre_usuario;
        }

        // Manda los datos a la vista
        $this->renderer->renderiza('perfil', $datosUsuario);
    }

    public function publico()
    {
        // Captura el usuario que viene por la URL (ej: /perfil/publico?usuario=Nahuguerrero)
        $nombre_usuario = $_GET['usuario'] ?? null;

        if (!$nombre_usuario) {
            header("Location: " . BASE_URL . "/home");
            exit();
        }

        // Busca los datos del usuario para mostrarlos en modo lectura
        $datosUsuario = $this->model->buscarUsuario($nombre_usuario);

        if (!$datosUsuario) {
            // Si no existe, lo mandamos al home
            header("Location: " . BASE_URL . "/home");
            exit();
        }

        $datosUsuario['nombre_usuario'] = $nombre_usuario;

        // Renderiza la misma vista de perfil (o una pública si prefieres)
        $this->renderer->renderiza('perfil', $datosUsuario);
    }
}