<?php
$isPeriodClosed = ($section['period_status'] === 'cerrado');
$enrolledCount = count($enrollments);
$isFull = ($enrolledCount >= (int)$section['capacity']);
$canEnroll = (!$isPeriodClosed && !$isFull);
?>

<?= partial('components/page_header', [
  'title' => $section['course_name'] . ' · Sección ' . $section['code'] . ' (' . $section['period_name'] . ')',
  'backUrl' => url('secciones'),
  'backText' => 'Volver a secciones'
]) ?>

<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 22px;">
  <article class="stat-card">
    <div class="stat-header">
      <span class="stat-label">Docente</span>
      <?php if (!empty($section['professor_photo'])): ?>
        <img class="mini-photo" style="width: 32px; height: 32px;" src="<?= e(photoUrl($section['professor_photo'])) ?>" alt="Foto docente">
      <?php else: ?>
        <span class="stat-icon"><?= icon('user') ?></span>
      <?php endif; ?>
    </div>
    <?php if (!empty($section['professor_id'])): ?>
      <strong style="font-size: 16px;">
        <a href="<?= e(url('catedraticos/' . $section['professor_id'])) ?>" style="color: inherit; text-decoration: none;">
          <?= e($section['teacher'] ?: 'Por asignar') ?>
        </a>
      </strong>
    <?php else: ?>
      <strong style="font-size: 16px;"><?= e($section['teacher'] ?: 'Por asignar') ?></strong>
    <?php endif; ?>
    <small><?= e($section['classroom'] ?: 'Sin aula asignada') ?></small>
  </article>

  <article class="stat-card">
    <div class="stat-header">
      <span class="stat-label">Horario</span>
      <span class="stat-icon"><?= icon('calendar') ?></span>
    </div>
    <strong style="font-size: 15px;"><?= e($section['schedule'] ?: 'Por definir') ?></strong>
    <small><?= e($section['period_name']) ?></small>
  </article>

  <article class="stat-card">
    <div class="stat-header">
      <span class="stat-label">Capacidad de cupo</span>
      <span class="stat-icon"><?= icon('users') ?></span>
    </div>
    <strong><?= $enrolledCount ?> / <?= (int)$section['capacity'] ?></strong>
    <small><?= (int)$section['capacity'] - $enrolledCount ?> cupos disponibles</small>
  </article>

  <article class="stat-card">
    <div class="stat-header">
      <span class="stat-label">Estado de sección</span>
      <span class="stat-icon"><?= icon('academic') ?></span>
    </div>
    <div>
      <?php if ($isPeriodClosed): ?>
        <span class="badge badge-red">Período cerrado</span>
      <?php elseif ($isFull): ?>
        <span class="badge badge-red">Llena</span>
      <?php else: ?>
        <span class="badge badge-green">Abierta</span>
      <?php endif; ?>
    </div>
    <small><?= ucfirst($section['period_status']) ?></small>
  </article>
</div>

<div class="panel">
  <div class="panel-heading" style="padding: 18px 22px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
    <div>
      <h2>Alumnos inscritos</h2>
      <p>Listado de estudiantes y registro de calificación final del curso.</p>
    </div>

    <?php if ($canEnroll && !empty($availableStudents)): ?>
      <form method="post" action="<?= e(url('secciones/' . $section['id'] . '/inscribir')) ?>" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <?= csrfField() ?>
        <select name="student_id" required style="height: 38px; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 0 12px; background: var(--bg-surface); font-size: 13.5px; max-width: 320px;">
          <option value="">Selecciona un alumno para inscribir...</option>
          <?php foreach ($availableStudents as $st): ?>
            <option value="<?= e($st['id']) ?>">
              <?= e($st['first_name'] . ' ' . $st['last_name']) ?> (AC-<?= str_pad((string)$st['id'], 5, '0', STR_PAD_LEFT) ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <button class="button primary small" type="submit">
          <?= icon('plus') ?>
          <span>Inscribir</span>
        </button>
      </form>
    <?php elseif ($isPeriodClosed): ?>
      <span class="badge badge-red">Inscripciones bloqueadas por cierre de período</span>
    <?php elseif ($isFull): ?>
      <span class="badge badge-red">Cupo completo</span>
    <?php endif; ?>
  </div>

  <?php if (empty($enrollments)): ?>
    <?= partial('components/empty_state', [
      'iconName' => 'users',
      'title' => 'No hay alumnos inscritos en esta sección',
      'message' => $isPeriodClosed ? 'El período se encuentra cerrado.' : 'Utiliza el selector superior para inscribir al primer estudiante.'
    ]) ?>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th style="width: 40px;">#</th>
            <th>Expediente</th>
            <th>Alumno</th>
            <th>Carrera</th>
            <th style="width: 220px;">Nota final</th>
            <th>Estado</th>
            <th style="text-align: right;"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($enrollments as $idx => $en): ?>
            <tr>
              <td data-label="#" class="muted"><?= $idx + 1 ?></td>
              <td data-label="Expediente" class="muted">AC-<?= str_pad((string)$en['student_id'], 5, '0', STR_PAD_LEFT) ?></td>
              <td data-label="Alumno">
                <a class="person-cell" href="<?= e(url('alumnos/' . $en['student_id'])) ?>">
                  <img class="mini-photo" src="<?= e(photoUrl($en['photo'])) ?>" alt="">
                  <strong><?= e($en['first_name'] . ' ' . $en['last_name']) ?></strong>
                </a>
              </td>
              <td data-label="Carrera"><?= e($en['career_name']) ?></td>
              <td data-label="Nota final">
                <?php if ($isPeriodClosed): ?>
                  <strong><?= $en['final_score'] !== null ? e($en['final_score']) . ' / 100' : '—' ?></strong>
                <?php else: ?>
                  <form method="post" action="<?= e(url('secciones/' . $section['id'] . '/nota/' . $en['id'])) ?>" style="display: flex; gap: 6px; align-items: center; justify-content: flex-end;">
                    <?= csrfField() ?>
                    <input type="number" name="final_score" min="0" max="100" 
                           value="<?= $en['final_score'] !== null ? e($en['final_score']) : '' ?>" 
                           placeholder="0-100" 
                           style="width: 75px; height: 32px; font-size: 13.5px; padding: 0 8px; border: 1px solid var(--border); border-radius: var(--radius-sm); text-align: center;">
                    <button class="button secondary small" type="submit" title="Guardar nota">
                      <?= icon('check') ?>
                      <span>Guardar</span>
                    </button>
                  </form>
                <?php endif; ?>
              </td>
              <td data-label="Estado">
                <?php if ($en['status'] === 'aprobado'): ?>
                  <span class="badge badge-green">Aprobado</span>
                <?php elseif ($en['status'] === 'reprobado'): ?>
                  <span class="badge badge-red">Reprobado</span>
                <?php elseif ($en['status'] === 'retirado'): ?>
                  <span class="badge badge-amber">Retirado</span>
                <?php else: ?>
                  <span class="badge badge-neutral">Cursando</span>
                <?php endif; ?>
              </td>
              <td data-label="Acciones" style="text-align: right;">
                <?php if (!$isPeriodClosed): ?>
                  <form method="post" action="<?= e(url('secciones/' . $section['id'] . '/desinscribir/' . $en['id'])) ?>" data-confirm="¿Deseas desinscribir a <?= e($en['first_name'] . ' ' . $en['last_name']) ?> de esta sección?<?= $en['final_score'] !== null ? ' ¡ATENCIÓN! Ya cuenta con una nota registrada de ' . $en['final_score'] . '.' : '' ?>">
                    <?= csrfField() ?>
                    <button class="button danger-outline small" type="submit" title="Quitar de la sección">
                      <?= icon('trash') ?>
                      <span>Quitar</span>
                    </button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
