<?php

namespace app\controller;

class LoginController
{
    private $renderer;
    private $model;

    public function __construct($model, $renderer)
    {
        $this->model = $model;
        $this->renderer = $renderer;
    }

    public function index()
    {
        $mensaje = $_GET['mensaje'] ?? '';
        $datosParaVista = ['ocultar_layout' => true];

        if ($mensaje === 'revisar_mail') {
            $datosParaVista['alerta_info'] = "Registro exitoso. Por favor, revisá tu casilla de correo (y la carpeta de SPAM) para activar tu cuenta.";
        } elseif ($mensaje === 'cuenta_activada') {
            $datosParaVista['alerta_exito'] = "¡Tu cuenta fue activada correctamente! Ya podés iniciar sesión.";
        }

        $this->renderer->renderiza('login', $datosParaVista);
    }

    public function procesarLogin()
    {
        $usuario_o_mail = $_POST['usuario_o_mail'] ?? '';
        $contrasenia = $_POST['contrasenia'] ?? '';

        $usuario = $this->model->buscarUsuario($usuario_o_mail);

        if (!$usuario) {
            $datosParaVista = ['alerta_error' => 'No estás registrado con este correo o usuario. ¡Creá una cuenta!', 'ocultar_layout' => true];
            $this->renderer->renderiza('login', $datosParaVista);
            return;
        }

        if (!password_verify($contrasenia, $usuario['contrasenia'])) {
            $datosParaVista = ['alerta_error' => 'La contraseña es incorrecta. Intentá de nuevo.', 'ocultar_layout' => true];
            $this->renderer->renderiza('login', $datosParaVista);
            return;
        }

        if ($usuario['cuenta_validada'] == 0) {
            $datosParaVista = ['alerta_error' => 'Tu cuenta aún no está activada. Por favor, revisá tu correo (y la carpeta SPAM).', 'ocultar_layout' => true];
            $this->renderer->renderiza('login', $datosParaVista);
            return;
        }

        $_SESSION['usuario_logueado'] = true;
        $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];
        $_SESSION['id_rol'] = $usuario['id_rol'];
        $_SESSION['id_usuario'] = $usuario['id_usuario'];

        header("Location: " . BASE_URL . "/home/index");
        exit();
    }

    public function logout()
    {
        session_unset();
        session_destroy();
        header("Location: " . BASE_URL . "/login");
        exit();
    }
}
