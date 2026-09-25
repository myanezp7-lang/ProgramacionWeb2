<?php

require_once __DIR__ . '/session_security.php';

header('Content-Type: application/json; charset=UTF-8');
echo json_encode(['token' => obtenerTokenCsrf()], JSON_THROW_ON_ERROR);
