<?php
// Mostrar erros para debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(["ok" => true]);
    exit();
}

include 'database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método inválido']);
    exit();
}

// Ler o corpo JSON enviado pelo fetch
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['id_produto']) || !isset($data['quantidade'])) {
    echo json_encode(['error' => 'Parâmetros inválidos']);
    exit();
}

$id_produto = (int)$data['id_produto'];
$quantidade = (int)$data['quantidade'];

try {
    // Buscar estoque atual
    $stmt = $pdo->prepare("SELECT estoque FROM tbl_produto WHERE id_produto = ?");
    $stmt->execute([$id_produto]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produto) {
        echo json_encode(['error' => 'Produto não encontrado']);
        exit();
    }

    $estoqueAtual = (int)$produto['estoque'];
    $novoEstoque = $estoqueAtual - $quantidade;

    if ($novoEstoque < 0) {
        echo json_encode([
            'error' => 'Estoque insuficiente',
            'estoque_atual' => $estoqueAtual
        ]);
        exit();
    }

    // Atualizar estoque
    $stmt = $pdo->prepare("UPDATE tbl_produto SET estoque = ? WHERE id_produto = ?");
    $stmt->execute([$novoEstoque, $id_produto]);

    echo json_encode([
        'success' => true,
        'id_produto' => $id_produto,
        'estoque_antigo' => $estoqueAtual,
        'novo_estoque' => $novoEstoque
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao atualizar estoque: ' . $e->getMessage()]);
}
