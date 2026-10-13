<?php
/**
 * Plantilla Base de Módulo de Listado (Reciclable)
 * 
 * Uso: Copiar esta estructura para nuevos módulos con listados,
 * búsqueda compacta a la derecha y botón de acción principal.
 */
?>
<?= partial('components/page_header', [
  'title' => 'Nombre del Módulo',
  'search' => [
    'action' => url('modulo'),
    'query' => $query ?? '',
    'placeholder' => 'Buscar',
    'filter' => 'alpha-search',
    'pattern' => "[A-Za-zÁÉÍÓÚáéíóúÑñÜü\\s'\\-]*"
  ],
  'action' => [
    'url' => url('modulo/nuevo'),
    'text' => 'Agregar',
    'icon' => 'plus'
  ]
]) ?>

<div class="panel">
  <?php if (empty($items)): ?>
    <?= partial('components/empty_state', [
      'iconName' => 'search',
      'title' => 'No encontramos registros',
      'message' => 'Prueba con otro término o crea un nuevo elemento.',
      'actionUrl' => url('modulo/nuevo'),
      'actionText' => 'Agregar registro'
    ]) ?>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Descripción</th>
            <th>Fecha</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): ?>
            <tr>
              <td data-label="Nombre"><?= e($item['name']) ?></td>
              <td data-label="Descripción"><?= e($item['description'] ?? '—') ?></td>
              <td data-label="Fecha"><?= e($item['created_at'] ?? '—') ?></td>
              <td data-label="Acciones">
                <div class="table-actions">
                  <a class="button secondary small" href="<?= e(url('modulo/' . $item['id'] . '/editar')) ?>" title="Editar" aria-label="Editar">
                    <?= icon('edit') ?>
                    <span>Editar</span>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
