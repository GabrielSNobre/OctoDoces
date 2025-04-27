<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

require 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $endereco = $_POST['endereco'];
    $usuario_id = $_SESSION['usuario']['id'];
    
    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET endereco = ? WHERE id = ?");
        $stmt->execute([$endereco, $usuario_id]);
        
        $_SESSION['usuario']['endereco'] = $endereco;
        header("Location: perfil.php?success=1");
        exit();
    } catch (PDOException $e) {
        header("Location: perfil.php?error=1");
        exit();
    }
}

header("Location: perfil.php");
exit();
?>