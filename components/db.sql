CREATE DATABASE octo_doces;

USE octo_doces;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL,
    imagem VARCHAR(255),
    categoria VARCHAR(50)
);

CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL,
    endereco TEXT NOT NULL,
    data_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'pendente',
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
);

CREATE TABLE carrinhos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (produto_id) REFERENCES produtos(id),
    UNIQUE KEY (usuario_id, produto_id)
);

-- Adicione alguns produtos de exemplo
INSERT INTO produtos (nome, descricao, preco, imagem, categoria) VALUES
('Taça Supreme Bronie', 'Brigadeiro feito com chocolate belga e granulados especiais', 30.50, 'https://images.unsplash.com/photo-1563805042-7684c019e1cb', 'doces'),
('Bolo de Chocolate', 'Bolo fofinho com recheio de chocolate meio amargo', 100.00, 'https://images.unsplash.com/photo-1578985545062-69928b1d9587', 'bolos'),
('Cupcake Red Velvet', 'massa aveludada e cream cheese frosting', 120.00, 'https://images.unsplash.com/photo-1559620192-032c4bc4674e', 'doces'),
('Brigadeiro Goumet', 'Brigadeiro feito com chocolate belga Goumet', 55.00, 'https://images.unsplash.com/photo-1603532648955-039310d9ed75', 'bolos'),
("Pirulitão", "Pirulito artesanal bom mesmo", 10.00, "https://images.unsplash.com/photo-1575224300306-1b8da36134ec?q=80&w=1935&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D", "Doces");

-- Adicione a coluna de endereço na tabela de usuários
ALTER TABLE usuarios ADD COLUMN endereco TEXT;
select * from produtos;

