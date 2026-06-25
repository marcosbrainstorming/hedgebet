<?php
/**
 * HedgeBet - Repositório de Gestão de Apostas e Operações
 * Arquivo: src/Repository/ApostaRepository.php
 */

class ApostaRepository {
    private $db;

    /**
     * Construtor recebe a conexão ativa do PDO
     */
    public function __construct($databaseConnection) {
        $this->db = $databaseConnection;
    }

    /**
     * Registra um novo jogo com as duas apostas casadas da estratégia (Hedge)
     * Utiliza Transação (Transaction) para garantir que ou salvamos o jogo com as DUAS apostas, ou não salva nada se houver erro.
     * * @param int $bancaId ID do ciclo de banca ativo
     * @param string $casa Nome do time da casa
     * @param string $visitante Nome do time visitante
     * @param string $data Data do confronto (YYYY-MM-DD)
     * @param array $dadosHedge Array contendo cotações e valores fracionados calculados
     * @return bool Retorna verdadeiro se a operação foi bem-sucedida
     */
    public function salvarHedge(int $bancaId, string $casa, string $visitante, string $data, array $dadosHedge): bool {
        try {
            // Inicia a transação para proteger a integridade dos dados
            $this->db->beginTransaction();

            // 1. Insere o registro principal do confronto na tabela 'jogos_operados'
            $queryJogo = "INSERT INTO jogos_operados (banca_id, time_casa, time_visitante, data_jogo) 
                          VALUES (:banca_id, :casa, :visitante, :data)";
            $stmtJogo = $this->db->prepare($queryJogo);
            $stmtJogo->execute([
                ':banca_id' => $bancaId,
                ':casa'     => $casa,
                ':visitante'=> $visitante,
                ':data'     => $data
            ]);
            
            // Recupera o ID do jogo gerado acima para amarrar as apostas abaixo
            $jogoId = $this->db->lastInsertId();

            // Prepare padrão para inserção de apostas individuais
            $queryAposta = "INSERT INTO apostas (jogo_id, palpite, odd, valor_apostado, status_resultado) 
                            VALUES (:jogo_id, :palpite, :odd, :valor_apostado, 'Pendente')";
            $stmtAposta = $this->db->prepare($queryAposta);

            // 2. Insere a perna do Favorito
            $stmtAposta->execute([
                ':jogo_id'        => $jogoId,
                ':palpite'        => 'Vitória Favorito',
                ':odd'            => $dadosHedge['odd_favorito'],
                ':valor_apostado' => $dadosHedge['aposta_favorito']
            ]);

            // 3. Insere a perna do Empate (Seu foco principal de lucro)
            $stmtAposta->execute([
                ':jogo_id'        => $jogoId,
                ':palpite'        => 'Empate',
                ':odd'            => $dadosHedge['odd_empate'],
                ':valor_apostado' => $dadosHedge['aposta_empate']
            ]);

            // Confirma todas as operações no banco de dados de uma vez só
            $this->db->commit();
            return true;

        } catch (Exception $e) {
            // Em caso de qualquer falha, desfaz tudo que foi feito nesta tentativa
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Busca todos os jogos operados que ainda possuem apostas com status 'Pendente'
     * Agrupa as informações das apostas em uma única string tratada para exibição no card
     * * @return array Lista de jogos aguardando resultado
     */
    public function listarJogosPendentes(): array {
        $query = "SELECT j.*, 
                         GROUP_CONCAT(CONCAT(a.palpite, ' (ODD ', a.odd, ' - R$ ', a.valor_apostado, ')') SEPARATOR ' | ') as detalhes_apostas
                  FROM jogos_operados j
                  JOIN apostas a ON a.jogo_id = j.id
                  WHERE a.status_resultado = 'Pendente'
                  GROUP BY j.id
                  ORDER BY j.data_jogo ASC";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Busca as linhas de apostas individuais (Favorito e Empate) vinculadas a um jogo específico
     * * @param int $jogoId ID do jogo operado
     * @return array Lista de apostas atreladas ao ID
     */
    public function buscarApostasDoJogo(int $jogoId): array {
        $query = "SELECT * FROM apostas WHERE jogo_id = :jogo_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':jogo_id', $jogoId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Atualiza o resultado de uma aposta individual (Ganha, Perdida ou Cashout) e salva o retorno financeiro real
     * * @param int $apostaId ID único da aposta
     * @param string $status Novo status ('Ganha', 'Perdida', 'Cashout')
     * @param float $valorRetornado Valor em dinheiro que retornou para a banca
     * @return bool
     */
    public function atualizarResultadoAposta(int $apostaId, string $status, float $valorRetornado): bool {
        $query = "UPDATE apostas 
                  SET status_resultado = :status, valor_retornado = :valor_retornado 
                  WHERE id = :id";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':valor_retornado', $valorRetornado);
        $stmt->bindValue(':id', $apostaId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
}