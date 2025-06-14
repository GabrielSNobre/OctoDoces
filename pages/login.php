<?php
session_start();
require 'conexao.php';

if ($usuario && password_verify($senha, $usuario['senha'])) {
    $_SESSION['usuario'] = [
        'id' => $usuario['id'],
        'nome' => $usuario['nome'],
        'email' => $usuario['email'],
        'tipo_usuario_id' => $usuario['tipo_usuario_id'] // Armazena o tipo de usuário
    ];

    // Redireciona conforme o tipo de usuário
    if ($usuario['tipo_usuario_id'] == 2) {
        $_SESSION['is_admin'] = true; // Sinaliza que é admin
        header("Location: admin.php");
    } else {
        header("Location: perfil.php");
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);
    
    // Verifica se o usuário existe
    $stmt = $pdo->prepare("SELECT id, nome, email, senha, tipo_usuario_id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        // Login bem-sucedido
        $_SESSION['usuario'] = [
            'id' => $usuario['id'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'tipo_usuario_id' => $usuario['tipo_usuario_id']
        ];
        
        if ($usuario['tipo_usuario_id'] == 2) {
            header("Location: admin.php");
        } else {
            header("Location: " . ($_GET['redirect'] ?? 'perfil.php'));
        }
        exit();
    } else {
        $erro = "Email ou senha incorretos!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Octo Doces</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/stylepage.css">
</head>

<body>
<header>
    <div class="container header-content">
    <div class="container-fluid col-11 m-auto"> 
                <a href="../index.html"> <img src="../img/Logo.png" style="height: 100px; width: 100px;"> </a>
            
            </div>
        <nav>
            <ul>
                <li><a href="../index.html">Início</a></li>
                
                <li><a href="login.php">login</a></li>
            </ul>
        </nav>
    </div>
</header>



    <div class="auth-container">
        <h1 class="auth-title">Login na Octo Doces</h1>
        
        <?php if (isset($_GET['sucesso']) && $_GET['sucesso'] == 1): ?>
            <div class="success-message">Cadastro realizado com sucesso! Faça login abaixo.</div>
        <?php endif; ?>
        
        <?php if (isset($erro)): ?>
            <div class="error-message"><?php echo $erro; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            
            <button type="submit" class="btn">Entrar</button>
        </form>
        
        <div class="auth-footer">
            <p>Não tem uma conta? <a color="#4ECDC4" href="cadastro.php" >Cadastre-se</a></p>
        </div>
    </div>
</body>
</html>