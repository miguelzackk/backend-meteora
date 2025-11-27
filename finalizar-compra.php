<?php
// =====================
// FINALIZAR COMPRA API
// =====================

// CORS
header('Access-Control-Allow-Origin: http://127.0.0.1:5500');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

// Responder preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// =====================
// CONEXÃO COM O BANCO
// =====================
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

// =====================
// LER BODY JSON
// =====================
$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input['id_cliente']) || !isset($input['itens'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Dados inválidos."]);
    exit();
}

$id_cliente = (int)$input['id_cliente'];
$itens = $input['itens'];

try {
    // Inicia transação
    $pdo->beginTransaction();

    // Criar o registro da compra principal
    $stmt = $pdo->prepare("INSERT INTO tbl_compra (id_cliente, data_compra, valor_total) VALUES (?, NOW(), 0)");
    $stmt->execute([$id_cliente]);
    $id_compra = $pdo->lastInsertId();

    $valor_total = 0;

    // Processar cada item do carrinho
    foreach ($itens as $item) {
        $id_produto = (int)$item['id_produto'];
        $quantidade = (int)$item['quantidade'];
        $valor_unitario = (float)$item['preco'];
        $subtotal = $quantidade * $valor_unitario;
        $valor_total += $subtotal;

        // Registrar item no histórico da compra
        $stmt = $pdo->prepare("
            INSERT INTO tbl_historico_compra (
                id_cliente,
                id_compra,
                id_produto,
                data_registro,
                quantidade,
                valor_unitario
            ) VALUES (?, ?, ?, NOW(), ?, ?)
        ");
        $stmt->execute([$id_cliente, $id_compra, $id_produto, $quantidade, $valor_unitario]);

        // Atualizar estoque do produto
        $stmt = $pdo->prepare("UPDATE tbl_produto SET estoque = estoque - ? WHERE id_produto = ?");
        $stmt->execute([$quantidade, $id_produto]);
    }

    // Atualizar o valor total da compra
    $stmt = $pdo->prepare("UPDATE tbl_compra SET valor_total = ? WHERE id_compra = ?");
    $stmt->execute([$valor_total, $id_compra]);

    // Finalizar transação
    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Compra registrada com sucesso!",
        "id_compra" => $id_compra,
        "valor_total" => $valor_total
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
