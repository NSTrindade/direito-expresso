<?php
/**
 * Integração do site com o totem físico (ESP32-2432S028R).
 *
 * O ESP32 fica ligado por USB ao computador. O programa ponte_esp32.py lê a
 * porta serial e abre um servidor local (http://127.0.0.1:8765). Esta classe
 * conversa com esse servidor para:
 *   - mandar a área sugerida pela IA (o ESP32 mostra o botão certo);
 *   - devolver o ESP32 à tela inicial quando o totem volta ao início.
 *
 * Tudo aqui é "melhor esforço": se a ponte não estiver rodando, o site
 * continua funcionando normalmente, só sem atualizar o ESP32.
 */
class Esp32Ponte
{
    /** Código do botão no ESP32 => nome da categoria no banco. */
    public const AREAS = [
        'CONSUMIDOR' => 'Consumidor',
        'CIVIL'      => 'Civil',
        'MORADIA'    => 'Moradia',
        'TRABALHO'   => 'Trabalho',
    ];

    private static function urlPonte(): string
    {
        return rtrim(getenv('PONTE_ESP32_URL') ?: 'http://127.0.0.1:8765', '/');
    }

    /** Converte um nome de categoria ("Direito do Consumidor", "Trabalhista"...) no código do botão. */
    public static function codigoPorNome(?string $nome): ?string
    {
        $t = self::normalizar((string) $nome);
        if ($t === '') {
            return null;
        }
        if (str_contains($t, 'consum')) {
            return 'CONSUMIDOR';
        }
        if (str_contains($t, 'trabalh')) {
            return 'TRABALHO';
        }
        if (str_contains($t, 'moradia') || str_contains($t, 'habitac') || str_contains($t, 'imovel') || str_contains($t, 'alug')) {
            return 'MORADIA';
        }
        if (str_contains($t, 'civil') || str_contains($t, 'civel')) {
            return 'CIVIL';
        }
        return null;
    }

    /**
     * Plano B quando a IA não responde: escolhe a área pelas palavras do relato.
     * Devolve null se não der para decidir com segurança.
     */
    public static function classificarPorPalavras(string $relato): ?string
    {
        $termos = [
            'CONSUMIDOR' => ['produto', 'compra', 'comprei', 'loja', 'defeito', 'garantia', 'troca', 'trocar',
                'reembolso', 'estorno', 'procon', 'nota fiscal', 'entrega', 'operadora', 'celular', 'internet',
                'cartao', 'fatura', 'cobranca', 'cobrando', 'tarifa', 'banco', 'plano de saude', 'pedido',
                'propaganda', 'cancelar', 'cancelamento', 'negativado', 'serasa', 'geladeira', 'mercado'],
            'CIVIL' => ['contrato', 'indenizacao', 'dano moral', 'danos morais', 'acidente', 'batida', 'heranca',
                'inventario', 'divorcio', 'pensao', 'guarda', 'casamento', 'uniao estavel', 'testamento',
                'emprestei', 'emprestimo', 'me deve', 'divida', 'calunia', 'difamacao', 'cartorio'],
            'MORADIA' => ['aluguel', 'alugo', 'alugado', 'inquilino', 'locador', 'locatario', 'proprietario',
                'despejo', 'despejar', 'condominio', 'imovel', 'apartamento', 'casa onde moro', 'moradia',
                'financiamento', 'usucapiao', 'posse', 'reintegracao', 'imobiliaria', 'iptu', 'vizinho',
                'goteira', 'infiltracao'],
            'TRABALHO' => ['trabalho', 'trabalhava', 'emprego', 'empregador', 'patrao', 'chefe',
                'demissao', 'demitido', 'demitida', 'salario', 'carteira assinada', 'ctps', 'fgts', 'ferias',
                'hora extra', 'horas extras', 'rescisao', 'aviso previo', 'justa causa', 'decimo terceiro',
                'clt', 'assedio', 'seguro-desemprego', 'seguro desemprego', 'sindicato'],
        ];

        $texto = ' ' . self::normalizar($relato) . ' ';
        $pontos = [];
        foreach ($termos as $codigo => $lista) {
            $pontos[$codigo] = 0;
            foreach (array_unique($lista) as $termo) {
                $pontos[$codigo] += preg_match_all('/\b' . preg_quote($termo, '/') . '\b/u', $texto);
            }
        }
        arsort($pontos);
        $codigos = array_keys($pontos);
        $melhor = $pontos[$codigos[0]];
        $segundo = $pontos[$codigos[1]];

        return ($melhor > 0 && $melhor > $segundo) ? $codigos[0] : null;
    }

    /** Manda a área ao ESP32. $codigo null = "IA não teve certeza" (ESP32 mostra as 4 áreas). */
    public static function enviarArea(?string $codigo): bool
    {
        return self::post('/categoria', ['categoria' => $codigo ?? 'INDEFINIDA']);
    }

    /** Volta o ESP32 para a tela inicial. */
    public static function resetar(): bool
    {
        return self::post('/reset', []);
    }

    private static function post(string $rota, array $dados): bool
    {
        if (!function_exists('curl_init')) {
            return false;
        }
        $ch = curl_init(self::urlPonte() . $rota);
        curl_setopt_array($ch, [
            CURLOPT_POST              => true,
            CURLOPT_POSTFIELDS        => json_encode($dados),
            CURLOPT_HTTPHEADER        => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER    => true,
            CURLOPT_CONNECTTIMEOUT_MS => 300,   // ponte desligada não pode travar o site
            CURLOPT_TIMEOUT_MS        => 1000,
        ]);
        $resposta = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $resposta !== false && $status === 200;
    }

    private static function normalizar(string $texto): string
    {
        $texto = function_exists('mb_strtolower') ? mb_strtolower($texto, 'UTF-8') : strtolower($texto);
        return strtr($texto, [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
        ]);
    }
}
