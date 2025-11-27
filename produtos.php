<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

// Responde OPTIONS rapidamente
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

// Lê variáveis de ambiente do Railway
$SUPABASE_URL = $_SERVER['SUPABASE_URL'] ?? '';
$SUPABASE_KEY = $_SERVER['SUPABASE_KEY'] ?? '';

// Se não estiverem definidas, retorna erro
if (!$SUPABASE_URL || !$SUPABASE_KEY) {
    echo json_encode([
        "error" => "Variáveis SUPABASE_URL ou SUPABASE_KEY não definidas no container"
    ]);
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

// Se erro na requisição
if ($httpCode >= 400 || !$response) {
    echo json_encode([
        "error" => "Erro ao buscar produtos",
        "status" => $httpCode,
        "response" => $response
    ]);
    exit;
}

// Decodifica o JSON retornado
$produtos = json_decode($response, true);
if (!$produtos) {
    echo json_encode(["error" => "Resposta inválida do Supabase"]);
    exit;
}

// Seleciona 1 produto de cada categoria (até 6)
$destaques = [];
$categoriasSelecionadas = [];

foreach ($produtos as $produto) {
    $cat = $produto['categoria'] ?? null;
    if ($cat && !in_array($cat, $categoriasSelecionadas)) {
        $destaques[] = $produto;
        $categoriasSelecionadas[] = $cat;
    }
    if (count($destaques) >= 6) break;
}

// Retorna JSON dos destaques
echo json_encode($destaques, JSON_UNESCAPED_UNICODE);
?>
