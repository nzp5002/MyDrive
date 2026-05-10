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

> [!IMPORTANT]
> **Atenção com a `SKEY`:** Esta chave cifra os arquivos via **AES-256-CBC**. Se você alterar ou perder essa chave, os arquivos no servidor se tornarão **irrecuperáveis**.

---

## 🚀 Como Configurar e Instalar

### 1. Clone o repositório
```bash
git clone [https://github.com/nzp5002/MyDrive.git](https://github.com/nzp5002/MyDrive.git)
cd MyDrive
