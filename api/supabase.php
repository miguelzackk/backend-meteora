<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

require __DIR__ . "/../vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$SUPABASE_URL = $_ENV["SUPABASE_URL"];
$SUPABASE_KEY = $_ENV["SUPABASE_KEY"];

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
        CURLOPT_CUSTOMREQUEST => $method
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
