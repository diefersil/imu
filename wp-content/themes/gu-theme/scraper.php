<?php

ini_set('max_execution_time', 2000);
set_time_limit(2000);

date_default_timezone_set("America/Sao_Paulo");

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

            $itemUrl = trim((string)$itemUrl);

            if ($itemUrl !== "" && !in_array($itemUrl, $urls)) {
                $urls[] = $itemUrl;
            }
        }

        return $urls;
    }

    $url = trim((string)$url);

    if ($url === "") {
        return [];
    }

    return [$url];
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
     * Títulos h1-h6 viram negrito + <br/>
     * Exemplo:
     * <h2>Descrição</h2> => <b>Descrição</b><br/>
     */
    $html = preg_replace_callback('/<h[1-6]\b[^>]*>(.*?)<\/h[1-6]>/is', function ($match) {

        $titulo = trim(
            preg_replace('/\s+/', ' ', strip_tags($match[1]))
        );

        if ($titulo === "") {
            return "<br/>";
        }

        return "<b>" . $titulo . "</b><br/>";
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
     * Mantém somente ul e li, sem atributos.
     * O <b> é mantido apenas para os títulos convertidos.
     * O <br/> é mantido para preservar quebras no WP All Import.
     */
    $html = preg_replace('/<\s*ul\b[^>]*>/i', '<ul>', $html);
    $html = preg_replace('/<\s*\/\s*ul\s*>/i', '</ul>', $html);
    $html = preg_replace('/<\s*li\b[^>]*>/i', '<li>', $html);
    $html = preg_replace('/<\s*\/\s*li\s*>/i', '</li>', $html);

    // Remove todo HTML restante, deixando somente ul, li, b e br
    $html = strip_tags($html, '<ul><li><b><br>');

    // Normaliza qualquer variação de br para <br/>
    $html = preg_replace('/<br\s*\/?>/i', '<br/>', $html);

    // Remove espaços excessivos entre tags e texto
    $html = preg_replace('/[ \t]+/', ' ', $html);
    $html = preg_replace('/\s*<br\/>\s*/i', '<br/>', $html);
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
 * GERAR TIMESTAMP DO PERÍODO
 *
 * Calcula a expiração usando:
 * data_primeiro_scraper_eua + período em dias.
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

    return strtotime(date("Y-m-d H:i:s", $timestampBase) . " +" . $periodo . " days");
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

    while (($linha = fgetcsv($fp, 0, ";")) !== false) {

        $item = [];

        foreach ($colunas as $index => $coluna) {
            $item[$coluna] = $linha[$index] ?? "";
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
        $item["data_periodo_timestamp"] = gerarDataPeriodoTimestamp($item["data_primeiro_scraper_eua"] ?? "", $periodoDias);

        unset($item["_periodo_dias"]);

        $resultado[$chave] = $item;
    }

    return array_slice(array_values($resultado), 0, $limite);
}

/**
 * PROCESSAMENTO
 */
$resultados = [];
$logs = [];

foreach ($sites as $site) {

    $nomeSite = $site["nome_site"] ?? "";
    $usuario = $site["usuario"] ?? "";
    $cidade = $site["cidade"] ?? "";
    $uf = $site["uf"] ?? "";

    $categoria = normalizarListaVirgula($site["categoria"] ?? "");
    $tags = normalizarListaVirgula($site["tags"] ?? "");

    $contato = $site["contato"] ?? "";

    $periodo = (int)($site["periodo"] ?? 0);
    $dataPeriodoEua = gerarDataPeriodoEua($periodo);

    $urlsSite = normalizarUrlsSite($site["url"] ?? "");
    $urlPrincipal = $urlsSite[0] ?? "";

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

    if (!deveRodarAgora($frequencia)) {

        $logs[] = [
            "nome_site" => $nomeSite,
            "usuario" => $usuario,
            "cidade" => $cidade,
            "uf" => $uf,
            "categoria" => $categoria,
            "tags" => $tags,
            "url" => $urlPrincipal,
            "status" => "ignorado_por_frequencia",
            "horario_atual" => date("H:i")
        ];

        continue;
    }

    if (empty($urlsSite)) {

        $logs[] = [
            "nome_site" => $nomeSite,
            "usuario" => $usuario,
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

            $cardCidade = "";

            if (empty($cidade)) {
                $cardCidade = getTextoSeletor(
                    $xpath,
                    $card,
                    $seletores["card_cidade"] ?? ""
                );
            }

            $cidadeFinal = !empty($cidade) ? $cidade : $cardCidade;

            $cardUf = "";

            if (empty($uf)) {
                $cardUf = getTextoSeletor(
                    $xpath,
                    $card,
                    $seletores["card_uf"] ?? ""
                );
            }

            $ufFinal = !empty($uf) ? $uf : $cardUf;

            $cardContato = "";

            if (empty($contato)) {
                $cardContato = getTextoSeletor(
                    $xpath,
                    $card,
                    $seletores["card_contato"] ?? ""
                );
            }

            $contatoFinal = !empty($contato) ? $contato : $cardContato;

            $cardLocalizacao = getTextoSeletor(
                $xpath,
                $card,
                $seletores["card_localizacao"] ?? ""
            );

            /**
             * FALLBACK DA LOCALIZAÇÃO
             *
             * Se card_localizacao não for encontrado ou vier vazio,
             * monta com cidade e UF finais.
             */
            if (empty($cardLocalizacao)) {

                if (!empty($cidadeFinal) && !empty($ufFinal)) {
                    $cardLocalizacao = $cidadeFinal . ", " . $ufFinal;
                } elseif (!empty($cidadeFinal)) {
                    $cardLocalizacao = $cidadeFinal;
                } elseif (!empty($ufFinal)) {
                    $cardLocalizacao = $ufFinal;
                }
            }

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

            $statusImovel = definirStatusImovel(
                $cardNome,
                $descricao,
                $StatusImovelRegras
            );

            $hash = md5(
                mb_strtolower(
                    $nomeSite . "|" .
                    $usuario . "|" .
                    $cidadeFinal . "|" .
                    $ufFinal . "|" .
                    $categoria . "|" .
                    $tags . "|" .
                    $categoriaImovel . "|" .
                    $statusImovel . "|" .
                    $contatoFinal . "|" .
                    $periodo . "|" .
                    $cardNome . "|" .
                    $cardLocalizacao . "|" .
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
                "usuario" => $usuario,
                "cidade" => $cidadeFinal,
                "uf" => $ufFinal,
                "categoria" => $categoria,
                "tags" => $tags,
                "categoria_imovel" => $categoriaImovel,
                "status_imovel" => $statusImovel,

                "contato" => $contatoFinal,

                "data_periodo_eua" => $dataPeriodoEua,

                "url" => $url,

                "card_nome" => $cardNome,
                "card_localizacao" => $cardLocalizacao,
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

                "data_periodo_timestamp" => gerarDataPeriodoTimestamp(date("Y-m-d H:i:s"), $periodo),
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
 * ul, li, b e br.
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

    // Remove atributos das tags permitidas
    $html = preg_replace('/<\s*ul\s+[^>]*>/i', '<ul>', $html);
    $html = preg_replace('/<\s*li\s+[^>]*>/i', '<li>', $html);
    $html = preg_replace('/<\s*b\s+[^>]*>/i', '<b>', $html);

    // Mantém somente estas tags
    $html = strip_tags($html, '<ul><li><b><br>');

    // Garante <br/> novamente depois do strip_tags
    $html = preg_replace('/<br\s*\/?>/i', '<br/>', $html);

    // Remove espaços duplicados
    $html = preg_replace('/\s+/', ' ', $html);

    // Remove <br/> repetidos
    $html = preg_replace('/(<br\/>\s*){2,}/i', '<br/>', $html);

    // Limpa espaços perto das tags
    $html = preg_replace('/\s*<br\/>\s*/i', '<br/>', $html);
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
 * COLUNAS DO CSV
 */
$colunas = [
    "nome_site",
    "usuario",
    "cidade",
    "uf",
    "categoria",
    "tags",
    "categoria_imovel",
    "status_imovel",

    "contato",
    "data_periodo_eua",

    "url",

    "card_nome",
    "card_localizacao",
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

    "data_periodo_timestamp"
];

/**
 * GRAVAR OU APENAS TESTAR SEM ALTERAR CSV
 */
$gravarCsvNormalizado = normalizarBusca($gravar_csv);

if ($gravarCsvNormalizado === "sim") {

    $registrosAntigos = lerCsvExistente($arquivoCsv, $colunas);

    $registrosFinais = mesclarRegistrosLimitados(
        $registrosAntigos,
        array_values($resultados),
        $limiteRegistrosCsv
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
            } else {
                $linha[] = limparCampoCsv($valor);
            }
        }

        fputcsv($fp, $linha, ";");
    }

    fclose($fp);

    $csvStatus = "gravado";

} else {

    /**
     * MODO TESTE
     *
     * Não lê nem grava o CSV.
     * Retorna apenas os resultados novos da execução atual.
     */
    $registrosFinais = array_values($resultados);
    $csvStatus = "nao_gravado_modo_teste";
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
    "gravar_csv" => $gravar_csv,
    "csv_status" => $csvStatus,
    "data_execucao" => date("d/m/Y H:i:s"),
    "horario_atual" => date("H:i"),
    "total_sites" => count($sites),
    "total_resultados_novos" => count($resultados),
    "total_resultados_csv" => count($registrosFinais),
    "limite_registros_csv" => $limiteRegistrosCsv,
    "baixar_imagens" => $baixar_imagens,
    "exibir_log_imagens" => $exibir_log_imagens,
    "pasta_imagens_import" => $pastaImagensImport,
    "total_logs_imagens" => count($logsImagens),
    "total_imagens_baixadas" => $totalImagensBaixadas,
    "total_imagens_ja_existiam" => $totalImagensJaExistiam,
    "total_erros_imagens" => $totalErrosImagens,
    "logs" => $logs,
    "resultado" => array_values($resultados)
];

if (normalizarBusca($exibir_log_imagens) === "sim") {
    $retornoJson["logs_imagens"] = $logsImagens;
}

echo json_encode($retornoJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

exit;