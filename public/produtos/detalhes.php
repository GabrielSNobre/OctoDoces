<?php
session_start();
// Define path prefix for use in included header and footer
$path_prefix = '../../'; 

// Adjusted path to conexao.php
require $path_prefix . 'config/conexao.php';

// --- Handle Add to Cart ---
if (isset($_POST['adicionar_carrinho'])) {
    $produto_id_cart = isset($_POST['produto_id']) ? intval($_POST['produto_id']) : 0;
    $quantidade_cart = isset($_POST['quantidade']) ? max(1, intval($_POST['quantidade'])) : 1;

    if ($produto_id_cart > 0) {
        if (!isset($_SESSION['carrinho'])) {
            $_SESSION['carrinho'] = [];
        }

        if (isset($_SESSION['carrinho'][$produto_id_cart])) {
            $_SESSION['carrinho'][$produto_id_cart] += $quantidade_cart;
        } else {
            $_SESSION['carrinho'][$produto_id_cart] = $quantidade_cart;
        }
        $_SESSION['mensagem_carrinho'] = "Produto adicionado ao carrinho!";
        // Redirect to cart page (path from public/produtos/)
        header("Location: ../pedidos/carrinho.php");
        exit();
    } else {
        // Potentially set an error message if product_id is invalid for cart addition
        $_SESSION['mensagem_erro'] = "Erro ao adicionar produto ao carrinho: ID inválido.";
        // Redirect back to the product page or product list
        // header("Location: " . $_SERVER['HTTP_REFERER'] ?? 'lista.php'); // Go back or to list
        // For simplicity, if it fails here, it might be due to tampering, so redirect to list.
        header("Location: lista.php");
        exit();
    }
}

// --- Fetch Product Details ---
$produto_id_page = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($produto_id_page <= 0) {
    header("Location: lista.php"); // Redirect to product list if ID is invalid
    exit();
}

// Query to get product details and category name
$stmt = $pdo->prepare("SELECT p.*, c.nome AS categoria_nome 
                       FROM produtos p
                       LEFT JOIN categorias c ON p.categoria_id = c.id
                       WHERE p.id = :id AND p.ativo = TRUE"); // Ensure product is active
$stmt->bindValue(':id', $produto_id_page, PDO::PARAM_INT);
$stmt->execute();
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

// If product not found or not active, redirect to product list
if (!$produto) {
    $_SESSION['mensagem_erro'] = "Produto não encontrado ou indisponível.";
    header("Location: lista.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($produto['nome']) ?> - Octo Doces</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $path_prefix; ?>assets/css/style.css">
</head>
<body>

<?php include $path_prefix . 'includes/header.php'; ?>

<div class="container page-container">
    <?php
    // Display any error messages from redirects (e.g., product not found from cart add fail)
    if (isset($_SESSION['mensagem_erro'])) {
        echo '<div class="message error page-message">' . htmlspecialchars($_SESSION['mensagem_erro']) . '</div>';
        unset($_SESSION['mensagem_erro']);
    }
    ?>
    <div class="produto-detalhes-wrapper">
        <div class="produto-imagem-section">
            <?php
                $image_path_details = $path_prefix . 'assets/img/products/' . htmlspecialchars($produto['imagem']);
                $image_placeholder_details = $path_prefix . 'assets/img/placeholder.png'; 
            ?>
            <img src="<?= file_exists(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim(str_replace('../', '', $image_path_details), '/')) ? $image_path_details : $image_placeholder_details ?>" 
                 alt="<?= htmlspecialchars($produto['nome']) ?>" class="produto-imagem-grande">
        </div>
        
        <div class="produto-info-section">
            <?php if (!empty($produto['categoria_nome'])): ?>
                <span class="produto-categoria-tag"><?= htmlspecialchars($produto['categoria_nome']) ?></span>
            <?php endif; ?>
            <h1 class="produto-nome-detalhes"><?= htmlspecialchars($produto['nome']) ?></h1>
            
            <p class="produto-preco-detalhes">
                R$ <?= number_format($produto['preco'], 2, ',', '.') ?>
                <?php /* Add logic here if you have original_price vs sale_price
                if (isset($produto['preco_original']) && $produto['preco_original'] > $produto['preco']) {
                    echo ' <span class="preco-original-detalhes">R$ ' . number_format($produto['preco_original'], 2, ',', '.') . '</span>';
                }
                */ ?>
            </p>
            
            <div class="produto-descricao-detalhes">
                <?= nl2br(htmlspecialchars($produto['descricao'])) ?>
            </div>
            
            <form method="POST" action="detalhes.php?id=<?= $produto_id_page ?>" class="form-add-carrinho-detalhes">
                <input type="hidden" name="produto_id" value="<?= $produto['id'] ?>">
                
                <div class="quantidade-controls-detalhes">
                    <label for="quantidade-detalhes">Quantidade:</label>
                    <input type="number" id="quantidade-detalhes" name="quantidade" value="1" min="1" max="99" class="quantidade-input-detalhes">
                </div>
                
                <button type="submit" name="adicionar_carrinho" class="btn btn-adicionar-carrinho-detalhes">
                    <i class="fas fa-shopping-cart"></i> Adicionar ao Carrinho
                </button>
            </form>
            
            <?php if (!empty($produto['informacoes_adicionais'])): ?>
            <div class="produto-informacoes-adicionais">
                <h3>Informações Adicionais</h3>
                <p><?= nl2br(htmlspecialchars($produto['informacoes_adicionais'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php include $path_prefix . 'includes/footer.php'; ?>

</body>
</html>