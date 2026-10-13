<?= partial('components/page_header', [
  'title' => 'Secciones',
  'search' => [
    'action' => url('secciones'),
    'query' => $query,
    'placeholder' => 'Buscar curso o docente',
    'filter' => 'text'
  ],
  'action' => [
    'url' => url('secciones/nueva' . ($selectedPeriod ? '?period_id=' . $selectedPeriod : '')),
    'text' => 'Agregar',
    'icon' => 'plus'
  ]
]) ?>

<div class="panel">
  <div class="filter-bar" style="border-bottom: 1px solid var(--border); padding: 16px 20px;">
    <form method="get" action="<?= e(url('secciones')) ?>" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
      <label style="display: flex; align-items: center; gap: 8px; font-size: 13.5px; font-weight: 500; color: var(--text-secondary);">
        <span>Período académico:</span>
        <select name="period_id" onchange="this.form.submit()" style="height: 38px; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 0 12px; background: var(--bg-surface); font-size: 13.5px;">
          <option value="">Todos los períodos</option>
          <?php foreach ($periods as $p): ?>
            <option value="<?= e($p['id']) ?>" <?= (int)$selectedPeriod === (int)$p['id'] ? 'selected' : '' ?>>
              <?= e($p['name']) ?> (<?= ucfirst($p['status']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <?php if ($query !== ''): ?>
        <input type="hidden" name="q" value="<?= e($query) ?>">
      <?php endif; ?>
    </form>
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
