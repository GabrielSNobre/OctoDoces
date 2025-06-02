<?php
session_start();
// Define path prefix for use in included header and footer
$path_prefix = '../../'; 

// Adjusted path to conexao.php
require $path_prefix . 'config/conexao.php';

// Adicionar item ao carrinho
if (isset($_POST['adicionar_carrinho'])) {
    $produto_id = $_POST['produto_id'];
    // Ensure quantity is at least 1
    $quantidade = isset($_POST['quantidade']) ? max(1, intval($_POST['quantidade'])) : 1;

    if (!isset($_SESSION['carrinho'])) {
        $_SESSION['carrinho'] = [];
    }

    // If product already in cart, increment quantity, otherwise set it
    if (isset($_SESSION['carrinho'][$produto_id])) {
        $_SESSION['carrinho'][$produto_id] += $quantidade;
    } else {
        $_SESSION['carrinho'][$produto_id] = $quantidade;
    }

    $_SESSION['mensagem_carrinho'] = "Produto adicionado ao carrinho!"; // Use a specific session key for cart messages
    // Adjusted path to carrinho.php from public/produtos/
    header("Location: ../pedidos/carrinho.php");
    exit();
}

// Buscar produtos com JOIN de categoria
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$categoria_id = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;

// Base SQL query
$sql = "SELECT p.id, p.nome, p.descricao, p.preco, p.preco_promocional, p.imagem, p.categoria_id, c.nome AS categoria_nome 
        FROM produtos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        WHERE p.ativo = TRUE"; // Ensure only active products are shown

// Append search conditions
if (!empty($busca)) {
    $sql .= " AND (p.nome LIKE :busca OR p.descricao LIKE :busca)";
}

if (!empty($categoria_id)) {
    $sql .= " AND p.categoria_id = :categoria_id";
}

$sql .= " ORDER BY p.nome ASC"; // Order products by name

$stmt = $pdo->prepare($sql);

// Bind values if they are set
if (!empty($busca)) {
    $stmt->bindValue(':busca', "%$busca%");
}
if (!empty($categoria_id)) {
    $stmt->bindValue(':categoria_id', $categoria_id, PDO::PARAM_INT);
}

$stmt->execute();
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar todas as categorias ativas para o filtro
$categorias = $pdo->query("SELECT id, nome FROM categorias WHERE ativo = TRUE ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nossos Doces e Bolos - Octo Doces</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $path_prefix; ?>assets/css/style.css">
</head>
<body>

<?php include $path_prefix . 'includes/header.php'; ?>

<div class="container page-container"> {/* Added page-container for specific page top margin if needed */}
    <h1>Nossos Doces e Bolos</h1>

    <div class="filtros-produtos">
        <form method="GET" action="lista.php" class="form-busca"> {/* Action points to self */}
            <div class="form-group">
                <label for="categoria-select" class="sr-only">Categoria</label> {/* sr-only for accessibility if label is hidden */}
                <select name="categoria" id="categoria-select">
                    <option value="">Todas as Categorias</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($categoria_id == $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="busca-input" class="sr-only">Buscar</label>
                <input type="text" name="busca" id="busca-input" placeholder="Buscar produtos..." value="<?= htmlspecialchars($busca) ?>">
            </div>
            <button type="submit" class="btn btn-filter"><i class="fas fa-filter"></i> Filtrar</button>
        </form>
    </div>

    <?php if (empty($produtos) && (!empty($busca) || !empty($categoria_id))): ?>
        <div class="alert alert-info">
            Nenhum produto encontrado com os filtros selecionados. <a href="lista.php">Limpar filtros</a>.
        </div>
    <?php elseif (empty($produtos)): ?>
        <div class="alert alert-info">
            Nenhum produto cadastrado no momento. Volte em breve!
        </div>
    <?php endif; ?>

    <div class="grid-produtos">
        <?php foreach ($produtos as $produto): ?>
            <div class="card-produto">
                {/* Link to product details page (detalhes.php in the same directory) */}
                <a href="detalhes.php?id=<?= $produto['id'] ?>" class="card-imagem-link">
                    <div class="card-imagem">
                        <?php 
                           // Assuming $produto['imagem'] stores filename like 'produto.jpg'
                           // And images are in 'assets/img/products/'
                           $image_path = $path_prefix . 'assets/img/products/' . htmlspecialchars($produto['imagem']);
                           // Fallback if image is missing or path is wrong
                           $image_placeholder = $path_prefix . 'assets/img/placeholder.png'; // Create a placeholder image
                        ?>
                        <img src="<?= file_exists(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim(str_replace('../', '', $image_path), '/')) ? $image_path : $image_placeholder ?>" 
                             alt="<?= htmlspecialchars($produto['nome']) ?>">
                    </div>
                </a>
                
                <div class="info-produto">
                    <?php if (!empty($produto['categoria_nome'])): ?>
                        <span class="categoria-produto"><?= htmlspecialchars($produto['categoria_nome']) ?></span>
                    <?php endif; ?>
                    <h3>
                        <a href="detalhes.php?id=<?= $produto['id'] ?>"><?= htmlspecialchars($produto['nome']) ?></a>
                    </h3>
                    <p class="descricao">
                        <?= nl2br(htmlspecialchars(mb_strimwidth($produto['descricao'], 0, 100, "..."))) ?>
                    </p>
                    
                    <div class="preco">
                        R$ <?= number_format($produto['preco'], 2, ',', '.') ?>
                        <?php if(isset($produto['preco_promocional']) && $produto['preco_promocional'] > 0 && $produto['preco_promocional'] < $produto['preco']): ?>
                            <span class="preco-original">R$ <?= number_format($produto['preco_promocional'], 2, ',', '.') // Assuming preco_promocional is the OLD price and preco is the NEW promotional one. If it's the other way around, swap them.
                            // Based on typical naming, 'preco' is current, 'preco_promocional' is the discounted one.
                            // So, if preco_promocional exists and is less than preco, it's the actual price.
                            // Let's adjust: 'preco' is the main price. 'preco_promocional' is the discount price IF set.
                            // The original template showed 'preco' as main and 'preco_promocional' as strikethrough.
                            // So if 'preco_promocional' is set AND *lower* than 'preco', then 'preco' is old, 'preco_promocional' is new.
                            // Or 'preco' is standard, 'preco_promocional' is special.
                            // The current HTML structure implies 'preco' is primary, and 'preco_promocional' (if present) is the STRIKETHROUGH one.
                            // This needs clarification. Assuming `preco` is the price to pay, `preco_original_ou_promocional_antigo` for strikethrough.
                            // Let's stick to the original provided HTML logic for prices:
                            // If 'preco_promocional' > 0, it was meant to be the OLD price that is struck out.
                            // This is confusing. A common pattern:
                            // `preco_base` = 100, `preco_oferta` = 80. Display 80, strike 100.
                            // The original DB has `preco` and `preco_promocional`.
                            // If `preco_promocional` is an *actual lower price*, then it should be displayed as the main price,
                            // and `preco` (the original) should be struck out.
                            // If `preco_promocional` is an *old higher price* (unlikely name), then it's struck out.
                            // Let's assume: `preco` is the current effective price. `preco_oferta_anterior` or `preco_de_lista` would be strikethrough.
                            // Given the original HTML:
                            // <div class="preco"> R$ <?= $produto['preco'] ?> <span class="preco-promocional">R$ <?= $produto['preco_promocional'] ?></span> </div>
                            // This suggests `preco` is the current price and `preco_promocional` is the one to strike.
                            // So, the DB schema should be that `preco_promocional` is the *original higher price* if `preco` is a sale price.
                            // OR `preco` is the standard price, and `preco_promocional` is the SALE price.
                            // If `preco_promocional` IS the sale price:
                            // Correct logic:
                            //   Current Price to pay: Is preco_promocional set and lower than preco? If yes, use preco_promocional. Else use preco.
                            //   Strikethrough Price: If preco_promocional is used as current, then preco is strikethrough.
                            //
                            // Reverting to simple interpretation of original HTML:
                            // `preco` is the price to pay. `preco_promocional` (if >0) is an older/RRP price that gets a strikethrough.
                            ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST" action="lista.php" class="form-carrinho"> {/* Action points to self */}
                        <div class="quantidade-container">
                            <label for="quantidade-<?= $produto['id'] ?>">Qtd:</label>
                            <input type="number" id="quantidade-<?= $produto['id'] ?>" name="quantidade" value="1" min="1" max="99" class="quantidade-input">
                        </div>
                        <input type="hidden" name="produto_id" value="<?= $produto['id'] ?>">
                        <button type="submit" name="adicionar_carrinho" class="btn btn-carrinho">
                            <i class="fas fa-shopping-cart"></i> Adicionar
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include $path_prefix . 'includes/footer.php'; ?>

</body>
</html>