<?php
/**
 * Símbolo do Direito Expresso: balão de conversa com uma balança dentro
 * (justiça explicada em linguagem simples). O balão usa a cor do texto
 * (currentColor) e a balança usa --logo-fundo, a cor do fundo onde o
 * símbolo é colocado. Usado no cabeçalho do site e na tela inicial do totem.
 */
?>
<svg class="logo-simbolo" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
  <path d="M13 9 H51 A7 7 0 0 1 58 16 V38 A7 7 0 0 1 51 45 H29 L17 56 V45 H13 A7 7 0 0 1 6 38 V16 A7 7 0 0 1 13 9 Z" fill="currentColor"/>
  <g fill="var(--logo-fundo, #1b8a4e)">
    <rect x="18" y="16" width="28" height="3" rx="1.5"/>
    <rect x="30.5" y="16" width="3" height="19"/>
    <rect x="25" y="35" width="14" height="3" rx="1.5"/>
    <path d="M22 19 L17.5 29 M22 19 L26.5 29 M42 19 L37.5 29 M42 19 L46.5 29" stroke="var(--logo-fundo, #1b8a4e)" stroke-width="1.6" stroke-linecap="round" fill="none"/>
    <path d="M16 29 H28 A6 6 0 0 1 16 29 Z M36 29 H48 A6 6 0 0 1 36 29 Z"/>
  </g>
</svg>
