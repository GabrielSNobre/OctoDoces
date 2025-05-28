<?php
session_start();
require 'conexao.php';

// Recebe o ID do produto via GET
$produto_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($produto_id <= 0) {
    header("Location: produtos.php");
    exit();
}

// Busca apenas o produto específico
$stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = :id");
$stmt->bindValue(':id', $produto_id);
$stmt->execute();
$produto = $stmt->fetch();

// Se não encontrar o produto, redireciona
if (!$produto) {
    header("Location: produtos.php");
    exit();
}
?>

<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($produto['nome']) ?> - Octo Doces</title>
    <link rel="stylesheet" href="../styles/stylepage.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .produto-detalhes {
            display: flex;
            gap: 40px;
            margin: 40px 0;
            align-items: flex-start;
        }
        
        .produto-imagem {
            flex: 1;
            max-width: 500px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .produto-imagem img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .produto-info {
            flex: 1;
            padding: 20px;
        }
        
        .produto-nome {
            font-size: 2.2rem;
            margin-bottom: 10px;
            color: #333;
        }
        
        .produto-preco {
            font-size: 1.8rem;
            font-weight: 600;
            color: #e63946;
            margin: 20px 0;
        }
        
        .produto-descricao {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 30px;
            color: #555;
        }
        
        .produto-categoria {
            display: inline-block;
            background: #f8f9fa;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            color: #666;
        }
        
        .quantidade-container {
            display: flex;
            align-items: center;
            margin: 25px 0;
        }
        
        .quantidade-container input {
            width: 70px;
            padding: 10px;
            text-align: center;
            margin-right: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .btn-carrinho {
            background-color: #e63946;
            color: white;
            border: none;
            padding: 12px 25px;
            font-size: 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-carrinho:hover {
            background-color: #d62839;
        }
        
        .produto-extra {
            margin-top: 40px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        
        @media (max-width: 768px) {
            .produto-detalhes {
                flex-direction: column;
            }
            
            .produto-imagem {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>    
    <header>
        <div class="container header-content"> 
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
                    <li><a href="login.php">Login</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="produto-detalhes">
            <div class="produto-imagem">
                <img src="<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>">
            </div>
            
            <div class="produto-info">
                <span class="produto-categoria"><?= htmlspecialchars($produto['categoria']) ?></span>
                <h1 class="produto-nome"><?= htmlspecialchars($produto['nome']) ?></h1>
                <p class="produto-preco">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></p>
                
                <div class="produto-descricao">
                    <?= nl2br(htmlspecialchars($produto['descricao'])) ?>
                </div>
                
                <form method="POST" action="adicionar_carrinho.php">
                    <input type="hidden" name="produto_id" value="<?= $produto['id'] ?>">
                    
                    <div class="quantidade-container">
                        <label for="quantidade">Quantidade:</label>
                        <input type="number" id="quantidade" name="quantidade" value="1" min="1">
                    </div>
                    
                    <button type="submit" name="adicionar_carrinho" class="btn-carrinho">
                        <i class="fas fa-shopping-cart"></i> Adicionar ao Carrinho
                    </button>
                </form>
                
                <?php if (!empty($produto['informacoes_adicionais'])): ?>
                <div class="produto-extra">
                    <h3>Informações Adicionais</h3>
                    <p><?= nl2br(htmlspecialchars($produto['informacoes_adicionais'])) ?></p>
                </div>
                <?php endif; ?>
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