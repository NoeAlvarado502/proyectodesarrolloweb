<?= partial('components/page_header', [
  'title' => 'Cursos',
  'search' => [
    'action' => url('cursos'),
    'query' => $query,
    'placeholder' => 'Buscar',
    'filter' => 'course-search',
    'pattern' => "[A-Za-z0-9ÁÉÍÓÚáéíóúÑñÜü\\s\\.\\'\\-]*"
  ],
  'action' => [
    'url' => url('cursos/nuevo'),
    'text' => 'Agregar',
    'icon' => 'plus'
  ]
]) ?>

<div class="panel">
  <?php if (!$courses): ?>
    <?= partial('components/empty_state', [
      'iconName' => 'book',
      'title' => 'No encontramos cursos',
      'message' => 'Prueba con otro término de búsqueda o registra un nuevo curso.',
      'actionUrl' => url('cursos/nuevo'),
      'actionText' => 'Registrar curso'
    ]) ?>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Curso</th>
            <th>Identificador</th>
            <th>Calificaciones</th>
            <th>Fecha de registro</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($courses as $row): ?>
            <tr>
              <td data-label="Curso">
                <span class="person-cell">
                  <span class="mini-avatar"><?= icon('book') ?></span>
                  <strong><?= e($row['name']) ?></strong>
                </span>
              </td>
              <td data-label="Identificador" class="muted">CRS-<?= str_pad((string)$row['id'], 4, '0', STR_PAD_LEFT) ?></td>
              <td data-label="Calificaciones">
                <span class="muted"><?= (int)$row['grades_count'] ?> <?= (int)$row['grades_count'] === 1 ? 'registro' : 'registros' ?></span>
              </td>
              <td data-label="Fecha de registro"><?= e(date('d/m/Y', strtotime($row['created_at']))) ?></td>
              <td data-label="Acciones" style="text-align: right;">
                <div class="table-actions">
                  <a class="button secondary small" href="<?= e(url('cursos/' . $row['id'] . '/editar')) ?>">
                    <?= icon('edit') ?>
                    <span>Editar</span>
                  </a>
                  <form method="post" action="<?= e(url('cursos/' . $row['id'] . '/eliminar')) ?>" data-confirm="¿Deseas eliminar el curso &quot;<?= e($row['name']) ?>&quot;? No podrás eliminarlo si ya está en uso en calificaciones.">
                    <?= csrfField() ?>
                    <button class="button danger-outline small" type="submit" title="Eliminar curso" <?= (int)$row['grades_count'] > 0 ? 'title="Tiene notas asignadas"' : '' ?>>
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
