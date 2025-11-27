<?php
require_once __DIR__ . "/supabase.php";

// Endpoint REST do Supabase
$url = SUPABASE_URL . "/rest/v1/tbl_produto?select=*";

// Requisição usando cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "apikey: " . SUPABASE_KEY,
    "Authorization: Bearer " . SUPABASE_KEY,
    "Content-Type: application/json",
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Se erro
if ($httpCode >= 400) {
    echo json_encode([
        "error" => "Erro ao buscar produtos",
        "status" => $httpCode,
        "response" => $response
    ]);
    exit;
}

// Sucesso
echo $response;
?>
