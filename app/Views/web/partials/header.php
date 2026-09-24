<?php
/** @var string $paginaAtiva */
$paginaAtiva = $paginaAtiva ?? 'home';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Direito Expresso — Consulta jurídica simplificada</title>
  <link rel="stylesheet" href="assets/css/base.css">
  <link rel="stylesheet" href="assets/css/web.css">
</head>
<body>
  <header class="cabecalho">
    <div class="container">
      <a href="index.php" class="marca">
        <span class="marca-icone">DE</span>
        <span class="marca-texto">
          <strong>Direito Expresso</strong>
          <span>Consulta jurídica simplificada</span>
        </span>
      </a>
      <nav class="nav-principal">
        <a href="index.php" class="<?= $paginaAtiva === 'home' ? 'ativo' : '' ?>">Início</a>
        <a href="index.php?page=consultas" class="<?= $paginaAtiva === 'consultas' ? 'ativo' : '' ?>">Consultas</a>
        <a href="index.php?page=sobre" class="<?= $paginaAtiva === 'sobre' ? 'ativo' : '' ?>">Sobre</a>
        <a href="index.php?page=contato" class="<?= $paginaAtiva === 'contato' ? 'ativo' : '' ?>">Contato</a>
      </nav>
    </div>
  </header>
