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

// --- VARIÁVEIS DEFINIDAS DIRETAMENTE PARA TESTE ---
$SUPABASE_URL = "https://ecbgnduxbpgxyajevdgz.supabase.co";  // Sua URL
$SUPABASE_KEY = "sb_secret_kusL9WUkSpcaperk1hTgIQ_qhV3Wo4u";  // Sua chave

// URL da tabela de produtos
$url = $SUPABASE_URL . "/rest/v1/tbl_produto?select=*";

// Inicia cURL
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

// Verifica se houve erro na requisição
if ($httpCode >= 400 || !$response) {
    echo json_encode([
        "error" => "Erro ao buscar produtos",
        "status" => $httpCode,
        "response" => $response
    ]);
    exit;
}

// Decodifica JSON retornado
$produtos = json_decode($response, true);
if (!$produtos) {
    echo json_encode(["error" => "Resposta inválida do Supabase"]);
    exit;
}

// Pega 1 produto de cada categoria (até 6)
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

// Retorna JSON
echo json_encode($destaques, JSON_UNESCAPED_UNICODE);
?>
