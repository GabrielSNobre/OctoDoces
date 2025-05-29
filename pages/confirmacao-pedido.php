<?php
session_start();
require 'conexao.php';

// Verificar se o ID do pedido foi passado
if (!isset($_GET['id'])) {
    header("Location: perfil.php");
    exit();
}

$pedido_id = $_GET['id'];
$usuario_id = $_SESSION['usuario']['id'];

// Buscar informações do pedido
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

// Buscar itens do pedido
$itens = $pdo->prepare("SELECT pi.*, pr.nome, pr.imagem 
                       FROM pedido_itens pi
                       JOIN produtos pr ON pi.produto_id = pr.id
                       WHERE pi.pedido_id = ?");
$itens->execute([$pedido_id]);
$itens = $itens->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Confirmado - Octo Doces</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="../styles/stylepage.css">
    <style>
        /* Mesmo estilo da página de finalização */
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
        
        .confirmation-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        
        .confirmation-icon {
            font-size: 4rem;
            color: var(--secondary);
            margin-bottom: 20px;
        }
        
        .confirmation-title {
            font-size: 2rem;
            color: var(--secondary);
            margin-bottom: 15px;
        }
        
        .confirmation-code {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 20px 0;
            color: var(--dark);
        }
        
        .confirmation-message {
            font-size: 1.1rem;
            margin-bottom: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background-color: var(--secondary);
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            margin: 10px;
        }
        
        .btn:hover {
            background-color: #3dbeb6;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 2px solid var(--secondary);
            color: var(--secondary);
        }
        
        .btn-outline:hover {
            background-color: var(--secondary);
            color: white;
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
            <a href="../index.html"><img src="../img/Logo.png" style="height: 80px; width: 80px;"></a>
        </div>
        <nav>
            <ul>
                <li><a href="../index.html">Início</a></li>
                <li><a href="produtos.php">Produtos</a></li>
                <li><a href="../index.html#about">Sobre</a></li>
                <li><a href="../index.html#contact">Contato</a></li>
                <li><a href="carrinho.php">Carrinho</a></li>
                <li><a href="perfil.php">Minha Conta</a></li>
            </ul>
        </nav>
    </div>
</header>

<div class="container">
    <div class="confirmation-card">
        <div class="confirmation-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1 class="confirmation-title">Pedido Confirmado!</h1>
        <p class="confirmation-message">Obrigado por comprar conosco. Seu pedido foi recebido e está sendo processado.</p>
        
        <div class="confirmation-code">
            Número do Pedido: <?= htmlspecialchars($pedido['codigo']) ?>
        </div>
        
        <p>Você receberá um e-mail com os detalhes do seu pedido em breve.</p>
        
        <div style="margin-top: 30px;">
            <a href="perfil.php" class="btn">Acompanhar Pedido</a>
            <a href="produtos.php" class="btn btn-outline">Continuar Comprando</a>
        </div>
    </div>
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