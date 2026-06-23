-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 23/06/2026 às 19:00
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `hedgebet`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `apostas`
--

CREATE TABLE `apostas` (
  `id` int(11) NOT NULL,
  `jogo_id` int(11) DEFAULT NULL,
  `time_casa` varchar(100) NOT NULL,
  `time_visitante` varchar(100) NOT NULL,
  `data_jogo` date NOT NULL,
  `palpite` varchar(50) DEFAULT NULL,
  `tipo_aposta` varchar(50) DEFAULT NULL,
  `odd` decimal(5,2) DEFAULT NULL,
  `odd_favorito` decimal(5,2) NOT NULL,
  `odd_empate` decimal(5,2) NOT NULL,
  `valor_apostado` decimal(10,2) DEFAULT NULL,
  `aposta_favorito` decimal(10,2) NOT NULL,
  `aposta_empate` decimal(10,2) NOT NULL,
  `status_resultado` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Aberta',
  `valor_retornado` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `apostas_backup`
--

CREATE TABLE `apostas_backup` (
  `id` int(11) NOT NULL,
  `time_casa` varchar(100) DEFAULT NULL,
  `time_visitante` varchar(100) DEFAULT NULL,
  `data_jogo` date DEFAULT NULL,
  `jogo_id` int(11) NOT NULL,
  `palpite` enum('Vitória Favorito','Empate','Outro') NOT NULL,
  `tipo_aposta` enum('Simples','Dupla','Tripla','Múltipla') DEFAULT 'Simples',
  `odd` decimal(5,2) NOT NULL,
  `odd_favorito` decimal(5,2) DEFAULT NULL,
  `odd_empate` decimal(5,2) DEFAULT NULL,
  `valor_apostado` decimal(10,2) NOT NULL,
  `aposta_favorito` decimal(10,2) DEFAULT NULL,
  `aposta_empate` decimal(10,2) DEFAULT NULL,
  `status_resultado` enum('Pendente','Ganha','Perdida','Cashout') DEFAULT 'Pendente',
  `status` varchar(20) DEFAULT 'Aberta',
  `valor_retornado` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `bancas`
--

CREATE TABLE `bancas` (
  `id` int(11) NOT NULL,
  `valor_deposito` decimal(10,2) NOT NULL,
  `valor_saque` decimal(10,2) DEFAULT 0.00,
  `status` enum('Aberto','Fechado') DEFAULT 'Aberto',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `jogos_operados`
--

CREATE TABLE `jogos_operados` (
  `id` int(11) NOT NULL,
  `banca_id` int(11) NOT NULL,
  `time_casa` varchar(100) NOT NULL,
  `time_visitante` varchar(100) NOT NULL,
  `data_jogo` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `saques_caixa`
--

CREATE TABLE `saques_caixa` (
  `id` int(11) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `data_saque` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `usuario`, `senha`, `criado_em`) VALUES
(1, 'marcos@bet.com', '123456', '2026-06-23 00:45:04');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `apostas`
--
ALTER TABLE `apostas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `apostas_backup`
--
ALTER TABLE `apostas_backup`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jogo_id` (`jogo_id`);

--
-- Índices de tabela `bancas`
--
ALTER TABLE `bancas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `jogos_operados`
--
ALTER TABLE `jogos_operados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `banca_id` (`banca_id`);

--
-- Índices de tabela `saques_caixa`
--
ALTER TABLE `saques_caixa`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `apostas`
--
ALTER TABLE `apostas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `apostas_backup`
--
ALTER TABLE `apostas_backup`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `bancas`
--
ALTER TABLE `bancas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `jogos_operados`
--
ALTER TABLE `jogos_operados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `saques_caixa`
--
ALTER TABLE `saques_caixa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `apostas_backup`
--
ALTER TABLE `apostas_backup`
  ADD CONSTRAINT `apostas_backup_ibfk_1` FOREIGN KEY (`jogo_id`) REFERENCES `jogos_operados` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `jogos_operados`
--
ALTER TABLE `jogos_operados`
  ADD CONSTRAINT `jogos_operados_ibfk_1` FOREIGN KEY (`banca_id`) REFERENCES `bancas` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
