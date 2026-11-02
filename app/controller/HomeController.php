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
        if (!isset($_SESSION['usuario_logueado']) || $_SESSION['usuario_logueado'] !== true) {
            $datosParaVista = [
                'usuario_logueado' => false
            ];
            $this->renderer->renderiza("home", $datosParaVista);
            return;
        }

        $nombreUsuario = $_SESSION['nombre_usuario'];

        $puntajeSimulado = 1250;

        $historialSimulado = [
            ['fecha' => '26/10/2026', 'resultado' => 'Victoria', 'puntos' => '+50'],
            ['fecha' => '25/10/2026', 'resultado' => 'Derrota',  'puntos' => '-10'],
            ['fecha' => '20/10/2026', 'resultado' => 'Victoria', 'puntos' => '+40']
        ];

        $datosParaVista = [
            'usuario_logueado' => true,
            'nombre_usuario' => $nombreUsuario,
            'puntaje_total' => $puntajeSimulado,
            'partidas' => $historialSimulado
        ];

        $this->renderer->renderiza("home", $datosParaVista);
    }
}
