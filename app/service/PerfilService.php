<?php

namespace app\service;

use app\model\PartidaModel;
use app\model\UsuarioModel;

class PerfilService
{
    private const LIMITE_HISTORIAL_PUBLICO = 200;

    private UsuarioModel $usuarioModel;
    private PartidaModel $partidaModel;

    public function __construct(UsuarioModel $usuarioModel, PartidaModel $partidaModel)
    {
        $this->usuarioModel = $usuarioModel;
        $this->partidaModel = $partidaModel;
    }

    public function obtenerPerfil(string $nombre_usuario, bool $es_propio): ?array
    {
        if ($nombre_usuario === '') {
            return null;
        }

        $usuario = $this->usuarioModel->buscarPorNombreUsuario($nombre_usuario);

        if (!$usuario) {
            return null;
        }

        $id_usuario = $usuario['id_usuario'];

        return [
            'es_propio' => $es_propio,
            'nombre_usuario' => $usuario['nombre_usuario'],
            'nombre_completo' => $usuario['nombre_completo'],
            'mail' => $es_propio ? $usuario['mail'] : null,
            'anio_nacimiento' => $usuario['anio_nacimiento'],
            'sexo' => $usuario['sexo'],
            'pais' => $usuario['pais'],
            'ciudad' => $usuario['ciudad'],
            'foto_perfil' => $usuario['foto_perfil'],
            'puntaje_mensual' => $this->partidaModel->obtenerPuntajeMensualUsuario($id_usuario),
            'puntaje_total' => $this->partidaModel->obtenerPuntajeTotalUsuario($id_usuario),
            'cantidad_partidas' => $this->partidaModel->obtenerCantidadPartidasUsuario($id_usuario),
            'historial' => $es_propio
                ? []
                : $this->partidaModel->obtenerHistorialUsuario($id_usuario, self::LIMITE_HISTORIAL_PUBLICO)
        ];
    }
}
