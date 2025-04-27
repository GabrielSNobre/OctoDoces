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

-- Adicione alguns produtos de exemplo
INSERT INTO produtos (nome, descricao, preco, imagem, categoria) VALUES
('Brigadeiro Gourmet', 'Brigadeiro feito com chocolate belga e granulados especiais', 2.50, 'https://images.unsplash.com/photo-1563805042-7684c019e1cb', 'doces'),
('Bolo de Chocolate', 'Bolo fofinho com recheio de chocolate meio amargo', 45.00, 'https://images.unsplash.com/photo-1578985545062-69928b1d9587', 'bolos'),
('Cupcake Red Velvet', 'Cupcake com massa aveludada e cream cheese frosting', 12.00, 'https://images.unsplash.com/photo-1559620192-032c4bc4674e', 'doces'),
('Torta de Limão', 'Torta com massa crocante e creme de limão siciliano', 35.00, 'https://images.unsplash.com/photo-1603532648955-039310d9ed75', 'bolos');

-- Adicione a coluna de endereço na tabela de usuários
ALTER TABLE usuarios ADD COLUMN endereco TEXT;