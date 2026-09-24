<?php
/** @var array $categorias */

$tela = 'categorias';
$breadcrumb = ['Início', 'Categoria', 'Dúvida', 'Resultado'];
$passoAtual = 1;
require __DIR__ . '/partials/layout-topo.php';

$estiloCategoria = [
    'Consumidor'        => '🛒',
    'Trabalho'          => '💼',
    'Moradia'           => '🏠',
    'Serviços Públicos' => '🏛️',
];
?>

<div class="totem-conteudo">
  <h1 class="totem-titulo">Qual é o seu problema?</h1>
  <p class="totem-subtitulo">Toque na categoria que mais parece com a sua situação.</p>

  <div class="totem-grade">
    <?php foreach ($categorias as $categoria): ?>
      <a class="totem-cartao" href="totem.php?page=duvidas&amp;categoria=<?= (int) $categoria['idCategoria'] ?>">
        <span class="icone-grande"><?= $estiloCategoria[$categoria['nome']] ?? '⚖️' ?></span>
        <span><?= e($categoria['nome']) ?></span>
        <span class="descricao-pequena"><?= e($categoria['descricao'] ?? '') ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<div class="totem-rodape">
  <a href="totem.php?page=inicio" class="totem-botao totem-botao-secundario">← Início</a>
  <div></div>
</div>

<?php require __DIR__ . '/partials/layout-rodape.php'; ?>
