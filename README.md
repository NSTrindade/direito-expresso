# Direito Expresso

**Totem jurídico inteligente que traduz o "juridiquês" para linguagem simples e indica qual órgão público procurar.**

TCC do curso Técnico em Desenvolvimento de Sistemas da **ETEC Zona Leste**, orientado pelo **Prof. Marcelo Collado**.

🌐 **Site no ar:** https://direito-expresso.onrender.com
🖥️ **Totem:** https://direito-expresso.onrender.com/totem.php

---

## O problema

Pesquisas citadas no projeto mostram que **86%** das pessoas consideram o Judiciário distante, **40,9%** desistem por achar tudo burocrático e **27,3%** não sabem a qual órgão recorrer. O Direito Expresso é o primeiro passo: entender o problema e saber para onde ir.

> O Direito Expresso **não substitui um advogado** e não emite parecer jurídico. Ele orienta o cidadão em linguagem simples e indica o órgão público adequado.

## O que o sistema faz

- **Totem físico (ESP32 + tela touch):** o cidadão toca em **FALE COM A IA**, descreve o problema no computador e a placa mostra o botão da área certa (Consumidor, Civil, Moradia ou Trabalho). Ao tocar no botão, o computador abre as orientações daquela área.
- **Assistente com IA (Google Gemini):** explica o problema em palavras simples, sugere o próximo passo, indica o órgão competente e a **base legal**, seguindo o Pacto Nacional pela Linguagem Simples (CNJ).
- **Base de conhecimento:** cada pergunta respondida pela IA vira um termo salvo no banco, com órgão e leis, e fica disponível na busca do site.
- **Site público:** consulta de termos e leis por palavra ou categoria, além das páginas Sobre e Contato.
- **Painel administrativo:** cadastro de categorias, termos, leis, órgãos e usuários.

## Como funciona

```
 Cidadão toca no ESP32 ──USB/serial──► ponte_esp32.py ──► navegador abre o chat (mesma aba)
                                            ▲
 Cidadão escreve no chat ──► site (PHP) ──► Gemini identifica a área e a base legal
                                            │
 Navegador avisa a ponte ───────────────────┘──► ESP32 mostra o botão da área
 Cidadão toca no botão ──► ponte ──► navegador abre a página da área
```

- **Plano B sem IA:** se o Gemini estiver fora do ar ou lotado, o site tenta outros modelos e, em último caso, identifica a área pelas palavras do relato. O botão certo aparece no ESP32 mesmo assim.
- Se a frase digitada for exatamente um termo já cadastrado (ex.: `Despejo`), a resposta vem direto do banco, sem chamar a IA.

## Tecnologias

| Parte | Tecnologia |
|---|---|
| Site, totem e painel | PHP 8.3 (MVC próprio), HTML, CSS e JavaScript |
| Banco de dados | MySQL 8 (Aiven na nuvem / MariaDB no XAMPP) |
| Inteligência artificial | Google Gemini API (`gemini-3.6-flash` + modelos reserva) |
| Hospedagem | Render (Docker, `php:8.3-apache`) |
| Totem físico | ESP32-2432S028R ("Cheap Yellow Display"), tela ILI9341 + touch XPT2046 |
| Ponte totem ↔ site | Python 3 + PySerial (servidor local em `127.0.0.1:8765`) |

## Estrutura do repositório

```
app/
  Config/config.php        configurações (lidas das variáveis de ambiente)
  Controllers/             Triagem (site e totem), Admin, Auth, Home
  Models/                  Categoria, TermoJuridico, Lei, OrgaoCompetente, Usuario...
  Services/
    GeminiService.php      chamada ao Gemini, fila de modelos reserva, base legal
    Esp32Ponte.php         envio da área sugerida ao totem físico
  Views/                   telas do site (web), do totem e do painel (admin)
public/
  index.php                site público
  totem.php                interface do totem (kiosk)
  admin.php                painel administrativo
  assets/                  CSS e JS (totem-esp32.js liga a página à ponte)
database/
  schema.sql               criação das tabelas
  seed.sql                 dados iniciais (rodar UMA vez só)
  migracao_*.sql           ajustes posteriores (categoria Civil, base legal, duplicados)
esp32/DireitoExpresso_Totem/
  DireitoExpresso_Totem.ino   firmware do totem
ponte/
  ponte_esp32.py           ponte serial ↔ site
  iniciar_totem.bat        atalho para ligar o totem no Windows
Dockerfile, docker-entrypoint.sh   publicação no Render
```

## Rodando localmente (XAMPP)

1. Instale o **XAMPP** (PHP 8.1+) e copie o projeto para `C:\xampp\htdocs\direito-expresso`.
2. No XAMPP Control Panel, dê **Start** no Apache e no MySQL.
3. No phpMyAdmin (`http://localhost/phpmyadmin`), crie o banco `totem_juridico`, selecione-o e rode, nesta ordem: `database/schema.sql`, `database/seed.sql`, `database/migracao_esp32_civil.sql` e `database/migracao_base_legal.sql`.
4. Copie `.env.example` para `.env` e preencha os valores (veja a tabela abaixo).
5. Acesse:
   - Site: `http://localhost/direito-expresso/public/`
   - Totem: `http://localhost/direito-expresso/public/totem.php`
   - Painel: `http://localhost/direito-expresso/public/admin.php`

O passo a passo detalhado, incluindo a criação da senha do administrador, está em [`COMO_RODAR.md`](COMO_RODAR.md).

## Variáveis de ambiente

No XAMPP elas ficam no arquivo `.env`. No Render, ficam em **Environment**. **Nunca envie o `.env` para o GitHub**: ele já está no `.gitignore`.

| Variável | Para que serve | Exemplo |
|---|---|---|
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | conexão com o MySQL | `127.0.0.1`, `3306`, `totem_juridico`, `root`, *(vazio)* |
| `DB_SSL`, `DB_SSL_CA` | conexão segura com o banco na nuvem (Aiven) | `true`, caminho do certificado |
| `GEMINI_API_KEY` | chave da API do Gemini ([AI Studio](https://aistudio.google.com/apikey)) | `AQ....` |
| `GEMINI_MODEL` | modelo principal | `gemini-3.6-flash` |
| `GEMINI_MODELOS_RESERVA` | *(opcional)* modelos usados se o principal estiver lotado | `gemini-3.5-flash-lite,gemini-3.5-flash` |
| `APP_ENV` | `production` esconde detalhes técnicos de erro | `production` |
| `PONTE_ESP32_URL` | *(opcional)* endereço da ponte do totem | `http://127.0.0.1:8765` |

## Publicação (Render)

O Render publica automaticamente a cada `git push` na branch `main`, usando o `Dockerfile`. O banco de produção fica na Aiven. Mudanças de estrutura ou dados do banco são feitas por SQL, pelo DBeaver: o `git push` não altera o banco.

> O plano gratuito do Render "dorme" sem uso. Abra o site cerca de 2 minutos antes de uma apresentação.

## Totem físico (ESP32)

**Gravar a placa (Arduino IDE):**
1. Placa **ESP32 Dev Module** e bibliotecas **TFT_eSPI** (com `User_Setup.h` configurado para `ILI9341_2_DRIVER` e `USE_HSPI_PORT`) e **XPT2046_Touchscreen**.
2. Abra `esp32/DireitoExpresso_Totem/DireitoExpresso_Totem.ino` e clique em **Carregar**.
3. Na primeira vez, a placa pede a **calibração do toque** (4 alvos nos cantos + conferência no centro). O resultado fica salvo na memória. Para recalibrar, segure a tela enquanto a placa liga.
4. Se as cores aparecerem trocadas (verde virando rosa), ajuste `INVERTER_CORES` ou `TROCAR_VERMELHO_AZUL` no início do arquivo.

**Ligar o totem no computador:**
1. Instale o **Python 3** (marcando *Add python.exe to PATH*) e rode `pip install pyserial`.
2. Conecte o ESP32 no USB e veja a porta em *Gerenciador de Dispositivos → Portas (COM e LPT)*.
3. No `ponte/iniciar_totem.bat`, ajuste `set PORTA=` e `set SITE=` (endereço do `totem.php`).
4. Feche abas antigas do totem e dê dois cliques no `iniciar_totem.bat`.
5. Use **Chrome ou Edge**. Na primeira vez, clique em **Permitir** quando o navegador pedir acesso à rede local. O Brave bloqueia essa conexão por padrão.

**Protocolo serial** (115200 baud, uma mensagem por linha):

| Sentido | Mensagem | Significado |
|---|---|---|
| ESP32 → PC | `ABRIR_IA` | tocou em FALE COM A IA |
| ESP32 → PC | `ABRIR_CATEGORIA:<AREA>` | tocou no botão de uma área |
| ESP32 → PC | `INICIO` | tocou em INICIO ou ficou parado 90 s |
| PC → ESP32 | `CATEGORIA:<AREA>` | mostrar o botão da área sugerida |
| PC → ESP32 | `CATEGORIA:INDEFINIDA` | IA sem certeza: mostrar as 4 áreas |
| PC → ESP32 | `RESET` / `PING` | voltar ao início / teste (`PONG`) |

`<AREA>` = `CONSUMIDOR`, `CIVIL`, `MORADIA` ou `TRABALHO`.

## Equipe

| Integrante | Função |
|---|---|
| Guilherme Queiroz Penarrubia | Desenvolvimento |
| Kelvin Silva Sena | Prototipação |
| Nicolas Silva Trindade | Desenvolvimento |
| Pedro Henrique de Paula | Documentação |

**Orientador:** Prof. Marcelo Collado. **Contato:** nico.trindade2@gmail.com

---

*Projeto acadêmico. As orientações do sistema são informativas e não substituem a consulta a um advogado ou à Defensoria Pública.*
