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

    // Portada pública: la ve cualquiera, esté logueado o no.
    // La vista cambia los botones según haya sesión (usuario_logueado lo agrega MustacheRender).
    public function index()
    {
        // El header solo se muestra si está logueado (para ir al perfil, al lobby o cerrar sesión).
        // A un invitado no se lo mostramos porque repetiría el botón de "Iniciar sesión".
        $estaLogueado = !empty($_SESSION['usuario_logueado']);

        $datosParaVista = [
            'ocultar_layout' => !$estaLogueado
        ];

        if ($estaLogueado) {
            $id_usuario = $_SESSION['id_usuario'];
            $nombre_usuario = $_SESSION['nombre_usuario'] ?? '';
            $datosParaVista['nombre_usuario'] = $nombre_usuario;

            if ($this->partidaModel !== null) {
                $datosParaVista['partidas'] = $this->partidaModel->obtenerHistorialUsuario($id_usuario);
                $datosParaVista['puntaje_total'] = $this->partidaModel->obtenerPuntajeTotalUsuario($id_usuario);
            }
        }

        $this->renderer->renderiza('home', $datosParaVista);
    }
}