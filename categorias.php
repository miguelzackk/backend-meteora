<?php
include 'database.php';

try {
    $stmt = $pdo->query("SELECT DISTINCT categoria FROM tbl_produto");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($categorias);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Erro ao buscar categorias: ' . $e->getMessage()]);
}
?>