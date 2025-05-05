<?php
session_start();
require 'conexao.php';

// Verificar se usuário está logado (opcional para visualizar produtos)
// if (!isset($_SESSION['usuario'])) {
//     header("Location: login.php");
//     exit();
// }

// Adicionar item ao carrinho
if (isset($_POST['adicionar_carrinho'])) {
    $produto_id = $_POST['produto_id'];
    $quantidade = isset($_POST['quantidade']) ? max(1, intval($_POST['quantidade'])) : 1;
    
    // Inicializar carrinho se não existir
    if (!isset($_SESSION['carrinho'])) {
        $_SESSION['carrinho'] = [];
    }
    
    // Adicionar/atualizar item no carrinho
    if (isset($_SESSION['carrinho'][$produto_id])) {
        $_SESSION['carrinho'][$produto_id] += $quantidade;
    } else {
        $_SESSION['carrinho'][$produto_id] = $quantidade;
    }
    
    $_SESSION['mensagem'] = "Produto adicionado ao carrinho!";
    header("Location: carrinho.php");
    exit();
}

// Buscar produtos no banco de dados
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : null;

$sql = "SELECT * FROM produtos WHERE 1=1";

if (!empty($busca)) {
    $sql .= " AND (nome LIKE :busca OR descricao LIKE :busca)";
}

if (!empty($categoria)) {
    $sql .= " AND categoria = :categoria";
}

$stmt = $pdo->prepare($sql);

if (!empty($busca)) {
    $stmt->bindValue(':busca', "%$busca%");
}

if (!empty($categoria)) {
    $stmt->bindValue(':categoria', $categoria);
}

$stmt->execute();
$produtos = $stmt->fetchAll();

// Buscar categorias para filtro
$categorias = $pdo->query("SELECT DISTINCT categoria FROM produtos")->fetchAll();
?>
<html>
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Octo Doces - Sabores que encantam</title>
    <link rel="stylesheet" href="../styles/stylepage.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        img{
            width: 30%;
            height: 30%;
        }
    </style>
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
    <h1>Nossos Doces e Bolos</h1>
    
    <!-- Filtros e busca -->
    <div class="filtros">
        <form method="GET" class="form-busca">
            
            <select name="categoria">
                <option value="">Todas categorias</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['categoria']) ?>" 
                        <?= ($categoria == $cat['categoria']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['categoria']) ?>
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
                <img src="<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>">
                <div class="info-produto">
                    <h3><?= htmlspecialchars($produto['nome']) ?></h3>
                    <p class="descricao"><?= htmlspecialchars($produto['descricao']) ?></p>
                    <p class="preco">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></p>
                    <form method="POST">
                        <input type="hidden" name="produto_id" value="<?= $produto['id'] ?>">
                        <input type="number" name="quantidade" value="1" min="1" class="quantidade">
                        <button type="submit" name="adicionar_carrinho" class="btn-carrinho">
                            Adicionar ao Carrinho
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