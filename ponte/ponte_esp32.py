"""
Direito Expresso — ponte entre o ESP32 e o computador (v2)

O que faz:
  1. Lê a porta serial (COM4, 115200) e reage aos comandos do ESP32:
       ABRIR_IA                 -> abre o chatbot
       ABRIR_CATEGORIA:<CAT>    -> abre a página da categoria
       INICIO                   -> volta o site à tela inicial
  2. Sobe um servidor HTTP local (http://127.0.0.1:8765) para o SITE falar com o ESP32:
       POST /categoria  {"categoria": "CONSUMIDOR"}  -> envia CATEGORIA:CONSUMIDOR ao ESP32
       POST /reset                                   -> envia RESET ao ESP32
       POST /escolha                                 -> mostra as 4 áreas no ESP32
       GET  /status                                  -> situação da ponte (teste)
       GET  /eventos                                 -> canal SSE: a página do totem recebe
                                                        "navegar" e troca de página na MESMA aba
  3. Se nenhuma página do totem estiver aberta escutando /eventos, abre o navegador
     com webbrowser (comportamento da v1).

Uso:
    python ponte_esp32.py                 # porta COM4
    python ponte_esp32.py --porta COM5
    python ponte_esp32.py --sem-serial    # testa o site sem a placa conectada
    python ponte_esp32.py --site https://SEU-SITE.onrender.com/totem.php   # site no Render

Requer: Python 3.10+ e PySerial (pip install pyserial)
"""

from __future__ import annotations

import argparse
import json
import queue
import threading
import time
import unicodedata
import webbrowser
from datetime import datetime
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from urllib.parse import urlparse

try:
    import serial  # PySerial
except ImportError:  # permite rodar --sem-serial sem PySerial instalado
    serial = None

# ---------------------------------------------------------------------------
# CONFIGURAÇÃO — ajuste aqui
# ---------------------------------------------------------------------------
PORTA_PADRAO = "COM4"
BAUD = 115200
HTTP_HOST = "127.0.0.1"
HTTP_PORTA = 8765

# Endereço do totem (a página totem.php). Pode ser o XAMPP ou o site no Render.
# Também dá para passar na hora: python ponte_esp32.py --site https://SEU-SITE.onrender.com/totem.php
BASE_TOTEM = "http://localhost/direito-expresso/public/totem.php"

# Sites autorizados a falar com a ponte. O site do --site entra sozinho.
ORIGENS_PERMITIDAS = {"http://localhost", "http://127.0.0.1"}

URL_INICIO = URL_CHATBOT = ""
URLS_CATEGORIA: dict[str, str] = {}


def configurar_site(base: str) -> None:
    """Monta os endereços que os botões do ESP32 abrem, a partir do totem.php."""
    global BASE_TOTEM, URL_INICIO, URL_CHATBOT, URLS_CATEGORIA
    BASE_TOTEM = base.strip().split("?")[0]
    URL_INICIO = f"{BASE_TOTEM}?page=inicio"
    URL_CHATBOT = f"{BASE_TOTEM}?page=chatbot&novo=1"   # novo=1 = conversa limpa
    # page=area procura a categoria pelo NOME no banco e abre as dúvidas dela
    URLS_CATEGORIA = {
        codigo: f"{BASE_TOTEM}?page=area&codigo={codigo}"
        for codigo in ("CONSUMIDOR", "CIVIL", "MORADIA", "TRABALHO")
    }
    partes = urlparse(BASE_TOTEM)
    if partes.scheme and partes.netloc:
        ORIGENS_PERMITIDAS.add(f"{partes.scheme}://{partes.netloc}")


configurar_site(BASE_TOTEM)

# Nomes que a IA ou o site podem mandar e o código correspondente.
SINONIMOS = {
    "CONSUMIDOR": "CONSUMIDOR",
    "DIREITO DO CONSUMIDOR": "CONSUMIDOR",
    "CIVIL": "CIVIL",
    "DIREITO CIVIL": "CIVIL",
    "MORADIA": "MORADIA",
    "DIREITO A MORADIA": "MORADIA",
    "HABITACAO": "MORADIA",
    "TRABALHO": "TRABALHO",
    "TRABALHISTA": "TRABALHO",
    "DIREITO DO TRABALHO": "TRABALHO",
    "INDEFINIDA": "INDEFINIDA",
    "INDEFINIDO": "INDEFINIDA",
}


# ---------------------------------------------------------------------------
def log(msg: str) -> None:
    print(f"[{datetime.now():%H:%M:%S}] {msg}", flush=True)


def normalizar_categoria(valor: str | None) -> str | None:
    """'Direito do Consumidor' -> 'CONSUMIDOR'. Retorna None se não reconhecer."""
    if not valor:
        return None
    sem_acento = unicodedata.normalize("NFKD", str(valor)).encode("ascii", "ignore").decode()
    chave = " ".join(sem_acento.upper().replace("_", " ").split())
    return SINONIMOS.get(chave)


# ---------------------------------------------------------------------------
# Eventos para as páginas abertas (Server-Sent Events)
# ---------------------------------------------------------------------------
class CanalEventos:
    def __init__(self) -> None:
        self._clientes: list[queue.Queue] = []
        self._trava = threading.Lock()

    def conectar(self) -> queue.Queue:
        q: queue.Queue = queue.Queue()
        with self._trava:
            self._clientes.append(q)
        return q

    def desconectar(self, q: queue.Queue) -> None:
        with self._trava:
            if q in self._clientes:
                self._clientes.remove(q)

    @property
    def total(self) -> int:
        with self._trava:
            return len(self._clientes)

    def publicar(self, tipo: str, dados: dict) -> int:
        with self._trava:
            for q in self._clientes:
                q.put((tipo, dados))
            return len(self._clientes)


# ---------------------------------------------------------------------------
# Ponte
# ---------------------------------------------------------------------------
class Ponte:
    def __init__(self, porta: str | None) -> None:
        self.porta = porta
        self.serial = None
        self.trava_escrita = threading.Lock()
        self.eventos = CanalEventos()
        self.esp32_conectado = False
        self.ultima_categoria: str | None = None
        self.rodando = True

    # ---------------- serial ----------------
    def loop_serial(self) -> None:
        if self.porta is None:
            log("Modo --sem-serial: os comandos para o ESP32 só aparecem no log.")
            return
        if serial is None:
            log("PySerial não está instalado. Rode: pip install pyserial")
            return
        while self.rodando:
            try:
                log(f"Conectando ao ESP32 em {self.porta}...")
                with serial.Serial(self.porta, BAUD, timeout=0.2) as s:
                    self.serial = s
                    self.esp32_conectado = True
                    log("ESP32 conectado.")
                    buffer = b""
                    while self.rodando:
                        buffer += s.read(256)
                        while b"\n" in buffer:
                            linha, buffer = buffer.split(b"\n", 1)
                            texto = linha.decode("utf-8", "ignore").strip()
                            if texto:
                                self.tratar_mensagem_esp32(texto)
            except Exception as erro:  # serial.SerialException, OSError etc.
                if self.esp32_conectado:
                    log(f"Conexão com o ESP32 perdida: {erro}")
                else:
                    log(f"Não foi possível abrir {self.porta}: {erro}")
                    log("Feche o Monitor Serial do Arduino IDE se estiver aberto.")
            self.serial = None
            self.esp32_conectado = False
            time.sleep(3)

    def enviar_esp32(self, comando: str) -> bool:
        log(f"PC -> ESP32: {comando}")
        if self.porta is None:
            return True  # modo de teste sem placa
        s = self.serial
        if s is None:
            log("  (ESP32 desconectado: comando não enviado)")
            return False
        with self.trava_escrita:
            try:
                s.write((comando + "\n").encode("ascii"))
                s.flush()
                return True
            except Exception as erro:
                log(f"  Falha ao enviar: {erro}")
                return False

    def tratar_mensagem_esp32(self, msg: str) -> None:
        log(f"ESP32 -> PC: {msg}")
        if msg == "ABRIR_IA":
            self.ultima_categoria = None
            self.navegar(URL_CHATBOT, novo_atendimento=True)
        elif msg.startswith("ABRIR_CATEGORIA:"):
            cat = normalizar_categoria(msg.split(":", 1)[1])
            url = URLS_CATEGORIA.get(cat or "")
            if url:
                self.navegar(url)
            else:
                log(f"  Categoria desconhecida: {msg}")
        elif msg == "INICIO":
            self.ultima_categoria = None
            self.navegar(URL_INICIO, novo_atendimento=True)
        elif msg == "PRONTO":
            log("  ESP32 reiniciou e está na tela inicial.")
        # PONG, OK:..., ERRO:... e DEBUG:... ficam apenas no log

    # ---------------- navegador ----------------
    def navegar(self, url: str, novo_atendimento: bool = False) -> None:
        paginas = self.eventos.publicar("navegar", {"url": url, "novo_atendimento": novo_atendimento})
        if paginas:
            log(f"  Navegando na aba do totem ({paginas} página(s)): {url}")
        else:
            log(f"  Nenhuma página do totem escutando; abrindo o navegador: {url}")
            webbrowser.open(url, new=0)

    # ---------------- ações vindas do site ----------------
    def definir_categoria(self, valor: str | None) -> tuple[bool, str]:
        cat = normalizar_categoria(valor)
        if cat is None:
            return False, f"categoria inválida: {valor!r}"
        self.ultima_categoria = None if cat == "INDEFINIDA" else cat
        ok = self.enviar_esp32(f"CATEGORIA:{cat}")
        return ok, cat


# ---------------------------------------------------------------------------
# Servidor HTTP
# ---------------------------------------------------------------------------
def criar_handler(ponte: Ponte):
    class Handler(BaseHTTPRequestHandler):
        protocol_version = "HTTP/1.1"

        def log_message(self, *args):  # silencia o log padrão do http.server
            pass

        # --- utilidades ---
        def _cors(self) -> None:
            origem = self.headers.get("Origin", "")
            base = origem.rsplit(":", 1)[0] if origem.count(":") > 1 else origem
            if "*" in ORIGENS_PERMITIDAS:
                self.send_header("Access-Control-Allow-Origin", "*")
            elif origem and (origem in ORIGENS_PERMITIDAS or base in ORIGENS_PERMITIDAS):
                self.send_header("Access-Control-Allow-Origin", origem)
                self.send_header("Vary", "Origin")
            self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
            self.send_header("Access-Control-Allow-Headers", "Content-Type")
            # Chrome: permite página http://localhost chamar 127.0.0.1
            self.send_header("Access-Control-Allow-Private-Network", "true")

        def _json(self, status: int, dados: dict) -> None:
            corpo = json.dumps(dados, ensure_ascii=False).encode("utf-8")
            self.send_response(status)
            self._cors()
            self.send_header("Content-Type", "application/json; charset=utf-8")
            self.send_header("Content-Length", str(len(corpo)))
            self.end_headers()
            self.wfile.write(corpo)

        def _ler_json(self) -> dict:
            tamanho = int(self.headers.get("Content-Length") or 0)
            if tamanho <= 0 or tamanho > 10_000:
                return {}
            try:
                return json.loads(self.rfile.read(tamanho).decode("utf-8"))
            except (ValueError, UnicodeDecodeError):
                return {}

        # --- métodos ---
        def do_OPTIONS(self):
            self.send_response(204)
            self._cors()
            self.send_header("Content-Length", "0")
            self.end_headers()

        def do_GET(self):
            caminho = urlparse(self.path).path
            if caminho == "/status":
                self._json(200, {
                    "ok": True,
                    "esp32_conectado": ponte.esp32_conectado or ponte.porta is None,
                    "modo_sem_serial": ponte.porta is None,
                    "porta": ponte.porta,
                    "paginas_escutando": ponte.eventos.total,
                    "ultima_categoria": ponte.ultima_categoria,
                })
            elif caminho == "/eventos":
                self._sse()
            else:
                self._json(404, {"ok": False, "erro": "rota inexistente"})

        def do_POST(self):
            caminho = urlparse(self.path).path
            dados = self._ler_json()
            if caminho == "/categoria":
                ok, resultado = ponte.definir_categoria(dados.get("categoria"))
                if not ok and resultado.startswith("categoria inválida"):
                    self._json(400, {"ok": False, "erro": resultado})
                else:
                    self._json(200, {"ok": ok, "categoria": resultado,
                                     "esp32_conectado": ponte.esp32_conectado})
            elif caminho == "/reset":
                ponte.ultima_categoria = None
                self._json(200, {"ok": ponte.enviar_esp32("RESET")})
            elif caminho == "/escolha":
                self._json(200, {"ok": ponte.enviar_esp32("ESCOLHA")})
            else:
                self._json(404, {"ok": False, "erro": "rota inexistente"})

        def _sse(self):
            self.send_response(200)
            self._cors()
            self.send_header("Content-Type", "text/event-stream")
            self.send_header("Cache-Control", "no-cache")
            self.send_header("Connection", "keep-alive")
            self.end_headers()
            q = ponte.eventos.conectar()
            log(f"Página do totem conectada aos eventos (total: {ponte.eventos.total}).")
            try:
                self.wfile.write(b": conectado\n\n")
                self.wfile.flush()
                while ponte.rodando:
                    try:
                        tipo, dados = q.get(timeout=15)
                        payload = json.dumps(dados, ensure_ascii=False)
                        self.wfile.write(f"event: {tipo}\ndata: {payload}\n\n".encode("utf-8"))
                    except queue.Empty:
                        self.wfile.write(b": ping\n\n")  # mantém a conexão viva
                    self.wfile.flush()
            except (BrokenPipeError, ConnectionResetError, ConnectionAbortedError, OSError):
                pass
            finally:
                ponte.eventos.desconectar(q)
                log(f"Página do totem desconectada (total: {ponte.eventos.total}).")
                self.close_connection = True

    return Handler


# ---------------------------------------------------------------------------
def main() -> None:
    parser = argparse.ArgumentParser(description="Ponte ESP32 <-> Direito Expresso")
    parser.add_argument("--porta", default=PORTA_PADRAO, help="porta serial (padrão: COM4)")
    parser.add_argument("--sem-serial", action="store_true", help="roda sem a placa (teste)")
    parser.add_argument("--http-porta", type=int, default=HTTP_PORTA)
    parser.add_argument("--site", default=None,
                        help="endereço do totem.php (ex.: https://meusite.onrender.com/totem.php)")
    args = parser.parse_args()
    if args.site:
        configurar_site(args.site)
    log(f"Site do totem: {BASE_TOTEM}")

    ponte = Ponte(None if args.sem_serial else args.porta)

    servidor = ThreadingHTTPServer((HTTP_HOST, args.http_porta), criar_handler(ponte))
    servidor.daemon_threads = True
    threading.Thread(target=servidor.serve_forever, daemon=True).start()
    log(f"Servidor da ponte em http://{HTTP_HOST}:{args.http_porta}  (teste: /status)")

    threading.Thread(target=ponte.loop_serial, daemon=True).start()

    try:
        while True:
            time.sleep(1)
    except KeyboardInterrupt:
        log("Encerrando a ponte...")
        ponte.rodando = False
        servidor.shutdown()


if __name__ == "__main__":
    main()
