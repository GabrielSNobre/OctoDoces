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

// Processar formulário de endereço
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cep = trim($_POST['cep']);
    $logradouro = trim($_POST['logradouro']);
    $numero = trim($_POST['numero']);
    $complemento = trim($_POST['complemento'] ?? '');
    $bairro = trim($_POST['bairro']);
    $cidade = trim($_POST['cidade']);
    $estado = trim($_POST['estado']);
    $apelido = trim($_POST['apelido'] ?? '');
    $principal = isset($_POST['principal']) ? 1 : 0;
    
    // Validações básicas
    if (empty($logradouro) || empty($numero) || empty($bairro) || empty($cidade) || empty($estado) || empty($cep)) {
        $erro = 'Preencha todos os campos obrigatórios';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Se marcou como principal, desmarca outros
            if ($principal) {
                $stmt = $pdo->prepare("UPDATE usuario_enderecos ue 
                                      JOIN enderecos e ON ue.endereco_id = e.id
                                      SET e.principal = FALSE 
                                      WHERE ue.usuario_id = ?");
                $stmt->execute([$usuario_id]);
            }
            
            // Inserir endereço
            $stmt = $pdo->prepare("INSERT INTO enderecos 
                                  (cep, logradouro, numero, complemento, bairro, cidade, estado, principal) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$cep, $logradouro, $numero, $complemento, $bairro, $cidade, $estado, $principal]);
            $endereco_id = $pdo->lastInsertId();
            
            // Relacionar com usuário
            $stmt = $pdo->prepare("INSERT INTO usuario_enderecos (usuario_id, endereco_id, apelido) 
                                  VALUES (?, ?, ?)");
            $stmt->execute([$usuario_id, $endereco_id, $apelido]);
            
            $pdo->commit();
            $sucesso = 'Endereço cadastrado com sucesso!';
            header("Location: perfil.php?success=endereco");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $erro = 'Erro ao cadastrar endereço: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Endereço - Octo Doces</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/stylepage.css">
    <style>
        :root {
     --primary: #ffffff;
     --secondary: #4ECDC4;
     --accent: #fc0000;
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
     background-color: rgb(255, 255, 255);
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
        
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input {
            width: auto;
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
    
    <div class="container">
        <h1>Adicionar Endereço</h1>
        
        <?php if ($erro): ?>
            <div class="message error"><?php echo $erro; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="apelido">Apelido (Ex: Casa, Trabalho)</label>
                <input type="text" id="apelido" name="apelido" value="<?php echo htmlspecialchars($_POST['apelido'] ?? ''); ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="cep">CEP *</label>
                    <input type="text" id="cep" name="cep" value="<?php echo htmlspecialchars($_POST['cep'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="numero">Número *</label>
                    <input type="text" id="numero" name="numero" value="<?php echo htmlspecialchars($_POST['numero'] ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="logradouro">Logradouro *</label>
                <input type="text" id="logradouro" name="logradouro" value="<?php echo htmlspecialchars($_POST['logradouro'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="complemento">Complemento</label>
                <input type="text" id="complemento" name="complemento" value="<?php echo htmlspecialchars($_POST['complemento'] ?? ''); ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="bairro">Bairro *</label>
                    <input type="text" id="bairro" name="bairro" value="<?php echo htmlspecialchars($_POST['bairro'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="cidade">Cidade *</label>
                    <input type="text" id="cidade" name="cidade" value="<?php echo htmlspecialchars($_POST['cidade'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="estado">Estado *</label>
                    <select id="estado" name="estado" required>
                        <option value="">Selecione</option>
                        <option value="AC" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'AC') ? 'selected' : ''; ?>>Acre</option>
                        <option value="AL" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'AL') ? 'selected' : ''; ?>>Alagoas</option>
                        <option value="AP" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'AP') ? 'selected' : ''; ?>>Amapá</option>
                        <option value="AM" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'AM') ? 'selected' : ''; ?>>Amazonas</option>
                        <option value="BA" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'BA') ? 'selected' : ''; ?>>Bahia</option>
                        <option value="CE" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'CE') ? 'selected' : ''; ?>>Ceará</option>
                        <option value="DF" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'DF') ? 'selected' : ''; ?>>Distrito Federal</option>
                        <option value="ES" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'ES') ? 'selected' : ''; ?>>Espírito Santo</option>
                        <option value="GO" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'GO') ? 'selected' : ''; ?>>Goiás</option>
                        <option value="MA" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'MA') ? 'selected' : ''; ?>>Maranhão</option>
                        <option value="MT" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'MT') ? 'selected' : ''; ?>>Mato Grosso</option>
                        <option value="MS" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'MS') ? 'selected' : ''; ?>>Mato Grosso do Sul</option>
                        <option value="MG" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'MG') ? 'selected' : ''; ?>>Minas Gerais</option>
                        <option value="PA" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'PA') ? 'selected' : ''; ?>>Pará</option>
                        <option value="PB" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'PB') ? 'selected' : ''; ?>>Paraíba</option>
                        <option value="PR" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'PR') ? 'selected' : ''; ?>>Paraná</option>
                        <option value="PE" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'PE') ? 'selected' : ''; ?>>Pernambuco</option>
                        <option value="PI" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'PI') ? 'selected' : ''; ?>>Piauí</option>
                        <option value="RJ" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'RJ') ? 'selected' : ''; ?>>Rio de Janeiro</option>
                        <option value="RN" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'RN') ? 'selected' : ''; ?>>Rio Grande do Norte</option>
                        <option value="RS" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'RS') ? 'selected' : ''; ?>>Rio Grande do Sul</option>
                        <option value="RO" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'RO') ? 'selected' : ''; ?>>Rondônia</option>
                        <option value="RR" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'RR') ? 'selected' : ''; ?>>Roraima</option>
                        <option value="SC" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'SC') ? 'selected' : ''; ?>>Santa Catarina</option>
                        <option value="SP" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'SP') ? 'selected' : ''; ?>>São Paulo</option>
                        <option value="SE" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'SE') ? 'selected' : ''; ?>>Sergipe</option>
                        <option value="TO" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'TO') ? 'selected' : ''; ?>>Tocantins</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group checkbox-group">
                <input type="checkbox" id="principal" name="principal" <?php echo (isset($_POST['principal']) ? 'checked' : '') ?>>
                <label for="principal">Definir como endereço principal</label>
            </div>
            
            <div class="form-group">
                
                <button type="submit" class="btn">Salvar Endereço</button>
                <a href="perfil.php" class="btn btn-back" aling-text="center">Cancelar</a>
            </div>
        </form>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <script>
        // Função para buscar CEP via API
        document.getElementById('cep').addEventListener('blur', function() {
            const cep = this.value.replace(/\D/g, '');
            
            if (cep.length === 8) {
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.erro) {
                            document.getElementById('logradouro').value = data.logradouro || '';
                            document.getElementById('bairro').value = data.bairro || '';
                            document.getElementById('cidade').value = data.localidade || '';
                            document.getElementById('estado').value = data.uf || '';
                            
                            // Foca no campo número após preencher
                            document.getElementById('numero').focus();
                        }
                    })
                    .catch(error => console.error('Erro ao buscar CEP:', error));
            }
        });
    </script>
</body>
</html>