<?php
$tela = 'inicio';
require __DIR__ . '/partials/layout-topo.php';
?>

<div class="totem-conteudo">
  <div class="totem-inicio">
    <div class="selo">DE</div>
    <div>
      <h1 class="totem-titulo">Direito Expresso</h1>
      <p class="totem-subtitulo">Toque na tela para entender um problema jurídico em linguagem simples e descobrir para onde ir.</p>
    </div>
    <a href="totem.php?page=categorias" class="totem-botao totem-botao-primario">Toque para começar</a>
  </div>
</div>

<?php require __DIR__ . '/partials/layout-rodape.php'; ?>
