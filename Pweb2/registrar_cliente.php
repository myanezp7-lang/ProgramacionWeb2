<?php

require_once __DIR__ . '/session_security.php';
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="styles.css" />
    <title>Registrar cliente</title>
  </head>
  <body>
    <main class="container">
      <section class="data-management-section single-form-page" aria-labelledby="cliente-title">
        <h1 id="cliente-title">Registrar cliente</h1>
        <p>Completa los datos del nuevo cliente.</p>

        <form class="data-form" method="POST" action="registrar_datos.php" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(obtenerTokenCsrf(), ENT_QUOTES, 'UTF-8') ?>" />
          <input type="hidden" name="tipo" value="cliente" />

          <div class="form-row">
            <label for="nombre">Nombre completo</label>
            <input id="nombre" type="text" name="nombre" minlength="2" maxlength="150" required />
          </div>

          <div class="form-row">
            <label for="email">Correo electrónico</label>
            <input id="email" type="email" name="email" maxlength="150" required />
          </div>

          <div class="form-row">
            <label for="direccion">Dirección</label>
            <input id="direccion" type="text" name="direccion" minlength="5" maxlength="255" required />
          </div>

          <button type="submit" class="submit-data">Guardar cliente</button>
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