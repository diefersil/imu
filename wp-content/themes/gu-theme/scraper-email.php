<?php

/**
 * FUNÇÕES DE E-MAIL PARA NOVO IMÓVEL
 *
 * Este arquivo deve ser chamado pelo scraper-res.php.
 * Ele concentra somente as funções responsáveis por:
 * - identificar imóveis novos em relação ao CSV antigo;
 * - montar o remetente;
 * - enviar e-mails de notificação para imóveis novos cadastrados.
 */



/**
 * CARREGAR WORDPRESS PARA USAR WP_MAIL
 *
 * Se o scraper estiver rodando fora do WordPress, tenta carregar wp-load.php
 * usando a raiz já detectada pelo scraper-res.php.
 */
function carregarWordPressParaEmailScraper() {

    if (function_exists("wp_mail")) {
        return true;
    }

    $possiveisCaminhos = [];

    if (!empty($GLOBALS["raizWordPress"])) {
        $possiveisCaminhos[] = rtrim((string)$GLOBALS["raizWordPress"], "/\\") . "/wp-load.php";
    }

    $possiveisCaminhos[] = __DIR__ . "/wp-load.php";
    $possiveisCaminhos[] = dirname(__DIR__) . "/wp-load.php";
    $possiveisCaminhos[] = dirname(__DIR__, 2) . "/wp-load.php";
    $possiveisCaminhos[] = dirname(__DIR__, 3) . "/wp-load.php";

    foreach ($possiveisCaminhos as $wpLoad) {

        if (is_string($wpLoad) && file_exists($wpLoad)) {
            require_once $wpLoad;
            break;
        }
    }

    return function_exists("wp_mail");
}

/**
 * ENVIAR E-MAIL DO SCRAPER
 */
function enviarEmailScraper($emailDestino, $assunto, $mensagem, $headersArray) {

    carregarWordPressParaEmailScraper();

    $assuntoEmail = $assunto;

    if (function_exists("mb_encode_mimeheader")) {
        $assuntoEmail = mb_encode_mimeheader($assunto, "UTF-8", "B", "\r\n");
    }

    if (function_exists("wp_mail")) {
        return wp_mail($emailDestino, $assuntoEmail, $mensagem, $headersArray);
    }

    return @mail($emailDestino, $assuntoEmail, $mensagem, implode("\r\n", $headersArray));
}


/**
 * FILTRAR IMÓVEIS REALMENTE NOVOS NO CSV
 *
 * Compara as chaves dos registros finais com as chaves do CSV antigo.
 * Assim o e-mail é disparado somente para imóvel que ainda não existia
 * e que entrou no arquivo scraper-res.csv nesta execução.
 */
function filtrarImoveisNovosCadastrados($registrosAntigos, $registrosFinais) {

    $chavesAntigas = [];

    foreach ($registrosAntigos as $itemAntigo) {
        $chavesAntigas[gerarChaveRegistro($itemAntigo)] = true;
    }

    $novos = [];
    $chavesNovas = [];

    foreach ($registrosFinais as $itemFinal) {

        $chave = gerarChaveRegistro($itemFinal);

        if (isset($chavesAntigas[$chave]) || isset($chavesNovas[$chave])) {
            continue;
        }

        $novos[] = $itemFinal;
        $chavesNovas[$chave] = true;
    }

    return $novos;
}

/**
 * DEFINIR E-MAIL REMETENTE DA NOTIFICAÇÃO
 */
function getEmailRemetenteNotificacaoNovoImovel($item) {

    $urlBase = trim((string)($item["contato_site"] ?? ""));

    if ($urlBase === "") {
        $urlBase = trim((string)($item["card_url"] ?? ""));
    }

    $host = "";

    if ($urlBase !== "") {
        $host = parse_url($urlBase, PHP_URL_HOST) ?: "";
    }

    if ($host === "" && function_exists("home_url")) {
        $host = parse_url(home_url("/"), PHP_URL_HOST) ?: "";
    }

    if ($host === "" && !empty($_SERVER["HTTP_HOST"])) {
        $host = $_SERVER["HTTP_HOST"];
    }

    $host = preg_replace('/^www\./i', '', trim((string)$host));
    $host = preg_replace('/[^a-z0-9.-]/i', '', $host);

    if ($host === "") {
        $host = "localhost.local";
    }

    return "nao-responda@" . $host;
}

/**
 * ENVIAR E-MAIL QUANDO UM IMÓVEL NOVO FOR CADASTRADO
 *
 * Envia um e-mail para cada imóvel realmente novo cadastrado no CSV.
 * O link enviado é o card_url capturado/gerado pelo scraper.
 */
function enviarEmailNovoImovelCadastrado($item, $emailDestino) {

    $emailDestino = trim((string)$emailDestino);

    if ($emailDestino === "") {
        return [
            "status" => "nao_enviado",
            "motivo" => "email_destino_vazio",
            "card_nome" => $item["card_nome"] ?? "",
            "card_url" => $item["card_url"] ?? ""
        ];
    }

    $cardNome = limpar($item["card_nome"] ?? "");
    $linkGerado = trim((string)($item["card_url"] ?? ""));

    if ($cardNome === "") {
        $cardNome = "Imóvel sem título";
    }

    $assunto = "Novo imóvel cadastrado: " . $cardNome;

    $mensagem = "Novo imóvel cadastrado no scraper.\n\n";
    $mensagem .= "Imóvel: " . $cardNome . "\n";
    $mensagem .= "Link gerado: " . ($linkGerado !== "" ? $linkGerado : "Sem link") . "\n";
    $mensagem .= "Site de origem: " . limpar($item["nome_site"] ?? "") . "\n";
    $mensagem .= "Data do cadastro: " . date("d/m/Y H:i:s") . "\n";

    $remetente = getEmailRemetenteNotificacaoNovoImovel($item);

    $headersArray = [
        "MIME-Version: 1.0",
        "Content-Type: text/plain; charset=UTF-8",
        "From: Scraper Imóveis <" . $remetente . ">"
    ];

    $assuntoEmail = $assunto;

    if (function_exists("mb_encode_mimeheader")) {
        $assuntoEmail = mb_encode_mimeheader($assunto, "UTF-8", "B", "\r\n");
    }

    if (function_exists("wp_mail")) {
        $enviado = wp_mail($emailDestino, $assuntoEmail, $mensagem, $headersArray);
    } else {
        $enviado = @mail($emailDestino, $assuntoEmail, $mensagem, implode("\r\n", $headersArray));
    }

    return [
        "status" => $enviado ? "enviado" : "erro_envio",
        "email_destino" => $emailDestino,
        "card_nome" => $cardNome,
        "card_url" => $linkGerado,
        "data" => date("d/m/Y H:i:s")
    ];
}

/**
 * ENVIAR E-MAILS DOS IMÓVEIS NOVOS
 */
function enviarEmailsNovosImoveisCadastrados($imoveisNovos, $emailDestino) {

    global $enviarEmailNovoImovel;

    $logsEmail = [];

    if (normalizarBusca($enviarEmailNovoImovel) !== "sim") {

        foreach ($imoveisNovos as $item) {
            $logsEmail[] = [
                "status" => "nao_enviado",
                "motivo" => "envio_desativado",
                "card_nome" => $item["card_nome"] ?? "",
                "card_url" => $item["card_url"] ?? ""
            ];
        }

        return $logsEmail;
    }

    foreach ($imoveisNovos as $item) {
        $logsEmail[] = enviarEmailNovoImovelCadastrado($item, $emailDestino);
    }

    return $logsEmail;
}


/**
 * CONTAR STATUS DOS LOGS DO SCRAPER
 */
function contarStatusLogsScraper($logs) {

    $contagem = [];

    if (empty($logs) || !is_array($logs)) {
        return $contagem;
    }

    foreach ($logs as $item) {

        $status = limpar($item["status"] ?? "sem_status");

        if ($status === "") {
            $status = "sem_status";
        }

        if (!isset($contagem[$status])) {
            $contagem[$status] = 0;
        }

        $contagem[$status]++;
    }

    ksort($contagem);

    return $contagem;
}

/**
 * CONTAR RESULTADOS POR SITE
 */
function contarResultadosPorSiteScraper($resultados) {

    $contagem = [];

    if (empty($resultados) || !is_array($resultados)) {
        return $contagem;
    }

    foreach ($resultados as $item) {

        $nomeSite = limpar($item["nome_site"] ?? "Site não informado");

        if ($nomeSite === "") {
            $nomeSite = "Site não informado";
        }

        if (!isset($contagem[$nomeSite])) {
            $contagem[$nomeSite] = 0;
        }

        $contagem[$nomeSite]++;
    }

    arsort($contagem);

    return $contagem;
}

/**
 * FORMATAR LISTA SIMPLES PARA O E-MAIL DE RESUMO
 */
function formatarListaResumoEmail($titulo, $itens) {

    $texto = $titulo . "\n";

    if (empty($itens) || !is_array($itens)) {
        return $texto . "- Nenhum registro\n";
    }

    foreach ($itens as $nome => $total) {
        $texto .= "- " . $nome . ": " . (int)$total . "\n";
    }

    return $texto;
}


/**
 * FORMATAR SITES QUE RODARAM OU FORAM IGNORADOS NO RESUMO
 */
function formatarSitesExecucaoResumoEmail($titulo, $sites) {

    $texto = $titulo . "\n";

    if (empty($sites) || !is_array($sites)) {
        return $texto . "- Nenhum site\n";
    }

    foreach ($sites as $site) {

        $nomeSite = limpar($site["nome_site"] ?? "Site não informado");
        $tipoFrequencia = limpar($site["tipo_frequencia"] ?? "");
        $horarioInicio = limpar($site["horario_inicio"] ?? "");
        $horarioFim = limpar($site["horario_fim"] ?? "");
        $status = limpar($site["status"] ?? "");

        if ($nomeSite === "") {
            $nomeSite = "Site não informado";
        }

        $linha = "- " . $nomeSite;

        if ($tipoFrequencia !== "") {
            $linha .= " | frequência: " . $tipoFrequencia;
        }

        if ($horarioInicio !== "" || $horarioFim !== "") {
            $linha .= " | horário configurado: " . ($horarioInicio !== "" ? $horarioInicio : "--:--") . " até " . ($horarioFim !== "" ? $horarioFim : "--:--");
        }

        if ($status !== "") {
            $linha .= " | status: " . $status;
        }

        $texto .= $linha . "\n";
    }

    return $texto;
}

/**
 * ENVIAR E-MAIL COM RESUMO DA EXECUÇÃO DO SCRAPER
 *
 * Este e-mail é enviado ao final da execução do scraper-res.php.
 * Ele não depende de existir imóvel novo.
 */
function enviarEmailResumoScraperRealizado($resumoScraper, $emailDestino) {

    global $enviarEmailResumoScraper;
    global $gravar_csv;

    $emailDestino = trim((string)$emailDestino);

    if (normalizarBusca($enviarEmailResumoScraper ?? "nao") !== "sim") {
        return [
            "status" => "nao_enviado",
            "motivo" => "envio_resumo_desativado",
            "data" => date("d/m/Y H:i:s")
        ];
    }

    if (normalizarBusca($gravar_csv ?? "") !== "sim") {
        return [
            "status" => "nao_enviado",
            "motivo" => "modo_teste_gravar_csv_nao",
            "data" => date("d/m/Y H:i:s")
        ];
    }

    if ($emailDestino === "") {
        return [
            "status" => "nao_enviado",
            "motivo" => "email_destino_resumo_vazio",
            "data" => date("d/m/Y H:i:s")
        ];
    }

    $resumoScraper = is_array($resumoScraper) ? $resumoScraper : [];

    $dataExecucao = limpar($resumoScraper["data_execucao"] ?? date("d/m/Y H:i:s"));
    $assunto = "Resumo do scraper de imóveis - " . $dataExecucao;

    $logs = $resumoScraper["logs"] ?? [];
    $resultados = $resumoScraper["resultado"] ?? [];

    $statusLogs = contarStatusLogsScraper($logs);
    $resultadosPorSite = contarResultadosPorSiteScraper($resultados);
    $sitesExecutadosScraper = $resumoScraper["sites_executados_scraper"] ?? [];
    $sitesIgnoradosPorFrequencia = $resumoScraper["sites_ignorados_por_frequencia"] ?? [];

    $mensagem = "Resumo do scraper realizado.\n\n";
    $mensagem .= "Data da execução: " . $dataExecucao . "\n";
    $mensagem .= "Horário atual: " . limpar($resumoScraper["horario_atual"] ?? "") . "\n";
    $mensagem .= "Status: " . limpar($resumoScraper["status"] ?? "") . "\n";
    $mensagem .= "Gravar CSV: " . limpar($resumoScraper["gravar_csv"] ?? "") . "\n";
    $mensagem .= "Status CSV imóveis: " . limpar($resumoScraper["csv_status"] ?? "") . "\n";
    $mensagem .= "Status CSV usuários: " . limpar($resumoScraper["csv_usuarios_status"] ?? "") . "\n\n";

    $mensagem .= "Totais\n";
    $mensagem .= "- Sites configurados: " . (int)($resumoScraper["total_sites"] ?? 0) . "\n";
    $mensagem .= "- Resultados coletados nesta execução: " . (int)($resumoScraper["total_resultados_novos"] ?? 0) . "\n";
    $mensagem .= "- Total atual no CSV de imóveis: " . (int)($resumoScraper["total_resultados_csv"] ?? 0) . "\n";
    $mensagem .= "- Total no CSV de usuários: " . (int)($resumoScraper["total_usuarios_csv"] ?? 0) . "\n";
    $mensagem .= "- Imóveis novos cadastrados: " . (int)($resumoScraper["total_imoveis_cadastrados_novos"] ?? 0) . "\n";
    $mensagem .= "- E-mails de imóvel novo: " . (int)($resumoScraper["total_emails_novo_imovel"] ?? 0) . "\n\n";

    $mensagem .= "Imagens\n";
    $mensagem .= "- Baixar imagens: " . limpar($resumoScraper["baixar_imagens"] ?? "") . "\n";
    $mensagem .= "- Imagens baixadas: " . (int)($resumoScraper["total_imagens_baixadas"] ?? 0) . "\n";
    $mensagem .= "- Imagens que já existiam: " . (int)($resumoScraper["total_imagens_ja_existiam"] ?? 0) . "\n";
    $mensagem .= "- Erros de imagem: " . (int)($resumoScraper["total_erros_imagens"] ?? 0) . "\n\n";

    $mensagem .= formatarListaResumoEmail("Resultados por site", $resultadosPorSite) . "\n";
    $mensagem .= formatarListaResumoEmail("Status dos logs", $statusLogs) . "\n";

    $mensagem .= "Arquivos\n";
    $mensagem .= "- Imóveis: " . limpar($resumoScraper["arquivo_csv"] ?? "") . "\n";
    $mensagem .= "- Usuários: " . limpar($resumoScraper["arquivo_csv_usuarios"] ?? "") . "\n";

    $itemRemetenteResumo = [];

    if (!empty($resultados) && is_array($resultados)) {
        $itemRemetenteResumo = $resultados[0] ?? [];
    }

    $remetente = getEmailRemetenteNotificacaoNovoImovel($itemRemetenteResumo);

    $headersArray = [
        "MIME-Version: 1.0",
        "Content-Type: text/plain; charset=UTF-8",
        "From: Scraper Imóveis <" . $remetente . ">"
    ];

    $assuntoEmail = $assunto;

    if (function_exists("mb_encode_mimeheader")) {
        $assuntoEmail = mb_encode_mimeheader($assunto, "UTF-8", "B", "\r\n");
    }

    if (function_exists("wp_mail")) {
        $enviado = wp_mail($emailDestino, $assuntoEmail, $mensagem, $headersArray);
    } else {
        $enviado = @mail($emailDestino, $assuntoEmail, $mensagem, implode("\r\n", $headersArray));
    }

    return [
        "status" => $enviado ? "enviado" : "erro_envio",
        "email_destino" => $emailDestino,
        "assunto" => $assunto,
        "data" => date("d/m/Y H:i:s")
    ];
}
