<?php
$generated = date('d/m/Y H:i');
?>
<div class="page-heading">
  <div>
    <h1>Consulta de Alumno</h1>
  </div>
  <?php if ($student): ?>
    <div class="heading-actions no-print">
      <button class="button secondary" onclick="window.print()">
        <?= icon('print') ?>
        <span>Imprimir informe</span>
      </button>
      <a class="button primary" href="<?= e(url('reportes/consulta-alumno')) ?>">
        <?= icon('search') ?>
        <span>Buscar otro alumno</span>
      </a>
    </div>
  <?php endif; ?>
</div>

<!-- Search & Selection Panel (hidden when printing) -->
<div class="panel no-print" style="margin-bottom: 22px;">
  <form class="toolbar" method="get" action="<?= e(url('reportes/consulta-alumno')) ?>">
    <div class="searchbox" style="flex:1;">
      <?= icon('search') ?>
      <input name="q" 
             value="<?= e($q) ?>" 
             placeholder="Buscar alumno por nombre o apellido..."
             data-filter="alpha-search"
             pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s'\-]*"
             maxlength="100"
             title="Solo se permiten letras y espacios"
             autocomplete="off">
    </div>
    <button class="button primary">
      <?= icon('search') ?>
      <span>Buscar</span>
    </button>
    <?php if ($q !== '' || $id > 0): ?>
      <a class="button secondary" href="<?= e(url('reportes/consulta-alumno')) ?>">
        <?= icon('x') ?>
        <span>Ver todos</span>
      </a>
    <?php endif; ?>
  </form>

  <!-- Quick Dropdown Selector -->
  <?php if ($studentList): ?>
    <div style="padding: 12px 20px; border-top: 1px solid var(--border-subtle); display:flex; align-items:center; gap:12px; background:var(--bg-subtle);">
      <span style="font-size:12.5px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.04em;">Selección rápida:</span>
      <select onchange="if(this.value) window.location.href='<?= e(url('reportes/consulta-alumno?id=')) ?>' + this.value" style="flex:1;max-width:420px;height:36px;font-size:13.5px;">
        <option value="">-- Selecciona un estudiante para consultar --</option>
        <?php foreach ($studentList as $s): ?>
          <option value="<?= e($s['id']) ?>" <?= ($student && $student['id'] == $s['id']) ? 'selected' : '' ?>>
            <?= e($s['last_name'] . ', ' . $s['first_name']) ?> (AC-<?= str_pad((string)$s['id'], 5, '0', STR_PAD_LEFT) ?>) - <?= e($s['career']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  <?php endif; ?>
</div>

<?php if ($student): ?>
  <?php
  $fullName = e($student['first_name'] . ' ' . $student['last_name']);
  $expediente = 'AC-' . str_pad((string)$student['id'], 5, '0', STR_PAD_LEFT);
  $totalCourses = count($grades);
  $approved = count(array_filter($grades, fn($g) => $g['score'] >= 60));
  $failed = $totalCourses - $approved;
  $avg = $grades ? round(array_sum(array_column($grades, 'score')) / $totalCourses, 1) : 0;
  $age = $student['birth_date'] ? (new DateTime($student['birth_date']))->diff(new DateTime('today'))->y : '—';
  
  $bySemester = [];
  foreach ($grades as $g) {
    $bySemester[$g['semester']][] = $g;
  }
  ?>

  <!-- Student Info Card -->
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
        <span class="profile-detail-label">Fecha de registro</span>
        <span class="profile-detail-val"><?= e(date('d/m/Y', strtotime($student['created_at']))) ?></span>
      </div>
    </div>
  </div>

  <!-- KPI Metrics -->
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

  <!-- Grade History Panel -->
  <div class="panel">
    <div class="panel-heading report-heading">
      <div>
        <h2>Historial académico completo</h2>
        <p><?= $totalCourses ?> curso<?= $totalCourses !== 1 ? 's' : '' ?> registrado<?= $totalCourses !== 1 ? 's' : '' ?> en el historial</p>
      </div>
    </div>

    <?php if (!$grades): ?>
      <div class="empty compact">
        <div class="empty-icon"><?= icon('grades') ?></div>
        <strong>Sin calificaciones registradas para este alumno</strong>
        <p>Aún no se han capturado calificaciones para este estudiante.</p>
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
                  <th colspan="2">Subtotal <?= e($semName) ?></th>
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
    <p>Control Académico &nbsp;·&nbsp; Consulta de Alumno &nbsp;·&nbsp; <?= $expediente ?> &nbsp;·&nbsp; <?= $fullName ?> &nbsp;·&nbsp; Generado el <?= $generated ?></p>
  </div>

<?php else: ?>
  <!-- Directory / Search results when no student is selected -->
  <div class="panel">
    <div class="panel-heading">
      <div>
        <h2>Selecciona un alumno para consultar</h2>
        <p><?= count($studentList) ?> alumno<?= count($studentList) !== 1 ? 's' : '' ?> disponible<?= count($studentList) !== 1 ? 's' : '' ?></p>
      </div>
    </div>

    <?php if (!$studentList): ?>
      <div class="empty compact">
        <div class="empty-icon"><?= icon('search') ?></div>
        <strong>No encontramos alumnos con ese criterio</strong>
        <p>Intenta con otro nombre o apellido.</p>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Expediente</th>
              <th>Alumno</th>
              <th>Carrera</th>
              <th>Fecha de nacimiento</th>
              <th style="text-align:right;">Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($studentList as $s): ?>
              <tr>
                <td data-label="Expediente" class="muted" style="font-family:monospace;font-weight:600;">AC-<?= str_pad((string)$s['id'], 5, '0', STR_PAD_LEFT) ?></td>
                <td data-label="Alumno">
                  <div style="display:flex;align-items:center;gap:10px;">
                    <img class="mini-photo" src="<?= e(photoUrl($s['photo'])) ?>" alt="">
                    <strong><?= e($s['first_name'] . ' ' . $s['last_name']) ?></strong>
                  </div>
                </td>
                <td data-label="Carrera" class="muted"><?= e($s['career']) ?></td>
                <td data-label="Nacimiento"><?= e(date('d/m/Y', strtotime($s['birth_date']))) ?></td>
                <td data-label="Acción" style="text-align:right;">
                  <a class="button secondary" style="height:32px;padding:0 12px;font-size:13px;" href="<?= e(url('reportes/consulta-alumno?id=' . $s['id'])) ?>">
                    <?= icon('search') ?>
                    <span>Consultar</span>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>
