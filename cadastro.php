<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ . "/supabase.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Método inválido"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$nome      = $data["nome"]      ?? "";
$sobrenome = $data["sobrenome"] ?? "";
$email     = $data["email"]     ?? "";
$senha     = $data["senha"]     ?? "";

// --- SE QUISER USAR HASH ---
// $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
// --------------------------------
// Se NÃO quiser usar hash (igual você pediu):
$senhaHash = $senha;

// Validação básica
if (!$nome || !$sobrenome || !$email || !$senha) {
    echo json_encode(["success" => false, "message" => "Campos incompletos"]);
    exit();
}

// 1) Verificar se já existe o email
$check = supabase(
    "GET",
    "tbl_cliente?select=id_cliente&gmail=eq.$email"
);

if (!empty($check["data"])) {
    echo json_encode([
        "success" => false,
        "message" => "Email já cadastrado"
    ]);
    exit();
}

// 2) Inserir o cliente
$insert = supabase(
    "POST",
    "tbl_cliente",
    [
        "nome"     => $nome,
        "sobrenome"=> $sobrenome,
        "gmail"    => $email,
        "senha"    => $senhaHash
    ]
);

if ($insert["status"] >= 200 && $insert["status"] < 300) {
    echo json_encode([
        "success" => true,
        "message" => "Conta criada com sucesso!"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Erro ao criar conta",
        "debug"   => $insert
    ]);
}
