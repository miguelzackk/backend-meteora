<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

require __DIR__ . "/supabase.php";

// Verifica se a categoria foi passada
if (!isset($_GET["categoria"]) || empty($_GET["categoria"])) {
    echo json_encode(["error" => "Categoria não especificada"]);
    exit();
}

$categoria = trim($_GET["categoria"]);

// ⚠️ Usar ilike (case-insensitive) e encode corretamente
$categoriaFiltro = rawurlencode("%" . $categoria . "%");

// Endpoint Supabase
$endpoint = "tbl_produto?select=*&categoria=ilike.$categoriaFiltro&order=id_produto";

// Faz requisição
$response = supabase("GET", $endpoint);

if (!isset($response["data"])) {
    echo json_encode(["error" => "Erro inesperado ao buscar produtos", "debug" => $response]);
    exit();
}

// Se nenhum produto for encontrado
if (empty($response["data"])) {
    echo json_encode(["warning" => "Nenhum produto encontrado para esta categoria"]);
    exit();
}

// Retornar produtos filtrados
echo json_encode($response["data"], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
