<?php
declare(strict_types=1);

$generatedDate = date('d/m/Y H:i');
$students = $reportData['students'];
$grouped = $reportData['grouped'];
$summary = $reportData['summary'];
$careers = $reportData['careers'];
$filters = $reportData['filters'];
$selectedCareerId = (int)$filters['career_id'];
$selectedStatus = (string)$filters['status'];
$searchQuery = (string)$filters['q'];

// Construir URL para exportar a Excel manteniendo filtros actuales
$excelUrl = url('reportes/alumnos?' . http_build_query([
    'career_id' => $selectedCareerId,
    'status' => $selectedStatus,
    'q' => $searchQuery,
    'export' => 'excel'
]));
?>
<div class="report-container">

  <!-- Encabezado Institucional (Visible únicamente en impresión / PDF) -->
  <div class="institutional-header-print">
    <div class="institutional-header-left">
      <div class="institutional-header-title">Sistema de Control Académico</div>
      <div class="institutional-header-subtitle">Reporte General de Alumnos por Carrera</div>
    </div>
    <div class="institutional-header-right">
      <div><strong>Emisión:</strong> <?= e($generatedDate) ?></div>
      <div><strong>Documento Oficial de Registro</strong></div>
    </div>
  </div>

  <!-- Barra Superior de Título y Acciones de Exportación -->
  <div class="report-header-row no-print">
    <h1>General de Alumnos por Carrera</h1>
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

  <!-- Barra de Filtros: Carrera, Estado, Búsqueda -->
  <div class="report-filters-card no-print">
    <form class="report-filters-form" method="get" action="<?= e(url('reportes/alumnos')) ?>">
      <!-- Carrera -->
      <div class="report-filter-item">
        <label for="f-career">Carrera</label>
        <select name="career_id" id="f-career" class="custom-select filter-select" data-icon="filter" onchange="this.form.submit()">
          <option value="0">Todas las carreras</option>
          <?php foreach ($careers as $c): ?>
            <option value="<?= e($c['id']) ?>" <?= ((int)$c['id'] === $selectedCareerId) ? 'selected' : '' ?>>
              <?= e($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Estado -->
      <div class="report-filter-item compact">
        <label for="f-status">Estado</label>
        <select name="status" id="f-status" class="custom-select filter-select" data-icon="filter" onchange="this.form.submit()">
          <option value="todas" <?= ($selectedStatus === 'todas') ? 'selected' : '' ?>>Todos</option>
          <option value="activo" <?= ($selectedStatus === 'activo') ? 'selected' : '' ?>>Activo</option>
          <option value="inactivo" <?= ($selectedStatus === 'inactivo') ? 'selected' : '' ?>>Inactivo</option>
          <option value="graduado" <?= ($selectedStatus === 'graduado') ? 'selected' : '' ?>>Graduado</option>
          <option value="retirado" <?= ($selectedStatus === 'retirado') ? 'selected' : '' ?>>Retirado</option>
        </select>
      </div>

      <!-- Búsqueda -->
      <div class="report-filter-item" style="flex:2;">
        <label for="f-q">Buscar</label>
        <?= partial('components/search_box', [
          'action' => url('reportes/alumnos' . ($selectedCareerId > 0 ? '?career_id=' . $selectedCareerId : '')),
          'query' => $searchQuery,
          'name' => 'q',
          'placeholder' => 'Buscar',
          'id' => 'f-q'
        ]) ?>
      </div>

      <!-- Botones -->
      <?php if ($selectedCareerId > 0 || $selectedStatus !== 'todas' || $searchQuery !== ''): ?>
        <div class="report-filter-buttons">
          <a class="button secondary" href="<?= e(url('reportes/alumnos')) ?>">
            <?= icon('x') ?>
            <span>Limpiar</span>
          </a>
        </div>
      <?php endif; ?>
    </form>
  </div>

  <!-- Resumen Superior: Total de alumnos y desglose por estado (una línea) -->
  <div class="report-summary-bar">
    <div class="report-summary-text">
      <span>Total alumnos: <strong><?= $summary['total'] ?></strong></span>
      <span class="report-summary-sep">·</span>
      <span>Activos: <strong><?= $summary['activos'] ?></strong></span>
      <span class="report-summary-sep">·</span>
      <span>Inactivos: <strong><?= $summary['inactivos'] ?></strong></span>
      <span class="report-summary-sep">·</span>
      <span>Graduados: <strong><?= $summary['graduados'] ?></strong></span>
      <span class="report-summary-sep">·</span>
      <span>Retirados: <strong><?= $summary['retirados'] ?></strong></span>
    </div>
  </div>

  <!-- Tabla de Resultados -->
  <?php if (empty($students)): ?>
    <div class="panel" style="padding:40px 20px;text-align:center;">
      <p style="margin:0;font-size:15px;color:var(--text-secondary);font-weight:600;">No se encontraron alumnos para los criterios seleccionados.</p>
      <p style="margin:6px 0 0;font-size:13px;color:var(--text-muted);">Intente modificando los filtros de carrera, estado o búsqueda.</p>
    </div>
  <?php else: ?>

    <?php if ($selectedCareerId === 0): ?>
      <!-- MODO "TODAS LAS CARRERAS": Agrupado por carrera con salto de página entre ellas en PDF y subtotal -->
      <?php 
      $groupIndex = 0;
      $totalGroups = count($grouped);
      foreach ($grouped as $careerName => $gData): 
        $groupIndex++;
        $isLastGroup = ($groupIndex === $totalGroups);
      ?>
        <div class="report-career-block <?= !$isLastGroup ? 'page-break-after' : '' ?>" style="margin-bottom:20px;">
          <div class="report-table-wrapper">
            <table class="report-table">
              <thead>
                <tr class="group-header-row">
                  <th colspan="6" class="text-left" style="font-size:13.5px;color:var(--text-primary);padding:10px 14px;">
                    <?= e($careerName) ?> &nbsp;<span style="font-weight:400;color:var(--text-muted);font-size:12px;">(<?= $gData['subtotal'] ?> alumno<?= $gData['subtotal'] !== 1 ? 's' : '' ?>)</span>
                  </th>
                </tr>
                <tr>
                  <th class="text-left" style="width:120px;">Código</th>
                  <th class="text-left">Apellidos y nombres</th>
                  <th class="text-left" style="width:200px;">Carrera</th>
                  <th class="text-left" style="width:140px;">Semestre actual</th>
                  <th class="text-left" style="width:110px;">Estado</th>
                  <th class="text-right" style="width:120px;">Ingreso</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($gData['students'] as $st): ?>
                  <tr>
                    <td class="text-left code-cell"><?= e($st['code']) ?></td>
                    <td class="text-left">
                      <a class="row-link" href="<?= e(url('reportes/consulta-alumno?id=' . $st['id'])) ?>" title="Ver ficha del alumno">
                        <?= e($st['full_name_sort']) ?>
                      </a>
                    </td>
                    <td class="text-left"><?= e($st['career_name']) ?></td>
                    <td class="text-left"><?= e($st['current_semester']) ?></td>
                    <td class="text-left" style="text-transform:capitalize;"><?= e($st['status']) ?></td>
                    <td class="text-right"><?= e($st['entry_date']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr class="subtotal-row">
                  <td colspan="5" class="text-left">Subtotal <?= e($careerName) ?>:</td>
                  <td class="text-right"><?= $gData['subtotal'] ?> alumnos</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      <?php endforeach; ?>

    <?php else: ?>
      <!-- MODO CARRERA ESPECÍFICA -->
      <div class="report-table-wrapper">
        <table class="report-table">
          <thead>
            <tr>
              <th class="text-left" style="width:120px;">Código</th>
              <th class="text-left">Apellidos y nombres</th>
              <th class="text-left" style="width:220px;">Carrera</th>
              <th class="text-left" style="width:150px;">Semestre actual</th>
              <th class="text-left" style="width:110px;">Estado</th>
              <th class="text-right" style="width:120px;">Ingreso</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $st): ?>
              <tr>
                <td class="text-left code-cell"><?= e($st['code']) ?></td>
                <td class="text-left">
                  <a class="row-link" href="<?= e(url('reportes/consulta-alumno?id=' . $st['id'])) ?>" title="Ver ficha del alumno">
                    <?= e($st['full_name_sort']) ?>
                  </a>
                </td>
                <td class="text-left"><?= e($st['career_name']) ?></td>
                <td class="text-left"><?= e($st['current_semester']) ?></td>
                <td class="text-left" style="text-transform:capitalize;"><?= e($st['status']) ?></td>
                <td class="text-right"><?= e($st['entry_date']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr class="subtotal-row">
              <td colspan="5" class="text-left">Total de la carrera:</td>
              <td class="text-right"><?= count($students) ?> alumnos</td>
            </tr>
          </tfoot>
        </table>
      </div>
    <?php endif; ?>

    <!-- Pie del Reporte: Fecha de emisión y número de página -->
    <div class="report-footer-meta">
      <div>Generado el <?= e($generatedDate) ?> · Sistema de Control Académico</div>
      <div>Página 1 de 1</div>
    </div>

  <?php endif; ?>

</div>
