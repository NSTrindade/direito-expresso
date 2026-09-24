<?php
require_once __DIR__ . '/../Models/Categoria.php';
require_once __DIR__ . '/../Models/TermoJuridico.php';

/** Páginas institucionais da versão Web (Início, Sobre, Contato). */
class HomeController
{
    public static function index(): void
    {
        renderView('web/home', [
            'categorias' => Categoria::all(),
        ]);
    }

    public static function sobre(): void
    {
        renderView('web/sobre', []);
    }

    public static function contato(): void
    {
        renderView('web/contato', [
            'enviado' => false,
        ]);
    }

    public static function processarContato(): void
    {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $assunto = trim($_POST['assunto'] ?? '');
        $mensagem = trim($_POST['mensagem'] ?? '');

        // Não há envio real de e-mail configurado (fora do escopo do MER
        // deste TCC) — a mensagem só é validada e confirmada na tela.
        // Para produção, plugar aqui um serviço de e-mail (PHPMailer, etc.)
        $valido = $nome !== '' && $email !== '' && $mensagem !== '';

        renderView('web/contato', [
            'enviado' => $valido,
            'erro' => $valido ? null : 'Preencha nome, e-mail e mensagem.',
            'nome' => $nome,
            'email' => $email,
            'assunto' => $assunto,
            'mensagem' => $mensagem,
        ]);
    }
}
