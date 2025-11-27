<?php
include 'database.php';

try {
    // Buscar 1 produto de cada categoria para a página inicial
    $stmt = $pdo->query("
        SELECT p.*
        FROM tbl_produto p
        INNER JOIN (
            SELECT categoria, MIN(id_produto) as min_id
            FROM tbl_produto
            GROUP BY categoria
        ) as sub ON p.categoria = sub.categoria AND p.id_produto = sub.min_id
        ORDER BY p.categoria
        LIMIT 6
    ");
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Se não tiver produtos, buscar qualquer 6 produtos
    if (empty($produtos)) {
        $stmt = $pdo->query("SELECT * FROM tbl_produto ORDER BY id_produto LIMIT 6");
        $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode($produtos);
    
} catch(PDOException $e) {
    echo json_encode(['error' => 'Erro ao buscar produtos: ' . $e->getMessage()]);
}
?>