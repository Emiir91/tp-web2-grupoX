<?php

// Color e ícono de cada categoría. Se usa en el juego y en la revisión de preguntas de resultados.
class EstiloCategoria
{
    private const ESTILOS = [
        'Geografía'       => ['color' => '#1bb5a4', 'icono' => '🌍'],
        'Ciencias'        => ['color' => '#2fa8e0', 'icono' => '🔬'],
        'Matemáticas'     => ['color' => '#4a6cf0', 'icono' => '➗'],
        'Física'          => ['color' => '#ff5252', 'icono' => '⚛️'],
        'Cultura General' => ['color' => '#e6c229', 'icono' => '💡'],
        'Animales'        => ['color' => '#8bc34a', 'icono' => '🐾'],
        'Literatura'      => ['color' => '#b07ad9', 'icono' => '📚'],
        'Historia'        => ['color' => '#c8813a', 'icono' => '🏛️'],
        'Deportes'        => ['color' => '#22c55e', 'icono' => '⚽'],
        'Arte'            => ['color' => '#f06aa8', 'icono' => '🎨'],
        'Música'          => ['color' => '#d946ef', 'icono' => '🎵'],
        'Cine'            => ['color' => '#cfd8dc', 'icono' => '🎬'],
        'Tecnología'      => ['color' => '#00e5ff', 'icono' => '💻'],
        'Videojuegos'     => ['color' => '#8c6cf0', 'icono' => '🎮']
    ];

    // Si una categoría no tiene estilo asignado, usa el naranja del juego
    private const POR_DEFECTO = ['color' => '#ff6a00', 'icono' => '❓'];

    public static function obtener($categoria)
    {
        return self::ESTILOS[$categoria] ?? self::POR_DEFECTO;
    }
}
