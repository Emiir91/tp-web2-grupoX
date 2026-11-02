<?php

namespace app\controller;

class HomeController
{
    private $renderer;
    private $partidaModel;

    public function __construct($renderer, $partidaModel = null)
    {
        $this->renderer = $renderer;
        $this->partidaModel = $partidaModel;
    }

    public function index()
    {
        $datosParaVista = [];

        if (isset($_SESSION['usuario_logueado']) && $_SESSION['usuario_logueado'] === true) {
            $id_usuario = $_SESSION['id_usuario'];
            
            $datosParaVista['partidas'] = $this->partidaModel->obtenerHistorialUsuario($id_usuario);
            $datosParaVista['puntaje_total'] = $this->partidaModel->obtenerPuntajeTotalUsuario($id_usuario);
        }

        $this->renderer->renderiza('home', $datosParaVista);
    }
}