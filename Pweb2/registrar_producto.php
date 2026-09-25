<?php

require_once __DIR__ . '/session_security.php';
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="styles.css" />
    <title>Registrar producto</title>
  </head>
  <body>
    <main class="container">
      <section class="data-management-section single-form-page" aria-labelledby="producto-title">
        <h1 id="producto-title">Registrar producto</h1>
        <p>Completa los datos del nuevo producto.</p>

        <form class="data-form" method="POST" action="registrar_datos.php" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(obtenerTokenCsrf(), ENT_QUOTES, 'UTF-8') ?>" />
          <input type="hidden" name="tipo" value="producto" />

          <div class="form-row">
            <label for="nombre">Nombre del producto</label>
            <input id="nombre" type="text" name="nombre" minlength="2" maxlength="150" required />
          </div>

          <div class="form-row">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" minlength="5" maxlength="1000" rows="4" required></textarea>
          </div>

          <div class="form-row">
            <label for="precio">Precio</label>
            <input id="precio" type="number" name="precio" min="0.01" step="0.01" required />
          </div>

          <div class="form-row">
            <label for="stock">Stock disponible</label>
            <input id="stock" type="number" name="stock" min="0" step="1" required />
          </div>

          <button type="submit" class="submit-data">Guardar producto</button>
        </form>

        <p><a href="index.html">Volver a la tienda</a></p>
      </section>
    </main>

    <script>
      document.querySelector('form').addEventListener('submit', (event) => {
        if (!event.currentTarget.checkValidity()) {
          event.preventDefault();
          event.currentTarget.reportValidity();
        }
      });
    </script>
  </body>
</html>