<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


require_once __DIR__ . "/supabase.php";


if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Método inválido"]);
    exit();
}

$input = file_get_contents("php://input");
error_log("Dados recebidos: " . $input);
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(["success" => false, "message" => "JSON inválido"]);
    exit();
}

$id_cliente = $data["id_cliente"] ?? null;
$nome = trim($data["nome"] ?? "");
$sobrenome = trim($data["sobrenome"] ?? "");
$senha = $data["senha"] ?? null;


if (!$id_cliente) {
    echo json_encode(["success" => false, "message" => "ID do cliente não informado"]);
    exit();
}


$updateBody = [
    "nome" => $nome,
    "sobrenome" => $sobrenome
];


if ($senha && strlen($senha) > 0) {
    $updateBody["senha"] = $senha; // Senha em texto puro
}

error_log("Atualizando cliente $id_cliente com dados: " . print_r($updateBody, true));


$response = supabase(
    "PATCH",
    "tbl_cliente?id_cliente=eq.$id_cliente",
    $updateBody
);

error_log("Resposta Supabase: " . print_r($response, true));


if ($response["status"] >= 400) {
    echo json_encode([
        "success" => false,
        "message" => "Erro ao atualizar perfil",
        "error" => $response["data"]["message"] ?? "Erro desconhecido",
        "status_code" => $response["status"]
    ]);
    exit();
}


echo json_encode([
    "success" => true,
    "message" => "Perfil atualizado com sucesso!",
    "cliente" => [
        "id_cliente" => $id_cliente,
        "nome" => $nome,
        "sobrenome" => $sobrenome
    ]
]);
?>