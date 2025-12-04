<?php
// Configurações iniciais e CORS
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

// --- CONFIGURAÇÃO SUPABASE ---
$SUPABASE_URL = "https://ecbgnduxbpgxyajevdgz.supabase.co";
$SUPABASE_KEY = "sb_secret_kusL9WUkSpcaperk1hTgIQ_qhV3Wo4u";

// Função genérica para chamadas Supabase
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
    return ["status" => $status, "data" => json_decode($response, true)];
}

// --- Lógica principal da busca ---
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    echo json_encode(["error" => "Método inválido"]);
    exit();
}

$termo = $_GET["q"] ?? "";

// 1️⃣ Se não há termo de busca, retorna todos os produtos
if (trim($termo) === "") {
    $response = supabase("GET", "tbl_produto?select=*");
    http_response_code($response["status"]);
    echo json_encode($response["data"]);
    exit();
}

// 2️⃣ Se há termo, aplica busca com filtros OR e ILIKE
// Importante: urlencode para caracteres especiais
$termoEncoded = rawurlencode($termo);

$endpoint = "tbl_produto?select=*"
    . "&or=(nome.ilike.*{$termoEncoded}*,descricao.ilike.*{$termoEncoded}*,categoria.ilike.*{$termoEncoded}*)";

$response = supabase("GET", $endpoint);

// 3️⃣ Retornar resultado
http_response_code($response["status"]);
echo json_encode($response["data"]);
?>
