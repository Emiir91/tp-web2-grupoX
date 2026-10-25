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
        // Usá render() o renderiza() según cómo esté en tu clase MustacheRender
        $this->renderer->renderiza("home");
    }
}