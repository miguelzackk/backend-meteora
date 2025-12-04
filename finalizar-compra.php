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
$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input["id_cliente"]) || !isset($input["itens"])) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Dados inválidos."]);
    exit();
}

$id_cliente = (int)$input["id_cliente"];
$itens = $input["itens"];
$valor_total = 0;

// DEBUG: Log dos dados recebidos
error_log("Dados recebidos: " . print_r($input, true));

// =====================
// 1) CRIAR COMPRA PRINCIPAL
// =====================
$novaCompra = [
    "id_cliente"   => $id_cliente,
    "data_compra"  => date("Y-m-d H:i:s"),
    "valor_total"  => 0
];

error_log("Criando compra com dados: " . print_r($novaCompra, true));
$resCompra = supabase("POST", "tbl_compra", $novaCompra);

error_log("Resposta da criação da compra: " . print_r($resCompra, true));

if ($resCompra["status"] < 200 || $resCompra["status"] >= 300) {
    echo json_encode(["success" => false, "error" => "Erro ao criar compra", "debug" => $resCompra]);
    exit();
}

// Obter ID da compra - o Supabase pode retornar de diferentes formas
$id_compra = null;

if (isset($resCompra["data"]) && is_array($resCompra["data"]) && count($resCompra["data"]) > 0) {
    // Formato mais comum: array de objetos
    if (isset($resCompra["data"][0]["id_compra"])) {
        $id_compra = $resCompra["data"][0]["id_compra"];
    } elseif (isset($resCompra["data"]["id_compra"])) {
        // Se for um objeto direto
        $id_compra = $resCompra["data"]["id_compra"];
    }
} elseif (isset($resCompra["id_compra"])) {
    // Se o ID estiver na raiz da resposta
    $id_compra = $resCompra["id_compra"];
}

error_log("ID da compra obtido: " . $id_compra);

if (!$id_compra) {
    echo json_encode([
        "success" => false, 
        "error" => "Falha ao obter ID da compra", 
        "debug" => [
            "resposta_completa" => $resCompra,
            "dados_enviados" => $novaCompra
        ]
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

    error_log("Processando item $index: Produto $id_produto, Quantidade $quantidade, Valor R$ $valor_unitario");

    // (a) Inserir histórico da compra
    $historico = [
        "id_cliente"    => $id_cliente,
        "id_compra"     => $id_compra,
        "id_produto"    => $id_produto,
        "data_registro" => date("Y-m-d H:i:s"),
        "quantidade"    => $quantidade,
        "valor_unitario"=> $valor_unitario
    ];

    error_log("Registrando histórico: " . print_r($historico, true));
    $resHist = supabase("POST", "tbl_historico_compra", $historico);
    
    if ($resHist["status"] >= 400) {
        echo json_encode([
            "success" => false, 
            "error" => "Erro ao registrar item", 
            "debug" => $resHist,
            "item" => $item
        ]);
        exit();
    }

    // (b) Atualizar estoque
    error_log("Buscando estoque do produto $id_produto");
    $resProd = supabase("GET", "tbl_produto?select=estoque&id_produto=eq.$id_produto");
    
    if (empty($resProd["data"])) {
        echo json_encode([
            "success" => false, 
            "error" => "Produto não encontrado",
            "id_produto" => $id_produto
        ]);
        exit();
    }

    $estoqueAtual = (int)$resProd["data"][0]["estoque"];
    $novoEstoque = $estoqueAtual - $quantidade;

    error_log("Estoque atual: $estoqueAtual, Novo estoque: $novoEstoque");

    if ($novoEstoque < 0) {
        echo json_encode([
            "success" => false, 
            "error" => "Estoque insuficiente", 
            "produto" => $id_produto,
            "estoque_atual" => $estoqueAtual,
            "quantidade_solicitada" => $quantidade
        ]);
        exit();
    }

    error_log("Atualizando estoque do produto $id_produto para $novoEstoque");
    $resUpdate = supabase("PATCH", "tbl_produto?id_produto=eq.$id_produto", ["estoque" => $novoEstoque]);
    
    if ($resUpdate["status"] >= 400) {
        echo json_encode([
            "success" => false, 
            "error" => "Erro ao atualizar estoque", 
            "debug" => $resUpdate,
            "produto" => $id_produto
        ]);
        exit();
    }
    
    error_log("Estoque atualizado com sucesso para produto $id_produto");
}

// =====================
// 3) ATUALIZAR VALOR TOTAL
// =====================
error_log("Atualizando valor total da compra $id_compra para R$ $valor_total");
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
    "message" => "Compra registrada com sucesso!",
    "id_compra" => $id_compra,
    "valor_total" => $valor_total,
    "quantidade_itens" => count($itens)
]);
?>