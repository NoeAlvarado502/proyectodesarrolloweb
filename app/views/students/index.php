<?= partial('components/page_header', [
  'title' => 'Alumnos',
  'search' => [
    'action' => url('alumnos'),
    'query' => $query,
    'placeholder' => 'Buscar',
    'filter' => 'alpha-search',
    'pattern' => "[A-Za-zÁÉÍÓÚáéíóúÑñÜü\\s'\\-]*"
  ],
  'action' => [
    'url' => url('alumnos/nuevo'),
    'text' => 'Agregar',
    'icon' => 'plus'
  ]
]) ?>

<div class="panel">
  <?php if (!$students): ?>
    <?= partial('components/empty_state', [
      'iconName' => 'search',
      'title' => 'No encontramos alumnos',
      'message' => 'Prueba con otro término de búsqueda o registra un nuevo expediente.',
      'actionUrl' => url('alumnos/nuevo'),
      'actionText' => 'Registrar alumno'
    ]) ?>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Alumno</th>
            <th>Carrera</th>
            <th>Fecha de nacimiento</th>
            <th>Expediente</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $row): ?>
            <tr>
              <td data-label="Alumno">
                <a class="person-cell" href="<?= e(url('alumnos/' . $row['id'])) ?>">
                  <img class="mini-photo" src="<?= e(photoUrl($row['photo'])) ?>" alt="">
                  <strong><?= e($row['first_name'] . ' ' . $row['last_name']) ?></strong>
                </a>
              </td>
              <td data-label="Carrera"><?= e($row['career']) ?></td>
              <td data-label="Nacimiento"><?= e(date('d/m/Y', strtotime($row['birth_date']))) ?></td>
              <td data-label="Expediente" class="muted">AC-<?= str_pad((string)$row['id'], 5, '0', STR_PAD_LEFT) ?></td>
              <td data-label="Acciones" style="text-align: right;">
                <a class="row-action" href="<?= e(url('alumnos/' . $row['id'])) ?>">
                  <span>Abrir</span>
                  <?= icon('arrow-right') ?>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
