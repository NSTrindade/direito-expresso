<?php
/**
 * @var string $q
 * @var int|null $idCategoriaSelecionada
 * @var array $categorias
 * @var array $termos
 * @var array $leis
 */

$paginaAtiva = 'consultas';
require __DIR__ . '/partials/header.php';
?>

<section class="secao" style="padding-top:40px;">
  <div class="container">
    <h2>Consultas</h2>
    <p>Busque por uma palavra do seu problema (ex: "atraso na entrega") ou pelo nome de uma lei.</p>

    <form class="filtros-consulta" action="index.php" method="get">
      <input type="hidden" name="page" value="consultas">
      <input type="text" name="q" placeholder="Buscar por termo ou lei..." value="<?= e($q) ?>">
      <select name="categoria" onchange="this.form.submit()">
        <option value="">Todas as categorias</option>
        <?php foreach ($categorias as $categoria): ?>
          <option value="<?= (int) $categoria['idCategoria'] ?>" <?= $idCategoriaSelecionada === (int) $categoria['idCategoria'] ? 'selected' : '' ?>>
            <?= e($categoria['nome']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-secundario">Filtrar</button>
    </form>

    <?php if ($q !== ''): ?>
      <p class="texto-suave"><?= count($termos) + count($leis) ?> resultado(s) encontrado(s) para "<?= e($q) ?>"</p>
    <?php endif; ?>

    <h3 style="color:var(--cor-secundaria);">Termos e orientações</h3>
    <div class="lista-resultados">
      <?php if (empty($termos)): ?>
        <p class="texto-suave">Nenhum termo encontrado. Tente outra palavra ou fale com a nossa IA pelo totem mais próximo.</p>
      <?php endif; ?>

      <?php foreach ($termos as $termo): ?>
        <?php $orgaos = TermoJuridico::getOrgaos((int) $termo['idTermo']); ?>
        <details class="card item-resultado" style="display:block;">
          <summary style="cursor:pointer;list-style:none;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;">
              <div>
                <h3 style="display:inline;"><?= e($termo['termoTecnico']) ?></h3>
                <p style="margin-top:8px;"><?= e(mb_strimwidth($termo['explicacaoColoquial'], 0, 140, '…')) ?></p>
              </div>
              <span class="pill pill-verde"><?= e($termo['categoriaNome'] ?? '') ?></span>
            </div>
          </summary>
          <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--cor-borda);">
            <p><strong>Em outras palavras:</strong> <?= nl2br(e($termo['explicacaoColoquial'])) ?></p>
            <?php if (!empty($termo['proxPassoSugerido'])): ?>
              <p><strong>👉 O que fazer:</strong> <?= nl2br(e($termo['proxPassoSugerido'])) ?></p>
            <?php endif; ?>
            <?php if (!empty($orgaos)): ?>
              <p><strong>📍 Procure:</strong>
                <?php foreach ($orgaos as $orgao): ?>
                  <span class="pill pill-azul"><?= e($orgao['nome']) ?></span>
                <?php endforeach; ?>
              </p>
            <?php endif; ?>
          </div>
        </details>
      <?php endforeach; ?>
    </div>

    <?php if (!empty($leis)): ?>
      <h3 style="color:var(--cor-secundaria);margin-top:32px;">Legislação relacionada</h3>
      <div class="lista-resultados">
        <?php foreach ($leis as $lei): ?>
          <div class="card item-resultado">
            <div>
              <h3><?= e($lei['numeroLei']) ?> — <?= e($lei['tipoNorma']) ?></h3>
              <p><?= e($lei['ementa']) ?></p>
              <div class="meta">
                <span class="pill pill-cinza"><?= e($lei['categoriaNome'] ?? '') ?></span>
                <span><?= dataBr($lei['dataPublicacao']) ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
