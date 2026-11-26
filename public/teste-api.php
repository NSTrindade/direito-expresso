<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Carrega o ficheiro do serviço diretamente
require_once __DIR__ . '/../app/Services/GeminiService.php';

echo "<h2>Verificação de Status - API Gemini</h2>";

try {
    // Instancia a classe
    $gemini = new GeminiService();
    
    // Chama a função correta ('traduzir') com uma frase de teste
    $resposta = $gemini->traduzir("Isto é apenas um teste de conexão. Confirme que está online de forma curta.");
    
    echo "<div style='padding: 15px; background: #e6ffe6; color: green; border: 1px solid green; border-radius: 5px;'>";
    echo "<strong>✅ Sucesso!</strong> A API está conectada e a funcionar perfeitamente.<br><br>";
    echo "<strong>Resposta Estruturada do Gemini:</strong><br>";
    
    // Como a função traduzir devolve um array, usamos print_r dentro da tag <pre> para ficar bonito na tela
    echo "<pre style='background: #fff; padding: 10px; border: 1px solid #ccc;'>" . print_r($resposta, true) . "</pre>";
    echo "</div>";

} catch (\Exception $e) {
    echo "<div style='padding: 15px; background: #ffe6e6; color: red; border: 1px solid red; border-radius: 5px;'>";
    echo "<strong>❌ Erro de Conexão:</strong><br><br>";
    echo $e->getMessage();
    echo "</div>";
} catch (\Error $e) {
    echo "<div style='padding: 15px; background: #ffcccc; color: #990000; border: 1px solid #990000; border-radius: 5px;'>";
    echo "<strong>❌ Erro de Código:</strong><br><br>";
    echo $e->getMessage();
    echo "</div>";
}
?>