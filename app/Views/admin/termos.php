<?php
/**
 * @var string $usuarioLogado
 * @var array $termos
 * @var array $categorias
 * @var array $orgaosTodos
 * @var array $leisTodas
 * @var array|null $termoEmEdicao
 * @var array $orgaosVinculados
 * @var array $leisVinculadas
 * @var array|null $mensagem
 */

$paginaAtiva = 'termos';
$tituloPagina = 'Termos jurídicos';
require __DIR__ . '/partials/layout-topo.php';
require __DIR__ . '/partials/mensagem.php';
?>

<div class="admin-grade-form">
  <div class="card">
    <h2><?= $termoEmEdicao ? 'Editar termo' : 'Novo termo' ?></h2>
    <form action="admin.php?page=termos&amp;acao=<?= $termoEmEdicao ? 'editar' : 'criar' ?>" method="post">
      <?php if ($termoEmEdicao): ?>
        <input type="hidden" name="idTermo" value="<?= (int) $termoEmEdicao['idTermo'] ?>">
      <?php endif; ?>
      <div class="campo">
        <label for="idCategoria">Categoria</label>
        <select id="idCategoria" name="idCategoria" required>
          <?php foreach ($categorias as $categoria): ?>
            <option value="<?= (int) $categoria['idCategoria'] ?>" <?= ($termoEmEdicao['idCategoria'] ?? null) == $categoria['idCategoria'] ? 'selected' : '' ?>>
              <?= e($categoria['nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="campo">
        <label for="termoTecnico">Termo técnico</label>
        <input type="text" id="termoTecnico" name="termoTecnico" value="<?= e($termoEmEdicao['termoTecnico'] ?? '') ?>" required>
      </div>
      <div class="campo">
        <label for="explicacaoColoquial">Explicação em linguagem simples</label>
        <textarea id="explicacaoColoquial" name="explicacaoColoquial" required><?= e($termoEmEdicao['explicacaoColoquial'] ?? '') ?></textarea>
      </div>
      <div class="campo">
        <label for="proxPassoSugerido">Próximo passo sugerido</label>
        <textarea id="proxPassoSugerido" name="proxPassoSugerido"><?= e($termoEmEdicao['proxPassoSugerido'] ?? '') ?></textarea>
      </div>

      <div class="campo">
        <label>Órgãos competentes</label>
        <div class="checkbox-lista">
          <?php foreach ($orgaosTodos as $orgao): ?>
            <label>
              <input type="checkbox" name="orgaos[]" value="<?= (int) $orgao['idOrgao'] ?>" <?= in_array($orgao['idOrgao'], $orgaosVinculados) ? 'checked' : '' ?>>
              <?= e($orgao['nome']) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="campo">
        <label>Leis relacionadas</label>
        <div class="checkbox-lista">
          <?php foreach ($leisTodas as $lei): ?>
            <label>
              <input type="checkbox" name="leis[]" value="<?= (int) $lei['idLei'] ?>" <?= in_array($lei['idLei'], $leisVinculadas) ? 'checked' : '' ?>>
              <?= e($lei['numeroLei']) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <button type="submit" class="btn btn-primario btn-bloco"><?= $termoEmEdicao ? 'Salvar alterações' : 'Cadastrar termo' ?></button>
      <?php if ($termoEmEdicao): ?>
        <a href="admin.php?page=termos" class="btn btn-secundario btn-bloco" style="margin-top:10px;">Cancelar</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="tabela-wrapper">
    <table class="admin-tabela">
      <thead>
        <tr><th>Termo</th><th>Categoria</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($termos as $termo): ?>
          <tr>
            <td><?= e($termo['termoTecnico']) ?></td>
            <td><?= e($termo['categoriaNome']) ?></td>
            <td class="tabela-acoes">
              <a class="btn btn-secundario" href="admin.php?page=termos&amp;acao=editar&amp;id=<?= (int) $termo['idTermo'] ?>">Editar</a>
              <form class="form-excluir" action="admin.php?page=termos&amp;acao=excluir" method="post">
                <input type="hidden" name="idTermo" value="<?= (int) $termo['idTermo'] ?>">
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
