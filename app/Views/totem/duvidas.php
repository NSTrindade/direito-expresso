<?php
/**
 * @var array $categoria
 * @var array $termos
 */

$tela = 'duvidas';
$breadcrumb = ['Início', 'Categoria', 'Dúvida', 'Resultado'];
$passoAtual = 2;
require __DIR__ . '/partials/layout-topo.php';
?>

<div class="totem-conteudo">
  <h1 class="totem-titulo"><?= e($categoria['nome']) ?></h1>
  <p class="totem-subtitulo">Toque na dúvida mais parecida com a sua. Se não achar, fale direto com a nossa assistente.</p>

  <div class="totem-grade">
    <?php foreach ($termos as $termo): ?>
      <a class="totem-cartao" href="totem.php?page=resultado&amp;termo=<?= (int) $termo['idTermo'] ?>">
        <span class="icone-grande">❓</span>
        <span><?= e($termo['termoTecnico']) ?></span>
      </a>
    <?php endforeach; ?>

    <a class="totem-cartao" style="background:var(--cor-primaria-clara);" href="totem.php?page=chatbot&amp;categoria=<?= (int) $categoria['idCategoria'] ?>">
      <span class="icone-grande">🤖</span>
      <span>Não achei minha dúvida</span>
      <span class="descricao-pequena">Toque aqui e explique com suas palavras</span>
    </a>
  </div>
</div>

<div class="totem-rodape">
  <a href="totem.php?page=categorias" class="totem-botao totem-botao-secundario">← Voltar</a>
  <a href="totem.php?page=inicio" class="totem-botao totem-botao-secundario">Recomeçar</a>
</div>

<?php require __DIR__ . '/partials/layout-rodape.php'; ?>
