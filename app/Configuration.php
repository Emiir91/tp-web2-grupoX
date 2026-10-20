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

    public function __construct()
    {
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

        return new MyDatabase($config["db_host"],
            $config["db_user"],
            $config["db_pass"],
            $config["db_name"]
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
