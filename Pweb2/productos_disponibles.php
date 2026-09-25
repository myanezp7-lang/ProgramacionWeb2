<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    $consulta = conectarBD()->query(
        'SELECT id_producto, nombre, descripcion, precio, stock,
                CASE WHEN stock > 0 THEN "Disponible" ELSE "Agotado" END AS disponibilidad
         FROM PRODUCTO
         ORDER BY nombre'
    );

    echo json_encode($consulta->fetchAll(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo consultar la disponibilidad.'], JSON_UNESCAPED_UNICODE);
}