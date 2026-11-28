<?php

namespace app\controller;

use app\service\RegistroService;

class RegistroController
{
    private $renderer;
    private RegistroService $registroService;

    public function __construct(RegistroService $registroService, $renderer)
    {
        $this->registroService = $registroService;
        $this->renderer = $renderer;
    }

    public function index()
    {
        $this->renderer->renderiza('registro', ['requiere_mapa' => true]);
    }

    public function procesarRegistro()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $resultado = $this->registroService->registrar(
            $_POST,
            $_FILES['foto_perfil'] ?? [],
            $this->urlActivacion()
        );

        if ($resultado === null) {
            header("Location: " . BASE_URL . "/login/index?mensaje=revisar_mail");
            exit();
        }

        $datosParaVista = $resultado['formulario'];
        $sexo = $datosParaVista['sexo'];
        $datosParaVista['is_masculino'] = $sexo === 'Masculino';
        $datosParaVista['is_femenino'] = $sexo === 'Femenino';
        $datosParaVista['is_prefiero_no'] = $sexo === 'Prefiero no cargarlo';
        $datosParaVista['requiere_mapa'] = true;
        $datosParaVista['error_backend'] = $resultado['error'];

        $this->renderer->renderiza('registro', $datosParaVista);
    }

    public function activarCuenta()
    {
        $token = $_GET['token'] ?? '';

        if ($this->registroService->activarCuenta($token)) {
            header("Location: " . BASE_URL . "/login/index?mensaje=cuenta_activada");
            exit();
        }

        echo "Error: Enlace de activación inválido o la cuenta ya fue activada.";
    }

    private function urlActivacion(): string
    {
        $protocolo = ($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $protocolo . '://' . $host . BASE_URL . '/registro/activarCuenta';
    }
}
