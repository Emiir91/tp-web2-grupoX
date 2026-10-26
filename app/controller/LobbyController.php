<?php

namespace app\controller;

class LobbyController
{
    private $renderer;

    public function __construct($renderer)
    {
        $this->renderer = $renderer;
    }

    public function index()
    {
        // 1. Barrera de seguridad: Si no está logueado, lo pateamos al login
        if (!isset($_SESSION['usuario_logueado']) || $_SESSION['usuario_logueado'] !== true) {
            header("Location: " . BASE_URL . "/login/index");
            exit();
        }

        // 2. Preparamos los datos reales que ya tenemos
        $nombreUsuario = $_SESSION['nombre_usuario']; // Esto viene real del login

        // 3. MOCKING: Simulamos los datos que todavía no existen en la BD
        $puntajeSimulado = 1250;

        $historialSimulado = [
            ['fecha' => '26/10/2026', 'resultado' => 'Victoria', 'puntos' => '+50'],
            ['fecha' => '25/10/2026', 'resultado' => 'Derrota',  'puntos' => '-10'],
            ['fecha' => '20/10/2026', 'resultado' => 'Victoria', 'puntos' => '+40']
        ];

        // 4. Empaquetamos todo para mandarlo a la vista
        $datosParaVista = [
            'nombre_usuario' => $nombreUsuario,
            'puntaje_total' => $puntajeSimulado,
            'partidas' => $historialSimulado
        ];

        // 5. Renderizamos la vista
        $this->renderer->renderiza("lobby", $datosParaVista);
    }
}