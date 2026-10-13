<?php
namespace app\controller;

class EquipoController{
    private $render;

    public function __construct($render){
        $this->render = $render;
    }

    public function index(){
        $datos = [
            "nombreEquipo" => "buscar nombre de equipo",
            "integrantes" => [
                ["nombre" => "Nahuel Guerrero"],
                ["nombre" => "Mariano Carusso"],
                ["nombre" => "Emir Martinez"],
            ]
        ];

        echo $this->render->renderiza("equipo", $datos);
    }
}
