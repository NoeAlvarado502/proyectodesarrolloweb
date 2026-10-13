<?php
$editing = !empty($period['id']);
$title = $editing ? 'Editar período' : 'Nuevo período';
?>
<?= partial('components/page_header', [
  'title' => $title,
  'backUrl' => url('periodos'),
  'backText' => 'Volver'
]) ?>

<form class="panel form-panel" method="post" action="<?= e(url($editing ? 'periodos/' . $period['id'] . '/actualizar' : 'periodos/guardar')) ?>">
  <?= csrfField() ?>

  <?= partial('components/form_errors', ['errors' => $errors ?? []]) ?>

  <div class="form-grid">
    <label>
      <span>Nombre del período *</span>
      <input name="name" id="name" maxlength="100" minlength="2" required 
             value="<?= e($period['name'] ?? '') ?>" 
             placeholder="Nombre"
             autocomplete="off">
    </label>

    <label>
      <span>Estado *</span>
      <select name="status" id="status" required>
        <option value="planificado" <?= ($period['status'] ?? 'planificado') === 'planificado' ? 'selected' : '' ?>>Planificado</option>
        <option value="activo" <?= ($period['status'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
        <option value="cerrado" <?= ($period['status'] ?? '') === 'cerrado' ? 'selected' : '' ?>>Cerrado</option>
      </select>
    </label>

    <label>
      <span>Fecha de inicio *</span>
      <input type="date" name="start_date" id="start_date" required 
             value="<?= e($period['start_date'] ?? '') ?>">
    </label>

    <label>
      <span>Fecha de fin *</span>
      <input type="date" name="end_date" id="end_date" required 
             value="<?= e($period['end_date'] ?? '') ?>">
    </label>
  </div>

  <?= partial('components/form_actions', [
    'cancelUrl' => url('periodos'),
    'cancelText' => 'Cancelar',
    'submitText' => $editing ? 'Guardar' : 'Crear'
  ]) ?>
</form>
