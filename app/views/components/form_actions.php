<?php
/**
 * Reusable Form Actions Component (Buttons bar)
 *
 * @var string      $cancelUrl   URL to return to on cancel
 * @var string|null $cancelText  Label for cancel button (default: 'Cancelar')
 * @var string|null $submitText  Label for submit button (default: 'Guardar')
 * @var string|null $submitId    DOM ID for submit button (default: 'btn-submit')
 * @var bool|null   $disabled    Whether submit button should be disabled
 */
$cancelUrl = $cancelUrl ?? url();
$cancelText = $cancelText ?? 'Cancelar';
$submitText = $submitText ?? 'Guardar';
$submitId = $submitId ?? 'btn-submit';
$disabled = !empty($disabled);
?>
<div class="form-actions">
  <a class="button secondary" href="<?= e($cancelUrl) ?>"><?= e($cancelText) ?></a>
  <button class="button primary" id="<?= e($submitId) ?>" <?= $disabled ? 'disabled' : '' ?>>
    <?= e($submitText) ?>
  </button>
</div>
