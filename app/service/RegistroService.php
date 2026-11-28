<?php

namespace app\service;

use app\model\UsuarioModel;

class RegistroService
{
    private const CAMPOS_OBLIGATORIOS = [
        'nombre_completo' => 'Nombre completo',
        'anio_nacimiento' => 'Año de nacimiento',
        'sexo' => 'Sexo',
        'pais' => 'País',
        'ciudad' => 'Ciudad',
        'mail' => 'Correo electrónico',
        'nombre_usuario' => 'Nombre de usuario',
        'contrasenia' => 'Contraseña',
        'repetir_contrasenia' => 'Repetir contraseña'
    ];
    private const AVATAR = 'public/img/avatar-default.svg';
    private const RUTA_FOTOS = 'public/uploads/perfiles/';

    private UsuarioModel $usuarioModel;
    private \Mailer $mailer;

    public function __construct(UsuarioModel $usuarioModel, \Mailer $mailer)
    {
        $this->usuarioModel = $usuarioModel;
        $this->mailer = $mailer;
    }

    public function registrar(array $formulario, array $foto, string $urlActivacion): ?array
    {
        $datos = [];
        $camposVacios = [];

        foreach (self::CAMPOS_OBLIGATORIOS as $campo => $etiqueta) {
            $valor = $formulario[$campo] ?? '';
            $valor = is_string($valor) ? $valor : '';

            // Las contraseñas conservan sus espacios y el sexo conserva el valor seleccionado.
            if (!in_array($campo, ['sexo', 'contrasenia', 'repetir_contrasenia'], true)) {
                $valor = trim($valor);
            }

            $datos[$campo] = $valor;
            if ($valor === '') {
                $camposVacios[] = $etiqueta;
            }
        }

        $contrasenia = $datos['contrasenia'];
        $repetirContrasenia = $datos['repetir_contrasenia'];
        unset($datos['contrasenia'], $datos['repetir_contrasenia']);

        $error = $this->validarRegistro($datos, $camposVacios, $contrasenia, $repetirContrasenia);
        if ($error !== null) {
            return ['error' => $error, 'formulario' => $datos];
        }

        $hash = password_hash($contrasenia, PASSWORD_BCRYPT);
        $token = bin2hex(random_bytes(16));
        $rutaFoto = $this->guardarFoto($foto);

        if ($rutaFoto === null) {
            return [
                'error' => 'La foto de perfil tiene que ser una imagen (JPG, PNG, GIF o WEBP).',
                'formulario' => $datos
            ];
        }

        $registroExitoso = $this->usuarioModel->registrarUsuario($datos + [
                'contrasenia' => $hash,
                'foto_perfil' => $rutaFoto,
                'token_validacion' => $token
            ]);

        if (!$registroExitoso) {
            if ($rutaFoto !== self::AVATAR) {
                @unlink(__DIR__ . '/../../' . $rutaFoto);
            }

            return ['error' => 'No se pudo completar el registro. Intentá de nuevo.', 'formulario' => $datos];
        }

        $this->enviarActivacion($datos['mail'], $datos['nombre_completo'], $urlActivacion, $token);

        return null;
    }

    public function activarCuenta($token): bool
    {
        return is_string($token) && $token !== '' && $this->usuarioModel->activarUsuario($token);
    }

    private function validarRegistro(array $datos, array $camposVacios, string $contrasenia, string $repetirContrasenia): ?string
    {
        if ($camposVacios) {
            return 'Completá los campos obligatorios: ' . implode(', ', $camposVacios) . '.';
        }

        if (!filter_var($datos['mail'], FILTER_VALIDATE_EMAIL)) {
            return 'El correo electrónico no tiene un formato válido.';
        }

        if ($contrasenia !== $repetirContrasenia) {
            return 'Error de seguridad: Las contraseñas enviadas no coinciden.';
        }

        if ($this->usuarioModel->existeMail($datos['mail'])) {
            return 'Ese correo ya está registrado. Probá iniciar sesión.';
        }

        if ($this->usuarioModel->existeNombreUsuario($datos['nombre_usuario'])) {
            return 'Ese nombre de usuario ya está en uso. Elegí otro.';
        }

        return null;
    }

    private function guardarFoto(array $foto): ?string
    {
        if (($foto['error'] ?? null) !== UPLOAD_ERR_OK) {
            return self::AVATAR;
        }

        $extension = strtolower(pathinfo($foto['name'], PATHINFO_EXTENSION));
        $infoImagen = @getimagesize($foto['tmp_name']);
        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($extension, $extensionesPermitidas, true)
            || $infoImagen === false
            || !in_array($infoImagen['mime'], $tiposPermitidos, true)) {
            return null;
        }

        $raiz = __DIR__ . '/../../';
        $directorio = $raiz . self::RUTA_FOTOS;
        if (!is_dir($directorio) && !@mkdir($directorio, 0777, true) && !is_dir($directorio)) {
            return self::AVATAR;
        }

        $ruta = self::RUTA_FOTOS . uniqid('perfil_') . '.' . $extension;

        return move_uploaded_file($foto['tmp_name'], $raiz . $ruta) ? $ruta : self::AVATAR;
    }

    private function enviarActivacion(string $mail, string $nombreCompleto, string $urlActivacion, string $token): void
    {
        $nombre = htmlspecialchars($nombreCompleto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $enlace = htmlspecialchars($urlActivacion . '?token=' . $token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $asunto = 'Activa tu cuenta en el Juego de Preguntas';
        $mensaje = "<h2>Hola $nombre</h2>";
        $mensaje .= '<p>Gracias por registrarte. Para activar tu cuenta y empezar a jugar, hacé clic en el siguiente enlace:</p>';
        $mensaje .= "<a href='$enlace'>Haz clic aquí para activar tu cuenta</a>";

        $this->mailer->enviarCorreo($mail, $asunto, $mensaje);
    }
}
