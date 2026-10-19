<?php

namespace app\model;

class UsuarioModel
{

    private $database;

    // Recibimos la conexión a la base de datos (PDO o MySQLi)
    public function __construct($database)
    {
        $this->database = $database;
    }

    public function registrarUsuario($datos)
    {
        // Preparamos la consulta SQL. Los "?" son espacios reservados seguros.
        $sql = "INSERT INTO usuarios (
                    nombre_completo, 
                    anio_nacimiento, 
                    sexo, 
                    pais, 
                    ciudad, 
                    mail, 
                    contrasenia, 
                    nombre_usuario, 
                    foto_perfil, 
                    token_validacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        // Armamos el arreglo estricto con los valores en el mismo orden que los "?"
        $parametros = [
            $datos['nombre_completo'],
            $datos['anio_nacimiento'],
            $datos['sexo'],
            $datos['pais'],
            $datos['ciudad'],
            $datos['mail'],
            $datos['contrasenia'],
            $datos['nombre_usuario'],
            $datos['foto_perfil'],
            $datos['token_validacion']
        ];

        // Ejecutamos la consulta.
        try {
            $this->database->execute($sql, $parametros);
            return true; // Se guardó con éxito
        } catch (Exception $e) {
            // Si hay un error (ej: el mail ya existe por la regla UNIQUE de la tabla)
            // En un proyecto real acá guardaríamos el error en un log (app/log/)
            return false;
        }
    }

    public function activarUsuario($token)
    {
        // Armamos la consulta inyectando el token directamente (entre comillas simples)
        $sql = "UPDATE usuarios SET cuenta_validada = 1 WHERE token_validacion = '$token'";

        // Usamos el método execute que ya tenés armado en tu helper MyDatabase
        return $this->database->execute($sql);
    }
}