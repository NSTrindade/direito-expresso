/**
 * Direito Expresso — totem-esp32.js
 * Liga o site ao totem físico (ESP32) através da ponte_esp32.py, que roda
 * no computador onde a placa está conectada (http://127.0.0.1:8765).
 *
 * 1. ESP32 -> site: quando o cidadão toca num botão da placa, a ponte manda o
 *    evento "navegar" e esta página troca de endereço NA MESMA ABA.
 * 2. Site -> ESP32: com o site no XAMPP, o PHP já avisa a ponte. Com o site
 *    hospedado na internet (Render), o servidor não alcança o computador do
 *    totem; então é o navegador que avisa a ponte a área sugerida pela IA e
 *    a volta ao início. (data-esp32-enviado="0" = o servidor não conseguiu.)
 *
 * Site na internet: o Chrome pede permissão para a página acessar
 * "dispositivos da rede local" na primeira vez. Clique em Permitir.
 * Se a ponte não estiver rodando, nada acontece e o site funciona normalmente.
 */
(function () {
  var PONTE = 'http://127.0.0.1:8765';

  function postPonte(rota, dados) {
    return fetch(PONTE + rota, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(dados || {})
    }).then(function (r) { return r.ok; }).catch(function () { return false; });
  }

  // ---- Site -> ESP32 (só quando o servidor não conseguiu avisar)
  function avisarPonte() {
    var area = document.querySelector('[data-esp32-area]');
    if (area && area.getAttribute('data-esp32-enviado') !== '1') {
      postPonte('/categoria', { categoria: area.getAttribute('data-esp32-area') }).then(function (ok) {
        if (!ok) return;
        area.setAttribute('data-esp32-enviado', '1');
        var com = area.querySelector('[data-esp32-texto="com-totem"]');
        var sem = area.querySelector('[data-esp32-texto="sem-totem"]');
        if (com) com.hidden = false;
        if (sem) sem.hidden = true;
      });
    }
    var reset = document.querySelector('[data-esp32-reset]');
    if (reset && reset.getAttribute('data-esp32-enviado') !== '1') {
      postPonte('/reset');
    }
  }

  // ---- ESP32 -> site
  // O Chrome/Edge só libera o acesso de um site da internet ao próprio computador
  // depois de um fetch (que pede a permissão "rede local"). Por isso a página
  // primeiro consulta /status e só então abre o canal de eventos; se o canal cair,
  // repete o processo a cada 3 segundos.
  var fonte = null;
  function escutarPonte() {
    if (!window.EventSource) return;
    fetch(PONTE + '/status').catch(function () {}).then(abrirCanal);
  }
  function abrirCanal() {
    if (fonte) fonte.close();
    fonte = new EventSource(PONTE + '/eventos');
    fonte.addEventListener('navegar', function (evento) {
      var dados;
      try { dados = JSON.parse(evento.data); } catch (e) { return; }
      if (dados.url && dados.url !== window.location.href) {
        window.location.href = dados.url;
      }
    });
    fonte.onerror = function () {
      if (fonte.readyState === EventSource.CLOSED) {
        setTimeout(escutarPonte, 3000);   // ponte desligada ou acesso ainda bloqueado
      }
      // readyState CONNECTING: o próprio EventSource tenta de novo
    };
  }

  function iniciar() { avisarPonte(); escutarPonte(); }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciar);
  } else {
    iniciar();
  }
})();
