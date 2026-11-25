# 05. Dicionário de Dados

Descrição de todas as tabelas e colunas do banco `controle_de_frota`, conforme o script físico [`database/controle.sql`](../database/controle.sql). O dicionário é coerente com o [modelo lógico](04-modelo-logico.md) e com o banco físico, como exige o guia do projeto.

> **Gerado a partir do script SQL.** Estrutura (tipos, tamanhos, chaves, nulidade e valores padrão) extraída automaticamente de `controle.sql`: 11 tabelas, 118 colunas e 12 chaves estrangeiras. Ao alterar o script, atualize este documento.

## Índice de tabelas

| Tabela | Entidade | Colunas | Descrição |
|---|---|---:|---|
| [`departamento`](#departamento) | Departamento | 5 | Setor da empresa responsável por veículos e solicitante de reservas internas. |
| [`usuario`](#usuario) | Usuário | 8 | Pessoa com login no sistema. É independente de `motorista` (ver RN05). |
| [`cliente`](#cliente) | Cliente | 10 | Pessoa física ou jurídica externa que pode solicitar reservas de veículos. |
| [`motorista`](#motorista) | Motorista | 11 | Condutor cadastrado dos veículos da frota. Não possui login (ver RN05). |
| [`veiculo`](#veiculo) | Veículo | 17 | Veículo da frota, vinculado obrigatoriamente a um departamento. |
| [`reserva`](#reserva) | Reserva | 12 | Intenção de uso futuro de um veículo, por um cliente ou departamento, em data e horário definidos. |
| [`percurso`](#percurso) | Percurso | 12 | Registro do uso efetivamente realizado de um veículo por um motorista. |
| [`manutencao`](#manutencao) | Manutenção | 11 | Histórico de manutenções (preventivas, corretivas e revisões) de cada veículo. |
| [`abastecimento`](#abastecimento) | Abastecimento | 11 | Histórico de abastecimentos de cada veículo; base para o cálculo de consumo (km/l). |
| [`multa`](#multa) | Multa | 13 | Multa de trânsito recebida por um veículo e, quando identificado, atribuída a um motorista. |
| [`sessao`](#sessao) | Sessão | 8 | Tabela de apoio à autenticação: sessões de login de um usuário. Hoje só é lida/limpa pela exclusão de conta. |

## Convenções

- **Tabelas:** nomes no singular, em minúsculas, sem acentos.
- **Chaves primárias:** inteiro autoincremental no formato `id_<abreviação>` (ex.: `id_vei`, `id_mot`). Todas as tabelas usam chave simples (substituta), sem chaves compostas.
- **Chaves estrangeiras:** mantêm o nome da coluna referenciada, com uma exceção: `id_departamento` (PK) é referenciada como `id_dep` (FK).
- **Inativação lógica:** as tabelas `departamento`, `usuario`, `cliente`, `motorista`, `veiculo` e `sessao` possuem o campo `ativo`, usado para preservar o histórico (RN04).
- **Auditoria:** `data_criacao` registra a criação da linha (`TIMESTAMP` com padrão `CURRENT_TIMESTAMP`). A tabela `sessao` usa `data_ini`/`data_fim`.
- **Integridade referencial:** nenhuma FK define `ON DELETE`; vale o comportamento padrão do InnoDB (`RESTRICT`), que impede excluir registros com histórico.
- **Motor e codificação:** o script não define `ENGINE` nem `CHARSET`; o MySQL 8.0 usa InnoDB e `utf8mb4` por padrão. A conexão PHP também usa `utf8mb4`.
- **Leitura das tabelas abaixo:** *Tam./Valores* mostra o tamanho do tipo ou, para `ENUM`, os valores permitidos. *Nulo = Não* equivale a `NOT NULL`. Colunas únicas estão marcadas com **(único)** na descrição.

---

## departamento

**Entidade:** Departamento. Setor da empresa responsável por veículos e solicitante de reservas internas.

| Campo | Tipo | Tam./Valores | PK | FK | Nulo | Padrão | Descrição |
|---|---|---|:--:|---|:--:|---|---|
| `id_departamento` | INT | — | Sim | Não | Não | — | Identificador do departamento. Autoincremental. |
| `nome` | VARCHAR | 100 | Não | Não | Não | — | Nome do departamento (ex.: Comercial, Logística). |
| `responsavel` | VARCHAR | 100 | Não | Não | Sim | — | Nome do responsável pelo departamento. |
| `ativo` | BOOLEAN | — | Não | Não | Sim | TRUE | Indica se o departamento está ativo (inativação lógica, ver RN04). |
| `data_criacao` | TIMESTAMP | — | Não | Não | Sim | CURRENT_TIMESTAMP | Data e hora de criação do registro. |

---

## usuario

**Entidade:** Usuário. Pessoa com login no sistema. É independente de `motorista` (ver RN05).

| Campo | Tipo | Tam./Valores | PK | FK | Nulo | Padrão | Descrição |
|---|---|---|:--:|---|:--:|---|---|
| `id_usr` | INT | — | Sim | Não | Não | — | Identificador do usuário. Autoincremental. |
| `nome` | VARCHAR | 100 | Não | Não | Não | — | Nome do usuário. |
| `email` | VARCHAR | 100 | Não | Não | Não | — | E-mail do usuário, usado como login. **(único)** |
| `senha` | VARCHAR | 255 | Não | Não | Não | — | Hash da senha gerado com `password_hash()` do PHP; nunca texto puro. |
| `perfil` | ENUM | admin, operador, visualizador | Não | Não | Sim | operador | Perfil de acesso do usuário (ver nota abaixo da tabela). |
| `ativo` | BOOLEAN | — | Não | Não | Sim | TRUE | Indica se a conta está ativa; contas inativas não conseguem entrar. |
| `data_criacao` | TIMESTAMP | — | Não | Não | Sim | CURRENT_TIMESTAMP | Data e hora de criação do registro. |
| `ultima_consessao` | TIMESTAMP | — | Não | Não | Sim | — | Data e hora do último login. O nome contém erro de grafia ("sessão"), mantido porque o código PHP depende dele. |

> **Perfis:** o ENUM do banco aceita `admin`, `operador` e `visualizador`, mas o código PHP também oferece `gerente` e `rh` e usa esses dois para liberar a gestão de usuários. Essa divergência está detalhada em [07-pendencias-e-evolucao.md](07-pendencias-e-evolucao.md) (item P1).

---

## cliente

**Entidade:** Cliente. Pessoa física ou jurídica externa que pode solicitar reservas de veículos.

| Campo | Tipo | Tam./Valores | PK | FK | Nulo | Padrão | Descrição |
|---|---|---|:--:|---|:--:|---|---|
| `id_cli` | INT | — | Sim | Não | Não | — | Identificador do cliente. Autoincremental. |
| `nome` | VARCHAR | 150 | Não | Não | Não | — | Nome (pessoa física) ou razão social (pessoa jurídica). |
| `cnpj_cpf` | VARCHAR | 18 | Não | Não | Sim | — | CPF (pessoa física) ou CNPJ (pessoa jurídica). |
| `ie_rg` | VARCHAR | 20 | Não | Não | Sim | — | Inscrição estadual (pessoa jurídica) ou RG (pessoa física). |
| `telefone` | VARCHAR | 20 | Não | Não | Sim | — | Telefone de contato. |
| `email` | VARCHAR | 100 | Não | Não | Sim | — | E-mail de contato. |
| `uf` | CHAR | 2 | Não | Não | Sim | — | Sigla da unidade federativa (2 letras). |
| `cidade` | VARCHAR | 100 | Não | Não | Sim | — | Cidade do cliente. |
| `ativo` | BOOLEAN | — | Não | Não | Sim | TRUE | Indica se o cliente está ativo (inativação lógica, ver RN04). |
| `data_criacao` | TIMESTAMP | — | Não | Não | Sim | CURRENT_TIMESTAMP | Data e hora de criação do registro. |

---

## motorista

**Entidade:** Motorista. Condutor cadastrado dos veículos da frota. Não possui login (ver RN05).

| Campo | Tipo | Tam./Valores | PK | FK | Nulo | Padrão | Descrição |
|---|---|---|:--:|---|:--:|---|---|
| `id_mot` | INT | — | Sim | Não | Não | — | Identificador do motorista. Autoincremental. |
| `nome` | VARCHAR | 150 | Não | Não | Não | — | Nome completo do motorista. |
| `cpf` | VARCHAR | 14 | Não | Não | Não | — | CPF do motorista. **(único)** |
| `cnh` | VARCHAR | 20 | Não | Não | Sim | — | Número da CNH. |
| `validade_cnh` | DATE | — | Não | Não | Sim | — | Data de validade da CNH; base para o alerta de vencimento (RN07). |
| `telefone` | VARCHAR | 20 | Não | Não | Sim | — | Telefone de contato. |
| `email` | VARCHAR | 100 | Não | Não | Sim | — | E-mail de contato. |
| `uf` | CHAR | 2 | Não | Não | Sim | — | Sigla da unidade federativa (2 letras). |
| `cidade` | VARCHAR | 100 | Não | Não | Sim | — | Cidade do motorista. |
| `ativo` | BOOLEAN | — | Não | Não | Sim | TRUE | Indica se o motorista está ativo (inativação lógica, ver RN04). |
| `data_criacao` | TIMESTAMP | — | Não | Não | Sim | CURRENT_TIMESTAMP | Data e hora de criação do registro. |

---

## veiculo

**Entidade:** Veículo. Veículo da frota, vinculado obrigatoriamente a um departamento.

| Campo | Tipo | Tam./Valores | PK | FK | Nulo | Padrão | Descrição |
|---|---|---|:--:|---|:--:|---|---|
| `id_vei` | INT | — | Sim | Não | Não | — | Identificador do veículo. Autoincremental. |
| `placa` | VARCHAR | 8 | Não | Não | Não | — | Placa do veículo (padrão antigo ou Mercosul). **(único)** |
| `marca` | VARCHAR | 50 | Não | Não | Sim | — | Marca do veículo. |
| `modelo` | VARCHAR | 50 | Não | Não | Sim | — | Modelo do veículo. |
| `ano_fabricacao` | INT | — | Não | Não | Sim | — | Ano de fabricação. |
| `ano_modelo` | INT | — | Não | Não | Sim | — | Ano do modelo. |
| `cor` | VARCHAR | 30 | Não | Não | Sim | — | Cor do veículo. |
| `tipo` | ENUM | carro, moto, caminhao, van, onibus | Não | Não | Sim | carro | Categoria do veículo. |
| `chassi` | VARCHAR | 17 | Não | Não | Sim | — | Número do chassi (17 caracteres). |
| `combustivel` | ENUM | flex, gasolina, diesel, eletrico | Não | Não | Sim | flex | Tipo de combustível utilizado. |
| `capacidade` | INT | — | Não | Não | Sim | — | Capacidade do veículo. A unidade (passageiros ou carga) não está definida no script. |
| `quilometragem_inicial` | INT | — | Não | Não | Sim | 0 | Quilometragem do veículo no momento do cadastro. |
| `quilometragemAtual` | INT | — | Não | Não | Sim | 0 | Quilometragem atual (hodômetro). O nome foge do padrão `snake_case` do restante do banco. |
| `status` | ENUM | disponivel, em_uso, manutencao, inativo | Não | Não | Sim | disponivel | Situação operacional do veículo; controla a disponibilidade (RN02). |
| `id_dep` | INT | — | Não | Sim → `departamento.id_departamento` | Não | — | Departamento responsável pelo veículo (RN06). |
| `ativo` | BOOLEAN | — | Não | Não | Sim | TRUE | Indica se o veículo está ativo (inativação lógica, ver RN04). |
| `data_criacao` | TIMESTAMP | — | Não | Não | Sim | CURRENT_TIMESTAMP | Data e hora de criação do registro. |

> **Disponibilidade:** o campo `status` reflete se o veículo pode ser reservado ou usado em percursos (RN02). Veículos `em_uso`, `manutencao` ou `inativo` não estão disponíveis.

---

## reserva

**Entidade:** Reserva. Intenção de uso futuro de um veículo, por um cliente ou departamento, em data e horário definidos.

| Campo | Tipo | Tam./Valores | PK | FK | Nulo | Padrão | Descrição |
|---|---|---|:--:|---|:--:|---|---|
| `id_res` | INT | — | Sim | Não | Não | — | Identificador da reserva. Autoincremental. |
| `id_vei` | INT | — | Não | Sim → `veiculo.id_vei` | Não | — | Veículo reservado. |
| `id_cli` | INT | — | Não | Sim → `cliente.id_cli` | Sim | — | Cliente solicitante, quando a reserva atende a um cliente externo. |
| `id_dep` | INT | — | Não | Sim → `departamento.id_departamento` | Sim | — | Departamento solicitante, quando a reserva é de uso interno. |
| `data_reserva` | DATE | — | Não | Não | Não | — | Data da reserva. |
| `hora_ini` | TIME | — | Não | Não | Não | — | Horário previsto de início. |
| `hora_fim` | TIME | — | Não | Não | Não | — | Horário previsto de término. |
| `origem` | VARCHAR | 200 | Não | Não | Sim | — | Local de origem. |
| `destino` | VARCHAR | 200 | Não | Não | Sim | — | Local de destino. |
| `justificativa` | TEXT | — | Não | Não | Sim | — | Motivo ou justificativa da reserva. |
| `status` | ENUM | pendente, confirmada, cancelada, concluida | Não | Não | Sim | pendente | Situação da reserva; apenas reservas confirmadas bloqueiam o veículo (RN01). |
| `data_criacao` | TIMESTAMP | — | Não | Não | Sim | CURRENT_TIMESTAMP | Data e hora de criação do registro. |

> **Solicitante:** `id_cli` e `id_dep` são opcionais, e o banco não obriga que pelo menos um dos dois seja informado (RN08).

> **Conflitos:** o banco não impede reservas sobrepostas para o mesmo veículo; essa validação é responsabilidade da aplicação (RN01).

---

## percurso

**Entidade:** Percurso. Registro do uso efetivamente realizado de um veículo por um motorista.

| Campo | Tipo | Tam./Valores | PK | FK | Nulo | Padrão | Descrição |
|---|---|---|:--:|---|:--:|---|---|
| `id_perc` | INT | — | Sim | Não | Não | — | Identificador do percurso. Autoincremental. |
| `id_vei` | INT | — | Não | Sim → `veiculo.id_vei` | Não | — | Veículo utilizado. |
| `id_mot` | INT | — | Não | Sim → `motorista.id_mot` | Não | — | Motorista que realizou o percurso. |
| `id_res` | INT | — | Não | Sim → `reserva.id_res` | Sim | — | Reserva que originou o percurso, quando houver. |
| `data_per` | DATE | — | Não | Não | Não | — | Data do percurso. |
| `hora_ini` | TIME | — | Não | Não | Não | — | Horário de saída. |
| `hora_fim` | TIME | — | Não | Não | Não | — | Horário de chegada. |
| `origem` | VARCHAR | 200 | Não | Não | Não | — | Local de origem. |
| `destino` | VARCHAR | 200 | Não | Não | Não | — | Local de destino. |
| `quilometragem` | INT | — | Não | Não | Não | — | Quilometragem registrada no percurso (distância percorrida ou leitura do hodômetro: a definir, ver pendências). |
| `observacoes` | TEXT | — | Não | Não | Sim | — | Observações livres. |
| `data_criacao` | TIMESTAMP | — | Não | Não | Sim | CURRENT_TIMESTAMP | Data e hora de criação do registro. |

---

## manutencao

**Entidade:** Manutenção. Histórico de manutenções (preventivas, corretivas e revisões) de cada veículo.

| Campo | Tipo | Tam./Valores | PK | FK | Nulo | Padrão | Descrição |
|---|---|---|:--:|---|:--:|---|---|
| `id_man` | INT | — | Sim | Não | Não | — | Identificador da manutenção. Autoincremental. |
| `id_vei` | INT | — | Não | Sim → `veiculo.id_vei` | Não | — | Veículo que recebeu a manutenção. |
| `tipo` | ENUM | preventiva, corretiva, revisao | Não | Não | Não | — | Natureza da manutenção. |
| `data_man` | DATE | — | Não | Não | Não | — | Data em que a manutenção foi realizada. |
| `oficina` | VARCHAR | 200 | Não | Não | Sim | — | Nome da oficina. |
| `servico` | TEXT | — | Não | Não | Sim | — | Descrição do serviço executado. |
| `custo` | DECIMAL | 10,2 | Não | Não | Sim | 0 | Custo total da manutenção, em reais. |
| `quilometragem` | INT | — | Não | Não | Sim | — | Quilometragem do veículo na manutenção. |
| `observacoes` | TEXT | — | Não | Não | Sim | — | Observações livres. |
| `proxima_data` | DATE | — | Não | Não | Sim | — | Data prevista para a próxima manutenção; alimenta os alertas do painel. |
| `data_criacao` | TIMESTAMP | — | Não | Não | Sim | CURRENT_TIMESTAMP | Data e hora de criação do registro. |

---

## abastecimento

**Entidade:** Abastecimento. Histórico de abastecimentos de cada veículo; base para o cálculo de consumo (km/l).

| Campo | Tipo | Tam./Valores | PK | FK | Nulo | Padrão | Descrição |
|---|---|---|:--:|---|:--:|---|---|
| `id_aba` | INT | — | Sim | Não | Não | — | Identificador do abastecimento. Autoincremental. |
| `id_vei` | INT | — | Não | Sim → `veiculo.id_vei` | Não | — | Veículo abastecido. |
| `data_aba` | DATE | — | Não | Não | Não | — | Data do abastecimento. |
| `hora_aba` | TIME | — | Não | Não | Não | — | Hora do abastecimento. |
| `posto` | VARCHAR | 200 | Não | Não | Sim | — | Nome do posto. |
| `litros` | DECIMAL | 8,2 | Não | Não | Não | — | Quantidade abastecida, em litros. |
| `valor` | DECIMAL | 10,2 | Não | Não | Não | — | Valor do abastecimento, em reais (total pago; interpretação a confirmar, ver pendências). |
| `quilometragem` | INT | — | Não | Não | Não | — | Quilometragem do veículo no abastecimento; base para o consumo médio (km/l). |
| `tipo_combustivel` | ENUM | flex, gasolina, diesel, eletrico | Não | Não | Sim | — | Combustível utilizado. |
| `observacoes` | TEXT | — | Não | Não | Sim | — | Observações livres. |
| `data_criacao` | TIMESTAMP | — | Não | Não | Sim | CURRENT_TIMESTAMP | Data e hora de criação do registro. |

---

## multa

**Entidade:** Multa. Multa de trânsito recebida por um veículo e, quando identificado, atribuída a um motorista.

| Campo | Tipo | Tam./Valores | PK | FK | Nulo | Padrão | Descrição |
|---|---|---|:--:|---|:--:|---|---|
| `id_mul` | INT | — | Sim | Não | Não | — | Identificador da multa. Autoincremental. |
| `id_vei` | INT | — | Não | Sim → `veiculo.id_vei` | Não | — | Veículo autuado. |
| `id_mot` | INT | — | Não | Sim → `motorista.id_mot` | Sim | — | Motorista responsável, quando identificado (RN03). |
| `data_infra` | DATE | — | Não | Não | Não | — | Data da infração. |
| `hora_infra` | TIME | — | Não | Não | Sim | — | Hora da infração. |
| `local` | VARCHAR | 200 | Não | Não | Sim | — | Local onde ocorreu a infração. |
| `infracao` | VARCHAR | 200 | Não | Não | Não | — | Descrição da infração. |
| `valor_multa` | DECIMAL | 10,2 | Não | Não | Não | — | Valor da multa, em reais. |
| `pontos` | INT | — | Não | Não | Sim | 0 | Pontos na CNH atribuídos pela infração. |
| `status` | ENUM | pendente, paga, cancelada | Não | Não | Sim | pendente | Situação do pagamento da multa. |
| `data_pagamento` | DATE | — | Não | Não | Sim | — | Data em que a multa foi paga. |
| `observacoes` | TEXT | — | Não | Não | Sim | — | Observações livres. |
| `data_criacao` | TIMESTAMP | — | Não | Não | Sim | CURRENT_TIMESTAMP | Data e hora de criação do registro. |

> **Responsável:** `id_mot` é opcional porque a multa pode chegar antes de o condutor ser identificado (RN03).

---

## sessao

**Entidade:** Sessão. Tabela de apoio à autenticação: sessões de login de um usuário. Hoje só é lida/limpa pela exclusão de conta.

| Campo | Tipo | Tam./Valores | PK | FK | Nulo | Padrão | Descrição |
|---|---|---|:--:|---|:--:|---|---|
| `id_sess` | INT | — | Sim | Não | Não | — | Identificador da sessão. Autoincremental. |
| `id_usr` | INT | — | Não | Sim → `usuario.id_usr` | Não | — | Usuário dono da sessão. |
| `token` | VARCHAR | 255 | Não | Não | Não | — | Token único da sessão. **(único)** |
| `data_ini` | TIMESTAMP | — | Não | Não | Sim | CURRENT_TIMESTAMP | Data e hora de início da sessão. |
| `data_fim` | TIMESTAMP | — | Não | Não | Sim | — | Data e hora de encerramento da sessão. |
| `ip` | VARCHAR | 45 | Não | Não | Sim | — | Endereço IP de origem (45 caracteres comportam IPv6). |
| `navigator` | VARCHAR | 255 | Não | Não | Sim | — | Identificação do navegador (user agent). |
| `ativo` | BOOLEAN | — | Não | Não | Sim | TRUE | Indica se a sessão está ativa. |

> A autenticação atual usa a sessão nativa do PHP (`$_SESSION`); esta tabela ainda não é preenchida no login.

---

## Resumo dos relacionamentos

Derivado das chaves estrangeiras do script. Detalhes e diagrama em [03-modelo-conceitual.md](03-modelo-conceitual.md) e [04-modelo-logico.md](04-modelo-logico.md).

| Tabela filha | Coluna FK | Tabela pai | Obrigatória |
|---|---|---|:--:|
| `veiculo` | `id_dep` | `departamento` (`id_departamento`) | Sim |
| `reserva` | `id_vei` | `veiculo` (`id_vei`) | Sim |
| `reserva` | `id_cli` | `cliente` (`id_cli`) | Não |
| `reserva` | `id_dep` | `departamento` (`id_departamento`) | Não |
| `percurso` | `id_vei` | `veiculo` (`id_vei`) | Sim |
| `percurso` | `id_mot` | `motorista` (`id_mot`) | Sim |
| `percurso` | `id_res` | `reserva` (`id_res`) | Não |
| `manutencao` | `id_vei` | `veiculo` (`id_vei`) | Sim |
| `abastecimento` | `id_vei` | `veiculo` (`id_vei`) | Sim |
| `multa` | `id_vei` | `veiculo` (`id_vei`) | Sim |
| `multa` | `id_mot` | `motorista` (`id_mot`) | Não |
| `sessao` | `id_usr` | `usuario` (`id_usr`) | Sim |

Não há relacionamentos N:M: `reserva`, `percurso`, `manutencao`, `abastecimento` e `multa` já são tabelas de fato com atributos próprios, portanto não são necessárias tabelas de junção.
