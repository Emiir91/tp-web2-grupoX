<?php
use app\Configuration;
// Asegurate de importar tu clase Request si es necesario, ej: use app\helper\Request;

session_start();
require_once("app/Configuration.php");

$configuration = new Configuration();
$router = $configuration->getRouter();

$router->dispatch(
    Request::get("controller", "login"), // Cambié "equipo" por "login" como default
    Request::get("method", "index")
);