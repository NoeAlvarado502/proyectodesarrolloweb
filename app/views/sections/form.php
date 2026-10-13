<?php
$editing = !empty($section['id']);
$title = $editing ? 'Editar sección' : 'Nueva sección';
?>
<?= partial('components/page_header', [
  'title' => $title,
  'backUrl' => url('secciones'),
  'backText' => 'Volver'
]) ?>

<form class="panel form-panel" method="post" action="<?= e(url($editing ? 'secciones/' . $section['id'] . '/actualizar' : 'secciones/guardar')) ?>">
  <?= csrfField() ?>

  <?= partial('components/form_errors', ['errors' => $errors ?? []]) ?>

  <div class="form-grid">
    <label>
      <span>Período académico *</span>
      <select name="period_id" id="period_id" required>
        <option value="">Selecciona un período</option>
        <?php foreach ($periods as $p): ?>
          <option value="<?= e($p['id']) ?>" <?= (int)($section['period_id'] ?? $defaultPeriodId) === (int)$p['id'] ? 'selected' : '' ?>>
            <?= e($p['name']) ?> (<?= ucfirst($p['status']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>
      <span>Curso *</span>
      <select name="course_id" id="course_id" required>
        <option value="">Selecciona un curso</option>
        <?php foreach ($courses as $c): ?>
          <option value="<?= e($c['id']) ?>" <?= (int)($section['course_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>>
            <?= e($c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>
      <span>Código de sección *</span>
      <input name="code" id="code" maxlength="20" required 
             value="<?= e($section['code'] ?? 'A') ?>" 
             placeholder="ej. A, B, C"
             autocomplete="off">
    </label>

    <label>
      <span>Catedrático asignado</span>
      <select name="professor_id" id="professor_id">
        <option value="">-- Sin catedrático asignado --</option>
        <?php foreach ($professors ?? [] as $prof): ?>
          <option value="<?= (int)$prof['id'] ?>" <?= (int)($section['professor_id'] ?? 0) === (int)$prof['id'] ? 'selected' : '' ?>>
            <?= e($prof['code'] . ' - ' . $prof['first_name'] . ' ' . $prof['last_name']) ?> <?= !empty($prof['specialty']) ? '(' . e($prof['specialty']) . ')' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>
      <span>Cupo máximo *</span>
      <input type="number" name="capacity" id="capacity" min="1" max="500" required 
             value="<?= e($section['capacity'] ?? 30) ?>" 
             placeholder="Cupo">
    </label>

    <label>
      <span>Aula</span>
      <input name="classroom" id="classroom" maxlength="100" 
             value="<?= e($section['classroom'] ?? '') ?>" 
             placeholder="ej. Aula 101, Lab 2"
             autocomplete="off">
    </label>

    <label class="full">
      <span>Horario</span>
      <input name="schedule" id="schedule" maxlength="150" 
             value="<?= e($section['schedule'] ?? '') ?>" 
             placeholder="ej. Lun y Mié 8:00-10:00"
             autocomplete="off">
    </label>
  </div>

  <?= partial('components/form_actions', [
    'cancelUrl' => url('secciones'),
    'cancelText' => 'Cancelar',
    'submitText' => $editing ? 'Guardar' : 'Crear'
  ]) ?>
</form>
