<?php
/**
 * @var string $usuarioLogado
 * @var int $totalCategorias
 * @var int $totalTermos
 * @var array $consultasRecentes
 * @var array $termosMaisConsultados
 * @var array|null $mensagem
 */

$paginaAtiva = 'dashboard';
$tituloPagina = 'Dashboard';
require __DIR__ . '/partials/layout-topo.php';
require __DIR__ . '/partials/mensagem.php';
?>

<div class="admin-stats">
  <div class="card">
    <div class="numero"><?= $totalCategorias ?></div>
    <div class="rotulo">Categorias cadastradas</div>
  </div>
  <div class="card">
    <div class="numero"><?= $totalTermos ?></div>
    <div class="rotulo">Termos no dicionário</div>
  </div>
  <div class="card">
    <div class="numero"><?= count($consultasRecentes) ?></div>
    <div class="rotulo">Consultas recentes exibidas</div>
  </div>
</div>

<h2 style="color:var(--cor-secundaria);font-size:1.1rem;">Termos mais consultados</h2>
<div class="tabela-wrapper" style="margin-bottom:32px;">
  <table class="admin-tabela">
    <thead>
      <tr><th>Termo</th><th>Consultas</th></tr>
    </thead>
    <tbody>
      <?php if (empty($termosMaisConsultados)): ?>
        <tr><td colspan="2" class="texto-suave">Ainda não há consultas registradas.</td></tr>
      <?php endif; ?>
      <?php foreach ($termosMaisConsultados as $linha): ?>
        <tr>
          <td><?= e($linha['termoTecnico']) ?></td>
          <td><?= (int) $linha['total'] ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<h2 style="color:var(--cor-secundaria);font-size:1.1rem;">Consultas recentes</h2>
<div class="tabela-wrapper">
  <table class="admin-tabela">
    <thead>
      <tr><th>Data/hora</th><th>Termo</th><th>Canal</th></tr>
    </thead>
    <tbody>
      <?php if (empty($consultasRecentes)): ?>
        <tr><td colspan="3" class="texto-suave">Ainda não há consultas registradas.</td></tr>
      <?php endif; ?>
      <?php foreach ($consultasRecentes as $consulta): ?>
        <tr>
          <td><?= e($consulta['dataHora']) ?></td>
          <td><?= e($consulta['termoTecnico']) ?></td>
          <td><span class="pill pill-cinza"><?= e($consulta['canalOrigem']) ?></span></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/partials/layout-rodape.php'; ?>
