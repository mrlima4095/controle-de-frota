CREATE DATABASE IF NOT EXISTS controle_de_frota;
USE controle_de_frota;

CREATE TABLE departamento (
    id_departamento INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    responsavel VARCHAR(100),
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE usuario (
    id_usr INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    cpf VARCHAR(14) UNIQUE,
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('admin', 'gerente', 'rh', 'operador', 'visualizador') DEFAULT 'operador',
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_consessao TIMESTAMP NULL
);

CREATE TABLE motorista (
    id_mot INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL,
    cpf VARCHAR(14) UNIQUE NOT NULL,
    cnh VARCHAR(20),
    validade_cnh DATE,
    telefone VARCHAR(20),
    email VARCHAR(100),
    uf CHAR(2),
    cidade VARCHAR(100),
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE veiculo (
    id_vei INT PRIMARY KEY AUTO_INCREMENT,
    placa VARCHAR(8) UNIQUE NOT NULL,
    marca VARCHAR(50),
    modelo VARCHAR(50),
    ano_fabricacao INT,
    ano_modelo INT,
    cor VARCHAR(30),
    tipo ENUM('carro', 'moto', 'caminhao', 'van', 'onibus') DEFAULT 'carro',
    chassi VARCHAR(17),
    combustivel ENUM('flex', 'gasolina', 'diesel', 'eletrico') DEFAULT 'flex',
    capacidade INT,
    quilometragem_inicial INT DEFAULT 0,
    quilometragemAtual INT DEFAULT 0,
    status ENUM('disponivel', 'em_uso', 'manutencao', 'inativo') DEFAULT 'disponivel',
    id_dep INT NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_dep) REFERENCES departamento(id_departamento)
);

CREATE TABLE percurso (
    id_perc INT PRIMARY KEY AUTO_INCREMENT,
    id_vei INT NOT NULL,
    id_mot INT NOT NULL,
    data_per DATE NOT NULL,
    hora_ini TIME NOT NULL,
    hora_fim TIME NOT NULL,
    origem VARCHAR(200) NOT NULL,
    destino VARCHAR(200) NOT NULL,
    quilometragem INT NOT NULL,
    observacoes TEXT,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_vei) REFERENCES veiculo(id_vei),
    FOREIGN KEY (id_mot) REFERENCES motorista(id_mot)
);

CREATE TABLE manutencao (
    id_man INT PRIMARY KEY AUTO_INCREMENT,
    id_vei INT NOT NULL,
    tipo ENUM('preventiva', 'corretiva', 'revisao') NOT NULL,
    data_man DATE NOT NULL,
    oficina VARCHAR(200),
    servico TEXT,
    custo DECIMAL(10,2) DEFAULT 0,
    quilometragem INT,
    observacoes TEXT,
    proxima_data DATE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_vei) REFERENCES veiculo(id_vei)
);

CREATE TABLE abastecimento (
    id_aba INT PRIMARY KEY AUTO_INCREMENT,
    id_vei INT NOT NULL,
    data_aba DATE NOT NULL,
    hora_aba TIME NOT NULL,
    posto VARCHAR(200),
    litros DECIMAL(8,2) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    quilometragem INT NOT NULL,
    tipo_combustivel ENUM('flex', 'gasolina', 'diesel', 'eletrico'),
    observacoes TEXT,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_vei) REFERENCES veiculo(id_vei)
);

CREATE TABLE multa (
    id_mul INT PRIMARY KEY AUTO_INCREMENT,
    id_vei INT NOT NULL,
    id_mot INT,
    data_infra DATE NOT NULL,
    hora_infra TIME,
    local VARCHAR(200),
    infracao VARCHAR(200) NOT NULL,
    valor_multa DECIMAL(10,2) NOT NULL,
    pontos INT DEFAULT 0,
    status ENUM('pendente', 'paga', 'cancelada') DEFAULT 'pendente',
    data_pagamento DATE,
    observacoes TEXT,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_vei) REFERENCES veiculo(id_vei),
    FOREIGN KEY (id_mot) REFERENCES motorista(id_mot)
);

CREATE TABLE sessao (
    id_sess INT PRIMARY KEY AUTO_INCREMENT,
    id_usr INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    data_ini TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_fim TIMESTAMP NULL,
    ip VARCHAR(45),
    navigator VARCHAR(255),
    ativo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (id_usr) REFERENCES usuario(id_usr)
);

CREATE TABLE pneu (
    id_pneu INT PRIMARY KEY AUTO_INCREMENT,
    id_vei INT NOT NULL,
    posicao ENUM('dianteiro_esquerdo', 'dianteiro_direito', 'traseiro_esquerdo', 'traseiro_direito', 'estepe', 'outro') DEFAULT 'outro',
    marca VARCHAR(50),
    modelo VARCHAR(50),
    dimensao VARCHAR(30),
    numero_serie VARCHAR(50),
    data_instalacao DATE,
    km_instalacao INT DEFAULT 0,
    km_atual INT DEFAULT 0,
    vida_util_km INT,
    status ENUM('em_uso', 'reserva', 'desgastado', 'descartado') DEFAULT 'em_uso',
    observacoes TEXT,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_vei) REFERENCES veiculo(id_vei)
);

CREATE TABLE fornecedor (
    id_forn INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL,
    cnpj_cpf VARCHAR(18),
    telefone VARCHAR(20),
    email VARCHAR(100),
    categoria VARCHAR(100),
    uf CHAR(2),
    cidade VARCHAR(100),
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE despesa (
    id_des INT PRIMARY KEY AUTO_INCREMENT,
    descricao VARCHAR(200) NOT NULL,
    categoria ENUM('combustivel', 'manutencao', 'pneu', 'multa', 'seguro', 'pedagio', 'salario', 'outro') DEFAULT 'outro',
    valor DECIMAL(10,2) NOT NULL,
    data_despesa DATE NOT NULL,
    id_vei INT,
    id_forn INT,
    observacoes TEXT,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_vei) REFERENCES veiculo(id_vei),
    FOREIGN KEY (id_forn) REFERENCES fornecedor(id_forn)
);

CREATE TABLE documento (
    id_doc INT PRIMARY KEY AUTO_INCREMENT,
    id_vei INT NOT NULL,
    tipo ENUM('crlv', 'licenciamento', 'seguro', 'ipva', 'vistoria', 'outro') DEFAULT 'outro',
    numero VARCHAR(100),
    descricao VARCHAR(200),
    data_emissao DATE,
    data_validade DATE,
    arquivo VARCHAR(255),
    observacoes TEXT,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_vei) REFERENCES veiculo(id_vei)
);
