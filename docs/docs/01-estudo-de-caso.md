# 01. Estudo de Caso

**Sistema de Controle de Frota**

| | |
|---|---|
| **Instituição** | Etec Jardim Ângela |
| **Curso** | Desenvolvimento de Sistemas |
| **Componentes** | Banco de Dados II · Programação para Internet · Análise e Projeto de Dados |
| **Equipe** | Felipe Souza Pereira de Lima · Pedro Henrique Mesquita Nogueira |

> Este documento cobre a **Fase 1 (Planejamento)**: problema, objetivos, escopo e atores. Os requisitos estão em [02-requisitos.md](02-requisitos.md) e a modelagem de dados em [03-modelo-conceitual.md](03-modelo-conceitual.md).

---

## 1. Contexto e cenário

Empresas que possuem uma frota própria de veículos (transportadoras, prestadoras de serviço, órgãos públicos ou empresas com equipes externas) precisam gerenciar, ao mesmo tempo, **pessoas** (motoristas), **ativos** (veículos) e **processos** (reservas, percursos, manutenções, abastecimentos e ocorrências como multas). Na maioria dos casos, esse controle ainda é feito de forma manual, com planilhas, cadernos de anotações ou comunicação informal entre setores, o que gera informações dispersas, retrabalho e dificuldade de auditoria.

O cenário adotado como base é o de uma empresa de médio porte, organizada em departamentos (por exemplo, Comercial, Manutenção e Logística), que disponibiliza veículos para uso interno de colaboradores e para atendimento a clientes externos. Cada veículo é conduzido por motoristas cadastrados, que realizam percursos, os quais podem ou não estar vinculados a reservas previamente solicitadas. Os veículos precisam de manutenções periódicas e abastecimentos regulares, e eventualmente recebem multas de trânsito que precisam ser controladas e quitadas.

## 2. Problema identificado

Sem um sistema informatizado, a empresa enfrenta os seguintes problemas recorrentes:

- Falta de visibilidade sobre quais veículos estão disponíveis, em uso ou em manutenção em determinado momento.
- Dificuldade em saber qual motorista utilizou determinado veículo em uma data específica, o que compromete a apuração de responsabilidade em caso de multa ou avaria.
- Ausência de histórico centralizado de manutenções, o que leva a manutenções atrasadas ou repetidas.
- Controle manual de abastecimentos, dificultando o cálculo de consumo médio e a identificação de gastos fora do padrão.
- Reservas feitas informalmente (telefone, mensagens), gerando conflitos de agenda e veículos reservados em duplicidade.
- Multas de trânsito registradas separadamente do restante do processo, sem vínculo claro com o motorista responsável na data da infração.

## 3. Solução proposta

O sistema de controle de frota centraliza essas informações em **um único banco de dados relacional**, permitindo cadastrar motoristas, clientes, veículos e departamentos, controlar reservas e percursos, registrar manutenções e abastecimentos vinculados a cada veículo, associar multas ao veículo e ao motorista responsável e gerar relatórios gerenciais de custo e uso da frota. O resultado esperado é mais controle, rastreabilidade e agilidade para a rotina operacional.

A interface web do sistema é apresentada com o nome **DriveHub**.

## 4. Objetivos

**Objetivo geral:** centralizar, em um único banco de dados relacional, todas as informações operacionais da frota, permitindo consultas rápidas e confiáveis.

**Objetivos específicos:**

- Cadastrar e manter atualizados os dados de usuários do sistema, motoristas, clientes, veículos e departamentos.
- Registrar reservas de veículos vinculadas a clientes ou departamentos, evitando conflitos de agenda.
- Registrar percursos realizados, associando motorista, veículo, origem, destino e quilometragem.
- Manter o histórico de manutenções preventivas e corretivas de cada veículo.
- Controlar abastecimentos, permitindo o cálculo de consumo (km/l) por veículo.
- Registrar multas de trânsito, associando-as ao veículo e ao motorista responsável no momento da infração.
- Gerar relatórios gerenciais consolidados (custos, quilometragem, manutenções e multas por período).

## 5. Escopo

### 5.1 Dentro do escopo

- Cadastro (CRUD) de usuários, motoristas, clientes, veículos e departamentos.
- Controle de **reservas** (vinculadas a veículo e a cliente ou departamento) e de **percursos** (vinculados a veículo e motorista).
- Controle de manutenções e abastecimentos por veículo.
- Registro de multas vinculadas a veículo e motorista.
- Autenticação de usuários (login/logout) com controle de sessão.
- Relatórios gerenciais básicos (consolidação por veículo, motorista e período).

### 5.2 Fora do escopo

- Integração com sistemas externos de rastreamento veicular (GPS em tempo real).
- Emissão de notas fiscais ou integração financeira/contábil completa.
- Aplicativo móvel dedicado para motoristas (o acesso é apenas via navegador).

## 6. Atores do sistema

| Ator | Descrição | Principais ações |
|---|---|---|
| **Usuário do sistema** (administrador, operador ou visualizador) | Colaborador com login que opera o sistema (setor administrativo, RH ou logística). | Cadastra motoristas, veículos, clientes, reservas, manutenções, abastecimentos e multas; consulta relatórios. O perfil define o que cada usuário pode fazer. |
| **Motorista** | Pessoa cadastrada como condutora dos veículos da frota. Nesta versão não possui login. | É associado a percursos e multas registrados por um usuário do sistema. |
| **Cliente** | Pessoa física ou jurídica atendida pela empresa, que pode solicitar reservas de veículo. | É associado a reservas de veículos. |

## 7. Exemplo de aplicação

A transportadora fictícia **Rota Certa Logística**, com quinze veículos e vinte motoristas distribuídos entre os departamentos Comercial e Logística, controla hoje sua frota por planilhas compartilhadas e anotações em papel:

- as reservas são combinadas por telefone entre os setores, o que já causou casos de dois motoristas reservando o mesmo veículo no mesmo horário;
- o histórico de manutenções fica registrado apenas nos canhotos de nota fiscal guardados em uma pasta física, dificultando saber quando foi a última revisão de cada carro;
- as multas chegam por e-mail e são anotadas manualmente em uma planilha separada, sem vínculo direto com o motorista que estava dirigindo na infração.

Diante do crescimento da frota, a diretoria decidiu substituir as planilhas por um sistema informatizado. Com a nova solução, o setor de logística registra as reservas diretamente no sistema, evitando conflitos de agenda; cada manutenção e abastecimento é lançado no histórico do veículo, permitindo calcular custo médio e consumo por carro; e cada multa é cadastrada com vínculo ao veículo e ao motorista responsável, agilizando a cobrança e o desconto em folha quando aplicável.

## 8. Benefícios esperados

- Centralização das informações da frota em um único banco de dados, eliminando planilhas paralelas.
- Redução de conflitos de agenda por meio do controle de reservas.
- Maior controle sobre custos de manutenção, combustível e multas, com dados históricos por veículo e por motorista.
- Rastreabilidade de responsabilidade sobre o uso de cada veículo, apoiada pelo vínculo entre percursos, motoristas e multas.
- Base de dados estruturada para relatórios gerenciais confiáveis, apoiando decisões como renovação da frota ou renegociação de contratos com oficinas e postos.
