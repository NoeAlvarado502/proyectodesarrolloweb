<?php
/**
 * Reusable Empty State Component
 *
 * @var string      $iconName    Icon identifier (default: 'search')
 * @var string      $title       Main title text
 * @var string|null $message     Secondary description text
 * @var string|null $actionUrl   URL for action button (optional)
 * @var string|null $actionText  Text for action button (default: 'Agregar')
 * @var string|null $actionIcon  Icon for action button (default: 'plus')
 * @var bool|null   $compact     Whether to use compact padding
 */
$iconName = $iconName ?? 'search';
$title = $title ?? 'No se encontraron resultados';
$message = $message ?? '';
$actionUrl = $actionUrl ?? null;
$actionText = $actionText ?? 'Agregar';
$actionIcon = $actionIcon ?? 'plus';
$compactClass = !empty($compact) ? ' compact' : '';
?>
<div class="empty<?= $compactClass ?>">
  <div class="empty-icon"><?= icon($iconName) ?></div>
  <strong><?= e($title) ?></strong>
  <?php if ($message): ?>
    <p><?= e($message) ?></p>
  <?php endif; ?>
  <?php if ($actionUrl): ?>
    <a class="button primary" href="<?= e($actionUrl) ?>">
      <?= icon($actionIcon) ?>
      <span><?= e($actionText) ?></span>
    </a>
  <?php endif; ?>
</div>
