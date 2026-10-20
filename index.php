<?php
use app\Configuration;

session_start();
require_once("app/Configuration.php");

$configuration = new Configuration();
$router = $configuration->getRouter();

// 1. Capturamos la URL exacta que escribió el usuario
$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 2. Le quitamos la carpeta base del proyecto para limpiar la cadena
// (Cuando subas a InfinityFree, cambiás '/mvc-sept-2026' por '' porque allá es la raíz)
$urlLimpia = str_replace('/mvc-sept-2026', '', $url);

// 3. Borramos barras iniciales/finales y cortamos por los '/'
$urlLimpia = trim($urlLimpia, '/');
$segmentos = explode('/', $urlLimpia);

// 4. El primer bloque es el controlador, el segundo el método
$controller = !empty($segmentos[0]) ? $segmentos[0] : 'login';
$method     = !empty($segmentos[1]) ? $segmentos[1] : 'index';

// 5. Despachamos
$router->dispatch($controller, $method);