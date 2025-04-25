<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

require 'conexao.php';

// Obter informações do usuário
$usuario_id = $_SESSION['usuario']['id'];
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();
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
            max-width: 800px;
            margin: 50px auto;
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
        }
        
        .profile-title {
            font-size: 2rem;
            color: var(--primary);
        }
        
        .btn-logout {
            background-color: var(--primary);
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-logout:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .profile-info {
            margin-bottom: 30px;
        }
        
        .info-item {
            margin-bottom: 15px;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark);
        }
        
        .info-value {
            margin-top: 5px;
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 5px;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <h1 class="profile-title">Bem-vindo, <?php echo htmlspecialchars($usuario['nome']); ?>!</h1>
            <a href="logout.php" class="btn-logout">Sair</a>
        </div>
        
        <div class="profile-info">
            <div class="info-item">
                <div class="info-label">Nome Completo</div>
                <div class="info-value"><?php echo htmlspecialchars($usuario['nome']); ?></div>
            </div>
            
            <div class="info-item">
                <div class="info-label">E-mail</div>
                <div class="info-value"><?php echo htmlspecialchars($usuario['email']); ?></div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Data de Cadastro</div>
                <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($usuario['data_cadastro'])); ?></div>
            </div>
        </div>
        
        <p>Esta é sua área de cliente na Octo Doces. Em breve, você poderá acompanhar seus pedidos e favoritos aqui.</p>
    </div>
</body>
</html>