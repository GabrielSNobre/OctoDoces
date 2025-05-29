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

// Verificar se o endereço pertence ao usuário
$stmt = $pdo->prepare("SELECT 1 
                      FROM usuario_enderecos 
                      WHERE usuario_id = ? AND endereco_id = ?");
$stmt->execute([$usuario_id, $endereco_id]);

if (!$stmt->fetch()) {
    header("Location: perfil.php");
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Primeiro excluir o relacionamento
    $stmt = $pdo->prepare("DELETE FROM usuario_enderecos 
                          WHERE usuario_id = ? AND endereco_id = ?");
    $stmt->execute([$usuario_id, $endereco_id]);
    
    // Depois excluir o endereço (se não estiver sendo usado por outros usuários)
    $stmt = $pdo->prepare("DELETE FROM enderecos 
                          WHERE id = ? AND NOT EXISTS (
                              SELECT 1 FROM usuario_enderecos 
                              WHERE endereco_id = ? AND usuario_id != ?
                          )");
    $stmt->execute([$endereco_id, $endereco_id, $usuario_id]);
    
    $pdo->commit();
    header("Location: perfil.php?success=endereco_excluido");
} catch (PDOException $e) {
    $pdo->rollBack();
    header("Location: perfil.php?error=endereco_excluido");
}

exit();
?>