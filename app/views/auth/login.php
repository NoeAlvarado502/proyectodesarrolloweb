<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#0f172a">
  <title>Ingresar · Control Académico</title>
  <link rel="icon" type="image/svg+xml" href="<?= e(url('assets/favicon.svg')) ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= e(url('assets/favicon-32x32.png')) ?>">
  <link rel="icon" type="image/png" sizes="64x64" href="<?= e(url('assets/favicon-64x64.png')) ?>">
  <link rel="shortcut icon" href="<?= e(url('assets/favicon.ico')) ?>">
  <link rel="stylesheet" href="<?= e(url('assets/app.css?v=' . filemtime(ROOT_PATH . '/assets/app.css'))) ?>">
</head>
<body class="login-body">
  <main class="login-container">
    <div class="login-card">
      <div class="login-card-header">
        <div class="login-card-brand">
          <span class="login-brand-icon"><?= icon('academic') ?></span>
          <h1 class="login-brand-title">Control Académico</h1>
        </div>
      </div>

      <?php if (!empty($error)): ?>
        <div class="login-alert danger" role="alert">
          <span class="login-alert-icon"><?= icon('info') ?></span>
          <span><?= e($error) ?></span>
        </div>
      <?php elseif (!empty($flash)): ?>
        <div class="login-alert <?= e($flash['type']) ?>" role="alert">
          <span class="login-alert-icon"><?= icon('info') ?></span>
          <span><?= e($flash['message']) ?></span>
        </div>
      <?php endif; ?>

      <form action="<?= e(url('login')) ?>" method="POST" class="login-form" novalidate autocomplete="on">
        <?= csrfField() ?>

        <div class="login-field">
          <label for="username" class="login-label">Usuario</label>
          <div class="login-input-wrap">
            <span class="login-input-icon"><?= icon('user') ?></span>
            <input
              type="text"
              id="username"
              name="username"
              class="login-input"
              placeholder="Usuario"
              value="<?= e($username ?? '') ?>"
              required
              autofocus
              autocomplete="username"
            >
          </div>
        </div>

        <div class="login-field">
          <label for="password" class="login-label">Contraseña</label>
          <div class="login-input-wrap login-password-wrap">
            <span class="login-input-icon"><?= icon('lock') ?></span>
            <input
              type="password"
              id="password"
              name="password"
              class="login-input"
              placeholder="Contraseña"
              required
              autocomplete="current-password"
            >
            <button
              type="button"
              class="login-password-toggle"
              id="togglePasswordBtn"
              aria-label="Ver u ocultar contraseña"
              title="Mostrar contraseña"
            >
              <span class="icon-eye-open"><?= icon('eye') ?></span>
              <span class="icon-eye-closed" style="display: none;"><?= icon('eye-off') ?></span>
            </button>
          </div>
        </div>

        <div class="login-actions">
          <button type="submit" class="login-submit-btn">Ingresar</button>
        </div>
      </form>
    </div>
  </main>

  <script src="<?= e(url('assets/app.js?v=' . filemtime(ROOT_PATH . '/assets/app.js'))) ?>"></script>
</body>
</html>
