<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

require 'conexao.php';

$usuario_id = $_SESSION['usuario']['id'];

// Verificar se o ID do pedido foi passado
if (!isset($_GET['id'])) {
    header("Location: perfil.php");
    exit();
}

$pedido_id = $_GET['id'];

// Obter informações básicas do pedido
$stmt = $pdo->prepare("SELECT p.*, e.* 
                      FROM pedidos p
                      JOIN enderecos e ON p.endereco_id = e.id
                      WHERE p.id = ? AND p.usuario_id = ?");
$stmt->execute([$pedido_id, $usuario_id]);
$pedido = $stmt->fetch();

if (!$pedido) {
    header("Location: perfil.php");
    exit();
}

// Obter itens do pedido
$itens = $pdo->prepare("SELECT pi.*, pr.nome, pr.imagem 
                       FROM pedido_itens pi
                       JOIN produtos pr ON pi.produto_id = pr.id
                       WHERE pi.pedido_id = ?");
$itens->execute([$pedido_id]);
$itens = $itens->fetchAll();

// Obter histórico de status
$historico = $pdo->prepare("SELECT * FROM pedido_status 
                           WHERE pedido_id = ? 
                           ORDER BY data DESC");
$historico->execute([$pedido_id]);
$historico = $historico->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Pedido - Octo Doces</title>
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
        
        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: var(--secondary);
            margin-bottom: 20px;
        }
        
        .order-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 768px) {
            .order-info {
                grid-template-columns: 1fr;
            }
        }
        
        .info-card {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .info-item {
            margin-bottom: 15px;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark);
            display: block;
            margin-bottom: 5px;
        }
        
        .info-value {
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 5px;
            width: 100%;
        }
        
        .section-title {
            font-size: 1.3rem;
            color: var(--secondary);
            margin: 20px 0 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid var(--accent);
        }
        
        .order-items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .order-items th, .order-items td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .order-items th {
            background-color: var(--primary);
            color: white;
        }
        
        .order-items tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .item-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .status-timeline {
            margin-top: 30px;
        }
        
        .timeline-item {
            display: flex;
            margin-bottom: 20px;
        }
        
        .timeline-date {
            width: 150px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .timeline-content {
            flex-grow: 1;
            padding-left: 20px;
            position: relative;
        }
        
        .timeline-content::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 2px;
            height: 100%;
            background-color: var(--secondary);
        }
        
        .timeline-dot {
            position: absolute;
            left: -6px;
            top: 5px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: var(--secondary);
        }
        
        .status {
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 5px;
        }
        
        .status-pendente {
            background-color: #FFF3CD;
            color: #856404;
        }
        
        .status-processando {
            background-color: #CCE5FF;
            color: #004085;
        }
        
        .status-enviado {
            background-color: #D4EDDA;
            color: #155724;
        }
        
        .status-entregue {
            background-color: #D1ECF1;
            color: #0C5460;
        }
        
        .status-cancelado {
            background-color: #F8D7DA;
            color: #721C24;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--secondary);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background-color: #3dbeb6;
            transform: translateY(-2px);
        }
        
        .btn-back {
            background-color: #6c757d;
            margin-right: 10px;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h1>Detalhes do Pedido #<?php echo htmlspecialchars($pedido['codigo']); ?></h1>
        
        <div class="order-info">
            <div class="info-card">
                <h2 class="section-title">Informações do Pedido</h2>
                
                <div class="info-item">
                    <span class="info-label">Código</span>
                    <div class="info-value"><?php echo htmlspecialchars($pedido['codigo']); ?></div>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Data</span>
                    <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?></div>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Status</span>
                    <div class="info-value">
                        <span class="status status-<?php echo strtolower($pedido['status']); ?>">
                            <?php echo ucfirst($pedido['status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Forma de Pagamento</span>
                    <div class="info-value"><?php echo htmlspecialchars($pedido['forma_pagamento']); ?></div>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Subtotal</span>
                    <div class="info-value">R$ <?php echo number_format($pedido['subtotal'], 2, ',', '.'); ?></div>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Frete</span>
                    <div class="info-value">R$ <?php echo number_format($pedido['frete'], 2, ',', '.'); ?></div>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Total</span>
                    <div class="info-value" style="font-weight: 700;">R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></div>
                </div>
            </div>
            
            <div class="info-card">
                <h2 class="section-title">Endereço de Entrega</h2>
                
                <div class="info-item">
                    <span class="info-label">Destinatário</span>
                    <div class="info-value"><?php echo htmlspecialchars($_SESSION['usuario']['nome']); ?></div>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Endereço</span>
                    <div class="info-value">
                        <?php echo htmlspecialchars($pedido['logradouro']); ?>, <?php echo htmlspecialchars($pedido['numero']); ?><br>
                        <?php if (!empty($pedido['complemento'])): ?>
                            <?php echo htmlspecialchars($pedido['complemento']); ?><br>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($pedido['bairro']); ?><br>
                        <?php echo htmlspecialchars($pedido['cidade']); ?> - <?php echo htmlspecialchars($pedido['estado']); ?><br>
                        CEP: <?php echo htmlspecialchars($pedido['cep']); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <h2 class="section-title">Itens do Pedido</h2>
        <table class="order-items">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Preço Unitário</th>
                    <th>Quantidade</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itens as $item): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="<?php echo htmlspecialchars($item['imagem']); ?>" alt="<?php echo htmlspecialchars($item['nome']); ?>" class="item-img">
                                <?php echo htmlspecialchars($item['nome']); ?>
                            </div>
                        </td>
                        <td>R$ <?php echo number_format($item['preco_unitario'], 2, ',', '.'); ?></td>
                        <td><?php echo $item['quantidade']; ?></td>
                        <td>R$ <?php echo number_format($item['preco_unitario'] * $item['quantidade'], 2, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (count($historico) > 0): ?>
            <h2 class="section-title">Histórico do Pedido</h2>
            <div class="status-timeline">
                <?php foreach ($historico as $status): ?>
                    <div class="timeline-item">
                        <div class="timeline-date">
                            <?php echo date('d/m/Y H:i', strtotime($status['data'])); ?>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-dot"></div>
                            <span class="status status-<?php echo strtolower($status['status']); ?>">
                                <?php echo ucfirst($status['status']); ?>
                            </span>
                            <?php if (!empty($status['observacao'])): ?>
                                <p><?php echo htmlspecialchars($status['observacao']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <a href="perfil.php" class="btn btn-back">Voltar para o Perfil</a>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>