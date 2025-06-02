<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: /includes/auth/login.php");
    exit();
}

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../config/functions.php';

// Obter informações do usuário
$usuario_id = $_SESSION['usuario']['id'];
$stmt = $pdo->prepare("SELECT u.*, t.nome as tipo_usuario 
                      FROM usuarios u 
                      JOIN tipos_usuario t ON u.tipo_usuario_id = t.id 
                      WHERE u.id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();

// Obter endereços do usuário
$enderecos = $pdo->prepare("SELECT e.* 
                           FROM enderecos e
                           JOIN usuario_enderecos ue ON e.id = ue.endereco_id
                           WHERE ue.usuario_id = ?
                           ORDER BY e.principal DESC");
$enderecos->execute([$usuario_id]);
$enderecos = $enderecos->fetchAll();

// Obter histórico de pedidos do usuário
$pedidos = $pdo->prepare("SELECT ped.id, ped.codigo, ped.total, ped.status, ped.data_pedido,
                         GROUP_CONCAT(pr.nome SEPARATOR ', ') as produtos_nomes
                         FROM pedidos ped
                         JOIN pedido_itens pit ON ped.id = pit.pedido_id
                         JOIN produtos pr ON pit.produto_id = pr.id
                         WHERE ped.usuario_id = ?
                         GROUP BY ped.id
                         ORDER BY ped.data_pedido DESC");
$pedidos->execute([$usuario_id]);
$pedidos = $pedidos->fetchAll();

// Verificar se é admin
$isAdmin = ($_SESSION['usuario']['tipo_usuario_id'] ?? 0) == 2;

// Definir título da página
$pageTitle = "Perfil - " . htmlspecialchars($usuario['nome']);

// Incluir cabeçalho
include __DIR__ . '/../../includes/header.php';
?>

<div class="profile-container">
    <div class="profile-header">
        <h1 class="profile-title">Olá, <?php echo htmlspecialchars($usuario['nome']); ?></h1>
        <div>
            <a href="editar-perfil.php" class="btn btn-primary">Editar Perfil</a>
            <a href="/includes/auth/logout.php" class="btn btn-logout">Sair</a>
        </div>
        
        <?php if ($isAdmin): ?>
            <div class="admin-panel-link">
                <a href="/public/admin/painel.php" class="btn-admin">
                    <i class="fas fa-cog"></i> Painel Administrativo
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php showFlashMessages(); ?>
    
    <div class="profile-info">
        <div class="info-card">
            <h3>Informações Pessoais</h3>
            <?php displayUserInfo($usuario); ?>
        </div>
        
        <div class="info-card">
            <h3>Meus Endereços</h3>
            <?php displayUserAddresses($enderecos); ?>
            <a href="enderecos/adicionar.php" class="btn btn-primary" style="margin-top: 15px;">Adicionar Endereço</a>
        </div>
    </div>
    
    <div class="tab-container">
        <div class="tab-buttons">
            <button class="tab-btn active" onclick="openTab('pedidos')">Meus Pedidos</button>
            <button class="tab-btn" onclick="openTab('favoritos')">Favoritos</button>
        </div>
        
        <div id="pedidos" class="tab-content active">
            <h2 class="section-title">Histórico de Pedidos</h2>
            <?php displayUserOrders($pedidos); ?>
        </div>
        
        <div id="favoritos" class="tab-content">
            <h2 class="section-title">Meus Favoritos</h2>
            <?php displayUserFavorites($usuario_id); ?>
        </div>
    </div>
</div>

<?php
// Incluir rodapé
include __DIR__ . '/../../includes/footer.php';

?>