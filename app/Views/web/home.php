<?php
/** @var array $categorias */

$paginaAtiva = 'home';
require __DIR__ . '/partials/header.php';

$estiloCategoria = [
    'Consumidor'        => ['icone' => '🛒', 'pill' => 'pill-verde'],
    'Trabalho'          => ['icone' => '💼', 'pill' => 'pill-azul'],
    'Moradia'           => ['icone' => '🏠', 'pill' => 'pill-laranja'],
    'Serviços Públicos' => ['icone' => '🏛️', 'pill' => 'pill-roxo'],
];
$pillsCiclo = ['pill-verde', 'pill-azul', 'pill-laranja', 'pill-roxo'];
?>

<section class="hero">
  <span class="pill pill-verde">● ACESSO LIVRE E GRATUITO</span>
  <h1>Entenda seus direitos <span>de forma simples</span></h1>
  <p>Conte o seu problema ou escolha uma categoria abaixo. Traduzimos o "juridiquês" para uma linguagem que qualquer pessoa entende, e indicamos exatamente para onde ir.</p>

  <form class="busca-caixa" action="index.php" method="get">
    <input type="hidden" name="page" value="consultas">
    <input type="text" name="q" placeholder="Ex: atraso na entrega, demissão, despejo...">
    <button type="submit" class="btn btn-primario">Buscar</button>
  </form>

  <div class="buscas-comuns">
    <span>Buscas comuns:</span>
    <a href="index.php?page=consultas&amp;q=vício+oculto">Vício oculto</a>
    <a href="index.php?page=consultas&amp;q=justa+causa">Justa causa</a>
    <a href="index.php?page=consultas&amp;q=despejo">Despejo</a>
    <a href="index.php?page=consultas&amp;q=liminar">Liminar</a>
  </div>
</section>

<section class="secao">
  <div class="container">
    <h2>Qual é o seu problema?</h2>
    <p>Escolha a categoria mais parecida com a sua situação.</p>

    <div class="grade-categorias">
      <?php foreach ($categorias as $indice => $categoria): ?>
        <?php
          $estilo = $estiloCategoria[$categoria['nome']] ?? [
              'icone' => '⚖️',
              'pill' => $pillsCiclo[$indice % 4],
          ];
        ?>
        <a class="card cartao-categoria" href="index.php?page=consultas&amp;categoria=<?= (int) $categoria['idCategoria'] ?>">
          <span class="icone <?= $estilo['pill'] ?>"><?= $estilo['icone'] ?></span>
          <h3><?= e($categoria['nome']) ?></h3>
          <p><?= e($categoria['descricao'] ?? '') ?></p>
          <span class="pill <?= $estilo['pill'] ?>">Ver orientações →</span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
