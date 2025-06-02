<?php
session_start();

// Redirect to login if user is not authenticated
if (!isset($_SESSION['usuario'])) {
    // Adjusted path to login.php from public/usuario/
    header("Location: ../../includes/auth/login.php");
    exit();
}

// Adjusted path to conexao.php from public/usuario/
require '../../config/conexao.php';

$usuario_id = $_SESSION['usuario']['id'];
$erro = '';
$sucesso = '';

// --- Obter dados atuais do usuário ---
try {
    $stmt = $pdo->prepare("SELECT nome, email, telefone, cpf FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        // Fallback if user somehow doesn't exist in DB but session does
        $erro = 'Usuário não encontrado.';
        // Potentially destroy session and redirect to login
        // session_destroy();
        // header("Location: ../../includes/auth/login.php");
        // exit();
        $usuario = ['nome' => '', 'email' => '', 'telefone' => '', 'cpf' => '']; // Provide defaults to avoid errors
    }

} catch (PDOException $e) {
    $erro = 'Erro ao carregar dados do perfil: ' . $e->getMessage();
    $usuario = ['nome' => '', 'email' => '', 'telefone' => '', 'cpf' => '']; // Provide defaults
}


// --- Processar formulário de atualização ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? ''); // Can be empty
    $cpf = trim($_POST['cpf'] ?? ''); // Can be empty
    
    // Validações básicas
    if (empty($nome)) {
        $erro = 'O nome é obrigatório.';
    } elseif (empty($email)) {
        $erro = 'O e-mail é obrigatório.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } else {
        try {
            // Verificar se o e-mail já existe (exceto para o próprio usuário)
            $stmtCheckEmail = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
            $stmtCheckEmail->execute([$email, $usuario_id]);
            if ($stmtCheckEmail->fetch()) {
                $erro = 'Este e-mail já está em uso por outro usuário.';
            } else {
                // Atualizar dados
                $stmtUpdate = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, telefone = ?, cpf = ? WHERE id = ?");
                if ($stmtUpdate->execute([$nome, $email, $telefone, $cpf, $usuario_id])) {
                    $sucesso = 'Perfil atualizado com sucesso!';
                    
                    // Atualizar dados na sessão para refletir imediatamente (e.g., no header)
                    $_SESSION['usuario']['nome'] = $nome;
                    $_SESSION['usuario']['email'] = $email;

                    // Re-fetch user data to display updated values in the form
                    $stmt = $pdo->prepare("SELECT nome, email, telefone, cpf FROM usuarios WHERE id = ?");
                    $stmt->execute([$usuario_id]);
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                } else {
                    $erro = 'Erro ao atualizar perfil. Tente novamente.';
                }
            }
        } catch (PDOException $e) {
            // Log detailed error: error_log('PDOException: ' . $e->getMessage());
            $erro = 'Erro no banco de dados ao atualizar perfil. Detalhes: ' . $e->getMessage();
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
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php 
    // Adjusted path to header.php from public/usuario/
    include '../../includes/header.php'; 
    ?>
    
    <div class="container edit-profile-container">
        <h1>Editar Perfil</h1>
        
        <?php if ($sucesso): ?>
            <div class="message success"><?php echo htmlspecialchars($sucesso); ?></div>
        <?php endif; ?>
        
        <?php if ($erro): ?>
            <div class="message error"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="editar-perfil.php"> {/* Action can be empty to submit to self */}
            <div class="form-group">
                <label for="nome">Nome Completo</label>
                <input type="text" id="nome" name="nome" 
                       value="<?php echo htmlspecialchars($usuario['nome'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="telefone">Telefone</label>
                <input type="text" id="telefone" name="telefone" 
                       value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="cpf">CPF</label>
                <input type="text" id="cpf" name="cpf" 
                       value="<?php echo htmlspecialchars($usuario['cpf'] ?? ''); ?>">
            </div>
            
            <div class="form-actions">
                {/* perfil.php is in the same directory 'public/usuario/' */}
                <a href="perfil.php" class="btn btn-back">Voltar</a>
                <button type="submit" class="btn">Salvar Alterações</button>
            </div>
        </form>
    </div>
    
    <?php 
    // Adjusted path to footer.php from public/usuario/
    include '../../includes/footer.php'; 
    ?>
</body>
</html>