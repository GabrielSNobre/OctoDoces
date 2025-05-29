<?php
session_start();
require 'conexao.php';

// Adicionar item ao carrinho
if (isset($_POST['adicionar_carrinho'])) {
    $produto_id = $_POST['produto_id'];
    $quantidade = isset($_POST['quantidade']) ? max(1, intval($_POST['quantidade'])) : 1;

    if (!isset($_SESSION['carrinho'])) {
        $_SESSION['carrinho'] = [];
    }

    if (isset($_SESSION['carrinho'][$produto_id])) {
        $_SESSION['carrinho'][$produto_id] += $quantidade;
    } else {
        $_SESSION['carrinho'][$produto_id] = $quantidade;
    }

    $_SESSION['mensagem'] = "Produto adicionado ao carrinho!";
    header("Location: carrinho.php");
    exit();
}

// Buscar produtos com JOIN de categoria
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$categoria_id = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;

$sql = "SELECT p.*, c.nome AS categoria_nome 
        FROM produtos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        WHERE p.ativo = TRUE";

if (!empty($busca)) {
    $sql .= " AND (p.nome LIKE :busca OR p.descricao LIKE :busca)";
}

if (!empty($categoria_id)) {
    $sql .= " AND p.categoria_id = :categoria_id";
}

$sql .= " ORDER BY p.nome";

$stmt = $pdo->prepare($sql);

if (!empty($busca)) {
    $stmt->bindValue(':busca', "%$busca%");
}
if (!empty($categoria_id)) {
    $stmt->bindValue(':categoria_id', $categoria_id, PDO::PARAM_INT);
}

$stmt->execute();
$produtos = $stmt->fetchAll();

// Buscar categorias
$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nome")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Octo Doces - Sabores que encantam</title>
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
        
        /* Filtros */
        .filtros {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .form-busca {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .form-busca select, 
        .form-busca input[type="text"] {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            flex: 1;
            min-width: 200px;
        }
        
        .form-busca button {
            background-color: var(--secondary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .form-busca button:hover {
            background-color: #3dbeb6;
            transform: translateY(-2px);
        }
        
        /* Grid de produtos */
        .grid-produtos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .card-produto {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .card-produto:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .card-imagem {
            height: 200px;
            position: relative;
            overflow: hidden;
        }
        
        .card-imagem img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .card-produto:hover .card-imagem img {
            transform: scale(1.05);
        }
        
        .info-produto {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .info-produto h3 {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .categoria-produto {
            display: inline-block;
            background-color: var(--accent);
            color: var(--dark);
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .descricao {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
            flex: 1;
        }
        
        .preco {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .preco-promocional {
            text-decoration: line-through;
            color: #999;
            font-size: 1rem;
            margin-left: 5px;
        }
        
        .form-carrinho {
            margin-top: auto;
        }
        
        .quantidade-container {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .quantidade-container label {
            margin-right: 10px;
            font-size: 0.9rem;
        }
        
        .quantidade {
            width: 60px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        
        .btn-carrinho {
            width: 100%;
            padding: 10px;
            background-color: var(--secondary);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-carrinho:hover {
            background-color: #3dbeb6;
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
        
        /* Responsividade */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            nav ul {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .form-busca {
                flex-direction: column;
            }
        }
    </style>
    <link rel="stylesheet" href="../styles/stylepage.css">
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
                <li><a href="carrinho.php">Carrinho</a></li>
               <li><a href="../index.html">Início</a></li>
                <?php if (isset($_SESSION['usuario'])): ?>
                <!-- Usuário logado -->
                <li>
                <a href="perfil.php" class="btn-login <?php echo ($_SESSION['usuario']['tipo_usuario_id'] == 2) ? 'admin' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <?php echo htmlspecialchars($_SESSION['usuario']['nome']); ?>
                    <?php if ($_SESSION['usuario']['tipo_usuario_id'] == 2): ?>
                        <span class="admin-badge">ADMIN</span>
                    <?php endif; ?>
                </a>
                </li>
                <?php else: ?>
                <!-- Usuário não logado -->
                <li><a href="login.php" class="btn-login">Login</a></li>
             <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<div class="container">
    <h1>Nossos Doces e Bolos</h1>

    <!-- Filtros -->
    <div class="filtros">
        <form method="GET" class="form-busca">
            <select name="categoria">
                <option value="">Todas categorias</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($categoria_id == $cat['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="text" name="busca" placeholder="Buscar produtos..." value="<?= htmlspecialchars($busca) ?>">
            <button type="submit">Filtrar</button>
        </form>
    </div>

    <!-- Lista de produtos -->
    <div class="grid-produtos">
        <?php foreach ($produtos as $produto): ?>
            <div class="card-produto">
                <a href="pagina-produto.php?id=<?= $produto['id'] ?>" class="card-imagem">
                    <img src="<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>">
                </a>
                
                <div class="info-produto">
                    <span class="categoria-produto"><?= htmlspecialchars($produto['categoria_nome'] ?? 'Geral') ?></span>
                    <h3><?= htmlspecialchars($produto['nome']) ?></h3>
                    <p class="descricao"><?= htmlspecialchars($produto['descricao']) ?></p>
                    
                    <div class="preco">
                        R$ <?= number_format($produto['preco'], 2, ',', '.') ?>
                        <?php if($produto['preco_promocional'] > 0): ?>
                            <span class="preco-promocional">R$ <?= number_format($produto['preco_promocional'], 2, ',', '.') ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST" class="form-carrinho">
                        <div class="quantidade-container">
                            <label for="quantidade-<?= $produto['id'] ?>">Qtd:</label>
                            <input type="number" id="quantidade-<?= $produto['id'] ?>" name="quantidade" value="1" min="1" class="quantidade">
                        </div>
                        <input type="hidden" name="produto_id" value="<?= $produto['id'] ?>">
                        <button type="submit" name="adicionar_carrinho" class="btn-carrinho">
                            <i class="fas fa-shopping-cart"></i> Adicionar ao Carrinho
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
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