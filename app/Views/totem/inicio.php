<?php
$tela = 'inicio';
require __DIR__ . '/partials/layout-topo.php';
?>

<?php /* marcador para o totem-esp32.js avisar a ponte quando o servidor não conseguir */ ?>
<span hidden data-esp32-reset data-esp32-enviado="<?= !empty($esp32Resetado) ? '1' : '0' ?>"></span>

<div class="totem-conteudo">
  <div class="totem-inicio">
    <div class="selo"><?php require dirname(__DIR__) . '/logo-simbolo.php'; ?></div>
    <div>
      <h1 class="totem-titulo">Direito Expresso</h1>
      <p class="totem-subtitulo">Toque na tela para entender um problema jurídico em linguagem simples e descobrir para onde ir.</p>
    </div>
    <a href="totem.php?page=categorias" class="totem-botao totem-botao-primario">Toque para começar</a>
  </div>
</div>

<?php require __DIR__ . '/partials/layout-rodape.php'; ?>
