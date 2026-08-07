<?php
// Mostrar erros (modo desenvolvimento)
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
$SUPABASE_URL = getenv("SUPABASE_URL") ?: "https://ecbgnduxbpgxyajevdgz.supabase.co";
$SUPABASE_KEY = getenv("SUPABASE_KEY");

// Função auxiliar genérica
function supabase_categories($method, $endpoint) {
    global $SUPABASE_URL, $SUPABASE_KEY;

    $url = rtrim($SUPABASE_URL, '/') . '/rest/v1/' . ltrim($endpoint, '/');
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $SUPABASE_KEY",
        "Authorization: Bearer $SUPABASE_KEY",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false) {
        echo json_encode(["error" => "CURL error: " . curl_error($ch)]);
        exit();
    }

    curl_close($ch);
    return ["status" => $status, "data" => json_decode($response, true)];
}

// --- EXECUÇÃO PRINCIPAL ---
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Método inválido"]);
    exit();
}

try {
    // Consulta agrupada para simular DISTINCT
    $response = supabase_categories("GET", "tbl_produto?select=categoria&group=categoria");

    if ($response["status"] !== 200) {
        throw new Exception("Erro na API Supabase: " . json_encode($response));
    }

    echo json_encode($response["data"]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erro ao buscar categorias: " . $e->getMessage()]);
}
?>
