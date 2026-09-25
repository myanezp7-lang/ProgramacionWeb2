<?php

declare(strict_types=1);

const DB_HOST = 'localhost';
const DB_PORT = '3306';
const DB_NAME = 'TIENDA';
const DB_CHARSET = 'utf8mb4';
const DB_USER = 'root';
const DB_PASSWORD = '';

function conectarBD(): PDO
{
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, DB_USER, DB_PASSWORD, $opciones);
    } catch (PDOException $e) {
        http_response_code(500);
        exit('Error de conexión a la base de datos: ' . $e->getMessage());
    }
}

?>
