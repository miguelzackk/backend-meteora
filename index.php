<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/supabase.php';


// 🔀 Roteamento simples
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

if (str_contains($path, "produtos-categoria")) {
    include "produtos-categoria.php";
    exit;
}

if (preg_match("/produtos(\.php)?$/", $path)) {
    include "produtos.php";
    exit;
}

echo json_encode(["message" => "API do Meteora está online 🚀"]);
