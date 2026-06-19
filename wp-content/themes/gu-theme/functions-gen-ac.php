<?php

$arquivoCSV1 = "https://veiculosdf.com.br/wp-load.php?security_key=8f518111c2d704d8&export_id=2&action=get_data"; // primeira coluna => tipo local
$arquivoCSV2 = "https://veiculosdf.com.br/wp-load.php?security_key=fd801ec82c125d9f&export_id=3&action=get_data";     // primeira coluna => tipo tags
$saidaJS = "functions-ac.js";

$locais = [];

/**
 * Detecta o delimitador do CSV
 */
function detectarDelimitador($arquivo)
{
    $handle = fopen($arquivo, "r");
    $linha = fgets($handle);
    fclose($handle);

    $delimitadores = [",", ";", "\t", "|"];
    $maiorQtd = 0;
    $melhor = ",";

    foreach ($delimitadores as $delimitador) {
        $qtd = count(str_getcsv($linha, $delimitador));
        if ($qtd > $maiorQtd) {
            $maiorQtd = $qtd;
            $melhor = $delimitador;
        }
    }

    return $melhor;
}

/**
 * Lê a primeira coluna do CSV
 * e aplica o tipo informado
 */
function lerPrimeiraColunaComTipo($arquivo, $tipo)
{
    $resultado = [];
    $delimitador = detectarDelimitador($arquivo);

    if (($handle = fopen($arquivo, "r")) !== false) {
        while (($data = fgetcsv($handle, 0, $delimitador)) !== false) {
            if (!isset($data[0])) {
                continue;
            }

            $nome = trim($data[0]);

            if ($nome === "") {
                continue;
            }

            $resultado[] = [
                "nome" => $nome,
                "tipo" => $tipo
            ];
        }

        fclose($handle);
    }

    return $resultado;
}

// Lê os dois CSVs
$locaisCSV1 = lerPrimeiraColunaComTipo($arquivoCSV1, "local");
$locaisCSV2 = lerPrimeiraColunaComTipo($arquivoCSV2, "marca");

// Junta tudo
$locais = array_merge($locaisCSV1, $locaisCSV2);

// Gera o arquivo JS
$conteudoJS = "// arquivo gerado automaticamente\n\n";
$conteudoJS .= "export const locais = " . json_encode($locais, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . ";\n\n";
//$conteudoJS .= "module.exports = locais;\n";

// Salva
file_put_contents($saidaJS, $conteudoJS);

echo "Arquivo functions-ac.js gerado com sucesso!\n";
echo "Total de registros: " . count($locais) . "\n";