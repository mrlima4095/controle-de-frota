# 02. Requisitos

Levantamento de requisitos do **Sistema de Controle de Frota**, seguindo a estrutura do guia do projeto (requisitos funcionais, não funcionais e regras de negócio). O problema e o escopo estão em [01-estudo-de-caso.md](01-estudo-de-caso.md).

> **Origem dos itens.** A coluna *Origem* indica de onde cada requisito veio: **Estudo de caso** (levantamento original da equipe), **Dicionário anterior** (primeira versão do dicionário de dados) ou **Código** (comportamento já presente no sistema e documentado aqui). Itens com origem *Código* foram acrescentados nesta revisão e devem ser validados pela equipe.

---

## 1. Requisitos funcionais

O que o sistema deve fazer. Os códigos RF01 a RF10 foram mantidos do levantamento original.

| Código | Requisito | Descrição | Origem |
|---|---|---|---|
| **RF01** | Autenticar usuário | O sistema deve permitir login e logout de usuários cadastrados, com controle de sessão. | Estudo de caso |
| **RF02** | Gerenciar cadastros básicos | O sistema deve permitir cadastrar, listar, editar e excluir (ou inativar, conforme RN04) motoristas, clientes, veículos e departamentos. | Estudo de caso |
| **RF03** | Registrar reserva | O sistema deve permitir registrar reservas de veículos, associando veículo, cliente ou departamento e o período desejado (data, hora de início e hora de término). | Estudo de caso |
| **RF04** | Controlar conflitos de reserva | O sistema deve impedir (ou alertar sobre) reservas de um mesmo veículo em períodos conflitantes. | Estudo de caso |
| **RF05** | Registrar percurso | O sistema deve permitir registrar percursos realizados, associando motorista, veículo, origem, destino, data e quilometragem. | Estudo de caso |
| **RF06** | Registrar manutenção | O sistema deve permitir registrar manutenções (tipo, custo, data, oficina) vinculadas a um veículo. | Estudo de caso |
| **RF07** | Registrar abastecimento | O sistema deve permitir registrar abastecimentos (litros, valor, quilometragem, posto) vinculados a um veículo. | Estudo de caso |
| **RF08** | Registrar multa | O sistema deve permitir registrar multas de trânsito, vinculando-as a um veículo e ao motorista responsável na data da infração. | Estudo de caso |
| **RF09** | Consultar histórico | O sistema deve permitir consultar o histórico de percursos, manutenções, abastecimentos e multas, filtrado por veículo, motorista ou período. | Estudo de caso |
| **RF10** | Gerar relatórios | O sistema deve gerar relatórios consolidados de custos e uso da frota. | Estudo de caso |
| **RF11** | Gerenciar usuários | O sistema deve permitir cadastrar, listar, visualizar, editar e ativar/desativar usuários, definindo o perfil de acesso de cada um. A função é restrita aos perfis autorizados (RN09). | Código |
| **RF12** | Gerenciar a própria conta | O usuário logado deve poder alterar seu nome, e-mail e senha (informando a senha atual) e excluir a própria conta. | Código |
| **RF13** | Exibir painel e alertas | O sistema deve exibir um painel com indicadores da frota (veículos, motoristas, veículos em uso e em manutenção, percursos, abastecimentos e manutenções do mês) e alertas: próximas manutenções, multas pendentes e CNHs vencendo em até 60 dias. | Código |

---

## 2. Requisitos não funcionais

Características e restrições do sistema, verificadas no código e na configuração do projeto.

| Código | Categoria | Requisito |
|---|---|---|
| **RNF01** | Usabilidade | A interface deve ser web, em português do Brasil, simples e responsiva (Bootstrap 5.3.3), com menu lateral que se converte em menu retrátil em telas pequenas. |
| **RNF02** | Segurança | Senhas são armazenadas apenas como hash (`password_hash`) e conferidas com `password_verify`. Consultas SQL usam *prepared statements* (PDO). O ID da sessão é regenerado no login. As páginas internas exigem sessão ativa e usuário ativo. Dados exibidos são escapados com `htmlspecialchars`. |
| **RNF03** | Compatibilidade | O acesso é feito por navegador, sem aplicativo dedicado (ver escopo). A execução exige PHP 8.3 e MySQL 8.0. |
| **RNF04** | Integridade dos dados | O banco deve garantir integridade por chaves estrangeiras, restrições `UNIQUE` e `NOT NULL`. A validação dos dados também deve ocorrer no servidor, e não apenas na interface. |
| **RNF05** | Implantação | O sistema deve executar em contêineres Docker (aplicação com Nginx e PHP-FPM, e banco MySQL), configurado por variáveis de ambiente (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`). |
| **RNF06** | Fuso horário | Aplicação e sessão do MySQL devem operar em `America/Sao_Paulo` (UTC-3). |

> **Ainda não levantados:** requisitos de desempenho (tempo de resposta, volume esperado de registros) e de backup. Proteção contra CSRF e validação centralizada estão previstas na estrutura (`includes/csrf.php` e `includes/validation.php`), mas esses arquivos ainda estão vazios.

---

## 3. Regras de negócio

| Código | Regra | Onde é garantida | Origem |
|---|---|---|---|
| **RN01** | Um veículo não pode ter duas reservas confirmadas com datas/horários sobrepostos. | Aplicação (o banco não impede) | Estudo de caso |
| **RN02** | Um percurso somente pode ser registrado para um veículo com status `disponivel` (veículo em manutenção, em uso ou inativo não pode ser usado). | Aplicação | Estudo de caso |
| **RN03** | Toda multa deve estar vinculada a um veículo e, sempre que possível, ao motorista que conduzia na data da infração. | Banco (`id_vei` obrigatório; `id_mot` opcional) | Estudo de caso |
| **RN04** | A exclusão de motorista, cliente ou veículo com histórico associado (percursos, reservas, manutenções, abastecimentos ou multas) deve respeitar a integridade referencial, por restrição ou por inativação lógica (`ativo`), evitando a perda do histórico. | Banco (FK sem `ON DELETE`, equivalente a `RESTRICT`) + campo `ativo` | Estudo de caso |
| **RN05** | Usuário do sistema (login) é diferente de motorista: nem todo motorista acessa o sistema e nem todo usuário é motorista. Nesta versão não há vínculo entre as duas tabelas. | Banco (tabelas independentes) | Estudo de caso |
| **RN06** | Cada veículo pertence a um departamento responsável, usado no rateio de custos e nos relatórios. | Banco (`veiculo.id_dep` obrigatório) | Estudo de caso |
| **RN07** | O sistema deve alertar quando a CNH de um motorista estiver vencida ou vencer em até 60 dias, e deve alertar antes de registrar novo percurso desse motorista. | Aplicação (o alerta do painel existe; o alerta no registro de percurso está pendente) | Dicionário anterior |
| **RN08** | Uma reserva deve ter ao menos um solicitante: cliente (`id_cli`) ou departamento (`id_dep`). | Aplicação (o banco não exige) | Estudo de caso (RF03) |
| **RN09** | Somente usuários com perfil `admin`, `gerente` ou `rh` podem cadastrar e gerenciar outros usuários. | Aplicação (`includes/auth.php`) | Código |
| **RN10** | O e-mail do usuário é único, e somente usuários ativos conseguem fazer login. | Banco (`UNIQUE` em `email`) + aplicação | Código |
| **RN11** | Na tela de edição, o usuário não pode alterar o próprio perfil nem desativar a própria conta. | Aplicação | Código |

---

## 4. Telas previstas

Levantamento original de telas do sistema:

- Login
- Home / menu
- Painel do administrador
- Cadastros: usuário, motorista, cliente, veículo, manutenção, abastecimento e multa
- Registro de percurso (uso interno)
- Reserva (uso por cliente ou departamento)
- Histórico de percursos e histórico de reservas
- Relatórios (frota, custos e reservas)

---

## 5. Matriz de rastreabilidade

O guia do projeto exige coerência entre **requisito → modelo → banco → aplicação**. A matriz mostra, para cada requisito funcional, as tabelas do banco que o sustentam e a situação da interface.

**Legenda da situação:** **Com código** = o arquivo existe e tem implementação · **Esqueleto** = os arquivos foram criados, mas ainda estão vazios · **Sem tela** = não há arquivo nem item de menu.

| Requisito | Tabelas | Telas / arquivos | Situação |
|---|---|---|---|
| RF01 | `usuario` | `login.php`, `logout.php`, `includes/auth.php` | Com código |
| RF02 | `motorista`, `veiculo`, `cliente`, `departamento` | `dashboard/motoristas/`, `dashboard/veiculos/` | Esqueleto (motoristas e veículos); **Sem tela** (clientes e departamentos) |
| RF03, RF04 | `reserva` | n/d | **Sem tela** |
| RF05 | `percurso` | `dashboard/viagens/` | Esqueleto |
| RF06 | `manutencao` | `dashboard/manutencao/` | Esqueleto |
| RF07 | `abastecimento` | `dashboard/abastecimentos/` | Esqueleto |
| RF08 | `multa` | n/d | **Sem tela** |
| RF09 | `percurso`, `manutencao`, `abastecimento`, `multa` | listagens dos módulos acima | Esqueleto |
| RF10 | todas | `dashboard/relatorios/` (consumo, custos, manutenções, viagens) | Esqueleto |
| RF11 | `usuario` | `dashboard/usuarios/` | Com código |
| RF12 | `usuario`, `sessao` | `usuario/index.php`, `config-perfil.php`, `deletar-conta.php` | Com código |
| RF13 | `veiculo`, `motorista`, `percurso`, `abastecimento`, `manutencao`, `multa` | `dashboard/index.php` | Com código |

> **Atenção:** o menu da interface (`includes/header.php`) inclui módulos sem tabela no banco (pneus, despesas, fornecedores, documentos, sinistros) e não inclui reservas, clientes, multas nem departamentos, que existem no banco. Veja o item P3 em [07-pendencias-e-evolucao.md](07-pendencias-e-evolucao.md).
