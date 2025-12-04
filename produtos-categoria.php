<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

require __DIR__ . "/supabase.php";

// Verifica se a categoria foi passada
if (!isset($_GET["categoria"]) || empty(trim($_GET["categoria"]))) {
    echo json_encode(["error" => "Categoria não especificada"]);
    exit();
}

$categoria = trim($_GET["categoria"]);

// 🔍 Usar `ilike.*valor*` — o formato correto do Supabase
$categoriaFiltro = rawurlencode("*" . $categoria . "*");

// Monta o endpoint completo
$endpoint = "tbl_produto?select=*&categoria=ilike.$categoriaFiltro&order=id_produto.asc";

// Faz a requisição
$response = supabase("GET", $endpoint);

// Verifica a resposta
if (!isset($response["data"]) || !is_array($response["data"])) {
    echo json_encode(["error" => "Erro ao buscar produtos", "debug" => $response]);
    exit();
}

// Nenhum produto encontrado
if (empty($response["data"])) {
    echo json_encode(["warning" => "Nenhum produto encontrado nesta categoria"]);
    exit();
}

// ✅ Retorna produtos encontrados
echo json_encode($response["data"], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
