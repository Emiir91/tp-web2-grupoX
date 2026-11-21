<?php

namespace app\controller;

class RegistroController
{
    private $usuarioModel;
    private $renderer;
    private $mailer;

    public function __construct($usuarioModel, $renderer, $mailer)
    {
        $this->usuarioModel = $usuarioModel;
        $this->renderer = $renderer;
        $this->mailer = $mailer;
    }

    public function index()
    {
        $datosParaVista = ['requiere_mapa' => true];
        echo $this->renderer->renderiza('registro', $datosParaVista);
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

            // Datos que se vuelven a cargar en el formulario si hay algún error
            $datosFormulario = [
                'nombre_completo' => $nombreCompleto,
                'anio_nacimiento' => $anioNacimiento,
                'is_masculino' => ($sexo === 'Masculino'),
                'is_femenino' => ($sexo === 'Femenino'),
                'is_prefiero_no' => ($sexo === 'Prefiero no cargarlo'),
                'pais' => $pais,
                'ciudad' => $ciudad,
                'mail' => $mail,
                'nombre_usuario' => $nombreUsuario,
                'requiere_mapa' => true
            ];

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
                    'nombre_usuario' => $nombreUsuario,
                    'requiere_mapa' => true
                ];
                echo $this->renderer->renderiza('registro', $datosParaVista);
                return;
            }

            // El mail y el nombre de usuario no pueden estar repetidos.
            // Se controla antes de subir la foto para no dejar imágenes sin usuario.
            if ($this->usuarioModel->existeMail($mail)) {
                $datosFormulario['error_backend'] = "Ese correo ya está registrado. Probá iniciar sesión.";
                echo $this->renderer->renderiza('registro', $datosFormulario);
                return;
            }

            if ($this->usuarioModel->existeNombreUsuario($nombreUsuario)) {
                $datosFormulario['error_backend'] = "Ese nombre de usuario ya está en uso. Elegí otro.";
                echo $this->renderer->renderiza('registro', $datosFormulario);
                return;
            }

            $hashContrasenia = password_hash($contrasenia, PASSWORD_BCRYPT);
            $tokenValidacion = bin2hex(random_bytes(16));
            $rutaFotoBaseDeDatos = 'public/uploads/default.png';

            if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
                // Solo se permiten imágenes: se controla la extensión y también el contenido real del archivo
                $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

                $extension = strtolower(pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));
                $infoImagen = @getimagesize($_FILES['foto_perfil']['tmp_name']);

                if (!in_array($extension, $extensionesPermitidas, true)
                    || $infoImagen === false
                    || !in_array($infoImagen['mime'], $tiposPermitidos, true)) {
                    $datosFormulario['error_backend'] = "La foto de perfil tiene que ser una imagen (JPG, PNG, GIF o WEBP).";
                    echo $this->renderer->renderiza('registro', $datosFormulario);
                    return;
                }

                $directorioDestino = __DIR__ . '/../../public/uploads/perfiles/';

                if (!file_exists($directorioDestino)) {
                    mkdir($directorioDestino, 0777, true);
                }

                $nombreArchivo = uniqid('perfil_') . '.' . $extension;

                if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $directorioDestino . $nombreArchivo)) {
                    $rutaFotoBaseDeDatos = 'public/uploads/perfiles/' . $nombreArchivo;
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
                $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $host = $_SERVER['HTTP_HOST'];

                $enlaceActivacion = $protocolo . "://" . $host . BASE_URL . "/registro/activarCuenta?token=" . $tokenValidacion;

                $asunto = "Activa tu cuenta en el Juego de Preguntas";
                $mensaje = "<h2>Hola " . $nombreCompleto . "</h2>";
                $mensaje .= "<p>Gracias por registrarte. Para activar tu cuenta y empezar a jugar, hacé clic en el siguiente enlace:</p>";
                $mensaje .= "<a href='" . $enlaceActivacion . "'>Haz clic aquí para activar tu cuenta</a>";

                $this->mailer->enviarCorreo($mail, $asunto, $mensaje);

                header("Location: " . BASE_URL . "/login/index?mensaje=revisar_mail");
                exit();
            }

            $datosFormulario['error_backend'] = "No se pudo completar el registro. Intentá de nuevo.";
            echo $this->renderer->renderiza('registro', $datosFormulario);
            return;
        }
    }

    public function activarCuenta()
    {
        $token = $_GET['token'] ?? '';

        if (!empty($token)) {
            $cuentaActivada = $this->usuarioModel->activarUsuario($token);

            if ($cuentaActivada) {
                header("Location: " . BASE_URL . "/login/index?mensaje=cuenta_activada");
                exit();
            }
        }

        echo "Error: Enlace de activación inválido o la cuenta ya fue activada.";
    }
}
