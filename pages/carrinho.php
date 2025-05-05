<?php
session_start();
require 'conexao.php';

// Redirecionar se não estiver logado
if (!isset($_SESSION['usuario'])) {
    $_SESSION['redirect_to'] = 'carrinho.php';
    header("Location: login.php");
    exit();
}

// Atualizar carrinho
if (isset($_POST['atualizar'])) {
    foreach ($_POST['quantidade'] as $produto_id => $quantidade) {
        if ($quantidade > 0) {
            $_SESSION['carrinho'][$produto_id] = $quantidade;
        } else {
            unset($_SESSION['carrinho'][$produto_id]);
        }
    }
    $_SESSION['mensagem'] = "Carrinho atualizado!";
}

if (isset($_GET['remover'])) {
    $produto_id = $_GET['remover'];
    
    if (isset($_SESSION['carrinho'][$produto_id])) {
        unset($_SESSION['carrinho'][$produto_id]);
        $_SESSION['mensagem'] = "Item removido do carrinho!";
    }
    
    header("Location: carrinho.php");
    exit();
}

// Finalizar compra
if (isset($_POST['finalizar'])) {
    $usuario_id = $_SESSION['usuario']['id'];
    
    // Verificar se tem endereço cadastrado
    $stmt = $pdo->prepare("SELECT endereco FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch();
    
    if (empty($usuario['endereco'])) {
        $_SESSION['erro'] = "Por favor, cadastre um endereço no seu perfil antes de finalizar a compra.";
        header("Location: perfil.php");
        exit();
    }
    
    // Registrar cada item do carrinho como um pedido
    foreach ($_SESSION['carrinho'] as $produto_id => $quantidade) {
        $stmt = $pdo->prepare("INSERT INTO pedidos (usuario_id, produto_id, quantidade, endereco, status) 
                              VALUES (?, ?, ?, ?, 'pendente')");
        $stmt->execute([$usuario_id, $produto_id, $quantidade, $usuario['endereco']]);
    }
    
    // Limpar carrinho
    unset($_SESSION['carrinho']);
    $_SESSION['mensagem'] = "Compra finalizada com sucesso! Acompanhe seus pedidos no seu perfil.";
    header("Location: perfil.php");
    exit();
}

// Buscar informações dos produtos no carrinho
$carrinho_itens = [];
$total_geral = 0;

if (!empty($_SESSION['carrinho'])) {
    $ids = array_keys($_SESSION['carrinho']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $produtos = $stmt->fetchAll();
    
    foreach ($produtos as $produto) {
        $quantidade = $_SESSION['carrinho'][$produto['id']];
        $total = $produto['preco'] * $quantidade;
        $total_geral += $total;
        
        $carrinho_itens[] = [
            'produto' => $produto,
            'quantidade' => $quantidade,
            'total' => $total
        ];
    }
}
?>
<html>
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Octo Doces - Sabores que encantam</title>
    <link rel="stylesheet" href="../styles/style.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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
                    <li><a href="../index.html#produtos.php">Produtos</a></li>
                    <li><a href="../index.html#about">Sobre</a></li>
                    <li><a href="../index.hmtl#contact">Contato</a></li>
                    <li><a href="carrinho.php">Carrinho</a></li>
                    <li><a href="login.php">Login</a></li>
                </ul>
            </nav>
        </div>
    </header>


    <div class="container">
    <h1>Seu Carrinho de Compras</h1>
    
    <?php if (isset($_SESSION['mensagem'])): ?>
        <div class="mensagem-sucesso"><?= $_SESSION['mensagem'] ?></div>
        <?php unset($_SESSION['mensagem']); ?>
    <?php endif; ?>
    
    <?php if (empty($carrinho_itens)): ?>
        <p>Seu carrinho está vazio.</p>
        <a href="produtos.php" class="btn">Continuar Comprando</a>
    <?php else: ?>
        <form method="POST" action="carrinho.php">
            <table class="tabela-carrinho">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Preço</th>
                        <th>Quantidade</th>
                        <th>Total</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($carrinho_itens as $item): ?>
                        <tr>
                            <td>
                                <img src="<?= htmlspecialchars($item['produto']['imagem']) ?>" width="50">
                                <?= htmlspecialchars($item['produto']['nome']) ?>
                            </td>
                            <td>R$ <?= number_format($item['produto']['preco'], 2, ',', '.') ?></td>
                            <td>
                                <input type="number" name="quantidade[<?= $item['produto']['id'] ?>]" 
                                       value="<?= $item['quantidade'] ?>" min="1">
                            </td>
                            <td>R$ <?= number_format($item['total'], 2, ',', '.') ?></td>
                            <td>
                                <a href="carrinho.php?remover=<?= $item['produto']['id'] ?>" class="btn-remover">Remover</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right"><strong>Total Geral:</strong></td>
                        <td colspan="2">R$ <?= number_format($total_geral, 2, ',', '.') ?></td>
                    </tr>
                </tfoot>
            </table>
            
            <div class="acoes-carrinho">
                <button type="submit" name="atualizar" class="btn">Atualizar Carrinho</button>
                <a href="produtos.php" class="btn">Continuar Comprando</a>
                <button type="submit" name="finalizar" class="btn btn-finalizar">Finalizar Compra</button>
            </div>
        </form>
    <?php endif; ?>
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