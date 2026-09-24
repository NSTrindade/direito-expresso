<?php
/** @var array|null $mensagem */
if (!empty($mensagem)):
?>
  <div class="alerta alerta-<?= e($mensagem['tipo']) ?>"><?= e($mensagem['texto']) ?></div>
<?php endif; ?>
