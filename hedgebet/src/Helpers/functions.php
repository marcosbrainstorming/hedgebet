<?php
/**
 * HedgeBet - Funções Auxiliares e Utilitários Globais
 * Arquivo: src/Helpers/functions.php
 */

/**
 * Formata um valor numérico para o padrão de moeda brasileiro (R$ 1.250,45)
 * @param float $valor O número decimal bruto
 * @return string O valor formatado com cifrão
 */
function formatarMoeda(float $valor): string {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

/**
 * Formata uma data do padrão do banco (YYYY-MM-DD) para o padrão brasileiro (DD/MM/YYYY)
 * @param string $data Data vinda do MySQL
 * @param bool $incluirAno Se deve exibir o ano completo ou apenas dia/mês (ótimo para telas compactas)
 * @return string Data formatada
 */
function formatarData(string $data, bool $incluirAno = true): string {
    $timestamp = strtotime($data);
    if (!$timestamp) {
        return $data;
    }
    
    $formato = $incluirAno ? 'd/m/Y' : 'd/m';
    return date($formato, $timestamp);
}

/**
 * Sanitize e limpa strings recebidas via POST/GET contra vulnerabilidades XSS
 * @param string $dados O texto bruto enviado pelo formulário
 * @return string Texto limpo e seguro para exibição no HTML
 */
function limparEntrada(string $dados): string {
    $dados = trim($dados);
    $dados = stripslashes($dados);
    return htmlspecialchars($dados, ENT_QUOTES, 'UTF-8');
}

/**
 * Calcula a porcentagem de lucro ou prejuízo sobre um investimento
 * @param float $lucroLiquido O valor ganho ou perdido líquido
 * @param float $capitalInvestido O valor total da stake
 * @return string Retorna a string formatada com sinal de + ou - (ex: +15,4% ou -5,0%)
 */
function calcularPorcentagemLucro(float $lucroLiquido, float $capitalInvestido): string {
    if ($capitalInvestido <= 0) {
        return '0,00%';
    }
    
    $percentual = ($lucroLiquido / $capitalInvestido) * 100;
    $sinal = $percentual > 0 ? '+' : '';
    
    return $sinal . number_format($percentual, 2, ',', '.') . '%';
}