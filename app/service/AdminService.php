<?php

namespace app\service;

use app\model\AdminModel;

class AdminService
{
    private const PERIODOS = ['dia', 'semana', 'mes', 'anio', 'todo'];

    private AdminModel $adminModel;

    public function __construct(AdminModel $adminModel)
    {
        $this->adminModel = $adminModel;
    }

    public function obtenerPendientes()
    {
        return [
            'altas' => $this->agregarRespuestas($this->adminModel->obtenerPendientesAltas()),
            'bajas' => $this->agregarRespuestas($this->adminModel->obtenerPendientesBajas()),
            'ediciones' => $this->adminModel->obtenerEdicionesPendientes(),
            'categorias_pendientes' => $this->adminModel->obtenerCategoriasPendientes()
        ];
    }

    public function obtenerEstadisticas($periodo)
    {
        if (!in_array($periodo, self::PERIODOS, true)) {
            $periodo = 'todo';
        }

        return $this->adminModel->obtenerEstadisticas($periodo);
    }

    public function resolverSolicitud($tipo, $decision, $id)
    {
        if (!$id) {
            return;
        }

        switch ($tipo) {
            case 'alta':
                if ($decision === 'aprobar') {
                    $this->adminModel->aprobarAlta($id);
                } else {
                    $this->adminModel->rechazarAlta($id);
                }
                break;
            case 'baja':
                if ($decision === 'aprobar') {
                    $this->adminModel->aprobarBaja($id);
                } else {
                    $this->adminModel->rechazarBaja($id);
                }
                break;
            case 'edicion':
                if ($decision === 'aprobar') {
                    $this->adminModel->aprobarEdicion($id);
                } else {
                    $this->adminModel->rechazarEdicion($id);
                }
                break;
            case 'categoria':
                if ($decision === 'aprobar') {
                    $this->adminModel->aprobarCategoria($id);
                } else {
                    $this->adminModel->rechazarCategoria($id);
                }
                break;
        }
    }

    private function agregarRespuestas(array $preguntas)
    {
        foreach ($preguntas as $indice => $pregunta) {
            $preguntas[$indice]['respuestas'] = $this->adminModel->obtenerRespuestasDePregunta(
                $pregunta['id_pregunta']
            );
        }

        return $preguntas;
    }
}
