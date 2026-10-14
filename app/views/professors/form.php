<?php
$editing = !empty($professor['id']);
$title = $editing ? 'Editar catedrático' : 'Nuevo catedrático';
?>
<?= partial('components/page_header', [
  'title' => $title,
  'backUrl' => url('catedraticos'),
  'backText' => 'Volver'
]) ?>

<form class="panel form-panel" method="post" enctype="multipart/form-data" action="<?= e(url($editing ? 'catedraticos/' . $professor['id'] . '/actualizar' : 'catedraticos/guardar')) ?>">
  <?= csrfField() ?>

  <?= partial('components/form_errors', ['errors' => $errors ?? []]) ?>

  <div class="form-grid">
    <label>
      <span>Código de catedrático *</span>
      <input name="code" id="code" maxlength="50" required 
             value="<?= e($professor['code'] ?? '') ?>" 
             placeholder="Ej. C-001"
             autocomplete="off">
    </label>

    <label>
      <span>Documento de identificación (DPI/DNI) *</span>
      <input name="document" id="document" maxlength="50" required 
             value="<?= e($professor['document'] ?? '') ?>" 
             placeholder="Número de documento"
             autocomplete="off">
    </label>

    <label>
      <span>Nombres *</span>
      <input name="first_name" id="first_name" maxlength="100" minlength="2" required 
             value="<?= e($professor['first_name'] ?? '') ?>" 
             placeholder="Nombres"
             autocomplete="off">
    </label>

    <label>
      <span>Apellidos *</span>
      <input name="last_name" id="last_name" maxlength="100" minlength="2" required 
             value="<?= e($professor['last_name'] ?? '') ?>" 
             placeholder="Apellidos"
             autocomplete="off">
    </label>

    <label>
      <span>Correo electrónico *</span>
      <input name="email" id="email" type="email" maxlength="150" required 
             value="<?= e($professor['email'] ?? '') ?>" 
             placeholder="correo@ejemplo.com"
             autocomplete="off">
    </label>

    <label>
      <span>Teléfono</span>
      <input name="phone" id="phone" maxlength="50" 
             value="<?= e($professor['phone'] ?? '') ?>" 
             placeholder="Número de teléfono"
             autocomplete="off">
    </label>

    <label class="full">
      <span>Título / Especialidad</span>
      <input name="specialty" id="specialty" maxlength="150" 
             value="<?= e($professor['specialty'] ?? '') ?>" 
             placeholder="Ej. Ing. en Ciencias de la Computación, Maestría en Educación Superior"
             autocomplete="off">
    </label>

    <label class="full">
      <span>Fotografía</span>
      <div class="photo-upload">
        <img class="preview-photo" src="<?= e(photoUrl($professor['photo'] ?? null)) ?>" alt="Vista previa">
        <div style="flex: 1;">
          <input type="file" name="photo" id="photo" accept="image/jpeg,image/png,image/webp" data-photo-preview>
          <div id="photo-feedback" class="field-feedback"></div>
          <small class="muted" style="display: block; margin-top: 4px; font-size: 12px;">Formatos admitidos: JPG, PNG o WEBP. Máximo 3 MB.</small>
        </div>
      </div>
    </label>

    <label class="full">
      <span>Estado</span>
      <select name="status">
        <option value="activo" <?= ($professor['status'] ?? 'activo') === 'activo' ? 'selected' : '' ?>>Activo</option>
        <option value="inactivo" <?= ($professor['status'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
      </select>
    </label>
  </div>

  <?= partial('components/form_actions', [
    'cancelUrl' => url('catedraticos'),
    'cancelText' => 'Cancelar',
    'submitText' => $editing ? 'Guardar' : 'Crear'
  ]) ?>
</form>
