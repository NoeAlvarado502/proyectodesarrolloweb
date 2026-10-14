<?php
ob_start(); ?>
<select name="period_id" class="custom-select filter-select" data-icon="filter" onchange="this.form.submit()" aria-label="Filtrar por período académico">
  <option value="all" <?= ($selectedPeriod === 'all' || $selectedPeriod === '') ? 'selected' : '' ?>>Todos los períodos</option>
  <?php foreach ($periods as $p): ?>
    <option value="<?= e($p['id']) ?>" <?= ((string)$selectedPeriod === (string)$p['id']) ? 'selected' : '' ?>>
      <?= e($p['name']) ?> (<?= ucfirst($p['status']) ?>)
    </option>
  <?php endforeach; ?>
</select>
<?php
$periodFilterHtml = ob_get_clean();
?>
<?= partial('components/page_header', [
  'title' => 'Secciones',
  'search' => [
    'action' => url('secciones' . ($selectedPeriod !== '' && $selectedPeriod !== 'all' ? '?period_id=' . $selectedPeriod : '')),
    'query' => $query,
    'placeholder' => 'Buscar',
    'filter' => 'text',
    'filter_select' => $periodFilterHtml
  ],
  'action' => [
    'url' => url('secciones/nueva' . ($selectedPeriod && $selectedPeriod !== 'all' ? '?period_id=' . $selectedPeriod : '')),
    'text' => 'Agregar',
    'icon' => 'plus'
  ]
]) ?>

<div class="panel">
  <div class="toolbar" style="border-bottom: 1px solid var(--border-subtle); padding: 12px 20px;">
    <div class="toolbar-count">
      <?= count($sections) ?> <?= count($sections) === 1 ? 'sección registrada' : 'secciones registradas' ?>
    </div>
  </div>

  <?php if (!$sections): ?>
    <?= partial('components/empty_state', [
      'iconName' => 'academic',
      'title' => 'No encontramos secciones',
      'message' => 'Crea una sección asignando curso, docente y cupo para este período.',
      'actionUrl' => url('secciones/nueva' . ($selectedPeriod ? '?period_id=' . $selectedPeriod : '')),
      'actionText' => 'Crear sección'
    ]) ?>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Curso</th>
            <th>Sección</th>
            <th>Período</th>
            <th>Docente</th>
            <th>Horario / Aula</th>
            <th>Cupo</th>
            <th>Estado</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sections as $row): ?>
            <?php
            $isFull = (int)$row['enrolled_count'] >= (int)$row['capacity'];
            $isOpen = !$isFull && $row['period_status'] === 'activo';
            ?>
            <tr>
              <td data-label="Curso">
                <span class="person-cell">
                  <span class="mini-avatar"><?= icon('book') ?></span>
                  <strong><?= e($row['course_name']) ?></strong>
                </span>
              </td>
              <td data-label="Sección"><strong><?= e($row['code']) ?></strong></td>
              <td data-label="Período"><?= e($row['period_name']) ?></td>
              <td data-label="Docente">
                <?php $teacherName = $row['teacher_display'] ?: ($row['teacher'] ?: 'Por asignar'); ?>
                <?php if (!empty($row['professor_id'])): ?>
                  <a href="<?= e(url('catedraticos/' . $row['professor_id'])) ?>" style="color: inherit; text-decoration: none; font-weight: 500;">
                    <?= e($teacherName) ?>
                  </a>
                <?php else: ?>
                  <?= e($teacherName) ?>
                <?php endif; ?>
              </td>
              <td data-label="Horario / Aula">
                <small class="muted">
                  <?= e($row['schedule'] ?: 'Sin horario') ?>
                  <?php if ($row['classroom']): ?> · <?= e($row['classroom']) ?><?php endif; ?>
                </small>
              </td>
              <td data-label="Cupo">
                <span class="muted"><?= (int)$row['enrolled_count'] ?> / <?= (int)$row['capacity'] ?></span>
              </td>
              <td data-label="Estado">
                <?php if ($row['period_status'] === 'cerrado'): ?>
                  <span class="badge badge-red">Período cerrado</span>
                <?php elseif ($isFull): ?>
                  <span class="badge badge-red">Llena</span>
                <?php else: ?>
                  <span class="badge badge-green">Abierta</span>
                <?php endif; ?>
              </td>
              <td data-label="Acciones" style="text-align: right;">
                <div class="table-actions">
                  <a class="button secondary small" href="<?= e(url('secciones/' . $row['id'])) ?>" title="Ver alumnos inscritos" aria-label="Ver alumnos inscritos">
                    <?= icon('users') ?>
                    <span>Alumnos</span>
                  </a>
                  <a class="button secondary small" href="<?= e(url('secciones/' . $row['id'] . '/editar')) ?>" title="Editar sección" aria-label="Editar sección">
                    <?= icon('edit') ?>
                    <span>Editar</span>
                  </a>
                  <form method="post" action="<?= e(url('secciones/' . $row['id'] . '/eliminar')) ?>" data-confirm="¿Deseas eliminar la sección &quot;<?= e($row['course_name']) ?> - <?= e($row['code']) ?>&quot;? No podrás eliminarla si tiene alumnos inscritos.">
                    <?= csrfField() ?>
                    <button class="button danger-outline small" type="submit" title="Eliminar sección" <?= (int)$row['enrolled_count'] > 0 ? 'disabled title="Tiene alumnos inscritos"' : '' ?>>
                      <?= icon('trash') ?>
                      <span>Eliminar</span>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
