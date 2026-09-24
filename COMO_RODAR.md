# Como rodar o Direito Expresso em outra máquina

Este guia assume que você está começando do zero em um computador Windows sem nada
instalado. Se o XAMPP já estiver instalado, pule direto para o **Passo 2**.

Já testei o projeto inteiro (site, totem e painel administrativo) rodando de
verdade com XAMPP antes de te passar isso — os passos abaixo são exatamente
o que eu fiz.

---

## O que você vai precisar

- Um computador Windows (10 ou 11).
- [XAMPP](https://www.apachefriends.org/pt_br/download.html) com PHP 8.x — é ele quem fornece o Apache (servidor web), o MySQL/MariaDB (banco de dados) e o PHP.
- Um navegador (Chrome, Edge ou Firefox).
- (Opcional, só necessário para a IA responder de verdade) Uma chave de API do
  Google Gemini — gratuita, ver Passo 6.

---

## Passo 1 — Instalar o XAMPP

1. Baixe o instalador em <https://www.apachefriends.org/pt_br/download.html> (escolha a versão para Windows, PHP 8.1 ou superior).
2. Rode o instalador. Se o Windows Defender ou antivírus reclamar, pode permitir — é normal com o XAMPP.
3. Na tela de componentes, deixe pelo menos marcados: **Apache**, **MySQL**, **PHP**, **phpMyAdmin**.
4. Instale no caminho padrão (`C:\xampp`).
5. Ao final, abra o **XAMPP Control Panel** (ele mesmo se oferece pra abrir).

## Passo 2 — Copiar o projeto para dentro do XAMPP

1. Copie a pasta inteira deste projeto (a pasta que contém `app`, `public`, `database`, etc.) para dentro de:
   ```
   C:\xampp\htdocs\
   ```
2. Renomeie a pasta copiada para `direito-expresso`, de forma que o caminho final seja:
   ```
   C:\xampp\htdocs\direito-expresso\
   ```

## Passo 3 — Ligar o Apache e o MySQL

1. Abra o **XAMPP Control Panel** (se ainda não estiver aberto).
2. Clique em **Start** na linha do **Apache**.
3. Clique em **Start** na linha do **MySQL**.
4. As duas linhas devem ficar com fundo verde e escrito "Running".

> **Se der erro de porta ocupada:** feche qualquer Skype, outro servidor web,
> ou outro XAMPP/WAMP que já esteja rodando na máquina, e tente iniciar de novo.

## Passo 4 — Criar o banco de dados

1. No navegador, acesse: <http://localhost/phpmyadmin>
2. Clique na aba **SQL** no topo da página.
3. Abra o arquivo `database/schema.sql` deste projeto num editor de texto (Bloco de Notas mesmo), copie **todo** o conteúdo, cole na caixa de texto do phpMyAdmin e clique em **Executar** (ou "Go").
   - Isso cria o banco `totem_juridico` e as 9 tabelas.
4. Repita o processo com o arquivo `database/seed.sql` (ele já vem com 4 categorias, 4 termos jurídicos de exemplo, 4 órgãos e 1 totem cadastrado).

## Passo 5 — Criar o arquivo `.env`

1. Na pasta raiz do projeto (`C:\xampp\htdocs\direito-expresso`), copie o arquivo `.env.example` e cole no mesmo lugar como uma cópia.
2. Renomeie a cópia para `.env` (sem nada depois do nome — atenção, o Windows às vezes esconde a extensão `.txt`; certifique-se que o nome final é exatamente `.env`).
3. Abra o `.env` num editor de texto. Os valores padrão já funcionam com uma instalação nova do XAMPP:
   ```
   DB_HOST=127.0.0.1
   DB_NAME=totem_juridico
   DB_USER=root
   DB_PASS=
   ```
   (o XAMPP vem, por padrão, com o usuário `root` sem senha).

## Passo 6 — Trocar a senha do administrador (importante!)

O `seed.sql` cria um usuário administrador, mas com uma senha inválida de
propósito — é preciso gerar uma senha de verdade:

1. Abra o **Prompt de Comando** do Windows (não precisa ser como administrador).
2. Rode o comando abaixo, trocando `sua_senha_aqui` pela senha que você quer usar:
   ```
   C:\xampp\php\php.exe -r "echo password_hash('sua_senha_aqui', PASSWORD_DEFAULT);"
   ```
3. Isso vai imprimir um texto longo começando com `$2y$10$...` — copie ele inteiro.
4. Volte ao phpMyAdmin, clique no banco `totem_juridico` → tabela `usuario` → aba **SQL**, e rode:
   ```sql
   UPDATE Usuario SET senha = 'COLE_O_HASH_AQUI' WHERE email = 'admin@direitoexpresso.local';
   ```
5. Pronto — agora você loga no painel com o e-mail `admin@direitoexpresso.local` e a senha que você escolheu.

## Passo 7 — (Opcional) Ligar a IA de verdade

Sem isso, o site e o totem funcionam normalmente para qualquer termo já
cadastrado no banco — só o **chatbot do totem** (a parte que conversa livremente
com o cidadão) precisa da chave da API pra traduzir termos novos.

1. Acesse <https://aistudio.google.com/apikey> e faça login com uma conta Google.
2. Clique em **Create API key** e copie a chave gerada.
3. Cole no arquivo `.env`, na linha:
   ```
   GEMINI_API_KEY=cole_sua_chave_aqui
   ```
4. Salve o arquivo. Não precisa reiniciar nada — o PHP lê o `.env` a cada requisição.

Se você não configurar isso, o chatbot mostra uma mensagem amigável avisando
que a assistente está indisponível, em vez de quebrar a página — já testei
esse comportamento.

## Passo 8 — Abrir e usar

Com o Apache e o MySQL rodando (Passo 3), acesse no navegador:

| Versão | URL |
|---|---|
| Site (versão web padrão) | <http://localhost/direito-expresso/public/index.php> |
| Totem (Kiosk Mode) | <http://localhost/direito-expresso/public/totem.php> |
| Painel administrativo | <http://localhost/direito-expresso/public/admin.php> |

## Passo 9 — (Opcional) Configurar o modo quiosque de verdade no totem físico

Quando for instalar no hardware real do totem, configure o Chrome para abrir
direto em tela cheia, sem barra de endereço nem atalhos de teclado do Windows:

1. Crie um atalho para o Chrome.
2. Clique com o botão direito no atalho → Propriedades → no campo "Destino", adicione no final:
   ```
   --kiosk http://localhost/direito-expresso/public/totem.php
   ```
3. Configure esse atalho para abrir automaticamente quando o Windows liga (colocando na pasta de Inicialização do Windows).
4. Recomendo também desabilitar o teclado físico do equipamento (deixando só o touch) e trocar o papel de parede/tela de bloqueio do Windows por algo neutro, já que o cidadão não deve ver a área de trabalho em nenhum momento.

---

## Problemas comuns

**"Error establishing a database connection" ou tela em branco no navegador**
→ Confira se o `.env` existe (não só o `.env.example`) e se o MySQL está com a
bolinha verde no XAMPP Control Panel.

**phpMyAdmin ou o site dão erro "Host ... is not allowed to connect"**
→ Isso pode acontecer se as tabelas internas do MySQL (`mysql.user`) estiverem
corrompidas — foi algo que encontrei e corrigi na máquina onde testei. Se
acontecer: pare o MySQL, abra o Prompt de Comando como administrador, rode:
```
cd C:\xampp\mysql\data\mysql
C:\xampp\mysql\bin\aria_chk.exe -r global_priv
C:\xampp\mysql\bin\aria_chk.exe -r db
```
e inicie o MySQL de novo pelo XAMPP Control Panel.

**Acentos aparecem estranhos (ex: "Servi├ºos" em vez de "Serviços")**
→ Isso acontece se o `schema.sql`/`seed.sql` for importado por um cliente de
linha de comando sem forçar UTF-8. Pelo phpMyAdmin (Passo 4) isso não deveria
acontecer; se acontecer mesmo assim, apague o banco (`DROP DATABASE
totem_juridico;`) e reimporte os dois arquivos garantindo que o phpMyAdmin
esteja com a opção de charset em "utf8mb4" na tela de importação.

**Login do admin não funciona**
→ Confirme que você fez o Passo 6 (a senha do seed.sql sozinha não funciona,
de propósito).

---

## O que EU não fiz — e você/Nicolas precisam decidir ou completar

Sendo direto sobre os limites do que entreguei:

1. **Conteúdo jurídico real**: só cadastrei 4 termos de exemplo (vício oculto,
   justa causa, despejo, liminar) pra provar que o sistema funciona
   ponta a ponta. Antes de apresentar o TCC, cadastrem os termos, leis e
   órgãos reais pelo painel administrativo (`/admin.php?page=termos`).
2. **Chave real do Gemini**: eu não tenho como testar a IA respondendo de
   verdade (não configurei nenhuma chave). Testei e confirmo que o sistema
   se comporta corretamente tanto quando encontra o termo em cache quanto
   quando a chamada de IA falha (mostra aviso, não quebra) — mas o cenário
   "IA responde com sucesso" só vai ser validado quando vocês colocarem uma
   chave de verdade.
3. **E-mail do formulário de contato**: o formulário valida os campos e
   mostra "mensagem recebida", mas não envia e-mail de verdade — isso não
   fazia parte do MER/RF do pré-projeto. Se quiserem isso funcionando de
   verdade, dá pra integrar uma biblioteca como o PHPMailer depois.
4. **Divergência Figma × categorias de triagem**: como conversamos antes,
   troquei os 4 cards da home (que no Figma eram "Constituições/Códigos/Leis/
   Decretos") por categorias de problema (Consumidor/Trabalho/Moradia/Serviços
   Públicos), porque é isso que a triagem do pré-projeto describe. Se a banca
   perguntar, expliquem essa escolha — está documentada nos comentários do
   código também.
5. **Teste em touchscreen real**: testei tudo num navegador comum simulando
   toques com clique de mouse. Antes de apresentar, testem no hardware físico
   do totem (tamanho de fonte, distância dos botões, etc. podem precisar de
   ajuste fino dependendo do tamanho da tela real).
6. **Segurança para produção**: a senha do admin do seed.sql é um placeholder
   proposital (Passo 6). Troquem também o valor de `APP_ENV` para algo como
   `production` e desliguem a exibição de erros do PHP (`display_errors`) se
   um dia isso for exposto na internet de verdade, em vez de só na rede local
   da escola.
