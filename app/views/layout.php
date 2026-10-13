<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#0f172a">
  <title><?= isset($title) ? e($title) . ' · ' : '' ?>Control Académico</title>
  <link rel="icon" type="image/svg+xml" href="<?= e(url('assets/favicon.svg')) ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= e(url('assets/favicon-32x32.png')) ?>">
  <link rel="icon" type="image/png" sizes="64x64" href="<?= e(url('assets/favicon-64x64.png')) ?>">
  <link rel="shortcut icon" href="<?= e(url('assets/favicon.ico')) ?>">
  <link rel="stylesheet" href="<?= e(url('assets/app.css?v=' . filemtime(ROOT_PATH . '/assets/app.css'))) ?>">
</head>
<body>
  <div class="shell">
    <aside class="sidebar">
      <a class="brand <?= isNavActive('/') ? 'active' : '' ?>" href="<?= e(url()) ?>" title="Ir al Panel Principal">
        <span class="brand-mark"><?= icon('academic', 'brand-icon') ?></span>
        <span>
          <strong>Control Académico</strong>
        </span>
      </a>

      <div class="nav-label">General</div>
      <nav>
        <a class="nav-link <?= isNavActive('alumnos') ? 'active' : '' ?>" href="<?= e(url('alumnos')) ?>">
          <?= icon('users', 'nav-icon') ?>
          <span>Alumnos</span>
        </a>
        <a class="nav-link <?= isNavActive('carreras') ? 'active' : '' ?>" href="<?= e(url('carreras')) ?>">
          <?= icon('academic', 'nav-icon') ?>
          <span>Carreras</span>
        </a>
        <a class="nav-link <?= isNavActive('cursos') ? 'active' : '' ?>" href="<?= e(url('cursos')) ?>">
          <?= icon('book', 'nav-icon') ?>
          <span>Cursos</span>
        </a>
        <a class="nav-link <?= isNavActive('catedraticos') ? 'active' : '' ?>" href="<?= e(url('catedraticos')) ?>">
          <?= icon('user', 'nav-icon') ?>
          <span>Catedráticos</span>
        </a>
        <a class="nav-link <?= isNavActive('periodos') ? 'active' : '' ?>" href="<?= e(url('periodos')) ?>">
          <?= icon('calendar', 'nav-icon') ?>
          <span>Períodos</span>
        </a>
        <a class="nav-link <?= isNavActive('secciones') ? 'active' : '' ?>" href="<?= e(url('secciones')) ?>">
          <?= icon('grades', 'nav-icon') ?>
          <span>Secciones</span>
        </a>


        <div class="nav-label" style="margin-top: 14px;">Informes</div>
        <a class="nav-link <?= isNavActive('reportes/alumnos') ? 'active' : '' ?>" href="<?= e(url('reportes/alumnos')) ?>">
          <?= icon('report-users', 'nav-icon') ?>
          <span>Alumnos por carrera</span>
        </a>
        <a class="nav-link <?= isNavActive('reportes/notas') ? 'active' : '' ?>" href="<?= e(url('reportes/notas')) ?>">
          <?= icon('report-grades', 'nav-icon') ?>
          <span>Reporte de notas</span>
        </a>
        <a class="nav-link <?= isNavActive('reportes/consulta-alumno') ? 'active' : '' ?>" href="<?= e(url('reportes/consulta-alumno')) ?>">
          <?= icon('user', 'nav-icon') ?>
          <span>Consulta de alumno</span>
        </a>
      </nav>
    </aside>

    <main class="main">
      <header class="topbar">
        <div></div>
        <div class="top-user">
          <span class="user-avatar"><?= icon('user') ?></span>
          <span>
            <strong>Coordinación</strong>
            <small>Control escolar</small>
          </span>
        </div>
      </header>

      <section class="page">
        <?php if ($flash): ?>
          <div class="alert <?= e($flash['type']) ?>">
            <span><?= e($flash['message']) ?></span>
            <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="Cerrar">
              <?= icon('close') ?>
            </button>
          </div>
        <?php endif; ?>

        <?= $content ?>
      </section>

      <footer class="footer">
        Control Académico
      </footer>
    </main>
  </div>
  <script src="<?= e(url('assets/app.js?v=' . filemtime(ROOT_PATH . '/assets/app.js'))) ?>"></script>
</body>
</html>
