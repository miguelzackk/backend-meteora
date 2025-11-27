<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

// Lê variáveis de ambiente
$SUPABASE_URL = $_ENV['SUPABASE_URL'] ?? $_SERVER['SUPABASE_URL'];
$SUPABASE_KEY = $_ENV['SUPABASE_KEY'] ?? $_SERVER['SUPABASE_KEY'];

// Se não estiverem definidas, retorna erro
if (!$SUPABASE_URL || !$SUPABASE_KEY) {
    echo json_encode([
        "error" => "Variáveis de ambiente SUPABASE_URL ou SUPABASE_KEY não definidas"
    ]);
    exit;
}

// URL da tabela de produtos
$url = $SUPABASE_URL . "/rest/v1/tbl_produto?select=*";

// cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "apikey: $SUPABASE_KEY",
    "Authorization: Bearer $SUPABASE_KEY",
    "Content-Type: application/json",
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Verifica erro
if ($httpCode >= 400) {
    echo json_encode([
        "error" => "Erro ao buscar produtos",
        "status" => $httpCode,
        "response" => $response
    ]);
    exit;
}

// Retorna JSON
echo $response;
?>
