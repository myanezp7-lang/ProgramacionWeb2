<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

$consulta = conectarBD()->query(
    'SELECT c.id_cliente,
            c.nombre,
            c.email,
            COUNT(co.id_compra) AS numero_compras,
            COALESCE(SUM(co.total), 0) AS total_compras
     FROM CLIENTE AS c
     INNER JOIN COMPRA AS co ON co.id_cliente = c.id_cliente
     GROUP BY c.id_cliente, c.nombre, c.email
     HAVING COUNT(co.id_compra) > 2
     ORDER BY numero_compras DESC, c.nombre ASC'
);

$clientesConMasDeDosCompras = $consulta->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="styles.css" />
    <title>Clientes con más de dos compras</title>
  </head>
  <body>
    <main class="container">
      <header class="header">
        <h1>Clientes con más de dos compras</h1>
        <a href="index.html">Volver a la tienda</a>
      </header>

      <section class="reviews-section" aria-labelledby="resultado-title">
        <h2 id="resultado-title">Resumen de compras por cliente</h2>

        <?php if (empty($clientesConMasDeDosCompras)): ?>
          <p class="no-results">No hay clientes con más de dos compras registradas.</p>
        <?php else: ?>
          <div class="table-wrapper">
            <table class="results-table">
              <thead>
                <tr>
                  <th>Cliente</th>
                  <th>Correo</th>
                  <th>Número de compras</th>
                  <th>Total comprado</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($clientesConMasDeDosCompras as $cliente): ?>
                  <tr>
                    <td><?= htmlspecialchars($cliente['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($cliente['email'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int) $cliente['numero_compras'] ?></td>
                    <td>$<?= number_format((float) $cliente['total_compras'], 0, ',', '.') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
    </main>
  </body>
</html>