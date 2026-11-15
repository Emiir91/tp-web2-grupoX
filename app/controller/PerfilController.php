<?php

namespace app\controller;

class PerfilController
{
    private $renderer;
    private $model;
    private $partidaModel;

    public function __construct($model, $renderer, $partidaModel)
    {
        $this->model = $model;
        $this->renderer = $renderer;
        $this->partidaModel = $partidaModel;
    }

    // Perfil propio (solo logueados): muestra todos los datos, incluido el mail
    public function index()
    {
        // Si no está logueado, lo patea al login
        if (empty($_SESSION['usuario_logueado'])) {
            header("Location: " . BASE_URL . "/login");
            exit();
        }

        $usuario = $this->model->buscarPorNombreUsuario($_SESSION['nombre_usuario']);

        $this->mostrarPerfil($usuario, true);
    }

    // Perfil público: perfil/publico?usuario=xxx
    // Lo puede ver cualquiera (es a donde lleva el QR y el ranking). No muestra el mail.
    public function publico()
    {
        $nombre_usuario = trim($_GET['usuario'] ?? '');

        // Si estás logueado y es tu propio perfil, te mandamos a tu perfil completo
        if (!empty($_SESSION['usuario_logueado']) && $nombre_usuario === ($_SESSION['nombre_usuario'] ?? null)) {
            header("Location: " . BASE_URL . "/perfil");
            exit();
        }

        $usuario = $nombre_usuario !== '' ? $this->model->buscarPorNombreUsuario($nombre_usuario) : false;

        $this->mostrarPerfil($usuario, false);
    }

    private function mostrarPerfil($usuario, $es_propio)
    {
        if (!$usuario) {
            $this->renderer->renderiza('perfil', ['perfil_no_encontrado' => true]);
            return;
        }

        $url_perfil_publico = $this->urlPerfilPublico($usuario['nombre_usuario']);

        // Pasamos solo los datos que se muestran (nunca la contraseña ni el token).
        // Van dentro de "perfil" porque MustacheRender pisa "nombre_usuario" con el usuario logueado.
        $perfil = [
            'es_propio' => $es_propio,
            'nombre_usuario' => $usuario['nombre_usuario'],
            'nombre_completo' => $usuario['nombre_completo'],
            'mail' => $es_propio ? $usuario['mail'] : null,
            'anio_nacimiento' => $usuario['anio_nacimiento'],
            'sexo' => $usuario['sexo'],
            'pais' => $usuario['pais'],
            'ciudad' => $usuario['ciudad'],
            'foto_perfil' => $usuario['foto_perfil'],
            'puntaje_total' => $this->partidaModel->obtenerPuntajeTotalUsuario($usuario['id_usuario']),
            'cantidad_partidas' => $this->partidaModel->obtenerCantidadPartidasUsuario($usuario['id_usuario']),
            'url_perfil_publico' => $url_perfil_publico,
            'url_qr' => "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($url_perfil_publico)
        ];

        $this->renderer->renderiza('perfil', [
            'perfil' => $perfil,
            'requiere_mapa' => true,
            'requiere_perfil' => true
        ]);
    }

    // Arma la dirección completa del perfil público con la dirección actual del sitio,
    // así el QR funciona igual en el hosting que en XAMPP (o desde el celular con la IP de la PC).
    private function urlPerfilPublico($nombre_usuario)
    {
        $esHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        $sitio = ($esHttps ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL;

        return $sitio . "/perfil/publico?usuario=" . rawurlencode($nombre_usuario);
    }
}
