<?php

namespace app\controller;

class RegistroController
{
    private $usuarioModel;
    private $renderer;
    private $mailer; // 1. Declaramos la propiedad para el Mailer

    // 2. Recibimos el Mailer como tercer parámetro
    public function __construct($usuarioModel, $renderer, $mailer)
    {
        $this->usuarioModel = $usuarioModel;
        $this->renderer = $renderer;
        $this->mailer = $mailer;
    }

    public function index()
    {
        echo $this->renderer->renderiza('registro');
    }

    public function procesarRegistro()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $nombreCompleto = $_POST['nombre_completo'] ?? '';
            $anioNacimiento = $_POST['anio_nacimiento'] ?? '';
            $sexo = $_POST['sexo'] ?? '';
            $pais = $_POST['pais'] ?? '';
            $ciudad = $_POST['ciudad'] ?? '';
            $mail = $_POST['mail'] ?? '';
            $nombreUsuario = $_POST['nombre_usuario'] ?? '';
            $contrasenia = $_POST['contrasenia'] ?? '';
            $repetirContrasenia = $_POST['repetir_contrasenia'] ?? '';

            if ($contrasenia !== $repetirContrasenia) {
                $datosParaVista = [
                    'error_backend' => "Error de seguridad: Las contraseñas enviadas no coinciden.",
                    'nombre_completo' => $nombreCompleto,
                    'anio_nacimiento' => $anioNacimiento,
                    'is_masculino' => ($sexo === 'Masculino'),
                    'is_femenino' => ($sexo === 'Femenino'),
                    'is_prefiero_no' => ($sexo === 'Prefiero no cargarlo'),
                    'pais' => $pais,
                    'ciudad' => $ciudad,
                    'mail' => $mail,
                    'nombre_usuario' => $nombreUsuario
                ];
                echo $this->renderer->renderiza('registro', $datosParaVista);
                return;
            }

            $hashContrasenia = password_hash($contrasenia, PASSWORD_BCRYPT);
            $tokenValidacion = bin2hex(random_bytes(16));
            $rutaFotoBaseDeDatos = 'public/images/default.png';

            if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
                $directorioDestino = __DIR__ . '/../../public/images/perfiles/';

                if (!file_exists($directorioDestino)) {
                    mkdir($directorioDestino, 0777, true);
                }

                $extension = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
                $nombreArchivo = uniqid('perfil_') . '.' . $extension;

                if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $directorioDestino . $nombreArchivo)) {
                    $rutaFotoBaseDeDatos = 'public/images/perfiles/' . $nombreArchivo;
                }
            }

            $datosUsuario = [
                'nombre_completo' => $nombreCompleto,
                'anio_nacimiento' => $anioNacimiento,
                'sexo' => $sexo,
                'pais' => $pais,
                'ciudad' => $ciudad,
                'mail' => $mail,
                'contrasenia' => $hashContrasenia,
                'nombre_usuario' => $nombreUsuario,
                'foto_perfil' => $rutaFotoBaseDeDatos,
                'token_validacion' => $tokenValidacion
            ];

            $registroExitoso = $this->usuarioModel->registrarUsuario($datosUsuario);

            if ($registroExitoso) {
                // Armamos la URL dinámica, validando si usa HTTP o HTTPS
                $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $host = $_SERVER['HTTP_HOST'];

                // Usamos la constante BASE_URL en lugar de adivinar rutas
                $enlaceActivacion = $protocolo . "://" . $host . BASE_URL . "/registro/activarCuenta?token=" . $tokenValidacion;

                $asunto = "Activa tu cuenta en el Juego de Preguntas";
                $mensaje = "<h2>Hola " . $nombreCompleto . "</h2>";
                $mensaje .= "<p>Gracias por registrarte. Para activar tu cuenta y empezar a jugar, hacé clic en el siguiente enlace:</p>";
                $mensaje .= "<a href='" . $enlaceActivacion . "'>Haz clic aquí para activar tu cuenta</a>";

                // 3. Usamos nuestro Helper en lugar del mail() nativo
                $this->mailer->enviarCorreo($mail, $asunto, $mensaje);

                header("Location: " . BASE_URL . "/login/index?mensaje=revisar_mail");
                exit();
            } else {
                // Acá podrías enviar a una vista de error si el mail ya está registrado
            }
        }
    }

    public function activarCuenta()
    {
        $token = $_GET['token'] ?? '';

        if (!empty($token)) {
            $cuentaActivada = $this->usuarioModel->activarUsuario($token);

            if ($cuentaActivada) {
                // 4. Limpiamos la ruta hardcodeada y usamos la URL amigable
                header("Location: " . BASE_URL . "/login/index?mensaje=cuenta_activada");
                exit();
            }
        }

        echo "Error: Enlace de activación inválido o la cuenta ya fue activada.";
    }
}