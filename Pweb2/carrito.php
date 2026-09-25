<?php

require_once __DIR__ . '/session_security.php';

$productos = [
    1 => ['nombre' => 'Laptop Lenovo IdeaPad', 'precio' => 799000],
    2 => ['nombre' => 'Smartphone Samsung Galaxy', 'precio' => 540000],
    3 => ['nombre' => 'Sofá Moderno', 'precio' => 420000],
    4 => ['nombre' => 'Set de Toallas', 'precio' => 28000],
    5 => ['nombre' => 'Camisa de algodón', 'precio' => 25000],
    6 => ['nombre' => 'Balón de fútbol', 'precio' => 32000]
];

$_SESSION['carrito'] ??= [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        responderSolicitudInvalida('La solicitud no es válida. Recarga la página e inténtalo nuevamente.');
    }

    $accion = $_POST['accion'] ?? '';
    $productoId = filter_var($_POST['producto_id'] ?? null, FILTER_VALIDATE_INT);

    if ($accion === 'agregar' && $productoId !== false && isset($productos[$productoId])) {
        $_SESSION['carrito'][$productoId] = min(99, ($_SESSION['carrito'][$productoId] ?? 0) + 1);
    } elseif ($accion === 'actualizar' && $productoId !== false && isset($productos[$productoId])) {
        $cantidad = filter_var($_POST['cantidad'] ?? null, FILTER_VALIDATE_INT);
        if ($cantidad === false || $cantidad < 1 || $cantidad > 99) {
            responderSolicitudInvalida('La cantidad debe estar entre 1 y 99.');
        }
        $_SESSION['carrito'][$productoId] = $cantidad;
    } elseif ($accion === 'eliminar' && $productoId !== false) {
        unset($_SESSION['carrito'][$productoId]);
    } elseif ($accion === 'vaciar') {
        $_SESSION['carrito'] = [];
    }

    header('Location: carrito.php');
    exit;
}

$token = obtenerTokenCsrf();
$total = 0;
foreach ($_SESSION['carrito'] as $productoId => $cantidad) {
    if (isset($productos[$productoId])) {
        $total += $productos[$productoId]['precio'] * $cantidad;
    }
}

function precio(int $valor): string
{
    return '$' . number_format($valor, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de compra</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <main class="container">
        <header class="header">
            <h1>Carrito de compra</h1>
            <a href="index.html">Volver a la tienda</a>
        </header>

        <section aria-labelledby="carrito-title">
            <h2 id="carrito-title">Productos seleccionados</h2>
            <?php if (empty($_SESSION['carrito'])): ?>
                <p>El carrito está vacío.</p>
            <?php else: ?>
                <?php foreach ($_SESSION['carrito'] as $productoId => $cantidad): ?>
                    <?php if (!isset($productos[$productoId])) { continue; } ?>
                    <form method="POST" class="form-row">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                        <input type="hidden" name="producto_id" value="<?= $productoId ?>">
                        <input type="hidden" name="accion" value="actualizar">
                        <strong><?= htmlspecialchars($productos[$productoId]['nombre']) ?></strong>
                        <span><?= precio($productos[$productoId]['precio']) ?></span>
                        <label>
                            Cantidad
                            <input type="number" name="cantidad" min="1" max="99" value="<?= $cantidad ?>">
                        </label>
                        <button type="submit">Actualizar</button>
                    </form>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                        <input type="hidden" name="producto_id" value="<?= $productoId ?>">
                        <input type="hidden" name="accion" value="eliminar">
                        <button type="submit">Eliminar</button>
                    </form>
                <?php endforeach; ?>
                <p><strong>Total: <?= precio($total) ?></strong></p>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                    <input type="hidden" name="accion" value="vaciar">
                    <button type="submit">Vaciar carrito</button>
                </form>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>