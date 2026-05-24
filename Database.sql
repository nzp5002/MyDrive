-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 24/05/2026 às 01:16
-- Versão do servidor: 10.4.6-MariaDB
-- Versão do PHP: 7.3.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `MyDrive`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `amizades`
--

CREATE TABLE `amizades` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `amigo_id` int(11) NOT NULL,
  `status` enum('pendente','aceito','recusado') DEFAULT 'pendente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura para tabela `arquivos`
--

CREATE TABLE `arquivos` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `folder_id` int(11) DEFAULT 0,
  `nome_original` varchar(255) NOT NULL,
  `nome_arquivo` varchar(255) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `tamanho` bigint(20) DEFAULT 0,
  `iv` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura para tabela `chaves_compartilhadas`
--

CREATE TABLE `chaves_compartilhadas` (
  `id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `de_user_id` int(11) NOT NULL,
  `chave_temporaria` text NOT NULL,
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura para tabela `compartilhamentos`
--

CREATE TABLE `compartilhamentos` (
  `id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `de_user_id` int(11) NOT NULL,
  `para_user_id` int(11) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura para tabela `compartilhamentos_folders`
--

CREATE TABLE `compartilhamentos_folders` (
  `id` int(11) NOT NULL,
  `folder_id` int(11) NOT NULL,
  `de_user_id` int(11) NOT NULL,
  `para_user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura para tabela `convites`
--

CREATE TABLE `convites` (
  `id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `criado_por` int(11) NOT NULL,
  `usado` tinyint(1) DEFAULT 0,
  `expira_em` datetime NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Despejando dados para a tabela `convites`
--

INSERT INTO `convites` (`id`, `token`, `criado_por`, `usado`, `expira_em`, `criado_em`) VALUES
(1, 'e11fb578ad51a3db', 1, 0, '2026-05-17 05:05:51', '2026-05-16 05:05:51'),
(2, '1e65a7fc4cbd2ecb', 1, 0, '2026-05-17 05:18:06', '2026-05-16 05:18:06'),
(3, 'feb8be0bd0af5f24', 1, 0, '2026-06-22 23:49:58', '2026-05-23 23:49:58'),
(4, '7207553066452459', 1, 0, '2026-06-22 23:56:27', '2026-05-23 23:56:28'),
(5, '213d37ad14759fe8', 1, 0, '2026-06-23 00:03:12', '2026-05-24 00:03:12'),
(6, '399ed28c350cf898', 1, 0, '2026-06-23 00:04:40', '2026-05-24 00:04:40'),
(7, '7fe3b6abd3531c0c', 1, 0, '2026-06-23 00:23:37', '2026-05-24 00:23:37');

-- --------------------------------------------------------

--
-- Estrutura para tabela `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `folder_id` int(11) DEFAULT 0,
  `nome` varchar(255) NOT NULL,
  `arquivo` varchar(255) NOT NULL,
  `tipo` varchar(100) DEFAULT NULL,
  `tamanho` bigint(20) DEFAULT 0,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura para tabela `folders`
--

CREATE TABLE `folders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT 0,
  `nome` varchar(255) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('private','friends','public') DEFAULT 'private',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Despejando dados para a tabela `folders`
--

INSERT INTO `folders` (`id`, `user_id`, `parent_id`, `nome`, `criado_em`, `status`, `created_at`) VALUES
(4, 1, 0, 'kik', '2026-05-17 19:31:50', 'public', '2026-05-17 19:31:50');

-- --------------------------------------------------------

--
-- Estrutura para tabela `fotos_verificacao`
--

CREATE TABLE `fotos_verificacao` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `arquivo` varchar(255) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `indicado_por` int(11) DEFAULT NULL,
  `status` enum('ativo','pendente','banido') DEFAULT 'pendente',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `foto` varchar(255) DEFAULT NULL,
  `theme` varchar(50) DEFAULT 'aurora'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `nome`, `username`, `password`, `indicado_por`, `status`, `criado_em`, `foto`, `theme`) VALUES
(1, 'Conta Teste', 'nino', '$2y$10$s0ndhcnpgHcx5oBqOTtrae8quOCdzVin6u.Ig.lRRDsDmkbog0rg.', 0, 'ativo', '2026-05-12 00:29:15', 'user_1_1779576317.webp', 'aurora');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `amizades`
--
ALTER TABLE `amizades`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `arquivos`
--
ALTER TABLE `arquivos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `chaves_compartilhadas`
--
ALTER TABLE `chaves_compartilhadas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_file_user` (`file_id`,`de_user_id`);

--
-- Índices de tabela `compartilhamentos`
--
ALTER TABLE `compartilhamentos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `compartilhamentos_folders`
--
ALTER TABLE `compartilhamentos_folders`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `convites`
--
ALTER TABLE `convites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Índices de tabela `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `folders`
--
ALTER TABLE `folders`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `fotos_verificacao`
--
ALTER TABLE `fotos_verificacao`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `amizades`
--
ALTER TABLE `amizades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `arquivos`
--
ALTER TABLE `arquivos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `chaves_compartilhadas`
--
ALTER TABLE `chaves_compartilhadas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `compartilhamentos`
--
ALTER TABLE `compartilhamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `compartilhamentos_folders`
--
ALTER TABLE `compartilhamentos_folders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `convites`
--
ALTER TABLE `convites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `folders`
--
ALTER TABLE `folders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `fotos_verificacao`
--
ALTER TABLE `fotos_verificacao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
