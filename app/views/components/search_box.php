<?php
declare(strict_types=1);

/**
 * Componente Genérico de Caja de Búsqueda (Search Box)
 *
 * Muestra un input con placeholder "Buscar", botón de lupa integrado a la izquierda
 * que ejecuta la búsqueda al presionarse o con Enter, y botón para limpiar cuando hay texto.
 *
 * @var string      $action       URL a la que se envía la búsqueda o a la que redirige al limpiar
 * @var string      $query        Término de búsqueda actual
 * @var string      $name         Nombre del input (por defecto: 'q')
 * @var string      $placeholder  Texto de placeholder (por defecto: 'Buscar')
 * @var string|null $filter       Atributo data-filter (ej. 'alpha-search', 'text')
 * @var string|null $pattern      Expresión regular de validación HTML5
 * @var string|null $class        Clases CSS adicionales para el contenedor .searchbox
 * @var string|null $id           ID HTML del input
 */
$action = $action ?? '';
$query = $query ?? '';
$name = $name ?? 'q';
$placeholder = 'Buscar';
$filter = $filter ?? '';
$pattern = $pattern ?? '';
$class = $class ?? '';
$id = $id ?? '';
?>
<div class="searchbox <?= e($class) ?>">
  <button type="submit" class="searchbox-btn" title="Buscar" aria-label="Buscar">
    <?= icon('search') ?>
  </button>
  <input <?= $id ? 'id="' . e($id) . '"' : '' ?>
         name="<?= e($name) ?>" 
         value="<?= e($query) ?>" 
         placeholder="<?= e($placeholder) ?>"
         <?= $filter ? 'data-filter="' . e($filter) . '"' : '' ?>
         <?= $pattern ? 'pattern="' . e($pattern) . '"' : '' ?>
         maxlength="100"
         autocomplete="off">
  <?php if ($query !== ''): ?>
    <a class="searchbox-clear" href="<?= e($action) ?>" title="Limpiar búsqueda" aria-label="Limpiar">
      <?= icon('close') ?>
    </a>
  <?php endif; ?>
</div>
