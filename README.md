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

O **MyDrive** é um sistema de armazenamento de arquivos inspirado em plataformas cloud modernas.

O projeto foi desenvolvido com foco em:

- ☁️ Armazenamento online
- 🔐 Sistema de autenticação
- 📂 Gerenciamento de arquivos
- ⚡ Simplicidade e desempenho
- 🌐 Integração entre cliente e servidor

---

# ⚡ Funcionalidades

- 📤 Upload de arquivos
- 📥 Download de arquivos
- 📂 Organização de pastas
- 🔑 Login de usuários
- ☁️ Sistema cloud
- 🗂️ Gerenciamento de mídia
- ⚙️ Backend integrado
- 🚀 Estrutura leve

---

# 🛠️ Tecnologias Utilizadas

| Tecnologia | Uso |
|---|---|
| PHP | Backend/API |
| MySQL | Banco de dados |
| JavaScript | Funcionalidades |
| HTML/CSS | Interface |
| Apache/Nginx | Servidor Web |

---


## ⚙️ Configuração do Ambiente

Este projeto utiliza variáveis de ambiente para gerenciar credenciais sensíveis e garantir a segurança dos dados. Certifique-se de configurar as seguintes variáveis no seu ambiente de hospedagem ou servidor.

### 🔑 Variáveis Necessárias

| Variável | Descrição | Exemplo / Padrão |
| :--- | :--- | :--- |
| **`DB_HOST`** | Endereço do servidor MySQL | `localhost` ou `127.0.0.1` |
| **`DB_USER`** | Usuário do banco de dados | `root` |
| **`DB_PASS`** | Senha do banco de dados | `sua_senha_segura` |
| **`DB_NAME`** | Nome da base de dados (Schema) | `mydrive_db` |
| **`DB_PORT`** | Porta de conexão do MySQL | `3306` |
| **`SKEY`** | Chave mestra para criptografia AES-256 | `32-caracteres-aleatorios` |

> [!IMPORTANT]
> **Atenção com a `SKEY`:** Esta chave é utilizada para cifrar os arquivos via **AES-256-CBC**. Se você alterar ou perder essa chave, todos os arquivos já armazenados no servidor se tornarão **irrecuperáveis**, pois não será possível descriptografá-los.

---

### 🚀 Como Configurar

#### No Terminal (Linux / Termux)
Se estiver executando o servidor manualmente, você pode exportar as variáveis antes de iniciar o serviço:
```bash
export DB_HOST='localhost'
export DB_USER='seu_usuario'
export DB_PASS='sua_senha'
export DB_NAME='mydrive'
export SKEY='sua_chave_secreta_de_32_caracteres'

# Iniciar o servidor (exemplo)

# 📦 Instalação

## Clone o repositório

```bash
git clone https://github.com/nzp5002/MyDrive.git
