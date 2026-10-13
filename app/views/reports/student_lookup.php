<?php
declare(strict_types=1);

$generatedDate = date('d/m/Y H:i');
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
    <h1>Ficha del Alumno</h1>
    <div class="report-actions">
      <?php if ($profile): ?>
        <button class="btn-report-export" onclick="window.print()" title="Imprimir constancia o guardar como PDF">
          <?= icon('print') ?>
          <span>Imprimir / PDF</span>
        </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- Buscador Superior y Selector Rápido (Ocultos en impresión) -->
  <div class="report-filters-card no-print">
    <form class="report-filters-form" method="get" action="<?= e(url('reportes/consulta-alumno')) ?>">
      <div class="report-filter-item" style="flex:2;">
        <label for="search-student">Buscar alumno</label>
        <div style="position:relative;display:flex;align-items:center;">
          <input 
            type="text" 
            id="search-student"
            name="q" 
            value="<?= e($query) ?>" 
            placeholder="Ingrese carné (ej. AC-00001) o apellidos / nombres..."
            autocomplete="off"
            style="width:100%;padding-right:32px;">
          <span style="position:absolute;right:10px;color:var(--text-muted);pointer-events:none;">
            <?= icon('search') ?>
          </span>
        </div>
      </div>
      <div class="report-filter-buttons">
        <button type="submit" class="button primary">
          <?= icon('search') ?>
          <span>Buscar</span>
        </button>
        <?php if ($query !== '' || $selectedId > 0): ?>
          <a class="button secondary" href="<?= e(url('reportes/consulta-alumno')) ?>">
            <?= icon('x') ?>
            <span>Limpiar</span>
          </a>
        <?php endif; ?>
      </div>
    </form>

    <?php if (!empty($studentList)): ?>
      <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border-subtle);display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <span class="report-filter-label" style="margin:0;">Selección rápida:</span>
        <select onchange="if(this.value) window.location.href='<?= e(url('reportes/consulta-alumno?id=')) ?>' + this.value" style="max-width:440px;height:34px;font-size:13px;border-radius:var(--radius-sm);border:1px solid var(--border-strong);">
          <option value="">-- Seleccionar de la lista (<?= count($studentList) ?> alumnos) --</option>
          <?php foreach ($studentList as $s): ?>
            <option value="<?= e($s['id']) ?>" <?= ($selectedId === (int)$s['id']) ? 'selected' : '' ?>>
              <?= e($s['last_name'] . ', ' . $s['first_name']) ?> (AC-<?= str_pad((string)$s['id'], 5, '0', STR_PAD_LEFT) ?>) — <?= e($s['career_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>
  </div>

  <?php if (!$profile): ?>
    <div class="panel" style="padding:40px 20px;text-align:center;">
      <?php if ($query !== '' || $selectedId > 0): ?>
        <p style="margin:0;font-size:15px;color:var(--text-secondary);font-weight:600;">No se encontró ningún alumno con los criterios ingresados.</p>
        <p style="margin:6px 0 0;font-size:13px;color:var(--text-muted);">Verifique el código o los nombres e intente nuevamente.</p>
      <?php else: ?>
        <p style="margin:0;font-size:15px;color:var(--text-secondary);font-weight:600;">Ingrese un carné o nombre en el buscador superior para consultar su expediente académico.</p>
        <p style="margin:6px 0 0;font-size:13px;color:var(--text-muted);">También puede seleccionar un alumno directamente desde el listado rápido.</p>
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
