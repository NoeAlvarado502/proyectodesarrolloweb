<?php
$total = $totalStudents;
$generated = date('d/m/Y H:i');
?>
<div class="page-heading">
  <div>
    <h1>Alumnos por Carrera</h1>
  </div>
  <div class="heading-actions">
    <button class="button secondary no-print" onclick="window.print()">
      <?= icon('print') ?>
      <span>Imprimir informe</span>
    </button>
  </div>
</div>

<!-- KPI Cards -->
<div class="report-kpis">
  <div class="kpi-card">
    <div class="kpi-icon"><?= icon('users', 'kpi-svg') ?></div>
    <div>
      <div class="kpi-val"><?= $total ?></div>
      <div class="kpi-label">Alumnos registrados</div>
    </div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon"><?= icon('academic', 'kpi-svg') ?></div>
    <div>
      <div class="kpi-val"><?= $totalCareers ?></div>
      <div class="kpi-label">Programas académicos</div>
    </div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon"><?= icon('report-users', 'kpi-svg') ?></div>
    <div>
      <div class="kpi-val"><?= $totalCareers > 0 ? round($total / $totalCareers, 1) : 0 ?></div>
      <div class="kpi-label">Promedio por carrera</div>
    </div>
  </div>
  <div class="kpi-card print-only" style="display:none;">
    <div>
      <div class="kpi-val" style="font-size:13px;font-weight:500;color:var(--text-muted);">Generado: <?= $generated ?></div>
    </div>
  </div>
</div>

<!-- Distribution Table -->
<div class="panel" style="margin-bottom:22px;">
  <div class="panel-heading report-heading">
    <div>
      <h2>Distribución por carrera</h2>
      <p><?= $total ?> alumno<?= $total !== 1 ? 's' : '' ?> en <?= $totalCareers ?> programa<?= $totalCareers !== 1 ? 's' : '' ?></p>
    </div>
  </div>

  <?php if (!$rows): ?>
    <div class="empty compact">
      <strong>No hay carreras registradas</strong>
      <p>Agrega carreras en el apartado de Catálogos para ver la distribución.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Carrera</th>
            <th>Alumnos inscritos</th>
            <th>Participación</th>
            <th class="no-print">Acceso rápido</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $i => $row):
            $percent = $total ? round($row['total'] * 100 / $total, 1) : 0;
            $barW = $total ? round($row['total'] * 100 / $total) : 0;
          ?>
            <tr>
              <td data-label="#" class="muted" style="width:36px;font-weight:600;"><?= $i + 1 ?></td>
              <td data-label="Carrera">
                <strong><?= e($row['career']) ?></strong>
              </td>
              <td data-label="Inscritos">
                <span style="font-size:16px;font-weight:700;color:var(--text-primary);"><?= e($row['total']) ?></span>
                <span style="font-size:13px;color:var(--text-muted);"> alumno<?= $row['total'] != 1 ? 's' : '' ?></span>
              </td>
              <td data-label="Participación">
                <div class="bar-cell">
                  <div class="bar-track" style="width:160px;">
                    <span style="width:<?= $barW ?>%;"></span>
                  </div>
                  <small style="font-weight:600;min-width:42px;"><?= $percent ?>%</small>
                </div>
              </td>
              <td data-label="Acciones" class="no-print">
                <a class="row-action" href="<?= e(url('reportes/notas?career_id=' . $row['id'])) ?>">
                  <span>Ver notas</span>
                  <?= icon('arrow-right') ?>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <th colspan="2">Total general</th>
            <th><?= $total ?> alumnos</th>
            <th>100%</th>
            <th class="no-print"></th>
          </tr>
        </tfoot>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- Full Student Directory -->
<div class="panel">
  <div class="panel-heading report-heading">
    <div>
      <h2>Directorio completo de alumnos</h2>
      <p><?= count($students) ?> expediente<?= count($students) !== 1 ? 's' : '' ?> en el sistema</p>
    </div>
  </div>

  <?php if (!$students): ?>
    <div class="empty compact">
      <strong>No hay alumnos registrados</strong>
      <p>Registra el primer alumno desde el módulo de Alumnos.</p>
    </div>
  <?php else: ?>
    <?php
    // Group students by career
    $byCareer = [];
    foreach ($students as $s) {
      $byCareer[$s['career']][] = $s;
    }
    ?>
    <?php foreach ($byCareer as $careerName => $careerStudents): ?>
      <div class="career-section">
        <div class="career-section-header">
          <?= icon('academic', 'career-section-icon') ?>
          <span><?= e($careerName) ?></span>
          <span class="career-count"><?= count($careerStudents) ?> alumno<?= count($careerStudents) !== 1 ? 's' : '' ?></span>
        </div>
        <div class="table-wrap" style="border-top:none;">
          <table>
            <thead>
              <tr>
                <th>Expediente</th>
                <th>Nombre completo</th>
                <th>Fecha de nacimiento</th>
                <th>Edad</th>
                <th class="no-print">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($careerStudents as $s):
                $birthDate = new DateTime($s['birth_date']);
                $age = $birthDate->diff(new DateTime('today'))->y;
              ?>
                <tr>
                  <td data-label="Expediente" class="muted" style="font-weight:600;font-family:monospace;">AC-<?= str_pad((string)$s['id'], 5, '0', STR_PAD_LEFT) ?></td>
                  <td data-label="Nombre">
                    <div style="display:flex;align-items:center;gap:10px;">
                      <img class="mini-photo" src="<?= e(photoUrl($s['photo'])) ?>" alt="">
                      <strong><?= e($s['first_name'] . ' ' . $s['last_name']) ?></strong>
                    </div>
                  </td>
                  <td data-label="Nacimiento"><?= e(date('d/m/Y', strtotime($s['birth_date']))) ?></td>
                  <td data-label="Edad"><?= $age ?> años</td>
                  <td data-label="Acciones" class="no-print" style="text-align:right;">
                    <a class="row-action" href="<?= e(url('reportes/consulta-alumno?id=' . $s['id'])) ?>">
                      <span>Consultar</span>
                      <?= icon('arrow-right') ?>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<div class="print-footer">
  <p>Control Académico &nbsp;·&nbsp; Reporte generado el <?= $generated ?> &nbsp;·&nbsp; <?= $total ?> alumno<?= $total !== 1 ? 's' : '' ?> en <?= $totalCareers ?> carrera<?= $totalCareers !== 1 ? 's' : '' ?></p>
</div>
