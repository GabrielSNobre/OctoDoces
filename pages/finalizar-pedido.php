<?php
session_start();
require 'conexao.php';

// Redirecionar se não estiver logado
if (!isset($_SESSION['usuario'])) {
    $_SESSION['redirect_to'] = 'finalizar-pedido.php';
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario']['id'];
$erro = '';
$sucesso = '';

// Verificar se o carrinho está vazio
if (empty($_SESSION['carrinho'])) {
    header("Location: carrinho.php");
    exit();
}

// Obter informações do usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();

// Obter endereços do usuário
$enderecos = $pdo->prepare("SELECT e.*, ue.apelido 
                           FROM enderecos e
                           JOIN usuario_enderecos ue ON e.id = ue.endereco_id
                           WHERE ue.usuario_id = ?
                           ORDER BY e.principal DESC");
$enderecos->execute([$usuario_id]);
$enderecos = $enderecos->fetchAll();

// Obter itens do carrinho
$carrinho_itens = [];
$subtotal = 0;

$ids = array_keys($_SESSION['carrinho']);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
    
$stmt = $pdo->prepare("SELECT * FROM produtos WHERE id IN ($placeholders)");
$stmt->execute($ids);
$produtos = $stmt->fetchAll();
    
foreach ($produtos as $produto) {
    $quantidade = $_SESSION['carrinho'][$produto['id']];
    $preco = $produto['preco_promocional'] > 0 ? $produto['preco_promocional'] : $produto['preco'];
    $total = $preco * $quantidade;
    $subtotal += $total;
        
    $carrinho_itens[] = [
        'produto' => $produto,
        'quantidade' => $quantidade,
        'preco' => $preco,
        'total' => $total
    ];
}

// Calcular frete (exemplo fixo)
$frete = 15.00;
$total_geral = $subtotal + $frete;

// Processar finalização do pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $endereco_id = $_POST['endereco_id'];
    $forma_pagamento = $_POST['forma_pagamento'];
    $observacao = trim($_POST['observacao'] ?? '');
    
    // Validar dados
    if (empty($endereco_id)) {
        $erro = 'Selecione um endereço de entrega';
    } elseif (empty($forma_pagamento)) {
        $erro = 'Selecione uma forma de pagamento';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Gerar código do pedido
            $codigo_pedido = 'PED' . date('Ymd') . strtoupper(substr(uniqid(), -6));
            
            // Criar pedido
            $stmt = $pdo->prepare("INSERT INTO pedidos 
                                  (usuario_id, endereco_id, codigo, subtotal, frete, total, forma_pagamento, observacao, status)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendente')");
            $stmt->execute([
                $usuario_id, 
                $endereco_id, 
                $codigo_pedido,
                $subtotal,
                $frete,
                $total_geral,
                $forma_pagamento,
                $observacao
            ]);
            $pedido_id = $pdo->lastInsertId();
            
            // Adicionar itens ao pedido
            foreach ($carrinho_itens as $item) {
                $stmt = $pdo->prepare("INSERT INTO pedido_itens 
                                      (pedido_id, produto_id, quantidade, preco_unitario)
                                      VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $pedido_id, 
                    $item['produto']['id'], 
                    $item['quantidade'], 
                    $item['preco']
                ]);
                
                // Atualizar estoque
                $stmt = $pdo->prepare("UPDATE produtos SET estoque = estoque - ? WHERE id = ?");
                $stmt->execute([$item['quantidade'], $item['produto']['id']]);
            }
            
            // Registrar status inicial
            $stmt = $pdo->prepare("INSERT INTO pedido_status 
                                  (pedido_id, status, observacao)
                                  VALUES (?, 'pendente', 'Pedido criado')");
            $stmt->execute([$pedido_id]);
            
            $pdo->commit();
            
            // Limpar carrinho
            unset($_SESSION['carrinho']);
            
            // Redirecionar para página de confirmação
            header("Location: confirmacao-pedido.php?id=$pedido_id");
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $erro = 'Erro ao finalizar pedido: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Pedido - Octo Doces</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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
            background-color: #f9f9f9;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        
        nav ul {
            display: flex;
            list-style: none;
            gap: 20px;
        }
        
        nav a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            transition: color 0.3s;
        }
        
        nav a:hover {
            color: var(--primary);
        }
        
        h1 {
            text-align: center;
            margin: 30px 0;
            color: var(--secondary);
            font-size: 2.2rem;
        }
        
        .checkout-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .step {
            flex: 1;
            text-align: center;
            position: relative;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            background-color: #ddd;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
        }
        
        .step.active .step-number {
            background-color: var(--secondary);
            color: white;
        }
        
        .step.completed .step-number {
            background-color: var(--accent);
            color: var(--dark);
        }
        
        .step::after {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            right: -50%;
            height: 2px;
            background-color: #ddd;
            z-index: -1;
        }
        
        .step:last-child::after {
            display: none;
        }
        
        .checkout-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        @media (max-width: 768px) {
            .checkout-content {
                grid-template-columns: 1fr;
            }
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.3rem;
            color: var(--secondary);
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid var(--accent);
        }
        
        /* Resumo do pedido */
        .resumo-pedido {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            padding: 20px;
            align-self: start;
            position: sticky;
            top: 100px;
        }
        
        .resumo-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .resumo-item-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 15px;
        }
        
        .resumo-item-info {
            flex: 1;
        }
        
        .resumo-item-nome {
            font-weight: 600;
        }
        
        .resumo-item-preco {
            color: var(--primary);
            font-weight: 700;
        }
        
        .resumo-linha {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .resumo-total {
            font-weight: 700;
            font-size: 1.2rem;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid var(--secondary);
        }
        
        /* Formulário */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .address-list {
            display: grid;
            gap: 15px;
        }
        
        .address-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .address-card.selected {
            border-color: var(--secondary);
            background-color: rgba(78, 205, 196, 0.1);
        }
        
        .address-card.principal::after {
            content: 'Principal';
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--accent);
            color: var(--dark);
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .payment-methods {
            display: grid;
            gap: 10px;
        }
        
        .payment-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-card.selected {
            border-color: var(--secondary);
            background-color: rgba(78, 205, 196, 0.1);
        }
        
        .btn-finalizar {
            width: 100%;
            padding: 15px;
            background-color: var(--secondary);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s;
            margin-top: 20px;
        }
        
        .btn-finalizar:hover {
            background-color: #3dbeb6;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .btn-finalizar:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
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
        
        /* Footer */
        footer {
            background-color: var(--dark);
            color: white;
            padding: 50px 0 20px;
            margin-top: 50px;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .footer-section h3 {
            color: var(--accent);
            margin-bottom: 20px;
            font-size: 1.2rem;
        }
        
        .footer-section p {
            margin-bottom: 10px;
        }
        
        .social-icons {
            display: flex;
            gap: 15px;
        }
        
        .social-icons a {
            color: white;
            font-size: 1.2rem;
            transition: color 0.3s;
        }
        
        .social-icons a:hover {
            color: var(--accent);
        }
        
        .copyright {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
    </style>
</head>
<body>
<header>
    <div class="container header-content">
        <div class="container-fluid col-11 m-auto">
            <a href="index.html"><img src="../img/Logo.png" style="height: 80px; width: 80px;"></a>
        </div>
        <nav>
            <ul>
                <li><a href="../index.html">Início</a></li>
                <li><a href="produtos.php">Produtos</a></li>
                <li><a href="../index.html#about">Sobre</a></li>
                <li><a href="../index.html#contact">Contato</a></li>
                <li><a href="carrinho.php">Carrinho <span class="cart-count"><?= count($_SESSION['carrinho'] ?? []) ?></span></a></li>
                <li><a href="perfil.php">Minha Conta</a></li>
            </ul>
        </nav>
    </div>
</header>

<div class="container">
    <h1>Finalizar Pedido</h1>
    
    <div class="checkout-steps">
        <div class="step completed">
            <div class="step-number">1</div>
            <div>Carrinho</div>
        </div>
        <div class="step active">
            <div class="step-number">2</div>
            <div>Informações</div>
        </div>
        <div class="step">
            <div class="step-number">3</div>
            <div>Confirmação</div>
        </div>
    </div>
    
    <?php if ($erro): ?>
        <div class="message error"><?= $erro ?></div>
    <?php endif; ?>
    
    <form method="POST" action="finalizar-pedido.php">
        <div class="checkout-content">
            <div>
                <div class="section">
                    <h2 class="section-title">Endereço de Entrega</h2>
                    
                    <?php if (count($enderecos) > 0): ?>
                        <div class="address-list">
                            <?php foreach ($enderecos as $endereco): ?>
                                <label>
                                    <div class="address-card <?= $endereco['principal'] ? 'principal' : '' ?> <?= (isset($_POST['endereco_id']) && $_POST['endereco_id'] == $endereco['id']) ? 'selected' : '' ?>">
                                        <input type="radio" name="endereco_id" value="<?= $endereco['id'] ?>" 
                                            <?= (isset($_POST['endereco_id']) && $_POST['endereco_id'] == $endereco['id']) ? 'checked' : '' ?>
                                            <?= (!$endereco['principal'] && !isset($_POST['endereco_id'])) ? '' : 'checked' ?>
                                            style="display: none;">
                                        <p><strong><?= htmlspecialchars($endereco['apelido'] ?? 'Endereço') ?></strong></p>
                                        <p><?= htmlspecialchars($endereco['logradouro']) ?>, <?= htmlspecialchars($endereco['numero']) ?></p>
                                        <?php if (!empty($endereco['complemento'])): ?>
                                            <p><?= htmlspecialchars($endereco['complemento']) ?></p>
                                        <?php endif; ?>
                                        <p><?= htmlspecialchars($endereco['bairro']) ?> - <?= htmlspecialchars($endereco['cidade']) ?>/<?= htmlspecialchars($endereco['estado']) ?></p>
                                        <p>CEP: <?= htmlspecialchars($endereco['cep']) ?></p>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <a href="adicionar-endereco.php" style="display: inline-block; margin-top: 15px;">Adicionar novo endereço</a>
                    <?php else: ?>
                        <p>Você não possui endereços cadastrados.</p>
                        <a href="adicionar-endereco.php" class="btn">Cadastrar Endereço</a>
                    <?php endif; ?>
                </div>
                
                <div class="section">
                    <h2 class="section-title">Forma de Pagamento</h2>
                    
                    <div class="payment-methods">
                        <label>
                            <div class="payment-card <?= (isset($_POST['forma_pagamento']) && $_POST['forma_pagamento'] === 'cartao_credito') ? 'selected' : '' ?>">
                                <input type="radio" name="forma_pagamento" value="cartao_credito" 
                                    <?= (isset($_POST['forma_pagamento']) && $_POST['forma_pagamento'] === 'cartao_credito') ? 'checked' : '' ?>
                                    style="display: none;">
                                <strong>Cartão de Crédito</strong>
                                <p>Pague com seu cartão de crédito</p>
                            </div>
                        </label>
                        
                        <label>
                            <div class="payment-card <?= (isset($_POST['forma_pagamento']) && $_POST['forma_pagamento'] === 'cartao_debito') ? 'selected' : '' ?>">
                                <input type="radio" name="forma_pagamento" value="cartao_debito" 
                                    <?= (isset($_POST['forma_pagamento']) && $_POST['forma_pagamento'] === 'cartao_debito') ? 'checked' : '' ?>
                                    style="display: none;">
                                <strong>Cartão de Débito</strong>
                                <p>Pague com seu cartão de débito</p>
                            </div>
                        </label>
                        
                        <label>
                            <div class="payment-card <?= (isset($_POST['forma_pagamento']) && $_POST['forma_pagamento'] === 'pix') ? 'selected' : '' ?>">
                                <input type="radio" name="forma_pagamento" value="pix" 
                                    <?= (isset($_POST['forma_pagamento']) && $_POST['forma_pagamento'] === 'pix') ? 'checked' : '' ?>
                                    style="display: none;">
                                <strong>PIX</strong>
                                <p>Pague instantaneamente com PIX</p>
                            </div>
                        </label>
                        
                        <label>
                            <div class="payment-card <?= (isset($_POST['forma_pagamento']) && $_POST['forma_pagamento'] === 'boleto') ? 'selected' : '' ?>">
                                <input type="radio" name="forma_pagamento" value="boleto" 
                                    <?= (isset($_POST['forma_pagamento']) && $_POST['forma_pagamento'] === 'boleto') ? 'checked' : '' ?>
                                    style="display: none;">
                                <strong>Boleto Bancário</strong>
                                <p>Pague com boleto bancário</p>
                            </div>
                        </label>
                    </div>
                </div>
                
                <div class="section">
                    <h2 class="section-title">Observações</h2>
                    <textarea name="observacoes" class="form-control" placeholder="Alguma observação sobre seu pedido?"><?= htmlspecialchars($_POST['observacao'] ?? '') ?></textarea>
                </div>
            </div>
            
            <div class="resumo-pedido">
                <h2 class="section-title">Resumo do Pedido</h2>
                
                <?php foreach ($carrinho_itens as $item): ?>
                    <div class="resumo-item">
                        <img src="<?= htmlspecialchars($item['produto']['imagem']) ?>" alt="<?= htmlspecialchars($item['produto']['nome']) ?>" class="resumo-item-img">
                        <div class="resumo-item-info">
                            <div class="resumo-item-nome"><?= htmlspecialchars($item['produto']['nome']) ?></div>
                            <div class="resumo-item-preco">R$ <?= number_format($item['preco'], 2, ',', '.') ?> × <?= $item['quantidade'] ?></div>
                        </div>
                        <div>R$ <?= number_format($item['total'], 2, ',', '.') ?></div>
                    </div>
                <?php endforeach; ?>
                
                <div class="resumo-linha">
                    <span>Subtotal:</span>
                    <span>R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
                </div>
                
                <div class="resumo-linha">
                    <span>Frete:</span>
                    <span>R$ <?= number_format($frete, 2, ',', '.') ?></span>
                </div>
                
                <div class="resumo-linha resumo-total">
                    <span>Total:</span>
                    <span>R$ <?= number_format($total_geral, 2, ',', '.') ?></span>
                </div>
                
                <button type="submit" class="btn-finalizar">
                    <i class="fas fa-credit-card"></i> Finalizar Pedido
                </button>
            </div>
        </div>
    </form>
</div>

<footer id="contact">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h3>Sobre Nós</h3>
                <p>A Octo Doces nasceu do amor por doces e da vontade de trazer sabores especiais para momentos especiais.</p>
            </div>

            <div class="footer-section">
                <h3>Contato</h3>
                <p><i class="fas fa-map-marker-alt"></i> Rua dos Doces, 123 - Sigma</p>
                <p><i class="fas fa-phone"></i> (00) 1234-5678</p>
                <p><i class="fas fa-envelope"></i> contato@octodoces.com</p>
            </div>

            <div class="footer-section">
                <h3>Redes Sociais</h3>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2023 Octo Doces. Todos os direitos reservados.</p>
        </div>
    </div>
</footer>
</body>
</html>