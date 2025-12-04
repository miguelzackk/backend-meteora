<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

// Variáveis do Railway
$SUPABASE_URL = getenv("SUPABASE_URL");
$SUPABASE_KEY = getenv("SUPABASE_KEY");

function supabase($method, $endpoint, $body = null)
{
    global $SUPABASE_URL, $SUPABASE_KEY;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "$SUPABASE_URL/rest/v1/$endpoint",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "apikey: $SUPABASE_KEY",
            "Authorization: Bearer $SUPABASE_KEY",
            "Content-Type: application/json"
        ],
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_SSL_VERIFYPEER => false, // Railway SSL fix
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        "status" => $code,
        "data" => json_decode($response, true)
    ];
}

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
