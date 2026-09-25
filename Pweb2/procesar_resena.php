<?php

require_once __DIR__ . '/session_security.php';

try {
    $conexion = new PDO('mysql:host=localhost;dbname=tienda_db;charset=utf8mb4', 'root', '');
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $error) {
    http_response_code(503);
    echo 'No se pudo conectar con la base de datos. Verifica que MySQL esté iniciado en XAMPP y que exista la base de datos tienda_db.';
    exit;
}

function guardarResena(PDO $conexion, ?int $usuarioId, int $productoId, int $calificacion, string $comentario): bool
{
    $comentario = trim($comentario);

    if ($comentario === '' || $calificacion < 1 || $calificacion > 5 || $productoId <= 0) {
        return false;
    }

    $sql = "INSERT INTO resenas (usuario_id, producto_id, calificacion, comentario, fecha_creacion)
            VALUES (:usuario_id, :producto_id, :calificacion, :comentario, NOW())";

    $stmt = $conexion->prepare($sql);

    return $stmt->execute([
        ':usuario_id' => $usuarioId,
        ':producto_id' => $productoId,
        ':calificacion' => $calificacion,
        ':comentario' => $comentario
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        responderSolicitudInvalida('La solicitud no es válida. Recarga la página e inténtalo nuevamente.');
    }

    $usuarioId = filter_var($_SESSION['usuario_id'] ?? null, FILTER_VALIDATE_INT);
    $productoId = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);
    $calificacion = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT);
    $comentario = trim((string) ($_POST['comment'] ?? ''));

    if ($usuarioId === false) {
        $usuarioId = null;
    }

    if (
        $productoId !== false && $productoId > 0 && $productoId <= 6 &&
        $calificacion !== false && $calificacion >= 1 && $calificacion <= 5 &&
        $comentario !== '' && strlen($comentario) <= 500
    ) {
        if (guardarResena($conexion, $usuarioId, $productoId, $calificacion, $comentario)) {
            echo '<!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>Reseña enviada</title>
                <link rel="stylesheet" href="styles.css">
            </head>
            <body>
                <main class="container">
                    <p class="success-message">Gracias por tu reseña. Tu opinión fue registrada.</p>
                    <p><a href="index.html">Volver a la tienda</a></p>
                </main>
            </body>
            </html>';
        } else {
            echo '<!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>Error</title>
                <link rel="stylesheet" href="styles.css">
            </head>
            <body>
                <main class="container">
                    <p class="error-message">No se pudo guardar la reseña. Inténtalo nuevamente.</p>
                    <p><a href="index.html">Volver</a></p>
                </main>
            </body>
            </html>';
        }
    } else {
        echo '<!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Error</title>
            <link rel="stylesheet" href="styles.css">
        </head>
        <body>
            <main class="container">
                <p class="error-message">Debes completar todos los campos correctamente.</p>
                <p><a href="index.html">Volver</a></p>
            </main>
        </body>
        </html>';
    }
} else {
    header('Location: index.html');
    exit;
}
