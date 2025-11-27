<?php
// =====================
// LISTAR HISTÓRICO DE COMPRAS
// =====================

// CORS
header('Access-Control-Allow-Origin: http://127.0.0.1:5500');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Conexão
$host = 'localhost';
$dbname = 'ecommerce_vestes_moda';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Erro de conexão: " . $e->getMessage()]);
    exit();
}

// Parâmetro: id_cliente
$id_cliente = isset($_GET['id_cliente']) ? (int)$_GET['id_cliente'] : 0;

if ($id_cliente <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "ID do cliente inválido."]);
    exit();
}

try {
    // Busca as compras e seus itens
    $sql = "
        SELECT 
            c.id_compra,
            c.data_compra,
            c.valor_total,
            p.nome AS nome_produto,
            p.imagem,
            h.quantidade,
            h.valor_unitario
        FROM tbl_compra c
        JOIN tbl_historico_compra h ON h.id_compra = c.id_compra
        JOIN tbl_produto p ON p.id_produto = h.id_produto
        WHERE c.id_cliente = ?
        ORDER BY c.data_compra DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_cliente]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        echo json_encode(["success" => true, "historico" => []]);
        exit();
    }

    // Agrupar itens por compra
    $historico = [];
    foreach ($rows as $r) {
        $id_compra = $r['id_compra'];
        if (!isset($historico[$id_compra])) {
            $historico[$id_compra] = [
                "id_compra" => $id_compra,
                "data_compra" => $r['data_compra'],
                "valor_total" => (float)$r['valor_total'],
                "itens" => []
            ];
        }

        $historico[$id_compra]["itens"][] = [
            "nome_produto" => $r['nome_produto'],
            "imagem" => $r['imagem'],
            "quantidade" => (int)$r['quantidade'],
            "valor_unitario" => (float)$r['valor_unitario']
        ];
    }

    echo json_encode(["success" => true, "historico" => array_values($historico)]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
