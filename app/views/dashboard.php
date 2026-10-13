<div class="page-heading">
  <div>
    <h1>Panel principal</h1>
  </div>
</div>

<!-- Sección: Gestión Académica -->
<section class="module-section">
  <div class="module-section-title">Gestión académica</div>
  <div class="module-cards-grid">
    <a class="module-card" href="<?= e(url('alumnos')) ?>">
      <div class="module-card-icon"><?= icon('users') ?></div>
      <div class="module-card-content">
        <h3 class="module-card-title">Alumnos</h3>
        <p class="module-card-desc">Expedientes estudiantiles, datos personales e historial de notas.</p>
      </div>
    </a>

    <a class="module-card" href="<?= e(url('carreras')) ?>">
      <div class="module-card-icon"><?= icon('academic') ?></div>
      <div class="module-card-content">
        <h3 class="module-card-title">Carreras</h3>
        <p class="module-card-desc">Planes de estudio, semestres curriculares y asignación de materias.</p>
      </div>
    </a>

    <a class="module-card" href="<?= e(url('cursos')) ?>">
      <div class="module-card-icon"><?= icon('book') ?></div>
      <div class="module-card-content">
        <h3 class="module-card-title">Cursos</h3>
        <p class="module-card-desc">Catálogo de materias y asignaturas del pensum de estudios.</p>
      </div>
    </a>

    <a class="module-card" href="<?= e(url('catedraticos')) ?>">
      <div class="module-card-icon"><?= icon('user') ?></div>
      <div class="module-card-content">
        <h3 class="module-card-title">Catedráticos</h3>
        <p class="module-card-desc">Directorio de docentes, especialidad y carga académica por ciclo.</p>
      </div>
    </a>
  </div>
</section>

<!-- Sección: Operaciones y Ciclos -->
<section class="module-section">
  <div class="module-section-title">Operaciones y ciclos</div>
  <div class="module-cards-grid">
    <a class="module-card" href="<?= e(url('periodos')) ?>">
      <div class="module-card-icon"><?= icon('calendar') ?></div>
      <div class="module-card-content">
        <h3 class="module-card-title">Períodos</h3>
        <p class="module-card-desc">Ventanas de tiempo calendario, ciclos lectivos y estados de período.</p>
      </div>
    </a>

    <a class="module-card" href="<?= e(url('secciones')) ?>">
      <div class="module-card-icon"><?= icon('grades') ?></div>
      <div class="module-card-content">
        <h3 class="module-card-title">Secciones</h3>
        <p class="module-card-desc">Apertura de cursos por ciclo, horarios, aulas y asignación de cupos.</p>
      </div>
    </a>
  </div>
</section>

<!-- Sección: Informes y Reportes -->
<section class="module-section">
  <div class="module-section-title">Informes y reportes</div>
  <div class="module-cards-grid">
    <a class="module-card" href="<?= e(url('reportes/alumnos')) ?>">
      <div class="module-card-icon"><?= icon('report-users') ?></div>
      <div class="module-card-content">
        <h3 class="module-card-title">Alumnos por carrera</h3>
        <p class="module-card-desc">Distribución de estudiantes inscritos y estadísticas por programa.</p>
      </div>
    </a>

    <a class="module-card" href="<?= e(url('reportes/notas')) ?>">
      <div class="module-card-icon"><?= icon('report-grades') ?></div>
      <div class="module-card-content">
        <h3 class="module-card-title">Reporte de notas</h3>
        <p class="module-card-desc">Consolidado general de calificaciones, aprobados y reprobados.</p>
      </div>
    </a>

    <a class="module-card" href="<?= e(url('reportes/consulta-alumno')) ?>">
      <div class="module-card-icon"><?= icon('search') ?></div>
      <div class="module-card-content">
        <h3 class="module-card-title">Consulta de alumno</h3>
        <p class="module-card-desc">Búsqueda rápida de expediente para historial y notas individuales.</p>
      </div>
    </a>
  </div>
</section>
