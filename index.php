<?php
use app\Configuration;
// Asegurate de importar tu clase Request si es necesario, ej: use app\helper\Request;

session_start();
require_once("app/Configuration.php");

$configuration = new Configuration();
$router = $configuration->getRouter();

$router->dispatch(
    Request::get("controller", "home"), // por default
    Request::get("method", "index")
);