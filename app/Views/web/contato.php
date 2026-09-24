<?php
/**
 * @var bool $enviado
 * @var string|null $erro
 */
$erro = $erro ?? null;
$nome = $nome ?? '';
$email = $email ?? '';
$assunto = $assunto ?? '';
$mensagem = $mensagem ?? '';

$paginaAtiva = 'contato';
require __DIR__ . '/partials/header.php';
?>

<section class="secao" style="padding-top:40px;">
  <div class="container">
    <h2>Contato</h2>
    <p>Dúvidas sobre o projeto, parcerias ou sugestões de melhoria? Fale com a gente.</p>

    <div class="grade-contato">
      <div class="card" style="padding:28px;">
        <?php if ($enviado): ?>
          <div class="alerta alerta-sucesso">Mensagem recebida! Em breve entraremos em contato.</div>
        <?php elseif ($erro): ?>
          <div class="alerta alerta-erro"><?= e($erro) ?></div>
        <?php endif; ?>

        <form action="index.php?page=contato" method="post">
          <div class="campo">
            <label for="nome">Nome completo</label>
            <input type="text" id="nome" name="nome" value="<?= e($nome) ?>" required>
          </div>
          <div class="campo">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>" required>
          </div>
          <div class="campo">
            <label for="assunto">Assunto</label>
            <input type="text" id="assunto" name="assunto" value="<?= e($assunto) ?>">
          </div>
          <div class="campo">
            <label for="mensagem">Mensagem</label>
            <textarea id="mensagem" name="mensagem" required><?= e($mensagem) ?></textarea>
          </div>
          <button type="submit" class="btn btn-primario btn-bloco">Enviar Mensagem</button>
        </form>
      </div>

      <div class="info-contato">
        <h3 style="color:var(--cor-secundaria);">Informações de contato</h3>
        <div class="item">
          <span class="icone">✉️</span>
          <div>
            <strong>E-mail</strong>
            <p class="texto-suave" style="margin:0;">contato@direitoexpresso.local</p>
          </div>
        </div>
        <div class="item">
          <span class="icone">📍</span>
          <div>
            <strong>Endereço</strong>
            <p class="texto-suave" style="margin:0;">ETEC Zona Leste — São Paulo/SP</p>
          </div>
        </div>
        <h3 style="color:var(--cor-secundaria);margin-top:24px;">Perguntas frequentes</h3>
        <p class="texto-suave">O Direito Expresso substitui um advogado? Não — somos um primeiro ponto de
        contato para você entender o problema e saber para onde ir.</p>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
