<?php
include 'database.php';

// Permitir CORS
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

try {
    // Verificar se a categoria foi passada
    if (!isset($_GET['categoria'])) {
        echo json_encode(['error' => 'Categoria não especificada']);
        exit();
    }

    $categoria = $_GET['categoria'];
    
    // Buscar produtos por categoria
    $stmt = $pdo->prepare("SELECT * FROM tbl_produto WHERE categoria = ? ORDER BY id_produto");
    $stmt->execute([$categoria]);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($produtos);
    
} catch(PDOException $e) {
    echo json_encode(['error' => 'Erro ao buscar produtos: ' . $e->getMessage()]);
}
?>