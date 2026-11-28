<?php

namespace app\service;

use app\model\UsuarioModel;

class LoginService
{
    public const USUARIO_NO_ENCONTRADO = 'usuario_no_encontrado';
    public const CONTRASENIA_INCORRECTA = 'contrasenia_incorrecta';
    public const CUENTA_SIN_ACTIVAR = 'cuenta_sin_activar';

    private UsuarioModel $usuarioModel;

    public function __construct(UsuarioModel $usuarioModel)
    {
        $this->usuarioModel = $usuarioModel;
    }

    public function autenticar($usuario_o_mail, $contrasenia): array
    {
        if (!is_string($usuario_o_mail)) {
            return ['error' => self::USUARIO_NO_ENCONTRADO];
        }

        $usuario = $this->usuarioModel->buscarUsuario($usuario_o_mail);

        if (!$usuario) {
            return ['error' => self::USUARIO_NO_ENCONTRADO];
        }

        if (!is_string($contrasenia) || !password_verify($contrasenia, $usuario['contrasenia'])) {
            return ['error' => self::CONTRASENIA_INCORRECTA];
        }

        if ($usuario['cuenta_validada'] == 0) {
            return ['error' => self::CUENTA_SIN_ACTIVAR];
        }

        return [
            'usuario' => [
                'nombre_usuario' => $usuario['nombre_usuario'],
                'id_rol' => $usuario['id_rol'],
                'id_usuario' => $usuario['id_usuario']
            ]
        ];
    }
}
