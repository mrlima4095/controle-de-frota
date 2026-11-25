# Controle de Frota

Sistema web para controle de frota de veículos: cadastro de motoristas, clientes, veículos e departamentos, reservas, percursos, manutenções, abastecimentos e multas, com relatórios de custo e uso. A interface é apresentada com o nome **DriveHub**.

Projeto das disciplinas **Banco de Dados II**, **Programação para Internet** e **Análise e Projeto de Dados** (Etec Jardim Ângela, Desenvolvimento de Sistemas).

**Equipe**

- Felipe Souza Pereira de Lima
- Pedro Henrique Mesquita Nogueira

## Tecnologias

PHP 8.3 (PHP-FPM) · Nginx · MySQL 8.0 · Bootstrap 5.3.3 · Docker e Docker Compose

## Situação atual

O banco de dados está modelado (11 tabelas). Na aplicação, já funcionam o login, o painel com indicadores e alertas, a gestão de usuários e a página "minha conta". Os demais módulos (veículos, motoristas, percursos, abastecimentos, manutenções, relatórios etc.) estão criados como esqueleto, ainda sem implementação. O detalhamento está em [docs/06-estrutura-do-projeto.md](docs/06-estrutura-do-projeto.md).

## Como executar

Pré-requisito: Docker com Docker Compose.

```bash
# 1. Apenas na primeira vez: o docker-compose.yml exige uma rede externa chamada "coolify".
#    (Em uma instância do Coolify ela já existe.)
docker network create coolify

# 2. Construir e subir os serviços
docker compose up -d --build
```

A aplicação fica disponível em **http://localhost:8090**.

O script `database/controle.sql` é executado automaticamente **apenas na primeira inicialização** do volume `db_data`. Para recriar o banco do zero (apaga todos os dados):

```bash
docker compose down -v
docker compose up -d --build
```

### Primeiro acesso

O script cria as tabelas, mas nenhum usuário. Para criar o primeiro administrador:

1. Gere o hash da senha (troque `SUA_SENHA_AQUI`):

   ```bash
   docker compose exec web php -r "echo password_hash('SUA_SENHA_AQUI', PASSWORD_DEFAULT), PHP_EOL;"
   ```

2. Abra o cliente MySQL (a senha do usuário `controle` está em `MYSQL_PASSWORD`, no `docker-compose.yml`):

   ```bash
   docker compose exec db mysql -ucontrole -p controle_de_frota
   ```

3. Insira o usuário, colando o hash gerado no passo 1:

   ```sql
   INSERT INTO usuario (nome, email, senha, perfil)
   VALUES ('Administrador', 'admin@exemplo.com', '<HASH_GERADO>', 'admin');
   ```

Depois, entre em `/login.php` com esse e-mail e a senha escolhida.

> As credenciais do banco no `docker-compose.yml` servem apenas para desenvolvimento. Veja [docs/07-pendencias-e-evolucao.md](docs/07-pendencias-e-evolucao.md#p9-segurança) antes de qualquer uso real.

## Documentação

Toda a documentação técnica está em [`docs/`](docs/README.md):

| Documento | Conteúdo |
|---|---|
| [Estudo de caso](docs/01-estudo-de-caso.md) | Problema, objetivos, escopo e atores |
| [Requisitos](docs/02-requisitos.md) | RF, RNF, regras de negócio e rastreabilidade |
| [Modelo conceitual](docs/03-modelo-conceitual.md) | MER |
| [Modelo lógico](docs/04-modelo-logico.md) | DER e normalização |
| [Dicionário de dados](docs/05-dicionario-de-dados.md) | Tabelas e colunas |
| [Estrutura do projeto](docs/06-estrutura-do-projeto.md) | Arquitetura e pastas |
| [Pendências e evolução](docs/07-pendencias-e-evolucao.md) | Divergências e melhorias |
