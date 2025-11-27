<?php
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = $_GET['id'] ?? '';
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM tbl_produto WHERE id_produto = ?");
        $stmt->execute([$id]);
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($produto) {
            echo json_encode($produto);
        } else {
            echo json_encode(['error' => 'Produto não encontrado']);
        }
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Erro ao buscar produto: ' . $e->getMessage()]);
    }
}
?>