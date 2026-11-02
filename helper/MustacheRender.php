<?php

require_once __DIR__ . "/../vendor/mustache/Mustache/Autoloader.php";
Mustache_Autoloader::register(__DIR__ . "/../vendor/mustache");

class MustacheRender
{
    private $mustache;
    private $viewPath;

    public function __construct()
    {
        $this->viewPath = dirname(__DIR__) . "/app/view";
        $this->mustache = new Mustache_Engine([
            "loader" => new Mustache_Loader_FilesystemLoader($this->viewPath, ["extension" => ".mustache"])
        ]);
    }

    public function renderiza($view, $data = [])
    {
        $data['base_url'] = BASE_URL;
        $data['vista'] = $view;

        $data['usuario_logueado'] = isset($_SESSION['usuario_logueado']) && $_SESSION['usuario_logueado'] === true;
        $data['nombre_usuario'] = $_SESSION['nombre_usuario'] ?? '';

        $data['requiere_mapa'] = $data['requiere_mapa'] ?? false;
        $data['requiere_perfil'] = $data['requiere_perfil'] ?? false;

        $ocultarLayout = $data['ocultar_layout'] ?? false;

        echo $this->mustache->render('head', $data);

        if (!$ocultarLayout) {
            echo $this->mustache->render('navbar', $data);
        }

        echo $this->mustache->render($view . "View", $data);

        if (!$ocultarLayout) {
            echo $this->mustache->render('footer', $data);
        }

        echo $this->mustache->render('scripts', $data);
    }
}
