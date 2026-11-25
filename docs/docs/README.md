# Documentação — Controle de Frota

Documentação técnica do projeto, organizada na sequência adotada na disciplina:

> **PROBLEMA → REQUISITOS → MODELO → BANCO → INTERFACE → PROGRAMAÇÃO → TESTES**

## Documentos

| Doc | Conteúdo | Fase |
|---|---|---|
| [01. Estudo de caso](01-estudo-de-caso.md) | Contexto, problema, objetivos, escopo, atores e exemplo de aplicação | Planejamento |
| [02. Requisitos](02-requisitos.md) | Requisitos funcionais, não funcionais, regras de negócio e matriz de rastreabilidade | Planejamento |
| [03. Modelo conceitual](03-modelo-conceitual.md) | MER: entidades, atributos, relacionamentos, cardinalidades e justificativas | Modelagem |
| [04. Modelo lógico](04-modelo-logico.md) | DER, restrições de integridade e normalização até a 3FN | Modelagem |
| [05. Dicionário de dados](05-dicionario-de-dados.md) | Todas as tabelas e colunas do banco | Modelagem |
| [06. Estrutura do projeto](06-estrutura-do-projeto.md) | Arquitetura, pastas e situação da implementação | Interface |
| [07. Pendências e evolução](07-pendencias-e-evolucao.md) | Divergências encontradas e melhorias sugeridas | Todas |

O **modelo físico** é o script [`../database/controle.sql`](../database/controle.sql).

## Outros arquivos

```text
docs/
├── modelagem/
│   ├── diagramas/      PNG e código-fonte Mermaid (.mmd) do MER e do DER
│   └── brmodelo/       Modelos do BrModelo (.brM), versões anteriores ao banco atual
└── referencias/        Material da disciplina (enunciado e exemplo de trabalho)
```

## Fonte da verdade

O script `database/controle.sql` é a referência do banco, e os documentos 03, 04 e 05 descrevem exatamente o que ele cria. Ao alterar o banco:

1. Atualize o `controle.sql`.
2. Atualize o [dicionário de dados](05-dicionario-de-dados.md) e, se houver mudança de relacionamento, os documentos 03 e 04.
3. Atualize os diagramas Mermaid dos documentos 03 e 04 e regenere as imagens (comando abaixo).
4. Se mudar uma regra de negócio, revise o [02](02-requisitos.md) (inclusive a matriz de rastreabilidade).

### Regenerar os diagramas

Os diagramas nos documentos `.md` são renderizados pelo próprio GitHub. Para exportar as imagens PNG (úteis para a entrega em Word e na apresentação), use o [Mermaid CLI](https://github.com/mermaid-js/mermaid-cli) na pasta `docs/modelagem/diagramas/`:

```bash
npx -p @mermaid-js/mermaid-cli mmdc -i modelo-conceitual.mmd -o modelo-conceitual.png -b white -s 2 -w 1800
npx -p @mermaid-js/mermaid-cli mmdc -i modelo-logico.mmd     -o modelo-logico.png     -b white -s 2 -w 1800
```

## Convenções

- Arquivos em minúsculas, separados por hífen, sem acentos nem espaços; documentos numerados na ordem de leitura.
- Documentação em Markdown, para facilitar a revisão por *diff* no Git.
- Códigos de rastreio usados nos textos: **RF** (requisito funcional), **RNF** (não funcional), **RN** (regra de negócio) e **P** (pendência).
