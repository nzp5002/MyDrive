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

# 🚀 Instalação, Credenciais e Banco de Dados

Siga os passos abaixo para clonar o repositório, verificar os dados de acesso e importar a estrutura do banco de dados:

```bash
# Clone o repositório
git clone [https://github.com/nzp5002/MyDrive.git](https://github.com/nzp5002/MyDrive.git)

# Acesse a pasta do projeto
cd MyDrive
