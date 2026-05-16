# ☁️ MyDrive

<div align="center">

<img src="https://raw.githubusercontent.com/github/explore/main/topics/cloud/cloud.png" width="140"/>

# 🚀 MyDrive

### Sistema moderno de armazenamento em nuvem.

<p align="center">
  <img src="https://img.shields.io/github/stars/nzp5002/MyDrive?style=for-the-badge&logo=github"/>
  <img src="https://img.shields.io/github/forks/nzp5002/MyDrive?style=for-the-badge&logo=git"/>
  <img src="https://img.shields.io/github/repo-size/nzp5002/MyDrive?style=for-the-badge"/>
</p>

</div>

---

# ✨ Sobre o Projeto

O **MyDrive** é um sistema de armazenamento de arquivos inspirado em plataformas cloud modernas, focado em segurança e portabilidade.

O projeto foi desenvolvido com foco em:
- ☁️ **Armazenamento online:** Acesso aos seus arquivos de qualquer lugar.
- 🔐 **Segurança:** Criptografia ponta a ponta no armazenamento físico.
- 📂 **Organização:** Sistema de pastas e gerenciamento intuitivo.
- ⚡ **Desempenho:** Processamento de uploads grandes via fragmentação (chunks).
- 🌐 **Hospedagem flexível:** Compatível com servidores Linux e ambientes Android (Termux).

---

# ⚡ Funcionalidades

- 📤 **Upload Resumable:** Suporte a arquivos grandes com retomada em caso de falha.
- 🛡️ **Criptografia AES-256:** Arquivos protegidos por chave mestra no servidor.
- 📂 **Gerenciamento de Pastas:** Crie e organize sua estrutura de diretórios.
- 🔑 **Autenticação:** Sistema de login seguro para múltiplos usuários.
- 🗂️ **Detecção de MIME:** Identificação automática do tipo de arquivo.
- 🚀 **Estrutura Leve:** Backend otimizado para baixo consumo de recursos.

---

# 🛠️ Tecnologias Utilizadas

| Tecnologia | Uso |
|---|---|
| **PHP 7.4+** | Backend, API e lógica de criptografia |
| **MySQL / MariaDB** | Persistência de dados e metadados |
| **JavaScript** | Interface dinâmica e lógica de upload (Resumable.js) |
| **HTML5 / CSS3** | Interface de usuário responsiva |
| **Apache / Nginx** | Servidor Web |

---

## ⚙️ Configuração do Ambiente

Este projeto utiliza variáveis de ambiente para gerenciar credenciais sensíveis. **Não pule esta etapa.**

### 🔑 Variáveis Necessárias

| Variável | Descrição | Exemplo / Padrão |
| :--- | :--- | :--- |
| **`DB_HOST`** | Endereço do servidor MySQL | `localhost` |
| **`DB_USER`** | Usuário do banco de dados | `root` |
| **`DB_PASS`** | Senha do banco de dados | `sua_senha` |
| **`DB_NAME`** | Nome da base de dados | `mydrive_db` |
| **`SKEY`** | Chave mestra de criptografia | `chave-de-32-caracteres` |

## 🗄️ Estrutura e Inicialização do Banco de Dados

Copie o código SQL abaixo, salve em um arquivo chamado `database.sql` e importe diretamente na aba **Importar** do seu phpMyAdmin para recriar o ambiente idêntico.

```sql
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
DROP TABLE IF EXISTS `amizades`;
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
DROP TABLE IF EXISTS `arquivos`;
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
DROP TABLE IF EXISTS `chaves_compartilhadas`;
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
DROP TABLE IF EXISTS `compartilhamentos`;
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
DROP TABLE IF EXISTS `compartilhamentos_folders`;
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
DROP TABLE IF EXISTS `convites`;
CREATE TABLE `convites` (
  `id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `criado_por` int(11) NOT NULL,
  `usado` tinyint(1) DEFAULT 0,
  `expira_em` datetime NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura para tabela `files`
--
DROP TABLE IF EXISTS `files`;
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
DROP TABLE IF EXISTS `folders`;
CREATE TABLE `folders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT 0,
  `nome` varchar(255) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('private','friends','public') DEFAULT 'private',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura para tabela `fotos_verificacao`
--
DROP TABLE IF EXISTS `fotos_verificacao`;
CREATE TABLE `fotos_verificacao` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `arquivo` varchar(255) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Estrutura para tabela `users`
--
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `indicado_por` int(11) DEFAULT NULL,
  `status` enum('ativo','pendente','banido') DEFAULT 'pendente',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Inserindo Administrador Padrão (Senha: 123)
--
INSERT INTO `users` (`id`, `nome`, `username`, `password`, `indicado_por`, `status`, `foto`) VALUES
(1, 'admin', 'admin', '$2y$10$iN6ZlA.r4M7a9m39BnyMkuYEq/oVshHAsD8B4GThmZ7P3wU2O8T06', 0, 'ativo', NULL);

--
-- Índices para tabelas despejadas
--

ALTER TABLE `amizades` ADD PRIMARY KEY (`id`);
ALTER TABLE `arquivos` ADD PRIMARY KEY (`id`);
ALTER TABLE `chaves_compartilhadas` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `unique_file_user` (`file_id`,`de_user_id`);
ALTER TABLE `compartilhamentos` ADD PRIMARY KEY (`id`);
ALTER TABLE `compartilhamentos_folders` ADD PRIMARY KEY (`id`);
ALTER TABLE `convites` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `token` (`token`);
ALTER TABLE `files` ADD PRIMARY KEY (`id`);
ALTER TABLE `folders` ADD PRIMARY KEY (`id`);
ALTER TABLE `fotos_verificacao` ADD PRIMARY KEY (`id`);
ALTER TABLE `users` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT para tabelas padrões
--

ALTER TABLE `amizades` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `arquivos` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `chaves_compartilhadas` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `compartilhamentos` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `compartilhamentos_folders` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `convites` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `files` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `folders` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `fotos_verificacao` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `users` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

COMMIT;




> [!IMPORTANT]
> **Atenção com a `SKEY`:** Esta chave cifra os arquivos via **AES-256-CBC**. Se você alterar ou perder essa chave, os arquivos no servidor se tornarão **irrecuperáveis**.

---

---

## 🛑 Status do Projeto: Descontinuado

## 🚀 Como Configurar e Instalar

### 1. Clone o repositório
```bash
git clone [https://github.com/nzp5002/MyDrive.git](https://github.com/nzp5002/MyDrive.git)
cd MyDrive

