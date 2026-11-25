# 07. Pendências e Evolução

Divergências, riscos e melhorias identificados ao conferir a documentação, o banco e o código. A documentação deste repositório descreve o banco **como ele está hoje** (`database/controle.sql`); este documento registra o que está inconsistente ou ainda falta decidir.

**Prioridade:** **Alta** = causa erro ou bloqueia o uso · **Média** = inconsistência de projeto ou risco · **Baixa** = qualidade e organização.

| ID | Prioridade | Pendência |
|---|---|---|
| [P1](#p1-perfis-de-usuário-diferentes-entre-código-e-banco) | Alta | Perfis de usuário diferentes entre código e banco |
| [P2](#p2-não-existe-usuário-inicial) | Alta | Não existe usuário inicial |
| [P3](#p3-interface-e-banco-descritos-por-escopos-diferentes) | Média | Interface e banco descritos por escopos diferentes |
| [P4](#p4-recursos-do-desenho-anterior-que-não-estão-no-banco) | Média | Recursos do desenho anterior que não estão no banco |
| [P5](#p5-restrições-faltando-no-banco) | Média | Restrições faltando no banco |
| [P9](#p9-segurança) | Média | Credenciais versionadas e ausência de proteção CSRF |
| [P6](#p6-nomenclatura) | Baixa | Nomenclatura |
| [P7](#p7-campos-com-significado-ambíguo) | Baixa | Campos com significado ambíguo |
| [P8](#p8-arquivos-do-brmodelo-desatualizados) | Baixa | Arquivos do BrModelo desatualizados |
| [P10](#p10-instrução-de-importação-do-script-contraditória) | Baixa | Instrução de importação do script contraditória |

---

## P1. Perfis de usuário diferentes entre código e banco

- **Banco:** `usuario.perfil` aceita `admin`, `operador` e `visualizador`.
- **Código:** `dashboard/usuarios/create.php` oferece `admin`, `gerente`, `rh`, `operador` e `visualizador`, e `includes/auth.php` só libera a gestão de usuários para `admin`, `gerente` e `rh`.

**Efeito:** criar ou editar um usuário com perfil `gerente` ou `rh` falha no MySQL em modo estrito (padrão do MySQL 8). A tela de login ainda orienta quem precisa de acesso a procurar "o gerente ou o RH".

**Ação:** decidir o conjunto de perfis e alinhar os dois lados. Se `gerente` e `rh` forem mantidos:

```sql
ALTER TABLE usuario
  MODIFY perfil ENUM('admin','gerente','rh','operador','visualizador') NOT NULL DEFAULT 'operador';
```

Em seguida, atualizar o `CREATE TABLE usuario` em `controle.sql`, o [dicionário](05-dicionario-de-dados.md#usuario) e a RN09.

## P2. Não existe usuário inicial

O script cria as tabelas, mas nenhum usuário, e não há tela pública de cadastro. Sem um primeiro usuário, ninguém consegue entrar. O procedimento manual está no [README](../README.md#primeiro-acesso).

**Ação sugerida:** criar um script `database/dados-iniciais.sql` para ambiente de desenvolvimento (usuário administrador e alguns registros de exemplo), mantendo a senha fora do repositório.

## P3. Interface e banco descritos por escopos diferentes

O menu (`includes/header.php`) e a pasta `dashboard/` descrevem um sistema de frota mais amplo do que o banco modela:

| Existe na interface, mas não no banco | Existe no banco, mas não na interface |
|---|---|
| Pneus, Despesas, Fornecedores, Documentos, Sinistros, Empresas | Reservas, Clientes, Multas, Departamentos |

Além disso, a interface chama de **Viagens** o que o banco chama de **percurso**.

**Ação:** decidir, para cada módulo, entre (a) remover do menu e do esqueleto, (b) modelar no banco, ou (c) deixar como evolução futura. O guia do projeto trata a coerência entre modelo, banco e aplicação como critério de avaliação.

## P4. Recursos do desenho anterior que não estão no banco

A primeira versão do dicionário, do levantamento de entidades e do modelo BrModelo previa recursos que o script SQL atual **não contém**. Se algum deles ainda for desejado, é preciso incluí-lo no banco:

| Recurso do desenho anterior | Situação no banco atual | Consequência |
|---|---|---|
| `motorista.categoria_cnh` | Ausente | Não é possível validar se a categoria permite dirigir o tipo de veículo. |
| `veiculo.renavam` e `veiculo.finalidade` (interno, locação, ambos) | Ausentes | Não há como separar veículos de uso interno dos disponíveis para locação. |
| `usuario.motorista_id` (motorista com login) | Ausente | Motorista não pode acessar o sistema (coerente com a RN05 atual). |
| `motorista.departamento_id` | Ausente | Motorista não é lotado em departamento. |
| `reserva.motorista_id`, valor da diária, valor total, datas e quilometragem reais | Ausentes | A reserva não registra motorista nem cobrança, só o agendamento. |
| `multa.cliente_id`, com `CHECK` de exclusividade com `motorista_id` | Ausente | Multas em veículo locado não podem ser atribuídas ao cliente. |
| `abastecimento.motorista_id` e `valor_litro` | Ausentes | Não se sabe quem abasteceu nem o preço por litro. |
| `manutencao.status`, `data_entrada`, `data_saida` | Ausentes | Só existe a data da manutenção e a data da próxima; não há controle de veículo parado na oficina. |
| `percurso` com saída/retorno separados (data, hora e km) | Substituído por uma única `quilometragem` e `hora_fim` obrigatório | Não é possível registrar um percurso em andamento. |

## P5. Restrições faltando no banco

1. **Colunas com valor padrão, mas sem `NOT NULL`:** `ativo`, `status`, `perfil`, `tipo`, `combustivel`, `data_criacao` etc. aceitam `NULL` se alguém o informar explicitamente. Uma linha com `ativo` nulo deixa de aparecer em consultas como `WHERE ativo = TRUE`.
2. **Sem `UNIQUE`:** `cliente.cnpj_cpf`, `veiculo.chassi` e `motorista.cnh` permitem duplicidade (já `motorista.cpf` é único). Também são opcionais, embora `validade_cnh` alimente alertas.
3. **Sem `CHECK`:** o MySQL 8.0 aplica restrições `CHECK`. Sugestões, **não executadas contra um MySQL**, para validar antes de adotar:

```sql
ALTER TABLE reserva
  ADD CONSTRAINT ck_reserva_solicitante CHECK (id_cli IS NOT NULL OR id_dep IS NOT NULL),
  ADD CONSTRAINT ck_reserva_horario     CHECK (hora_fim > hora_ini);

ALTER TABLE multa
  ADD CONSTRAINT ck_multa_valor CHECK (valor_multa >= 0),
  ADD CONSTRAINT ck_multa_pontos CHECK (pontos >= 0);
```

4. **Sobreposição de reservas (RN01)** não pode ser expressa por `CHECK`; continua sendo validação da aplicação (ou de uma *trigger*).

## P6. Nomenclatura

| Item | Problema | Impacto de renomear |
|---|---|---|
| `usuario.ultima_consessao` | Erro de grafia ("sessão") | Usado em 5 arquivos PHP: `login.php`, `usuario/index.php` e `dashboard/usuarios/` (`index`, `view`, `edit`). |
| `veiculo.quilometragemAtual` | `camelCase` num banco em `snake_case` | **Nenhum arquivo PHP usa ainda**: é o momento mais barato para renomear (`quilometragem_atual`). |
| `departamento.id_departamento` (PK) × `id_dep` (FK) | Nome da FK difere do nome da PK | Ao padronizar, alterar também as FKs de `veiculo` e `reserva`. |
| `sessao.navigator` | Armazena o *user agent* | Baixo. |

## P7. Campos com significado ambíguo

Confirmar com a equipe e registrar a definição no [dicionário](05-dicionario-de-dados.md):

- `veiculo.capacidade`: passageiros ou carga (e em que unidade)?
- `percurso.quilometragem`: distância percorrida ou leitura do hodômetro?
- `abastecimento.valor`: valor total pago ou preço por litro? (O dicionário assume o total.)
- Tabela `sessao`: o login atual usa a sessão nativa do PHP e não grava nela. Passar a usá-la ou remover?

## P8. Arquivos do BrModelo desatualizados

`conceitual-v1-entidades.brM` e `conceitual-v2-atributos.brM` (em `docs/modelagem/brmodelo/`) refletem o desenho anterior (ver P4) e não o banco atual. Opções: atualizar manualmente no BrModelo, ou manter apenas os diagramas Mermaid (que podem ser regenerados do script) e arquivar os `.brM` como histórico.

## P9. Segurança

- **Credenciais versionadas:** `docker-compose.yml` contém as senhas do banco em texto, e o domínio de implantação aparece nos rótulos do Traefik. Para uso real, mover os segredos para um arquivo `.env` fora do controle de versão (ou para o gerenciador de segredos da plataforma) e trocar as senhas atuais.
- **CSRF:** nenhum formulário envia token e `includes/csrf.php` está vazio. As ações que alteram dados (cadastro e edição de usuários, exclusão de conta) ficam sujeitas a requisições forjadas.

## P10. Instrução de importação do script contraditória

O `docker-compose.yml` comenta que o `controle.sql` "deve ser importado manualmente", mas o `database/Dockerfile` o copia para `/docker-entrypoint-initdb.d/`, o que o executa automaticamente na primeira inicialização do volume. O README descreve o comportamento real; o comentário do compose deve ser removido ou corrigido.
