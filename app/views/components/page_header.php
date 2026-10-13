<?php
/**
 * Reusable Page Header Component
 *
 * @var string      $title             Page title
 * @var string|null $backUrl           URL for back button (optional)
 * @var string|null $backText          Label for back button (default: 'Volver')
 * @var array|null  $search            Search configuration [action, query, placeholder, filter, pattern, name]
 * @var array|null  $action            Primary action button [url, text, icon]
 * @var string|null $extraActions      Raw HTML for additional action items
 */
$title = $title ?? '';
$backUrl = $backUrl ?? null;
$backText = $backText ?? 'Volver';
$search = $search ?? null;
$action = $action ?? null;
$extraActions = $extraActions ?? '';
?>
<div class="page-heading">
  <div>
    <h1><?= e($title) ?></h1>
  </div>

  <?php if ($backUrl): ?>
    <a class="button secondary" href="<?= e($backUrl) ?>">
      <?= icon('arrow-left') ?>
      <span><?= e($backText) ?></span>
    </a>
  <?php elseif ($search || $action || $extraActions): ?>
    <div class="heading-actions">
      <?php if ($search): ?>
        <?php
        $searchAction = is_array($search) ? ($search['action'] ?? '') : (string)$search;
        $searchQuery = is_array($search) ? ($search['query'] ?? '') : '';
        $searchPlaceholder = is_array($search) ? ($search['placeholder'] ?? 'Buscar') : 'Buscar';
        $searchFilter = is_array($search) ? ($search['filter'] ?? 'alpha-search') : 'alpha-search';
        $searchPattern = is_array($search) ? ($search['pattern'] ?? "[A-Za-zÁÉÍÓÚáéíóúÑñÜü\\s'\\-]*") : "[A-Za-zÁÉÍÓÚáéíóúÑñÜü\\s'\\-]*";
        $searchName = is_array($search) ? ($search['name'] ?? 'q') : 'q';
        ?>
        <form class="heading-search" method="get" action="<?= e($searchAction) ?>">
          <div class="searchbox">
            <button type="submit" class="searchbox-btn" title="Buscar" aria-label="Buscar">
              <?= icon('search') ?>
            </button>
            <input name="<?= e($searchName) ?>" 
                   value="<?= e($searchQuery) ?>" 
                   placeholder="<?= e($searchPlaceholder) ?>"
                   data-filter="<?= e($searchFilter) ?>"
                   pattern="<?= e($searchPattern) ?>"
                   maxlength="100"
                   autocomplete="off">
            <?php if ($searchQuery !== ''): ?>
              <a class="searchbox-clear" href="<?= e($searchAction) ?>" title="Limpiar búsqueda" aria-label="Limpiar">
                <?= icon('close') ?>
              </a>
            <?php endif; ?>
          </div>
        </form>
      <?php endif; ?>

      <?= $extraActions ?>

      <?php if ($action): ?>
        <?php
        $actionUrl = $action['url'] ?? '';
        $actionText = $action['text'] ?? 'Agregar';
        $actionIcon = $action['icon'] ?? 'plus';
        ?>
        <a class="button primary" href="<?= e($actionUrl) ?>">
          <?= icon($actionIcon) ?>
          <span><?= e($actionText) ?></span>
        </a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
