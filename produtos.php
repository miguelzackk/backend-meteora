<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

// Pega variáveis de ambiente
$SUPABASE_URL = $_ENV['SUPABASE_URL'] ?? $_SERVER['SUPABASE_URL'];
$SUPABASE_KEY = $_ENV['SUPABASE_KEY'] ?? $_SERVER['SUPABASE_KEY'];

if (!$SUPABASE_URL || !$SUPABASE_KEY) {
    echo json_encode(["error" => "Variáveis de ambiente SUPABASE_URL ou SUPABASE_KEY não definidas"]);
    exit;
}

// Busca todos os produtos
$url = $SUPABASE_URL . "/rest/v1/tbl_produto?select=*";

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

if ($httpCode >= 400 || !$response) {
    echo json_encode(["error" => "Erro ao buscar produtos", "status" => $httpCode, "response" => $response]);
    exit;
}

$produtos = json_decode($response, true);
if (!$produtos) {
    echo json_encode(["error" => "Resposta inválida do Supabase"]);
    exit;
}

// Pega 1 produto de cada categoria
$destaques = [];
$categoriasSelecionadas = [];

foreach ($produtos as $produto) {
    $cat = $produto['categoria'] ?? null;
    if ($cat && !in_array($cat, $categoriasSelecionadas)) {
        $destaques[] = $produto;
        $categoriasSelecionadas[] = $cat;
    }
    if (count($destaques) >= 6) break; // pega só 6
}

echo json_encode($destaques, JSON_UNESCAPED_UNICODE);
?>
