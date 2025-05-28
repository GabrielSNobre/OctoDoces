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
        WHERE 1=1";

if (!empty($busca)) {
    $sql .= " AND (p.nome LIKE :busca OR p.descricao LIKE :busca)";
}

if (!empty($categoria_id)) {
    $sql .= " AND p.categoria_id = :categoria_id";
}

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
    <title>Octo Doces - Sabores que encantam</title>
    <link rel="stylesheet" href="../styles/stylepage.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        img {
            width: 30%;
            height: 30%;
        }
    </style>
</head>
<body>
<header>
    <div class="container header-content">
        <div class="container-fluid col-11 m-auto">
            <a href="index.html"><img src="../img/Logo.png" style="height: 100px; width: 100px;"></a>
        </div>
        <nav>
            <ul>
                <li><a href="../index.html">Início</a></li>
                <li><a href="produtos.php">Produtos</a></li>
                <li><a href="../index.html#about">Sobre</a></li>
                <li><a href="../index.html#contact">Contato</a></li>
                <li><a href="carrinho.php">Carrinho</a></li>
                <li><a href="login.php">Login</a></li>
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
            <a href="pagina-produto.php?id=<?= $produto['id'] ?>">
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
            </a>
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
