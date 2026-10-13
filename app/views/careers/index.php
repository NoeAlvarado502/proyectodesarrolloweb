<?= partial('components/page_header', [
  'title' => 'Carreras',
  'search' => [
    'action' => url('carreras'),
    'query' => $query,
    'placeholder' => 'Buscar carrera',
    'filter' => 'text'
  ],
  'action' => [
    'url' => url('carreras/nueva'),
    'text' => 'Agregar',
    'icon' => 'plus'
  ]
]) ?>

<div class="panel">
  <?php if (!$careers): ?>
    <?= partial('components/empty_state', [
      'iconName' => 'academic',
      'title' => 'No encontramos carreras',
      'message' => 'Registra una carrera para comenzar a diseñar su plan de estudios.',
      'actionUrl' => url('carreras/nueva'),
      'actionText' => 'Registrar carrera'
    ]) ?>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Carrera</th>
            <th>Semestres del plan</th>
            <th>Cursos asignados</th>
            <th>Alumnos matriculados</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($careers as $row): ?>
            <tr>
              <td data-label="Carrera">
                <span class="person-cell">
                  <span class="mini-avatar"><?= icon('academic') ?></span>
                  <strong><?= e($row['name']) ?></strong>
                </span>
              </td>
              <td data-label="Semestres del plan">
                <span class="muted"><?= (int)$row['semesters_count'] ?> <?= (int)$row['semesters_count'] === 1 ? 'semestre' : 'semestres' ?></span>
              </td>
              <td data-label="Cursos asignados">
                <span class="muted"><?= (int)$row['courses_count'] ?> <?= (int)$row['courses_count'] === 1 ? 'curso' : 'cursos' ?></span>
              </td>
              <td data-label="Alumnos matriculados">
                <span class="muted"><?= (int)$row['students_count'] ?> <?= (int)$row['students_count'] === 1 ? 'alumno' : 'alumnos' ?></span>
              </td>
              <td data-label="Acciones">
                <div class="table-actions">
                  <a class="button primary small" href="<?= e(url('carreras/' . $row['id'] . '/plan')) ?>" title="Editar plan de estudios por semestres" aria-label="Plan de estudios">
                    <?= icon('book') ?>
                    <span>Plan de estudios</span>
                  </a>
                  <a class="button secondary small" href="<?= e(url('carreras/' . $row['id'] . '/editar')) ?>" title="Editar carrera" aria-label="Editar carrera">
                    <?= icon('edit') ?>
                    <span>Editar</span>
                  </a>
                  <form method="post" action="<?= e(url('carreras/' . $row['id'] . '/eliminar')) ?>" data-confirm="¿Deseas eliminar la carrera &quot;<?= e($row['name']) ?>&quot;? No podrás eliminarla si tiene alumnos asignados.">
                    <?= csrfField() ?>
                    <button class="button danger-outline small" type="submit" title="Eliminar carrera" <?= (int)$row['students_count'] > 0 ? 'disabled title="Tiene alumnos inscritos"' : '' ?>>
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
