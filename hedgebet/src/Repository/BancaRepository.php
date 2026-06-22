<?php
/**
 * HedgeBet - Repositório de Relatórios e Dashboard
 * Arquivo: src/Repository/BancaRepository.php
 */

class BancaRepository {
    private $db;

    public function __construct($databaseConnection) {
        $this->db = $databaseConnection;
    }

    /**
     * Calcula o resumo financeiro geral da banca
     */
    public function obterResumoGeral(): array {
        // Busca total apostado, total retornado e lucro líquido
        $query = "SELECT 
                    SUM(valor_apostado) as total_apostado,
                    SUM(valor_retornado) as total_retornado,
                    SUM(valor_retornado - valor_apostado) as lucro_liquido
                  FROM apostas 
                  WHERE status_resultado != 'Pendente'";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $resumo = $stmt->fetch();

        // Busca o total de saques realizados
        $querySaques = "SELECT SUM(valor_saque) as total_saques FROM bancas";
        $stmtSaques = $this->db->prepare($querySaques);
        $stmtSaques->execute();
        $saques = $stmtSaques->fetch();

        return [
            'total_apostado' => $resumo['total_apostado'] ?? 0.00,
            'total_retornado' => $resumo['total_retornado'] ?? 0.00,
            'lucro_liquido'  => $resumo['lucro_liquido'] ?? 0.00,
            'total_saques'   => $saques['total_saques'] ?? 0.00
        ];
    }

    /**
     * Traz a performance dividida por palpite (Vitória vs Empate) para validar a estratégia
     */
    public function obterPerformancePorPalpite(): array {
        $query = "SELECT palpite, 
                         COUNT(*) as total_entradas,
                         SUM(CASE WHEN status_resultado = 'Ganha' THEN 1 ELSE 0 END) as acertos,
                         SUM(valor_retornado - valor_apostado) as lucro_acumulado
                  FROM apostas 
                  WHERE status_resultado != 'Pendente'
                  GROUP BY palpite";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}