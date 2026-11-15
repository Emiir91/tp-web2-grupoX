<?php

namespace app\controller;

class HomeController
{
    private $renderer;

    public function __construct($renderer)
    {
        $this->renderer = $renderer;
    }

    // Portada pública: la ve cualquiera, esté logueado o no.
    // La vista cambia los botones según haya sesión (usuario_logueado lo agrega MustacheRender).
    public function index()
    {
        // El header solo se muestra si está logueado (para ir al perfil, al lobby o cerrar sesión).
        // A un invitado no se lo mostramos porque repetiría el botón de "Iniciar sesión".
        $estaLogueado = !empty($_SESSION['usuario_logueado']);

        $this->renderer->renderiza('home', ['ocultar_layout' => !$estaLogueado]);
    }
}
