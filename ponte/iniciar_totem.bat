@echo off
REM ==========================================================
REM  Direito Expresso - inicia a ponte do ESP32 e abre o totem
REM  Uso: dois cliques. Ajuste as duas linhas "set" abaixo.
REM ==========================================================

REM Porta do ESP32 (Gerenciador de Dispositivos > Portas COM e LPT)
set PORTA=COM4

REM Endereco da pagina totem.php
REM   Site no Render:  set SITE=https://SEU-SITE.onrender.com/totem.php
REM   Site no XAMPP:   set SITE=http://localhost/direito-expresso/public/totem.php
set SITE=https://direito-expresso.onrender.com/totem.php

cd /d "%~dp0"
start "Ponte ESP32 (nao feche esta janela)" cmd /k python ponte_esp32.py --porta %PORTA% --site %SITE%

REM espera a ponte subir antes de abrir o navegador
timeout /t 3 /nobreak >nul
start "" "%SITE%?page=inicio"
