<?php

ini_set('max_execution_time', 2000);
set_time_limit(2000);

date_default_timezone_set("America/Sao_Paulo");

/**
 * COMPATIBILIDADE COM SERVIDORES SEM EXTENSÃO MBSTRING
 *
 * Alguns servidores PHP podem estar sem mbstring ativa.
 * Estas funções simples evitam erro fatal em mb_strtolower(), mb_stripos()
 * e mb_convert_case(), mantendo o scraper funcionando.
 */
if (!defined("MB_CASE_UPPER")) {
    define("MB_CASE_UPPER", 0);
}

if (!defined("MB_CASE_LOWER")) {
    define("MB_CASE_LOWER", 1);
}

if (!defined("MB_CASE_TITLE")) {
    define("MB_CASE_TITLE", 2);
}

if (!function_exists("mb_strtolower")) {
    function mb_strtolower($texto, $encoding = null) {

        $texto = (string)$texto;

        $mapa = [
            "Á" => "á", "À" => "à", "Ã" => "ã", "Â" => "â", "Ä" => "ä",
            "É" => "é", "È" => "è", "Ê" => "ê", "Ë" => "ë",
            "Í" => "í", "Ì" => "ì", "Î" => "î", "Ï" => "ï",
            "Ó" => "ó", "Ò" => "ò", "Õ" => "õ", "Ô" => "ô", "Ö" => "ö",
            "Ú" => "ú", "Ù" => "ù", "Û" => "û", "Ü" => "ü",
            "Ç" => "ç"
        ];

        return strtolower(strtr($texto, $mapa));
    }
}

if (!function_exists("mb_stripos")) {
    function mb_stripos($haystack, $needle, $offset = 0, $encoding = null) {
        return stripos((string)$haystack, (string)$needle, (int)$offset);
    }
}

if (!function_exists("mb_convert_case")) {
    function mb_convert_case($texto, $modo, $encoding = null) {

        $texto = (string)$texto;

        if ($modo === MB_CASE_UPPER) {
            return strtoupper($texto);
        }

        if ($modo === MB_CASE_TITLE) {
            return ucwords(strtolower($texto));
        }

        return strtolower($texto);
    }
}


/**
 * DESCOBRIR A RAIZ DO WORDPRESS
 *
 * Se o script estiver dentro de tema/plugin/subpasta, esta função sobe
 * os diretórios até encontrar wp-load.php ou wp-config.php.
 * Assim as imagens são salvas na raiz correta do WordPress.
 */
function detectarRaizWordPress() {

    if (defined("ABSPATH") && ABSPATH !== "") {
        return rtrim(ABSPATH, "/\\");
    }

    $dir = __DIR__;

    for ($i = 0; $i < 8; $i++) {

        if (file_exists($dir . "/wp-load.php") || file_exists($dir . "/wp-config.php")) {
            return rtrim($dir, "/\\");
        }

        $dirPai = dirname($dir);

        if ($dirPai === $dir) {
            break;
        }

        $dir = $dirPai;
    }

    // Fallback: mantém o comportamento antigo caso não encontre a raiz
    return rtrim(__DIR__, "/\\");
}

/**
 * REGRA GLOBAIS
 */

$arquivoCsv = "scraper-res.csv";
$arquivoCsvUsuarios = "scraper-users.csv";
$enviarEmailNovoImovel = "sim";
$emailNotificacaoNovoImovel = "diefersil@gmail.com";
$enviarEmailResumoScraper = "sim";
$emailNotificacaoResumoScraper = "diefersil@gmail.com";
$gravar_csv = "sim";
$limiteRegistrosCsv = 500;
$limiteImagensGaleria = 10;
$raizWordPress = detectarRaizWordPress();
$baixar_imagens = "sim";
$exibir_log_imagens = "sim";
$pastaImagensImport = $raizWordPress . "/wp-content/uploads/wpallimport/files";
$caminhoRelativoImagensImport = "wp-content/uploads/wpallimport/files";
$logsImagens = [];

/**
 * REGRA GLOBAL DE CATEGORIA DO IMÓVEL
 */
$categoriaImovelRegras = [
    [
        "categoria" => "Casas",
        "strings" => "casa, sobrado, meia agua, meia água, casas, mansao, mansão"
    ],
    [
        "categoria" => "Fazendas",
        "strings" => "fazenda,fazendas"
    ],
    [
        "categoria" => "Sítios e Chácaras",
        "strings" => "chácara,chacaras,sitio,sitios"
    ],
    [
        "categoria" => "Chácaras",
        "strings" => "chácara,chacaras"
    ],
    [
        "categoria" => "Lotes e Terrenos",
        "strings" => "lote, lotes, terreno, terrenos"
    ],
    [
        "categoria" => "Apartamentos",
        "strings" => "apartamento, apartamentos, apto"
    ],
    [
        "categoria" => "Kitnet",
        "strings" => "kitnet,kitinets,quitinete,kitnete"
    ],
    [
        "categoria" => "Barracoes e Galpões",
        "strings" => "barracao,barracoes,galpao,galpoes"
    ],
    [
        "categoria" => "Salas Comerciais",
        "strings" => "sala comercial,salas comerciais"
    ]
];

/**
 * REGRA GLOBAL DE STATUS DO IMÓVEL
 */
$StatusImovelRegras = [
    [
        "status" => "Aluguel",
        "strings" => "aluguel,aluga,aluga-se,locação,locações,locacao, locacoes,alugar"
    ],
    [
        "status" => "Venda",
        "strings" => "venda,vende,vende-se,à venda,a venda,compra,comprar,vender"
    ]
];

/**
 * CONFIGURAÇÃO DOS SITES
 *
 * O array $sites foi separado para facilitar manutenção.
 */
require_once __DIR__ . "/scraper-sites-config.php";

/**
 * NORMALIZAR URLS DO SITE
 */
function normalizarUrlsSite($url) {

    if (empty($url)) {
        return [];
    }

    if (is_array($url)) {

        $urls = [];

        foreach ($url as $itemUrl) {

            $itemUrl = normalizarUrlConfig($itemUrl);

            if ($itemUrl !== "" && !in_array($itemUrl, $urls)) {
                $urls[] = $itemUrl;
            }
        }

        return $urls;
    }

    $url = normalizarUrlConfig($url);

    if ($url === "") {
        return [];
    }

    return [$url];
}

/**
 * NORMALIZAR URL DO CONFIG
 *
 * Se a URL vier sem http/https, adiciona https:// automaticamente.
 */
function normalizarUrlConfig($url) {

    $url = trim((string)$url);

    if ($url === "") {
        return "";
    }

    if (preg_match('/^www\./i', $url)) {
        return "https://" . $url;
    }

    if (!preg_match('/^https?:\/\//i', $url)) {
        return "https://" . ltrim($url, "/");
    }

    return $url;
}


/**
 * PEGAR URL PRINCIPAL SEM BARRA FINAL
 *
 * Exemplo:
 * https://site.com.br/imoveis/page/2
 * vira:
 * https://site.com.br
 */
function getUrlPrincipalSemBarra($url) {

    $url = trim((string)$url);

    if ($url === "") {
        return "";
    }

    $partes = parse_url($url);

    if (empty($partes["scheme"]) || empty($partes["host"])) {
        return rtrim($url, "/");
    }

    return rtrim($partes["scheme"] . "://" . $partes["host"], "/");
}

/**
 * VERIFICA SE O SITE DEVE RODAR AGORA
 */
function deveRodarAgora($frequencia) {

    if (empty($frequencia) || empty($frequencia["tipo"])) {
        return true;
    }

    $tipo = mb_strtolower(trim((string)$frequencia["tipo"]), "UTF-8");

    if ($tipo === "nunca") {
        return false;
    }

    if ($tipo === "sempre") {
        return true;
    }

    if ($tipo === "horario") {

        $inicio = $frequencia["horario_inicio"] ?? "";
        $fim = $frequencia["horario_fim"] ?? "";

        if (empty($inicio) || empty($fim)) {
            return false;
        }

        // Evita problema com strtotime("24:00")
        if ($fim === "24:00") {
            $fim = "23:59";
        }

        $agora = strtotime(date("H:i"));
        $horaInicio = strtotime($inicio);
        $horaFim = strtotime($fim);

        if ($horaInicio <= $horaFim) {
            return ($agora >= $horaInicio && $agora <= $horaFim);
        }

        // Caso atravesse meia-noite, exemplo: 23:00 até 01:00
        return ($agora >= $horaInicio || $agora <= $horaFim);
    }

    return true;
}

/**
 * CURL
 */
function getHtml($url) {

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36",
        CURLOPT_TIMEOUT => 40,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_ENCODING => "",
        CURLOPT_REFERER => "https://www.google.com/",
        CURLOPT_HTTPHEADER => [
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
            "Accept-Language: pt-BR,pt;q=0.9,en;q=0.8",
            "Cache-Control: no-cache",
        ],
    ]);

    $html = curl_exec($ch);

    $erro = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $urlFinal = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

    curl_close($ch);

    return [
        "html" => $html,
        "erro" => $erro,
        "http_code" => $httpCode,
        "url_final" => $urlFinal,
        "ok" => ($html && $httpCode >= 200 && $httpCode < 400)
    ];
}

/**
 * LIMPAR TEXTO
 */
function limpar($texto) {
    return trim(
        preg_replace('/\s+/', ' ', strip_tags((string)$texto))
    );
}

/**
 * PEGAR HTML INTERNO DE UM NODE
 */
function getInnerHtml($node) {

    if (!$node) {
        return "";
    }

    $html = "";

    foreach ($node->childNodes as $child) {
        $html .= $node->ownerDocument->saveHTML($child);
    }

    return trim($html);
}

/**
 * LIMPAR DESCRIÇÃO HTML
 *
 * Mantém somente:
 * h1, h2, h3, h4, h5, h6, ul, li, b, i
 *
 * Remove atributos das tags.
 * Remove scripts, styles e demais tags.
 * Mantém quebras de linha.
 */
function limparDescricaoHtmlPermitida($html) {

    $html = (string)$html;

    if ($html === "") {
        return "";
    }

    // Decodifica entidades HTML antes do tratamento
    $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, "UTF-8");

    // Remove scripts e styles completamente
    $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
    $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);

    /**
     * Títulos h1-h6 viram <h4 class='desc-title'>.
     * Exemplo:
     * <h2>Descrição</h2> => <h4 class='desc-title'>Descrição</h4>
     */
    $html = preg_replace_callback('/<h[1-6]\b[^>]*>(.*?)<\/h[1-6]>/is', function ($match) {

        $titulo = trim(
            preg_replace('/\s+/', ' ', strip_tags($match[1]))
        );

        if ($titulo === "") {
            return "";
        }

        return "<h4 class='desc-title'>" . $titulo . "</h4>";
    }, $html);

    /**
     * Parágrafos, br e quebras reais viram <br/>
     */
    $html = preg_replace('/<\s*br\s*\/?>/i', '<br/>', $html);
    $html = preg_replace('/<\s*p\b[^>]*>/i', '<br/>', $html);
    $html = preg_replace('/<\s*\/\s*p\s*>/i', '<br/>', $html);
    $html = preg_replace('/\r\n|\r|\n/', '<br/>', $html);

    /**
     * Divs e blocos viram quebra no fechamento.
     * Exemplo:
     * <div>Exemplo</div> => Exemplo<br/>
     */
    $html = preg_replace('/<\s*(div|section|article|tr|table)\b[^>]*>/i', '', $html);
    $html = preg_replace('/<\s*\/\s*(div|section|article|tr|table)\s*>/i', '<br/>', $html);

    /**
     * Mantém somente h4.desc-title, ul, li, b e br, sem atributos extras.
     * O <h4 class='desc-title'> é usado para títulos da descrição.
     * O <br/> é mantido para preservar quebras no WP All Import.
     */
    $html = preg_replace('/<\s*h4\b[^>]*>/i', "<h4 class='desc-title'>", $html);
    $html = preg_replace('/<\s*\/\s*h4\s*>/i', '</h4>', $html);
    $html = preg_replace('/<\s*ul\b[^>]*>/i', '<ul>', $html);
    $html = preg_replace('/<\s*\/\s*ul\s*>/i', '</ul>', $html);
    $html = preg_replace('/<\s*li\b[^>]*>/i', '<li>', $html);
    $html = preg_replace('/<\s*\/\s*li\s*>/i', '</li>', $html);

    // Remove todo HTML restante, deixando somente h4, ul, li, b e br
    $html = strip_tags($html, '<h4><ul><li><b><br>');

    // Normaliza qualquer variação de br para <br/>
    $html = preg_replace('/<br\s*\/?>/i', '<br/>', $html);

    // Remove espaços excessivos entre tags e texto
    $html = preg_replace('/[ \t]+/', ' ', $html);
    $html = preg_replace('/\s*<br\/>\s*/i', '<br/>', $html);
    $html = preg_replace('/\s*<h4\s+class=[\'\"]desc-title[\'\"]>\s*/i', "<h4 class='desc-title'>", $html);
    $html = preg_replace('/\s*<\/h4>\s*/i', '</h4>', $html);
    $html = preg_replace('/\s*<ul>\s*/i', '<ul>', $html);
    $html = preg_replace('/\s*<\/ul>\s*/i', '</ul>', $html);
    $html = preg_replace('/\s*<li>\s*/i', '<li>', $html);
    $html = preg_replace('/\s*<\/li>\s*/i', '</li>', $html);

    // Evita vários <br/> seguidos
    $html = preg_replace('/(?:<br\/>){2,}/i', '<br/>', $html);

    // Remove <br/> no começo, mas mantém no final quando veio de um bloco/div
    $html = preg_replace('/^(<br\/>)+/i', '', $html);

    return trim($html);
}


/**
 * NORMALIZA LISTAS SEPARADAS POR VÍRGULA
 */
function normalizarListaVirgula($texto) {

    $texto = limpar($texto);

    if ($texto === "") {
        return "";
    }

    $partes = explode(",", $texto);
    $limpos = [];

    foreach ($partes as $parte) {

        $valor = limpar($parte);

        if ($valor !== "" && !in_array($valor, $limpos)) {
            $limpos[] = $valor;
        }
    }

    return implode(", ", $limpos);
}

/**
 * REMOVER ACENTOS PARA COMPARAÇÃO
 */
function normalizarBusca($texto) {

    $texto = limpar($texto);
    $texto = mb_strtolower($texto, "UTF-8");

    $comAcento = [
        "á", "à", "ã", "â", "ä",
        "é", "è", "ê", "ë",
        "í", "ì", "î", "ï",
        "ó", "ò", "õ", "ô", "ö",
        "ú", "ù", "û", "ü",
        "ç"
    ];

    $semAcento = [
        "a", "a", "a", "a", "a",
        "e", "e", "e", "e",
        "i", "i", "i", "i",
        "o", "o", "o", "o", "o",
        "u", "u", "u", "u",
        "c"
    ];

    return str_replace($comAcento, $semAcento, $texto);
}


/**
 * PADRONIZAR NOME DA CIDADE ENCONTRADO NO TEXTO
 */
function normalizarNomeCidade(string $cidade): string {

    $cidade = preg_replace('/\s+/u', ' ', $cidade);

    $cidade = trim(
        $cidade,
        " \t\n\r\0\x0B-–—,.;:/"
    );

    if (function_exists('mb_convert_case')) {

        $cidade = mb_convert_case(
            mb_strtolower($cidade, 'UTF-8'),
            MB_CASE_TITLE,
            'UTF-8'
        );

        $cidade = preg_replace_callback(
            '/\b(De|Da|Do|Das|Dos|E)\b/u',
            static fn(array $item): string =>
                mb_strtolower($item[1], 'UTF-8'),
            $cidade
        );
    }

    return $cidade;
}

/**
 * PREPARAR O TEXTO PARA BUSCA DE CIDADE/UF
 *
 * IMPORTANTE:
 * A fonte de cidade_sugerida usa o texto real do imóvel:
 * card_localizacao_original + descricao + og_title.
 *
 * O card_localizacao_original é o valor capturado do site antes do fallback.
 * As quebras de linha são preservadas porque ajudam a identificar títulos
 * e trechos como "FAZENDA EM BURITIS MG" no início do texto.
 */
function prepararTextoParaBuscaCidade($texto): string {

    $texto = (string)($texto ?? "");

    if (trim($texto) === "") {
        return "";
    }

    $texto = html_entity_decode(
        $texto,
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );

    // Preserva separação visual antes de remover o HTML.
    $texto = preg_replace('/<\s*br\s*\/?>/i', "\n", $texto);
    $texto = preg_replace('/<\s*\/\s*(?:p|div|li|ul|ol|h[1-6]|section|article)\s*>/i', "\n", $texto);
    $texto = preg_replace('/<\s*(?:p|div|li|ul|ol|h[1-6]|section|article)\b[^>]*>/i', '', $texto);

    $texto = strip_tags($texto);

    // Espaços internos são normalizados sem eliminar quebras de linha.
    $texto = preg_replace('/[ \t]+/u', ' ', $texto);
    $texto = preg_replace('/ *\R+ */u', "\n", $texto);
    $texto = preg_replace('/\n{2,}/u', "\n", $texto);

    return trim($texto);
}

/**
 * ADICIONAR CANDIDATO DE CIDADE/UF
 *
 * Se a mesma cidade/UF for encontrada por mais de uma regra,
 * mantém a ocorrência com maior pontuação.
 */
function adicionarCandidatoCidadeUf(array &$candidatos, $cidade, $uf, $pontos, $regra, $trecho, $posicao): void {

    $cidade = normalizarNomeCidade((string)$cidade);
    $uf = strtoupper(trim((string)$uf));
    $trecho = trim((string)$trecho);
    $posicao = max(0, (int)$posicao);

    if ($cidade === "") {
        return;
    }

    // Evita candidatos obviamente inválidos.
    if (preg_match('/\d/u', $cidade)) {
        return;
    }

    $cidadeBusca = normalizarBusca($cidade);

    $invalidosExatos = [
        'cidade', 'municipio', 'imovel', 'propriedade',
        'casa', 'apartamento', 'terreno', 'lote',
        'fazenda', 'sitio', 'chacara', 'galpao',
        'barracao', 'loja', 'sala comercial', 'ponto comercial'
    ];

    if (in_array($cidadeBusca, $invalidosExatos, true)) {
        return;
    }

    /**
     * Evita que o padrão genérico transforme trechos de referência em cidade.
     * Exemplos que devem ser descartados:
     * - km de Unaí-MG
     * - próximo de Brasília-DF
     * - Fazenda em Buritis-MG (quando capturado inteiro pelo fallback genérico)
     */
    if (preg_match(
        '/^(?:km\s+de|quil[oô]metros?\s+de|pr[oó]xim[oa]\s+de|distante\s+de|sentido|acesso\s+por|via)\b/iu',
        $cidadeBusca
    )) {
        return;
    }

    if (preg_match(
        '/^(?:em|no|na|de|do|da)\s+/iu',
        $cidadeBusca
    )) {
        return;
    }

    if (preg_match(
        '/^(?:fazenda|s[ií]tio|ch[áa]cara|casa|apartamento|apto|sobrado|terreno|lote|kitnet|quitinete|galp[aã]o|barrac[aã]o|loja|im[oó]vel|propriedade)\s+(?:em|no|na)\s+/iu',
        $cidadeBusca
    )) {
        return;
    }

    $chave = $cidadeBusca . '|' . $uf;

    $novo = [
        'cidade' => $cidade,
        'uf' => $uf,
        'pontos' => (int)$pontos,
        'regra' => (string)$regra,
        'trecho' => $trecho,
        'posicao' => $posicao
    ];

    if (!isset($candidatos[$chave])) {
        $candidatos[$chave] = $novo;
        return;
    }

    $atual = $candidatos[$chave];

    if (
        $novo['pontos'] > $atual['pontos'] ||
        ($novo['pontos'] === $atual['pontos'] && $novo['posicao'] < $atual['posicao'])
    ) {
        $candidatos[$chave] = $novo;
    }
}

/**
 * PROCURA CIDADE E UF NO TEXTO REAL DO IMÓVEL
 *
 * Estratégia:
 * - encontra vários candidatos;
 * - atribui maior pontuação para padrões imobiliários mais explícitos;
 * - prioriza ocorrências no início do texto;
 * - penaliza cidades usadas apenas como referência de distância/acesso;
 * - retorna somente o candidato mais confiável.
 */
function encontrarCidadeUf(string $texto): array {

    $textoLimpo = prepararTextoParaBuscaCidade($texto);

    if ($textoLimpo === "") {
        return [
            'cidade' => null,
            'uf' => null,
            'regra' => null,
            'trecho' => null,
            'confianca' => 0
        ];
    }

    $ufs = implode('|', [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF',
        'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA',
        'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS',
        'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
    ]);

    /**
     * Até 6 palavras no nome da cidade.
     * Exemplos:
     * Buritis
     * Rio Verde
     * Santa Helena de Goiás
     * São João da Ponte
     */
    $nomeCidade =
        "[\\p{L}][\\p{L}\\p{M}'’.-]*" .
        "(?:\\s+[\\p{L}][\\p{L}\\p{M}'’.-]*){0,5}";

    /**
     * Tipos de imóveis rurais e urbanos.
     */
    $tiposImovel =
        "fazenda|fazendas|" .
        "s[ií]tio|s[ií]tios|" .
        "ch[áa]cara|ch[áa]caras|" .
        "casa|casas|sobrado|sobrados|mans[aã]o|mans[oõ]es|" .
        "apartamento|apartamentos|apto|aptos|" .
        "terreno|terrenos|lote|lotes|" .
        "kitnet|kitnets|quitinete|quitinetes|" .
        "flat|flats|loft|lofts|duplex|cobertura|coberturas|" .
        "galp[aã]o|galp[oõ]es|barrac[aã]o|barrac[oõ]es|" .
        "loja|lojas|sala\\s+comercial|salas\\s+comerciais|" .
        "ponto\\s+comercial|pontos\\s+comerciais|" .
        "pr[eé]dio|pr[eé]dios|" .
        "im[oó]vel|im[oó]veis|propriedade|propriedades|" .
        "[aá]rea\\s+rural|[aá]rea\\s+urbana|[aá]rea";

    $candidatos = [];

    /**
     * Ordem não define o vencedor sozinha: cada regra tem uma pontuação.
     */
    $regras = [
        [
            'nome' => 'cidade_ou_municipio_com_uf',
            'pontos' => 100,
            'regex' =>
                "/\\b(?:cidade|munic[ií]pio)\\s+de\\s+" .
                "($nomeCidade)\\s*" .
                "(?:[-–—\\/,]\\s*|\\s+)" .
                "($ufs)\\b/iu"
        ],
        [
            'nome' => 'tipo_imovel_em_com_uf',
            'pontos' => 98,
            'regex' =>
                "/\\b(?:$tiposImovel)\\b" .
                "(?:\\s+(?:rural|urbano|urbana))?" .
                "(?:\\s+(?:à\\s+venda|a\\s+venda|para\\s+venda))?" .
                "(?:\\s+(?:localizad[oa]|situad[oa]))?" .
                "\\s+(?:em|no|na)\\s+" .
                "($nomeCidade)\\s*" .
                "(?:[-–—\\/,]\\s*|\\s+)" .
                "($ufs)\\b/iu"
        ],
        [
            'nome' => 'localizado_em_com_uf',
            'pontos' => 95,
            'regex' =>
                "/\\b(?:localizad[oa]s?|situad[oa]s?|localiza-se|situa-se)\\s+" .
                "(?:em|no|na)\\s+" .
                "(?:cidade\\s+de\\s+|munic[ií]pio\\s+de\\s+)?" .
                "($nomeCidade)\\s*" .
                "(?:[-–—\\/,]\\s*|\\s+)" .
                "($ufs)\\b/iu"
        ],
        [
            'nome' => 'venda_em_com_uf',
            'pontos' => 90,
            'regex' =>
                "/\\b(?:à\\s+venda|a\\s+venda|vende-se|venda)\\s+" .
                "(?:em|no|na)\\s+" .
                "($nomeCidade)\\s*" .
                "(?:[-–—\\/,]\\s*|\\s+)" .
                "($ufs)\\b/iu"
        ],
        [
            'nome' => 'rotulo_localizacao_com_uf',
            'pontos' => 88,
            'regex' =>
                "/\\b(?:localiza[cç][aã]o|cidade|munic[ií]pio|endere[cç]o)\\s*[:=-]\\s*" .
                "($nomeCidade)\\s*" .
                "(?:[-–—\\/,]\\s*|\\s+)" .
                "($ufs)\\b/iu"
        ],
        [
            'nome' => 'cidade_uf_com_separador',
            'pontos' => 72,
            'regex' =>
                "/\\b($nomeCidade)\\s*[-–—\\/]\\s*($ufs)\\b/iu"
        ]
    ];

    foreach ($regras as $regra) {

        if (!preg_match_all(
            $regra['regex'],
            $textoLimpo,
            $matches,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE
        )) {
            continue;
        }

        foreach ($matches as $match) {

            $trecho = $match[0][0] ?? '';
            $posicao = $match[0][1] ?? 0;
            $cidade = $match[1][0] ?? '';
            $uf = $match[2][0] ?? '';

            $pontos = (int)$regra['pontos'];

            // Títulos e primeiras linhas tendem a descrever o imóvel.
            if ($posicao <= 120) {
                $pontos += 15;
            } elseif ($posicao <= 350) {
                $pontos += 8;
            }

            adicionarCandidatoCidadeUf(
                $candidatos,
                $cidade,
                $uf,
                $pontos,
                $regra['nome'],
                $trecho,
                $posicao
            );
        }
    }

    /**
     * Sem UF: aceita apenas formas explícitas "cidade de" / "município de".
     * Isso reduz falsos positivos como "casa em condomínio fechado".
     */
    $regexSemUf =
        "/\\b(?:cidade|munic[ií]pio)\\s+de\\s+" .
        "($nomeCidade)" .
        "(?=\\s+(?:com|onde|que|fica|possui|tem|e)\\b|" .
        "[\\n,.;:()]|$)/iu";

    if (preg_match_all(
        $regexSemUf,
        $textoLimpo,
        $matchesSemUf,
        PREG_SET_ORDER | PREG_OFFSET_CAPTURE
    )) {

        foreach ($matchesSemUf as $match) {

            $trecho = $match[0][0] ?? '';
            $posicao = $match[0][1] ?? 0;
            $cidade = $match[1][0] ?? '';
            $pontos = 68;

            if ($posicao <= 120) {
                $pontos += 12;
            } elseif ($posicao <= 350) {
                $pontos += 6;
            }

            adicionarCandidatoCidadeUf(
                $candidatos,
                $cidade,
                '',
                $pontos,
                'cidade_ou_municipio_sem_uf',
                $trecho,
                $posicao
            );
        }
    }

    if (empty($candidatos)) {
        return [
            'cidade' => null,
            'uf' => null,
            'regra' => null,
            'trecho' => null,
            'confianca' => 0
        ];
    }

    /**
     * Penaliza cidades citadas apenas como referência.
     * Exemplos:
     * - 230 km de Brasília
     * - distante 50 km de Unaí
     * - próximo de Paracatu
     * - sentido Rio Verde
     */
    foreach ($candidatos as $chave => $candidato) {

        $cidadeRegex = preg_quote($candidato['cidade'], '/');
        $penalidade = 0;

        if (preg_match(
            '/\\b\\d+(?:[.,]\\d+)?\\s*km\\s+(?:de|da|do|at[eé]|para)\\s+(?:a\\s+)?' . $cidadeRegex . '\\b/iu',
            $textoLimpo
        )) {
            $penalidade = max($penalidade, 85);
        }

        if (preg_match(
            '/\\b(?:distante|dist[aâ]ncia)\\s+(?:de\\s+)?\\d+(?:[.,]\\d+)?\\s*km\\s+(?:de|da|do)?\\s*' . $cidadeRegex . '\\b/iu',
            $textoLimpo
        )) {
            $penalidade = max($penalidade, 85);
        }

        if (preg_match(
            '/\\b(?:pr[oó]ximo|pr[oó]xima|proximidades|sentido|acesso\\s+por|via)\\s+(?:a|de|da|do|para)?\\s*' . $cidadeRegex . '\\b/iu',
            $textoLimpo
        )) {
            $penalidade = max($penalidade, 40);
        }

        $candidatos[$chave]['pontos'] -= $penalidade;
    }

    $candidatos = array_values($candidatos);

    usort($candidatos, static function ($a, $b) {

        if ($a['pontos'] === $b['pontos']) {
            return $a['posicao'] <=> $b['posicao'];
        }

        return $b['pontos'] <=> $a['pontos'];
    });

    $melhor = $candidatos[0];
    $confianca = max(0, min(100, (int)$melhor['pontos']));

    /**
     * Abaixo de 50 pontos, prefere não sugerir uma cidade.
     */
    if ($confianca < 50) {
        return [
            'cidade' => null,
            'uf' => null,
            'regra' => null,
            'trecho' => null,
            'confianca' => $confianca
        ];
    }

    return [
        'cidade' => $melhor['cidade'],
        'uf' => $melhor['uf'],
        'regra' => $melhor['regra'],
        'trecho' => $melhor['trecho'],
        'confianca' => $confianca
    ];
}

/**
 * SUGERIR CIDADE E UF DO IMÓVEL
 *
 * REGRA DEFINITIVA:
 * cidade_sugerida e uf_sugerido usam o texto real do imóvel:
 * card_localizacao_original + descricao + og_title.
 *
 * IMPORTANTE:
 * - card_localizacao_original é capturado antes do fallback;
 * - o fallback de card_localizacao não entra na busca de cidade_sugerida;
 * - isso evita falso positivo quando a localização é montada com a cidade global.
 */
function sugerirCidadeUfImovel($descricao, $ogTitle = "", $cardLocalizacaoOriginal = ""): array {

    $textoBuscaCidade = trim(
        (string)$cardLocalizacaoOriginal . "\n" .
        (string)$descricao . "\n" .
        (string)$ogTitle
    );

    if ($textoBuscaCidade === "") {
        return [
            "cidade" => "",
            "uf" => "",
            "regra" => null,
            "trecho" => null,
            "confianca" => 0
        ];
    }

    $sugestao = encontrarCidadeUf($textoBuscaCidade);

    return [
        "cidade" => !empty($sugestao["cidade"]) ? $sugestao["cidade"] : "",
        "uf" => !empty($sugestao["uf"]) ? $sugestao["uf"] : "",
        "regra" => $sugestao["regra"] ?? null,
        "trecho" => $sugestao["trecho"] ?? null,
        "confianca" => (int)($sugestao["confianca"] ?? 0)
    ];
}

/**
 * NORMALIZAR PREÇO
 */
function normalizarPrecoInteiro($preco) {

    $precoOriginal = limpar($preco);

    if ($precoOriginal === "") {
        return "";
    }

    $precoBusca = normalizarBusca($precoOriginal);

    if (preg_match('/(\d+(?:[.,]\d+)?)\s*mil\b/i', $precoBusca, $match)) {

        $numero = str_replace(",", ".", $match[1]);
        $valor = (float)$numero * 1000;

        return (string)(int)round($valor);
    }

    if (preg_match('/(\d+(?:[.,]\d+)?)\s*(milhao|milhoes)\b/i', $precoBusca, $match)) {

        $numero = str_replace(",", ".", $match[1]);
        $valor = (float)$numero * 1000000;

        return (string)(int)round($valor);
    }

    $preco = preg_replace('/[^\d,\.]/', '', $precoOriginal);

    if ($preco === "") {
        return "";
    }

    if (strpos($preco, ",") !== false) {
        $partes = explode(",", $preco);
        $preco = $partes[0];
    }

    $preco = str_replace(".", "", $preco);
    $preco = preg_replace('/\D/', '', $preco);

    return $preco;
}

/**
 * GERAR DATA FUTURA EM FORMATO AMERICANO
 */
function gerarDataPeriodoEua($periodo) {

    $periodo = (int)$periodo;

    if ($periodo <= 0) {
        return "";
    }

    return date("Y-m-d", strtotime("+" . $periodo . " days"));
}

/**
 * GERAR DATA DE EXPIRAÇÃO
 *
 * Calcula a expiração usando:
 * data_primeiro_scraper_eua + período em dias.
 *
 * Formato final:
 * 2026-08-10 17:07:32
 */
function gerarDataPeriodoTimestamp($dataPrimeiroScraperEua, $periodo) {

    $dataPrimeiroScraperEua = trim((string)$dataPrimeiroScraperEua);
    $periodo = (int)$periodo;

    if ($dataPrimeiroScraperEua === "" || $periodo <= 0) {
        return "";
    }

    $timestampBase = strtotime($dataPrimeiroScraperEua);

    if (!$timestampBase) {
        return "";
    }

    $timestampExpiracao = strtotime(date("Y-m-d H:i:s", $timestampBase) . " +" . $periodo . " days");

    if (!$timestampExpiracao) {
        return "";
    }

    return date("Y-m-d H:i:s", $timestampExpiracao);
}

/**
 * NORMALIZAR DATA DE EXPIRAÇÃO PARA O CSV
 *
 * Garante compatibilidade com CSV antigo que tinha timestamp numérico.
 */
function normalizarDataExpiracaoCsv($dataExpiracao) {

    $dataExpiracao = trim((string)$dataExpiracao);

    if ($dataExpiracao === "") {
        return "";
    }

    if (ctype_digit($dataExpiracao)) {
        return date("Y-m-d H:i:s", (int)$dataExpiracao);
    }

    $timestamp = strtotime($dataExpiracao);

    if (!$timestamp) {
        return $dataExpiracao;
    }

    return date("Y-m-d H:i:s", $timestamp);
}

/**
 * VERIFICAÇÃO OPCIONAL POR STRING
 */
function deveSalvarPorString($cardNome, $verificarString) {

    $verificarString = limpar($verificarString ?? "");

    if ($verificarString === "") {
        return true;
    }

    $listaStrings = explode(",", $verificarString);

    foreach ($listaStrings as $string) {

        $string = limpar($string);

        if ($string === "") {
            continue;
        }

        if (mb_stripos($cardNome, $string, 0, "UTF-8") !== false) {
            return true;
        }
    }

    return false;
}

/**
 * DEFINIR CATEGORIA DO IMÓVEL PELO CARD_NOME
 */
function definirCategoriaImovel($cardNome, $regrasCategoriaImovel) {

    if (empty($regrasCategoriaImovel) || !is_array($regrasCategoriaImovel)) {
        return "";
    }

    $cardNomeBusca = normalizarBusca($cardNome);
    $categoriaPadrao = "";

    foreach ($regrasCategoriaImovel as $regra) {

        $categoria = limpar($regra["categoria"] ?? "");
        $strings = limpar($regra["strings"] ?? "");

        if ($categoria === "") {
            continue;
        }

        if ($strings === "") {
            if ($categoriaPadrao === "") {
                $categoriaPadrao = $categoria;
            }

            continue;
        }

        $listaStrings = explode(",", $strings);

        foreach ($listaStrings as $string) {

            $stringBusca = normalizarBusca($string);

            if ($stringBusca === "") {
                continue;
            }

            if (mb_stripos($cardNomeBusca, $stringBusca, 0, "UTF-8") !== false) {
                return $categoria;
            }
        }
    }

    return $categoriaPadrao;
}

/**
 * DEFINIR STATUS DO IMÓVEL
 */
function definirStatusImovel($cardNome, $descricao, $regrasStatusImovel) {

    if (empty($regrasStatusImovel) || !is_array($regrasStatusImovel)) {
        return "";
    }

    $textoBusca = normalizarBusca($cardNome . " " . $descricao);
    $statusPadrao = "";

    foreach ($regrasStatusImovel as $regra) {

        $status = limpar($regra["status"] ?? "");
        $strings = limpar($regra["strings"] ?? "");

        if ($status === "") {
            continue;
        }

        if ($strings === "") {
            if ($statusPadrao === "") {
                $statusPadrao = $status;
            }

            continue;
        }

        $listaStrings = explode(",", $strings);

        foreach ($listaStrings as $string) {

            $stringBusca = normalizarBusca($string);

            if ($stringBusca === "") {
                continue;
            }

            if (mb_stripos($textoBusca, $stringBusca, 0, "UTF-8") !== false) {
                return $status;
            }
        }
    }

    return $statusPadrao;
}

/**
 * CRIAR DOM XPATH
 */
function criarXpath($html) {

    libxml_use_internal_errors(true);

    $dom = new DOMDocument();

    $dom->loadHTML('<?xml encoding="UTF-8">' . $html);

    libxml_clear_errors();

    return new DOMXPath($dom);
}

/**
 * TRANSFORMAR URL RELATIVA EM ABSOLUTA
 */
function urlAbsoluta($url, $base) {

    $url = trim((string)$url);

    if ($url === "") {
        return "";
    }

    if (preg_match('/^https?:\/\//i', $url)) {
        return $url;
    }

    $partes = parse_url($base);

    if (empty($partes["scheme"]) || empty($partes["host"])) {
        return $url;
    }

    if (strpos($url, "//") === 0) {
        return $partes["scheme"] . ":" . $url;
    }

    $dominio = $partes["scheme"] . "://" . $partes["host"];

    if (strpos($url, "/") === 0) {
        return $dominio . $url;
    }

    $path = isset($partes["path"]) ? dirname($partes["path"]) : "";

    return rtrim($dominio . "/" . trim($path, "/"), "/") . "/" . ltrim($url, "/");
}


/**
 * NORMALIZAR URL DE IMAGEM PARA WP ALL IMPORT
 *
 * Alguns plugins/importadores podem interpretar "+" como espaço.
 * Esta função codifica corretamente o path da URL:
 *
 * +       => %2B
 * =       => %3D
 * espaço  => %20
 */
function normalizarUrlImagemImport($url) {

    $url = trim((string)$url);

    if ($url === "") {
        return "";
    }

    $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, "UTF-8");

    $partes = parse_url($url);

    if (empty($partes["scheme"]) || empty($partes["host"])) {
        return $url;
    }

    $scheme = $partes["scheme"];
    $host = $partes["host"];
    $path = $partes["path"] ?? "";
    $query = isset($partes["query"]) ? "?" . $partes["query"] : "";

    $segmentos = explode("/", ltrim($path, "/"));
    $segmentosCodificados = [];

    foreach ($segmentos as $segmento) {
        $segmentosCodificados[] = rawurlencode(rawurldecode($segmento));
    }

    $pathFinal = "/" . implode("/", $segmentosCodificados);

    return $scheme . "://" . $host . $pathFinal . $query;
}

/**
 * PEGAR EXTENSÃO DA IMAGEM PELA URL
 */
function getExtensaoImagemUrl($url) {

    $path = parse_url($url, PHP_URL_PATH);
    $ext = strtolower(pathinfo($path ?? "", PATHINFO_EXTENSION));

    $permitidas = ["jpg", "jpeg", "png", "webp", "gif"];

    if (in_array($ext, $permitidas)) {
        return $ext;
    }

    return "jpg";
}

/**
 * GERAR NOME LOCAL SEGURO PARA IMAGEM
 */
function gerarNomeImagemLocal($url) {

    $ext = getExtensaoImagemUrl($url);

    return "img_" . md5($url) . "." . $ext;
}

/**
 * VERIFICA SE A IMAGEM É DO CDN VISTAHOST / ÁREA 38
 */
function imagemEhArea38VistaHost($urlImagem) {

    $urlBusca = mb_strtolower((string)$urlImagem, "UTF-8");

    return (
        strpos($urlBusca, "cdn.vistahost.com.br/area38lt/") !== false ||
        strpos($urlBusca, "vista.imobi/fotos/") !== false ||
        strpos($urlBusca, "area38") !== false
    );
}

/**
 * PEGAR RAIZ DO REFERER
 *
 * Exemplo:
 * https://area38.com.br/imovel/abc => https://area38.com.br/
 */
function getRaizReferer($url) {

    $url = trim((string)$url);

    if ($url === "" || !preg_match('/^https?:\/\//i', $url)) {
        return "";
    }

    $partes = parse_url($url);

    if (empty($partes["scheme"]) || empty($partes["host"])) {
        return "";
    }

    return $partes["scheme"] . "://" . $partes["host"] . "/";
}

/**
 * DEFINIR LISTA DE REFERERS PARA DOWNLOAD DE IMAGEM
 *
 * Em alguns CDNs, principalmente o VistaHost/Área 38, a imagem só baixa
 * quando o referer é a raiz do site, por exemplo: https://area38.com.br/
 *
 * Por isso, o download tenta mais de um referer em ordem.
 */
function getReferersDownloadImagem($urlImagem, $refererOrigem = "") {

    $referers = [];
    $refererOrigem = trim((string)$refererOrigem);
    $raizRefererOrigem = getRaizReferer($refererOrigem);

    /**
     * Para imagens da Área 38/VistaHost, prioriza o referer que funcionou
     * no seu teste manual com cURL.
     */
    if (imagemEhArea38VistaHost($urlImagem)) {
        $referers[] = "https://area38.com.br/";
    }

    // Depois tenta a URL exata da página de origem
    if ($refererOrigem !== "" && preg_match('/^https?:\/\//i', $refererOrigem)) {
        $referers[] = $refererOrigem;
    }

    // Depois tenta somente a raiz do domínio da origem
    if ($raizRefererOrigem !== "") {
        $referers[] = $raizRefererOrigem;
    }

    // Fallback genérico
    $referers[] = "https://www.google.com/";

    // Remove duplicados preservando ordem
    $referersUnicos = [];

    foreach ($referers as $referer) {
        $referer = trim($referer);

        if ($referer !== "" && !in_array($referer, $referersUnicos)) {
            $referersUnicos[] = $referer;
        }
    }

    return $referersUnicos;
}

/**
 * DEFINIR REFERER PRINCIPAL PARA LOG
 */
function getRefererDownloadImagem($urlImagem, $refererOrigem = "") {

    $referers = getReferersDownloadImagem($urlImagem, $refererOrigem);

    return $referers[0] ?? "https://www.google.com/";
}

/**
 * ADICIONAR ITEM AO LOG DE IMAGENS
 */
function adicionarLogImagem($status, $urlOriginal, $caminhoRelativo = "", $mensagem = "", $extra = []) {

    global $logsImagens;

    $item = [
        "data" => date("d/m/Y H:i:s"),
        "status" => $status,
        "url_original" => $urlOriginal,
        "caminho_local" => $caminhoRelativo,
        "mensagem" => $mensagem
    ];

    if (!empty($extra) && is_array($extra)) {
        $item = array_merge($item, $extra);
    }

    $logsImagens[] = $item;
}

/**
 * BAIXAR IMAGEM POR CURL PARA A PASTA DO WP ALL IMPORT
 *
 * Retorna somente o nome final do arquivo salvo no CSV/JSON.
 * Se falhar, retorna a URL original normalizada para não perder a imagem.
 */
function baixarImagemParaWpAllImport($url, $refererOrigem = "") {

    global $baixar_imagens;
    global $pastaImagensImport;
    global $caminhoRelativoImagensImport;

    $urlOriginal = trim((string)$url);
    $url = normalizarUrlImagemImport($url);

    if (empty($url)) {
        adicionarLogImagem("ignorado", $urlOriginal, "", "URL vazia");
        return "";
    }

    // Se não for URL http/https, mantém como está
    if (!preg_match('/^https?:\/\//i', $url)) {
        adicionarLogImagem("ignorado", $url, $url, "Não é URL externa http/https");
        return $url;
    }

    // Se estiver desativado, mantém a URL externa
    if (normalizarBusca($baixar_imagens) !== "sim") {
        adicionarLogImagem("ignorado", $url, $url, "Download de imagens desativado");
        return $url;
    }

    if (empty($pastaImagensImport)) {
        adicionarLogImagem("erro", $url, "", "Pasta de imagens não configurada");
        return $url;
    }

    if (!is_dir($pastaImagensImport)) {
        @mkdir($pastaImagensImport, 0755, true);
    }

    if (!is_dir($pastaImagensImport) || !is_writable($pastaImagensImport)) {
        adicionarLogImagem("erro", $url, "", "Pasta não existe ou sem permissão de escrita", [
            "pasta" => $pastaImagensImport
        ]);
        return $url;
    }

    $nomeArquivo = gerarNomeImagemLocal($url);
    $caminhoArquivo = rtrim($pastaImagensImport, "/") . "/" . $nomeArquivo;
    $caminhoRelativo = trim($caminhoRelativoImagensImport, "/") . "/" . $nomeArquivo;

    $referersImagem = getReferersDownloadImagem($url, $refererOrigem);

    // Se já existe, não baixa novamente
    if (file_exists($caminhoArquivo) && filesize($caminhoArquivo) > 0) {
        adicionarLogImagem("ja_existia", $url, $caminhoRelativo, "Imagem já existia, não baixou novamente", [
            "arquivo" => $caminhoArquivo,
            "tamanho_bytes" => filesize($caminhoArquivo),
            "referer_usado" => $referersImagem[0] ?? "",
            "referers_tentados" => $referersImagem
        ]);
        return $nomeArquivo;
    }

    $tentativas = [];

    foreach ($referersImagem as $refererImagem) {

        $arquivoTmp = $caminhoArquivo . ".tmp";

        if (file_exists($arquivoTmp)) {
            @unlink($arquivoTmp);
        }

        $fp = @fopen($arquivoTmp, "wb");

        if (!$fp) {
            adicionarLogImagem("erro", $url, "", "Não foi possível criar arquivo temporário", [
                "arquivo_tmp" => $arquivoTmp
            ]);
            return $url;
        }

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_ENCODING => "",
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36",
            CURLOPT_REFERER => $refererImagem,
            CURLOPT_HTTPHEADER => [
                "Accept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8",
                "Accept-Language: pt-BR,pt;q=0.9,en;q=0.8",
                "Cache-Control: no-cache",
                "Pragma: no-cache",
                "Referer: " . $refererImagem,
            ],
        ]);

        $success = curl_exec($ch);

        $erro = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $urlFinal = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

        curl_close($ch);
        fclose($fp);

        $tamanhoTmp = (file_exists($arquivoTmp)) ? filesize($arquivoTmp) : 0;

        $tentativas[] = [
            "referer" => $refererImagem,
            "success" => $success ? "sim" : "nao",
            "http_code" => $httpCode,
            "erro_curl" => $erro,
            "content_type" => $contentType,
            "url_final" => $urlFinal,
            "tamanho_bytes_tmp" => $tamanhoTmp
        ];

        $downloadOk = (
            $success &&
            !$erro &&
            $httpCode >= 200 &&
            $httpCode < 400 &&
            file_exists($arquivoTmp) &&
            filesize($arquivoTmp) > 0
        );

        if ($downloadOk && !empty($contentType) && stripos($contentType, "image/") === false) {
            $downloadOk = false;
        }

        if (!$downloadOk) {
            @unlink($arquivoTmp);
            continue;
        }

        @rename($arquivoTmp, $caminhoArquivo);

        if (file_exists($caminhoArquivo) && filesize($caminhoArquivo) > 0) {
            adicionarLogImagem("baixada", $url, $caminhoRelativo, "Imagem baixada com sucesso", [
                "arquivo" => $caminhoArquivo,
                "tamanho_bytes" => filesize($caminhoArquivo),
                "http_code" => $httpCode,
                "content_type" => $contentType,
                "referer_usado" => $refererImagem,
                "referers_tentados" => $referersImagem,
                "tentativas" => $tentativas
            ]);
            return $nomeArquivo;
        }

        @unlink($arquivoTmp);
    }

    adicionarLogImagem("erro", $url, "", "Falha no download da imagem em todas as tentativas de referer", [
        "referer_origem" => $refererOrigem,
        "referers_tentados" => $referersImagem,
        "tentativas" => $tentativas
    ]);

    return $url;
}

/**
 * PEGAR URL DO ATRIBUTO STYLE
 */
function getUrlFromStyle($style) {

    $style = trim((string)$style);

    if ($style === "") {
        return "";
    }

    if (preg_match('/url\((["\']?)(.*?)\1\)/i', $style, $match)) {
        return trim($match[2]);
    }

    return "";
}

/**
 * PEGAR ATRIBUTO COM FALLBACK
 */
function getAtributoFallback($node, $atributos) {

    if (!$node) {
        return "";
    }

    foreach ($atributos as $attr) {

        if ($attr === "style") {

            $style = trim($node->getAttribute("style"));
            $urlStyle = getUrlFromStyle($style);

            if ($urlStyle !== "") {
                return $urlStyle;
            }

            continue;
        }

        $valor = trim($node->getAttribute($attr));

        if ($valor !== "") {

            if ($attr === "srcset" || $attr === "data-srcset") {
                $partes = explode(",", $valor);
                $valor = trim(explode(" ", trim($partes[0]))[0]);
            }

            return $valor;
        }
    }

    return "";
}

/**
 * PEGAR TEXTO PELO SELETOR
 */
function getTextoSeletor($xpath, $contexto, $seletor) {

    if (empty($seletor)) {
        return "";
    }

    $node = $xpath->query($seletor, $contexto);

    if ($node && $node->length > 0) {
        return limpar($node->item(0)->textContent);
    }

    return "";
}

/**
 * PEGAR URL PELO SELETOR
 */
function getUrlSeletor($xpath, $contexto, $seletor, $baseUrl) {

    if (empty($seletor)) {
        return "";
    }

    $node = $xpath->query($seletor, $contexto);

    if (!$node || $node->length === 0) {
        return "";
    }

    $url = getAtributoFallback($node->item(0), [
        "href",
        "src",
        "data-src",
        "data-img",
        "data-thumb",
        "data-lazy-src",
        "data-original",
        "data-full",
        "data-image",
        "data-large",
        "srcset",
        "data-srcset",
        "style"
    ]);

    return urlAbsoluta($url, $baseUrl);
}

/**
 * PEGAR META CONTENT
 */
function getMetaContent($xpath, $queries) {

    foreach ($queries as $query) {

        $node = $xpath->query($query);

        if ($node && $node->length > 0) {

            $content = limpar($node->item(0)->getAttribute("content"));

            if ($content !== "") {
                return $content;
            }
        }
    }

    return "";
}

/**
 * PEGAR OG, DESCRIÇÃO E GALERIA DA URL DO CARD
 */
function getDadosInternos($urlCard, $selectorGaleria = "", $selectorDescricao = "") {

    global $limiteImagensGaleria;

    $dados = [
        "og_title" => "",
        "og_image" => "",
        "og_description" => "",
        "og_status" => "",
        "galeria" => "",
        "descricao" => ""
    ];

    if (empty($urlCard)) {
        $dados["og_status"] = "sem_card_url";
        return $dados;
    }

    $resposta = getHtml($urlCard);

    if (!$resposta["ok"]) {

        $dados["og_status"] = "erro_http_" . $resposta["http_code"];

        if (!empty($resposta["erro"])) {
            $dados["og_status"] .= " - " . $resposta["erro"];
        }

        return $dados;
    }

    $xpath = criarXpath($resposta["html"]);

    $dados["og_title"] = getMetaContent($xpath, [
        "//meta[@property='og:title']",
        "//meta[@name='twitter:title']"
    ]);

    if ($dados["og_title"] === "") {

        $titleNode = $xpath->query("//title");

        if ($titleNode && $titleNode->length > 0) {
            $dados["og_title"] = limpar($titleNode->item(0)->textContent);
        }
    }

    $dados["og_image"] = getMetaContent($xpath, [
        "//meta[@property='og:image']",
        "//meta[@property='og:image:url']",
        "//meta[@name='twitter:image']"
    ]);

    if ($dados["og_image"] !== "") {
        $dados["og_image"] = urlAbsoluta($dados["og_image"], $urlCard);
        $dados["og_image"] = baixarImagemParaWpAllImport($dados["og_image"], $urlCard);
    }

    $dados["og_description"] = getMetaContent($xpath, [
        "//meta[@property='og:description']",
        "//meta[@name='description']",
        "//meta[@name='twitter:description']"
    ]);

    if (!empty($selectorDescricao)) {

        $descricaoNode = $xpath->query($selectorDescricao);

        if ($descricaoNode && $descricaoNode->length > 0) {

            $descricaoHtml = getInnerHtml($descricaoNode->item(0));

            $dados["descricao"] = limparDescricaoHtmlPermitida($descricaoHtml);
        }
    }

    if (!empty($selectorGaleria)) {

        $imagens = [];

        $nodesGaleria = $xpath->query($selectorGaleria);

        if ($nodesGaleria && $nodesGaleria->length > 0) {

            foreach ($nodesGaleria as $imgNode) {

                $imgUrl = getAtributoFallback($imgNode, [
                    "src",
                    "data-src",
                    "data-img",
                    "data-thumb",
                    "data-lazy-src",
                    "data-original",
                    "data-full",
                    "data-image",
                    "data-large",
                    "href",
                    "srcset",
                    "data-srcset",
                    "style"
                ]);

                $imgUrl = urlAbsoluta($imgUrl, $urlCard);
                $imgUrl = baixarImagemParaWpAllImport($imgUrl, $urlCard);

                if (!empty($imgUrl) && !in_array($imgUrl, $imagens)) {
                    $imagens[] = $imgUrl;

                    if (!empty($limiteImagensGaleria) && count($imagens) >= (int)$limiteImagensGaleria) {
                        break;
                    }
                }
            }
        }

        if (!empty($imagens)) {
            $dados["galeria"] = implode(",", $imagens);
        }
    }

    $dados["og_status"] = "ok";

    return $dados;
}

/**
 * GERAR CHAVE ÚNICA DO REGISTRO
 */
function gerarChaveRegistro($item) {

    $cardUrl = trim($item["card_url"] ?? "");

    if ($cardUrl !== "") {
        return md5(mb_strtolower($cardUrl, "UTF-8"));
    }

    return md5(
        mb_strtolower(
            ($item["nome_site"] ?? "") . "|" .
            ($item["card_nome"] ?? "") . "|" .
            ($item["preco"] ?? ""),
            "UTF-8"
        )
    );
}

/**
 * LER CSV EXISTENTE
 *
 * Lê o CSV usando o cabeçalho real do arquivo.
 * Isso evita desalinhamento quando colunas forem renomeadas,
 * removidas ou adicionadas.
 */
function lerCsvExistente($arquivoCsv, $colunas) {

    $registros = [];

    if (!file_exists($arquivoCsv)) {
        return $registros;
    }

    $fp = fopen($arquivoCsv, "r");

    if (!$fp) {
        return $registros;
    }

    $cabecalho = fgetcsv($fp, 0, ";");

    if (!$cabecalho) {
        fclose($fp);
        return $registros;
    }

    if (isset($cabecalho[0])) {
        $cabecalho[0] = preg_replace('/^\xEF\xBB\xBF/', '', $cabecalho[0]);
    }

    $cabecalho = array_map(function ($coluna) {
        return trim((string)$coluna);
    }, $cabecalho);

    while (($linha = fgetcsv($fp, 0, ";")) !== false) {

        $item = [];

        foreach ($cabecalho as $index => $nomeColuna) {
            if ($nomeColuna !== "") {
                $item[$nomeColuna] = $linha[$index] ?? "";
            }
        }

        /**
         * Compatibilidade com CSV antigo.
         */
        if (!isset($item["negociacao"]) && isset($item["status_imovel"])) {
            $item["negociacao"] = $item["status_imovel"];
        }

        if (!isset($item["contato_fone"]) && isset($item["contato"])) {
            $item["contato_fone"] = $item["contato"];
        }

        if (!isset($item["card_area_contruida"]) && isset($item["card_area2"])) {
            $item["card_area_contruida"] = $item["card_area2"];
        }

        if (!isset($item["data_expiracao"]) && isset($item["data_periodo_timestamp"])) {
            $item["data_expiracao"] = $item["data_periodo_timestamp"];
        }

        if (isset($item["data_expiracao"])) {
            $item["data_expiracao"] = normalizarDataExpiracaoCsv($item["data_expiracao"]);
        }

        if (!isset($item["contato_nome"])) {
            $item["contato_nome"] = "";
        }

        foreach ($colunas as $coluna) {
            if (!array_key_exists($coluna, $item)) {
                $item[$coluna] = "";
            }
        }

        $registros[] = $item;
    }

    fclose($fp);

    return $registros;
}

/**
 * MESCLAR REGISTROS SEM DUPLICAR E LIMITAR TOTAL
 */
function mesclarRegistrosLimitados($registrosAntigos, $registrosNovos, $limite) {

    $resultado = [];

    /**
     * Primeiro carrega os registros antigos.
     * Assim conseguimos preservar a data da primeira captura.
     */
    foreach ($registrosAntigos as $item) {
        $chave = gerarChaveRegistro($item);
        $resultado[$chave] = $item;
    }

    /**
     * Depois aplica os registros novos.
     * Se já existir, mantém data_primeiro_* antiga
     * e atualiza apenas data_ultimo_*.
     */
    foreach ($registrosNovos as $item) {

        $chave = gerarChaveRegistro($item);
        $periodoDias = (int)($item["_periodo_dias"] ?? 0);

        if (isset($resultado[$chave])) {

            $item["data_primeiro_scraper_brasil"] =
                $resultado[$chave]["data_primeiro_scraper_brasil"] ?? ($resultado[$chave]["data_scraper_brasil"] ?? $item["data_primeiro_scraper_brasil"] ?? "");

            $item["data_primeiro_scraper_eua"] =
                $resultado[$chave]["data_primeiro_scraper_eua"] ?? ($resultado[$chave]["data_scraper_eua"] ?? $item["data_primeiro_scraper_eua"] ?? "");
        }

        $item["data_ultimo_scraper_brasil"] = date("d/m/Y H:i:s");
        $item["data_ultimo_scraper_eua"] = date("Y-m-d H:i:s");
        $item["data_expiracao"] = gerarDataPeriodoTimestamp($item["data_primeiro_scraper_eua"] ?? "", $periodoDias);

        unset($item["_periodo_dias"]);

        $resultado[$chave] = $item;
    }

    return array_slice(array_values($resultado), 0, $limite);
}


/**
 * VALIDAR EXTENSÕES OBRIGATÓRIAS DO PHP
 *
 * Evita tela branca/erro fatal quando o servidor não tem alguma extensão ativa.
 */
function validarExtensoesObrigatoriasScraper() {

    $faltando = [];

    if (!function_exists("curl_init")) {
        $faltando[] = "curl";
    }

    if (!class_exists("DOMDocument") || !class_exists("DOMXPath")) {
        $faltando[] = "dom/xml";
    }

    if (empty($faltando)) {
        return;
    }

    header("Content-Type: application/json; charset=utf-8");

    echo json_encode([
        "status" => "error",
        "mensagem" => "Extensões obrigatórias do PHP não estão ativas no servidor.",
        "extensoes_faltando" => $faltando,
        "orientacao" => "Ative as extensões PHP informadas no cPanel/HostGator ou no php.ini."
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}


/**
 * FUNÇÕES DE E-MAIL PARA NOVO IMÓVEL
 *
 * As funções de notificação ficam separadas para facilitar manutenção.
 */
require_once __DIR__ . "/scraper-email.php";

validarExtensoesObrigatoriasScraper();

/**
 * PROCESSAMENTO
 */
$resultados = [];
$logs = [];
$sitesExecutadosScraper = [];
$sitesIgnoradosPorFrequencia = [];

foreach ($sites as $site) {

    $nomeSite = $site["nome_site"] ?? "";
    $contatoNome = $site["contato_nome"] ?? "";
    $usuario = $site["usuario"] ?? "";
    $usuarioEmail = $site["usuario_email"] ?? "";
    $cidade = $site["cidade"] ?? "";
    $uf = $site["uf"] ?? "";

    $categoria = normalizarListaVirgula($site["categoria"] ?? "");
    $tags = normalizarListaVirgula($site["tags"] ?? "");

    $contatoFone = $site["contato_fone"] ?? ($site["contato"] ?? "");
    $contatoWhatsapp = $site["contato_whatsapp"] ?? "";
    $contatoInstagram = $site["contato_instagram"] ?? "";
    $contatoDesc = $site["contato_desc"] ?? "";

    $periodo = (int)($site["periodo"] ?? 0);

    $urlsSite = normalizarUrlsSite($site["url"] ?? "");
    $urlPrincipal = $urlsSite[0] ?? "";
    $contatoSite = getUrlPrincipalSemBarra($urlPrincipal);

    $numeroRegistros = (int)($site["numero_registros"] ?? 0);

    /**
     * LIMITE MÁXIMO DE REGISTROS POR URL
     *
     * Se for maior que zero, limita quantos imóveis serão salvos por cada URL
     * do mesmo site. Exemplo: 4 URLs x 10 por URL = até 40 registros.
     *
     * Se não existir ou for zero, não limita por URL.
     */
    $numeroMaximoPorUrl = (int)($site["numero_maximo_por_url"] ?? 0);

    $seletores = $site["seletores"] ?? [];

    $frequencia = $site["frequencia"] ?? [
        "tipo" => "sempre"
    ];

    $verificarString = $site["verificar_string"] ?? "";

    $infoExecucaoSite = [
        "nome_site" => $nomeSite,
        "usuario" => $usuario,
        "usuario_email" => $usuarioEmail,
        "cidade" => $cidade,
        "uf" => $uf,
        "tipo_frequencia" => $frequencia["tipo"] ?? "sempre",
        "horario_inicio" => $frequencia["horario_inicio"] ?? "",
        "horario_fim" => $frequencia["horario_fim"] ?? "",
        "horario_atual" => date("H:i")
    ];

    if (!deveRodarAgora($frequencia)) {

        $sitesIgnoradosPorFrequencia[] = array_merge($infoExecucaoSite, [
            "status" => "ignorado_por_frequencia"
        ]);

        $logs[] = array_merge($infoExecucaoSite, [
            "categoria" => $categoria,
            "tags" => $tags,
            "url" => $urlPrincipal,
            "status" => "ignorado_por_frequencia"
        ]);

        continue;
    }

    $sitesExecutadosScraper[] = array_merge($infoExecucaoSite, [
        "status" => "executado"
    ]);

    if (empty($urlsSite)) {

        $logs[] = [
            "nome_site" => $nomeSite,
            "usuario" => $usuario,
            "usuario_email" => $usuarioEmail,
            "cidade" => $cidade,
            "uf" => $uf,
            "categoria" => $categoria,
            "tags" => $tags,
            "url" => "",
            "status" => "url_vazia"
        ];

        continue;
    }

    $contador = 0;
    $ignoradosPorString = 0;
    $registrosInvalidos = 0;
    $cardsEncontradosTotal = 0;
    $registrosPorUrl = [];

    foreach ($urlsSite as $url) {

        $contadorPorUrl = 0;
        $registrosPorUrl[$url] = 0;

        $resposta = getHtml($url);

        if (!$resposta["ok"]) {

            $logs[] = [
                "nome_site" => $nomeSite,
                "usuario" => $usuario,
                "usuario_email" => $usuarioEmail,
                "cidade" => $cidade,
                "uf" => $uf,
                "categoria" => $categoria,
                "tags" => $tags,
                "url" => $url,
                "status" => "erro_http",
                "http_code" => $resposta["http_code"],
                "erro" => $resposta["erro"]
            ];

            continue;
        }

        $xpath = criarXpath($resposta["html"]);

        $selectorCard = $seletores["card"] ?? "";

        if (empty($selectorCard)) {

            $logs[] = [
                "nome_site" => $nomeSite,
                "usuario" => $usuario,
                "usuario_email" => $usuarioEmail,
                "cidade" => $cidade,
                "uf" => $uf,
                "categoria" => $categoria,
                "tags" => $tags,
                "url" => $url,
                "status" => "selector_card_vazio"
            ];

            continue;
        }

        $cards = $xpath->query($selectorCard);

        if (!$cards || $cards->length === 0) {

            $logs[] = [
                "nome_site" => $nomeSite,
                "usuario" => $usuario,
                "usuario_email" => $usuarioEmail,
                "cidade" => $cidade,
                "uf" => $uf,
                "categoria" => $categoria,
                "tags" => $tags,
                "url" => $url,
                "status" => "sem_cards"
            ];

            continue;
        }

        $cardsEncontradosTotal += $cards->length;

        foreach ($cards as $card) {

            if ($numeroRegistros > 0 && $contador >= $numeroRegistros) {
                break 2;
            }

            if ($numeroMaximoPorUrl > 0 && $contadorPorUrl >= $numeroMaximoPorUrl) {
                break;
            }

            $cardNome = getTextoSeletor(
                $xpath,
                $card,
                $seletores["card_nome"] ?? ""
            );

            $cidadeFinal = $cidade;
            $ufFinal = $uf;

            $cardContatoNome = "";

            if (empty($contatoNome)) {
                $cardContatoNome = getTextoSeletor(
                    $xpath,
                    $card,
                    $seletores["card_contato_nome"] ?? ""
                );
            }

            $contatoNomeFinal = !empty($contatoNome) ? $contatoNome : $cardContatoNome;

            $cardContatoWhatsapp = "";

            if (empty($contatoWhatsapp)) {
                $cardContatoWhatsapp = getTextoSeletor(
                    $xpath,
                    $card,
                    $seletores["card_contato_whatsapp"] ?? ""
                );
            }

            $contatoWhatsappFinal = !empty($contatoWhatsapp) ? $contatoWhatsapp : $cardContatoWhatsapp;

            $cardContato = "";

            if (empty($contatoFone)) {
                $cardContato = getTextoSeletor(
                    $xpath,
                    $card,
                    $seletores["card_contato"] ?? ""
                );
            }

            $contatoFoneFinal = !empty($contatoFone) ? $contatoFone : $cardContato;

            $cardLocalizacaoOriginal = getTextoSeletor(
                $xpath,
                $card,
                $seletores["card_localizacao"] ?? ""
            );

            /**
             * IMPORTANTE:
             * card_localizacao fica somente com o valor encontrado pelo seletor.
             * Se o seletor não encontrar nada, permanece vazio.
             */
            $cardLocalizacao = $cardLocalizacaoOriginal;

            $cardArea = getTextoSeletor(
                $xpath,
                $card,
                $seletores["card_area"] ?? ""
            );

            $cardAreaContruida = getTextoSeletor(
                $xpath,
                $card,
                $seletores["card_area_contruida"] ?? ""
            );

            $categoriaImovel = definirCategoriaImovel(
                $cardNome,
                $categoriaImovelRegras
            );

            $precoOriginal = getTextoSeletor(
                $xpath,
                $card,
                $seletores["preco"] ?? ""
            );

            $preco = normalizarPrecoInteiro($precoOriginal);

            $cardImagemUrl = getUrlSeletor(
                $xpath,
                $card,
                $seletores["card_imagem_url"] ?? "",
                $url
            );

            $cardImagemUrl = baixarImagemParaWpAllImport($cardImagemUrl, $url);

            $cardUrl = getUrlSeletor(
                $xpath,
                $card,
                $seletores["card_url"] ?? "",
                $url
            );

            if (empty($cardNome) && empty($cardUrl)) {
                continue;
            }

            if (!deveSalvarPorString($cardNome, $verificarString)) {
                $ignoradosPorString++;
                continue;
            }

            $dadosInternos = getDadosInternos(
                $cardUrl,
                $seletores["galeria"] ?? "",
                $seletores["descricao"] ?? ""
            );

            $galeria = $dadosInternos["galeria"];

            if (empty($galeria)) {
                $galeria = $cardImagemUrl;
            }

            $descricao = $dadosInternos["descricao"] ?? "";

            /**
             * Limpa a descrição antes de salvar no array de resultados.
             * Assim o CSV e também o JSON de retorno ficam sem ponto e vírgula.
             */
            $descricao = limparDescricaoCsv($descricao);

            /**
             * cidade_sugerida e uf_sugerido são calculados usando:
             * card_localizacao_original + descricao + og_title.
             *
             * Não usa o fallback de card_localizacao para evitar falso positivo.
             */
            $ogTitle = $dadosInternos["og_title"] ?? "";

            $cidadeUfSugerida = sugerirCidadeUfImovel(
                $descricao,
                $ogTitle,
                $cardLocalizacaoOriginal
            );

            $cidadeSugerida = $cidadeUfSugerida["cidade"] ?? "";
            $ufSugerido = $cidadeUfSugerida["uf"] ?? "";
            $cidadeConfianca = (int)($cidadeUfSugerida["confianca"] ?? 0);

            if (empty($cidadeFinal) && !empty($cidadeSugerida)) {
                $cidadeFinal = $cidadeSugerida;
            }

            if (empty($ufFinal) && !empty($ufSugerido)) {
                $ufFinal = $ufSugerido;
            }

            /**
             * Sem fallback para card_localizacao.
             * O campo mantém somente o que foi encontrado no site.
             */

            $negociacao = definirStatusImovel(
                $cardNome,
                $descricao,
                $StatusImovelRegras
            );

            /**
             * VALIDAÇÃO DO REGISTRO
             *
             * Se faltar nome, preço, galeria ou categoria do imóvel,
             * o registro é considerado inválido e não é salvo no CSV.
             */
            $motivosInvalidos = [];

            if (trim((string)$cardNome) === "") {
                $motivosInvalidos[] = "nome_vazio";
            }

            if (trim((string)$preco) === "") {
                $motivosInvalidos[] = "preco_vazio";
            }

            if (trim((string)$galeria) === "") {
                $motivosInvalidos[] = "galeria_vazia";
            }

            if (trim((string)$categoriaImovel) === "") {
                $motivosInvalidos[] = "categoria_imovel_vazia";
            }

            if (!empty($motivosInvalidos)) {

                $registrosInvalidos++;

                $logs[] = [
                    "nome_site" => $nomeSite,
                    "usuario" => $usuario,
                    "usuario_email" => $usuarioEmail,
                    "cidade" => $cidadeFinal,
                    "uf" => $ufFinal,
                    "url" => $url,
                    "status" => "registro_invalido",
                    "motivos" => $motivosInvalidos,
                    "card_nome" => $cardNome,
                    "card_url" => $cardUrl,
                    "preco" => $preco,
                    "galeria" => $galeria,
                    "categoria_imovel" => $categoriaImovel,
                    "cidade_sugerida" => $cidadeSugerida,
                    "uf_sugerido" => $ufSugerido,
                    "cidade_sugerida_regra" => $cidadeUfSugerida["regra"] ?? null,
                    "cidade_sugerida_trecho" => $cidadeUfSugerida["trecho"] ?? null,
                    "cidade_confianca" => $cidadeConfianca
                ];

                continue;
            }

            $hash = md5(
                mb_strtolower(
                    $nomeSite . "|" .
                    $contatoNomeFinal . "|" .
                    $usuario . "|" .
                    $cidadeFinal . "|" .
                    $ufFinal . "|" .
                    $cidadeSugerida . "|" .
                    $ufSugerido . "|" .
                    $categoria . "|" .
                    $tags . "|" .
                    $categoriaImovel . "|" .
                    $negociacao . "|" .
                    $contatoFoneFinal . "|" .
                    $contatoWhatsappFinal . "|" .
                    $contatoInstagram . "|" .
                    $contatoDesc . "|" .
                    $contatoSite . "|" .
                    $periodo . "|" .
                    $cardNome . "|" .
                    $cardLocalizacao . "|" .
                    $cardArea . "|" .
                    $cardAreaContruida . "|" .
                    $preco . "|" .
                    $cardUrl,
                    "UTF-8"
                )
            );

            if (isset($resultados[$hash])) {
                continue;
            }

            $resultados[$hash] = [
                "nome_site" => $nomeSite,
                "contato_nome" => $contatoNomeFinal,
                "usuario" => $usuario,
                "cidade" => $cidadeFinal,
                "uf" => $ufFinal,
                "categoria" => $categoria,
                "tags" => $tags,
                "categoria_imovel" => $categoriaImovel,
                "negociacao" => $negociacao,

                "contato_fone" => $contatoFoneFinal,
                "contato_whatsapp" => $contatoWhatsappFinal,
                "contato_instagram" => $contatoInstagram,
                "contato_site" => $contatoSite,
                "contato_desc" => $contatoDesc,

                "card_nome" => $cardNome,
                "card_localizacao" => $cardLocalizacao,
                "card_area" => $cardArea,
                "card_area_contruida" => $cardAreaContruida,
                "descricao" => $descricao,
                "preco" => $preco,
                "card_imagem_url" => $cardImagemUrl,
                "card_url" => $cardUrl,

                "og_title" => $dadosInternos["og_title"],
                "og_image" => $dadosInternos["og_image"],
                "og_description" => $dadosInternos["og_description"],
                "og_status" => $dadosInternos["og_status"],
                "galeria" => $galeria,

                "data_primeiro_scraper_brasil" => date("d/m/Y H:i:s"),
                "data_primeiro_scraper_eua" => date("Y-m-d H:i:s"),

                "data_ultimo_scraper_brasil" => date("d/m/Y H:i:s"),
                "data_ultimo_scraper_eua" => date("Y-m-d H:i:s"),

                "data_expiracao" => gerarDataPeriodoTimestamp(date("Y-m-d H:i:s"), $periodo),

                "cidade_sugerida" => $cidadeSugerida,
                "uf_sugerido" => $ufSugerido,
                "cidade_confianca" => $cidadeConfianca,
                "usuario_email" => $usuarioEmail,

                "_periodo_dias" => $periodo
            ];

            $contador++;
            $contadorPorUrl++;
            $registrosPorUrl[$url] = $contadorPorUrl;

            usleep(rand(400000, 1200000));
        }
    }

    $logs[] = [
        "nome_site" => $nomeSite,
        "usuario" => $usuario,
        "usuario_email" => $usuarioEmail,
        "cidade" => $cidade,
        "uf" => $uf,
        "categoria" => $categoria,
        "tags" => $tags,
        "url" => $urlPrincipal,
        "status" => "ok",
        "cards_encontrados" => $cardsEncontradosTotal,
        "numero_registros" => $numeroRegistros,
        "numero_maximo_por_url" => $numeroMaximoPorUrl,
        "registros_salvos" => $contador,
        "registros_invalidos" => $registrosInvalidos,
        "registros_por_url" => $registrosPorUrl,
        "ignorados_por_string" => $ignoradosPorString
    ];
}


/**
 * LIMPAR CAMPO CSV PADRÃO
 *
 * Usado em campos comuns para evitar quebra de linha real,
 * ponto e vírgula interno e espaços duplicados no CSV.
 */
function limparCampoCsv($texto) {

    $texto = html_entity_decode($texto ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Remove quebras reais para manter 1 imóvel por linha no CSV
    $texto = str_replace(["\r\n", "\r", "\n"], ' ', $texto);

    // Evita conflito visual com separador CSV ;
    $texto = str_replace(';', ',', $texto);

    // Remove espaços duplicados
    $texto = preg_replace('/\s+/', ' ', $texto);

    return trim($texto);
}

/**
 * LIMPAR DESCRIÇÃO PARA CSV / WP ALL IMPORT
 *
 * A descrição pode conter HTML permitido, então não deve usar
 * a limpeza genérica. Mantém somente:
 * h4.desc-title, ul, li, b e br.
 */
function limparDescricaoCsv($html) {

    $html = (string)($html ?? "");

    if ($html === "") {
        return "";
    }

    // Decodifica entidades HTML
    $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, "UTF-8");

    // Normaliza quebras reais para <br/>
    $html = str_replace(["\r\n", "\r", "\n"], "<br/>", $html);

    // Normaliza variações de <br>
    $html = preg_replace('/<br\s*\/?>/i', '<br/>', $html);

    /**
     * IMPORTANTE:
     * Remove ponto e vírgula da descrição para não quebrar CSV separado por ;
     *
     * Exemplo:
     * Área total 1.401,6027 hectares;
     * vira:
     * Área total 1.401,6027 hectares<br/>
     */
    $html = str_replace([";", "；"], "<br/>", $html);

    // Remove atributos extras das tags permitidas
    $html = preg_replace('/<\s*h4\b[^>]*>/i', "<h4 class='desc-title'>", $html);
    $html = preg_replace('/<\s*\/\s*h4\s*>/i', '</h4>', $html);
    $html = preg_replace('/<\s*ul\s+[^>]*>/i', '<ul>', $html);
    $html = preg_replace('/<\s*li\s+[^>]*>/i', '<li>', $html);
    $html = preg_replace('/<\s*b\s+[^>]*>/i', '<b>', $html);

    // Mantém somente estas tags
    $html = strip_tags($html, '<h4><ul><li><b><br>');

    // Garante <br/> novamente depois do strip_tags
    $html = preg_replace('/<br\s*\/?>/i', '<br/>', $html);

    // Remove espaços duplicados
    $html = preg_replace('/\s+/', ' ', $html);

    // Remove <br/> repetidos
    $html = preg_replace('/(<br\/>\s*){2,}/i', '<br/>', $html);

    // Limpa espaços perto das tags
    $html = preg_replace('/\s*<br\/>\s*/i', '<br/>', $html);
    $html = preg_replace('/\s*<h4\s+class=[\'\"]desc-title[\'\"]>\s*/i', "<h4 class='desc-title'>", $html);
    $html = preg_replace('/\s*<\/h4>\s*/i', '</h4>', $html);
    $html = preg_replace('/\s*<li>\s*/i', '<li>', $html);
    $html = preg_replace('/\s*<\/li>\s*/i', '</li>', $html);
    $html = preg_replace('/\s*<ul>\s*/i', '<ul>', $html);
    $html = preg_replace('/\s*<\/ul>\s*/i', '</ul>', $html);

    // Remove <br/> sobrando no início/fim
    $html = preg_replace('/^(<br\/>)+/i', '', $html);
    $html = preg_replace('/(<br\/>)+$/i', '', $html);

    return trim($html);
}


/**
 * GERAR REGISTROS DO CSV DE USUÁRIOS
 *
 * Gera o arquivo scraper-users.csv com dados exclusivos do responsável/contato
 * configurado em cada site.
 */
function gerarRegistrosUsuariosSites($sites) {

    $registros = [];

    if (empty($sites) || !is_array($sites)) {
        return $registros;
    }

    foreach ($sites as $site) {

        $urlsSite = normalizarUrlsSite($site["url"] ?? "");
        $urlPrincipal = $urlsSite[0] ?? "";
        $contatoSite = getUrlPrincipalSemBarra($urlPrincipal);

        $contatoNome = $site["contato_nome"] ?? ($site["nome_site"] ?? "");
        $usuario = $site["usuario"] ?? "";
        $usuarioEmail = $site["usuario_email"] ?? "";
        $contatoFone = $site["contato_fone"] ?? ($site["contato"] ?? "");
        $contatoWhatsapp = $site["contato_whatsapp"] ?? "";
        $contatoInstagram = $site["contato_instagram"] ?? "";
        $contatoDesc = $site["contato_desc"] ?? "";

        $item = [
            "contato_nome" => $contatoNome,
            "usuario" => $usuario,
            "usuario_email" => $usuarioEmail,
            "contato_fone" => $contatoFone,
            "contato_whatsapp" => $contatoWhatsapp,
            "contato_instagram" => $contatoInstagram,
            "contato_desc" => $contatoDesc,
            "contato_site" => $contatoSite
        ];

        /**
         * Evita registros duplicados no scraper-users.csv.
         */
        $chave = md5(
            mb_strtolower(
                ($item["contato_nome"] ?? "") . "|" .
                ($item["usuario"] ?? "") . "|" .
                ($item["usuario_email"] ?? "") . "|" .
                ($item["contato_fone"] ?? "") . "|" .
                ($item["contato_whatsapp"] ?? "") . "|" .
                ($item["contato_site"] ?? ""),
                "UTF-8"
            )
        );

        $registros[$chave] = $item;
    }

    return array_values($registros);
}

/**
 * GRAVAR CSV SIMPLES
 */
function gravarCsvSimples($arquivoCsv, $colunas, $registros) {

    $fp = fopen($arquivoCsv, "w");

    if (!$fp) {
        return false;
    }

    fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($fp, $colunas, ";");

    foreach ($registros as $item) {

        $linha = [];

        foreach ($colunas as $coluna) {
            $linha[] = limparCampoCsv($item[$coluna] ?? "");
        }

        fputcsv($fp, $linha, ";");
    }

    fclose($fp);

    return true;
}

/**
 * COLUNAS DO CSV
 */
$colunas = [
    "nome_site",
    "contato_nome",
    "usuario",
    "cidade",
    "uf",
    "categoria",
    "tags",
    "categoria_imovel",
    "negociacao",

    "contato_fone",
    "contato_whatsapp",
    "contato_instagram",
    "contato_site",
    "contato_desc",

    "card_nome",
    "card_localizacao",
    "card_area",
    "card_area_contruida",
    "descricao",
    "preco",
    "card_imagem_url",
    "card_url",

    "og_title",
    "og_image",
    "og_description",
    "og_status",
    "galeria",

    "data_primeiro_scraper_brasil",
    "data_primeiro_scraper_eua",

    "data_ultimo_scraper_brasil",
    "data_ultimo_scraper_eua",

    "data_expiracao",

    "cidade_sugerida",
    "uf_sugerido",
    "cidade_confianca",
    "usuario_email"
];


/**
 * COLUNAS DO CSV DE USUÁRIOS
 */
$colunasUsuarios = [
    "contato_nome",
    "usuario",
    "usuario_email",
    "contato_fone",
    "contato_whatsapp",
    "contato_instagram",
    "contato_desc",
    "contato_site"
];

$registrosUsuarios = gerarRegistrosUsuariosSites($sites);

/**
 * GRAVAR OU APENAS TESTAR SEM ALTERAR CSV
 */
$gravarCsvNormalizado = normalizarBusca($gravar_csv);
$novosImoveisCadastrados = [];
$logsEmailNovoImovel = [];
$logEmailResumoScraper = [];

if ($gravarCsvNormalizado === "sim") {

    $registrosAntigos = lerCsvExistente($arquivoCsv, $colunas);

    $registrosFinais = mesclarRegistrosLimitados(
        $registrosAntigos,
        array_values($resultados),
        $limiteRegistrosCsv
    );

    $novosImoveisCadastrados = filtrarImoveisNovosCadastrados(
        $registrosAntigos,
        $registrosFinais
    );

    /**
     * SALVAR CSV
     */
    $fp = fopen($arquivoCsv, "w");

    if (!$fp) {
        header("Content-Type: application/json; charset=utf-8");

        echo json_encode([
            "status" => "error",
            "mensagem" => "Não foi possível criar o arquivo CSV.",
            "arquivo_csv" => $arquivoCsv,
            "gravar_csv" => $gravar_csv
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }

    fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($fp, $colunas, ";");

    foreach ($registrosFinais as $item) {

        $linha = [];

        foreach ($colunas as $coluna) {

            $valor = $item[$coluna] ?? "";

            if ($coluna === "descricao") {
                $linha[] = limparDescricaoCsv($valor);
            } elseif ($coluna === "data_expiracao") {
                $linha[] = limparCampoCsv(normalizarDataExpiracaoCsv($valor));
            } else {
                $linha[] = limparCampoCsv($valor);
            }
        }

        fputcsv($fp, $linha, ";");
    }

    fclose($fp);

    $csvStatus = "gravado";

    $csvUsuariosGravado = gravarCsvSimples($arquivoCsvUsuarios, $colunasUsuarios, $registrosUsuarios);
    $csvUsuariosStatus = $csvUsuariosGravado ? "gravado" : "erro_gravacao";

    /**
     * ENVIAR NOTIFICAÇÃO POR E-MAIL PARA IMÓVEIS NOVOS
     *
     * O e-mail só é enviado depois que o scraper-res.csv foi gravado com sucesso.
     */
    $logsEmailNovoImovel = enviarEmailsNovosImoveisCadastrados(
        $novosImoveisCadastrados,
        $emailNotificacaoNovoImovel
    );

} else {

    /**
     * MODO TESTE
     *
     * Não lê nem grava o CSV.
     * Retorna apenas os resultados novos da execução atual.
     */
    $registrosFinais = array_values($resultados);
    $csvStatus = "nao_gravado_modo_teste";
    $csvUsuariosStatus = "nao_gravado_modo_teste";
}

/**
 * RETORNO JSON
 */
header("Content-Type: application/json; charset=utf-8");

$totalImagensBaixadas = count(array_filter($logsImagens, function ($item) {
    return ($item["status"] ?? "") === "baixada";
}));

$totalImagensJaExistiam = count(array_filter($logsImagens, function ($item) {
    return ($item["status"] ?? "") === "ja_existia";
}));

$totalErrosImagens = count(array_filter($logsImagens, function ($item) {
    return ($item["status"] ?? "") === "erro";
}));

$retornoJson = [
    "status" => "success",
    "arquivo_csv" => $arquivoCsv,
    "arquivo_csv_usuarios" => $arquivoCsvUsuarios,
    "gravar_csv" => $gravar_csv,
    "csv_status" => $csvStatus,
    "csv_usuarios_status" => $csvUsuariosStatus,
    "enviar_email_novo_imovel" => $enviarEmailNovoImovel,
    "email_notificacao_novo_imovel" => $emailNotificacaoNovoImovel,
    "enviar_email_resumo_scraper" => $enviarEmailResumoScraper,
    "email_notificacao_resumo_scraper" => $emailNotificacaoResumoScraper,
    "total_imoveis_cadastrados_novos" => count($novosImoveisCadastrados),
    "total_emails_novo_imovel" => count($logsEmailNovoImovel),
    "data_execucao" => date("d/m/Y H:i:s"),
    "horario_atual" => date("H:i"),
    "total_sites" => count($sites),
    "total_sites_executados_scraper" => count($sitesExecutadosScraper),
    "total_sites_ignorados_por_frequencia" => count($sitesIgnoradosPorFrequencia),
    "total_resultados_novos" => count($resultados),
    "total_resultados_csv" => count($registrosFinais),
    "total_usuarios_csv" => count($registrosUsuarios),
    "limite_registros_csv" => $limiteRegistrosCsv,
    "baixar_imagens" => $baixar_imagens,
    "exibir_log_imagens" => $exibir_log_imagens,
    "pasta_imagens_import" => $pastaImagensImport,
    "total_logs_imagens" => count($logsImagens),
    "total_imagens_baixadas" => $totalImagensBaixadas,
    "total_imagens_ja_existiam" => $totalImagensJaExistiam,
    "total_erros_imagens" => $totalErrosImagens,
    "sites_executados_scraper" => $sitesExecutadosScraper,
    "sites_ignorados_por_frequencia" => $sitesIgnoradosPorFrequencia,
    "logs" => $logs,
    "logs_email_novo_imovel" => $logsEmailNovoImovel,
    "resultado" => array_values($resultados),
    "resultado_usuarios" => $registrosUsuarios
];

if (normalizarBusca($exibir_log_imagens) === "sim") {
    $retornoJson["logs_imagens"] = $logsImagens;
}

/**
 * ENVIAR E-MAIL COM RESUMO DA EXECUÇÃO DO SCRAPER
 *
 * Dispara um resumo geral ao final de cada execução real do scraper.
 * Em modo teste ($gravar_csv = "nao"), a função registra como não enviado.
 */
$logEmailResumoScraper = enviarEmailResumoScraperRealizado(
    $retornoJson,
    $emailNotificacaoResumoScraper
);

$retornoJson["log_email_resumo_scraper"] = $logEmailResumoScraper;

echo json_encode($retornoJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

exit;