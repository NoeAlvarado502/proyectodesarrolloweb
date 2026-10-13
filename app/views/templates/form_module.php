<?php
/**
 * Plantilla Base de Módulo de Formulario (Reciclable)
 * 
 * Uso: Copiar esta estructura para formularios limpios a ancho completo,
 * sin notas saturadas ni información innecesaria de filtros.
 */
$editing = !empty($item['id']);
$title = $editing ? 'Editar elemento' : 'Nuevo elemento';
?>
<?= partial('components/page_header', [
  'title' => $title,
  'backUrl' => url('modulo'),
  'backText' => 'Volver'
]) ?>

<form class="panel form-panel" method="post" action="<?= e(url($editing ? 'modulo/' . $item['id'] . '/actualizar' : 'modulo/guardar')) ?>">
  <?= csrfField() ?>

  <?= partial('components/form_errors', ['errors' => $errors ?? []]) ?>

  <div class="form-grid">
    <label>
      <span>Nombre *</span>
      <input name="name" id="name" required
             value="<?= e($item['name'] ?? '') ?>"
             placeholder="Nombre"
             autocomplete="off">
    </label>

    <label>
      <span>Categoría *</span>
      <select name="category_id" id="category_id" required>
        <option value="">Selecciona una opción</option>
        <?php foreach ($categories ?? [] as $cat): ?>
          <option value="<?= e($cat['id']) ?>" <?= (string)($item['category_id'] ?? '') === (string)$cat['id'] ? 'selected' : '' ?>>
            <?= e($cat['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <label class="full">
      <span>Descripción</span>
      <input name="description" id="description"
             value="<?= e($item['description'] ?? '') ?>"
             placeholder="Descripción"
             autocomplete="off">
    </label>
  </div>

  <?= partial('components/form_actions', [
    'cancelUrl' => url('modulo'),
    'cancelText' => 'Cancelar',
    'submitText' => $editing ? 'Guardar' : 'Crear'
  ]) ?>
</form>
