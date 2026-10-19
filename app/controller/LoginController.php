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

        // Le pedimos al modelo que verifique si los datos son correctos
        $usuario = $this->model->verificarCredenciales($usuario_o_mail, $contrasenia);

        if (!$usuario) {
            $datosParaVista = ['alerta_error' => 'Usuario o contraseña incorrectos.'];
            $this->renderer->renderiza('login', $datosParaVista);
            return;
        }

        // Si existe, verificamos si ya activó la cuenta
        if ($usuario['cuenta_validada'] == 0) {
            $datosParaVista = ['alerta_error' => 'Tu cuenta aún no está activada. Revisá tu correo electrónico.'];
            $this->renderer->renderiza('login', $datosParaVista);
            return;
        }

        // Todo correcto: creamos la sesión y mandamos al Home
        session_start();
        $_SESSION['usuario_logueado'] = true;
        $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];
        $_SESSION['id_rol'] = $usuario['id_rol'];

        header("Location: /index.php?controller=home");
        exit();
    }
}