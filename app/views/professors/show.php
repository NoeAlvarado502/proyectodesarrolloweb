<?php
$fullName = e($professor['first_name'] . ' ' . $professor['last_name']);
$totalActive = count($activeSections);
$totalHistorical = count($allSections);
$totalEnrolledActive = array_sum(array_column($activeSections, 'enrolled_count'));
?>

<div class="page-heading">
  <div>
    <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 4px;">
      <a href="<?= e(url('catedraticos')) ?>" style="color: inherit; text-decoration: none;">Catedráticos</a> &rsaquo; <span>Ficha del docente</span>
    </div>
    <h1><?= $fullName ?></h1>
  </div>
  <div class="page-actions">
    <a class="button secondary small" href="<?= e(url('catedraticos')) ?>">
      <?= icon('arrow-left') ?>
      <span>Volver</span>
    </a>
    <a class="button primary small" href="<?= e(url('catedraticos/' . $professor['id'] . '/editar')) ?>">
      <?= icon('edit') ?>
      <span>Editar</span>
    </a>
  </div>
</div>

<!-- Profile Card -->
<div class="student-profile-panel">
  <div class="student-profile-left">
    <img class="profile-photo-lg" src="<?= e(photoUrl($professor['photo'] ?? null)) ?>" alt="Foto de <?= $fullName ?>">
    <div class="profile-name-block">
      <h2><?= $fullName ?></h2>
      <div style="display: flex; gap: 8px; align-items: center; margin-top: 4px;">
        <span class="profile-id-badge"><?= e($professor['code']) ?></span>
        <?php if ($professor['status'] === 'activo'): ?>
          <span class="status-badge badge-green">Activo</span>
        <?php else: ?>
          <span class="status-badge badge-neutral">Inactivo</span>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="student-profile-details">
    <div class="profile-detail-item">
      <span class="profile-detail-label">Especialidad</span>
      <span class="profile-detail-val"><?= e($professor['specialty'] ?: 'Sin especificar') ?></span>
    </div>
    <div class="profile-detail-item">
      <span class="profile-detail-label">Documento (DPI)</span>
      <span class="profile-detail-val"><?= e($professor['document']) ?></span>
    </div>
    <div class="profile-detail-item">
      <span class="profile-detail-label">Correo electrónico</span>
      <span class="profile-detail-val"><?= e($professor['email']) ?></span>
    </div>
    <div class="profile-detail-item">
      <span class="profile-detail-label">Teléfono</span>
      <span class="profile-detail-val"><?= e($professor['phone'] ?: 'No registrado') ?></span>
    </div>
  </div>
</div>

<!-- KPIs -->
<div class="report-kpis">
  <div class="kpi-card">
    <div class="kpi-icon"><?= icon('book', 'kpi-svg') ?></div>
    <div>
      <div class="kpi-val"><?= $totalActive ?></div>
      <div class="kpi-label">Secciones (período actual)</div>
    </div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon"><?= icon('users', 'kpi-svg') ?></div>
    <div>
      <div class="kpi-val"><?= $totalEnrolledActive ?></div>
      <div class="kpi-label">Alumnos a cargo (actual)</div>
    </div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon"><?= icon('calendar', 'kpi-svg') ?></div>
    <div>
      <div class="kpi-val"><?= $totalHistorical ?></div>
      <div class="kpi-label">Total histórico de secciones</div>
    </div>
  </div>
</div>

<!-- Academic Load (Current Period) -->
<div class="panel" style="margin-bottom: 24px;">
  <div class="panel-header" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid var(--border-subtle);">
    <div>
      <h3 style="font-size: 16px; margin: 0; font-weight: 700; color: var(--text-primary);">
        Carga académica <?= $currentPeriod ? '&mdash; ' . e($currentPeriod['name']) : '' ?>
      </h3>
      <div style="font-size: 12.5px; color: var(--text-muted); margin-top: 2px;">
        Secciones que dicta el docente en el período lectivo actual
      </div>
    </div>
  </div>

  <?php if (empty($activeSections)): ?>
    <div style="padding: 36px 20px; text-align: center; color: var(--text-muted); font-size: 14px;">
      El catedrático no tiene secciones asignadas en el período activo actual.
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Curso</th>
            <th>Sección</th>
            <th>Horario</th>
            <th>Aula</th>
            <th>Cupo</th>
            <th>Inscritos</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($activeSections as $sec): ?>
            <tr>
              <td data-label="Curso">
                <strong><?= e($sec['course_name']) ?></strong>
              </td>
              <td data-label="Sección">
                <span class="status-badge badge-neutral" style="font-family: monospace;">
                  <?= e($sec['code']) ?>
                </span>
              </td>
              <td data-label="Horario">
                <span class="muted"><?= e($sec['schedule'] ?: '&mdash;') ?></span>
              </td>
              <td data-label="Aula">
                <span class="muted"><?= e($sec['classroom'] ?: '&mdash;') ?></span>
              </td>
              <td data-label="Cupo">
                <span class="muted"><?= (int)$sec['capacity'] ?></span>
              </td>
              <td data-label="Inscritos">
                <span class="status-badge <?= (int)$sec['enrolled_count'] >= (int)$sec['capacity'] ? 'badge-amber' : 'badge-neutral' ?>">
                  <?= (int)$sec['enrolled_count'] ?> / <?= (int)$sec['capacity'] ?>
                </span>
              </td>
              <td data-label="Acciones" style="text-align: right;">
                <a class="button secondary small" href="<?= e(url('secciones/' . $sec['id'])) ?>">
                  <span>Ver sección</span>
                  <?= icon('arrow-right') ?>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- History of Sections -->
<div class="panel">
  <div class="panel-header" style="padding: 16px 20px; border-bottom: 1px solid var(--border-subtle);">
    <h3 style="font-size: 16px; margin: 0; font-weight: 700; color: var(--text-primary);">
      Historial de secciones dictadas
    </h3>
    <div style="font-size: 12.5px; color: var(--text-muted); margin-top: 2px;">
      Registro histórico completo de cursos y períodos anteriores
    </div>
  </div>

  <?php if (empty($allSections)): ?>
    <div style="padding: 36px 20px; text-align: center; color: var(--text-muted); font-size: 14px;">
      No hay historial de secciones registradas para este catedrático.
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Período</th>
            <th>Curso</th>
            <th>Sección</th>
            <th>Horario</th>
            <th>Alumnos inscritos</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($allSections as $hSec): ?>
            <tr>
              <td data-label="Período">
                <strong><?= e($hSec['period_name']) ?></strong>
                <span class="status-badge <?= $hSec['period_status'] === 'activo' ? 'badge-green' : 'badge-neutral' ?>" style="font-size: 11px; margin-left: 6px;">
                  <?= e(ucfirst($hSec['period_status'])) ?>
                </span>
              </td>
              <td data-label="Curso"><?= e($hSec['course_name']) ?></td>
              <td data-label="Sección">
                <span class="status-badge badge-neutral" style="font-family: monospace;">
                  <?= e($hSec['code']) ?>
                </span>
              </td>
              <td data-label="Horario">
                <span class="muted"><?= e($hSec['schedule'] ?: '&mdash;') ?></span>
              </td>
              <td data-label="Inscritos">
                <span class="muted"><?= (int)$hSec['enrolled_count'] ?> estudiantes</span>
              </td>
              <td data-label="Acciones" style="text-align: right;">
                <a class="button secondary small" href="<?= e(url('secciones/' . $hSec['id'])) ?>">
                  <span>Detalle</span>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
