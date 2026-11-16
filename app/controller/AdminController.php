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
            'ediciones' => $this->adminModel->obtenerEdicionesPendientes()
        ];

        if (($_GET['mensaje'] ?? '') === 'procesado') {
            $datosParaVista['alerta_exito'] = 'La acción fue procesada correctamente.';
        }

        $this->renderer->renderiza('admin', $datosParaVista);
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
