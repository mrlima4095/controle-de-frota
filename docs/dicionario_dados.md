# Dicionário de Dados — Sistema de Controle de Frota

Este documento detalha o modelo lógico apresentado em `modelo_logico.mermaid`, servindo de base para o `script_fisico.sql`.

---

## 1. DEPARTAMENTO

| Coluna | Tipo | Restrições | Descrição |
|---|---|---|---|
| id | INT | PK, AUTO_INCREMENT | Identificador |
| nome | VARCHAR(100) | NOT NULL | Nome do departamento |
| descricao | VARCHAR(255) | NULL | Descrição opcional |

---

## 2. USUARIO

| Coluna | Tipo | Restrições | Descrição |
|---|---|---|---|
| id | INT | PK, AUTO_INCREMENT | Identificador |
| nome | VARCHAR(100) | NOT NULL | Nome do usuário |
| email | VARCHAR(150) | NOT NULL, UNIQUE | Login |
| senha_hash | VARCHAR(255) | NOT NULL | Senha criptografada (password_hash) |
| perfil | ENUM('admin','gestor','motorista') | NOT NULL | Nível de acesso |
| ativo | BOOLEAN | NOT NULL, DEFAULT TRUE | Conta ativa/inativa |
| motorista_id | INT | FK → MOTORISTA.id, NULL | Preenchido se o usuário for um motorista |

**Regra de negócio:** perfis `admin`/`gestor` normalmente têm `motorista_id` nulo.

---

## 3. MOTORISTA

| Coluna | Tipo | Restrições | Descrição |
|---|---|---|---|
| id | INT | PK, AUTO_INCREMENT | Identificador |
| nome | VARCHAR(100) | NOT NULL | Nome completo |
| cpf | VARCHAR(14) | NOT NULL, UNIQUE | CPF (validar formato) |
| cnh | VARCHAR(20) | NOT NULL, UNIQUE | Número da CNH |
| categoria_cnh | VARCHAR(5) | NOT NULL | A, B, C, D, E, AB etc. |
| validade_cnh | DATE | NOT NULL | Usada para alertar vencimento |
| telefone | VARCHAR(20) | NULL | |
| email | VARCHAR(150) | NULL | |
| ativo | BOOLEAN | NOT NULL, DEFAULT TRUE | |
| departamento_id | INT | FK → DEPARTAMENTO.id, NULL | |

**Regra de negócio:** sistema deve alertar quando `validade_cnh` estiver próxima ou vencida antes de permitir novo percurso/reserva.

---

## 4. CLIENTE

| Coluna | Tipo | Restrições | Descrição |
|---|---|---|---|
| id | INT | PK, AUTO_INCREMENT | Identificador |
| nome_razao_social | VARCHAR(150) | NOT NULL | PF ou PJ |
| cpf_cnpj | VARCHAR(18) | NOT NULL, UNIQUE | Validar CPF ou CNPJ |
| telefone | VARCHAR(20) | NULL | |
| email | VARCHAR(150) | NULL | |
| endereco | VARCHAR(255) | NULL | |
| ativo | BOOLEAN | NOT NULL, DEFAULT TRUE | |

---

## 5. VEICULO

| Coluna | Tipo | Restrições | Descrição |
|---|---|---|---|
| id | INT | PK, AUTO_INCREMENT | Identificador |
| placa | VARCHAR(8) | NOT NULL, UNIQUE | Padrão Mercosul ou antigo |
| renavam | VARCHAR(20) | NOT NULL, UNIQUE | |
| marca | VARCHAR(50) | NOT NULL | |
| modelo | VARCHAR(50) | NOT NULL | |
| ano_fabricacao | SMALLINT | NOT NULL | |
| ano_modelo | SMALLINT | NOT NULL | |
| cor | VARCHAR(30) | NULL | |
| tipo | VARCHAR(30) | NOT NULL | carro, van, caminhão, moto etc. |
| finalidade | ENUM('interno','locacao','ambos') | NOT NULL | Define se aparece em Percurso e/ou Reserva |
| km_atual | INT | NOT NULL, DEFAULT 0 | Atualizado a cada percurso/reserva |
| status | ENUM('disponivel','em_uso','manutencao','inativo') | NOT NULL, DEFAULT 'disponivel' | Controla disponibilidade |
| departamento_id | INT | FK → DEPARTAMENTO.id, NULL | |

**Regra de negócio:** um veículo com `finalidade = 'interno'` não deveria aparecer disponível para RESERVA, e vice-versa (validar na aplicação/Controller).

---

## 6. PERCURSO (uso interno)

| Coluna | Tipo | Restrições | Descrição |
|---|---|---|---|
| id | INT | PK, AUTO_INCREMENT | |
| veiculo_id | INT | FK → VEICULO.id, NOT NULL | |
| motorista_id | INT | FK → MOTORISTA.id, NOT NULL | |
| data_saida | DATE | NOT NULL | |
| hora_saida | TIME | NOT NULL | |
| km_saida | INT | NOT NULL | |
| data_retorno | DATE | NULL | Nulo enquanto em andamento |
| hora_retorno | TIME | NULL | |
| km_retorno | INT | NULL | |
| destino | VARCHAR(150) | NOT NULL | |
| finalidade | VARCHAR(150) | NULL | Motivo do trajeto |
| observacoes | TEXT | NULL | |

**Regra de negócio:** `km_retorno` deve ser ≥ `km_saida`; ao registrar retorno, atualizar `VEICULO.km_atual`.

---

## 7. RESERVA (uso externo / locação)

| Coluna | Tipo | Restrições | Descrição |
|---|---|---|---|
| id | INT | PK, AUTO_INCREMENT | |
| veiculo_id | INT | FK → VEICULO.id, NOT NULL | |
| cliente_id | INT | FK → CLIENTE.id, NOT NULL | |
| motorista_id | INT | FK → MOTORISTA.id, NULL | Preenchido se a empresa fornece motorista |
| data_inicio_prevista | DATETIME | NOT NULL | |
| data_fim_prevista | DATETIME | NOT NULL | |
| data_inicio_real | DATETIME | NULL | |
| data_fim_real | DATETIME | NULL | |
| km_saida | INT | NULL | |
| km_retorno | INT | NULL | |
| valor_diaria | DECIMAL(10,2) | NOT NULL | |
| valor_total | DECIMAL(10,2) | NULL | Calculado ao fechar |
| status | ENUM('reservado','em_andamento','concluida','cancelada') | NOT NULL, DEFAULT 'reservado' | |
| observacoes | TEXT | NULL | |

**Regra de negócio:** não permitir duas reservas com o mesmo `veiculo_id` em períodos sobrepostos (`data_inicio_prevista`/`data_fim_prevista`) com status ativo.

---

## 8. MANUTENCAO

| Coluna | Tipo | Restrições | Descrição |
|---|---|---|---|
| id | INT | PK, AUTO_INCREMENT | |
| veiculo_id | INT | FK → VEICULO.id, NOT NULL | |
| tipo | ENUM('preventiva','corretiva') | NOT NULL | |
| descricao | TEXT | NOT NULL | |
| data_entrada | DATE | NOT NULL | |
| data_saida | DATE | NULL | |
| km_manutencao | INT | NOT NULL | |
| custo | DECIMAL(10,2) | NULL | |
| oficina | VARCHAR(100) | NULL | |
| status | ENUM('agendada','em_andamento','concluida') | NOT NULL, DEFAULT 'agendada' | |

**Regra de negócio:** enquanto `status <> 'concluida'`, o `VEICULO.status` correspondente deve ser `'manutencao'`.

---

## 9. ABASTECIMENTO

| Coluna | Tipo | Restrições | Descrição |
|---|---|---|---|
| id | INT | PK, AUTO_INCREMENT | |
| veiculo_id | INT | FK → VEICULO.id, NOT NULL | |
| motorista_id | INT | FK → MOTORISTA.id, NULL | |
| data | DATE | NOT NULL | |
| km | INT | NOT NULL | |
| litros | DECIMAL(8,2) | NOT NULL | |
| valor_litro | DECIMAL(6,3) | NOT NULL | |
| valor_total | DECIMAL(10,2) | NOT NULL | litros × valor_litro |
| tipo_combustivel | VARCHAR(20) | NOT NULL | gasolina, etanol, diesel, GNV |
| posto | VARCHAR(100) | NULL | |

---

## 10. MULTA

| Coluna | Tipo | Restrições | Descrição |
|---|---|---|---|
| id | INT | PK, AUTO_INCREMENT | |
| veiculo_id | INT | FK → VEICULO.id, NOT NULL | |
| motorista_id | INT | FK → MOTORISTA.id, NULL | |
| cliente_id | INT | FK → CLIENTE.id, NULL | |
| data_infracao | DATE | NOT NULL | |
| data_vencimento | DATE | NOT NULL | |
| valor | DECIMAL(10,2) | NOT NULL | |
| descricao | TEXT | NULL | |
| orgao_autuador | VARCHAR(100) | NULL | DETRAN, PRF etc. |
| status_pagamento | ENUM('pendente','pago','recurso') | NOT NULL, DEFAULT 'pendente' | |

**Regra de negócio crítica:** exatamente um entre `motorista_id` e `cliente_id` deve estar preenchido (nunca os dois, nunca nenhum). Em MySQL 8+, isso pode ser garantido com um `CHECK`:

```sql
CHECK (
  (motorista_id IS NOT NULL AND cliente_id IS NULL) OR
  (motorista_id IS NULL AND cliente_id IS NOT NULL)
)
```

---

## Resumo de relacionamentos (cardinalidade)

| Relacionamento | Cardinalidade |
|---|---|
| Departamento → Veículo | 1:N |
| Motorista → Usuário | 1:0..1 |
| Motorista → Percurso | 1:N |
| Veículo → Percurso | 1:N |
| Veículo → Reserva | 1:N |
| Cliente → Reserva | 1:N |
| Motorista → Reserva | 1:0..N (opcional) |
| Veículo → Manutenção | 1:N |
| Veículo → Abastecimento | 1:N |
| Motorista → Abastecimento | 1:0..N (opcional) |
| Veículo → Multa | 1:N |
| Motorista → Multa | 1:0..N (opcional, exclusivo com Cliente) |
| Cliente → Multa | 1:0..N (opcional, exclusivo com Motorista) |

Nenhum relacionamento N:M foi identificado nos requisitos atuais — todas as tabelas de fato (Percurso, Reserva, Manutenção, Abastecimento, Multa) já funcionam como entidades associativas com atributos próprios, então não são necessárias tabelas de junção adicionais.
