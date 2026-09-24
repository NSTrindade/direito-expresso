<?php
/**
 * @var string $usuarioLogado
 * @var array $usuarios
 * @var array|null $usuarioEmEdicao
 * @var array|null $mensagem
 */

$paginaAtiva = 'usuarios';
$tituloPagina = 'Usuários';
require __DIR__ . '/partials/layout-topo.php';
require __DIR__ . '/partials/mensagem.php';
?>

<div class="admin-grade-form">
  <div class="card">
    <h2><?= $usuarioEmEdicao ? 'Editar usuário' : 'Novo usuário' ?></h2>
    <form action="admin.php?page=usuarios&amp;acao=<?= $usuarioEmEdicao ? 'editar' : 'criar' ?>" method="post">
      <?php if ($usuarioEmEdicao): ?>
        <input type="hidden" name="idUsuario" value="<?= (int) $usuarioEmEdicao['idUsuario'] ?>">
      <?php endif; ?>
      <div class="campo">
        <label for="nome">Nome</label>
        <input type="text" id="nome" name="nome" value="<?= e($usuarioEmEdicao['nome'] ?? '') ?>" required>
      </div>
      <div class="campo">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" value="<?= e($usuarioEmEdicao['email'] ?? '') ?>" required>
      </div>
      <div class="campo">
        <label for="senha">Senha <?= $usuarioEmEdicao ? '(deixe em branco para manter a atual)' : '' ?></label>
        <input type="password" id="senha" name="senha" <?= $usuarioEmEdicao ? '' : 'required' ?>>
      </div>
      <button type="submit" class="btn btn-primario btn-bloco"><?= $usuarioEmEdicao ? 'Salvar alterações' : 'Criar usuário' ?></button>
      <?php if ($usuarioEmEdicao): ?>
        <a href="admin.php?page=usuarios" class="btn btn-secundario btn-bloco" style="margin-top:10px;">Cancelar</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="tabela-wrapper">
    <table class="admin-tabela">
      <thead>
        <tr><th>Nome</th><th>E-mail</th><th>Cadastro</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $usuario): ?>
          <tr>
            <td><?= e($usuario['nome']) ?></td>
            <td><?= e($usuario['email']) ?></td>
            <td><?= dataBr($usuario['dataCadastro']) ?></td>
            <td class="tabela-acoes">
              <a class="btn btn-secundario" href="admin.php?page=usuarios&amp;acao=editar&amp;id=<?= (int) $usuario['idUsuario'] ?>">Editar</a>
              <form class="form-excluir" action="admin.php?page=usuarios&amp;acao=excluir" method="post">
                <input type="hidden" name="idUsuario" value="<?= (int) $usuario['idUsuario'] ?>">
                <button type="submit" class="btn btn-perigo">Excluir</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/partials/layout-rodape.php'; ?>
