<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

require 'conexao.php';

$usuario_id = $_SESSION['usuario']['id'];

// Verificar se o ID do endereço foi passado
if (!isset($_GET['id'])) {
    header("Location: perfil.php");
    exit();
}

$endereco_id = $_GET['id'];

try {
    $pdo->beginTransaction();
    
    // Primeiro, desmarcar todos os endereços como principal
    $stmt = $pdo->prepare("UPDATE usuario_enderecos ue 
                          JOIN enderecos e ON ue.endereco_id = e.id
                          SET e.principal = FALSE 
                          WHERE ue.usuario_id = ?");
    $stmt->execute([$usuario_id]);
    
    // Agora marcar o endereço selecionado como principal
    $stmt = $pdo->prepare("UPDATE enderecos 
                          SET principal = TRUE 
                          WHERE id = ?");
    $stmt->execute([$endereco_id]);
    
    $pdo->commit();
    header("Location: perfil.php?success=endereco_principal");
} catch (PDOException $e) {
    $pdo->rollBack();
    header("Location: perfil.php?error=endereco_principal");
}

exit();
?>