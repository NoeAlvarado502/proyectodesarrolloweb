<?php
$editing = !empty($course['id']);
$title = $editing ? 'Editar curso' : 'Nuevo curso';
?>
<?= partial('components/page_header', [
  'title' => $title,
  'backUrl' => url('cursos'),
  'backText' => 'Volver'
]) ?>

<form class="panel form-panel" method="post" action="<?= e(url($editing ? 'cursos/' . $course['id'] . '/actualizar' : 'cursos/guardar')) ?>">
  <?= csrfField() ?>

  <?= partial('components/form_errors', ['errors' => $errors ?? []]) ?>

  <div class="form-grid">
    <label class="full">
      <span>Nombre del curso *</span>
      <input name="name" id="name" maxlength="100" minlength="3" required 
             value="<?= e($course['name'] ?? '') ?>" 
             placeholder="Nombre"
             pattern="[A-Za-z0-9ÁÉÍÓÚáéíóúÑñÜü\s\.\:\'\-\(\)]{3,100}"
             autocomplete="off">
    </label>
  </div>

  <?= partial('components/form_actions', [
    'cancelUrl' => url('cursos'),
    'cancelText' => 'Cancelar',
    'submitText' => $editing ? 'Guardar' : 'Crear'
  ]) ?>
</form>
