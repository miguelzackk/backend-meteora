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

$termo = $_GET["q"] ?? "";

// Caso não tenha termo, retorna lista completa
if ($termo === "") {
    $response = supabase("GET", "tbl_produto?select=*");
    echo json_encode($response["data"]);
    exit();
}

// Monta filtros usando `or=` e `ilike`
$termoEncoded = urlencode("{$termo}");

$endpoint = "tbl_produto?select=*&or=(nome.ilike.*{$termoEncoded}*,descricao.ilike.*{$termoEncoded}*,categoria.ilike.*{$termoEncoded}*)";

$response = supabase("GET", $endpoint);

http_response_code($response["status"]);
echo json_encode($response["data"]);
