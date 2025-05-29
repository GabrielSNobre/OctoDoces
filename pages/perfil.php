<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

require 'conexao.php';

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

// Obter lista de produtos disponíveis
$produtos = $pdo->query("SELECT p.*, c.nome as categoria_nome 
                        FROM produtos p
                        LEFT JOIN categorias c ON p.categoria_id = c.id
                        WHERE p.ativo = TRUE")->fetchAll();

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

// Obter carrinho do usuário
$carrinho = $pdo->prepare("SELECT ci.*, p.nome, p.imagem, p.preco as preco_original
                          FROM carrinhos c
                          JOIN carrinho_itens ci ON c.id = ci.carrinho_id
                          JOIN produtos p ON ci.produto_id = p.id
                          WHERE c.usuario_id = ?");
$carrinho->execute([$usuario_id]);
$carrinho_itens = $carrinho->fetchAll();

// Calcular total do carrinho
$total_carrinho = 0;
foreach ($carrinho_itens as $item) {
    $total_carrinho += $item['preco_unitario'] * $item['quantidade'];
}
$isAdmin = ($_SESSION['usuario']['tipo_usuario_id'] ?? 0) == 2;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - Octo Doces</title>
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
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.6;
        }

        .profile-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .profile-title {
            font-size: 2rem;
            color: var(--secondary);
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-block;
            text-align: center;
        }
        
        .btn-logout {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary {
            background-color: var(--secondary);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .profile-info {
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        @media (max-width: 768px) {
            .profile-info {
                grid-template-columns: 1fr;
            }
        }
        
        .info-card {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .info-item {
            margin-bottom: 15px;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark);
            display: block;
            margin-bottom: 5px;
        }
        
        .info-value {
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 5px;
            width: 100%;
        }
        
        .section-title {
            font-size: 1.5rem;
            color: var(--secondary);
            margin: 30px 0 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid var(--accent);
        }
        
        .address-list {
            display: grid;
            gap: 15px;
        }
        
        .address-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            position: relative;
        }
        
        .address-card.principal::after {
            content: 'Principal';
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--accent);
            color: var(--dark);
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .address-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .btn-small {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .orders-table th, .orders-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .orders-table th {
            background-color: var(--primary);
            color: white;
        }
        
        .orders-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .status {
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 4px;
        }
        
        .status-pendente {
            background-color: #FFF3CD;
            color: #856404;
        }
        
        .status-processando {
            background-color: #CCE5FF;
            color: #004085;
        }
        
        .status-enviado {
            background-color: #D4EDDA;
            color: #155724;
        }
        
        .status-entregue {
            background-color: #D1ECF1;
            color: #0C5460;
        }
        
        .status-cancelado {
            background-color: #F8D7DA;
            color: #721C24;
        }
        
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .tab-container {
            margin-top: 30px;
        }
        
        .tab-buttons {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .tab-btn {
            padding: 10px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: var(--dark);
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tab-btn.active {
            border-bottom: 3px solid var(--secondary);
            color: var(--secondary);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .cart-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .cart-item-details {
            flex-grow: 1;
        }
        
        .cart-item-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .cart-item-price {
            color: var(--primary);
            font-weight: 700;
        }
        
        .cart-item-quantity {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .cart-total {
            font-size: 1.2rem;
            font-weight: 700;
            text-align: right;
            margin-top: 20px;
        }
        
        .empty-message {
            text-align: center;
            padding: 30px;
            color: #666;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/stylepage.css">
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
                <li><a href="carrinho.php">Carrinho (<?php echo count($carrinho_itens); ?>)</a></li>
                <li><a href="logout.php" class="btn-logout">Sair</a></li>
            </ul>
        </nav>
    </div>
</header>

<div class="profile-container">
    <div class="profile-header">
        <h1 class="profile-title">Olá, <?php echo htmlspecialchars($usuario['nome']); ?></h1>
        <div>
            <a href="editar-perfil.php" class="btn btn-primary">Editar Perfil</a>
            <a href="logout.php" class="btn btn-logout">Sair</a>
        </div>
        <div class="perfil-content">
        <h1>Olá, <?php echo htmlspecialchars($_SESSION['usuario']['nome']); ?></h1>
    
    <!-- Botão do Painel Admin (só aparece para admins) -->
        <?php if ($isAdmin): ?>
            <a href="admin.php" class="btn-admin">
            <i class="fas fa-cog"></i> Painel Administrativo
            </a>
         <?php endif; ?>

    <!-- Restante do conteúdo do perfil -->
</div>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="message success">
            <?php 
                switch($_GET['success']) {
                    case 'endereco':
                        echo "Endereço atualizado com sucesso!";
                        break;
                    case 'pedido':
                        echo "Pedido realizado com sucesso!";
                        break;
                    default:
                        echo "Operação realizada com sucesso!";
                }
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="message error">
            <?php 
                switch($_GET['error']) {
                    case 'endereco':
                        echo "Erro ao atualizar endereço. Tente novamente.";
                        break;
                    case 'pedido':
                        echo "Erro ao processar pedido. Tente novamente.";
                        break;
                    default:
                        echo "Ocorreu um erro. Tente novamente.";
                }
            ?>
        </div>
    <?php endif; ?>
    
    <div class="profile-info">
        <div class="info-card">
            <h3>Informações Pessoais</h3>
            <div class="info-item">
                <span class="info-label">Nome Completo</span>
                <div class="info-value"><?php echo htmlspecialchars($usuario['nome']); ?></div>
            </div>
            
            <div class="info-item">
                <span class="info-label">E-mail</span>
                <div class="info-value"><?php echo htmlspecialchars($usuario['email']); ?></div>
            </div>
            
            <div class="info-item">
                <span class="info-label">CPF</span>
                <div class="info-value"><?php echo htmlspecialchars($usuario['cpf'] ?? 'Não informado'); ?></div>
            </div>
            
            <div class="info-item">
                <span class="info-label">Telefone</span>
                <div class="info-value"><?php echo htmlspecialchars($usuario['telefone'] ?? 'Não informado'); ?></div>
            </div>
            
            <div class="info-item">
                <span class="info-label">Tipo de Usuário</span>
                <div class="info-value"><?php echo ucfirst(htmlspecialchars($usuario['tipo_usuario'])); ?></div>
            </div>
            
            <div class="info-item">
                <span class="info-label">Data de Cadastro</span>
                <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($usuario['data_cadastro'])); ?></div>
            </div>
        </div>
        
        <div class="info-card">
            <h3>Meus Endereços</h3>
            <div class="address-list">
                <?php if (count($enderecos) > 0): ?>
                    <?php foreach ($enderecos as $endereco): ?>
                        <div class="address-card <?php echo $endereco['principal'] ? 'principal' : ''; ?>">
                            <p><strong><?php echo htmlspecialchars($endereco['apelido'] ?? 'Endereço'); ?></strong></p>
                            <p><?php echo htmlspecialchars($endereco['logradouro']); ?>, <?php echo htmlspecialchars($endereco['numero']); ?></p>
                            <p><?php echo htmlspecialchars($endereco['complemento'] ?? ''); ?></p>
                            <p><?php echo htmlspecialchars($endereco['bairro']); ?> - <?php echo htmlspecialchars($endereco['cidade']); ?>/<?php echo htmlspecialchars($endereco['estado']); ?></p>
                            <p>CEP: <?php echo htmlspecialchars($endereco['cep']); ?></p>
                            <div class="address-actions">
                                <a href="editar-endereco.php?id=<?php echo $endereco['id']; ?>" class="btn btn-primary btn-small">Editar</a>
                                <?php if (!$endereco['principal']): ?>
                                    <a href="definir-principal.php?id=<?php echo $endereco['id']; ?>" class="btn btn-small">Tornar Principal</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Você ainda não cadastrou nenhum endereço.</p>
                <?php endif; ?>
                <a href="adicionar-endereco.php" class="btn btn-primary" style="margin-top: 15px;">Adicionar Endereço</a>
            </div>
        </div>
    </div>
    
    <div class="tab-container">
        <div class="tab-buttons">
            <button class="tab-btn active" onclick="openTab('pedidos')">Meus Pedidos</button>
            
        </div>
        
        <div id="pedidos" class="tab-content active">
            <h2 class="section-title">Histórico de Pedidos</h2>
            <?php if (count($pedidos) > 0): ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Produtos</th>
                            <th>Total</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pedido['codigo']); ?></td>
                                <td><?php echo htmlspecialchars($pedido['produtos_nomes']); ?></td>
                                <td>R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?></td>
                                <td>
                                    <span class="status status-<?php echo strtolower($pedido['status']); ?>">
                                        <?php echo ucfirst($pedido['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="detalhes-pedido.php?id=<?php echo $pedido['id']; ?>" class="btn btn-primary btn-small">Detalhes</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="empty-message">Você ainda não fez nenhum pedido.</p>
            <?php endif; ?>
        </div>
        
       
    </div>
</div>

<footer id="contact">
    <div class="container">
        <div class="footer-content">
            <div class="copyright">
                <p>&copy; 2023 Octo Doces. Todos os direitos reservados.</p>
            </div>
        </div>
    </div>
</footer>

<script>
    function openTab(tabName) {
        // Esconde todos os conteúdos das abas
        const tabContents = document.getElementsByClassName('tab-content');
        for (let i = 0; i < tabContents.length; i++) {
            tabContents[i].classList.remove('active');
        }
        
        // Remove a classe 'active' de todos os botões
        const tabButtons = document.getElementsByClassName('tab-btn');
        for (let i = 0; i < tabButtons.length; i++) {
            tabButtons[i].classList.remove('active');
        }
        
        // Mostra o conteúdo da aba selecionada e marca o botão como ativo
        document.getElementById(tabName).classList.add('active');
        event.currentTarget.classList.add('active');
    }
</script>
</body>
</html>