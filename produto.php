<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

require __DIR__ . "/supabase.php";

$id = $_GET["id"] ?? "";

if (!$id) {
    echo json_encode(["error" => "ID do produto não fornecido"]);
    exit();
}

// Faz a consulta no Supabase
$response = supabase("GET", "tbl_produto?select=*&id_produto=eq.$id");

if ($response["status"] >= 400) {
    echo json_encode(["error" => "Erro ao buscar produto", "debug" => $response]);
    exit();
}

$produtos = $response["data"] ?? [];

if (empty($produtos)) {
    echo json_encode(["error" => "Produto não encontrado"]);
    exit();
}

echo json_encode($produtos[0]);
// NÃO ADICIONE HTML AQUI - este arquivo deve ser apenas API