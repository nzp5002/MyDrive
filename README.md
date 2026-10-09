# ☁️ MyDrive

<div align="center">

<img src="https://raw.githubusercontent.com/nzp5002/MyDrive/main/Screenshot_20260516_041624_Chrome.jpg" alt="MyDrive" width="140">

# MyDrive — armazenamento de arquivos

**Uma solução pessoal para enviar, organizar e acessar arquivos pela web.**

<p>
  <img src="https://img.shields.io/github/forks/nzp5002/MyDrive?style=for-the-badge&logo=github" alt="Forks">
  <img src="https://img.shields.io/github/repo-size/nzp5002/MyDrive?style=for-the-badge" alt="Tamanho do repositório">
</p>

</div>

---

## 📖 Sobre o projeto

O **MyDrive** é um projeto de armazenamento de arquivos com interface web, desenvolvido em PHP e integrado a um banco de dados MySQL. A proposta é facilitar o envio e a organização de arquivos em um servidor próprio.

O projeto busca oferecer:

- 📤 **Envio de arquivos:** suporte a uploads divididos em partes (*chunks*), útil para arquivos maiores.
- 📁 **Organização:** gerenciamento de arquivos e pastas.
- 🔐 **Acesso por conta:** autenticação para acessar o sistema.
- 🛡️ **Proteção de arquivos:** recursos de criptografia no lado do servidor.
- 🖥️ **Hospedagem flexível:** possibilidade de execução em ambientes Linux com PHP, incluindo Termux, desde que as dependências estejam configuradas.

## ✨ Funcionalidades

- Upload de arquivos em partes.
- Organização de arquivos por pastas.
- Login de usuários.
- Compartilhamento e acesso a arquivos, conforme a configuração e os recursos disponíveis na instalação.
- Integração com PHP e MySQL.

## 🧰 Tecnologias

- **Backend:** PHP
- **Banco de dados:** MySQL
- **Interface:** HTML, CSS e JavaScript, conforme os arquivos do projeto
- **Servidor:** ambiente web compatível com PHP

## ⚙️ Requisitos

Antes de instalar, verifique se o seu ambiente possui:

- PHP e as extensões necessárias ao projeto.
- MySQL ou MariaDB.
- Um servidor web compatível com PHP, como Apache ou Nginx.
- Git, para clonar o repositório.

## 🚀 Instalação

1. Clone o repositório:

   ```bash
   git clone https://github.com/nzp5002/MyDrive.git
   ```

2. Entre na pasta do projeto:

   ```bash
   cd MyDrive
   ```

3. Configure o banco de dados MySQL/MariaDB e ajuste o arquivo de configuração de conexão utilizado pelo projeto.
4. Configure o servidor web para apontar para a pasta correta do projeto.
5. Abra o endereço configurado no navegador e siga o fluxo de acesso do sistema.

> **Importante:** os nomes do banco de dados, as credenciais e os passos finais dependem dos arquivos de configuração desta versão. Confira-os antes de iniciar o serviço.

## 🖼️ Capturas de tela

<div align="center">

<img src="https://raw.githubusercontent.com/nzp5002/MyDrive/main/Screenshot_20260516_040440_Chrome.jpg" alt="Tela de login do MyDrive" width="320">

*Tela de login*

</div>

## 🔒 Segurança

- Não mantenha senhas padrão ou credenciais de teste em uma instalação pública.
- Use uma senha forte para o banco de dados e para as contas do sistema.
- Não publique arquivos de configuração, chaves de criptografia ou segredos no repositório.
- Em produção, use HTTPS e mantenha PHP, banco de dados e servidor web atualizados.
- Revise as permissões de arquivos e pastas; evite permissões amplas como `777`.

## 🤝 Contribuições

Sugestões, relatos de erros e melhorias são bem-vindos. Abra uma *issue* para descrever o problema ou envie um *pull request* com a alteração proposta.

## 📄 Licença

Consulte os arquivos do repositório para verificar os termos de licença aplicáveis.

---

<div align="center">

Feito com ☁️ para facilitar o armazenamento de arquivos.

</div>
