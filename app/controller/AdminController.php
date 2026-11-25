<?php

namespace app\controller;

class AdminController
{
    private $renderer;
    private $adminModel;

    public function __construct($renderer, $adminModel)
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
        $this->adminModel = $adminModel;
    }

    public function index()
    {
        $altas = $this->adminModel->obtenerPendientesAltas();
        foreach ($altas as &$a) {
            $a['respuestas'] = $this->adminModel->obtenerRespuestasDePregunta($a['id_pregunta']);
        }
        unset($a);

        $bajas = $this->adminModel->obtenerPendientesBajas();
        foreach ($bajas as &$b) {
            $b['respuestas'] = $this->adminModel->obtenerRespuestasDePregunta($b['id_pregunta']);
        }
        unset($b);

        $datosParaVista = [
            'altas' => $altas,
            'bajas' => $bajas,
            'ediciones' => $this->adminModel->obtenerEdicionesPendientes(),
            'categorias_pendientes' => $this->adminModel->obtenerCategoriasPendientes()
        ];

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
        $periodo = $_GET['periodo'] ?? 'todo';
        if (!in_array($periodo, ['dia', 'semana', 'mes', 'anio', 'todo'], true)) {
            $periodo = 'todo';
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->adminModel->obtenerEstadisticas($periodo));
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

        $id = $_POST['id'] ?? null;

        if ($id) {
            if ($tipo === 'alta') {
                $decision === 'aprobar'
                    ? $this->adminModel->aprobarAlta($id)
                    : $this->adminModel->rechazarAlta($id);
            } elseif ($tipo === 'baja') {
                $decision === 'aprobar'
                    ? $this->adminModel->aprobarBaja($id)
                    : $this->adminModel->rechazarBaja($id);
            } elseif ($tipo === 'categoria') {
                $decision === 'aprobar'
                    ? $this->adminModel->aprobarCategoria($id)
                    : $this->adminModel->rechazarCategoria($id);
            } else {
                $decision === 'aprobar'
                    ? $this->adminModel->aprobarEdicion($id)
                    : $this->adminModel->rechazarEdicion($id);
            }
        }

        header("Location: " . BASE_URL . "/admin/index?mensaje=procesado");
        exit();
    }
}
