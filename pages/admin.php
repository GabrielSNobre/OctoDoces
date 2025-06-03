<?php
session_start();
require 'conexao.php';

// Verificar se o usuário é administrador
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo_usuario_id'] != 2) {
    header("Location: login.php");
    exit();
}

// Determinar qual aba está ativa
$aba_ativa = isset($_GET['aba']) ? $_GET['aba'] : 'pedidos';

// Variáveis para edição
$editar_admin = null;
$editar_produto = null;

// Verificar se está editando um item
if (isset($_GET['editar'])) {
    $tipo = $_GET['tipo'];
    $id = $_GET['id'];
    
    if ($tipo == 'admin' && $aba_ativa == 'administradores') {
        $editar_admin = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? AND tipo_usuario_id = 2");
        $editar_admin->execute([$id]);
        $editar_admin = $editar_admin->fetch();
    } elseif ($tipo == 'produto' && $aba_ativa == 'produtos') {
        $editar_produto = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
        $editar_produto->execute([$id]);
        $editar_produto = $editar_produto->fetch();
    }
}

// Consultas SQL para cada aba
switch ($aba_ativa) {
    case 'administradores':
        $administradores = $pdo->query("SELECT id, nome, email FROM usuarios WHERE tipo_usuario_id = 2 ORDER BY nome")->fetchAll();
        break;
        
    case 'produtos':
        $produtos = $pdo->query("
            SELECT p.*, c.nome as categoria_nome 
            FROM produtos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            ORDER BY p.nome
        ")->fetchAll();
        $categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome")->fetchAll();
        break;
        
    case 'pedidos':
    default:
        $pedidos = $pdo->query("
            SELECT p.*, u.nome as cliente_nome, u.email as cliente_email 
            FROM pedidos p
            JOIN usuarios u ON p.usuario_id = u.id
            ORDER BY p.data_pedido DESC
        ")->fetchAll();
        break;
}

// Processar formulários
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['atualizar_admin'])) {
        // Processar atualização de administrador
        $id = $_POST['id'];
        $nome = trim($_POST['nome']);
        $email = trim($_POST['email']);
        $senha = $_POST['senha'];
        
        try {
            if (!empty($senha)) {
                $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, senha = ? WHERE id = ?");
                $stmt->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT), $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ? WHERE id = ?");
                $stmt->execute([$nome, $email, $id]);
            }
            
            $_SESSION['mensagem'] = "Administrador atualizado com sucesso!";
        } catch (PDOException $e) {
            $_SESSION['erro'] = "Erro ao atualizar administrador: " . $e->getMessage();
        }
        
        header("Location: admin.php?aba=administradores");
        exit();
        
    } elseif (isset($_POST['atualizar_produto'])) {
        // Processar atualização de produto
        $id = $_POST['id'];
        $nome = trim($_POST['nome']);
        $descricao = trim($_POST['descricao']);
        $preco = str_replace(['R$', '.', ','], ['', '', '.'], $_POST['preco']);
        $preco_promocional = !empty($_POST['preco_promocional']) ? 
            str_replace(['R$', '.', ','], ['', '', '.'], $_POST['preco_promocional']) : null;
        $categoria_id = $_POST['categoria_id'];
        $estoque = $_POST['estoque'];
        $imagem = trim($_POST['imagem']);
        
        try {
            $stmt = $pdo->prepare("UPDATE produtos SET 
                nome = ?, descricao = ?, preco = ?, preco_promocional = ?, 
                categoria_id = ?, estoque = ?, imagem = ?
                WHERE id = ?");
            $stmt->execute([
                $nome, $descricao, $preco, $preco_promocional,
                $categoria_id, $estoque, $imagem, $id
            ]);
            
            $_SESSION['mensagem'] = "Produto atualizado com sucesso!";
        } catch (PDOException $e) {
            $_SESSION['erro'] = "Erro ao atualizar produto: " . $e->getMessage();
        }
        
        header("Location: admin.php?aba=produtos");
        exit();
        
    } elseif (isset($_POST['adicionar_admin'])) {
        $nome = trim($_POST['nome']);
        $email = trim($_POST['email']);
        $senha = $_POST['senha'];
        $confirmar_senha = $_POST['confirmar_senha'];
        
        if ($senha !== $confirmar_senha) {
            $_SESSION['erro'] = "As senhas não coincidem!";
            header("Location: admin.php?aba=administradores");
            exit();
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo_usuario_id) VALUES (?, ?, ?, 2)");
            $stmt->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT)]);
            
            $_SESSION['mensagem'] = "Novo administrador cadastrado com sucesso!";
        } catch (PDOException $e) {
            $_SESSION['erro'] = "Erro ao cadastrar administrador: " . $e->getMessage();
        }
        
        header("Location: admin.php?aba=administradores");
        exit();
    } elseif (isset($_POST['adicionar_produto'])) {
        $nome = trim($_POST['nome']);
        $descricao = trim($_POST['descricao']);
        $preco = str_replace(['R$', '.', ','], ['', '', '.'], $_POST['preco']);
        $preco_promocional = !empty($_POST['preco_promocional']) ? 
            str_replace(['R$', '.', ','], ['', '', '.'], $_POST['preco_promocional']) : null;
        $categoria_id = $_POST['categoria_id'];
        $estoque = $_POST['estoque'];
        $imagem = trim($_POST['imagem']);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO produtos 
                (nome, descricao, preco, preco_promocional, categoria_id, estoque, imagem, ativo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([
                $nome, $descricao, $preco, $preco_promocional,
                $categoria_id, $estoque, $imagem
            ]);
            
            $_SESSION['mensagem'] = "Produto cadastrado com sucesso!";
        } catch (PDOException $e) {
            $_SESSION['erro'] = "Erro ao cadastrar produto: " . $e->getMessage();
        }
        
        header("Location: admin.php?aba=produtos");
        exit();
    }
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
    <link rel="stylesheet" href="../styles/stylepage.css">
    <style>
        :root {
            --primary: #FF6B6B;
            --secondary: #4ECDC4;
            --accent: #FFE66D;
            --dark: #292F36;
            --light: #F7FFF7;
            --gray: #f5f5f5;
            --gray-dark: #e9ecef;
            --white: #ffffff;
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
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--secondary);
        }
        
        .admin-title {
            color: var(--secondary);
            font-size: 2rem;
        }
        
        /* Abas de navegação */
        .admin-tabs {
            display: flex;
            border-bottom: 1px solid var(--gray-dark);
            margin-bottom: 20px;
        }
        
        .admin-tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            color: var(--dark);
            text-decoration: none;
        }
        
        .admin-tab:hover {
            background-color: var(--gray);
        }
        
        .admin-tab.ativa {
            border-bottom-color: var(--secondary);
            font-weight: 600;
        }
        
        /* Conteúdo das abas */
        .admin-tab-content {
            display: none;
        }
        
        .admin-tab-content.ativa {
            display: block;
        }
        
        /* Listagem de itens */
        .lista-itens {
            list-style: none;
            padding: 0;
        }
        
        .item-lista {
            padding: 15px;
            border-bottom: 1px solid var(--gray-dark);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.3s;
        }
        
        .item-lista:hover {
            background-color: var(--gray);
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-info h3 {
            margin: 0 0 5px 0;
            color: var(--dark);
        }
        
        .item-info p {
            margin: 5px 0;
            color: #555;
        }
        
        .item-acoes {
            display: flex;
            gap: 10px;
        }
        
        /* Formulários */
        .formulario-edicao {
            background-color: var(--gray);
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
            border-left: 4px solid var(--secondary);
        }
        
        .formulario-edicao h3 {
            margin-top: 0;
            color: var(--secondary);
            font-size: 1.3rem;
        }
        
        .admin-form {
            display: grid;
            gap: 15px;
        }
        
        .form-group {
            margin-bottom: 10px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--gray-dark);
            border-radius: 4px;
            font-family: 'Poppins', sans-serif;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        /* Botões */
        .btn {
            padding: 10px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background-color: var(--secondary);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background-color: #3dbeb6;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }
        
        .btn-cancelar {
            background-color: var(--gray-dark);
            color: var(--dark);
        }
        
        .btn-cancelar:hover {
            background-color: #d1d1d1;
        }
        
        /* Status */
        .status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pendente { background-color: #fff3cd; color: #856404; }
        .status-processando { background-color: #cce5ff; color: #004085; }
        .status-enviado { background-color: #d4edda; color: #155724; }
        .status-entregue { background-color: #d1ecf1; color: #0c5460; }
        
        /* Mensagens */
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
        
        /* Preview de imagem */
        .preview-imagem {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            border-radius: 4px;
            border: 1px solid var(--gray-dark);
            display: <?= isset($editar_produto) && !empty($editar_produto['imagem']) ? 'block' : 'none' ?>;
        }
        
        /* Responsivo */
        @media (max-width: 768px) {
            .admin-tabs {
                flex-direction: column;
            }
            
            .admin-tab {
                border-bottom: none;
                border-left: 3px solid transparent;
            }
            
            .admin-tab.ativa {
                border-bottom: none;
                border-left-color: var(--secondary);
            }
            
            .item-lista {
                flex-direction: column;
                gap: 10px;
            }
            
            .item-acoes {
                align-self: flex-end;
            }
            
            .admin-form {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-content">
            <div class="container-fluid col-11 m-auto">
                <a href="../index.php"><img src="../img/Logo.png" style="height: 80px; width: 80px;" alt="Logo Octo Doces"></a>
            </div>
            <nav>
                <ul>
                    <li><a href="../index.php">Início</a></li>
                    <li><a href="produtos.php">Produtos</a></li>
                    <li><a href="../index.php#about">Sobre</a></li>
                    <li><a href="../index.php#contact">Contato</a></li>
                    <li><a href="carrinho.php">Carrinho</a></li>
                    <?php if (isset($_SESSION['usuario'])): ?>
                        <li>
                            <a href="perfil.php" class="btn-login <?= ($_SESSION['usuario']['tipo_usuario_id'] == 2) ? 'admin' : '' ?>">
                                <i class="fas fa-user"></i>
                                <?= htmlspecialchars($_SESSION['usuario']['nome']) ?>
                                <?php if ($_SESSION['usuario']['tipo_usuario_id'] == 2): ?>
                                    <span class="admin-badge">ADMIN</span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php else: ?>
                        <li><a href="login.php" class="btn-login">Login</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">Painel Administrativo</h1>
            <a href="logout.php" class="btn btn-primary">Sair</a>
        </div>
        
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="message success"><?= $_SESSION['mensagem'] ?></div>
            <?php unset($_SESSION['mensagem']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['erro'])): ?>
            <div class="message error"><?= $_SESSION['erro'] ?></div>
            <?php unset($_SESSION['erro']); ?>
        <?php endif; ?>
        
        <!-- Abas de navegação -->
        <div class="admin-tabs">
            <a href="?aba=pedidos" class="admin-tab <?= $aba_ativa == 'pedidos' ? 'ativa' : '' ?>">
                <i class="fas fa-shopping-bag"></i> Pedidos
            </a>
            <a href="?aba=administradores" class="admin-tab <?= $aba_ativa == 'administradores' ? 'ativa' : '' ?>">
                <i class="fas fa-users-cog"></i> Administradores
            </a>
            <a href="?aba=produtos" class="admin-tab <?= $aba_ativa == 'produtos' ? 'ativa' : '' ?>">
                <i class="fas fa-box-open"></i> Produtos
            </a>
        </div>
        
        <!-- Conteúdo da aba Pedidos -->
        <div class="admin-tab-content <?= $aba_ativa == 'pedidos' ? 'ativa' : '' ?>">
            <h2 class="section-title">Lista de Pedidos</h2>
            
            <?php if (empty($pedidos)): ?>
                <p>Nenhum pedido encontrado.</p>
            <?php else: ?>
                <ul class="lista-itens">
                    <?php foreach ($pedidos as $pedido): ?>
                        <li class="item-lista">
                            <div class="item-info">
                                <h3>Pedido #<?= htmlspecialchars($pedido['codigo']) ?></h3>
                                <p><strong>Cliente:</strong> <?= htmlspecialchars($pedido['cliente_nome']) ?> (<?= htmlspecialchars($pedido['cliente_email']) ?>)</p>
                                <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></p>
                                <p><strong>Total:</strong> R$ <?= number_format($pedido['total'], 2, ',', '.') ?></p>
                                <span class="status status-<?= $pedido['status'] ?>"><?= ucfirst($pedido['status']) ?></span>
                            </div>
                            <div class="item-acoes">
                                <a href="detalhes-pedido.php?id=<?= $pedido['id'] ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> Detalhes
                                </a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <!-- Conteúdo da aba Administradores -->
        <div class="admin-tab-content <?= $aba_ativa == 'administradores' ? 'ativa' : '' ?>">
            <h2 class="section-title">Administradores do Sistema</h2>
            
            <?php if (empty($administradores)): ?>
                <p>Nenhum administrador cadastrado.</p>
            <?php else: ?>
                <ul class="lista-itens">
                    <?php foreach ($administradores as $admin): ?>
                        <li class="item-lista">
                            <div class="item-info">
                                <h3><?= htmlspecialchars($admin['nome']) ?></h3>
                                <p><?= htmlspecialchars($admin['email']) ?></p>
                            </div>
                            <div class="item-acoes">
                                <a href="?aba=administradores&editar=1&tipo=admin&id=<?= $admin['id'] ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <!-- Formulário de Edição/Adição de Admin -->
            <div class="formulario-edicao">
                <h3><?= isset($editar_admin) ? 'Editar Administrador' : 'Adicionar Novo Administrador' ?></h3>
                
                <form method="POST" class="admin-form">
                    <?php if (isset($editar_admin)): ?>
                        <input type="hidden" name="id" value="<?= $editar_admin['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="nome">Nome Completo</label>
                        <input type="text" id="nome" name="nome" required 
                               value="<?= isset($editar_admin) ? htmlspecialchars($editar_admin['nome']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" required
                               value="<?= isset($editar_admin) ? htmlspecialchars($editar_admin['email']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="senha"><?= isset($editar_admin) ? 'Nova ' : '' ?>Senha</label>
                        <input type="password" id="senha" name="senha" <?= !isset($editar_admin) ? 'required' : '' ?> minlength="6">
                        <?php if (isset($editar_admin)): ?>
                            <small class="text-muted">Deixe em branco para manter a senha atual</small>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!isset($editar_admin)): ?>
                        <div class="form-group">
                            <label for="confirmar_senha">Confirmar Senha</label>
                            <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="6">
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-actions">
                        <?php if (isset($editar_admin)): ?>
                            <button type="submit" name="atualizar_admin" class="btn btn-primary">
                                <i class="fas fa-save"></i> Atualizar
                            </button>
                            <a href="?aba=administradores" class="btn btn-cancelar">Cancelar</a>
                        <?php else: ?>
                            <button type="submit" name="adicionar_admin" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> Cadastrar
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Conteúdo da aba Produtos -->
        <div class="admin-tab-content <?= $aba_ativa == 'produtos' ? 'ativa' : '' ?>">
            <h2 class="section-title">Lista de Produtos</h2>
            
            <?php if (empty($produtos)): ?>
                <p>Nenhum produto cadastrado.</p>
            <?php else: ?>
                <ul class="lista-itens">
                    <?php foreach ($produtos as $produto): ?>
                        <li class="item-lista">
                            <div class="item-info">
                                <h3><?= htmlspecialchars($produto['nome']) ?></h3>
                                <p><strong>Categoria:</strong> <?= htmlspecialchars($produto['categoria_nome'] ?? 'Sem categoria') ?></p>
                                <p><strong>Preço:</strong> R$ <?= number_format($produto['preco'], 2, ',', '.') ?>
                                    <?php if ($produto['preco_promocional'] > 0): ?>
                                        <span style="color: var(--primary);">(Promoção: R$ <?= number_format($produto['preco_promocional'], 2, ',', '.') ?>)</span>
                                    <?php endif; ?>
                                </p>
                                <p><strong>Estoque:</strong> <?= $produto['estoque'] ?></p>
                            </div>
                            <div class="item-acoes">
                                <a href="?aba=produtos&editar=1&tipo=produto&id=<?= $produto['id'] ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <!-- Formulário de Edição/Adição de Produto -->
            <div class="formulario-edicao">
                <h3><?= isset($editar_produto) ? 'Editar Produto' : 'Adicionar Novo Produto' ?></h3>
                
                <form method="POST" class="admin-form">
                    <?php if (isset($editar_produto)): ?>
                        <input type="hidden" name="id" value="<?= $editar_produto['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="nome_produto">Nome do Produto*</label>
                        <input type="text" id="nome_produto" name="nome" required
                               value="<?= isset($editar_produto) ? htmlspecialchars($editar_produto['nome']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="descricao">Descrição</label>
                        <textarea id="descricao" name="descricao"><?= isset($editar_produto) ? htmlspecialchars($editar_produto['descricao']) : '' ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="categoria">Categoria*</label>
                        <select id="categoria" name="categoria_id" required>
                            <option value="">Selecione uma categoria</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= $categoria['id'] ?>" 
                                    <?= (isset($editar_produto) && $editar_produto['categoria_id'] == $categoria['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($categoria['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="preco">Preço*</label>
                        <input type="text" id="preco" name="preco" placeholder="0,00" required 
                               onkeyup="formatCurrency(this)" pattern="^\d+(,\d{1,2})?$"
                               value="<?= isset($editar_produto) ? number_format($editar_produto['preco'], 2, ',', '.') : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="preco_promocional">Preço Promocional</label>
                        <input type="text" id="preco_promocional" name="preco_promocional" placeholder="0,00" 
                               onkeyup="formatCurrency(this)" pattern="^\d+(,\d{1,2})?$"
                               value="<?= isset($editar_produto) && $editar_produto['preco_promocional'] > 0 ? number_format($editar_produto['preco_promocional'], 2, ',', '.') : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="estoque">Estoque*</label>
                        <input type="number" id="estoque" name="estoque" min="0" required
                               value="<?= isset($editar_produto) ? $editar_produto['estoque'] : '0' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="imagem">URL da Imagem</label>
                        <input type="url" id="imagem" name="imagem" placeholder="https://exemplo.com/imagem.jpg"
                               value="<?= isset($editar_produto) ? htmlspecialchars($editar_produto['imagem']) : '' ?>"
                               onchange="atualizarPreview(this.value)">
                        <img id="preview-imagem" class="preview-imagem" src="<?= isset($editar_produto) ? htmlspecialchars($editar_produto['imagem']) : '' ?>" 
                             alt="Pré-visualização da imagem">
                    </div>
                    
                    <div class="form-actions">
                        <?php if (isset($editar_produto)): ?>
                            <button type="submit" name="atualizar_produto" class="btn btn-primary">
                                <i class="fas fa-save"></i> Atualizar
                            </button>
                            <a href="?aba=produtos" class="btn btn-cancelar">Cancelar</a>
                        <?php else: ?>
                            <button type="submit" name="adicionar_produto" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Adicionar
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Função para formatar valores monetários
        function formatCurrency(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length === 1) value = '0' + value;
            if (value.length === 0) value = '00';
            
            const formatted = (parseInt(value) / 100).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            
            input.value = formatted;
        }
        
        // Função para atualizar pré-visualização da imagem
        function atualizarPreview(url) {
            const preview = document.getElementById('preview-imagem');
            if (url) {
                preview.src = url;
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        }
        
        // Validação do formulário de produtos
        document.querySelector('form[name="adicionar_produto"], form[name="atualizar_produto"]')?.addEventListener('submit', function(e) {
            const preco = document.getElementById('preco');
            const precoValue = preco.value.replace(/\./g, '').replace(',', '.');
            
            if (isNaN(parseFloat(precoValue)) || parseFloat(precoValue) <= 0) {
                alert('Preço inválido!');
                e.preventDefault();
                preco.focus();
                return false;
            }
            
            preco.value = precoValue;
            
            const precoPromocional = document.getElementById('preco_promocional');
            if (precoPromocional.value) {
                const precoPromocionalValue = precoPromocional.value.replace(/\./g, '').replace(',', '.');
                precoPromocional.value = precoPromocionalValue;
            }
            
            return true;
        });
    </script>
</body>
</html>
