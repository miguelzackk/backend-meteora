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


require_once __DIR__ . "/supabase.php";


if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    echo json_encode(["error" => "Método inválido"]);
    exit();
}

$termo = $_GET["q"] ?? "";


if (trim($termo) === "") {
    $response = supabase("GET", "tbl_produto?select=*");
    http_response_code($response["status"]);
    echo json_encode($response["data"]);
    exit();
}


$termoEncoded = rawurlencode($termo);

$endpoint = "tbl_produto?select=*"
    . "&or=(nome.ilike.*{$termoEncoded}*,descricao.ilike.*{$termoEncoded}*,categoria.ilike.*{$termoEncoded}*)";

$response = supabase("GET", $endpoint);


http_response_code($response["status"]);
echo json_encode($response["data"]);
?>