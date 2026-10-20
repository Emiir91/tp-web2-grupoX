<?php

namespace app;

use app\controller\EquipoController;

use app\controller\RegistroController;
use app\controller\LoginController;
use app\controller\HomeController;
use app\model\UsuarioModel;

use MustacheRender;
use MyDatabase;

use Router;
use Request;

require_once("helper/Autoloader.php");



class Configuration
{
    private $config;
    public function __construct()
    {
        // 1. Leemos el archivo UNA sola vez para toda la aplicación
        $this->config = parse_ini_file(__DIR__ . "/../config/config.ini");

        // 2. Definimos una constante global para usarla en cualquier lado
        if (!defined('BASE_URL')) {
            define('BASE_URL', $this->config['base_url'] ?? '');
        }
    }

    // Instanciamos nuestro nuevo controlador y le pasamos el motor de vistas (Mustache)
    public function getEquipoController()
    {
        return new EquipoController(
            $this->getRender()
        );
    }

    // instanciamos el controller de registro
    public function getRegistroController(){
        return new RegistroController(
            $this->getUsuarioModel(),
            $this->getRender()
        );
    }

    public function getLoginController(){
        return new LoginController(
            $this->getUsuarioModel(), // <-- Tenés que sumarle esta línea
            $this->getRender()
        );
    }

    public function getHomeController(){
        return new HomeController(
            $this->getRender(),
        );
    }


   //los privados

    private function getDatabase()
    {
        $config = parse_ini_file(__DIR__ . "/../config/config.ini");

        return new MyDatabase(
            $this->config["db_host"],
            $this->config["db_user"],
            $this->config["db_pass"],
            $this->config["db_name"]
        );
    }

    private function getRender()
    {
        return new MustacheRender();
    }

    private function getUsuarioModel(){
        return new UsuarioModel(
            $this->getDatabase()
        );
    }


    public function getRouter()
    {
        return new Router($this, "login", "index");
    }
}
