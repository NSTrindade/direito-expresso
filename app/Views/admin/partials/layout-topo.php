<?php
/**
 * @var string $paginaAtiva
 * @var string $usuarioLogado
 * @var string $tituloPagina
 */
$paginaAtiva = $paginaAtiva ?? 'dashboard';
$usuarioLogado = $usuarioLogado ?? '';
$tituloPagina = $tituloPagina ?? 'Painel';

$itensMenu = [
    'dashboard' => 'Dashboard',
    'categorias' => 'Categorias',
    'termos' => 'Termos jurídicos',
    'leis' => 'Leis',
    'orgaos' => 'Órgãos competentes',
    'usuarios' => 'Usuários',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($tituloPagina) ?> — Painel Direito Expresso</title>
  <link rel="stylesheet" href="assets/css/base.css">
  <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <div class="marca">
        <strong>Direito Expresso</strong>
        <span>Painel administrativo</span>
      </div>
      <nav class="admin-nav">
        <?php foreach ($itensMenu as $chave => $rotulo): ?>
          <a href="admin.php?page=<?= $chave ?>" class="<?= $paginaAtiva === $chave ? 'ativo' : '' ?>"><?= $rotulo ?></a>
        <?php endforeach; ?>
      </nav>
      <div class="admin-sair">
        <a href="admin.php?page=logout">Sair</a>
      </div>
    </aside>

    <main class="admin-conteudo">
      <div class="admin-cabecalho">
        <h1><?= e($tituloPagina) ?></h1>
        <div class="admin-usuario">Olá, <?= e($usuarioLogado) ?></div>
      </div>
