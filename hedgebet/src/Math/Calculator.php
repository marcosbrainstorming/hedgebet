<?php
/**
 * HedgeBet - Mecanismo de Cálculo de Investimento e Cobertura
 * Arquivo: src/Math/Calculator.php
 */

class Calculator {

    /**
     * Calcula a divisão de stakes proporcional para buscar lucro em ambos os cenários.
     * Focado na estratégia HedgeBet (Turbinar Empate ou Equilibrar).
     * * @param float $oddFavorito Cotação do time favorito
     * @param float $oddEmpate Cotação do Empate
     * @param float $stakeTotal O valor total (aporte) que você deseja expor no jogo
     * @param float $pesoEmpate Percentual de preferência para o lucro do Empate (0.5 para igual, maior que 0.5 para priorizar o empate)
     * @return array Valores fracionados a apostar e retornos projetados
     */
    public static function calcularHedgeProporcional(float $oddFavorito, float $oddEmpate, float $stakeTotal, float $pesoEmpate = 0.60): array {
        // Evita divisões por zero ou valores absurdos nas ODDs
        if ($oddFavorito <= 1 || $oddEmpate <= 1 || $stakeTotal <= 0) {
            return [
                'sucesso' => false,
                'mensagem' => 'Valores de ODD ou Stake inválidos para cálculo.'
            ];
        }

        /*
         * A mágica do HedgeBet:
         * Descobrimos a proporção inversa das probabilidades das cotações
         * e aplicamos o viés estratégico (peso) que você quer dar ao empate.
         */
        $probabilidadeFav = 1 / $oddFavorito;
        $probabilidadeEmp = 1 / $oddEmpate;
        $somaProbabilidades = $probabilidadeFav + $probabilidadeEmp;

        // Ajusta a distribuição de acordo com o seu viés (padrão de 60% focado no empate)
        $fatorFav = ($probabilidadeFav / $somaProbabilidades) * (1 - ($pesoEmpate - 0.5));
        $fatorEmp = ($probabilidadeEmp / $somaProbabilidades) * (1 + ($pesoEmpate - 0.5));
        $totalFatores = $fatorFav + $fatorEmp;

        // Distribui o valor do aporte total baseado nos fatores encontrados
        $apostaFavorito = round(($fatorFav / $totalFatores) * $stakeTotal, 2);
        $apostaEmpate = round($stakeTotal - $apostaFavorito, 2); // Garante que a soma bate os centavos da stake total

        // Calcula o cenário caso dê Vitória do Favorito
        $retornoFavorito = round($apostaFavorito * $oddFavorito, 2);
        $lucroLiquidoFavorito = round($retornoFavorito - $stakeTotal, 2);
        $porcentagemFav = round(($lucroLiquidoFavorito / $stakeTotal) * 100, 2);

        // Calcula o cenário caso dê Empate
        $retornoEmpate = round($apostaEmpate * $oddEmpate, 2);
        $lucroLiquidoEmpate = round($retornoEmpate - $stakeTotal, 2);
        $porcentagemEmp = round(($lucroLiquidoEmpate / $stakeTotal) * 100, 2);

        return [
            'sucesso' => true,
            'valores_entrada' => [
                'aposta_favorito' => $apostaFavorito,
                'aposta_empate'   => $apostaEmpate,
                'stake_total'     => $stakeTotal
            ],
            'cenario_favorito' => [
                'retorno_bruto' => $retornoFavorito,
                'lucro_liquido' => $lucroLiquidoFavorito,
                'lucro_percentual' => $porcentagemFav
            ],
            'cenario_empate' => [
                'retorno_bruto' => $retornoEmpate,
                'lucro_liquido' => $lucroLiquidoEmpate,
                'lucro_percentual' => $porcentagemEmp
            ]
        ];
    }
}