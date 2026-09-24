<?php
/** @var string|null $erro */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — Painel Direito Expresso</title>
  <link rel="stylesheet" href="assets/css/base.css">
  <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
  <div class="admin-login-tela">
    <div class="admin-login-caixa">
      <div class="marca">
        <strong>Direito Expresso</strong>
        <span class="texto-suave">Painel administrativo</span>
      </div>

      <?php if (!empty($erro)): ?>
        <div class="alerta alerta-erro"><?= e($erro) ?></div>
      <?php endif; ?>

      <form action="admin.php?page=login" method="post">
        <div class="campo">
          <label for="email">E-mail</label>
          <input type="email" id="email" name="email" required autofocus>
        </div>
        <div class="campo">
          <label for="senha">Senha</label>
          <input type="password" id="senha" name="senha" required>
        </div>
        <button type="submit" class="btn btn-primario btn-bloco">Entrar</button>
      </form>
    </div>
  </div>
</body>
</html>
