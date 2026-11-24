<?php

require_once __DIR__ . "/../vendor/mustache/Mustache/Autoloader.php";
Mustache_Autoloader::register(__DIR__ . "/../vendor/mustache");

class MustacheRender
{
    private $mustache;
    private $viewPath;
    private $notificaciones;

    // $notificaciones: modelo que cuenta las tareas pendientes de editor y admin (globito de la barra)
    public function __construct($notificaciones = null)
    {
        $this->notificaciones = $notificaciones;
        $this->viewPath = dirname(__DIR__) . "/app/view";
        $this->mustache = new Mustache_Engine([
            "loader" => new Mustache_Loader_FilesystemLoader($this->viewPath, ["extension" => ".mustache"])
        ]);
    }

    private function versionCss($nombre)
    {
        $archivo = dirname(__DIR__) . "/public/css/" . $nombre . ".css";
        return file_exists($archivo) ? filemtime($archivo) : 1;
    }

    // Deja en $data si hay pendientes y el texto del globito (más de 9 se muestra como "9+")
    private function agregarPendientes(&$data, $panel, $cantidad)
    {
        $data['hay_pendientes_' . $panel] = $cantidad > 0;
        $data['pendientes_' . $panel] = $cantidad > 9 ? '9+' : (string) $cantidad;
        $data['pendientes_' . $panel . '_titulo'] = $cantidad === 1 ? '1 tarea pendiente' : $cantidad . ' tareas pendientes';
    }

    public function renderiza($view, $data = [])
    {
        $data['base_url'] = BASE_URL;
        $data['vista'] = $view;

        // Versión de los CSS = fecha de última modificación del archivo.
        // Así, cada vez que se edita un CSS, el navegador lo vuelve a descargar en vez de usar el de la caché.
        $data['version_css'] = $this->versionCss($view);
        $data['version_header_css'] = $this->versionCss('header');

        // En la pantalla de login no mostramos el botón "Iniciar Sesión" del header (ya estás ahí)
        $data['en_login'] = ($view === 'login');

        $data['usuario_logueado'] = isset($_SESSION['usuario_logueado']) && $_SESSION['usuario_logueado'] === true;
        $data['nombre_usuario'] = $_SESSION['nombre_usuario'] ?? '';
        $data['es_editor'] = in_array($_SESSION['id_rol'] ?? 0, [2, 3]);
        $data['es_admin'] = (($_SESSION['id_rol'] ?? 0) == 3);

        // Globito con la cantidad de tareas pendientes en los botones Editor y Admin de la barra
        if ($this->notificaciones !== null && $data['usuario_logueado']) {
            if ($data['es_editor']) {
                $this->agregarPendientes($data, 'editor', $this->notificaciones->contarPendientesEditor());
            }
            if ($data['es_admin']) {
                $this->agregarPendientes($data, 'admin', $this->notificaciones->contarPendientesAdmin());
            }
        }

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
