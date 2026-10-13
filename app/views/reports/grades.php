<?php
declare(strict_types=1);

$generatedDate = date('d/m/Y H:i');
$sel = $reportData['selected'];
$sections = $reportData['sections_data'];
$comparative = $reportData['comparative_table'];
$globalSummary = $reportData['global_summary'];
$periods = $reportData['periods'];
$careers = $reportData['careers'];
$courses = $reportData['courses'];
$availableSections = $reportData['available_sections'];

// Construir URL para exportar a Excel manteniendo filtros actuales
$excelUrl = url('reportes/notas?' . http_build_query([
    'period_id' => $sel['period_id'],
    'career_id' => $sel['career_id'],
    'course_id' => $sel['course_id'],
    'section_id' => $sel['section_id'],
    'export' => 'excel'
]));
?>
<div class="report-container">

  <!-- Encabezado Institucional (Visible únicamente en impresión / PDF) -->
  <div class="institutional-header-print">
    <div class="institutional-header-left">
      <div class="institutional-header-title">Sistema de Control Académico</div>
      <div class="institutional-header-subtitle">Reporte de Calificaciones por Sección y Curso</div>
    </div>
    <div class="institutional-header-right">
      <div><strong>Emisión:</strong> <?= e($generatedDate) ?></div>
      <div><strong>Documento Oficial de Calificaciones</strong></div>
    </div>
  </div>

  <!-- Barra Superior de Título y Acciones de Exportación -->
  <div class="report-header-row no-print">
    <h1>Notas por Carrera, Curso y Sección</h1>
    <div class="report-actions">
      <button class="btn-report-export" onclick="window.print()" title="Imprimir en PDF tamaño carta">
        <?= icon('print') ?>
        <span>Imprimir / PDF</span>
      </button>
      <a class="btn-report-export" href="<?= e($excelUrl) ?>" title="Descargar como archivo de hoja de cálculo Excel">
        <?= icon('file-spreadsheet') ?>
        <span>Excel</span>
      </a>
    </div>
  </div>

  <!-- Filtros en cascada (Período -> Carrera -> Curso -> Sección) -->
  <div class="report-filters-card no-print">
    <form class="report-filters-form" method="get" action="<?= e(url('reportes/notas')) ?>" id="grades-filter-form">
      <!-- 1. Período -->
      <div class="report-filter-item">
        <label for="f-period">Período</label>
        <select name="period_id" id="f-period" onchange="document.getElementById('grades-filter-form').submit()">
          <?php foreach ($periods as $p): ?>
            <option value="<?= e($p['id']) ?>" <?= ((int)$p['id'] === $sel['period_id']) ? 'selected' : '' ?>>
              <?= e($p['name']) ?><?= $p['status'] === 'activo' ? ' (Activo)' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- 2. Carrera -->
      <div class="report-filter-item">
        <label for="f-career">Carrera</label>
        <select name="career_id" id="f-career" onchange="document.getElementById('f-course').value='0'; document.getElementById('grades-filter-form').submit()">
          <option value="0">Todas las carreras</option>
          <?php foreach ($careers as $c): ?>
            <option value="<?= e($c['id']) ?>" <?= ((int)$c['id'] === $sel['career_id']) ? 'selected' : '' ?>>
              <?= e($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- 3. Curso -->
      <div class="report-filter-item">
        <label for="f-course">Curso</label>
        <select name="course_id" id="f-course" onchange="document.getElementById('f-section').value='0'; document.getElementById('grades-filter-form').submit()">
          <option value="0">Todos los cursos</option>
          <?php foreach ($courses as $co): ?>
            <option value="<?= e($co['id']) ?>" <?= ((int)$co['id'] === $sel['course_id']) ? 'selected' : '' ?>>
              <?= e($co['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- 4. Sección -->
      <div class="report-filter-item">
        <label for="f-section">Sección</label>
        <select name="section_id" id="f-section">
          <option value="0">Todas las secciones</option>
          <?php foreach ($availableSections as $as): ?>
            <option value="<?= e($as['id']) ?>" <?= ((int)$as['id'] === $sel['section_id']) ? 'selected' : '' ?>>
              <?= e($as['course_name']) ?> — Sección <?= e($as['code']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Botones de Acción -->
      <div class="report-filter-buttons">
        <button type="submit" class="button primary">
          <?= icon('filter') ?>
          <span>Aplicar</span>
        </button>
        <?php if ($sel['career_id'] > 0 || $sel['course_id'] > 0 || $sel['section_id'] > 0): ?>
          <a class="button secondary" href="<?= e(url('reportes/notas?period_id=' . $sel['period_id'])) ?>">
            <?= icon('x') ?>
            <span>Limpiar</span>
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <?php if (empty($sections)): ?>
    <div class="panel" style="padding:40px 20px;text-align:center;">
      <p style="margin:0;font-size:15px;color:var(--text-secondary);font-weight:600;">No se encontraron secciones ni calificaciones para los filtros seleccionados.</p>
      <p style="margin:6px 0 0;font-size:13px;color:var(--text-muted);">Pruebe seleccionando otro período o ajustando los filtros de curso y carrera.</p>
    </div>
  <?php else: ?>

    <!-- Despliegue de secciones -->
    <?php foreach ($sections as $sIdx => $sec): 
      $sum = $sec['summary'];
      $isAllSections = ($sel['section_id'] === 0);
    ?>
      <div class="report-career-block <?= ($sIdx < count($sections) - 1) ? 'page-break-after' : '' ?>" style="margin-bottom:24px;">
        
        <!-- Encabezado del resultado de la sección -->
        <div style="margin-bottom:8px;">
          <div style="font-size:15px;font-weight:700;color:var(--text-primary);">
            <?= e($sec['career_name']) ?> &nbsp;·&nbsp; <?= e($sec['course_name']) ?> &nbsp;·&nbsp; Sección <?= e($sec['section_code']) ?> &nbsp;·&nbsp; <?= e($sec['period_name']) ?>
          </div>
          <div style="font-size:13px;color:var(--text-muted);margin-top:2px;">
            Catedrático: <strong><?= e($sec['professor_name']) ?></strong> &nbsp;·&nbsp; Inscritos: <strong><?= $sum['enrolled'] ?></strong><?= !empty($sec['classroom']) ? ' &nbsp;·&nbsp; Aula: ' . e($sec['classroom']) : '' ?>
          </div>
        </div>

        <!-- Resumen por sección (una línea sobria) -->
        <div class="report-summary-bar">
          <div class="report-summary-text">
            <span>Inscritos: <strong><?= $sum['enrolled'] ?></strong></span>
            <span class="report-summary-sep">·</span>
            <span>Aprobados: <strong><?= $sum['approved'] ?></strong></span>
            <span class="report-summary-sep">·</span>
            <span>Reprobados: <strong><?= $sum['failed'] ?></strong></span>
            <span class="report-summary-sep">·</span>
            <span>Retirados: <strong><?= $sum['withdrawn'] ?></strong></span>
            <span class="report-summary-sep">·</span>
            <span>Promedio: <strong><?= $sum['average'] !== null ? number_format((float)$sum['average'], 1) : '—' ?></strong></span>
            <span class="report-summary-sep">·</span>
            <span>Nota máx: <strong><?= $sum['max_score'] !== null ? number_format((float)$sum['max_score'], 0) : '—' ?></strong></span>
            <span class="report-summary-sep">·</span>
            <span>Nota mín: <strong><?= $sum['min_score'] !== null ? number_format((float)$sum['min_score'], 0) : '—' ?></strong></span>
          </div>
        </div>

        <!-- Tabla de detalle de alumnos -->
        <div class="report-table-wrapper" style="margin-top:8px;">
          <table class="report-table">
            <thead>
              <tr>
                <th class="text-right" style="width:44px;">#</th>
                <th class="text-left" style="width:120px;">Código</th>
                <th class="text-left">Apellidos y nombres</th>
                <th class="text-right" style="width:110px;">Nota final</th>
                <th class="text-left" style="width:130px;">Estado</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($sec['students'])): ?>
                <tr>
                  <td colspan="5" class="text-center" style="padding:20px;color:var(--text-muted);">
                    No hay estudiantes inscritos en esta sección.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($sec['students'] as $st): ?>
                  <tr>
                    <td class="text-right muted" style="font-weight:600;"><?= $st['num'] ?></td>
                    <td class="text-left code-cell"><?= e($st['student_code']) ?></td>
                    <td class="text-left">
                      <a class="row-link" href="<?= e(url('reportes/consulta-alumno?id=' . $st['student_id'])) ?>" title="Ver ficha del alumno">
                        <?= e($st['full_name_sort']) ?>
                      </a>
                    </td>
                    <td class="text-right <?= $st['is_reprobado'] ? 'score-failed' : '' ?>" style="font-weight:600;">
                      <?= $st['final_score'] !== null ? number_format((float)$st['final_score'], 0) : '—' ?>
                    </td>
                    <td class="text-left <?= $st['is_reprobado'] ? 'text-reprobado' : '' ?>" style="text-transform:capitalize;">
                      <?= e($st['status']) ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
    <?php endforeach; ?>

    <!-- Cuadro Comparativo Gerencial (Solo cuando se eligen "todas las secciones" o hay más de una sección) -->
    <?php if ($sel['section_id'] === 0 && count($comparative) > 1): ?>
      <div class="comparative-card">
        <div class="comparative-card-header">
          <h2>Cuadro Comparativo por Sección</h2>
        </div>
        <div class="table-wrap" style="margin:0;border:none;">
          <table class="report-table">
            <thead>
              <tr>
                <th class="text-left">Sección / Curso</th>
                <th class="text-left">Catedrático</th>
                <th class="text-right" style="width:110px;">Inscritos</th>
                <th class="text-right" style="width:110px;">Aprobados</th>
                <th class="text-right" style="width:130px;">% Aprobación</th>
                <th class="text-right" style="width:110px;">Promedio</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($comparative as $cRow): ?>
                <tr>
                  <td class="text-left" style="font-weight:600;">
                    Sección <?= e($cRow['section_code']) ?> &nbsp;<span style="font-weight:400;color:var(--text-muted);">(<?= e($cRow['course_name']) ?>)</span>
                  </td>
                  <td class="text-left"><?= e($cRow['professor_name']) ?></td>
                  <td class="text-right"><?= $cRow['enrolled'] ?></td>
                  <td class="text-right" style="font-weight:600;"><?= $cRow['approved'] ?></td>
                  <td class="text-right"><?= number_format((float)$cRow['approval_rate'], 1) ?>%</td>
                  <td class="text-right" style="font-weight:600;"><?= is_numeric($cRow['average']) ? number_format((float)$cRow['average'], 1) : $cRow['average'] ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr class="subtotal-row">
                <td colspan="2" class="text-left">TOTALES / RESUMEN GENERAL</td>
                <td class="text-right"><?= $globalSummary['enrolled'] ?></td>
                <td class="text-right"><?= $globalSummary['approved'] ?></td>
                <td class="text-right"><?= number_format((float)$globalSummary['approval_rate'], 1) ?>%</td>
                <td class="text-right"><?= number_format((float)$globalSummary['average'], 1) ?></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    <?php endif; ?>

    <!-- Pie del Reporte -->
    <div class="report-footer-meta">
      <div>Generado el <?= e($generatedDate) ?> · Sistema de Control Académico</div>
      <div>Página 1 de 1</div>
    </div>

  <?php endif; ?>

</div>
