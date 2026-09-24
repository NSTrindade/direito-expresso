/**
 * Direito Expresso — web.js
 * Reservado para pequenas melhorias de interação do site (nenhuma
 * lógica de negócio deve morar aqui — isso fica no PHP, nos
 * Controllers). Por enquanto, só um pequeno realce de acessibilidade:
 * remove o outline padrão de foco só para clique de mouse, mantendo-o
 * visível para navegação por teclado.
 */
document.body.addEventListener('mousedown', function () {
  document.body.classList.add('usando-mouse');
});

document.body.addEventListener('keydown', function (evento) {
  if (evento.key === 'Tab') {
    document.body.classList.remove('usando-mouse');
  }
});
