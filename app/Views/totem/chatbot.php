<?php
/**
 * @var array|null $categoria
 * @var array $conversa
 * @var string|null $erroApi
 * @var string|null $erroDetalhe  motivo técnico (só fora de produção)
 * @var array|null $areaSugerida  ['codigo','nome','origem','enviadoAoTotem'] (ver TriagemController)
 */

$tela = 'chatbot';
$breadcrumb = ['Início', 'Categoria', 'Dúvida', 'Assistente IA'];
$passoAtual = 3;
require __DIR__ . '/partials/layout-topo.php';
?>

<div class="totem-conteudo">
  <h1 class="totem-titulo">Fale com a assistente</h1>
  <p class="totem-subtitulo">Conte o que aconteceu com suas palavras. Vamos traduzir e indicar o que fazer.</p>

  <?php if ($erroApi): ?>
    <div class="alerta alerta-erro" style="max-width:640px;"><?= e($erroApi) ?>
      <?php if (!empty($erroDetalhe)): ?>
        <br><small style="opacity:.75;font-weight:400;">Detalhe técnico: <?= e($erroDetalhe) ?></small>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($areaSugerida)): ?>
    <div class="totem-area-sugerida" role="status"
         data-esp32-area="<?= e($areaSugerida['codigo'] ?? 'INDEFINIDA') ?>"
         data-esp32-enviado="<?= !empty($areaSugerida['enviadoAoTotem']) ? '1' : '0' ?>">
      <?php if (!empty($areaSugerida['codigo'])): ?>
        <div>
          <strong>Área sugerida: <?= e($areaSugerida['nome']) ?></strong><br>
          <?php $noTotem = !empty($areaSugerida['enviadoAoTotem']); ?>
          <span data-esp32-texto="com-totem" <?= $noTotem ? '' : 'hidden' ?>>Toque no botão <b><?= e($areaSugerida['codigo']) ?></b> na telinha do totem para ver as orientações.</span>
          <span data-esp32-texto="sem-totem" <?= $noTotem ? 'hidden' : '' ?>>Toque no botão ao lado para ver as orientações dessa área.</span>
        </div>
        <a class="totem-botao totem-botao-primario" href="totem.php?page=area&amp;codigo=<?= e($areaSugerida['codigo']) ?>">Ver <?= e($areaSugerida['nome']) ?> →</a>
      <?php else: ?>
        <div>
          <strong>Ainda não deu para identificar a área.</strong><br>
          Conte mais detalhes ou escolha uma das áreas na telinha do totem.
        </div>
        <a class="totem-botao totem-botao-secundario" href="totem.php?page=categorias">Ver áreas</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="totem-chat">
    <?php if (empty($conversa)): ?>
      <div class="totem-bolha totem-bolha-ia">Oi! Pode me contar o que aconteceu? Por exemplo: "comprei uma geladeira e ela veio com defeito".</div>
    <?php endif; ?>

    <?php foreach ($conversa as $mensagem): ?>
      <div class="totem-bolha <?= $mensagem['autor'] === 'cidadao' ? 'totem-bolha-cidadao' : 'totem-bolha-ia' ?>">
        <?= nl2br(e($mensagem['texto'])) ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<form class="totem-chat-form" action="totem.php?page=chatbot" method="post">
  <?php if (!empty($categoria)): ?>
    <input type="hidden" name="categoria" value="<?= (int) $categoria['idCategoria'] ?>">
  <?php endif; ?>
  <textarea name="relato" placeholder="Digite aqui o que aconteceu..." required autofocus></textarea>
  <button type="submit" class="totem-botao totem-botao-primario">Enviar</button>
</form>

<div class="totem-rodape">
  <a href="<?= !empty($categoria) ? 'totem.php?page=duvidas&categoria=' . (int) $categoria['idCategoria'] : 'totem.php?page=categorias' ?>" class="totem-botao totem-botao-secundario">← Voltar</a>
  <a href="totem.php?page=inicio" class="totem-botao totem-botao-secundario">Recomeçar</a>
</div>

<?php require __DIR__ . '/partials/layout-rodape.php'; ?>
