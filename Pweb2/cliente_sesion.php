<?php

declare(strict_types=1);

require_once __DIR__ . '/session_security.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $clientes = conectarBD()->query(
            'SELECT id_cliente, nombre, email FROM CLIENTE ORDER BY nombre'
        )->fetchAll();

        $clienteActual = null;
        if (isset($_SESSION['cliente_id'])) {
            $consultaActual = conectarBD()->prepare(
                'SELECT id_cliente, nombre, email FROM CLIENTE WHERE id_cliente = :id_cliente'
            );
            $consultaActual->execute([':id_cliente' => (int) $_SESSION['cliente_id']]);
            $clienteActual = $consultaActual->fetch() ?: null;
        }

        echo json_encode([
            'clientes' => $clientes,
            'cliente_id' => $_SESSION['cliente_id'] ?? null,
            'cliente_actual' => $clienteActual,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    } catch (Throwable $error) {
        http_response_code(500);
        echo json_encode(['error' => 'No se pudieron cargar los clientes.'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validarTokenCsrf($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    echo json_encode(['error' => 'Solicitud no válida.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$clienteId = filter_var($_POST['cliente_id'] ?? null, FILTER_VALIDATE_INT);
if ($clienteId === false || $clienteId < 1) {
    http_response_code(422);
    echo json_encode(['error' => 'Selecciona un cliente válido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$consulta = conectarBD()->prepare(
    'SELECT id_cliente, nombre, email FROM CLIENTE WHERE id_cliente = :id_cliente'
);
$consulta->execute([':id_cliente' => $clienteId]);
$cliente = $consulta->fetch();

if ($cliente === false) {
    http_response_code(404);
    echo json_encode(['error' => 'El cliente seleccionado no existe.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$_SESSION['cliente_id'] = (int) $cliente['id_cliente'];
$_SESSION['cliente_nombre'] = $cliente['nombre'];

echo json_encode(['cliente' => $cliente], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);