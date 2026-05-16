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

---

# ⚙️ Pré-requisitos e Variáveis de Ambiente

Antes de iniciar, configure o seu arquivo de conexão (ex: `config.php`) com as credenciais do seu ambiente:

| Variável | Descrição | Exemplo Padrão |
| :--- | :--- | :--- |
| **DB_HOST** | Endereço do servidor MySQL | `localhost` |
| **DB_USER** | Usuário do banco de dados | `root` |
| **DB_PASS** | Senha do banco de dados | ` ` (vazio) ou `sua_senha` |
| **DB_NAME** | Nome do banco de dados | `MyDrive` |

---

# 🚀 Como Executar o Projeto

Instale o projeto localmente seguindo os passos abaixo:

```bash
# Clone o repositório
git clone [https://github.com/nzp5002/MyDrive.git](https://github.com/nzp5002/MyDrive.git)

### 🔑 Credenciais Padrão para Testes

Após realizar a importação do banco de dados, você pode acessar o sistema utilizando o seguinte usuário administrador padrão:

| Usuário (Username) | Senha Padrão (Password) | Status da Conta |
| :--- | :--- | :--- |
| **`admin`** | `123` | Ativo |

---

### 🗄️ Script de Importação do Banco de Dados

Copie o código SQL abaixo, salve-o em um arquivo chamado `database.sql` e importe diretamente na aba **Importar** do seu phpMyAdmin:

```sql
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Estrutura para tabela `amizades`
--
DROP TABLE IF EXISTS `amizades`;
CREATE TABLE `amizades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `amigo_id` int(11) NOT NULL,
  `status` enum('pendente','aceito','recusado') DEFAULT 'pendente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Estrutura para tabela `arquivos`
--
DROP TABLE IF EXISTS `arquivos`;
CREATE TABLE `arquivos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `folder_id` int(11) DEFAULT 0,
  `nome_original` varchar(255) NOT NULL,
  `nome_arquivo` varchar(255) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `tamanho` bigint(20) DEFAULT 0,
  `iv` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Estrutura para tabela `chaves_compartilhadas`
--
DROP TABLE IF EXISTS `chaves_compartilhadas`;
CREATE TABLE `chaves_compartilhadas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) NOT NULL,
  `de_user_id` int(11) NOT NULL,
  `chave_temporaria` text NOT NULL,
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_file_user` (`file_id`,`de_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Estrutura para tabela `compartilhamentos`
--
DROP TABLE IF EXISTS `compartilhamentos`;
CREATE TABLE `compartilhamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) NOT NULL,
  `de_user_id` int(11) NOT NULL,
  `para_user_id` int(11) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Estrutura para tabela `compartilhamentos_folders`
--
DROP TABLE IF EXISTS `compartilhamentos_folders`;
CREATE TABLE `compartilhamentos_folders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `folder_id` int(11) NOT NULL,
  `de_user_id` int(11) NOT NULL,
  `para_user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Estrutura para tabela `convites`
--
DROP TABLE IF EXISTS `convites`;
CREATE TABLE `convites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` varchar(255) NOT NULL,
  `criado_por` int(11) NOT NULL,
  `usado` tinyint(1) DEFAULT 0,
  `expira_em` datetime NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Estrutura para tabela `files`
--
DROP TABLE IF EXISTS `files`;
CREATE TABLE `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `folder_id` int(11) DEFAULT 0,
  `nome` varchar(255) NOT NULL,
  `arquivo` varchar(255) NOT NULL,
  `tipo` varchar(100) DEFAULT NULL,
  `tamanho` bigint(20) DEFAULT 0,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Estrutura para tabela `folders`
--
DROP TABLE IF EXISTS `folders`;
CREATE TABLE `folders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT 0,
  `nome` varchar(255) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('private','friends','public') DEFAULT 'private',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Estrutura para tabela `fotos_verificacao`
--
DROP TABLE IF EXISTS `fotos_verificacao`;
CREATE TABLE `fotos_verificacao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `arquivo` varchar(255) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Estrutura para tabela `users`
--
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `indicado_por` int(11) DEFAULT NULL,
  `status` enum('ativo','pendente','banido') DEFAULT 'pendente',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `foto` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Inserindo usuário padrão administrativo (Senha: 123)
--
INSERT INTO `users` (`id`, `nome`, `username`, `password`, `indicado_por`, `status`, `foto`) VALUES
(1, 'admin', 'admin', '$2y$10$iN6ZlA.r4M7a9m39BnyMkuYEq/oVshHAsD8B4GThmZ7P3wU2O8T06', 0, 'ativo', NULL);

COMMIT;


# Acesse a pasta do projeto
cd MyDrive
