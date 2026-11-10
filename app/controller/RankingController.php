<?php

namespace app\controller;

class RankingController
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

        // Traemos el top 10 del ranking mensual global
        $datosParaVista['ranking'] = $this->partidaModel->obtenerRankingMensualGlobal(10);

        // Renderizamos la vista independiente
        $this->renderer->renderiza('ranking', $datosParaVista);
    }
}