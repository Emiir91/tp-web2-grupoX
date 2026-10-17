<?php

class MyDatabase
{
    private $conexion;

    public function __construct($host, $user, $pass, $db)
    {
        $this->conexion = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    }

    public function queryOne(string $sql)
    {
        Log::info($sql);
        $resultado = $this->conexion->query($sql, PDO::FETCH_ASSOC);
        $fila = $resultado->fetch();
        return $fila === false ? null : $fila;
    }

    public function queryAll(string $sql)
    {
        Log::info($sql);
        $resultado = $this->conexion->query($sql, PDO::FETCH_ASSOC);
        return $resultado->fetchAll();
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
