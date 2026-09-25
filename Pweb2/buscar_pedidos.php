<?php
require 'Pedido.php';

$filtrosGuardados = $_SESSION['filtros_pedidos'] ?? [
    'busqueda' => '',
    'tipo' => '',
    'minimo' => 0,
    'maximo' => 9999
];

$busqueda = isset($_GET['busqueda'])
    ? trim(substr((string) $_GET['busqueda'], 0, 100))
    : $filtrosGuardados['busqueda'];
$tipo = isset($_GET['tipo']) && in_array($_GET['tipo'], ['Compra', 'Venta'], true)
    ? $_GET['tipo']
    : (isset($_GET['tipo']) ? '' : $filtrosGuardados['tipo']);
$minimo = isset($_GET['minimo']) ? max(0, (int) $_GET['minimo']) : $filtrosGuardados['minimo'];
$maximo = isset($_GET['maximo']) ? max($minimo, (int) $_GET['maximo']) : $filtrosGuardados['maximo'];

$_SESSION['filtros_pedidos'] = compact('busqueda', 'tipo', 'minimo', 'maximo');

$pedidos = [
    new Pedido('Pedido de material para oficina', 'Compra', 'Laptop', 3, 'Entrega urgente'),
    new Pedido('Pedido de ropa deportiva', 'Compra', 'Zapatillas', 2, 'Color negro'),
    new Pedido('Pedido de artículos para hogar', 'Venta', 'Sofá', 1, 'Entrega en 5 días'),
    new Pedido('Pedido de accesorios', 'Compra', 'Teclado', 4, 'Teclado mecánico')
];

$resultados = $pedidos;

if ($busqueda !== '') {
    $resultados = Pedido::buscarPorProducto($resultados, $busqueda);
}

if ($tipo !== '') {
    $resultados = Pedido::buscarPorTipo($resultados, $tipo);
}

$resultados = Pedido::buscarPorUnidades($resultados, (int)$minimo, (int)$maximo);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Búsqueda de pedidos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 30px;
        }
        .container {
            max-width: 900px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        form {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 25px;
        }
        input, select, button {
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        button {
            background: #2563eb;
            color: white;
            border: none;
            cursor: pointer;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background: #eef2ff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Buscar pedidos</h1>

        <form method="GET">
            <input type="text" name="busqueda" placeholder="Buscar producto" value="<?= htmlspecialchars($busqueda) ?>">
            <select name="tipo">
                <option value="">Todos los tipos</option>
                <option value="Compra" <?= $tipo === 'Compra' ? 'selected' : '' ?>>Compra</option>
                <option value="Venta" <?= $tipo === 'Venta' ? 'selected' : '' ?>>Venta</option>
            </select>
            <input type="number" name="minimo" value="<?= htmlspecialchars((string)$minimo) ?>" placeholder="Mínimo">
            <input type="number" name="maximo" value="<?= htmlspecialchars((string)$maximo) ?>" placeholder="Máximo">
            <button type="submit">Buscar</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th>Tipo</th>
                    <th>Producto</th>
                    <th>Unidades</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($resultados)): ?>
                    <tr>
                        <td colspan="5">No se encontraron pedidos con esos criterios.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($resultados as $pedido): ?>
                        <tr>
                            <td><?= htmlspecialchars($pedido->getDescripcionPedido()) ?></td>
                            <td><?= htmlspecialchars($pedido->getTipoPedido()) ?></td>
                            <td><?= htmlspecialchars($pedido->getProducto()) ?></td>
                            <td><?= htmlspecialchars((string)$pedido->getUnidades()) ?></td>
                            <td><?= htmlspecialchars($pedido->getObservaciones()) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
