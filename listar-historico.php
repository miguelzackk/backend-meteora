<?php
// =====================
// LISTAR HISTÓRICO DE COMPRAS (SUPABASE)
// =====================

ini_set("display_errors", 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

require_once __DIR__ . "/supabase.php";

// =====================
// Parâmetro: id_cliente
// =====================
$id_cliente = isset($_GET["id_cliente"]) ? (int)$_GET["id_cliente"] : 0;

if ($id_cliente <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "ID do cliente inválido."]);
    exit();
}

// =====================
// Buscar histórico no Supabase
// =====================
// Aqui usamos a sintaxe `select` com relacionamentos, que funciona no Supabase.
// Ela permite retornar os dados de várias tabelas (JOIN implícito).
// Veja a estrutura abaixo: 
//   tbl_compra -> tbl_historico_compra -> tbl_produto

$endpoint = "tbl_compra?select=id_compra,data_compra,valor_total,"
    . "tbl_historico_compra(id_produto,quantidade,valor_unitario,"
    . "tbl_produto(nome,imagem))"
    . "&id_cliente=eq.$id_cliente"
    . "&order=data_compra.desc";

$response = supabase("GET", $endpoint);

if ($response["status"] >= 400) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Erro ao buscar histórico", "debug" => $response]);
    exit();
}

$data = $response["data"] ?? [];

if (empty($data)) {
    echo json_encode(["success" => true, "historico" => []]);
    exit();
}

// =====================
// Reorganizar estrutura (igual seu JSON antigo)
// =====================
$historico = [];

foreach ($data as $compra) {
    $compraItem = [
        "id_compra" => $compra["id_compra"],
        "data_compra" => $compra["data_compra"],
        "valor_total" => (float)$compra["valor_total"],
        "itens" => []
    ];

    if (!empty($compra["tbl_historico_compra"])) {
        foreach ($compra["tbl_historico_compra"] as $hist) {
            $prod = $hist["tbl_produto"] ?? [];

            $compraItem["itens"][] = [
                "nome_produto" => $prod["nome"] ?? "Produto removido",
                "imagem" => $prod["imagem"] ?? null,
                "quantidade" => (int)$hist["quantidade"],
                "valor_unitario" => (float)$hist["valor_unitario"]
            ];
        }
    }

    $historico[] = $compraItem;
}

echo json_encode(["success" => true, "historico" => $historico]);
