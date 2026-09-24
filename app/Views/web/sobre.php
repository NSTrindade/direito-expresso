<?php
$paginaAtiva = 'sobre';
require __DIR__ . '/partials/header.php';

$equipe = [
    ['nome' => 'Guilherme Queiroz Penarrubia', 'papel' => 'Desenvolvimento'],
    ['nome' => 'Kelvin Silva Sena', 'papel' => 'Desenvolvimento'],
    ['nome' => 'Nicolas Silva Trindade', 'papel' => 'Desenvolvimento'],
    ['nome' => 'Pedro Henrique de Paula', 'papel' => 'Desenvolvimento'],
];
?>

<section class="sobre-hero">
  <div class="container">
    <h1>Sobre o Direito Expresso</h1>
    <p style="opacity:.85;max-width:640px;margin:0 auto;">Um TCC do curso Técnico em Desenvolvimento de Sistemas da ETEC Zona Leste, orientado pelo Prof. Marcelo Collado.</p>

    <div class="stats">
      <div class="card">
        <div class="numero">86%</div>
        <div>consideram o Judiciário distante</div>
      </div>
      <div class="card">
        <div class="numero">40,9%</div>
        <div>desistem por achar tudo burocrático</div>
      </div>
      <div class="card">
        <div class="numero">27,3%</div>
        <div>não sabiam a qual órgão recorrer</div>
      </div>
    </div>
  </div>
</section>

<section class="secao">
  <div class="container">
    <h2>O que é o Direito Expresso?</h2>
    <p>
      O "juridiquês" afasta as pessoas dos seus próprios direitos. O Direito Expresso nasceu para atacar
      esse problema na ponta: um totem físico e um site que traduzem leis complexas em orientações
      simples, e apontam exatamente qual órgão público procurar — Procon, Defensoria Pública, Justiça
      do Trabalho, Juizado Especial Cível — sem juridiquês e sem fila.
    </p>
    <p>
      Não somos consultoria jurídica. Somos o primeiro passo: entender o problema e saber para onde ir.
    </p>

    <h2 style="margin-top:40px;">Quem faz o Direito Expresso</h2>
    <div class="grade-categorias">
      <?php foreach ($equipe as $pessoa): ?>
        <div class="card" style="padding:22px;text-align:center;">
          <div class="icone pill-azul" style="margin:0 auto 12px;">🎓</div>
          <h3><?= e($pessoa['nome']) ?></h3>
          <p class="texto-suave"><?= e($pessoa['papel']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
