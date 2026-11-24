<?php

class MyDatabase
{
    private $conexion;

    public function __construct($host, $user, $pass, $db)
    {
        $this->conexion = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
        $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function queryOne(string $sql, array $parametros = [])
    {
        Log::info($sql);
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute($parametros);
        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $fila;
    }

    public function queryAll(string $sql, array $parametros = [], array $tipos = [])
    {
        Log::info($sql);
        $sentencia = $this->conexion->prepare($sql);

        if (empty($tipos)) {
            $sentencia->execute($parametros);
        } else {
            foreach ($parametros as $i => $valor) {
                $sentencia->bindValue($i + 1, $valor, $tipos[$i] ?? PDO::PARAM_STR);
            }
            $sentencia->execute();
        }

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    public function execute(string $sql, array $parametros = [])
    {
        Log::info($sql);

        // Preparamos la consulta de forma segura
        $sentencia = $this->conexion->prepare($sql);

        // Ejecutamos pasándole el arreglo de datos limpios
        return $sentencia->execute($parametros);
    }

    public function lastInsertId()
    {
        return $this->conexion->lastInsertId();
    }
}
