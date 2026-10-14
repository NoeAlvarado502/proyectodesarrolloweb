<?php
ob_start(); ?>
<select name="status" class="custom-select filter-select" data-icon="filter" onchange="this.form.submit()" aria-label="Filtrar por estado">
  <option value="" <?= ($statusFilter === '') ? 'selected' : '' ?>>Todos los estados</option>
  <option value="activo" <?= ($statusFilter === 'activo') ? 'selected' : '' ?>>Activos</option>
  <option value="inactivo" <?= ($statusFilter === 'inactivo') ? 'selected' : '' ?>>Inactivos</option>
</select>
<?php
$statusFilterHtml = ob_get_clean();
?>
<?= partial('components/page_header', [
  'title' => 'Catedráticos',
  'search' => [
    'action' => url('catedraticos' . ($statusFilter !== '' ? '?status=' . $statusFilter : '')),
    'query' => $query,
    'placeholder' => 'Buscar',
    'filter' => 'text',
    'filter_select' => $statusFilterHtml
  ],
  'action' => [
    'url' => url('catedraticos/nuevo'),
    'text' => 'Agregar',
    'icon' => 'plus'
  ]
]) ?>

<div class="panel">
  <div class="toolbar" style="border-bottom: 1px solid var(--border-subtle); padding: 12px 20px;">
    <div class="toolbar-count">
      <?= count($professors) ?> <?= count($professors) === 1 ? 'catedrático registrado' : 'catedráticos registrados' ?>
    </div>
  </div>

  <?php if (empty($professors)): ?>
    <?= partial('components/empty_state', [
      'iconName' => 'users',
      'title' => 'No encontramos catedráticos',
      'message' => $query !== '' ? 'Intenta buscar con otro término o elimina el filtro.' : 'Registra el primer catedrático para asignarlo a secciones.',
      'actionUrl' => url('catedraticos/nuevo'),
      'actionText' => 'Registrar catedrático'
    ]) ?>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Código</th>
            <th>Nombre</th>
            <th>Correo</th>
            <th>Especialidad</th>
            <th>Estado</th>
            <th>Secciones activas</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($professors as $p): ?>
            <tr>
              <td data-label="Código">
                <span class="status-badge badge-neutral" style="font-family: monospace; font-size: 12px;">
                  <?= e($p['code']) ?>
                </span>
              </td>
              <td data-label="Nombre">
                <span class="person-cell">
                  <span class="mini-avatar"><?= icon('user') ?></span>
                  <div>
                    <a href="<?= e(url('catedraticos/' . $p['id'])) ?>" style="color: inherit; text-decoration: none; font-weight: 600;">
                      <?= e($p['first_name'] . ' ' . $p['last_name']) ?>
                    </a>
                    <?php if (!empty($p['document'])): ?>
                      <div class="muted" style="font-size: 12px;">DPI: <?= e($p['document']) ?></div>
                    <?php endif; ?>
                  </div>
                </span>
              </td>
              <td data-label="Correo">
                <span class="muted"><?= e($p['email']) ?></span>
              </td>
              <td data-label="Especialidad">
                <span class="muted"><?= e($p['specialty'] ?: '&mdash;') ?></span>
              </td>
              <td data-label="Estado">
                <?php if ($p['status'] === 'activo'): ?>
                  <span class="status-badge badge-green">Activo</span>
                <?php else: ?>
                  <span class="status-badge badge-neutral">Inactivo</span>
                <?php endif; ?>
              </td>
              <td data-label="Secciones activas">
                <span class="muted"><?= (int)$p['active_sections_count'] ?> <?= (int)$p['active_sections_count'] === 1 ? 'sección' : 'secciones' ?></span>
              </td>
              <td data-label="Acciones" style="text-align: right;">
                <div class="table-actions">
                  <a class="button secondary small" href="<?= e(url('catedraticos/' . $p['id'])) ?>" title="Ver ficha del catedrático" aria-label="Ver ficha del catedrático">
                    <?= icon('info') ?>
                    <span>Ficha</span>
                  </a>
                  <a class="button secondary small" href="<?= e(url('catedraticos/' . $p['id'] . '/editar')) ?>" title="Editar datos del catedrático" aria-label="Editar datos">
                    <?= icon('edit') ?>
                    <span>Editar</span>
                  </a>
                  <?php if ((int)$p['total_sections_count'] === 0): ?>
                    <form method="post" action="<?= e(url('catedraticos/' . $p['id'] . '/eliminar')) ?>" 
                          data-confirm="¿Deseas eliminar al catedrático &quot;<?= e($p['first_name'] . ' ' . $p['last_name']) ?>&quot;?">
                      <?= csrfField() ?>
                      <button class="button danger-outline small" type="submit" title="Eliminar registro" aria-label="Eliminar registro">
                        <?= icon('trash') ?>
                        <span>Eliminar</span>
                      </button>
                    </form>
                  <?php else: ?>
                    <form method="post" action="<?= e(url('catedraticos/' . $p['id'] . '/toggle-estado')) ?>">
                      <?= csrfField() ?>
                      <button class="button secondary small" type="submit" title="<?= $p['status'] === 'activo' ? 'Desactivar catedrático' : 'Activar catedrático' ?>" aria-label="<?= $p['status'] === 'activo' ? 'Desactivar' : 'Activar' ?>">
                        <?= icon($p['status'] === 'activo' ? 'close' : 'check') ?>
                        <span><?= $p['status'] === 'activo' ? 'Desactivar' : 'Activar' ?></span>
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
