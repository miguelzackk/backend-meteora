<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/supabase.php';


// 🟣 Verifica parâmetro
if (!isset($_GET["categoria"]) || empty(trim($_GET["categoria"]))) {
    echo json_encode(["error" => "Categoria não especificada"]);
    exit();
}

$categoria = trim($_GET["categoria"]);

// ✅ Filtro Supabase
$filtro = str_replace(" ", "%20", $categoria);
$endpoint = "tbl_produto?select=*&categoria=ilike.*{$filtro}*&order=id_produto.asc";

// 🔍 Busca os dados
$response = supabase("GET", $endpoint);

// 🚀 Retorno
if (!isset($response["data"]) || !is_array($response["data"])) {
    echo json_encode(["error" => "Erro ao buscar produtos", "debug" => $response]);
    exit();
}

if (empty($response["data"])) {
    echo json_encode(["warning" => "Nenhum produto encontrado nesta categoria"]);
    exit();
}

echo json_encode($response["data"], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
