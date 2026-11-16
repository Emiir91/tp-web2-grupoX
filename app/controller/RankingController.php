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
        // Si no está logueado, no puede ver esta pantalla: lo mandamos al login
        if (empty($_SESSION['usuario_logueado'])) {
            header("Location: " . BASE_URL . "/login");
            exit();
        }

        $datosParaVista = [];

        // Traemos el ranking
        $ranking = $this->partidaModel->obtenerRankingMensualGlobal(10);

        // Le asignamos un número de posición a cada elemento (1, 2, 3...),
        // el link a su perfil público y marcamos cuál sos vos
        $contador = 1;
        foreach ($ranking as &$jugador) {
            $jugador['posicion'] = $contador++;
            $jugador['url_perfil'] = BASE_URL . "/perfil/publico?usuario=" . rawurlencode($jugador['usuario']);
            $jugador['soy_yo'] = ($jugador['usuario'] === ($_SESSION['nombre_usuario'] ?? null));
        }
        unset($jugador);

        $datosParaVista['ranking'] = $ranking;

        // Renderizamos la vista
        $this->renderer->renderiza('ranking', $datosParaVista);
    }
}