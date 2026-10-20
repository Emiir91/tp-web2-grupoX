<?php

namespace app\controller;

class LoginController
{
    private $renderer;
    private $model; // 1. Agregamos la propiedad para el modelo

    // 2. Recibimos el modelo en el constructor
    public function __construct($model, $renderer)
    {
        $this->model = $model;
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

    // 3. Agregamos la función que procesa el formulario de login
    public function procesarLogin()
    {
        $usuario_o_mail = $_POST['usuario_o_mail'] ?? '';
        $contrasenia = $_POST['contrasenia'] ?? '';

        // 1. Le pedimos al modelo que traiga la ficha del usuario
        $usuario = $this->model->buscarUsuario($usuario_o_mail);

        // Validación A: El usuario no existe en la base de datos
        if (!$usuario) {
            $datosParaVista = ['alerta_error' => 'No estás registrado con este correo o usuario. ¡Creá una cuenta!'];
            $this->renderer->renderiza('login', $datosParaVista);
            return;
        }

        // Validación B: El usuario existe, pero puso mal la clave
        if (!password_verify($contrasenia, $usuario['contrasenia'])) {
            $datosParaVista = ['alerta_error' => 'La contraseña es incorrecta. Intentá de nuevo.'];
            $this->renderer->renderiza('login', $datosParaVista);
            return;
        }

        // Validación C: Todo es correcto, pero no validó el mail
        if ($usuario['cuenta_validada'] == 0) {
            $datosParaVista = ['alerta_error' => 'Tu cuenta aún no está activada. Por favor, revisá tu correo (y la carpeta SPAM).'];
            $this->renderer->renderiza('login', $datosParaVista);
            return;
        }

        // ¡Pasó todas las pruebas! Creamos la sesión y mandamos al Home

        $_SESSION['usuario_logueado'] = true;
        $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];
        $_SESSION['id_rol'] = $usuario['id_rol'];

        header("Location: " . BASE_URL . "/home/index");
        exit();
    }
}