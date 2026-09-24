<?php
/**
 * @var string $usuarioLogado
 * @var array $leis
 * @var array $categorias
 * @var array|null $leiEmEdicao
 * @var array|null $mensagem
 */

$paginaAtiva = 'leis';
$tituloPagina = 'Leis';
require __DIR__ . '/partials/layout-topo.php';
require __DIR__ . '/partials/mensagem.php';
?>

<div class="admin-grade-form">
  <div class="card">
    <h2><?= $leiEmEdicao ? 'Editar lei' : 'Nova lei' ?></h2>
    <form action="admin.php?page=leis&amp;acao=<?= $leiEmEdicao ? 'editar' : 'criar' ?>" method="post">
      <?php if ($leiEmEdicao): ?>
        <input type="hidden" name="idLei" value="<?= (int) $leiEmEdicao['idLei'] ?>">
      <?php endif; ?>
      <div class="campo">
        <label for="idCategoria">Categoria</label>
        <select id="idCategoria" name="idCategoria" required>
          <?php foreach ($categorias as $categoria): ?>
            <option value="<?= (int) $categoria['idCategoria'] ?>" <?= ($leiEmEdicao['idCategoria'] ?? null) == $categoria['idCategoria'] ? 'selected' : '' ?>>
              <?= e($categoria['nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="campo">
        <label for="numeroLei">Número da lei</label>
        <input type="text" id="numeroLei" name="numeroLei" value="<?= e($leiEmEdicao['numeroLei'] ?? '') ?>" required>
      </div>
      <div class="campo">
        <label for="tipoNorma">Tipo de norma</label>
        <input type="text" id="tipoNorma" name="tipoNorma" placeholder="Lei Federal, Decreto..." value="<?= e($leiEmEdicao['tipoNorma'] ?? '') ?>" required>
      </div>
      <div class="campo">
        <label for="ementa">Ementa</label>
        <textarea id="ementa" name="ementa" required><?= e($leiEmEdicao['ementa'] ?? '') ?></textarea>
      </div>
      <div class="campo">
        <label for="dataPublicacao">Data de publicação</label>
        <input type="date" id="dataPublicacao" name="dataPublicacao" value="<?= e($leiEmEdicao['dataPublicacao'] ?? '') ?>">
      </div>
      <button type="submit" class="btn btn-primario btn-bloco"><?= $leiEmEdicao ? 'Salvar alterações' : 'Cadastrar lei' ?></button>
      <?php if ($leiEmEdicao): ?>
        <a href="admin.php?page=leis" class="btn btn-secundario btn-bloco" style="margin-top:10px;">Cancelar</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="tabela-wrapper">
    <table class="admin-tabela">
      <thead>
        <tr><th>Número</th><th>Categoria</th><th>Publicação</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($leis as $lei): ?>
          <tr>
            <td><?= e($lei['numeroLei']) ?></td>
            <td><?= e($lei['categoriaNome']) ?></td>
            <td><?= dataBr($lei['dataPublicacao']) ?></td>
            <td class="tabela-acoes">
              <a class="btn btn-secundario" href="admin.php?page=leis&amp;acao=editar&amp;id=<?= (int) $lei['idLei'] ?>">Editar</a>
              <form class="form-excluir" action="admin.php?page=leis&amp;acao=excluir" method="post">
                <input type="hidden" name="idLei" value="<?= (int) $lei['idLei'] ?>">
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
