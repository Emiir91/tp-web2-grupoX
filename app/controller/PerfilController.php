<?php

namespace app\controller;

use app\service\PerfilService;

class PerfilController
{
    private $renderer;
    private PerfilService $perfilService;

    public function __construct(PerfilService $perfilService, $renderer)
    {
        $this->perfilService = $perfilService;
        $this->renderer = $renderer;
    }

    // Perfil propio (solo logueados): muestra todos los datos, incluido el mail
    public function index()
    {
        // Si no está logueado, lo patea al login
        if (empty($_SESSION['usuario_logueado'])) {
            header("Location: " . BASE_URL . "/login");
            exit();
        }

        $perfil = $this->perfilService->obtenerPerfil($_SESSION['nombre_usuario'] ?? '', true);
        $this->mostrarPerfil($perfil);
    }

    // Perfil público: perfil/publico?usuario=xxx
    // Lo puede ver cualquiera (es a donde lleva el QR y el ranking). No muestra el mail.
    public function publico()
    {
        $usuario = $_GET['usuario'] ?? '';
        $nombre_usuario = is_string($usuario) ? trim($usuario) : '';

        // Si estás logueado y es tu propio perfil, te mandamos a tu perfil completo
        if (!empty($_SESSION['usuario_logueado']) && $nombre_usuario === ($_SESSION['nombre_usuario'] ?? null)) {
            header("Location: " . BASE_URL . "/perfil");
            exit();
        }

        $perfil = $this->perfilService->obtenerPerfil($nombre_usuario, false);
        $this->mostrarPerfil($perfil);
    }

    private function mostrarPerfil(?array $perfil)
    {
        if ($perfil === null) {
            $this->renderer->renderiza('perfil', ['perfil_no_encontrado' => true]);
            return;
        }

        $url_perfil_publico = $this->urlPerfilPublico($perfil['nombre_usuario']);
        $perfil['foto_perfil'] = $this->fotoPerfil($perfil['foto_perfil']);
        $perfil['url_perfil_publico'] = $url_perfil_publico;
        $perfil['url_qr'] = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data='
            . urlencode($url_perfil_publico);

        $this->renderer->renderiza('perfil', [
            'perfil' => $perfil,
            'requiere_mapa' => true,
            'requiere_perfil' => true
        ]);
    }

    // Si el usuario no tiene foto o el archivo no existe (por ejemplo, la ruta vieja default.png),
    // se muestra el avatar por defecto con el estilo del juego.
    private function fotoPerfil($ruta)
    {
        $rutaDefecto = 'public/img/avatar-default.svg';

        if (empty($ruta) || !file_exists(__DIR__ . '/../../' . $ruta)) {
            return $rutaDefecto;
        }

        return $ruta;
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
