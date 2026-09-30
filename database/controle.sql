CREATE DATABASE controle_de_frota;
USE controle_de_frota;

CREATE TABLE usuarios(
	id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    data_nascimento DATE NOT NULL,
  	tipo INT NOT NULL
    -- 1 - Motorista
    -- 2 - 
    -- 3 - 
    -- 4 - Gerente 
    -- 5 - Admin Geral
);

CREATE TABLE empresas(
	id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	nome VARCHAR(255) NOT NULL,
	cnpj VARCHAR(14) NOT NULL,
	email VARCHAR(255) NOT NULL,
	quantidade_veiculos INT NOT NULL,
);


CREATE TABLE veiculos(
	placa VARCHAR(10) NOT NULL PRIMARY KEY,
    cor VARCHAR(10) NOT NULL,
	empresa INT NOT NULL
);


CREATE TABLE abastecimentos(
	id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    placa_veiculo VARCHAR(10),
    data DATE NOT NULL,
	quantidade DECIMAL NOT NULL
);
CREATE TABLE documentos(
	id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	nome VARCHAR(255),
	tipo INT NOT NULL,
	dono INT NOT NULL,
);

CREATE TABLE fornecedores(
	id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	cpnj VARCHAR(14) NOT NULL
);


CREATE TABLE multas(
	id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    placa_veiculo VARCHAR(10),
    motorista_id INT,
    valor DECIMAL NOT NULL,
    data DATE NOT NULL,
    pago BOOL NOT NULL,
);

CREATE TABLE manutencoes(
	id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    placa_veiculo NOT NULL,
    tipo VAo VARCHAR(10),
    data DATE RCHAR(16),
    sobre TEXT
);



CREATE TABLE viagem(
	id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    placa_veiculo VARCHAR(10),
    motorista_id INT,
	tempo_inicio TIMESTAMP NOT NULL,
    tempo_fim TIMESTAMP NOT NULL,
    local_inicio VARCHAR(50) NOT NULL,
    local_fim VARCAHR(50) NOT NULL
);

CREATE TABLE sinistro(
	id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	assunto VARCHAR(100) NOT NULL,
	descricao TEXT NOT NULL,
	arquivos TEXT,
	tipo INT NOT NULL,
	-- 1 - roubo
	bo BOOLEAN NOT NULL
);


CREATE TABLE logs(
	id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    usuario INT,
    acao VARCHAR(10) NOT NULL,
    sobre TEXT
);
