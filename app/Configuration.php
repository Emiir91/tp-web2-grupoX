<?php

namespace app;

use app\controller\EquipoController;
use MustacheRender;
use MyDatabase;
use Router;

require_once("helper/Autoloader.php");



class Configuration
{

    public function __construct()
    {
    }

    // Instanciamos nuestro nuevo controlador y le pasamos el motor de vistas (Mustache)
    public function getEquipoController()
    {
        return new EquipoController(
            $this->gerRender()
        );
    }


   //los privados

    private function getDatabase()
    {
        $config = parse_ini_file("config/config.ini");

        return new MyDatabase($config["db_host"],
            $config["db_user"],
            $config["db_pass"],
            $config["db_name"]
        );
    }

    private function gerRender()
    {
        return new MustacheRender();
    }


    public function getRouter()
    {
        return new Router($this, "equipo", "index");
    }
}
