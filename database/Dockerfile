FROM mysql:8.0

# O script é copiado para dentro da imagem.
# O MySQL executa tudo em /docker-entrypoint-initdb.d/ na primeira
# inicialização do volume, criando as tabelas automaticamente.
COPY controle.sql /docker-entrypoint-initdb.d/controle.sql
