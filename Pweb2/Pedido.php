<?php

require_once __DIR__ . '/session_security.php';
require_once __DIR__ . '/db.php';

class Pedido
{
    private string $descripcionPedido;
    private string $tipoPedido;
    private string $producto;
    private int $unidades;
    private string $observaciones;

    public function __construct(
        string $descripcionPedido,
        string $tipoPedido,
        string $producto,
        int $unidades,
        string $observaciones = ""
    ) {
        $this->descripcionPedido = trim($descripcionPedido);
        $this->tipoPedido = trim($tipoPedido);
        $this->producto = trim($producto);
        $this->unidades = $unidades;
        $this->observaciones = trim($observaciones);
    }

    public function getDescripcionPedido(): string
    {
        return $this->descripcionPedido;
    }

    public function setDescripcionPedido(string $descripcionPedido): void
    {
        $this->descripcionPedido = trim($descripcionPedido);
    }

    public function getTipoPedido(): string
    {
        return $this->tipoPedido;
    }

    public function setTipoPedido(string $tipoPedido): void
    {
        $this->tipoPedido = trim($tipoPedido);
    }

    public function getProducto(): string
    {
        return $this->producto;
    }

    public function setProducto(string $producto): void
    {
        $this->producto = trim($producto);
    }

    public function getUnidades(): int
    {
        return $this->unidades;
    }

    public function setUnidades(int $unidades): void
    {
        $this->unidades = $unidades;
    }

    public function getObservaciones(): string
    {
        return $this->observaciones;
    }

    public function setObservaciones(string $observaciones): void
    {
        $this->observaciones = trim($observaciones);
    }

    public function aumentarUnidades(int $cantidad): void
    {
        if ($cantidad > 0) {
            $this->unidades += $cantidad;
        }
    }

    public function reducirUnidades(int $cantidad): void
    {
        if ($cantidad > 0 && $this->unidades >= $cantidad) {
            $this->unidades -= $cantidad;
        }
    }

    public function tieneObservaciones(): bool
    {
        return $this->observaciones !== "";
    }

    public function resumen(): string
    {
        return "Pedido: {$this->descripcionPedido} | Tipo: {$this->tipoPedido} | Producto: {$this->producto} | Unidades: {$this->unidades}";
    }

    public static function buscarPorProducto(array $pedidos, string $producto): array
    {
        return array_values(array_filter($pedidos, function (Pedido $pedido) use ($producto) {
            return stripos($pedido->getProducto(), $producto) !== false;
        }));
    }

    public static function buscarPorTipo(array $pedidos, string $tipo): array
    {
        return array_values(array_filter($pedidos, function (Pedido $pedido) use ($tipo) {
            return stripos($pedido->getTipoPedido(), $tipo) !== false;
        }));
    }

    public static function buscarPorUnidades(array $pedidos, int $minimo, int $maximo): array
    {
        return array_values(array_filter($pedidos, function (Pedido $pedido) use ($minimo, $maximo) {
            return $pedido->getUnidades() >= $minimo && $pedido->getUnidades() <= $maximo;
        }));
    }

    public static function buscarPorObservaciones(array $pedidos, string $texto): array
    {
        return array_values(array_filter($pedidos, function (Pedido $pedido) use ($texto) {
            return stripos($pedido->getObservaciones(), $texto) !== false;
        }));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        responderSolicitudInvalida('La solicitud no es válida. Recarga la página e inténtalo nuevamente.');
    }

    $selectedProductId = filter_var($_POST['selectedProductId'] ?? null, FILTER_VALIDATE_INT);
    $descripcionPedido = trim((string) ($_POST['descripcionPedido'] ?? ''));
    $tipoPedido = trim((string) ($_POST['tipoPedido'] ?? ''));
    $producto = trim((string) ($_POST['producto'] ?? ''));
    $unidades = filter_var($_POST['unidades'] ?? null, FILTER_VALIDATE_INT);
    $observaciones = trim((string) ($_POST['observaciones'] ?? ''));

    if (
        $selectedProductId === false || $selectedProductId < 1 ||
        $descripcionPedido === '' || strlen($descripcionPedido) > 150 ||
        !in_array($tipoPedido, ['Compra', 'Venta'], true) ||
        $unidades === false || $unidades < 1 || $unidades > 100 ||
        strlen($observaciones) > 500
    ) {
        responderSolicitudInvalida('Los datos del pedido no son válidos.');
    }

    $clienteId = filter_var($_SESSION['cliente_id'] ?? null, FILTER_VALIDATE_INT);
    if ($clienteId === false || $clienteId < 1) {
        responderSolicitudInvalida('Selecciona un usuario antes de confirmar el pedido.');
    }

    $conexion = conectarBD();

    try {
        $conexion->beginTransaction();

        $consultaProducto = $conexion->prepare(
            'SELECT p.id_producto, p.nombre, p.precio, p.stock, c.id_cliente, c.nombre AS cliente
             FROM PRODUCTO p
             INNER JOIN CLIENTE c ON c.id_cliente = :id_cliente
             WHERE p.id_producto = :id_producto AND p.stock >= :unidades
             FOR UPDATE'
        );
        $consultaProducto->execute([
            ':id_producto' => $selectedProductId,
            ':id_cliente' => $clienteId,
            ':unidades' => $unidades,
        ]);
        $datos = $consultaProducto->fetch();

        if ($datos === false || $producto !== $datos['nombre']) {
            $conexion->rollBack();
            responderSolicitudInvalida('El producto no existe o no tiene stock suficiente.');
        }

        $actualizarStock = $conexion->prepare(
            'UPDATE PRODUCTO SET stock = stock - :cantidad_restar
             WHERE id_producto = :id_producto AND stock >= :cantidad_minima'
        );
        $actualizarStock->execute([
            ':cantidad_restar' => $unidades,
            ':id_producto' => $selectedProductId,
            ':cantidad_minima' => $unidades,
        ]);

        if ($actualizarStock->rowCount() !== 1) {
            $conexion->rollBack();
            responderSolicitudInvalida('El stock cambió y ya no es suficiente.');
        }

        $insertarCompra = $conexion->prepare(
            'INSERT INTO COMPRA (cantidad, total, id_producto, id_cliente)
             VALUES (:cantidad, :total, :id_producto, :id_cliente)'
        );
        $insertarCompra->execute([
            ':cantidad' => $unidades,
            ':total' => (float) $datos['precio'] * $unidades,
            ':id_producto' => $selectedProductId,
            ':id_cliente' => $clienteId,
        ]);

        $conexion->commit();
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">';
        echo '<link rel="stylesheet" href="styles.css"><title>Compra registrada</title></head><body>';
        echo '<main class="container"><p class="success-message">Compra registrada para ' .
            htmlspecialchars($datos['cliente'], ENT_QUOTES, 'UTF-8') . '.</p>';
        echo '<p><a href="index.html">Volver a la tienda</a></p></main></body></html>';
        exit;
    } catch (Throwable $error) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        responderSolicitudInvalida('No se pudo registrar la compra.');
    }

    echo '<!DOCTYPE html>';
    echo '<html lang="es">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<title>Pedido recibido</title>';
    echo '<link rel="stylesheet" href="styles.css">';
    echo '</head>';
    echo '<body>';
    echo '<main class="container">';
    echo '<h1>Pedido recibido</h1>';
    echo '<p><strong>Descripción:</strong> ' . htmlspecialchars($pedido->getDescripcionPedido()) . '</p>';
    echo '<p><strong>Tipo:</strong> ' . htmlspecialchars($pedido->getTipoPedido()) . '</p>';
    echo '<p><strong>Producto:</strong> ' . htmlspecialchars($pedido->getProducto()) . '</p>';
    echo '<p><strong>Cantidad:</strong> ' . htmlspecialchars((string) $pedido->getUnidades()) . '</p>';
    echo '<p><strong>Observaciones:</strong> ' . htmlspecialchars($pedido->getObservaciones()) . '</p>';
    echo '<p><a href="index.html">Volver a la tienda</a></p>';
    echo '</main>';
    echo '</body>';
    echo '</html>';
    exit;
}
