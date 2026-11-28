<?php

namespace app\controller;

use app\service\AdminService;

class AdminController
{
    private $renderer;
    private $adminService;

    public function __construct($renderer, AdminService $adminService)
    {
        if (empty($_SESSION['usuario_logueado'])) {
            header("Location: " . BASE_URL . "/login");
            exit();
        }

        if (($_SESSION['id_rol'] ?? 0) != 3) {
            header("Location: " . BASE_URL . "/lobby");
            exit();
        }

        $this->renderer = $renderer;
        $this->adminService = $adminService;
    }

    public function index()
    {
        $datosParaVista = $this->adminService->obtenerPendientes();

        if (($_GET['mensaje'] ?? '') === 'procesado') {
            $datosParaVista['alerta_exito'] = 'La acción fue procesada correctamente.';
        }

        $this->renderer->renderiza('admin', $datosParaVista);
    }

    // Pantalla de estadísticas. Los datos los pide el JavaScript por AJAX a datosEstadisticas().
    public function estadisticas()
    {
        $this->renderer->renderiza('adminEstadisticas', [
            'requiere_estadisticas' => true
        ]);
    }

    // Devuelve las estadísticas en JSON para el período elegido (dia, semana, mes, anio o todo)
    public function datosEstadisticas()
    {
        $estadisticas = $this->adminService->obtenerEstadisticas($_GET['periodo'] ?? 'todo');

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($estadisticas);
    }

    public function aprobarAlta()
    {
        $this->procesarAccion('alta', 'aprobar');
    }

    public function rechazarAlta()
    {
        $this->procesarAccion('alta', 'rechazar');
    }

    public function aprobarBaja()
    {
        $this->procesarAccion('baja', 'aprobar');
    }

    public function rechazarBaja()
    {
        $this->procesarAccion('baja', 'rechazar');
    }

    public function aprobarEdicion()
    {
        $this->procesarAccion('edicion', 'aprobar');
    }

    public function rechazarEdicion()
    {
        $this->procesarAccion('edicion', 'rechazar');
    }

    public function aprobarCategoria()
    {
        $this->procesarAccion('categoria', 'aprobar');
    }

    public function rechazarCategoria()
    {
        $this->procesarAccion('categoria', 'rechazar');
    }

    private function procesarAccion($tipo, $decision)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/admin/index");
            exit();
        }

        $this->adminService->resolverSolicitud($tipo, $decision, $_POST['id'] ?? null);

        header("Location: " . BASE_URL . "/admin/index?mensaje=procesado");
        exit();
    }
}
