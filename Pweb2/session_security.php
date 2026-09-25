<?php

declare(strict_types=1);

const SESION_DURACION = 7200;
const SESION_REGENERACION = 900;

function iniciarSesionSegura(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.gc_maxlifetime', (string) SESION_DURACION);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_set_cookie_params([
        'lifetime' => SESION_DURACION,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Strict'
    ]);

    session_start();

    $ultimaActividad = $_SESSION['ultima_actividad'] ?? 0;
    $ahora = time();
    if ($ultimaActividad > 0 && $ahora - $ultimaActividad > SESION_DURACION) {
        session_unset();
        session_regenerate_id(true);
    }

    if (
        !isset($_SESSION['ultimo_cambio_id']) ||
        $ahora - (int) $_SESSION['ultimo_cambio_id'] >= SESION_REGENERACION
    ) {
        session_regenerate_id(true);
        $_SESSION['ultimo_cambio_id'] = $ahora;
    }

    $_SESSION['ultima_actividad'] = $ahora;
}

function obtenerTokenCsrf(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validarTokenCsrf(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function responderSolicitudInvalida(string $mensaje): void
{
    http_response_code(400);
    echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8');
    exit;
}

iniciarSesionSegura();
