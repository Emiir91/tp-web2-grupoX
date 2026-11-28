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
        $this->renderer->renderiza('home', [
            'ocultar_layout' => empty($_SESSION['usuario_logueado'])
        ]);
    }
}