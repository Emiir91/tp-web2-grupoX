<?php

namespace app\controller;
class RegistroController
{
    private $usuarioModel;
    private $renderer;

    // Recibimos el Modelo y el motor de Mustache por inyección de dependencias
    public function __construct($usuarioModel, $renderer)
    {
        $this->usuarioModel = $usuarioModel;
        $this->renderer = $renderer;
    }

    // Esta función solo se encarga de dibujar el formulario vacío la primera vez
    public function index()
    {
        echo $this->renderer->renderiza('registro');
    }
    public function procesarRegistro()
    {
        // Solo actuamos si el formulario realmente se envió por el método POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // 1. Recolectar datos de forma segura
            $nombreCompleto = $_POST['nombre_completo'] ?? '';
            $anioNacimiento = $_POST['anio_nacimiento'] ?? '';
            $sexo = $_POST['sexo'] ?? '';
            $pais = $_POST['pais'] ?? '';
            $ciudad = $_POST['ciudad'] ?? '';
            $mail = $_POST['mail'] ?? '';
            $nombreUsuario = $_POST['nombre_usuario'] ?? '';
            $contrasenia = $_POST['contrasenia'] ?? '';
            $repetirContrasenia = $_POST['repetir_contrasenia'] ?? '';

            // 2. Validación Backend (El plan B por si desactivan el JavaScript)
            if ($contrasenia !== $repetirContrasenia) {
                // Preparamos los datos para que el Mustache "recuerde" lo que el usuario escribió
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
                // Volvemos a dibujar la vista con los datos y el error
                echo $this->renderer->renderiza('registro', $datosParaVista);
                return; // Cortamos la ejecución acá
            }

            // 3. Encriptar la contraseña (Regla de oro: jamás se guarda en texto plano)
            $hashContrasenia = password_hash($contrasenia, PASSWORD_BCRYPT);

            // 4. Generar el Token aleatorio para el link de validación por mail
            $tokenValidacion = bin2hex(random_bytes(16));

            // 5. Procesar la Foto de Perfil
            $rutaFotoBaseDeDatos = 'public/images/default.png'; // Ruta por si algo falla o no suben nada

            if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
                $directorioDestino = __DIR__ . '/../../public/images/perfiles/';

                // Si la carpeta 'perfiles' no existe, PHP la crea automáticamente
                if (!file_exists($directorioDestino)) {
                    mkdir($directorioDestino, 0777, true);
                }

                // Extraemos la extensión (.jpg, .png) y le inventamos un nombre único para que no se pisen
                $extension = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
                $nombreArchivo = uniqid('perfil_') . '.' . $extension;

                // Movemos la imagen de la memoria temporal al disco duro del servidor
                if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $directorioDestino . $nombreArchivo)) {
                    // Si se movió bien, guardamos esta ruta para la base de datos
                    $rutaFotoBaseDeDatos = 'public/images/perfiles/' . $nombreArchivo;
                }
            }

            // 6. Empaquetar todo en un arreglo limpio para mandárselo al Modelo
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

            // 7. Le pasamos la pelota al Modelo para que haga el INSERT en MySQL
            $registroExitoso = $this->usuarioModel->registrarUsuario($datosUsuario);

            // 8. Decidir qué hacer según la respuesta del Modelo
            if ($registroExitoso) {
                // LA VUELTA DE TUERCA: Armamos la URL dinámicamente.
                // $_SERVER['HTTP_HOST'] detecta si estás en 'localhost' o en tu dominio de InfinityFree.
                $host = $_SERVER['HTTP_HOST'];

                // Si estás en local, agregamos la carpeta. Si estás en Infinity, la raíz es directa.
                $rutaProyecto = ($host === 'localhost') ? '/mvc-sept-2026' : '';

                $enlaceActivacion = "http://" . $host . BASE_URL . "/registro/activarCuenta?token=" . $tokenValidacion;
                // Armamos el correo
                $asunto = "Activa tu cuenta en el Juego de Preguntas";
                $mensaje = "<h2>Hola " . $nombreCompleto . "</h2>";
                $mensaje .= "<p>Gracias por registrarte. Para activar tu cuenta y empezar a jugar, hacé clic en el siguiente enlace:</p>";
                $mensaje .= "<a href='" . $enlaceActivacion . "'>Haz clic aquí para activar tu cuenta</a>";

                // Cabeceras obligatorias para que PHP envíe el mail en formato HTML sin usar librerías
                $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
                $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                $cabeceras .= 'From: no-reply@' . $host . "\r\n";

                // Disparamos la función nativa de PHP (En XAMPP dará error silencioso, en InfinityFree se enviará)
                @mail($mail, $asunto, $mensaje, $cabeceras);

                // Redirigimos a una vista (que podés crear) avisando que revise el mail
                // Por ahora lo mandamos al login con un aviso
                header("Location: " . BASE_URL . "/login/index?mensaje=revisar_mail");                exit();
            } else {}
        }
    }

    public function activarCuenta()
    {
        // 1. Recibimos el token que viene pegado en la URL del mail
        $token = $_GET['token'] ?? '';

        if (!empty($token)) {
            // 2. Le pedimos al Modelo que busque este token y cambie la cuenta a validada (1)
            $cuentaActivada = $this->usuarioModel->activarUsuario($token);

            if ($cuentaActivada) {
                // Éxito: Lo mandamos al login avisando que ya puede entrar
                header("Location: /mvc-sept-2026/index.php?controller=login&mensaje=cuenta_activada");
                exit();
            }
        }

        // Si el token no existe o falló
        echo "Error: Enlace de activación inválido o la cuenta ya fue activada.";
    }
}