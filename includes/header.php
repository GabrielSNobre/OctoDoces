<?php
// Inicia a sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado
$usuarioLogado = isset($_SESSION['usuario_id']);
$isAdmin = $usuarioLogado && isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'administrador';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($tituloPagina) ? $tituloPagina : 'Octo Doces - Sabores que encantam' ?></title>
    <link rel="stylesheet" href="../styles/stylepage.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <?= isset($cssExtra) ? $cssExtra : '' ?>
</head>
<body>
    <header>
        <div class="container header-content"> 
            <div class="container-fluid col-11 m-auto"> 
                <a href="index.php"> <img src="../img/Logo.png" style="height: 100px; width: 100px;" alt="Logo Octo Doces"> </a>
            </div>
            <nav>
                <ul>
                    <li><a href="../index.php">Início</a></li>
                    <li><a href="produtos.php">Produtos</a></li>
                    <li><a href="../index.php#about">Sobre</a></li>
                    <li><a href="../index.php#contact">Contato</a></li>
                    <li><a href="carrinho.php">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($usuarioLogado && isset($_SESSION['itens_carrinho'])): ?>
                            <span class="carrinho-contador"><?= $_SESSION['itens_carrinho'] ?></span>
                        <?php endif; ?>
                    </a></li>
                    <?php if ($usuarioLogado): ?>
                        <li class="menu-usuario">
                            <a href="minha_conta.php">
                                <i class="fas fa-user-circle"></i> Minha Conta
                            </a>
                            <ul class="submenu">
                                <?php if ($isAdmin): ?>
                                    <li><a href="admin/"><i class="fas fa-cog"></i> Administração</a></li>
                                <?php endif; ?>
                                <li><a href="meus_pedidos.php"><i class="fas fa-box-open"></i> Meus Pedidos</a></li>
                                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="cadastro.php">Cadastre-se</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="mensagem-flutuante <?= isset($_SESSION['mensagem_tipo']) ? $_SESSION['mensagem_tipo'] : 'sucesso' ?>">
                <?= $_SESSION['mensagem'] ?>
                <span class="fechar-mensagem">&times;</span>
            </div>
            <?php unset($_SESSION['mensagem']); unset($_SESSION['mensagem_tipo']); ?>
        <?php endif; ?>