<?php
declare(strict_types=1);

$generatedDate = date('d/m/Y H:i');
$careerId = (int)($careerId ?? 0);
$statusFilter = (string)($statusFilter ?? 'todos');
$query = (string)($query ?? '');
$selectedId = (int)($selectedId ?? 0);
$studentList = $studentList ?? [];
$careers = $careers ?? [];
?>
<div class="report-container">

  <!-- Encabezado Institucional (Visible únicamente en impresión / PDF) -->
  <div class="institutional-header-print">
    <div class="institutional-header-left">
      <div class="institutional-header-title">Sistema de Control Académico</div>
      <div class="institutional-header-subtitle">Constancia de Ficha y Expediente del Alumno</div>
    </div>
    <div class="institutional-header-right">
      <div><strong>Emisión:</strong> <?= e($generatedDate) ?></div>
      <div><strong>Documento Oficial</strong></div>
    </div>
  </div>

  <!-- Barra Superior en Pantalla -->
  <div class="report-header-row no-print">
    <div>
      <h1>Ficha del Alumno</h1>
      <?php if ($profile): ?>
        <div style="font-size: 13px; color: var(--text-muted); margin-top: 3px;">
          Expediente académico de <strong><?= e($profile['student']['full_name']) ?></strong> (<?= e($profile['student']['code']) ?>)
        </div>
      <?php endif; ?>
    </div>
    <div class="report-actions">
      <?php if ($profile): ?>
        <a class="btn-report-export" href="<?= e(url('reportes/consulta-alumno' . ($careerId > 0 || $statusFilter !== 'todos' || $query !== '' ? '?' . http_build_query(array_filter(['career_id' => $careerId ?: null, 'status' => $statusFilter !== 'todos' ? $statusFilter : null, 'q' => $query ?: null])) : ''))) ?>" title="Volver al listado de alumnos">
          <?= icon('arrow-left') ?>
          <span>Volver al listado</span>
        </a>
        <button class="btn-report-export" onclick="window.print()" title="Imprimir constancia o guardar como PDF">
          <?= icon('print') ?>
          <span>Imprimir / PDF</span>
        </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- Barra de Filtros: Input de búsqueda al lado de Filtros de Carrera y Estado (Ocultos en impresión) -->
  <div class="report-filters-card no-print">
    <form class="report-filters-form" method="get" action="<?= e(url('reportes/consulta-alumno')) ?>" style="align-items: center;">
      <!-- Input de búsqueda (únicamente el input) -->
      <div class="report-filter-item" style="flex: 2; min-width: 220px;">
        <?= partial('components/search_box', [
          'action' => url('reportes/consulta-alumno' . ($careerId > 0 || $statusFilter !== 'todos' ? '?' . http_build_query(array_filter(['career_id' => $careerId ?: null, 'status' => $statusFilter !== 'todos' ? $statusFilter : null])) : '')),
          'query' => $query,
          'name' => 'q',
          'placeholder' => 'Buscar',
          'id' => 'search-student'
        ]) ?>
      </div>

      <!-- Filtro Carrera al lado del input de búsqueda -->
      <div class="report-filter-item" style="flex: 1.5; min-width: 190px;">
        <select name="career_id" id="f-career" class="custom-select filter-select" data-icon="filter" onchange="this.form.submit()" aria-label="Filtrar por carrera">
          <option value="0">Todas las carreras</option>
          <?php foreach ($careers as $c): ?>
            <option value="<?= e($c['id']) ?>" <?= ((int)$c['id'] === $careerId) ? 'selected' : '' ?>>
              <?= e($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Filtro Estado al lado de carrera -->
      <div class="report-filter-item compact" style="min-width: 140px;">
        <select name="status" id="f-status" class="custom-select filter-select" data-icon="filter" onchange="this.form.submit()" aria-label="Filtrar por estado">
          <option value="todos" <?= ($statusFilter === 'todos') ? 'selected' : '' ?>>Todos los estados</option>
          <option value="activo" <?= ($statusFilter === 'activo') ? 'selected' : '' ?>>Activo</option>
          <option value="inactivo" <?= ($statusFilter === 'inactivo') ? 'selected' : '' ?>>Inactivo</option>
          <option value="graduado" <?= ($statusFilter === 'graduado') ? 'selected' : '' ?>>Graduado</option>
          <option value="retirado" <?= ($statusFilter === 'retirado') ? 'selected' : '' ?>>Retirado</option>
        </select>
      </div>

      <!-- Botón para limpiar filtros si hay alguno activo -->
      <?php if ($query !== '' || $careerId > 0 || ($statusFilter !== 'todos' && $statusFilter !== '')): ?>
        <div class="report-filter-buttons" style="margin-left: auto;">
          <a class="button secondary small" href="<?= e(url('reportes/consulta-alumno')) ?>" title="Restablecer filtros">
            <?= icon('x') ?>
            <span>Limpiar filtros</span>
          </a>
        </div>
      <?php endif; ?>
    </form>
  </div>

  <?php if (!$profile): ?>
    <div class="panel" style="margin-top: 16px; border: 1px solid var(--border); border-radius: var(--radius-md); overflow: hidden;">
      <div style="padding: 12px 18px; border-bottom: 1px solid var(--border-subtle); display: flex; justify-content: space-between; align-items: center; background: var(--bg-surface);">
        <div>
          <h3 style="margin: 0; font-size: 15px; font-weight: 600; color: var(--text-primary);">
            Listado de Alumnos
          </h3>
          <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
            <?= count($studentList) ?> <?= count($studentList) === 1 ? 'alumno encontrado' : 'alumnos encontrados' ?>
            <?php if ($query !== '' || $careerId > 0 || ($statusFilter !== 'todos' && $statusFilter !== '')): ?>
              · con los filtros aplicados
            <?php endif; ?>
          </div>
        </div>
      </div>

      <?php if (empty($studentList)): ?>
        <div style="padding: 40px 20px; text-align: center; color: var(--text-muted);">
          <p style="margin: 0; font-size: 15px; font-weight: 600; color: var(--text-secondary);">No se encontró ningún alumno con los criterios ingresados.</p>
          <p style="margin: 6px 0 0; font-size: 13px;">Verifique los filtros o el término de búsqueda e intente nuevamente.</p>
          <div style="margin-top: 14px;">
            <a class="button secondary small" href="<?= e(url('reportes/consulta-alumno')) ?>">
              <?= icon('x') ?>
              <span>Limpiar filtros</span>
            </a>
          </div>
        </div>
      <?php else: ?>
        <div class="table-wrap" style="margin: 0; border: none;">
          <table class="report-table">
            <thead>
              <tr>
                <th class="text-left" style="width: 120px;">Carné</th>
                <th class="text-left">Estudiante</th>
                <th class="text-left">Carrera</th>
                <th class="text-left" style="width: 130px;">Estado</th>
                <th class="text-right" style="width: 170px;">Acción</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($studentList as $s): ?>
                <tr>
                  <td class="text-left code-cell">
                    <strong><?= e($s['code']) ?></strong>
                  </td>
                  <td class="text-left">
                    <span class="person-cell">
                      <?php if (!empty($s['photo'])): ?>
                        <img class="mini-photo" src="<?= e(photoUrl($s['photo'])) ?>" alt="Foto de <?= e($s['full_name']) ?>">
                      <?php else: ?>
                        <span class="mini-avatar"><?= icon('user') ?></span>
                      <?php endif; ?>
                      <strong><?= e($s['full_name']) ?></strong>
                    </span>
                  </td>
                  <td class="text-left">
                    <?= e($s['career_name']) ?>
                  </td>
                  <td class="text-left">
                    <?php
                      $badgeClass = match($s['status']) {
                        'activo' => 'badge-green',
                        'inactivo' => 'badge-neutral',
                        'graduado' => 'badge-blue',
                        'retirado' => 'badge-red',
                        default => 'badge-neutral'
                      };
                    ?>
                    <span class="status-badge <?= $badgeClass ?>" style="text-transform: capitalize;">
                      <?= e($s['status']) ?>
                    </span>
                  </td>
                  <td class="text-right" style="white-space: nowrap;">
                    <a class="button primary small" href="<?= e(url('reportes/consulta-alumno?id=' . $s['id'] . ($careerId > 0 ? '&career_id=' . $careerId : '') . ($statusFilter !== 'todos' ? '&status=' . $statusFilter : '') . ($query !== '' ? '&q=' . urlencode($query) : ''))) ?>" title="Consultar expediente de <?= e($s['full_name']) ?>">
                      <?= icon('file-text') ?>
                      <span>Ver expediente</span>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <?php
      $st = $profile['student'];
      $summary = $profile['academic_summary'];
      $history = $profile['periods_history'];
      $planSemesters = $profile['semesters_progress'];
    ?>

    <!-- Bloque 1: Datos del alumno (dos columnas compactas) -->
    <div class="student-sheet-card">
      <div class="student-data-grid">
        <div>
          <div class="student-data-row">
            <span class="student-data-label">Código</span>
            <span class="student-data-val code-cell"><?= e($st['code']) ?></span>
          </div>
          <div class="student-data-row">
            <span class="student-data-label">Nombre</span>
            <span class="student-data-val"><?= e($st['full_name']) ?></span>
          </div>
          <div class="student-data-row">
            <span class="student-data-label">Fecha de nacimiento</span>
            <span class="student-data-val"><?= e($st['birth_date']) ?><?= $st['age'] !== '—' ? ' (' . $st['age'] . ' años)' : '' ?></span>
          </div>
        </div>
        <div>
          <div class="student-data-row">
            <span class="student-data-label">Carrera</span>
            <span class="student-data-val"><?= e($st['career_name']) ?></span>
          </div>
          <div class="student-data-row">
            <span class="student-data-label">Estado</span>
            <span class="student-data-val" style="text-transform:capitalize;"><?= e($st['status']) ?></span>
          </div>
          <div class="student-data-row">
            <span class="student-data-label">Fecha de ingreso</span>
            <span class="student-data-val"><?= e($st['entry_date']) ?></span>
          </div>
        </div>
      </div>

      <!-- Bloque 2: Resumen académico (una línea) -->
      <div class="report-summary-bar">
        <div class="report-summary-text">
          <span>Cursos aprobados: <strong><?= $summary['approved_courses'] ?></strong> de <strong><?= $summary['total_mandatory'] > 0 ? $summary['total_mandatory'] : ($summary['total_courses_taken'] ?: '—') ?></strong></span>
          <span class="report-summary-sep">·</span>
          <span>Promedio general: <strong><?= number_format((float)$summary['gpa'], 1) ?></strong></span>
          <span class="report-summary-sep">·</span>
          <span>Semestre actual: <strong><?= e($summary['current_semester_label']) ?></strong></span>
        </div>
      </div>

      <!-- Bloque 3: Historial de notas agrupado por período (del más reciente al más antiguo) -->
      <div>
        <h2 style="font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:0.03em;color:var(--text-muted);margin:0 0 10px;">
          Historial de Notas por Período
        </h2>

        <?php if (empty($history)): ?>
          <div style="padding:16px;text-align:center;color:var(--text-muted);font-size:13px;border:1px dashed var(--border-strong);border-radius:var(--radius-sm);">
            No hay registros de inscripciones o cursos cursados para este alumno.
          </div>
        <?php else: ?>
          <?php foreach ($history as $periodName => $pData): ?>
            <div class="period-history-block">
              <div class="period-history-header">
                <?= e($periodName) ?>
              </div>
              <div class="table-wrap" style="margin:0;border:none;">
                <table class="report-table">
                  <thead>
                    <tr>
                      <th class="text-left">Curso</th>
                      <th class="text-left" style="width:100px;">Sección</th>
                      <th class="text-left">Catedrático</th>
                      <th class="text-right" style="width:100px;">Nota</th>
                      <th class="text-left" style="width:130px;">Estado</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($pData['courses'] as $cRow): ?>
                      <tr>
                        <td class="text-left">
                          <a class="row-link" href="<?= e(url('cursos/' . $cRow['course_id'] . '/editar')) ?>">
                            <?= e($cRow['course_name']) ?>
                          </a>
                        </td>
                        <td class="text-left code-cell">
                          <?php if (!empty($cRow['section_id'])): ?>
                            <a class="row-link" href="<?= e(url('secciones/' . $cRow['section_id'])) ?>">
                              Sección <?= e($cRow['section_code']) ?>
                            </a>
                          <?php else: ?>
                            <?= e($cRow['section_code']) ?>
                          <?php endif; ?>
                        </td>
                        <td class="text-left"><?= e($cRow['professor_name']) ?></td>
                        <td class="text-right <?= $cRow['is_reprobado'] ? 'score-failed' : '' ?>" style="font-weight:600;">
                          <?= $cRow['final_score'] !== null ? number_format((float)$cRow['final_score'], 0) : '—' ?>
                        </td>
                        <td class="text-left <?= $cRow['is_reprobado'] ? 'text-reprobado' : '' ?>" style="text-transform:capitalize;">
                          <?= e($cRow['status']) ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <div class="period-history-footer">
                Promedio del período: <strong><?= $pData['average'] !== null ? number_format((float)$pData['average'], 1) : '—' ?></strong>
                &nbsp;·&nbsp; Cursos inscritos: <strong><?= $pData['total_courses'] ?></strong>
                &nbsp;·&nbsp; Aprobados: <strong><?= $pData['approved_count'] ?></strong>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Bloque 4: Avance por semestre del plan de estudios -->
      <?php if (!empty($planSemesters)): ?>
        <div style="margin-top:6px;">
          <h2 style="font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:0.03em;color:var(--text-muted);margin:0 0 10px;">
            Avance por Semestre del Plan de Estudios
          </h2>
          <div class="report-table-wrapper" style="max-height:none;">
            <table class="report-table">
              <thead>
                <tr>
                  <th class="text-left" style="width:160px;">Semestre</th>
                  <th class="text-right" style="width:140px;">Cursos del Plan</th>
                  <th class="text-right" style="width:140px;">Aprobados</th>
                  <th class="text-right" style="width:120px;">% Avance</th>
                  <th class="text-left" style="width:140px;">Estado</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($planSemesters as $ps): 
                  $pct = $ps['mandatory_courses'] > 0 ? round(($ps['approved_courses'] / $ps['mandatory_courses']) * 100, 1) : 0;
                  $semStatus = ($ps['approved_courses'] >= $ps['mandatory_courses'] && $ps['mandatory_courses'] > 0) ? 'Completado' : (($ps['approved_courses'] > 0) ? 'En progreso' : 'Pendiente');
                ?>
                  <tr>
                    <td class="text-left" style="font-weight:600;"><?= e($ps['name']) ?></td>
                    <td class="text-right"><?= $ps['mandatory_courses'] ?></td>
                    <td class="text-right" style="font-weight:600;"><?= $ps['approved_courses'] ?></td>
                    <td class="text-right"><?= $pct ?>%</td>
                    <td class="text-left" style="font-weight:500;"><?= $semStatus ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>

    </div>

    <!-- Pie del reporte -->
    <div class="report-footer-meta">
      <div>Generado el <?= e($generatedDate) ?> · Sistema de Control Académico</div>
      <div>Página 1 de 1</div>
    </div>
  <?php endif; ?>

</div>
