<?php
function salvarCarrinho($pdo, $usuario_id) {
    if (!isset($_SESSION['carrinho']) || empty($_SESSION['carrinho'])) {
        return;
    }
    
    // Limpar carrinho antigo
    $pdo->prepare("DELETE FROM carrinhos WHERE usuario_id = ?")->execute([$usuario_id]);
    
    // Inserir itens atuais
    $stmt = $pdo->prepare("INSERT INTO carrinhos (usuario_id, produto_id, quantidade) VALUES (?, ?, ?)");
    
    foreach ($_SESSION['carrinho'] as $produto_id => $quantidade) {
        $stmt->execute([$usuario_id, $produto_id, $quantidade]);
    }
}

function recuperarCarrinho($pdo, $usuario_id) {
    $stmt = $pdo->prepare("SELECT produto_id, quantidade FROM carrinhos WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    $itens = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Retorna como [produto_id => quantidade]
    
    if (!empty($itens)) {
        $_SESSION['carrinho'] = $itens;
    }
}
function showFlashMessages() {
    if (isset($_GET['success'])): ?>
        <div class="message success">
            <?php 
                switch($_GET['success']) {
                    case 'endereco': echo "Endereço atualizado com sucesso!"; break;
                    case 'pedido': echo "Pedido realizado com sucesso!"; break;
                    case 'perfil': echo "Perfil atualizado com sucesso!"; break;
                    default: echo "Operação realizada com sucesso!";
                }
            ?>
        </div>
    <?php endif;
    
    if (isset($_GET['error'])): ?>
        <div class="message error">
            <?php 
                switch($_GET['error']) {
                    case 'endereco': echo "Erro ao atualizar endereço."; break;
                    case 'pedido': echo "Erro ao processar pedido."; break;
                    case 'perfil': echo "Erro ao atualizar perfil."; break;
                    default: echo "Ocorreu um erro.";
                }
            ?>
        </div>
    <?php endif;
}

function displayUserInfo($usuario) {
    ?>
    <div class="info-item">
        <span class="info-label">Nome Completo</span>
        <div class="info-value"><?= htmlspecialchars($usuario['nome']) ?></div>
    </div>
    <div class="info-item">
        <span class="info-label">E-mail</span>
        <div class="info-value"><?= htmlspecialchars($usuario['email']) ?></div>
    </div>
    <div class="info-item">
        <span class="info-label">CPF</span>
        <div class="info-value"><?= $usuario['cpf'] ? formatCPF($usuario['cpf']) : 'Não informado' ?></div>
    </div>
    <div class="info-item">
        <span class="info-label">Telefone</span>
        <div class="info-value"><?= $usuario['telefone'] ? formatPhone($usuario['telefone']) : 'Não informado' ?></div>
    </div>
    <div class="info-item">
        <span class="info-label">Tipo de Usuário</span>
        <div class="info-value"><?= ucfirst(htmlspecialchars($usuario['tipo_usuario'])) ?></div>
    </div>
    <div class="info-item">
        <span class="info-label">Data de Cadastro</span>
        <div class="info-value"><?= date('d/m/Y H:i', strtotime($usuario['data_cadastro'])) ?></div>
    </div>
    <?php
}

function displayUserAddresses($enderecos) {
    if (count($enderecos) > 0): ?>
        <div class="address-list">
            <?php foreach ($enderecos as $endereco): ?>
                <div class="address-card <?= $endereco['principal'] ? 'principal' : '' ?>">
                    <p><strong><?= htmlspecialchars($endereco['apelido'] ?? 'Endereço') ?></strong></p>
                    <p><?= htmlspecialchars($endereco['logradouro']) ?>, <?= htmlspecialchars($endereco['numero']) ?></p>
                    <p><?= htmlspecialchars($endereco['complemento'] ?? '') ?></p>
                    <p><?= htmlspecialchars($endereco['bairro']) ?> - <?= htmlspecialchars($endereco['cidade']) ?>/<?= htmlspecialchars($endereco['estado']) ?></p>
                    <p>CEP: <?= htmlspecialchars($endereco['cep']) ?></p>
                    <div class="address-actions">
                        <a href="enderecos/editar.php?id=<?= $endereco['id'] ?>" class="btn btn-primary btn-small">Editar</a>
                        <?php if (!$endereco['principal']): ?>
                            <a href="enderecos/principal.php?id=<?= $endereco['id'] ?>" class="btn btn-small">Tornar Principal</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>Você ainda não cadastrou nenhum endereço.</p>
    <?php endif;
}

function displayUserOrders($pedidos) {
    if (count($pedidos) > 0): ?>
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
                        <td><?= htmlspecialchars($pedido['codigo']) ?></td>
                        <td><?= htmlspecialchars(ellipsis($pedido['produtos_nomes'], 50)) ?></td>
                        <td>R$ <?= number_format($pedido['total'], 2, ',', '.') ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></td>
                        <td>
                            <span class="status status-<?= strtolower($pedido['status']) ?>">
                                <?= ucfirst($pedido['status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="/public/pedidos/detalhes.php?id=<?= $pedido['id'] ?>" class="btn btn-primary btn-small">Detalhes</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="empty-message">Você ainda não fez nenhum pedido.</p>
    <?php endif;
}

// Funções auxiliares
function formatCPF($cpf) {
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

function formatPhone($phone) {
    $length = strlen($phone);
    if ($length === 11) {
        return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $phone);
    }
    return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $phone);
}

function ellipsis($string, $maxLength) {
    if (strlen($string) > $maxLength) {
        return substr($string, 0, $maxLength) . '...';
    }
    return $string;
}
?>