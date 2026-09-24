<?php
/**
 * @var string $usuarioLogado
 * @var array $orgaos
 * @var array|null $orgaoEmEdicao
 * @var array|null $mensagem
 */

$paginaAtiva = 'orgaos';
$tituloPagina = 'Órgãos competentes';
require __DIR__ . '/partials/layout-topo.php';
require __DIR__ . '/partials/mensagem.php';
?>

<div class="admin-grade-form">
  <div class="card">
    <h2><?= $orgaoEmEdicao ? 'Editar órgão' : 'Novo órgão' ?></h2>
    <form action="admin.php?page=orgaos&amp;acao=<?= $orgaoEmEdicao ? 'editar' : 'criar' ?>" method="post">
      <?php if ($orgaoEmEdicao): ?>
        <input type="hidden" name="idOrgao" value="<?= (int) $orgaoEmEdicao['idOrgao'] ?>">
      <?php endif; ?>
      <div class="campo">
        <label for="nome">Nome</label>
        <input type="text" id="nome" name="nome" value="<?= e($orgaoEmEdicao['nome'] ?? '') ?>" required>
      </div>
      <div class="campo">
        <label for="tipoOrgao">Tipo</label>
        <input type="text" id="tipoOrgao" name="tipoOrgao" placeholder="Órgão judiciário, defesa do consumidor..." value="<?= e($orgaoEmEdicao['tipoOrgao'] ?? '') ?>">
      </div>
      <div class="campo">
        <label for="endereco">Endereço</label>
        <input type="text" id="endereco" name="endereco" value="<?= e($orgaoEmEdicao['endereco'] ?? '') ?>">
      </div>
      <div class="campo">
        <label for="telefone">Telefone</label>
        <input type="text" id="telefone" name="telefone" value="<?= e($orgaoEmEdicao['telefone'] ?? '') ?>">
      </div>
      <div class="campo">
        <label for="site">Site</label>
        <input type="text" id="site" name="site" value="<?= e($orgaoEmEdicao['site'] ?? '') ?>">
      </div>
      <button type="submit" class="btn btn-primario btn-bloco"><?= $orgaoEmEdicao ? 'Salvar alterações' : 'Cadastrar órgão' ?></button>
      <?php if ($orgaoEmEdicao): ?>
        <a href="admin.php?page=orgaos" class="btn btn-secundario btn-bloco" style="margin-top:10px;">Cancelar</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="tabela-wrapper">
    <table class="admin-tabela">
      <thead>
        <tr><th>Nome</th><th>Tipo</th><th>Telefone</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($orgaos as $orgao): ?>
          <tr>
            <td><?= e($orgao['nome']) ?></td>
            <td class="texto-suave"><?= e($orgao['tipoOrgao'] ?? '') ?></td>
            <td><?= e($orgao['telefone'] ?? '') ?></td>
            <td class="tabela-acoes">
              <a class="btn btn-secundario" href="admin.php?page=orgaos&amp;acao=editar&amp;id=<?= (int) $orgao['idOrgao'] ?>">Editar</a>
              <form class="form-excluir" action="admin.php?page=orgaos&amp;acao=excluir" method="post">
                <input type="hidden" name="idOrgao" value="<?= (int) $orgao['idOrgao'] ?>">
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
