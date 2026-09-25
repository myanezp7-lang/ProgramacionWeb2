<?php

declare(strict_types=1);

require_once __DIR__ . '/session_security.php';
require_once __DIR__ . '/db.php';

function mostrarMensajeCompra(string $mensaje, bool $exito = false): never
{
    $clase = $exito ? 'success-message' : 'error-message';
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<link rel="stylesheet" href="styles.css"><title>Compra</title></head><body>';
    echo '<main class="container"><p class="' . $clase . '">';
    echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8');
    echo '</p><p><a href="comprar.php">Realizar otra compra</a> | ';
    echo '<a href="index.html">Volver a la tienda</a></p></main></body></html>';
    exit;
}

$conexion = conectarBD();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        responderSolicitudInvalida('La solicitud no es válida. Recarga la página e inténtalo nuevamente.');
    }

    $productoId = filter_var($_POST['id_producto'] ?? null, FILTER_VALIDATE_INT);
    $clienteId = filter_var($_SESSION['cliente_id'] ?? null, FILTER_VALIDATE_INT);
    $cantidad = filter_var($_POST['cantidad'] ?? null, FILTER_VALIDATE_INT);

    if (
        $productoId === false || $productoId < 1 ||
        $clienteId === false || $clienteId < 1 ||
        $cantidad === false || $cantidad < 1 || $cantidad > 99
    ) {
        mostrarMensajeCompra('Selecciona un producto, un cliente y una cantidad válida.');
    }

    try {
        $conexion->beginTransaction();

        // Confirma producto, cliente y stock disponible en una sola consulta.
        $consulta = $conexion->prepare(
            'SELECT p.id_producto, p.nombre AS producto, p.precio, p.stock,
                    c.id_cliente, c.nombre AS cliente
             FROM PRODUCTO p
             INNER JOIN CLIENTE c ON c.id_cliente = :id_cliente
             WHERE p.id_producto = :id_producto
               AND p.stock >= :cantidad
             FOR UPDATE'
        );
        $consulta->execute([
            ':id_producto' => $productoId,
            ':id_cliente' => $clienteId,
            ':cantidad' => $cantidad,
        ]);
        $datosCompra = $consulta->fetch();

        if ($datosCompra === false) {
            $conexion->rollBack();
            mostrarMensajeCompra('El producto no existe, el cliente no existe o no hay stock suficiente.');
        }

        $total = (float) $datosCompra['precio'] * $cantidad;

        $actualizarStock = $conexion->prepare(
          'UPDATE PRODUCTO
           SET stock = stock - :cantidad_restar
           WHERE id_producto = :id_producto AND stock >= :cantidad_minima'
        );
        $actualizarStock->execute([
          ':cantidad_restar' => $cantidad,
            ':id_producto' => $productoId,
          ':cantidad_minima' => $cantidad,
        ]);

        if ($actualizarStock->rowCount() !== 1) {
            $conexion->rollBack();
            mostrarMensajeCompra('El stock cambió y ya no es suficiente para completar la compra.');
        }

        $insertarCompra = $conexion->prepare(
            'INSERT INTO COMPRA (cantidad, total, id_producto, id_cliente)
             VALUES (:cantidad, :total, :id_producto, :id_cliente)'
        );
        $insertarCompra->execute([
            ':cantidad' => $cantidad,
            ':total' => $total,
            ':id_producto' => $productoId,
            ':id_cliente' => $clienteId,
        ]);

        $conexion->commit();
        mostrarMensajeCompra(
            'Compra registrada para ' . $datosCompra['cliente'] . ': ' .
            $cantidad . ' unidad(es) de ' . $datosCompra['producto'] . '.',
            true
        );
    } catch (Throwable $error) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        mostrarMensajeCompra('No se pudo registrar la compra. Inténtalo nuevamente.');
    }
}

$productos = $conexion->query(
    'SELECT id_producto, nombre, descripcion, precio, stock,
            CASE WHEN stock > 0 THEN "Disponible" ELSE "Agotado" END AS disponibilidad
     FROM PRODUCTO
     ORDER BY nombre'
)->fetchAll();
$clienteActual = null;
if (isset($_SESSION['cliente_id'])) {
  $consultaCliente = $conexion->prepare(
    'SELECT id_cliente, nombre, email FROM CLIENTE WHERE id_cliente = :id_cliente'
  );
  $consultaCliente->execute([':id_cliente' => (int) $_SESSION['cliente_id']]);
  $clienteActual = $consultaCliente->fetch();
}
$token = obtenerTokenCsrf();
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="styles.css" />
    <title>Comprar producto</title>
  </head>
  <body>
    <main class="container">
      <section class="data-management-section single-form-page" aria-labelledby="compra-title">
        <h1 id="compra-title">Comprar producto</h1>
        <p>Selecciona un cliente y un producto con stock disponible.</p>

        <?php if (empty($productos) || $clienteActual === false || $clienteActual === null): ?>
          <p class="error-message">Selecciona un cliente en la página principal antes de comprar.</p>
        <?php else: ?>
          <form class="data-form" method="POST" action="comprar.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="form-row">
              <span>Cliente seleccionado</span>
              <strong><?= htmlspecialchars($clienteActual['nombre'] . ' - ' . $clienteActual['email'], ENT_QUOTES, 'UTF-8') ?></strong>
            </div>

            <div class="form-row">
              <label for="id_producto">Producto y disponibilidad</label>
              <select id="id_producto" name="id_producto" required>
                <option value="">Selecciona un producto</option>
                <?php foreach ($productos as $producto): ?>
                  <option value="<?= (int) $producto['id_producto'] ?>" <?= (int) $producto['stock'] < 1 ? 'disabled' : '' ?>>
                    <?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8') ?> -
                    Stock: <?= (int) $producto['stock'] ?> -
                    <?= htmlspecialchars($producto['disponibilidad'], ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-row">
              <label for="cantidad">Cantidad</label>
              <input id="cantidad" type="number" name="cantidad" min="1" max="99" value="1" required />
            </div>

            <button type="submit" class="submit-data">Registrar compra</button>
          </form>
        <?php endif; ?>

        <p><a href="index.html">Volver a la tienda</a></p>
      </section>
    </main>
  </body>
</html>