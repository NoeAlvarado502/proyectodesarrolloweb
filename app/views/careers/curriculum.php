<div class="page-heading">
  <div>
    <h1><?= e($career['name']) ?></h1>
  </div>
  <div class="page-actions">
    <a href="<?= e(url('carreras/' . $career['id'] . '/plan' . ($activeSemester ? '?semestre=' . $activeSemester['id'] : ''))) ?>" class="button secondary small" title="Recargar vista">
      <?= icon('refresh') ?>
    </a>
    <a href="<?= e(url('carreras')) ?>" class="button primary small" title="Guardar y volver">
      <?= icon('check') ?>
      <span>Listo</span>
    </a>
  </div>
</div>

<div class="curriculum-layout">
  <!-- Left Column: Semesters Vertical Tabs -->
  <aside>
    <div class="panel" style="padding: 16px;">
      <div class="semester-nav">
        <?php foreach ($semesters as $sem): ?>
          <?php $isActive = $activeSemester && (int)$activeSemester['id'] === (int)$sem['id']; ?>
          <a href="<?= e(url('carreras/' . $career['id'] . '/plan?semestre=' . $sem['id'])) ?>" 
             class="semester-tab <?= $isActive ? 'active' : '' ?>">
            <span><?= e($sem['name'] ?? ('Semestre ' . $sem['number'])) ?></span>
            <span class="semester-tab-badge"><?= (int)$sem['courses_count'] ?></span>
          </a>
        <?php endforeach; ?>

        <!-- Add Next Semester Button (Auto increments) -->
        <form method="post" action="<?= e(url('carreras/' . $career['id'] . '/semestres/agregar')) ?>" style="margin-top: 4px;">
          <?= csrfField() ?>
          <button type="submit" class="semester-add-btn">
            <?= icon('plus') ?>
            <span>Semestre</span>
          </button>
        </form>
      </div>
    </div>
  </aside>

  <!-- Right Column: Courses of Active Semester -->
  <main>
    <div class="panel curriculum-content-pane" style="padding: 24px;">
      <?php if (!$activeSemester): ?>
        <?= partial('components/empty_state', [
          'iconName' => 'book',
          'title' => 'Sin semestres configurados',
          'message' => 'Comienza agregando el primer semestre a esta carrera haciendo clic en "+ Semestre".'
        ]) ?>
      <?php else: ?>
        <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 16px; border-bottom: 1px solid var(--border-subtle);">
          <div>
            <h2 style="font-size: 17px; font-weight: 700; margin: 0; color: var(--text-primary);">
              <?= e($activeSemester['name'] ?? ('Semestre ' . $activeSemester['number'])) ?>
            </h2>
          </div>

          <?php if (count($coursesInSemester) === 0): ?>
            <form method="post" action="<?= e(url('carreras/' . $career['id'] . '/semestres/' . $activeSemester['id'] . '/eliminar')) ?>" 
                  data-confirm="¿Deseas eliminar este semestre vacío del plan?">
              <?= csrfField() ?>
              <button type="submit" class="button danger-outline small">
                <?= icon('trash') ?>
                <span>Eliminar semestre</span>
              </button>
            </form>
          <?php endif; ?>
        </div>

        <?php if (empty($coursesInSemester)): ?>
          <div style="padding: 48px 20px; text-align: center; color: var(--text-muted);">
            <div style="margin-bottom: 10px;"><?= icon('book', 'style="width: 38px; height: 38px; stroke-width: 1.5; color: var(--text-faint);"') ?></div>
            <p style="font-size: 14.5px; margin: 0 0 6px; font-weight: 500; color: var(--text-secondary);">No hay cursos asignados a este semestre</p>
            <p style="font-size: 13px; margin: 0;">Presiona el botón <strong>+</strong> en la esquina inferior derecha para agregar cursos.</p>
          </div>
        <?php else: ?>
          <div class="course-cards-grid">
            <?php foreach ($coursesInSemester as $course): ?>
              <div class="course-card-box">
                <div class="course-card-info">
                  <span class="course-card-title"><?= e($course['name']) ?></span>
                  <?php if (empty($course['is_mandatory'])): ?>
                    <span class="course-opt-tag">Optativo</span>
                  <?php endif; ?>
                </div>
                <form method="post" action="<?= e(url('carreras/' . $career['id'] . '/semestres/' . $activeSemester['id'] . '/cursos/' . $course['id'] . '/quitar')) ?>" 
                      data-confirm="¿Quitar el curso &quot;<?= e($course['name']) ?>&quot; de este semestre?">
                  <?= csrfField() ?>
                  <button type="submit" class="icon-button" title="Quitar curso del semestre">
                    <?= icon('x') ?>
                  </button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Big '+' Button at bottom right exactly matching reference sketch -->
        <div class="curriculum-fab-container">
          <button type="button" class="course-fab-add" onclick="document.getElementById('modalAddCourse').showModal()" title="Agregar curso a este semestre">
            <?= icon('plus') ?>
          </button>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>

<!-- Modal Dialog: Search & Add Course (Excludes courses in other semesters of this career) -->
<?php if ($activeSemester): ?>
  <dialog id="modalAddCourse" class="custom-modal">
    <form method="post" action="<?= e(url('carreras/' . $career['id'] . '/semestres/' . $activeSemester['id'] . '/cursos/agregar')) ?>">
      <?= csrfField() ?>
      <div class="modal-header">
        <h3>Agregar curso a <?= e($activeSemester['name'] ?? ('Semestre ' . $activeSemester['number'])) ?></h3>
        <button type="button" class="icon-button" onclick="document.getElementById('modalAddCourse').close()" title="Cerrar">
          <?= icon('x') ?>
        </button>
      </div>

      <div class="modal-body">
        <?php if (empty($availableCourses)): ?>
          <p style="color: var(--text-muted); font-size: 13.5px; margin: 0;">
            Todos los cursos del catálogo ya han sido asignados al plan de estudios de esta carrera, o no hay más cursos registrados.
          </p>
        <?php else: ?>
          <div style="display: flex; flex-direction: column; gap: 16px;">
            <label>
              <span style="font-size: 12.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-muted); display: block; margin-bottom: 6px;">
                Seleccionar curso *
              </span>
              <select name="course_id" required style="width: 100%; height: 42px;">
                <option value="">-- Selecciona un curso disponible --</option>
                <?php foreach ($availableCourses as $ac): ?>
                  <option value="<?= (int)$ac['id'] ?>"><?= e($ac['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </label>

            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
              <input type="checkbox" name="is_mandatory" value="1" checked style="width: 18px; height: 18px;">
              <span style="font-size: 14px; font-weight: 500; color: var(--text-primary);">Curso obligatorio</span>
            </label>

            <div style="font-size: 12px; color: var(--text-muted); background: var(--bg-subtle); padding: 10px 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
              <?= icon('info', 'style="width: 14px; height: 14px; vertical-align: middle; margin-right: 4px;"') ?>
              Solo se muestran cursos que no están asignados en ningún otro semestre de esta carrera.
            </div>
          </div>
        <?php endif; ?>
      </div>

      <div class="modal-footer">
        <button type="button" class="button secondary small" onclick="document.getElementById('modalAddCourse').close()">
          Cancelar
        </button>
        <?php if (!empty($availableCourses)): ?>
          <button type="submit" class="button primary small">
            <?= icon('plus') ?>
            <span>Agregar curso</span>
          </button>
        <?php endif; ?>
      </div>
    </form>
  </dialog>
<?php endif; ?>
