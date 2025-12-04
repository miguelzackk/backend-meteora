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

if (!isset($_GET["categoria"]) || empty(trim($_GET["categoria"]))) {
    echo json_encode(["error" => "Categoria não especificada"]);
    exit();
}

$categoria = trim($_GET["categoria"]);

// ✅ Formata corretamente o filtro ilike do Supabase
// Ele precisa ser sem encoding do asterisco (*)
$filtro = urlencode($categoria);
$endpoint = "tbl_produto?select=*&categoria=ilike.*{$filtro}*&order=id_produto.asc";

// 🔍 Faz requisição
$response = supabase("GET", $endpoint);

// 🚨 Debug opcional: mostrar endpoint usado (pode remover depois)
# echo json_encode(["endpoint" => $endpoint, "response" => $response]);

if (!isset($response["data"]) || !is_array($response["data"])) {
    echo json_encode(["error" => "Erro ao buscar produtos", "debug" => $response]);
    exit();
}

if (empty($response["data"])) {
    echo json_encode(["warning" => "Nenhum produto encontrado nesta categoria"]);
    exit();
}

echo json_encode($response["data"], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
