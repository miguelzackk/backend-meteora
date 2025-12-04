<?php
// =====================
// LISTAR HISTÓRICO DE COMPRAS (SUPABASE) - VERSÃO CORRIGIDA
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
// BUSCAR COMPRAS DO CLIENTE
// =====================
error_log("Buscando histórico para cliente ID: " . $id_cliente);

// Primeiro buscar as compras do cliente
$endpointCompras = "tbl_compra?select=id_compra,data_compra,valor_total"
    . "&id_cliente=eq.$id_cliente"
    . "&order=id_compra.desc";

$responseCompras = supabase("GET", $endpointCompras);

if ($responseCompras["status"] >= 400) {
    error_log("Erro ao buscar compras: " . print_r($responseCompras, true));
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "error" => "Erro ao buscar compras", 
        "debug" => $responseCompras
    ]);
    exit();
}

$compras = $responseCompras["data"] ?? [];

error_log("Compras encontradas: " . count($compras));

if (empty($compras)) {
    echo json_encode(["success" => true, "historico" => []]);
    exit();
}

// =====================
// BUSCAR ITENS DE CADA COMPRA
// =====================
$historico = [];

foreach ($compras as $compra) {
    $id_compra = (int)$compra["id_compra"];
    error_log("Processando compra ID: " . $id_compra);
    
    // Buscar itens desta compra
    $endpointItens = "tbl_historico_compra?select=id_produto,quantidade,valor_unitario"
        . "&id_compra=eq.$id_compra";
    
    $responseItens = supabase("GET", $endpointItens);
    $itens = $responseItens["data"] ?? [];
    
    error_log("Itens encontrados para compra $id_compra: " . count($itens));
    
    // Para cada item, buscar detalhes do produto
    $itensDetalhados = [];
    
    foreach ($itens as $item) {
        $id_produto = (int)$item["id_produto"];
        
        // Buscar informações do produto
        $endpointProduto = "tbl_produto?select=nome,imagem"
            . "&id_produto=eq.$id_produto";
        
        $responseProduto = supabase("GET", $endpointProduto);
        $produto = $responseProduto["data"][0] ?? null;
        
        if ($produto) {
            $itensDetalhados[] = [
                "nome_produto" => $produto["nome"] ?? "Produto desconhecido",
                "imagem" => $produto["imagem"] ?? null,
                "quantidade" => (int)$item["quantidade"],
                "valor_unitario" => (float)$item["valor_unitario"]
            ];
        } else {
            // Se o produto não foi encontrado, usar dados básicos
            $itensDetalhados[] = [
                "nome_produto" => "Produto #" . $id_produto . " (não encontrado)",
                "imagem" => null,
                "quantidade" => (int)$item["quantidade"],
                "valor_unitario" => (float)$item["valor_unitario"]
            ];
        }
    }
    
    $historico[] = [
        "id_compra" => $id_compra,
        "data_compra" => $compra["data_compra"] ?? "",
        "valor_total" => (float)($compra["valor_total"] ?? 0),
        "itens" => $itensDetalhados
    ];
    
    error_log("Compra $id_compra processada com " . count($itensDetalhados) . " itens");
}

error_log("Histórico final: " . count($historico) . " compras");

echo json_encode([
    "success" => true, 
    "historico" => $historico,
    "debug_info" => [
        "cliente_id" => $id_cliente,
        "total_compras" => count($historico)
    ]
]);