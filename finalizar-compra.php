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

// Criar compra com return=representation para obter ID
$resCompra = supabase("POST", "tbl_compra?select=id_compra", $novaCompra, [
    'return_representation' => true
]);

error_log("RESPOSTA SUPABASE (criar compra): " . print_r($resCompra, true));

if ($resCompra["status"] !== 201 && $resCompra["status"] !== 200) {
    echo json_encode([
        "success" => false, 
        "error" => "Erro ao criar compra. Status: " . $resCompra["status"],
        "debug" => $resCompra
    ]);
    exit();
}

// Obter ID da compra da resposta
$id_compra = null;

if (isset($resCompra["data"]) && is_array($resCompra["data"]) && !empty($resCompra["data"])) {
    if (isset($resCompra["data"][0]["id_compra"])) {
        $id_compra = (int)$resCompra["data"][0]["id_compra"];
    }
}

error_log("ID COMPRA OBTIDO: " . $id_compra);

if (!$id_compra) {
    // Se não conseguirmos obter o ID da resposta, fazer uma consulta para pegar o último ID
    error_log("Tentando obter último ID da compra via consulta...");
    
    // Consultar a última compra deste cliente
    $resUltimaCompra = supabase("GET", "tbl_compra?id_cliente=eq.$id_cliente&order=id_compra.desc&limit=1");
    
    if (isset($resUltimaCompra["data"]) && is_array($resUltimaCompra["data"]) && !empty($resUltimaCompra["data"])) {
        $id_compra = (int)$resUltimaCompra["data"][0]["id_compra"];
        error_log("ID obtido via consulta: " . $id_compra);
    }
    
    if (!$id_compra) {
        echo json_encode([
            "success" => false, 
            "error" => "Falha ao obter ID da compra",
            "debug_response" => $resCompra,
            "ultima_consulta" => $resUltimaCompra ?? null
        ]);
        exit();
    }
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
            "item" => $item,
            "debug" => $resHist
        ]);
        exit();
    }

    // (b) Atualizar estoque
    $resProd = supabase("GET", "tbl_produto?select=estoque&id_produto=eq.$id_produto");
    
    if (empty($resProd["data"])) {
        echo json_encode([
            "success" => false, 
            "error" => "Produto não encontrado: $id_produto",
            "debug" => $resProd
        ]);
        exit();
    }

    $estoqueAtual = (int)$resProd["data"][0]["estoque"];
    $novoEstoque = $estoqueAtual - $quantidade;

    if ($novoEstoque < 0) {
        echo json_encode([
            "success" => false, 
            "error" => "Estoque insuficiente para produto: $id_produto",
            "estoque_atual" => $estoqueAtual,
            "quantidade" => $quantidade
        ]);
        exit();
    }

    $resUpdate = supabase("PATCH", "tbl_produto?id_produto=eq.$id_produto", ["estoque" => $novoEstoque]);
    
    if ($resUpdate["status"] >= 400) {
        echo json_encode([
            "success" => false, 
            "error" => "Erro ao atualizar estoque",
            "debug" => $resUpdate
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
        "error" => "Erro ao atualizar valor total",
        "debug" => $updateCompra
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