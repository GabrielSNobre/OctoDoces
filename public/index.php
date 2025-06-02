<?php
// Inicia a sessão (necessário para verificar login)
session_start();

// Define o título da página
$pageTitle = "Octo Doces - Sabores que encantam";

// Inclui o cabeçalho
require_once __DIR__ . '/../includes/header.php';
?>

<section class="hero" id="home">
    <div class="container">
        <div class="hero-content">
            <h1>Doces que fazem seu dia mais feliz</h1>
            <p>Na Octo Doces, cada doce é feito com ingredientes de alta qualidade e muito carinho. Experimente nossos sabores exclusivos e delicie-se!</p>
            <a href="produtos/lista.php" class="btn">Conheça nossos produtos</a>
        </div>
    </div>
</section>

<!-- Outras seções do seu conteúdo principal aqui -->

<?php
// Inclui o rodapé
require_once __DIR__ . '/../includes/footer.php';
?>