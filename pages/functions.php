<?php
function salvarCarrinho($pdo, $usuario_id) {
    if (!isset($_SESSION['carrinho']) || empty($_SESSION['carrinho'])) {
        return;
    }
    
    // Limpar carrinho antigo
    $pdo->prepare("DELETE FROM carrinhos WHERE usuario_id = ?")->execute([$usuario_id]);
    
    // Inserir itens atuais
    $stmt = $pdo->prepare("INSERT INTO carrinhos (usuario_id, produto_id, quantidade) VALUES (?, ?, ?)");
    
    foreach ($_SESSION['carrinho'] as $produto_id => $quantidade) {
        $stmt->execute([$usuario_id, $produto_id, $quantidade]);
    }
}

function recuperarCarrinho($pdo, $usuario_id) {
    $stmt = $pdo->prepare("SELECT produto_id, quantidade FROM carrinhos WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    $itens = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Retorna como [produto_id => quantidade]
    
    if (!empty($itens)) {
        $_SESSION['carrinho'] = $itens;
    }
}
?>