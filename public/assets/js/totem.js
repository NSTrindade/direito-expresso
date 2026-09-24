/**
 * Direito Expresso — totem.js
 * Timer de inatividade: se o cidadão não tocar em nada por N segundos
 * (definido em app/Config/config.php -> app.totem_timeout_segundos,
 * injetado no HTML via window.TOTEM_TIMEOUT_SEGUNDOS), a tela volta
 * sozinha para o início — exatamente o "Fluxo Alternativo" do caso de
 * uso "Selecionar Categoria" (RF_07).
 */
(function () {
  var segundos = window.TOTEM_TIMEOUT_SEGUNDOS || 90;
  var estaNaTelaInicial = document.body.dataset.tela === 'inicio';
  var temporizador = null;

  function reiniciarContagem() {
    if (estaNaTelaInicial) {
      return; // já está no início, não precisa redirecionar
    }
    clearTimeout(temporizador);
    temporizador = setTimeout(function () {
      window.location.href = 'totem.php?page=inicio';
    }, segundos * 1000);
  }

  ['touchstart', 'mousedown', 'keydown'].forEach(function (evento) {
    document.addEventListener(evento, reiniciarContagem, { passive: true });
  });

  reiniciarContagem();
})();
