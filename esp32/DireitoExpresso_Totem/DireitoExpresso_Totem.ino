/*
 * Direito Expresso — Totem Jurídico Inteligente
 * Firmware do ESP32-2432S028R (v2: comunicação nos dois sentidos)
 *
 * Telas (máquina de estados):
 *   TELA_INICIAL    -> botão "FALE COM A IA"
 *   TELA_AGUARDANDO -> "Descreva seu problema no computador" (espera a IA)
 *   TELA_SUGERIDA   -> botão da categoria sugerida pela IA + INICIO + OUTRAS AREAS
 *   TELA_ESCOLHA    -> as 4 categorias em grade (quando a IA não tem certeza
 *                      ou quando o cidadão toca em OUTRAS AREAS)
 *
 * Protocolo serial (115200 baud, uma mensagem por linha, terminada em '\n'):
 *   ESP32 -> PC
 *     PRONTO                       placa ligou / reiniciou
 *     ABRIR_IA                     tocou em "FALE COM A IA" (igual à v1)
 *     ABRIR_CATEGORIA:<CAT>        tocou no botão de uma categoria
 *     INICIO                       tocou em "INICIO"
 *     PONG                         resposta ao PING
 *     OK:<COMANDO> / ERRO:<motivo> confirmação dos comandos recebidos
 *   PC -> ESP32
 *     CATEGORIA:<CAT>              mostrar o botão da categoria sugerida
 *     CATEGORIA:INDEFINIDA         IA não teve certeza: mostrar as 4 áreas
 *     ESCOLHA                      mostrar as 4 áreas
 *     RESET                        voltar à tela inicial
 *     PING                         teste de comunicação
 *   <CAT> = CONSUMIDOR | CIVIL | MORADIA | TRABALHO
 *
 * Observação: as fontes do TFT_eSPI não têm acentos, por isso os textos
 * exibidos na tela estão sem acentuação.
 */

#include <SPI.h>
#include <TFT_eSPI.h>
#include <XPT2046_Touchscreen.h>
#include <Preferences.h>   // já vem com o pacote do ESP32: guarda a calibração na memória

// ---------------------------------------------------------------------------
// Pinos do touchscreen (iguais à documentação do projeto)
// ---------------------------------------------------------------------------
#define XPT2046_IRQ  36
#define XPT2046_MOSI 32
#define XPT2046_MISO 39
#define XPT2046_CLK  25
#define XPT2046_CS   33

// Barramento usado pelo toque. A tela usa o HSPI (USE_HSPI_PORT no User_Setup),
// então o toque fica no VSPI, como na configuração mais comum desta placa.
// Se o toque não responder de jeito nenhum, teste trocar para HSPI.
#define TOUCH_SPI_BUS VSPI

// true  = usa o pino IRQ (36) para saber quando há toque
// false = consulta o controlador o tempo todo (mais robusto; recomendado)
#define TOUCH_USAR_IRQ false

// ---------------------------------------------------------------------------
// Calibração do toque — AUTOMÁTICA
//  * Na primeira vez que a placa liga, aparece a tela de calibração (4 alvos).
//  * O resultado fica salvo na memória do ESP32 (não se perde ao desligar).
//  * Para calibrar de novo: mantenha o dedo/caneta na tela enquanto a placa
//    liga (ou aperte RST segurando a tela), ou envie CALIBRAR pela serial.
// Os valores abaixo são só o ponto de partida antes da primeira calibração.
// ---------------------------------------------------------------------------
#define TOUCH_X_MIN 200
#define TOUCH_X_MAX 3700
#define TOUCH_Y_MIN 240
#define TOUCH_Y_MAX 3800
#define TOUCH_ROTACAO 1      // mesma orientação horizontal da tela
#define DEBUG_TOQUE false    // true = imprime coordenadas (não deixe ligado com a ponte Python)
#define TOQUE_PRESSAO_MIN 300   // ignora toques muito leves (ruído)
#define ERRO_MAXIMO_PX 25       // tolerância do teste de conferência no centro

// ---------------------------------------------------------------------------
// Ajuste de cores da tela (alguns lotes desta placa mostram as cores trocadas)
//  * Verde aparecendo ROSA / fundo claro em vez de escuro -> INVERTER_CORES
//  * Vermelho e azul trocados (verde certo, mas azul virou marrom) -> TROCAR_VERMELHO_AZUL
// Troque entre true/false e grave de novo até as cores ficarem iguais às do site.
// ---------------------------------------------------------------------------
#define INVERTER_CORES true
#define TROCAR_VERMELHO_AZUL false

// ---------------------------------------------------------------------------
// Tempos
// ---------------------------------------------------------------------------
const unsigned long TEMPO_INATIVIDADE_MS = 90000;   // igual ao totem.js (90 s)
const unsigned long TEMPO_AGUARDANDO_MS  = 300000;  // 5 min para o cidadão conversar com a IA
const unsigned long TEMPO_FEEDBACK_MS    = 180;     // botão fica "aceso" ao toque
const unsigned long INTERVALO_TOQUE_MS   = 350;     // evita toque duplo

// ---------------------------------------------------------------------------
// Objetos de hardware
// ---------------------------------------------------------------------------
TFT_eSPI tft = TFT_eSPI();
SPIClass touchSPI(TOUCH_SPI_BUS);
XPT2046_Touchscreen ts(XPT2046_CS, TOUCH_USAR_IRQ ? XPT2046_IRQ : 255);  // 255 = sem IRQ

// ---------------------------------------------------------------------------
// Identidade visual: as mesmas cores do site (public/assets/css/base.css e totem.css)
// ---------------------------------------------------------------------------
uint16_t COR_FUNDO;         // #12203a  --cor-secundaria-escura (fundo do totem)
uint16_t COR_NAVY;          // #1b2a4a  --cor-secundaria (texto dos cartões)
uint16_t COR_VERDE;         // #1b8a4e  --cor-primaria (botão principal, "Expresso")
uint16_t COR_VERDE_ESCURO;  // #146b3c  --cor-primaria-escura (botão verde pressionado)
uint16_t COR_VERDE_CLARO;   // #e6f6ec  --cor-primaria-clara (cartão pressionado)
uint16_t COR_BRANCO;        // #ffffff  texto sobre o fundo escuro, cartões
uint16_t COR_SUAVE;         // textos de apoio sobre o fundo escuro
uint16_t COR_CONTORNO;      // contorno branco 60% dos botões secundários

// ---------------------------------------------------------------------------
// Categorias
// ---------------------------------------------------------------------------
enum Categoria { CAT_NENHUMA = -1, CAT_CONSUMIDOR = 0, CAT_CIVIL, CAT_MORADIA, CAT_TRABALHO, TOTAL_CATEGORIAS };

const char* CAT_CODIGO[TOTAL_CATEGORIAS] = { "CONSUMIDOR", "CIVIL", "MORADIA", "TRABALHO" };

// ---------------------------------------------------------------------------
// Botões
// IMPORTANTE: todos os enum/struct ficam ANTES da primeira função, porque o
// Arduino IDE gera as declarações das funções antes da primeira função do arquivo.
// ---------------------------------------------------------------------------
enum AcaoBotao { ACAO_NENHUMA, ACAO_ABRIR_IA, ACAO_CATEGORIA, ACAO_INICIO, ACAO_OUTRAS };

// Estilos iguais aos do site:
//  PRIMARIO  = .totem-botao-primario   (pílula verde, texto branco)
//  SECUNDARIO= .totem-botao-secundario (pílula com contorno branco, fundo escuro)
//  CARTAO    = .totem-cartao           (cartão branco, texto azul-marinho)
//  DESTAQUE  = cartão verde (área sugerida pela IA na grade)
enum EstiloBotao { ESTILO_PRIMARIO, ESTILO_SECUNDARIO, ESTILO_CARTAO, ESTILO_DESTAQUE };

struct Botao {
  int16_t x, y, w, h;
  const char* rotulo;
  EstiloBotao estilo;
  const GFXfont* fonte;
  AcaoBotao acao;
  Categoria categoria;
};

const int MAX_BOTOES = 6;
Botao botoes[MAX_BOTOES];
int totalBotoes = 0;

// ---------------------------------------------------------------------------
// Estados
// ---------------------------------------------------------------------------
enum Tela { TELA_INICIAL, TELA_AGUARDANDO, TELA_SUGERIDA, TELA_ESCOLHA };
Tela telaAtual = TELA_INICIAL;
Categoria categoriaSugerida = CAT_NENHUMA;
unsigned long ultimoEvento = 0;   // último toque ou comando recebido
unsigned long ultimoToque = 0;

String bufferSerial = "";

// ---------------------------------------------------------------------------
// Calibração (valores em uso)
// ---------------------------------------------------------------------------
struct PontoCru { long x; long y; };

Preferences memoria;
long calXMin = TOUCH_X_MIN, calXMax = TOUCH_X_MAX;
long calYMin = TOUCH_Y_MIN, calYMax = TOUCH_Y_MAX;
bool calTrocaXY = false;   // alguns lotes da placa têm os eixos do toque trocados

// ===========================================================================
// Funções (a partir daqui)
// ===========================================================================
// Converte uma cor do site (#rrggbb) para a tela, respeitando TROCAR_VERMELHO_AZUL
uint16_t cor(uint8_t r, uint8_t g, uint8_t b) {
  return TROCAR_VERMELHO_AZUL ? tft.color565(b, g, r) : tft.color565(r, g, b);
}

Categoria categoriaPorCodigo(const String& codigo) {
  for (int i = 0; i < TOTAL_CATEGORIAS; i++) {
    if (codigo == CAT_CODIGO[i]) return (Categoria)i;
  }
  return CAT_NENHUMA;
}

// ===========================================================================
// Desenho
// ===========================================================================
void limparBotoes() { totalBotoes = 0; }

void adicionarBotao(int16_t x, int16_t y, int16_t w, int16_t h, const char* rotulo,
                    EstiloBotao estilo, const GFXfont* fonte,
                    AcaoBotao acao, Categoria cat) {
  if (totalBotoes >= MAX_BOTOES) return;
  botoes[totalBotoes++] = { x, y, w, h, rotulo, estilo, fonte, acao, cat };
}

void desenharBotao(const Botao& b, bool pressionado) {
  uint16_t fundo, texto;
  int16_t raio;
  switch (b.estilo) {
    case ESTILO_PRIMARIO:
      fundo = pressionado ? COR_VERDE_ESCURO : COR_VERDE;  texto = COR_BRANCO; raio = b.h / 2; break;
    case ESTILO_SECUNDARIO:
      fundo = pressionado ? COR_NAVY : COR_FUNDO;          texto = COR_BRANCO; raio = b.h / 2; break;
    case ESTILO_DESTAQUE:
      fundo = pressionado ? COR_VERDE_ESCURO : COR_VERDE;  texto = COR_BRANCO; raio = 12;      break;
    case ESTILO_CARTAO:
    default:
      fundo = pressionado ? COR_VERDE_CLARO : COR_BRANCO;  texto = COR_NAVY;   raio = 12;      break;
  }
  tft.fillRoundRect(b.x, b.y, b.w, b.h, raio, fundo);
  if (b.estilo == ESTILO_SECUNDARIO) {   // contorno de 2 px, como o "border" do site
    tft.drawRoundRect(b.x, b.y, b.w, b.h, raio, COR_CONTORNO);
    tft.drawRoundRect(b.x + 1, b.y + 1, b.w - 2, b.h - 2, raio - 1, COR_CONTORNO);
  }
  tft.setFreeFont(b.fonte);
  tft.setTextColor(texto, fundo);
  tft.setTextDatum(MC_DATUM);
  tft.drawString(b.rotulo, b.x + b.w / 2, b.y + b.h / 2 + 1);
}

void desenharBotoes() {
  for (int i = 0; i < totalBotoes; i++) desenharBotao(botoes[i], false);
}

void desenharCabecalho() {
  tft.fillScreen(COR_FUNDO);
  // Logo igual ao do site: "Direito" em branco e "Expresso" em verde
  tft.setFreeFont(&FreeSansBold12pt7b);
  int16_t w1 = tft.textWidth("Direito ");
  int16_t w2 = tft.textWidth("Expresso");
  int16_t x0 = 160 - (w1 + w2) / 2;
  tft.setTextDatum(ML_DATUM);
  tft.setTextColor(COR_BRANCO, COR_FUNDO);
  tft.drawString("Direito ", x0, 22);
  tft.setTextColor(COR_VERDE, COR_FUNDO);
  tft.drawString("Expresso", x0 + w1, 22);
  tft.drawFastHLine(20, 42, 280, COR_NAVY);   // linha discreta separando o topo
}

void textoCentral(const char* texto, int16_t y, const GFXfont* fonte, uint16_t cor) {
  tft.setFreeFont(fonte);
  tft.setTextColor(cor, COR_FUNDO);
  tft.setTextDatum(MC_DATUM);
  tft.drawString(texto, 160, y);
}

void mostrarTelaInicial() {
  telaAtual = TELA_INICIAL;
  categoriaSugerida = CAT_NENHUMA;
  desenharCabecalho();
  textoCentral("Orientacao juridica inicial", 66, &FreeSans9pt7b, COR_SUAVE);
  limparBotoes();
  adicionarBotao(30, 88, 260, 82, "FALE COM A IA", ESTILO_PRIMARIO,
                 &FreeSansBold12pt7b, ACAO_ABRIR_IA, CAT_NENHUMA);
  desenharBotoes();
  textoCentral("Toque no botao para comecar", 200, &FreeSans9pt7b, COR_SUAVE);
}

void mostrarTelaAguardando() {
  telaAtual = TELA_AGUARDANDO;
  desenharCabecalho();
  textoCentral("Descreva seu problema", 76, &FreeSansBold12pt7b, COR_BRANCO);
  textoCentral("no computador.", 104, &FreeSansBold12pt7b, COR_BRANCO);
  textoCentral("A IA vai indicar a area certa", 140, &FreeSans9pt7b, COR_SUAVE);
  textoCentral("e o botao aparecera aqui.", 160, &FreeSans9pt7b, COR_SUAVE);
  limparBotoes();
  adicionarBotao(110, 190, 100, 38, "INICIO", ESTILO_SECUNDARIO,
                 &FreeSansBold9pt7b, ACAO_INICIO, CAT_NENHUMA);
  desenharBotoes();
}

void mostrarTelaSugerida(Categoria cat) {
  telaAtual = TELA_SUGERIDA;
  categoriaSugerida = cat;
  desenharCabecalho();
  textoCentral("Area sugerida pela IA:", 62, &FreeSans9pt7b, COR_SUAVE);
  limparBotoes();
  adicionarBotao(30, 78, 260, 76, CAT_CODIGO[cat], ESTILO_PRIMARIO,
                 &FreeSansBold12pt7b, ACAO_CATEGORIA, cat);
  adicionarBotao(12, 192, 112, 38, "INICIO", ESTILO_SECUNDARIO,
                 &FreeSansBold9pt7b, ACAO_INICIO, CAT_NENHUMA);
  adicionarBotao(134, 192, 174, 38, "OUTRAS AREAS", ESTILO_SECUNDARIO,
                 &FreeSansBold9pt7b, ACAO_OUTRAS, CAT_NENHUMA);
  desenharBotoes();
  textoCentral("Toque para ver as orientacoes", 170, &FreeSans9pt7b, COR_SUAVE);
}

void mostrarTelaEscolha(const char* titulo) {
  telaAtual = TELA_ESCOLHA;
  desenharCabecalho();
  textoCentral(titulo, 60, &FreeSans9pt7b, COR_SUAVE);
  limparBotoes();
  const int16_t xs[2] = { 15, 165 };
  const int16_t ys[2] = { 74, 132 };
  for (int i = 0; i < TOTAL_CATEGORIAS; i++) {
    Categoria c = (Categoria)i;
    bool destaque = (c == categoriaSugerida);
    adicionarBotao(xs[i % 2], ys[i / 2], 140, 52, CAT_CODIGO[i],
                   destaque ? ESTILO_DESTAQUE : ESTILO_CARTAO,
                   &FreeSansBold9pt7b, ACAO_CATEGORIA, c);
  }
  adicionarBotao(110, 194, 100, 38, "INICIO", ESTILO_SECUNDARIO,
                 &FreeSansBold9pt7b, ACAO_INICIO, CAT_NENHUMA);
  desenharBotoes();
}

// ===========================================================================
// Comunicação serial
// ===========================================================================
void enviar(const String& msg) {
  Serial.println(msg);
}

void processarComando(String cmd) {
  cmd.trim();
  cmd.toUpperCase();
  if (cmd.length() == 0) return;
  ultimoEvento = millis();

  if (cmd == "PING") {
    enviar("PONG");
  } else if (cmd == "CALIBRAR") {
    enviar("OK:CALIBRAR");
    executarCalibracao();
    mostrarTelaInicial();
  } else if (cmd == "RESET") {
    mostrarTelaInicial();
    enviar("OK:RESET");
  } else if (cmd == "ESCOLHA") {
    mostrarTelaEscolha("Escolha a area do seu problema:");
    enviar("OK:ESCOLHA");
  } else if (cmd.startsWith("CATEGORIA:")) {
    String codigo = cmd.substring(10);
    if (codigo == "INDEFINIDA") {
      categoriaSugerida = CAT_NENHUMA;
      mostrarTelaEscolha("A IA nao teve certeza. Escolha:");
      enviar("OK:CATEGORIA:INDEFINIDA");
      return;
    }
    Categoria c = categoriaPorCodigo(codigo);
    if (c == CAT_NENHUMA) {
      enviar("ERRO:CATEGORIA_DESCONHECIDA:" + codigo);
      return;
    }
    mostrarTelaSugerida(c);
    enviar("OK:CATEGORIA:" + codigo);
  } else {
    enviar("ERRO:COMANDO_DESCONHECIDO:" + cmd);
  }
}

void lerSerial() {
  while (Serial.available()) {
    char ch = (char)Serial.read();
    if (ch == '\n') {
      processarComando(bufferSerial);
      bufferSerial = "";
    } else if (ch != '\r') {
      if (bufferSerial.length() < 64) bufferSerial += ch;  // descarta lixo longo
    }
  }
}

// ===========================================================================
// Toque
// ===========================================================================
// Converte uma leitura crua do XPT2046 em pixel da tela (320 x 240)
void converterParaTela(long rx, long ry, int16_t& x, int16_t& y) {
  if (calTrocaXY) { long t = rx; rx = ry; ry = t; }
  x = (int16_t)constrain(map(rx, calXMin, calXMax, 0, 320), 0, 319);
  y = (int16_t)constrain(map(ry, calYMin, calYMax, 0, 240), 0, 239);
}

bool lerToque(int16_t& x, int16_t& y) {
  if (!ts.touched()) return false;
  TS_Point p = ts.getPoint();
  if (p.z < TOQUE_PRESSAO_MIN) return false;
  converterParaTela(p.x, p.y, x, y);
  if (DEBUG_TOQUE) {
    Serial.printf("DEBUG:TOQUE raw=(%d,%d,%d) tela=(%d,%d)\n", p.x, p.y, p.z, x, y);
  }
  return true;
}

// ===========================================================================
// Calibração do touchscreen
// ===========================================================================
bool carregarCalibracao() {
  memoria.begin("toque", true);
  bool valida = memoria.getBool("valida", false);
  if (valida) {
    calXMin = memoria.getLong("xmin", TOUCH_X_MIN);
    calXMax = memoria.getLong("xmax", TOUCH_X_MAX);
    calYMin = memoria.getLong("ymin", TOUCH_Y_MIN);
    calYMax = memoria.getLong("ymax", TOUCH_Y_MAX);
    calTrocaXY = memoria.getBool("troca", false);
  }
  memoria.end();
  return valida;
}

void salvarCalibracao() {
  memoria.begin("toque", false);
  memoria.putLong("xmin", calXMin);
  memoria.putLong("xmax", calXMax);
  memoria.putLong("ymin", calYMin);
  memoria.putLong("ymax", calYMax);
  memoria.putBool("troca", calTrocaXY);
  memoria.putBool("valida", true);
  memoria.end();
}

void esperarSoltarSimples() {
  unsigned long inicio = millis();
  while (ts.touched() && millis() - inicio < 3000) delay(20);  // não trava se a leitura "grudar"
  delay(150);
}

// Mostra no rodapé da calibração o que o controlador de toque está lendo.
// Sem tocar: z perto de 0. Tocando: z sobe (centenas/milhares) e x/y mudam.
void mostrarLeituraAoVivo() {
  static unsigned long ultimo = 0;
  if (millis() - ultimo < 150) return;
  ultimo = millis();
  TS_Point p = ts.getPoint();
  bool tocando = ts.touched();
  char linha[48];
  snprintf(linha, sizeof(linha), "x=%d y=%d z=%d %s", p.x, p.y, p.z, tocando ? "TOCANDO" : "-");
  tft.fillRect(40, 170, 240, 22, COR_FUNDO);   // entre o texto e os alvos de baixo
  tft.setTextFont(2);                 // fonte simples, cabe no rodapé
  tft.setTextColor(tocando ? COR_VERDE : COR_SUAVE, COR_FUNDO);
  tft.setTextDatum(MC_DATUM);
  tft.drawString(linha, 160, 181);
  static unsigned long ultimoSerial = 0;
  if (millis() - ultimoSerial > 500) {
    ultimoSerial = millis();
    Serial.printf("DEBUG:CALIB %s\n", linha);
  }
}

void desenharAlvo(int16_t x, int16_t y, uint16_t cor) {
  tft.drawCircle(x, y, 10, cor);
  tft.drawCircle(x, y, 4, cor);
  tft.drawFastHLine(x - 16, y, 33, cor);
  tft.drawFastVLine(x, y - 16, 33, cor);
}

void telaCalibracao(const char* linha1, const char* linha2) {
  tft.fillScreen(COR_FUNDO);
  tft.fillRect(0, 0, 320, 4, COR_VERDE);
  textoCentral(linha1, 100, &FreeSansBold9pt7b, COR_BRANCO);
  textoCentral(linha2, 126, &FreeSans9pt7b, COR_SUAVE);
}

// Espera um toque firme e devolve a média das leituras cruas
PontoCru coletarToqueCru() {
  while (true) {
    esperarSoltarSimples();
    while (!ts.touched()) { mostrarLeituraAoVivo(); delay(10); }
    long sx = 0, sy = 0;
    int n = 0;
    unsigned long inicio = millis();
    while (ts.touched() && n < 40 && millis() - inicio < 2000) {
      TS_Point p = ts.getPoint();
      if (p.z >= TOQUE_PRESSAO_MIN) { sx += p.x; sy += p.y; n++; }
      delay(8);
    }
    esperarSoltarSimples();
    if (n >= 5) return { sx / n, sy / n };
    // toque rápido/leve demais: tenta de novo
  }
}

// Calibra com 4 alvos nos cantos e confere com um toque no centro
void executarCalibracao() {
  const int16_t M = 20;   // distância dos alvos até a borda
  const int16_t alvoX[4] = { M, 320 - M, M, 320 - M };
  const int16_t alvoY[4] = { M, M, 240 - M, 240 - M };
  const char* nomes[4] = { "(1/4) canto superior esquerdo", "(2/4) canto superior direito",
                           "(3/4) canto inferior esquerdo", "(4/4) canto inferior direito" };
  uint16_t COR_ALVO = cor(200, 30, 30);

  while (true) {
    PontoCru p[4];
    for (int i = 0; i < 4; i++) {
      telaCalibracao("CALIBRACAO DO TOQUE", "Toque no centro do alvo vermelho");
      textoCentral(nomes[i], 150, &FreeSans9pt7b, COR_SUAVE);
      desenharAlvo(alvoX[i], alvoY[i], COR_ALVO);
      p[i] = coletarToqueCru();
      desenharAlvo(alvoX[i], alvoY[i], COR_VERDE);
      delay(200);
    }

    // Eixos trocados? (ao ir da esquerda para a direita, quem muda mais: x ou y cru?)
    long mudaX = labs((p[1].x + p[3].x) - (p[0].x + p[2].x));
    long mudaY = labs((p[1].y + p[3].y) - (p[0].y + p[2].y));
    calTrocaXY = mudaY > mudaX;
    if (calTrocaXY) {
      for (int i = 0; i < 4; i++) { long t = p[i].x; p[i].x = p[i].y; p[i].y = t; }
    }

    // Médias das leituras nas colunas/linhas dos alvos
    long rxEsq = (p[0].x + p[2].x) / 2, rxDir = (p[1].x + p[3].x) / 2;
    long ryCim = (p[0].y + p[1].y) / 2, ryBai = (p[2].y + p[3].y) / 2;

    bool coerente = labs(rxDir - rxEsq) > 800 && labs(ryBai - ryCim) > 600;
    if (coerente) {
      // Extrapola dos alvos (a 20 px da borda) para as bordas da tela
      calXMin = rxEsq - (rxDir - rxEsq) * M / (320 - 2 * M);
      calXMax = rxDir + (rxDir - rxEsq) * M / (320 - 2 * M);
      calYMin = ryCim - (ryBai - ryCim) * M / (240 - 2 * M);
      calYMax = ryBai + (ryBai - ryCim) * M / (240 - 2 * M);

      // Conferência: toque no centro
      tft.fillScreen(COR_FUNDO);
      tft.fillRect(0, 0, 320, 4, COR_VERDE);
      textoCentral("CONFERENCIA", 36, &FreeSansBold9pt7b, COR_BRANCO);
      textoCentral("Toque no centro do alvo", 62, &FreeSans9pt7b, COR_SUAVE);
      desenharAlvo(160, 140, COR_ALVO);
      PontoCru c = coletarToqueCru();
      int16_t tx, ty;
      // converterParaTela troca os eixos sozinho se calTrocaXY = true
      converterParaTela(c.x, c.y, tx, ty);
      long erro = labs(tx - 160) + labs(ty - 140);
      tft.fillCircle(tx, ty, 4, COR_VERDE);   // mostra onde o toque caiu
      delay(400);

      if (erro <= ERRO_MAXIMO_PX * 2) {
        salvarCalibracao();
        Serial.printf("CALIB:xmin=%ld,xmax=%ld,ymin=%ld,ymax=%ld,troca=%d\n",
                      calXMin, calXMax, calYMin, calYMax, calTrocaXY ? 1 : 0);
        telaCalibracao("CALIBRACAO SALVA!", "O toque esta pronto para uso.");
        delay(1500);
        return;
      }
    }

    telaCalibracao("Calibracao imprecisa", "Vamos repetir. Toque com calma.");
    delay(2000);
  }
}

// Mostra a abertura; se a tela estiver sendo tocada, entra na calibração
bool pedidoDeCalibracaoNoBoot() {
  desenharCabecalho();
  textoCentral("Iniciando...", 110, &FreeSans9pt7b, COR_SUAVE);
  textoCentral("(segure a tela para calibrar)", 140, &FreeSans9pt7b, COR_SUAVE);
  unsigned long inicio = millis();
  unsigned long tocando = 0;
  while (millis() - inicio < 1500) {
    if (ts.touched()) {
      tocando += 20;
      if (tocando >= 600) return true;   // segurou por ~0,6 s
    }
    delay(20);
  }
  return false;
}

void esperarSoltar() {
  unsigned long inicio = millis();
  while (ts.touched() && millis() - inicio < 1500) {
    lerSerial();   // não perde comandos enquanto o dedo está na tela
    delay(10);
  }
}

void executarAcao(const Botao& b) {
  switch (b.acao) {
    case ACAO_ABRIR_IA:
      enviar("ABRIR_IA");
      mostrarTelaAguardando();
      break;
    case ACAO_CATEGORIA:
      enviar(String("ABRIR_CATEGORIA:") + CAT_CODIGO[b.categoria]);
      // a tela continua mostrando os botões: o cidadão pode trocar de área
      break;
    case ACAO_INICIO:
      enviar("INICIO");
      mostrarTelaInicial();
      break;
    case ACAO_OUTRAS:
      mostrarTelaEscolha("Escolha a area do seu problema:");
      break;
    default:
      break;
  }
}

void tratarToque() {
  int16_t x, y;
  if (!lerToque(x, y)) return;
  if (millis() - ultimoToque < INTERVALO_TOQUE_MS) return;
  ultimoToque = millis();
  ultimoEvento = millis();

  for (int i = 0; i < totalBotoes; i++) {
    Botao b = botoes[i];  // cópia: executarAcao pode redesenhar e trocar a lista
    if (x >= b.x && x <= b.x + b.w && y >= b.y && y <= b.y + b.h) {
      desenharBotao(b, true);
      delay(TEMPO_FEEDBACK_MS);
      desenharBotao(b, false);
      esperarSoltar();
      executarAcao(b);
      return;
    }
  }
  esperarSoltar();
}

// ===========================================================================
// Inatividade
// ===========================================================================
void verificarInatividade() {
  if (telaAtual == TELA_INICIAL) return;
  unsigned long limite = (telaAtual == TELA_AGUARDANDO) ? TEMPO_AGUARDANDO_MS : TEMPO_INATIVIDADE_MS;
  if (millis() - ultimoEvento > limite) {
    mostrarTelaInicial();
    enviar("INICIO");   // a ponte leva o navegador de volta à tela inicial
  }
}

// ===========================================================================
void setup() {
  Serial.begin(115200);

  touchSPI.begin(XPT2046_CLK, XPT2046_MISO, XPT2046_MOSI, XPT2046_CS);
  ts.begin(touchSPI);
  ts.setRotation(TOUCH_ROTACAO);

  tft.init();
  tft.setRotation(1);  // horizontal: 320 x 240
  tft.invertDisplay(INVERTER_CORES);

  // Paleta do site Direito Expresso
  COR_FUNDO        = cor(0x12, 0x20, 0x3a);  // #12203a
  COR_NAVY         = cor(0x1b, 0x2a, 0x4a);  // #1b2a4a
  COR_VERDE        = cor(0x1b, 0x8a, 0x4e);  // #1b8a4e
  COR_VERDE_ESCURO = cor(0x14, 0x6b, 0x3c);  // #146b3c
  COR_VERDE_CLARO  = cor(0xe6, 0xf6, 0xec);  // #e6f6ec
  COR_BRANCO       = TFT_WHITE;
  COR_SUAVE        = cor(0xc3, 0xc9, 0xd4);  // branco suavizado sobre o fundo
  COR_CONTORNO     = cor(0xa0, 0xa6, 0xb0);  // branco 60% sobre o fundo

  bool temCalibracao = carregarCalibracao();
  if (!temCalibracao || pedidoDeCalibracaoNoBoot()) {
    executarCalibracao();
  }

  mostrarTelaInicial();
  ultimoEvento = millis();
  enviar("PRONTO");
}

void loop() {
  lerSerial();
  tratarToque();
  verificarInatividade();
  delay(5);
}
