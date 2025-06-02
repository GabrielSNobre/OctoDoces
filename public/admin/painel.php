<?php
session_start();
require 'conexao.php';
// Verificar se o usuário é administrador
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo_usuario_id'] != 2) {
    header("Location: login.php");
    exit();
}

if (($_SESSION['usuario']['tipo_usuario_id'] ?? 0) != 2) {
    header("Location: perfil.php");
    exit();
}




// Obter todos os pedidos
$pedidos = $pdo->query("
    SELECT p.*, u.nome as cliente_nome, u.email as cliente_email 
    FROM pedidos p
    JOIN usuarios u ON p.usuario_id = u.id
    ORDER BY p.data_pedido DESC
")->fetchAll();

// Processar atualização de status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_status'])) {
    $pedido_id = $_POST['pedido_id'];
    $novo_status = $_POST['novo_status'];
    $mensagem = $_POST['mensagem'];
    
    try {
        $pdo->beginTransaction();
        
        // Atualizar status do pedido
        $stmt = $pdo->prepare("UPDATE pedidos SET status = ? WHERE id = ?");
        $stmt->execute([$novo_status, $pedido_id]);
        
        // Registrar no histórico
        $stmt = $pdo->prepare("INSERT INTO pedido_status (pedido_id, status, observacao) VALUES (?, ?, ?)");
        $stmt->execute([$pedido_id, $novo_status, $mensagem]);
        
        // Obter informações do pedido para enviar e-mail
        $stmt = $pdo->prepare("SELECT u.email, p.codigo FROM pedidos p JOIN usuarios u ON p.usuario_id = u.id WHERE p.id = ?");
        $stmt->execute([$pedido_id]);
        $pedido_info = $stmt->fetch();
        
        // Enviar e-mail (simulado - na prática use PHPMailer ou similar)
        $to = $pedido_info['email'];
        $subject = "Atualização do seu pedido #" . $pedido_info['codigo'];
        $message = "Seu pedido foi atualizado para o status: " . $novo_status . "\n\n";
        $message .= "Mensagem do administrador:\n" . $mensagem . "\n\n";
        $message .= "Acompanhe seu pedido em nosso site.";
        $headers = "From: contato@octodoces.com";
        
        // Descomente para enviar realmente
        // mail($to, $subject, $message, $headers);
        
        $pdo->commit();
        $_SESSION['mensagem'] = "Status atualizado e cliente notificado!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['erro'] = "Erro ao atualizar status: " . $e->getMessage();
    }
    
    header("Location: admin.php");
    exit();
}

// Processar cadastro de novo administrador
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar_admin'])) {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    
    if ($senha !== $confirmar_senha) {
        $_SESSION['erro'] = "As senhas não coincidem!";
        header("Location: admin.php");
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo_usuario_id) VALUES (?, ?, ?, 2)");
        $stmt->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT)]);
        
        $_SESSION['mensagem'] = "Novo administrador cadastrado com sucesso!";
    } catch (PDOException $e) {
        $_SESSION['erro'] = "Erro ao cadastrar administrador: " . $e->getMessage();
    }
    
    header("Location: admin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Octo Doces</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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
            background-color: #f5f5f5;
            color: var(--dark);
        }
        
        .admin-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--secondary);
        }
        
        .admin-title {
            color: var(--secondary);
            font-size: 2rem;
        }
        
        .btn-logout {
            background-color: var(--primary);
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
        }
        
        .admin-sections {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
        }
        
        .admin-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .section-title {
            font-size: 1.5rem;
            color: var(--secondary);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: var(--secondary);
            color: white;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .status-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        select, textarea {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn-update {
            background-color: var(--accent);
            color: var(--dark);
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-update:hover {
            background-color: #e6d252;
        }
        
        .admin-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn-submit {
            background-color: var(--secondary);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            grid-column: span 2;
        }
        
        .btn-submit:hover {
            background-color: #3dbeb6;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
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
        
        .status-pendente {
            color: #e67e22;
            font-weight: 600;
        }
        
        .status-processando {
            color: #3498db;
            font-weight: 600;
        }
        
        .status-enviado {
            color: #2ecc71;
            font-weight: 600;
        }
        
        .status-entregue {
            color: #16a085;
            font-weight: 600;
        }
    </style>
    <link rel="stylesheet" href="../styles/stylepage.css">
</head>
<body>
    <header>
    <div class="container header-content">
        <div class="container-fluid col-11 m-auto">
            <a href="index.html"><img src="../img/Logo.png" style="height: 80px; width: 80px;"></a>
        </div>
        <nav>
            <ul>
                <li><a href="../index.html">Início</a></li>
                <li><a href="produtos.php">Produtos</a></li>
                <li><a href="../index.html#about">Sobre</a></li>
                <li><a href="../index.html#contact">Contato</a></li>
                <li><a href="carrinho.php">Carrinho</a></li>
               <li><a href="../index.html">Início</a></li>
                <?php if (isset($_SESSION['usuario'])): ?>
                <!-- Usuário logado -->
                <li>
                <a href="perfil.php" class="btn-login <?php echo ($_SESSION['usuario']['tipo_usuario_id'] == 2) ? 'admin' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <?php echo htmlspecialchars($_SESSION['usuario']['nome']); ?>
                    <?php if ($_SESSION['usuario']['tipo_usuario_id'] == 2): ?>
                        <span class="admin-badge">ADMIN</span>
                    <?php endif; ?>
                </a>
                </li>
                <?php else: ?>
                <!-- Usuário não logado -->
                <li><a href="login.php" class="btn-login">Login</a></li>
             <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">Painel Administrativo</h1>
            <a href="../logout.php" class="btn-logout">Sair</a>
        </div>
        
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="message success"><?= $_SESSION['mensagem'] ?></div>
            <?php unset($_SESSION['mensagem']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['erro'])): ?>
            <div class="message error"><?= $_SESSION['erro'] ?></div>
            <?php unset($_SESSION['erro']); ?>
        <?php endif; ?>
        
        <div class="admin-sections">
            <div class="admin-section">
                <h2 class="section-title">Pedidos</h2>
                
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Cliente</th>
                            <th>Data</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td><?= htmlspecialchars($pedido['codigo']) ?></td>
                                <td><?= htmlspecialchars($pedido['cliente_nome']) ?><br><?= htmlspecialchars($pedido['cliente_email']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></td>
                                <td>R$ <?= number_format($pedido['total'], 2, ',', '.') ?></td>
                                <td class="status-<?= $pedido['status'] ?>"><?= ucfirst($pedido['status']) ?></td>
                                <td>
                                    <form method="POST" class="status-form">
                                        <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
                                        <select name="novo_status" required>
                                            <option value="pendente" <?= $pedido['status'] == 'pendente' ? 'selected' : '' ?>>Pendente</option>
                                            <option value="processando" <?= $pedido['status'] == 'processando' ? 'selected' : '' ?>>Processando</option>
                                            <option value="enviado" <?= $pedido['status'] == 'enviado' ? 'selected' : '' ?>>Enviado</option>
                                            <option value="entregue" <?= $pedido['status'] == 'entregue' ? 'selected' : '' ?>>Entregue</option>
                                        </select>
                                        <textarea name="mensagem" placeholder="Mensagem para o cliente" rows="1" required></textarea>
                                        <button type="submit" name="atualizar_status" class="btn-update">Atualizar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="admin-section">
                <h2 class="section-title">Adicionar Administrador</h2>
                
                <form method="POST" class="admin-form">
                    <div class="form-group">
                        <label for="nome">Nome Completo</label>
                        <input type="text" id="nome" name="nome" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="senha">Senha</label>
                        <input type="password" id="senha" name="senha" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmar_senha">Confirmar Senha</label>
                        <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="6">
                    </div>
                    
                    <button type="submit" name="adicionar_admin" class="btn-submit">Cadastrar Administrador</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>