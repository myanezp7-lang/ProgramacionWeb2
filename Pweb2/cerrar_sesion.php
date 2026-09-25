<?php

declare(strict_types=1);

require_once __DIR__ . '/session_security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validarTokenCsrf($_POST['csrf_token'] ?? null)) {
    responderSolicitudInvalida('La solicitud no es válida.');
}

unset($_SESSION['cliente_id'], $_SESSION['cliente_nombre']);

header('Location: index.html');
exit;