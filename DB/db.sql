CREATE DATABASE IF NOT EXISTS octo_doces;
USE octo_doces;

-- Tabela de tipos de usuário (comum, admin, etc.)
CREATE TABLE tipos_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE,
    descricao VARCHAR(255)
);

-- Inserir tipos básicos de usuário
INSERT INTO tipos_usuario (nome, descricao) VALUES
('cliente', 'Usuário comum que faz compras'),
('administrador', 'Usuário com permissões de administração');

-- Tabela de usuários (modificada)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo_usuario_id INT DEFAULT 1, -- Default para cliente
    cpf VARCHAR(14) UNIQUE,
    telefone VARCHAR(20),
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_login DATETIME,
    ativo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (tipo_usuario_id) REFERENCES tipos_usuario(id)
);

-- Tabela de endereços
CREATE TABLE enderecos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cep VARCHAR(9) NOT NULL,
    logradouro VARCHAR(100) NOT NULL,
    numero VARCHAR(10) NOT NULL,
    complemento VARCHAR(50),
    bairro VARCHAR(50) NOT NULL,
    cidade VARCHAR(50) NOT NULL,
    estado CHAR(2) NOT NULL,
    referencia VARCHAR(100),
    principal BOOLEAN DEFAULT FALSE,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de relacionamento usuário-endereço (many-to-many)
CREATE TABLE usuario_enderecos (
    usuario_id INT NOT NULL,
    endereco_id INT NOT NULL,
    apelido VARCHAR(50), -- Ex: "Casa", "Trabalho"
    PRIMARY KEY (usuario_id, endereco_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (endereco_id) REFERENCES enderecos(id) ON DELETE CASCADE
);

-- Tabela de produtos (modificada)
CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL,
    preco_promocional DECIMAL(10,2),
    imagem VARCHAR(255),
    categoria VARCHAR(50),
    estoque INT DEFAULT 0,
    peso_gramas INT,
    altura_cm INT,
    largura_cm INT,
    comprimento_cm INT,
    ativo BOOLEAN DEFAULT TRUE,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de categorias de produtos
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE,
    descricao VARCHAR(255)
);

-- Atualizar tabela produtos para usar categorias
ALTER TABLE produtos
ADD COLUMN categoria_id INT,
ADD FOREIGN KEY (categoria_id) REFERENCES categorias(id);

-- Tabela de carrinhos (modificada)
CREATE TABLE carrinhos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Tabela de itens do carrinho
CREATE TABLE carrinho_itens (
    carrinho_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL DEFAULT 1,
    preco_unitario DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (carrinho_id, produto_id),
    FOREIGN KEY (carrinho_id) REFERENCES carrinhos(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
);

-- Tabela de pedidos (modificada)
CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    endereco_id INT NOT NULL,
    codigo VARCHAR(20) UNIQUE,
    subtotal DECIMAL(10,2) NOT NULL,
    frete DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    forma_pagamento VARCHAR(50) NOT NULL,
    status VARCHAR(20) DEFAULT 'pendente',
    data_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (endereco_id) REFERENCES enderecos(id)
);

-- Tabela de itens do pedido
CREATE TABLE pedido_itens (
    pedido_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (pedido_id, produto_id),
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
);

-- Tabela de histórico de status do pedido
CREATE TABLE pedido_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    status VARCHAR(20) NOT NULL,
    observacao TEXT,
    data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id)
);

-- Inserir dados iniciais
INSERT INTO categorias (nome, descricao) VALUES
('Doces', 'Doces artesanais e guloseimas'),
('Bolos', 'Bolos caseiros e decorados'),
('Tortas', 'Tortas doces e salgadas'),
('Kits', 'Kits especiais e presentes');

-- Atualizar produtos existentes para usar categorias
UPDATE produtos p
JOIN categorias c ON p.categoria = c.nome
SET p.categoria_id = c.id;

-- Remover coluna categoria antiga após migração
ALTER TABLE produtos DROP COLUMN categoria;

-- Inserir produtos de exemplo (atualizados)
INSERT INTO produtos (nome, descricao, preco, imagem, categoria_id, estoque) VALUES
('Taça Supreme Bronie', 'Brigadeiro feito com chocolate belga e granulados especiais', 30.50, 'https://images.unsplash.com/photo-1563805042-7684c019e1cb', 1, 50),
('Bolo de Chocolate', 'Bolo fofinho com recheio de chocolate meio amargo', 100.00, 'https://images.unsplash.com/photo-1578985545062-69928b1d9587', 2, 20),
('Cupcake Red Velvet', 'Massa aveludada e cream cheese frosting', 12.00, 'https://images.unsplash.com/photo-1559620192-032c4bc4674e', 1, 100),
('Brigadeiro Gourmet', 'Brigadeiro feito com chocolate belga Gourmet', 5.50, 'https://images.unsplash.com/photo-1603532648955-039310d9ed75', 1, 200),
('Pirulitão', 'Pirulito artesanal bom mesmo', 10.00, 'https://images.unsplash.com/photo-1575224300306-1b8da36134ec', 1, 150);

-- Criar usuário admin de exemplo
INSERT INTO usuarios (nome, email, senha, tipo_usuario_id, cpf, telefone) VALUES
('Admin Octo', 'admin@octodoces.com', SHA2('senhaadmin123', 256), 2, '123.456.789-00', '(11) 99999-9999');
use octo_doces;
select * from usuarios;
update usuarios set tipo_usuario_id = 2 where id = 2;