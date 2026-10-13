<?php
$editing = !empty($career['id']);
$title = $editing ? 'Editar carrera' : 'Nueva carrera';
?>
<?= partial('components/page_header', [
  'title' => $title,
  'backUrl' => url('carreras'),
  'backText' => 'Volver'
]) ?>

<form class="panel form-panel" method="post" action="<?= e(url($editing ? 'carreras/' . $career['id'] . '/actualizar' : 'carreras/guardar')) ?>">
  <?= csrfField() ?>

  <?= partial('components/form_errors', ['errors' => $errors ?? []]) ?>

  <div class="form-grid">
    <label class="full">
      <span>Nombre de la carrera *</span>
      <input name="name" id="name" maxlength="100" minlength="3" required 
             value="<?= e($career['name'] ?? '') ?>" 
             placeholder="Nombre de la carrera"
             pattern="[A-Za-z0-9ÁÉÍÓÚáéíóúÑñÜü\s\.\:\'\-\(\)]{3,100}"
             autocomplete="off">
    </label>
  </div>

  <?= partial('components/form_actions', [
    'cancelUrl' => url('carreras'),
    'cancelText' => 'Cancelar',
    'submitText' => $editing ? 'Guardar' : 'Crear'
  ]) ?>
</form>
