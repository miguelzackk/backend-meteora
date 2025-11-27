<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . "/supabase.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["error" => "Método inválido"]);
    exit();
}

// Lê JSON enviado pelo frontend
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data["id_produto"]) || !isset($data["quantidade"])) {
    echo json_encode(["error" => "Parâmetros inválidos"]);
    exit();
}

$id = (int)$data["id_produto"];
$qtd = (int)$data["quantidade"];


//1) BUSCAR O PRODUTO
$response = supabase(
    "GET",
    "tbl_produto?id_produto=eq.$id&select=estoque,id_produto"
);

if ($response["status"] !== 200 || empty($response["data"])) {
    echo json_encode(["error" => "Produto não encontrado"]);
    exit();
}

$estoqueAtual = (int)$response["data"][0]["estoque"];
$novoEstoque = $estoqueAtual - $qtd;

if ($novoEstoque < 0) {
    echo json_encode([
        "error" => "Estoque insuficiente",
        "estoque_atual" => $estoqueAtual
    ]);
    exit();
}


// 2) ATUALIZAR ESTOQUE
$update = supabase(
    "PATCH",
    "tbl_produto?id_produto=eq.$id",
    ["estoque" => $novoEstoque]
);

if ($update["status"] >= 400) {
    echo json_encode(["error" => "Erro ao atualizar estoque"]);
    exit();
}


// 3) RETORNO FINAL
echo json_encode([
    "success" => true,
    "id_produto" => $id,
    "estoque_antigo" => $estoqueAtual,
    "novo_estoque" => $novoEstoque
]);
