<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . "/index.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Método inválido"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$id_cliente = $data["id_cliente"] ?? null;
$nome = $data["nome"] ?? "";
$sobrenome = $data["sobrenome"] ?? "";
$senha = $data["senha"] ?? null;

if (!$id_cliente) {
    echo json_encode(["success" => false, "message" => "ID do cliente não informado"]);
    exit();
}

// Construir corpo do update
$updateBody = [
    "nome" => $nome,
    "sobrenome" => $sobrenome
];

if ($senha && strlen($senha) > 0) {
    $updateBody["senha"] = $senha;
}

// 1) ATUALIZAR CLIENTE NO SUPABASE
$response = supabase(
    "PATCH",
    "tbl_cliente?id_cliente=eq.$id_cliente",
    $updateBody
);

if ($response["status"] >= 400) {
    echo json_encode([
        "success" => false,
        "message" => "Erro ao atualizar perfil",
        "debug" => $response
    ]);
    exit();
}

echo json_encode([
    "success" => true,
    "message" => "Perfil atualizado com sucesso!"
]);
