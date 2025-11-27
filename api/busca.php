<?php
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $termo = $_GET['q'] ?? '';
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM tbl_produto WHERE nome LIKE ? OR descricao LIKE ? OR categoria LIKE ?");
        $termoLike = "%$termo%";
        $stmt->execute([$termoLike, $termoLike, $termoLike]);
        $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($produtos);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Erro na busca: ' . $e->getMessage()]);
    }
}
?>