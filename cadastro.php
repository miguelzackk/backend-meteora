<?php
// Mostrar erros durante o desenvolvimento
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

require __DIR__ . "/supabase.php";


// --- PROCESSAMENTO PRINCIPAL ---
try {
    // Ler o JSON recebido
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if ($data === null) {
        throw new Exception("JSON inválido");
    }

    // Obter dados
    $nome = trim($data["nome"] ?? "");
    $sobrenome = trim($data["sobrenome"] ?? "");
    $gmail = trim($data["email"] ?? ""); // frontend usa "email", banco usa "gmail"
    $senha = $data["senha"] ?? "";

    // Validação básica
    if (!$nome || !$sobrenome || !$gmail || !$senha) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Todos os campos são obrigatórios"
        ]);
        exit();
    }

    if (!filter_var($gmail, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Email inválido"
        ]);
        exit();
    }

    if (strlen($senha) < 6) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Senha deve ter pelo menos 6 caracteres"
        ]);
        exit();
    }

    // Criptografar senha
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    // 1️⃣ Verificar se já existe cliente com este email
    $check = supabase("GET", "tbl_cliente?gmail=eq.$gmail&select=id_cliente");
    
    if ($check["status"] === 200 && is_array($check["data"]) && count($check["data"]) > 0) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "message" => "Este email já está cadastrado"
        ]);
        exit();
    }

    // 2️⃣ Inserir novo cliente
    $insert = supabase("POST", "tbl_cliente", [
        "nome" => $nome,
        "sobrenome" => $sobrenome,
        "gmail" => $gmail,
        "senha" => $senhaHash
    ]);

    if ($insert["status"] >= 200 && $insert["status"] < 300) {
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Conta criada com sucesso!"
        ]);
    } else {
        error_log("Erro Supabase: " . print_r($insert, true));
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Erro ao criar conta no banco de dados",
            "debug" => $insert["data"]
        ]);
    }

} catch (Exception $e) {
    error_log("Erro no cadastro: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erro interno do servidor: " . $e->getMessage()
    ]);
}
?>