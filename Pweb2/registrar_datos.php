<?php

declare(strict_types=1);

require_once __DIR__ . '/session_security.php';
require_once __DIR__ . '/db.php';

function responderRegistro(string $mensaje, bool $exito = false): never
{
    $clase = $exito ? 'success-message' : 'error-message';
    $titulo = $exito ? 'Registro exitoso' : 'No se pudo registrar';

    echo '<!DOCTYPE html>';
    echo '<html lang="es"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<link rel="stylesheet" href="styles.css">';
    echo '<title>' . $titulo . '</title></head><body><main class="container">';
    echo '<p class="' . $clase . '">' . htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p><a href="index.html">Volver a la tienda</a></p>';
    echo '</main></body></html>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
    responderSolicitudInvalida('La solicitud no es válida. Recarga la página e inténtalo nuevamente.');
}

$tipo = trim((string) ($_POST['tipo'] ?? ''));
$nombre = trim((string) ($_POST['nombre'] ?? ''));

if ($tipo === 'producto') {
    $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
    $precio = filter_var($_POST['precio'] ?? null, FILTER_VALIDATE_FLOAT);
    $stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT);

    if (
        strlen($nombre) < 2 || strlen($nombre) > 150 ||
        strlen($descripcion) < 5 || strlen($descripcion) > 1000 ||
        $precio === false || $precio <= 0 ||
        $stock === false || $stock < 0
    ) {
        responderRegistro('Revisa los datos del producto.');
    }

    $consulta = conectarBD()->prepare(
        'INSERT INTO PRODUCTO (nombre, descripcion, precio, stock)
         VALUES (:nombre, :descripcion, :precio, :stock)'
    );
    $consulta->execute([
        ':nombre' => $nombre,
        ':descripcion' => $descripcion,
        ':precio' => $precio,
        ':stock' => $stock,
    ]);

    responderRegistro('El producto fue registrado correctamente.', true);
}

if ($tipo === 'cliente') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $direccion = trim((string) ($_POST['direccion'] ?? ''));

    if (
        strlen($nombre) < 2 || strlen($nombre) > 150 ||
        filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($email) > 150 ||
        strlen($direccion) < 5 || strlen($direccion) > 255
    ) {
        responderRegistro('Revisa los datos del cliente.');
    }

    try {
        $consulta = conectarBD()->prepare(
            'INSERT INTO CLIENTE (nombre, email, direccion)
             VALUES (:nombre, :email, :direccion)'
        );
        $consulta->execute([
            ':nombre' => $nombre,
            ':email' => $email,
            ':direccion' => $direccion,
        ]);
    } catch (PDOException $error) {
        if (($error->errorInfo[1] ?? null) === 1062) {
            responderRegistro('Ese correo electrónico ya está registrado.');
        }

        responderRegistro('No se pudo registrar el cliente.');
    }

    responderRegistro('El cliente fue registrado correctamente.', true);
}

responderRegistro('El tipo de registro no es válido.');