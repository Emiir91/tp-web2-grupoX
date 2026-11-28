<?php

namespace app;

use app\controller\RegistroController;
use app\controller\LoginController;
use app\controller\HomeController;
use app\controller\JuegoController;
use app\controller\PerfilController;
use app\controller\LobbyController;
use app\controller\RankingController;
use app\controller\ResultadosController;
use app\controller\EditorController;
use app\controller\AdminController;
use app\model\UsuarioModel;
use app\model\PartidaModel;
use app\model\EditorModel;
use app\model\AdminModel;
use app\model\CategoriaModel;
use app\model\NotificacionModel;
use app\service\AdminService;
use app\service\LobbyService;
use app\service\LoginService;
use app\service\PartidaService;
use app\service\PerfilService;
use app\service\RegistroService;
use app\service\ResultadosService;

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
            new RegistroService($this->getUsuarioModel(), $this->getMailer()),
            $this->getRender()
        );
    }

    public function getLoginController(){
        return new LoginController(
            new LoginService($this->getUsuarioModel()),
            $this->getRender()
        );
    }

    public function getHomeController()
    {
        return new HomeController($this->getRender());
    }

    public function getLobbyController()
    {
        return new LobbyController(
            $this->getRender(),
            new LobbyService($this->getPartidaModel(), $this->getCategoriaModel())
        );
    }

    public function getJuegoController()
    {
        $partidaModel = $this->getPartidaModel();

        return new JuegoController(
            $partidaModel,
            $this->getRender(),
            $this->getCategoriaModel(),
            new PartidaService($partidaModel)
        );
    }

    public function getPerfilController()
    {
        return new PerfilController(
            new PerfilService($this->getUsuarioModel(), $this->getPartidaModel()),
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
        // El render recibe el modelo de notificaciones para mostrar los pendientes en la barra superior
        return new MustacheRender($this->getNotificacionModel());
    }

    private function getNotificacionModel()
    {
        return new NotificacionModel(
            $this->getDatabase()
        );
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

    public function getResultadosController()
    {
        return new ResultadosController(
            new ResultadosService($this->getPartidaModel(), $this->getCategoriaModel()),
            $this->getRender()
        );
    }

    public function getEditorController()
    {
        return new EditorController(
            $this->getRender(),
            $this->getEditorModel(),
            $this->getCategoriaModel()
        );
    }

    public function getAdminController()
    {
        return new AdminController(
            $this->getRender(),
            new AdminService($this->getAdminModel())
        );
    }

    private function getEditorModel(){
        return new EditorModel(
            $this->getDatabase()
        );
    }

    private function getCategoriaModel(){
        return new CategoriaModel(
            $this->getDatabase()
        );
    }

    private function getAdminModel(){
        return new AdminModel(
            $this->getDatabase()
        );
    }

}
