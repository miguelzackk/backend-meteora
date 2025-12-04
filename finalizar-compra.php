<?php
// =====================
// FINALIZAR COMPRA VIA SUPABASE
// =====================

ini_set("display_errors", 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

require_once __DIR__ . "/supabase.php";

// =====================
// LER BODY JSON
// =====================
$input = file_get_contents("php://input");
error_log("RAW INPUT: " . $input);
$input = json_decode($input, true);

if (!$input || !isset($input["id_cliente"]) || !isset($input["itens"])) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Dados inválidos."]);
    exit();
}

$id_cliente = (int)$input["id_cliente"];
$itens = $input["itens"];
$valor_total = 0;

// =====================
// 1) CRIAR COMPRA PRINCIPAL
// =====================
$novaCompra = [
    "id_cliente"   => $id_cliente,
    "data_compra"  => date("Y-m-d H:i:s"),
    "valor_total"  => 0
];

// Usar RETURN=REPRESENTATION para obter o registro criado
$resCompra = supabase("POST", "tbl_compra?select=id_compra", $novaCompra);

error_log("RESPOSTA SUPABASE: " . print_r($resCompra, true));

if (!isset($resCompra["status"]) || $resCompra["status"] < 200 || $resCompra["status"] >= 300) {
    echo json_encode(["success" => false, "error" => "Erro ao criar compra", "debug" => $resCompra]);
    exit();
}

// Obter ID da compra
$id_compra = null;

// Tentar diferentes formatos de resposta
if (isset($resCompra["data"]) && is_array($resCompra["data"])) {
    if (!empty($resCompra["data"]) && isset($resCompra["data"][0]["id_compra"])) {
        $id_compra = (int)$resCompra["data"][0]["id_compra"];
    } elseif (isset($resCompra["data"]["id_compra"])) {
        $id_compra = (int)$resCompra["data"]["id_compra"];
    }
} elseif (isset($resCompra["id_compra"])) {
    $id_compra = (int)$resCompra["id_compra"];
}

error_log("ID COMPRA OBTIDO: " . $id_compra);

if (!$id_compra || $id_compra <= 0) {
    echo json_encode([
        "success" => false, 
        "error" => "Falha ao obter ID da compra: " . $id_compra,
        "debug_response" => $resCompra
    ]);
    exit();
}

// =====================
// 2) PROCESSAR ITENS
// =====================
foreach ($itens as $index => $item) {
    $id_produto = (int)$item["id_produto"];
    $quantidade = (int)$item["quantidade"];
    $valor_unitario = (float)$item["preco"];
    $subtotal = $quantidade * $valor_unitario;
    $valor_total += $subtotal;

    // (a) Inserir histórico da compra
    $historico = [
        "id_cliente"    => $id_cliente,
        "id_compra"     => $id_compra,
        "id_produto"    => $id_produto,
        "data_registro" => date("Y-m-d H:i:s"),
        "quantidade"    => $quantidade,
        "valor_unitario"=> $valor_unitario
    ];

    $resHist = supabase("POST", "tbl_historico_compra", $historico);
    
    if ($resHist["status"] >= 400) {
        echo json_encode([
            "success" => false, 
            "error" => "Erro ao registrar item no histórico",
            "item" => $item
        ]);
        exit();
    }

    // (b) Atualizar estoque
    $resProd = supabase("GET", "tbl_produto?select=estoque&id_produto=eq.$id_produto");
    
    if (empty($resProd["data"])) {
        echo json_encode([
            "success" => false, 
            "error" => "Produto não encontrado: $id_produto"
        ]);
        exit();
    }

    $estoqueAtual = (int)$resProd["data"][0]["estoque"];
    $novoEstoque = $estoqueAtual - $quantidade;

    if ($novoEstoque < 0) {
        echo json_encode([
            "success" => false, 
            "error" => "Estoque insuficiente para produto: $id_produto"
        ]);
        exit();
    }

    $resUpdate = supabase("PATCH", "tbl_produto?id_produto=eq.$id_produto", ["estoque" => $novoEstoque]);
    
    if ($resUpdate["status"] >= 400) {
        echo json_encode([
            "success" => false, 
            "error" => "Erro ao atualizar estoque"
        ]);
        exit();
    }
}

// =====================
// 3) ATUALIZAR VALOR TOTAL
// =====================
$updateCompra = supabase("PATCH", "tbl_compra?id_compra=eq.$id_compra", ["valor_total" => $valor_total]);

if ($updateCompra["status"] >= 400) {
    echo json_encode([
        "success" => false, 
        "error" => "Erro ao atualizar valor total"
    ]);
    exit();
}

// =====================
// 4) RESPOSTA FINAL
// =====================
echo json_encode([
    "success" => true,
    "message" => "Compra finalizada com sucesso!",
    "id_compra" => $id_compra,
    "valor_total" => $valor_total,
    "quantidade_itens" => count($itens)
]);
?>