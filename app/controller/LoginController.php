<?php

namespace app\controller;

class LoginController
{
    private $renderer;

    public function __construct($renderer)
    {
        $this->renderer = $renderer;
    }

    // Esta función dibuja la pantalla de login y chequea si hay mensajes en la URL
    public function index()
    {
        $mensaje = $_GET['mensaje'] ?? '';
        $datosParaVista = [];

        // Evaluamos qué mensaje mostrar según de dónde venga el usuario
        if ($mensaje === 'revisar_mail') {
            $datosParaVista['alerta_info'] = "Registro exitoso. Por favor, revisá tu casilla de correo (y la carpeta de SPAM) para activar tu cuenta.";
        } elseif ($mensaje === 'cuenta_activada') {
            $datosParaVista['alerta_exito'] = "¡Tu cuenta fue activada correctamente! Ya podés iniciar sesión.";
        }

        // Renderizamos la vista pasándole los mensajes si es que existen
        $this->renderer->renderiza('login', $datosParaVista);
    }
}