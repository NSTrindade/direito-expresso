<?php
/**
 * @var string $tela          identificador da tela atual (usado pelo totem.js)
 * @var array  $breadcrumb    lista de rótulos das etapas, ex: ['Início','Categoria','Dúvida','Resultado']
 * @var int    $passoAtual    índice (0-based) da etapa atual dentro de $breadcrumb
 */
$breadcrumb = $breadcrumb ?? [];
$passoAtual = $passoAtual ?? 0;

$config = require dirname(__DIR__, 3) . '/Config/config.php';
?>
<!DOCTYPE html>
<html lang="pt-BR" class="totem">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Direito Expresso — Totem</title>
  <link rel="stylesheet" href="assets/css/base.css">
  <link rel="stylesheet" href="assets/css/totem.css">
  <script>window.TOTEM_TIMEOUT_SEGUNDOS = <?= (int) $config['app']['totem_timeout_segundos'] ?>;</script>
</head>
<body class="totem" data-tela="<?= e($tela ?? '') ?>">
  <div class="totem-tela">
    <div class="totem-topo">
      <div class="totem-logo">Direito <span>Expresso</span></div>
      <?php if (!empty($breadcrumb)): ?>
        <div class="totem-breadcrumb">
          <?php foreach ($breadcrumb as $indice => $rotulo): ?>
            <span class="<?= $indice === $passoAtual ? 'passo-atual' : '' ?>">
              <?= $indice + 1 ?>. <?= e($rotulo) ?>
            </span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
