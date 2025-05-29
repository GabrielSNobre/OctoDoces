<?php
session_start();
require 'conexao.php';

// Função para retornar JSON
function jsonResponse($success, $message = '', $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// API para ações no carrinho
if (isset($_GET['action'])) {
    if (!isset($_SESSION['usuario'])) {
        jsonResponse(false, 'Por favor, faça login para continuar');
    }

    switch ($_GET['action']) {
        case 'update':
            $produto_id = $_POST['produto_id'];
            $quantidade = intval($_POST['quantidade']);
            
            if ($quantidade > 0) {
                $_SESSION['carrinho'][$produto_id] = $quantidade;
                jsonResponse(true, 'Quantidade atualizada');
            } else {
                unset($_SESSION['carrinho'][$produto_id]);
                jsonResponse(true, 'Item removido do carrinho');
            }
            break;
            
        case 'remove':
            $produto_id = $_GET['produto_id'];
            if (isset($_SESSION['carrinho'][$produto_id])) {
                unset($_SESSION['carrinho'][$produto_id]);
                jsonResponse(true, 'Item removido do carrinho');
            } else {
                jsonResponse(false, 'Item não encontrado no carrinho');
            }
            break;
            
        case 'get_cart':
            $carrinho_itens = [];
            $subtotal = 0;
            
            if (!empty($_SESSION['carrinho'])) {
                $ids = array_keys($_SESSION['carrinho']);
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                
                $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                $produtos = $stmt->fetchAll();
                
                foreach ($produtos as $produto) {
                    $quantidade = $_SESSION['carrinho'][$produto['id']];
                    $preco = $produto['preco_promocional'] > 0 ? $produto['preco_promocional'] : $produto['preco'];
                    $total = $preco * $quantidade;
                    $subtotal += $total;
                    
                    $carrinho_itens[] = [
                        'produto' => $produto,
                        'quantidade' => $quantidade,
                        'preco' => $preco,
                        'total' => $total
                    ];
                }
            }
            
            // Calcular frete e total
            $frete = 15.00;
            $total_geral = $subtotal + $frete;
            
            jsonResponse(true, '', [
            'itens' => $carrinho_itens,
            'subtotal' => (float)$subtotal,
            'frete' => (float)$frete,
            'total_geral' => (float)$total_geral,
            'count' => count($_SESSION['carrinho'] ?? [])
            ]);
            break;
            
        default:
            jsonResponse(false, 'Ação inválida');
    }
}

// Redirecionar se não estiver logado
if (!isset($_SESSION['usuario'])) {
    $_SESSION['redirect_to'] = 'carrinho.php';
    header("Location: login.php");
    exit();
}

// Atualizar carrinho
if (isset($_POST['atualizar'])) {
    foreach ($_POST['quantidade'] as $produto_id => $quantidade) {
        if ($quantidade > 0) {
            $_SESSION['carrinho'][$produto_id] = $quantidade;
        } else {
            unset($_SESSION['carrinho'][$produto_id]);
        }
    }
    $_SESSION['mensagem'] = "Carrinho atualizado com sucesso!";
    header("Location: carrinho.php");
    exit();
}

// Remover item
if (isset($_GET['remover'])) {
    $produto_id = $_GET['remover'];
    
    if (isset($_SESSION['carrinho'][$produto_id])) {
        unset($_SESSION['carrinho'][$produto_id]);
        $_SESSION['mensagem'] = "Item removido do carrinho!";
    }
    
    header("Location: carrinho.php");
    exit();
}

// Buscar informações dos produtos no carrinho
$carrinho_itens = [];
$total_geral = 0;
$subtotal = 0;

if (!empty($_SESSION['carrinho'])) {
    $ids = array_keys($_SESSION['carrinho']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $produtos = $stmt->fetchAll();
    
    foreach ($produtos as $produto) {
        $quantidade = $_SESSION['carrinho'][$produto['id']];
        $preco = $produto['preco_promocional'] > 0 ? $produto['preco_promocional'] : $produto['preco'];
        $total = $preco * $quantidade;
        $subtotal += $total;
        
        $carrinho_itens[] = [
            'produto' => $produto,
            'quantidade' => $quantidade,
            'preco' => $preco,
            'total' => $total
        ];
    }
    
    // Calcular frete (exemplo fixo)
    $frete = 15.00;
    $total_geral = $subtotal + $frete;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrinho de Compras - Octo Doces</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>    
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
            background-color: #f9f9f9;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        
        nav ul {
            display: flex;
            list-style: none;
            gap: 20px;
        }
        
        nav a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            transition: color 0.3s;
        }
        
        nav a:hover {
            color: var(--primary);
        }
        
        h1 {
            text-align: center;
            margin: 30px 0;
            color: var(--secondary);
            font-size: 2.2rem;
        }
        
        /* Mensagens */
        .mensagem {
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            text-align: center;
        }
        
        .mensagem-sucesso {
            background-color: #d4edda;
            color: #155724;
        }
        
        .mensagem-erro {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* Carrinho */
        .carrinho-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        
        @media (max-width: 768px) {
            .carrinho-container {
                grid-template-columns: 1fr;
            }
        }
        
        .carrinho-itens {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            padding: 20px;
        }
        
        .carrinho-vazio {
            text-align: center;
            padding: 40px 0;
        }
        
        .carrinho-vazio p {
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
        
        .item-carrinho {
            display: grid;
            grid-template-columns: 100px 1fr auto auto auto;
            gap: 20px;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
        }
        
        @media (max-width: 600px) {
            .item-carrinho {
                grid-template-columns: 1fr;
                text-align: center;
            }
        }
        
        .item-imagem {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .item-info h3 {
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .item-info .categoria {
            font-size: 0.8rem;
            color: #666;
        }
        
        .item-preco {
            font-weight: 600;
            color: var(--primary);
        }
        
        .item-preco-promocional {
            text-decoration: line-through;
            color: #999;
            font-size: 0.9rem;
        }
        
        .item-quantidade {
            display: flex;
            align-items: center;
        }
        
        .item-quantidade input {
            width: 60px;
            padding: 8px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .item-total {
            font-weight: 700;
        }
        
        .item-remover {
            color: var(--primary);
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .item-remover:hover {
            color: #e63946;
        }
        
        /* Resumo do pedido */
        .resumo-pedido {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            padding: 20px;
            align-self: start;
            position: sticky;
            top: 100px;
        }
        
        .resumo-pedido h2 {
            margin-bottom: 20px;
            color: var(--secondary);
            font-size: 1.3rem;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--accent);
        }
        
        .resumo-linha {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .resumo-total {
            font-weight: 700;
            font-size: 1.2rem;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        /* Botões */
        .botoes-carrinho {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            border-radius: 4px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
        }
        
        .btn-continuar {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-finalizar {
            width: 100%;
            margin-top: 20px;
            padding: 15px;
            font-size: 1.1rem;
            background-color: var(--secondary);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-finalizar:hover {
            background-color: #3dbeb6;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .btn-finalizar i {
            margin-right: 10px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .btn-continuar:hover {
            background-color: #5a6268;
        }
        
        .btn-atualizar:hover {
            background-color: #e6d252;
        }
        /* Footer */
        footer {
            background-color: var(--dark);
            color: white;
            padding: 50px 0 20px;
            margin-top: 50px;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .footer-section h3 {
            color: var(--accent);
            margin-bottom: 20px;
            font-size: 1.2rem;
        }
        
        .footer-section p {
            margin-bottom: 10px;
        }
        
        .social-icons {
            display: flex;
            gap: 15px;
        }
        
        .social-icons a {
            color: white;
            font-size: 1.2rem;
            transition: color 0.3s;
        }
        
        .social-icons a:hover {
            color: var(--accent);
        }
        
        .copyright {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
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
                <?php if(isset($_SESSION['usuario'])): ?>
                    <li><a href="perfil.php">Minha Conta</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

    <div class="container">
        <h1>Seu Carrinho de Compras</h1>
        
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="mensagem mensagem-sucesso"><?= $_SESSION['mensagem'] ?></div>
            <?php unset($_SESSION['mensagem']); ?>
        <?php endif; ?>
        
        <div class="carrinho-container">
            <div class="carrinho-itens">
                <!-- Itens serão carregados via AJAX -->
            </div>
            
            <div class="resumo-pedido">
                <h2>Resumo do Pedido</h2>
                
                <div class="resumo-linha">
                    <span>Subtotal:</span>
                    <span id="subtotal">R$ 0,00</span>
                </div>
                
                <div class="resumo-linha">
                    <span>Frete:</span>
                    <span id="frete">R$ 0,00</span>
                </div>
                
                <div class="resumo-linha resumo-total">
                    <span>Total:</span>
                    <span id="total-geral">R$ 0,00</span>
                </div>
                
                <button id="finalizar-compra" class="btn-finalizar">
                    <i class="fas fa-credit-card"></i> Finalizar Compra
                </button>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Função para atualizar o carrinho
       function atualizarCarrinho() {
    $.get('carrinho.php?action=get_cart', function(response) {
        if (response.success) {
            const data = response.data;
            
            // Atualizar contador no menu
            $('.cart-count').text(data.count);
            
            if (data.count === 0) {
                $('.carrinho-container').html(`
                    <div class="carrinho-vazio">
                        <p>Seu carrinho está vazio</p>
                        <a href="produtos.php" class="btn btn-continuar">Continuar Comprando</a>
                    </div>
                `);
                return;
            }
            
            let itensHTML = '';
            data.itens.forEach(item => {
                // Converter preços para número se necessário
                const preco = parseFloat(item.preco) || 0;
                const precoOriginal = item.produto.preco_promocional > 0 ? 
                    parseFloat(item.produto.preco) || 0 : 0;
                const total = parseFloat(item.total) || 0;
                
                const precoOriginalHTML = item.produto.preco_promocional > 0 ? 
                    `<span class="item-preco-promocional">R$ ${precoOriginal.toFixed(2).replace('.', ',')}</span>` : '';
                
                itensHTML += `
                <div class="item-carrinho" data-id="${item.produto.id}">
                    <img src="${item.produto.imagem}" alt="${item.produto.nome}" class="item-imagem">
                    
                    <div class="item-info">
                        <h3>${item.produto.nome}</h3>
                        <p class="categoria">${item.produto.categoria_nome || 'Geral'}</p>
                    </div>
                    
                    <div class="item-preco">
                        R$ ${preco.toFixed(2).replace('.', ',')}
                        ${precoOriginalHTML}
                    </div>
                    
                    <div class="item-quantidade">
                        <input type="number" value="${item.quantidade}" min="1" 
                            onchange="atualizarQuantidade(${item.produto.id}, this.value)">
                    </div>
                    
                    <div class="item-total">
                        R$ ${total.toFixed(2).replace('.', ',')}
                    </div>
                    
                    <a href="#" onclick="removerItem(${item.produto.id})" class="item-remover" title="Remover item">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                </div>
                `;
            });
            
            // Atualizar resumo
            const subtotal = parseFloat(data.subtotal) || 0;
            const frete = parseFloat(data.frete) || 0;
            const totalGeral = parseFloat(data.total_geral) || 0;
            
            $('#subtotal').text(`R$ ${subtotal.toFixed(2).replace('.', ',')}`);
            $('#frete').text(`R$ ${frete.toFixed(2).replace('.', ',')}`);
            $('#total-geral').text(`R$ ${totalGeral.toFixed(2).replace('.', ',')}`);
            
            $('.carrinho-itens').html(itensHTML);
        }
    });
}
        
        // Atualizar quantidade (automática)
        window.atualizarQuantidade = function(produto_id, quantidade) {
            $.post('carrinho.php?action=update', {
                produto_id: produto_id,
                quantidade: quantidade
            }, function(response) {
                if (response.success) {
                    atualizarCarrinho();
                } else {
                    alert(response.message);
                }
            }, 'json');
        };
        
        // Remover item
        window.removerItem = function(produto_id) {
            if (confirm('Deseja realmente remover este item do carrinho?')) {
                $.get('carrinho.php?action=remove&produto_id=' + produto_id, function(response) {
                    if (response.success) {
                        atualizarCarrinho();
                    } else {
                        alert(response.message);
                    }
                }, 'json');
            }
            return false;
        };
        
        // Finalizar compra
        $('#finalizar-compra').click(function(e) {
            e.preventDefault();
            
            // Verificar se o carrinho está vazio
            if ($('.cart-count').text() === '0') {
                alert('Seu carrinho está vazio. Adicione produtos antes de finalizar.');
                return;
            }
            
            // Redirecionar para página de finalização
            window.location.href = 'finalizar-pedido.php';
        });
        
        // Carregar carrinho inicial
        atualizarCarrinho();
    });
    </script>

<footer id="contact">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h3>Sobre Nós</h3>
                <p>A Octo Doces nasceu do amor por doces e da vontade de trazer sabores especiais para momentos especiais.</p>
            </div>

            <div class="footer-section">
                <h3>Contato</h3>
                <p><i class="fas fa-map-marker-alt"></i> Rua dos Doces, 123 - Sigma</p>
                <p><i class="fas fa-phone"></i> (00) 1234-5678</p>
                <p><i class="fas fa-envelope"></i> contato@octodoces.com</p>
            </div>

            <div class="footer-section">
                <h3>Redes Sociais</h3>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2023 Octo Doces. Todos os direitos reservados.</p>
        </div>
    </div>
</footer>
</body>
</html>