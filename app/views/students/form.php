<?php
$editing = !empty($student['id']);
$title = $editing ? 'Editar alumno' : 'Nuevo alumno';
$maxBirthDate = date('Y-m-d', strtotime('-14 years'));
$minBirthDate = date('Y-m-d', strtotime('-95 years'));
?>
<?= partial('components/page_header', [
  'title' => $title,
  'backUrl' => url('alumnos'),
  'backText' => 'Volver'
]) ?>

<form class="panel form-panel" method="post" enctype="multipart/form-data" action="<?= e(url($editing ? 'alumnos/' . $student['id'] . '/actualizar' : 'alumnos/guardar')) ?>">
  <?= csrfField() ?>

  <?= partial('components/form_errors', ['errors' => $errors ?? []]) ?>

    <div class="form-grid">
      <label>
        <span>Nombre *</span>
        <input name="first_name" id="first_name" maxlength="100" minlength="2" required 
               value="<?= e($student['first_name'] ?? '') ?>" 
               placeholder="Nombre"
               data-filter="alpha"
               pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s'\-]{2,100}"
               autocomplete="off">
      </label>

      <label>
        <span>Apellido *</span>
        <input name="last_name" id="last_name" maxlength="100" minlength="2" required 
               value="<?= e($student['last_name'] ?? '') ?>" 
               placeholder="Apellido"
               data-filter="alpha"
               pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s'\-]{2,100}"
               autocomplete="off">
      </label>

      <label>
        <span>Fecha de nacimiento *</span>
        <input type="date" name="birth_date" id="birth_date" 
               min="<?= $minBirthDate ?>" 
               max="<?= $maxBirthDate ?>" 
               required 
               value="<?= e($student['birth_date'] ?? '') ?>"
               data-filter="birth-date">
        <div id="birth-date-feedback" class="field-feedback"></div>
      </label>

      <label>
        <span>Carrera *</span>
        <select name="career_id" id="career_id" required>
          <option value="">Selecciona una carrera</option>
          <?php foreach ($careers as $career): ?>
            <option value="<?= e($career['id']) ?>" <?= (string)($student['career_id'] ?? '') === (string)$career['id'] ? 'selected' : '' ?>>
              <?= e($career['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (!$careers): ?>
          <small class="field-note">Primero debes registrar carreras en Catálogos.</small>
        <?php endif; ?>
      </label>

      <label class="full">
        <span>Fotografía</span>
        <div class="photo-upload">
          <img class="preview-photo" src="<?= e(photoUrl($student['photo'] ?? null)) ?>" alt="Vista previa">
          <div style="flex: 1;">
            <input type="file" name="photo" id="photo" accept="image/jpeg,image/png,image/webp" data-photo-preview>
            <div id="photo-feedback" class="field-feedback"></div>
          </div>
        </div>
      </label>
    </div>

    <?= partial('components/form_actions', [
      'cancelUrl' => url('alumnos'),
      'cancelText' => 'Cancelar',
      'submitText' => $editing ? 'Guardar' : 'Crear',
      'disabled' => !$careers
    ]) ?>
  </form>
