<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cortocircuito - La trivia más eléctrica</title>

    <?php if (strtolower($view) === 'registro' || strtolower($view) === 'perfil'): ?>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <?php endif; ?>

    <!-- Carga del CSS de la barra superior -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/header.css?v=1">
    <!-- Carga del CSS dinámico de cada vista -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/<?= $view ?>.css?v=1">
</head>
<body>

<header class="barra-superior">
    <div class="barra-logo">
        <a href="<?= BASE_URL ?>/home">
            <img src="<?= BASE_URL ?>/public/img/logotipo.png" alt="Cortocircuito" class="logo-nav">
        </a>
    </div>

    <div class="barra-opciones">
        <?php
        // Verificamos la variable exacta que definiste en el LoginController
        if (isset($_SESSION['usuario_logueado']) && $_SESSION['usuario_logueado'] === true):
            ?>
            <!-- Vista para USUARIO LOGUEADO -->
            <div class="menu-perfil-contenedor">
                <button id="btn-perfil" class="btn-icono-perfil">
                    👤 <?= htmlspecialchars($_SESSION['nombre_usuario']) ?> ▼
                </button>

                <div id="menu-desplegable" class="dropdown-oculto">
                    <a href="<?= BASE_URL ?>/perfil">Mi Perfil</a>
                    <a href="<?= BASE_URL ?>/login/logout" class="cerrar-sesion">Cerrar Sesión</a>
                </div>
            </div>

        <?php else: ?>
            <!-- Vista para INVITADO -->
            <a href="<?= BASE_URL ?>/login" class="btn-iniciar-sesion">Iniciar Sesión</a>
        <?php endif; ?>
    </div>
</header>


