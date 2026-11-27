<?php

namespace app\model;

use Exception;

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

    public function buscarUsuario($usuario_o_mail) {
        // Consulta preparada: lo que escribe el usuario viaja aparte y nunca se interpreta como SQL
        $sql = "SELECT * FROM usuarios WHERE mail = ? OR nombre_usuario = ?";
        $resultado = $this->database->queryAll($sql, [$usuario_o_mail, $usuario_o_mail]);

        // Si el array vuelve vacío, el usuario no está registrado
        if (empty($resultado)) {
            return false;
        }

        // Si existe, devolvemos todos sus datos para que el Controlador los analice
        return $resultado[0];
    }

    // Devuelve true si ya hay un usuario registrado con ese mail
    public function existeMail($mail)
    {
        $sql = "SELECT 1 FROM usuarios WHERE mail = ?";
        return $this->database->queryOne($sql, [$mail]) !== null;
    }

    // Devuelve true si ya hay un usuario registrado con ese nombre de usuario
    public function existeNombreUsuario($nombre_usuario)
    {
        $sql = "SELECT 1 FROM usuarios WHERE nombre_usuario = ?";
        return $this->database->queryOne($sql, [$nombre_usuario]) !== null;
    }

    // Busca un usuario con la cuenta ya validada por su nombre de usuario (consulta preparada)
    public function buscarPorNombreUsuario($nombre_usuario)
    {
        $sql = "SELECT * FROM usuarios WHERE nombre_usuario = ? AND cuenta_validada = 1";
        $resultado = $this->database->queryOne($sql, [$nombre_usuario]);

        return $resultado ?: false;
    }

    // Activa la cuenta del token recibido. Devuelve true solo si había una cuenta sin activar con ese token.
    public function activarUsuario($token)
    {
        // Consulta preparada: el token nunca se interpreta como SQL
        $usuario = $this->database->queryOne(
            "SELECT id_usuario FROM usuarios WHERE token_validacion = ? AND cuenta_validada = 0",
            [$token]
        );

        // Token inventado o cuenta ya activada
        if (!$usuario) {
            return false;
        }

        // Se borra el token para que el link sirva una sola vez
        return $this->database->execute(
            "UPDATE usuarios SET cuenta_validada = 1, token_validacion = NULL WHERE id_usuario = ?",
            [$usuario['id_usuario']]
        );
    }
}