<?= partial('components/page_header', [
  'title' => 'Períodos',
  'search' => [
    'action' => url('periodos'),
    'query' => $query,
    'placeholder' => 'Buscar período',
    'filter' => 'text'
  ],
  'action' => [
    'url' => url('periodos/nuevo'),
    'text' => 'Agregar',
    'icon' => 'plus'
  ]
]) ?>

<div class="panel">
  <?php if (!$periods): ?>
    <?= partial('components/empty_state', [
      'iconName' => 'calendar',
      'title' => 'No encontramos períodos',
      'message' => 'Registra un período académico para comenzar a crear secciones.',
      'actionUrl' => url('periodos/nuevo'),
      'actionText' => 'Crear período'
    ]) ?>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Inicio</th>
            <th>Fin</th>
            <th>Estado</th>
            <th>Secciones</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($periods as $row): ?>
            <tr>
              <td data-label="Período">
                <span class="person-cell">
                  <span class="mini-avatar"><?= icon('calendar') ?></span>
                  <strong><?= e($row['name']) ?></strong>
                </span>
              </td>
              <td data-label="Inicio"><?= e(date('d/m/Y', strtotime($row['start_date']))) ?></td>
              <td data-label="Fin"><?= e(date('d/m/Y', strtotime($row['end_date']))) ?></td>
              <td data-label="Estado">
                <?php if ($row['status'] === 'activo'): ?>
                  <span class="badge badge-green">Activo</span>
                <?php elseif ($row['status'] === 'cerrado'): ?>
                  <span class="badge badge-red">Cerrado</span>
                <?php else: ?>
                  <span class="badge badge-neutral">Planificado</span>
                <?php endif; ?>
              </td>
              <td data-label="Secciones">
                <span class="muted"><?= (int)$row['sections_count'] ?> <?= (int)$row['sections_count'] === 1 ? 'sección' : 'secciones' ?></span>
              </td>
              <td data-label="Acciones" style="text-align: right;">
                <div class="table-actions">
                  <?php if ($row['status'] === 'activo'): ?>
                    <form method="post" action="<?= e(url('periodos/' . $row['id'] . '/cerrar')) ?>" data-confirm="¿Cerrar el período &quot;<?= e($row['name']) ?>&quot;? Esta acción bloqueará la edición de calificaciones y calculará el estado de los alumnos.">
                      <?= csrfField() ?>
                      <button class="button secondary small" type="submit" title="Cerrar período">
                        <span>Cerrar</span>
                      </button>
                    </form>
                  <?php elseif ($row['status'] === 'planificado'): ?>
                    <form method="post" action="<?= e(url('periodos/' . $row['id'] . '/activar')) ?>" data-confirm="¿Activar el período &quot;<?= e($row['name']) ?>&quot;? El período activo actual se cerrará automáticamente.">
                      <?= csrfField() ?>
                      <button class="button secondary small" type="submit" title="Activar período">
                        <span>Activar</span>
                      </button>
                    </form>
                  <?php elseif ($row['status'] === 'cerrado'): ?>
                    <form method="post" action="<?= e(url('periodos/' . $row['id'] . '/reabrir')) ?>" data-confirm="¿Reabrir el período &quot;<?= e($row['name']) ?>&quot; para correcciones extraordinarias?">
                      <?= csrfField() ?>
                      <button class="button secondary small" type="submit" title="Reabrir período">
                        <span>Reabrir</span>
                      </button>
                    </form>
                  <?php endif; ?>

                  <a class="button secondary small" href="<?= e(url('periodos/' . $row['id'] . '/editar')) ?>">
                    <?= icon('edit') ?>
                    <span>Editar</span>
                  </a>

                  <form method="post" action="<?= e(url('periodos/' . $row['id'] . '/eliminar')) ?>" data-confirm="¿Deseas eliminar el período &quot;<?= e($row['name']) ?>&quot;? No podrás eliminarlo si tiene secciones asociadas.">
                    <?= csrfField() ?>
                    <button class="button danger-outline small" type="submit" title="Eliminar período" <?= (int)$row['sections_count'] > 0 ? 'disabled title="No se puede eliminar un período con secciones"' : '' ?>>
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
