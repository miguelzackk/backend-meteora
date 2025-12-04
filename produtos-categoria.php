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

// Verifica se a categoria foi passada
if (!isset($_GET["categoria"])) {
    echo json_encode(["error" => "Categoria não especificada"]);
    exit();
}

$categoria = $_GET["categoria"];
$categoriaEncoded = urlencode($categoria);

// Faz a requisição ao Supabase
$endpoint = "tbl_produto?select=*&categoria=eq.$categoriaEncoded&order=id_produto";

$response = supabase("GET", $endpoint);

if ($response["status"] >= 400) {
    echo json_encode(["error" => "Erro ao buscar produtos", "debug" => $response]);
    exit();
}

echo json_encode($response["data"]);
