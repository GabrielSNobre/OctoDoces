<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

require 'conexao.php';

// Obter informações do usuário
$usuario_id = $_SESSION['usuario']['id'];
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();

// Processar compra se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comprar'])) {
    $produto_id = $_POST['produto_id'];
    $quantidade = $_POST['quantidade'];
    $endereco = $_POST['endereco'];
    
    try {
        // Inserir pedido no banco de dados
        $stmt = $pdo->prepare("INSERT INTO pedidos (usuario_id, produto_id, quantidade, endereco, status) 
                              VALUES (?, ?, ?, ?, 'pendente')");
        $stmt->execute([$usuario_id, $produto_id, $quantidade, $endereco]);
        
        $mensagem = "Pedido realizado com sucesso!";
    } catch (PDOException $e) {
        $erro = "Erro ao processar pedido: " . $e->getMessage();
    }
}

// Obter lista de produtos disponíveis
$produtos = $pdo->query("SELECT * FROM produtos")->fetchAll();

// Obter histórico de pedidos do usuário
$pedidos = $pdo->prepare("SELECT p.*, pr.nome as produto_nome, pr.preco 
                         FROM pedidos p 
                         JOIN produtos pr ON p.produto_id = pr.id 
                         WHERE p.usuario_id = ? 
                         ORDER BY p.data_pedido DESC");
$pedidos->execute([$usuario_id]);
$pedidos = $pedidos->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - Octo Doces</title>
    <style>
        :root {
            --primary: #FF6B6B;
            --secondary: #4ECDC4;
            --accent: #FFE66D;
            --dark: #292F36;
            --light: #F7FFF7;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.6;
        }

        
        
        .profile-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .profile-title {
            font-size: 2rem;
            color: var(--secondary);
        }
        
        .btn-logout {
            background-color: var(--primary);
            color: red;
            padding: 8px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-logout:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .profile-info {
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .info-item {
            margin-bottom: 15px;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark);
        }
        
        .info-value {
            margin-top: 5px;
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 5px;
        }
        
        .section-title {
            font-size: 1.5rem;
            color: var(--secundary);
            margin: 30px 0 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid var(--accent);
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .product-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .product-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        
        .product-details {
            padding: 15px;
        }
        
        .product-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .product-price {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .product-form input, .product-form select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn-buy {
            background-color: var(--secondary);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-weight: 600;
        }
        
        .btn-buy:hover {
            background-color: #3dbeb6;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .orders-table th, .orders-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .orders-table th {
            background-color: var(--primary);
            color: white;
        }
        
        .orders-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .status-pendente {
            color: #e67e22;
            font-weight: 600;
        }
        
        .status-enviado {
            color: #3498db;
            font-weight: 600;
        }
        
        .status-entregue {
            color: #2ecc71;
            font-weight: 600;
        }
        
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        h3 {
            color: #000000;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/stylepage.css">
</head>
<body>

<header>
        <div class="container header-content    "> 
            <div class="container-fluid col-11 m-auto"> 
                <a href="index.html"> <img src="../img/Logo.png" style="height: 100px; width: 100px;"> </a>
            
            </div>
            <nav>
                <ul>
                    <li><a href="../index.html">Início</a></li>
                    <li><a href="produtos.php">Produtos</a></li>
                    <li><a href="../index.html#about">Sobre</a></li>
                    <li><a href="../index.hmtl#contact">Contato</a></li>
                    <li><a href="carrinho.php">Carrinho</a></li>
                                        <li><a href="logout.php" class="btn-logout">Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <div class="profile-container">
        
        
        <?php if (isset($mensagem)): ?>
            <div class="message success"><?php echo $mensagem; ?></div>
        <?php endif; ?>
        
        <?php if (isset($erro)): ?>
            <div class="message error"><?php echo $erro; ?></div>
        <?php endif; ?>
        
        <div class="profile-info">
            <div>
                <div class="info-item">
                    <div class="info-label">Nome Completo</div>
                    <div class="info-value"><?php echo htmlspecialchars($usuario['nome']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">E-mail</div>
                    <div class="info-value"><?php echo htmlspecialchars($usuario['email']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Data de Cadastro</div>
                    <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($usuario['data_cadastro'])); ?></div>
                </div>
            </div>
            
            <div>
                <b><h3 class="section-title">Seu Endereço</h3></b>
                <form method="POST" action="Endereco_Usuario.php">
                    <textarea name="endereco" rows="4" style="width: 100%; padding: 8px;" 
                              placeholder="Digite seu endereço completo para entrega"><?php echo htmlspecialchars($usuario['endereco'] ?? ''); ?></textarea>
                    <button type="submit" class="btn-buy" style="margin-top: 10px;">Salvar Endereço</button>
                </form>
            </div>
        </div>
        
         <h2 class="section-title">Seus Pedidos</h2>
        <?php if (count($pedidos) > 0): ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Valor Unitário</th>
                        <th>Total</th>
                        <th>Data</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $pedido): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pedido['produto_nome']); ?></td>
                            <td><?php echo $pedido['quantidade']; ?></td>
                            <td>R$ <?php echo number_format($pedido['preco'], 2, ',', '.'); ?></td>
                            <td>R$ <?php echo number_format($pedido['preco'] * $pedido['quantidade'], 2, ',', '.'); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?></td>
                            <td class="status-<?php echo $pedido['status']; ?>">
                                <?php 
                                    $status = [
                                        'pendente' => 'Pendente',
                                        'enviado' => 'Enviado',
                                        'entregue' => 'Entregue'
                                    ];
                                    echo $status[$pedido['status']] ?? $pedido['status'];
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Você ainda não fez nenhum pedido.</p>
        <?php endif; ?>
    </div>
    <footer id="contact">
        <div class="container">
            <div class="footer-content">
               
            
            <div class="copyright">
                <p>&copy; 2023 Octo Doces. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>
</body>
</html>