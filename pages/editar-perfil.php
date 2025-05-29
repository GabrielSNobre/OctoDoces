<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

require 'conexao.php';

$usuario_id = $_SESSION['usuario']['id'];
$erro = '';
$sucesso = '';

// Obter dados atuais do usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();

// Processar formulário de atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $telefone = trim($_POST['telefone']);
    $cpf = trim($_POST['cpf']);
    
    // Validações básicas
    if (empty($nome)) {
        $erro = 'O nome é obrigatório';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido';
    } else {
        try {
            // Verificar se o e-mail já existe (exceto para o próprio usuário)
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
            $stmt->execute([$email, $usuario_id]);
            if ($stmt->fetch()) {
                $erro = 'Este e-mail já está em uso por outro usuário';
            } else {
                // Atualizar dados
                $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, telefone = ?, cpf = ? WHERE id = ?");
                $stmt->execute([$nome, $email, $telefone, $cpf, $usuario_id]);
                $sucesso = 'Perfil atualizado com sucesso!';
                
                // Atualizar dados na sessão
                $_SESSION['usuario']['nome'] = $nome;
                $_SESSION['usuario']['email'] = $email;
            }
        } catch (PDOException $e) {
            $erro = 'Erro ao atualizar perfil: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - Octo Doces</title>
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
        
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: var(--secondary);
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
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
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--secondary);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background-color: #3dbeb6;
            transform: translateY(-2px);
        }
        
        .btn-back {
            background-color: #6c757d;
            margin-right: 10px;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h1>Editar Perfil</h1>
        
        <?php if ($sucesso): ?>
            <div class="message success"><?php echo $sucesso; ?></div>
        <?php endif; ?>
        
        <?php if ($erro): ?>
            <div class="message error"><?php echo $erro; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="nome">Nome Completo</label>
                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="telefone">Telefone</label>
                <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="cpf">CPF</label>
                <input type="text" id="cpf" name="cpf" value="<?php echo htmlspecialchars($usuario['cpf'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <a href="perfil.php" class="btn btn-back">Voltar</a>
                <button type="submit" class="btn">Salvar Alterações</button>
            </div>
        </form>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>