<?php

namespace app\controller;

use app\service\LoginService;

class LoginController
{
    private $renderer;
    private LoginService $loginService;

    public function __construct(LoginService $loginService, $renderer)
    {
        $this->loginService = $loginService;
        $this->renderer = $renderer;
    }

    public function index()
    {
        $mensaje = $_GET['mensaje'] ?? '';
        $datosParaVista = [];

        if ($mensaje === 'revisar_mail') {
            $datosParaVista['alerta_info'] = "Registro exitoso. Por favor, revisá tu casilla de correo (y la carpeta de SPAM) para activar tu cuenta.";
        } elseif ($mensaje === 'cuenta_activada') {
            $datosParaVista['alerta_exito'] = "¡Tu cuenta fue activada correctamente! Ya podés iniciar sesión.";
        }

        $this->renderer->renderiza('login', $datosParaVista);
    }

    public function procesarLogin()
    {
        $resultado = $this->loginService->autenticar(
            $_POST['usuario_o_mail'] ?? '',
            $_POST['contrasenia'] ?? ''
        );

        if (isset($resultado['error'])) {
            $mensajes = [
                LoginService::USUARIO_NO_ENCONTRADO => 'No estás registrado con este correo o usuario. ¡Creá una cuenta!',
                LoginService::CONTRASENIA_INCORRECTA => 'La contraseña es incorrecta. Intentá de nuevo.',
                LoginService::CUENTA_SIN_ACTIVAR => 'Tu cuenta aún no está activada. Por favor, revisá tu correo (y la carpeta SPAM).'
            ];
            $this->renderer->renderiza('login', ['alerta_error' => $mensajes[$resultado['error']]]);
            return;
        }

        $usuario = $resultado['usuario'];
        $_SESSION['usuario_logueado'] = true;
        $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];
        $_SESSION['id_rol'] = $usuario['id_rol'];
        $_SESSION['id_usuario'] = $usuario['id_usuario'];

        header("Location: " . BASE_URL . "/lobby");
        exit();
    }

    public function logout()
    {
        session_unset();
        session_destroy();
        // Al cerrar sesión volvemos a la portada (home), ya como invitado
        header("Location: " . BASE_URL . "/home");
        exit();
    }
}
