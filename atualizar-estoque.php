<?php
// Mostrar erros para debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(["ok" => true]);
    exit();
}

// Configuração da Supabase
$SUPABASE_URL = "https://ecbgnduxbpgxyajevdgz.supabase.co";
$SUPABASE_KEY = "sb_secret_kusL9WUkSpcaperk1hTgIQ_qhV3Wo4u";

// Função auxiliar genérica para chamadas HTTP na API do Supabase
function supabase($method, $endpoint, $body = null) {
    global $SUPABASE_URL, $SUPABASE_KEY;

    $url = rtrim($SUPABASE_URL, '/') . '/rest/v1/' . ltrim($endpoint, '/');

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $SUPABASE_KEY",
        "Authorization: Bearer $SUPABASE_KEY",
        "Content-Type: application/json",
        "Prefer: return=representation"
    ]);

    if ($body) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false) {
        return ["status" => 500, "data" => ["error" => curl_error($ch)]];
    }

    curl_close($ch);

    return [
        "status" => $status,
        "data" => json_decode($response, true)
    ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método inválido']);
    exit();
}

// Ler corpo JSON enviado pelo fetch
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['id_produto']) || !isset($data['quantidade'])) {
    echo json_encode(['error' => 'Parâmetros inválidos']);
    exit();
}

$id_produto = (int)$data['id_produto'];
$quantidade = (int)$data['quantidade'];

// 1️⃣ Buscar o produto no Supabase
$response = supabase("GET", "tbl_produto?id_produto=eq.$id_produto&select=estoque,id_produto");

if ($response["status"] !== 200 || empty($response["data"])) {
    echo json_encode(['error' => 'Produto não encontrado']);
    exit();
}

$estoqueAtual = (int)$response["data"][0]["estoque"];
$novoEstoque = $estoqueAtual - $quantidade;

if ($novoEstoque < 0) {
    echo json_encode([
        'error' => 'Estoque insuficiente',
        'estoque_atual' => $estoqueAtual
    ]);
    exit();
}

// 2️⃣ Atualizar estoque no Supabase
$update = supabase("PATCH", "tbl_produto?id_produto=eq.$id_produto", ["estoque" => $novoEstoque]);

if ($update["status"] >= 400) {
    echo json_encode(['error' => 'Erro ao atualizar estoque']);
    exit();
}

// 3️⃣ Retorno final
echo json_encode([
    'success' => true,
    'id_produto' => $id_produto,
    'estoque_antigo' => $estoqueAtual,
    'novo_estoque' => $novoEstoque
]);
?>
