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

        // Traemos el ranking
        $ranking = $this->partidaModel->obtenerRankingMensualGlobal(10);

        // Le asignamos un número de posición a cada elemento (1, 2, 3...)
        $contador = 1;
        foreach ($ranking as &$jugador) {
            $jugador['posicion'] = $contador++;
        }

        $datosParaVista['ranking'] = $ranking;

        // Renderizamos la vista
        $this->renderer->renderiza('ranking', $datosParaVista);
    }
}