<?php
/**
 * Reusable Form Errors Component
 *
 * @var array|null $errors Array of error messages
 */
$errors = $errors ?? [];
?>
<?php if (!empty($errors)): ?>
  <div class="alert danger">
    <ul>
      <?php foreach ($errors as $error): ?>
        <li><?= e($error) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>
