<?php
/**
 * @var array $termo
 * @var array $orgaos
 * @var array $leis
 */

$tela = 'resultado';
$breadcrumb = ['Início', 'Categoria', 'Dúvida', 'Resultado'];
$passoAtual = 3;
require __DIR__ . '/partials/layout-topo.php';
?>

<div class="totem-conteudo">
  <div class="totem-resultado">
    <div class="bloco">
      <h3>Você perguntou sobre</h3>
      <p style="font-weight:700;font-size:1.3rem;color:var(--cor-secundaria);"><?= e($termo['termoTecnico']) ?></p>
    </div>

    <div class="bloco">
      <h3>Em palavras simples</h3>
      <p><?= nl2br(e($termo['explicacaoColoquial'])) ?></p>
    </div>

    <?php if (!empty($termo['proxPassoSugerido'])): ?>
      <div class="bloco">
        <h3>👉 O que fazer agora</h3>
        <p><?= nl2br(e($termo['proxPassoSugerido'])) ?></p>
      </div>
    <?php endif; ?>

    <?php if (!empty($orgaos)): ?>
      <div class="bloco orgao-destino">
        <strong>📍 Procure este órgão:</strong>
        <?php foreach ($orgaos as $orgao): ?>
          <p style="margin:4px 0;">
            <?= e($orgao['nome']) ?>
            <?php if (!empty($orgao['telefone'])): ?> · ☎ <?= e($orgao['telefone']) ?><?php endif; ?>
          </p>
          <?php if (!empty($orgao['observacao'])): ?>
            <p class="texto-suave" style="margin:0;font-size:0.95rem;"><?= e($orgao['observacao']) ?></p>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($leis)): ?>
      <div class="bloco">
        <h3>Base legal</h3>
        <?php foreach ($leis as $lei): ?>
          <span class="pill pill-cinza"><?= e($lei['numeroLei']) ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div style="margin-top:24px;display:flex;gap:16px;flex-wrap:wrap;justify-content:center;">
    <a href="totem.php?page=chatbot&amp;categoria=<?= (int) $termo['idCategoria'] ?>" class="totem-botao totem-botao-primario">Ainda não entendi</a>
  </div>
</div>

<div class="totem-rodape">
  <a href="totem.php?page=duvidas&amp;categoria=<?= (int) $termo['idCategoria'] ?>" class="totem-botao totem-botao-secundario">← Voltar</a>
  <a href="totem.php?page=inicio" class="totem-botao totem-botao-secundario">Recomeçar</a>
</div>

<?php require __DIR__ . '/partials/layout-rodape.php'; ?>
