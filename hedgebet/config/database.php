<?php
/**
 * HedgeBet - Configuração e Conexão com o Banco de Dados
 * Arquivo: config/database.php
 */

class Database {
    // Configurações do banco de dados (ajuste se o seu ambiente local for diferente)
    private $host = "localhost";
    private $db_name = "hedgebet";
    private $username = "root";
    private $password = ""; // Se usar XAMPP no Windows, geralmente é vazio. Se usar MAMP/Docker, verifique a senha.
    public $conn;

    /**
     * Estabelece a conexão com o banco de dados usando PDO
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;

        try {
            // Define o DSN (Data Source Name) com charset UTF-8 para evitar problemas com acentos
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            // Configurações adicionais do PDO para segurança e diagnóstico
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Transforma erros do SQL em exceções do PHP
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Retorna os dados como arrays associativos limpos
                PDO::ATTR_EMULATE_PREPARES   => false,                  // Desativa a emulação para usar Prepared Statements reais do MySQL
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $exception) {
            // Em produção, o ideal é salvar isso em um log e mostrar uma mensagem genérica.
            // Para o desenvolvimento do HedgeBet, vamos exibir o erro na tela para facilitar o debug.
            die("Erro de conexão no HedgeBet: " . $exception->getMessage());
        }

        return $this->conn;
    }
}