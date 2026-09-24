/**
 * Direito Expresso — admin.js
 * Confirmação antes de excluir qualquer registro no painel.
 */
document.addEventListener('submit', function (evento) {
  var formulario = evento.target;
  if (formulario.classList.contains('form-excluir')) {
    var confirmado = window.confirm('Tem certeza que deseja excluir este item? Essa ação não pode ser desfeita.');
    if (!confirmado) {
      evento.preventDefault();
    }
  }
});
