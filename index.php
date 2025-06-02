<?php
// Inicia a sessão
session_start();

// Verifica se o usuário está logado
$usuarioLogado = isset($_SESSION['usuario']);
$isAdmin = $usuarioLogado && isset($_SESSION['usuario']['tipo_usuario_id']) && $_SESSION['usuario']['tipo_usuario_id'] == 2;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Octo Doces - Sabores que encantam</title>
    <link rel="stylesheet" href="styles/style.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    
</head>
<body>
    <header>
        <div class="container header-content"> 
            <div class="container-fluid col-11 m-auto"> 
                <a href="index.php"> <img src="img/Logo.png" style="height: 100px; width: 100px;" alt="Logo Octo Doces"> </a>
            </div>
            <nav>
                <ul>
                    <li><a href="#home">Início</a></li>
                    <li><a href="pages/produtos.php">Produtos</a></li>
                    <li><a href="#about">Sobre</a></li>
                    <li><a href="#contact">Contato</a></li>
                    <li><a href="pages/carrinho.php">Carrinho</a></li>
                    <?php if ($usuarioLogado): ?>
                        <!-- Usuário logado -->
                        <li>
                            <a href="pages/perfil.php" class="btn-login <?= $isAdmin ? 'admin' : '' ?>">
                                <i class="fas fa-user"></i>
                                <?= htmlspecialchars($_SESSION['usuario']['nome']) ?>
                                <?php if ($isAdmin): ?>
                                    <span class="admin-badge">ADMIN</span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php else: ?>
                        <!-- Usuário não logado -->
                        <li><a href="pages/login.php" class="btn-login">Login</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    
    <section class="hero" id="home">
        <div class="container">
            <div class="hero-content">
                <h1>Doces que fazem seu dia mais feliz</h1>
                <p>Na Octo Doces, cada doce é feito com ingredientes de alta qualidade e muito carinho. Experimente nossos sabores exclusivos e delicie-se!</p>
                <a href="pages/produtos.php" class="btn">Conheça nossos produtos</a>
            </div>
        </div>
    </section>
    
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
                <p>&copy; <?= date('Y') ?> Octo Doces. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>
</body>
</html>