<?php
$fullName = e($student['first_name'] . ' ' . $student['last_name']);
$expediente = 'AC-' . str_pad((string)$student['id'], 5, '0', STR_PAD_LEFT);
$totalCourses = count($grades);
$approved = count(array_filter($grades, fn($g) => $g['score'] >= 60));
$failed = $totalCourses - $approved;
$avg = $grades ? round(array_sum(array_column($grades, 'score')) / $totalCourses, 1) : 0;
$age = $student['birth_date'] ? (new DateTime($student['birth_date']))->diff(new DateTime('today'))->y : '—';
$generated = date('d/m/Y H:i');

// Group grades by semester
$bySemester = [];
foreach ($grades as $g) {
  $bySemester[$g['semester']][] = $g;
}
?>

<div class="page-heading">
  <div>
    <h1><?= $fullName ?></h1>
  </div>
  <div class="heading-actions">
    <button class="button secondary no-print" onclick="window.print()">
      <?= icon('print') ?>
      <span>Imprimir expediente</span>
    </button>
    <a class="button primary no-print" href="<?= e(url('alumnos/' . $student['id'] . '/editar')) ?>">
      <?= icon('edit') ?>
      <span>Editar</span>
    </a>
  </div>
</div>

<!-- Profile Card -->
<div class="student-profile-panel">
  <div class="student-profile-left">
    <img class="profile-photo-lg" src="<?= e(photoUrl($student['photo'])) ?>" alt="Foto de <?= $fullName ?>">
    <div class="profile-name-block">
      <h2><?= $fullName ?></h2>
      <div class="profile-id-badge"><?= $expediente ?></div>
    </div>
  </div>
  <div class="student-profile-details">
    <div class="profile-detail-item">
      <span class="profile-detail-label">Carrera</span>
      <span class="profile-detail-val"><?= e($student['career']) ?></span>
    </div>
    <div class="profile-detail-item">
      <span class="profile-detail-label">Fecha de nacimiento</span>
      <span class="profile-detail-val"><?= e(date('d/m/Y', strtotime($student['birth_date']))) ?></span>
    </div>
    <div class="profile-detail-item">
      <span class="profile-detail-label">Edad</span>
      <span class="profile-detail-val"><?= $age ?> años</span>
    </div>
    <div class="profile-detail-item">
      <span class="profile-detail-label">Registrado</span>
      <span class="profile-detail-val"><?= e(date('d/m/Y', strtotime($student['created_at']))) ?></span>
    </div>
  </div>
</div>

<!-- KPI Stats -->
<div class="report-kpis" style="margin-bottom:22px;">
  <div class="kpi-card">
    <div class="kpi-icon"><?= icon('grades', 'kpi-svg') ?></div>
    <div>
      <div class="kpi-val"><?= $totalCourses ?></div>
      <div class="kpi-label">Cursos registrados</div>
    </div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon"><?= icon('check', 'kpi-svg') ?></div>
    <div>
      <div class="kpi-val"><?= $approved ?></div>
      <div class="kpi-label">Aprobados</div>
    </div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon"><?= icon('x', 'kpi-svg') ?></div>
    <div>
      <div class="kpi-val"><?= $failed ?></div>
      <div class="kpi-label">Reprobados</div>
    </div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon"><?= icon('star', 'kpi-svg') ?></div>
    <div>
      <div class="kpi-val"><?= $avg ?></div>
      <div class="kpi-label">Promedio general</div>
    </div>
  </div>
  <?php if ($totalCourses > 0): ?>
  <div class="kpi-card">
    <div class="kpi-icon"><?= icon('percent', 'kpi-svg') ?></div>
    <div>
      <div class="kpi-val"><?= round($approved / $totalCourses * 100, 1) ?>%</div>
      <div class="kpi-label">Tasa de aprobación</div>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Grade History -->
<div class="panel">
  <div class="panel-heading report-heading">
    <div>
      <h2>Historial de calificaciones</h2>
      <p><?= $totalCourses ?> curso<?= $totalCourses !== 1 ? 's' : '' ?> registrado<?= $totalCourses !== 1 ? 's' : '' ?></p>
    </div>
  </div>

  <?php if (!$grades): ?>
    <div class="empty compact">
      <div class="empty-icon"><?= icon('grades') ?></div>
      <strong>Sin calificaciones registradas</strong>
      <p>Las notas que se registren en las secciones aparecerán aquí agrupadas por semestre y curso.</p>
    </div>
  <?php else: ?>
    <?php foreach ($bySemester as $semName => $semGrades):
      $semAvg = round(array_sum(array_column($semGrades,'score')) / count($semGrades), 1);
      $semApproved = count(array_filter($semGrades, fn($g) => $g['score'] >= 60));
    ?>
      <div class="semester-section">
        <div class="semester-header">
          <?= icon('calendar', 'semester-icon') ?>
          <span><?= e($semName) ?></span>
          <span class="semester-meta"><?= count($semGrades) ?> cursos &nbsp;·&nbsp; Promedio: <strong><?= $semAvg ?></strong> &nbsp;·&nbsp; <?= $semApproved ?>/<?= count($semGrades) ?> aprobados</span>
        </div>
        <div class="table-wrap" style="border-top:none;">
          <table>
            <thead>
              <tr>
                <th>Curso</th>
                <th>Sección</th>
                <th>Nota final</th>
                <th>Rendimiento</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($semGrades as $g): ?>
                <tr>
                  <td data-label="Curso"><strong><?= e($g['course']) ?></strong></td>
                  <td data-label="Sección" class="muted">Sección <?= e($g['section']) ?></td>
                  <td data-label="Nota final">
                    <strong><?= e($g['score']) ?></strong>
                    <span class="muted" style="font-size:12px;">/ 100</span>
                  </td>
                  <td data-label="Rendimiento" style="min-width:150px;">
                    <div class="bar-cell">
                      <div class="bar-track" style="width:120px;">
                        <span style="width:<?= e($g['score']) ?>%;"></span>
                      </div>
                    </div>
                  </td>
                  <td data-label="Estado">
                    <span class="badge <?= $g['score'] >= 60 ? 'badge-green' : 'badge-red' ?>">
                      <?= $g['score'] >= 60 ? 'Aprobado' : 'Reprobado' ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr>
                <th colspan="2">Subtotal semestre</th>
                <th><?= $semAvg ?> / 100</th>
                <th></th>
                <th><?= $semApproved ?> / <?= count($semGrades) ?> aprobados</th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<div class="print-footer">
  <p>Control Académico &nbsp;·&nbsp; Expediente <?= $expediente ?> &nbsp;·&nbsp; <?= $fullName ?> &nbsp;·&nbsp; Generado el <?= $generated ?></p>
</div>

<!-- Danger Zone -->
<form method="post" action="<?= e(url('alumnos/' . $student['id'] . '/eliminar')) ?>" class="danger-zone no-print" data-confirm="¿Eliminar este alumno y todo su historial de notas? Esta acción no se puede deshacer.">
  <?= csrfField() ?>
  <span>Eliminar definitivamente este expediente y su historial</span>
  <button class="button danger-outline">
    <?= icon('trash') ?>
    <span>Eliminar alumno</span>
  </button>
</form>
