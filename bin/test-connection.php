<?php
/**
 * Teste rápido de sanidade: confirma que o .env está configurado e que
 * os Models conseguem ler o banco. Rode antes de avançar para
 * Controllers/Views, direto pela linha de comando:
 *
 *   cd bin
 *   "C:\xampp\php\php.exe" test-connection.php
 *
 * (ou o caminho do php.exe da sua instalação do XAMPP)
 */

require_once __DIR__ . '/../app/Models/Database.php';
require_once __DIR__ . '/../app/Models/Categoria.php';
require_once __DIR__ . '/../app/Models/TermoJuridico.php';
require_once __DIR__ . '/../app/Models/OrgaoCompetente.php';

try {
    Database::getConnection();
    echo "[OK] Conectado ao banco totem_juridico.\n\n";

    $categorias = Categoria::all();
    echo '[OK] ' . count($categorias) . " categorias encontradas:\n";
    foreach ($categorias as $categoria) {
        echo "  - {$categoria['nome']}\n";
    }

    echo "\n";
    $termos = TermoJuridico::all();
    echo '[OK] ' . count($termos) . " termos jurídicos cadastrados:\n";
    foreach ($termos as $termo) {
        echo "  - {$termo['termoTecnico']} ({$termo['categoriaNome']})\n";
    }

    echo "\n";
    $orgaos = OrgaoCompetente::all();
    echo '[OK] ' . count($orgaos) . " órgãos competentes cadastrados:\n";
    foreach ($orgaos as $orgao) {
        echo "  - {$orgao['nome']}\n";
    }

    echo "\nTudo certo! Pode seguir para os Controllers e as Views.\n";
} catch (Throwable $e) {
    echo '[ERRO] ' . $e->getMessage() . "\n";
    echo "Confira se: 1) o arquivo .env existe na raiz do projeto (copiou do .env.example?), \n";
    echo "2) o MySQL do XAMPP está rodando, 3) schema.sql e seed.sql já foram executados.\n";
    exit(1);
}
