<?php
$generated = date('d/m/Y H:i');
$total = count($rows);
?>
<div class="page-heading">
  <div>
    <h1>Reporte de Notas</h1>
  </div>
  <div class="heading-actions">
    <button class="button secondary no-print" onclick="window.print()">
      <?= icon('print') ?>
      <span>Imprimir informe</span>
    </button>
  </div>
</div>

<!-- Filter Bar -->
<div class="panel no-print" style="margin-bottom:22px;">
  <form class="grades-filter-form" method="get" action="<?= e(url('reportes/notas')) ?>">
    <div class="filter-row">
      <label>
        <span class="filter-label">Carrera</span>
        <select name="career_id" id="rpt-career">
          <option value="0">Todas las carreras</option>
          <?php foreach ($careers as $c): ?>
            <option value="<?= e($c['id']) ?>" <?= $career === $c['id'] ? 'selected' : '' ?>>
              <?= e($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>
        <span class="filter-label">Curso</span>
        <select name="course_id">
          <option value="0">Todos los cursos</option>
          <?php foreach ($courses as $c): ?>
            <option value="<?= e($c['id']) ?>" <?= $course === $c['id'] ? 'selected' : '' ?>>
              <?= e($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>
        <span class="filter-label">Sección</span>
        <select name="section_id">
          <option value="0">Todas las secciones</option>
          <?php foreach ($sections as $c): ?>
            <option value="<?= e($c['id']) ?>" <?= $section === $c['id'] ? 'selected' : '' ?>>
              <?= e($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>
        <span class="filter-label">Semestre</span>
        <select name="semester_id">
          <option value="0">Todos los semestres</option>
          <?php foreach ($semesters as $sm): ?>
            <option value="<?= e($sm['id']) ?>" <?= $semester === $sm['id'] ? 'selected' : '' ?>>
              <?= e($sm['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <button class="button primary">
        <?= icon('search') ?>
        <span>Filtrar</span>
      </button>
      <?php if ($career || $course || $section || $semester): ?>
        <a class="button secondary" href="<?= e(url('reportes/notas')) ?>">
          <?= icon('x') ?>
          <span>Limpiar</span>
        </a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- KPI Summary -->
<?php if ($rows): ?>
<div class="report-kpis" style="margin-bottom:22px;">
  <div class="kpi-card">
    <div class="kpi-icon"><?= icon('grades', 'kpi-svg') ?></div>
    <div>
      <div class="kpi-val"><?= $total ?></div>
      <div class="kpi-label">Calificaciones</div>
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
  <?php if ($total > 0): ?>
  <div class="kpi-card">
    <div class="kpi-icon"><?= icon('percent', 'kpi-svg') ?></div>
    <div>
      <div class="kpi-val"><?= round($approved / $total * 100, 1) ?>%</div>
      <div class="kpi-label">Tasa de aprobación</div>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- Results Table -->
<div class="panel">
  <div class="panel-heading report-heading">
    <div>
      <h2>Resultados</h2>
      <p>
        <?php
        $parts = [];
        if ($career) { foreach ($careers as $c) { if ($c['id']==$career) $parts[] = $c['name']; } }
        if ($course) { foreach ($courses as $c) { if ($c['id']==$course) $parts[] = $c['name']; } }
        if ($section) { foreach ($sections as $c) { if ($c['id']==$section) $parts[] = 'Sección '.$c['name']; } }
        if ($semester) { foreach ($semesters as $sm) { if ($sm['id']==$semester) $parts[] = $sm['name']; } }
        echo $parts ? e(implode(' · ', $parts)) : 'Todos los registros';
        ?>
        &nbsp;·&nbsp; <?= $total ?> calificación<?= $total !== 1 ? 'es' : '' ?>
      </p>
    </div>
  </div>

  <?php if (!$rows): ?>
    <div class="empty compact">
      <div class="empty-icon"><?= icon('grades') ?></div>
      <strong>No hay calificaciones con estos criterios</strong>
      <p>Prueba seleccionando otros filtros o ingresa las notas de un alumno.</p>
    </div>
  <?php else: ?>
    <?php
    // Group by semester for better reading
    $bySemester = [];
    foreach ($rows as $r) { $bySemester[$r['semester']][] = $r; }
    ?>
    <?php foreach ($bySemester as $semName => $semRows):
      $semApproved = count(array_filter($semRows, fn($r) => $r['score'] >= 60));
      $semAvg = round(array_sum(array_column($semRows,'score')) / count($semRows), 1);
    ?>
      <div class="semester-section">
        <div class="semester-header">
          <?= icon('calendar', 'semester-icon') ?>
          <span><?= e($semName) ?></span>
          <span class="semester-meta"><?= count($semRows) ?> notas &nbsp;·&nbsp; Promedio: <strong><?= $semAvg ?></strong> &nbsp;·&nbsp; <?= $semApproved ?>/<?= count($semRows) ?> aprobados</span>
        </div>
        <div class="table-wrap" style="border-top:none;">
          <table>
            <thead>
              <tr>
                <th>Alumno</th>
                <th>Carrera</th>
                <th>Curso</th>
                <th>Sección</th>
                <th>Nota</th>
                <th>Estado</th>
                <th class="no-print">Perfil</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($semRows as $row): ?>
                <tr>
                  <td data-label="Alumno"><strong><?= e($row['first_name'] . ' ' . $row['last_name']) ?></strong></td>
                  <td data-label="Carrera" class="muted"><?= e($row['career']) ?></td>
                  <td data-label="Curso"><?= e($row['course']) ?></td>
                  <td data-label="Sección"><?= e($row['section']) ?></td>
                  <td data-label="Nota">
                    <strong><?= e($row['score']) ?></strong>
                    <span class="muted" style="font-size:12px;">/ 100</span>
                  </td>
                  <td data-label="Estado">
                    <span class="badge <?= $row['score'] >= 60 ? 'badge-green' : 'badge-red' ?>">
                      <?= $row['score'] >= 60 ? 'Aprobado' : 'Reprobado' ?>
                    </span>
                  </td>
                  <td data-label="Acciones" class="no-print" style="text-align:right;">
                    <a class="row-action" href="<?= e(url('reportes/consulta-alumno?id=' . $row['student_id'])) ?>" title="Consultar expediente">
                      <span>Ver ficha</span>
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
  <p>Control Académico &nbsp;·&nbsp; Reporte de Notas &nbsp;·&nbsp; Generado el <?= $generated ?></p>
</div>
