CREATE DATABASE controle_de_frota;
USE controle_de_frota;

CREATE TABLE IF NOT EXISTS usuarios(
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

CREATE TABLE IF NOT EXISTS empresas(
	id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	nome VARCHAR(255) NOT NULL,
	cnpj VARCHAR(14) NOT NULL,
	email VARCHAR(255) NOT NULL,
	quantidade_veiculos INT NOT NULL,
);


CREATE TABLE IF NOT EXISTS veiculos(
	placa VARCHAR(10) NOT NULL PRIMARY KEY,
    cor VARCHAR(10) NOT NULL,
	empresa INT NOT NULL
);


CREATE TABLE IF NOT EXISTS abastecimentos(
	id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    placa_veiculo VARCHAR(10),
    data DATE NOT NULL,
	quantidade DECIMAL NOT NULL
);
CREATE TABLE IF NOT EXISTS documentos(
	id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	nome VARCHAR(255),
	tipo INT NOT NULL,
	dono INT NOT NULL,
);

CREATE TABLE IF NOT EXISTS fornecedores(
	id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	cpnj VARCHAR(14) NOT NULL
);


CREATE TABLE IF NOT EXISTS multas(
	id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    placa_veiculo VARCHAR(10),
    motorista_id INT,
    valor DECIMAL NOT NULL,
    data DATE NOT NULL,
    pago BOOL NOT NULL,
);

CREATE TABLE IF NOT EXISTS manutencoes(
	id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    placa_veiculo NOT NULL,
    tipo VAo VARCHAR(10),
    data DATE RCHAR(16),
    sobre TEXT
);



CREATE TABLE IF NOT EXISTS viagem(
	id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    placa_veiculo VARCHAR(10),
    motorista_id INT,
	tempo_inicio TIMESTAMP NOT NULL,
    tempo_fim TIMESTAMP NOT NULL,
    local_inicio VARCHAR(50) NOT NULL,
    local_fim VARCAHR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS sinistro(
	id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	assunto VARCHAR(100) NOT NULL,
	descricao TEXT NOT NULL,
	arquivos TEXT,
	tipo INT NOT NULL,
	-- 1 - roubo
	bo BOOLEAN NOT NULL
);


CREATE TABLE IF NOT EXISTS logs(
	id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    usuario INT,
    acao VARCHAR(10) NOT NULL,
    sobre TEXT
);

CREATE DATABASE control;
USE control;

CREATE TABLE usuario  (
	CPF CHAR(11) PRIMARY KEY NOT NULL,
	Nome VARCHAR(100) NOT NULL,
	Email VARCHAR(100) UNIQUE NOT NULL,
	Senha VARCHAR(255) NOT NULL,
	Funcao VARCHAR(50) NOT NULL,
	Telefone VARCHAR(15) NOT NULL,
	Salario DECIMAL(10,2) NOT NULL
	Nivel_Permissao ENUM('ADMIN', 'GERENTE', 'USUARIO', 'MOTORISTA', 'RH') NOT NULL, -- Padronizado em maiúsculas
);

CREATE TABLE Carga (
	Id_Carga INT AUTO_INCREMENT PRIMARY KEY,
	Descricao VARCHAR(255) NOT NULL,
	Peso DECIMAL(10,2) NOT NULL, -- Peso é importante ser obrigatório
	Origem VARCHAR(100) NOT NULL, -- Obrigatório para logística
	Destino VARCHAR(100) NOT NULL,
	Data_Envio DATE NOT NULL
);

CREATE TABLE Veiculos (
	Id_Veiculo INT AUTO_INCREMENT PRIMARY KEY,
	Placa VARCHAR(10) UNIQUE NOT NULL,
	Modelo VARCHAR(100) NOT NULL,
	Marca VARCHAR(100) NOT NULL, -- Evita veículos sem marca definida
	Ano INT NOT NULL,
	Capacidade_Carga DECIMAL(10,2) NOT NULL
);

CREATE TABLE Banco_de_horas (
	Id_Banco INT AUTO_INCREMENT PRIMARY KEY,
	CPF_Funcionario CHAR(11) NOT NULL,
	Horas DECIMAL(5,2) NOT NULL,
	Data_Registro DATE NOT NULL,
	FOREIGN KEY (CPF_Funcionario) REFERENCES Funcionario(CPF)
);

CREATE TABLE Abastecimentos (
	Id_Abastecimento INT AUTO_INCREMENT PRIMARY KEY,
	Id_Veiculo INT NOT NULL,
	Data_Abastecimento DATETIME NOT NULL,
	Litros DECIMAL(10,2) NOT NULL,
	Valor_Total DECIMAL(10,2) NOT NULL,
	Posto VARCHAR(100) NOT NULL,
	FOREIGN KEY (Id_Veiculo) REFERENCES Veiculos(Id_Veiculo)
);

CREATE TABLE Manutencoes (
	Id_Manutencao INT AUTO_INCREMENT PRIMARY KEY,
	Id_Veiculo INT NOT NULL,
	Tipo VARCHAR(100) NOT NULL,
	Descricao TEXT,
	Data_Manutencao DATE NOT NULL,
	Custo DECIMAL(10,2) NOT NULL,
	FOREIGN KEY (Id_Veiculo) REFERENCES Veiculos(Id_Veiculo)
);

CREATE TABLE Viagens (
	Id_Viagem INT AUTO_INCREMENT PRIMARY KEY,
	Id_Veiculo INT NOT NULL,
	CPF_Funcionario CHAR(11) NOT NULL,
	Id_Carga INT NOT NULL, -- Se é uma viagem de controle de carga, o ID deve ser obrigatório
	Data_Saida DATETIME NOT NULL,
	Data_Chegada DATETIME, -- Pode ser NULL até que a viagem seja concluída
	Origem VARCHAR(100) NOT NULL,
	Destino VARCHAR(100) NOT NULL,
	FOREIGN KEY (Id_Veiculo) REFERENCES Veiculos(Id_Veiculo),
	FOREIGN KEY (CPF_Funcionario) REFERENCES Funcionario(CPF),
	FOREIGN KEY (Id_Carga) REFERENCES Carga(Id_Carga)
);

CREATE TABLE Multas (
	Id_Multa INT AUTO_INCREMENT PRIMARY KEY,
	Id_Veiculo INT NOT NULL,
	CPF_Funcionario CHAR(11) NOT NULL,
	Data_Multa DATE NOT NULL,
	Valor DECIMAL(10,2) NOT NULL,
	Motivo VARCHAR(255) NOT NULL,
	FOREIGN KEY (Id_Veiculo) REFERENCES Veiculos(Id_Veiculo),
	FOREIGN KEY (CPF_Funcionario) REFERENCES Funcionario(CPF)
);

