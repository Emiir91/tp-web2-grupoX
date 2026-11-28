<?php

namespace app\controller;

class RankingController
{
    private $renderer;
    private $partidaModel;

    public function __construct($renderer, $partidaModel)
    {
        $this->renderer = $renderer;
        $this->partidaModel = $partidaModel;
    }

    public function index()
    {
        if (empty($_SESSION['usuario_logueado'])) {
            header("Location: " . BASE_URL . "/login");
            exit();
        }

        $nombre_usuario = $_SESSION['nombre_usuario'] ?? null;
        $ranking = [];

        foreach ($this->partidaModel->obtenerRankingMensualGlobal() as $jugador) {
            $jugador['posicion'] = count($ranking) + 1;
            $jugador['url_perfil'] = BASE_URL . '/perfil/publico?usuario='
                . rawurlencode($jugador['usuario']);
            $jugador['soy_yo'] = $jugador['usuario'] === $nombre_usuario;
            $ranking[] = $jugador;
        }

        $this->renderer->renderiza('ranking', ['ranking' => $ranking]);
    }
}