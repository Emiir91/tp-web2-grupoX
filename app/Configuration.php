<?php

namespace app;

use app\controller\EquipoController;

use app\controller\RegistroController;
use app\controller\LoginController;
use app\controller\HomeController;
use app\controller\JuegoController;
use app\controller\PerfilController;
use app\controller\LobbyController;
use app\controller\RankingController;
use app\model\UsuarioModel;
use app\model\PartidaModel;

use MustacheRender;
use MyDatabase;

use Router;
use Request;

require_once("helper/Autoloader.php");

class Configuration
{
    private array $config;
    public function __construct()
    {
        $this->config = parse_ini_file(__DIR__ . "/../config/config.ini");

        if (!defined('BASE_URL')) {
            define('BASE_URL', $this->config['base_url'] ?? '');
        }
    }

    public function getRegistroController(){
        return new RegistroController(
            $this->getUsuarioModel(),
            $this->getRender(),
            $this->getMailer()
        );
    }

    public function getLoginController(){
        return new LoginController(
            $this->getUsuarioModel(),
            $this->getRender()
        );
    }

    public function getHomeController()
    {
        return new HomeController(
            $this->getRender(),
            $this->getPartidaModel()
        );
    }

    public function getJuegoController()
    {
        return new JuegoController(
            $this->getPartidaModel(),
            $this->getRender()
        );
    }

    public function getPerfilController()
    {
        return new PerfilController(
            $this->getUsuarioModel(),
            $this->getRender()
        );
    }

    private function getDatabase()
    {
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

    private function getPartidaModel(){
        return new PartidaModel(
            $this->getDatabase()
        );
    }

    private function getMailer()
    {
        require_once("helper/Mailer.php");
        return new \Mailer($this->config);
    }

    public function getRouter()
    {
        return new Router($this, "home", "index");
    }

    public function getRankingController()
    {
        return new RankingController(
            $this->getRender(),
            $this->getPartidaModel()
        );
    }

}
