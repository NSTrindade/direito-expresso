<?php
/**
 * @var string $usuarioLogado
 * @var array $categorias
 * @var array|null $categoriaEmEdicao
 * @var array|null $mensagem
 */

$paginaAtiva = 'categorias';
$tituloPagina = 'Categorias';
require __DIR__ . '/partials/layout-topo.php';
require __DIR__ . '/partials/mensagem.php';
?>

<div class="admin-grade-form">
  <div class="card">
    <h2><?= $categoriaEmEdicao ? 'Editar categoria' : 'Nova categoria' ?></h2>
    <form action="admin.php?page=categorias&amp;acao=<?= $categoriaEmEdicao ? 'editar' : 'criar' ?>" method="post">
      <?php if ($categoriaEmEdicao): ?>
        <input type="hidden" name="idCategoria" value="<?= (int) $categoriaEmEdicao['idCategoria'] ?>">
      <?php endif; ?>
      <div class="campo">
        <label for="nome">Nome</label>
        <input type="text" id="nome" name="nome" value="<?= e($categoriaEmEdicao['nome'] ?? '') ?>" required>
      </div>
      <div class="campo">
        <label for="descricao">Descrição</label>
        <textarea id="descricao" name="descricao"><?= e($categoriaEmEdicao['descricao'] ?? '') ?></textarea>
      </div>
      <button type="submit" class="btn btn-primario btn-bloco"><?= $categoriaEmEdicao ? 'Salvar alterações' : 'Criar categoria' ?></button>
      <?php if ($categoriaEmEdicao): ?>
        <a href="admin.php?page=categorias" class="btn btn-secundario btn-bloco" style="margin-top:10px;">Cancelar</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="tabela-wrapper">
    <table class="admin-tabela">
      <thead>
        <tr><th>Nome</th><th>Descrição</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($categorias as $categoria): ?>
          <tr>
            <td><?= e($categoria['nome']) ?></td>
            <td class="texto-suave"><?= e(mb_strimwidth($categoria['descricao'] ?? '', 0, 60, '…')) ?></td>
            <td class="tabela-acoes">
              <a class="btn btn-secundario" href="admin.php?page=categorias&amp;acao=editar&amp;id=<?= (int) $categoria['idCategoria'] ?>">Editar</a>
              <form class="form-excluir" action="admin.php?page=categorias&amp;acao=excluir" method="post">
                <input type="hidden" name="idCategoria" value="<?= (int) $categoria['idCategoria'] ?>">
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
