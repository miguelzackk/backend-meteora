<?php
// Exibir erros durante o desenvolvimento
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Lidar com requisições CORS pré-flight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --- CONFIGURAÇÃO SUPABASE ---
$SUPABASE_URL = "https://ecbgnduxbpgxyajevdgz.supabase.co";
$SUPABASE_KEY = "sb_secret_kusL9WUkSpcaperk1hTgIQ_qhV3Wo4u";

// Função genérica para chamadas Supabase
function supabase($method, $endpoint, $body = null) {
    global $SUPABASE_URL, $SUPABASE_KEY;

    $url = rtrim($SUPABASE_URL, '/') . '/rest/v1/' . ltrim($endpoint, '/');
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $SUPABASE_KEY",
        "Authorization: Bearer $SUPABASE_KEY",
        "Content-Type: application/json",
        "Prefer: return=representation"
    ]);

    if ($body) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false) {
        return ["status" => 500, "data" => ["error" => curl_error($ch)]];
    }

    curl_close($ch);
    return ["status" => $status, "data" => json_decode($response, true)];
}

// --- PROCESSAMENTO PRINCIPAL ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método inválido"]);
    exit();
}

// Ler JSON enviado
$data = json_decode(file_get_contents('php://input'), true);

$nome       = trim($data["nome"] ?? "");
$sobrenome  = trim($data["sobrenome"] ?? "");
$email      = trim($data["email"] ?? "");
$senha      = $data["senha"] ?? "";

// Caso deseje aplicar hash nas senhas:
$senhaHash = $senha; // ou use password_hash($senha, PASSWORD_DEFAULT);

if (!$nome || !$sobrenome || !$email || !$senha) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Campos incompletos"]);
    exit();
}

// 1️⃣ Verificar se o e-mail já existe
$emailEncoded = rawurlencode($email);
$check = supabase("GET", "tbl_cliente?select=id_cliente&gmail=eq.$emailEncoded");

if (!empty($check["data"])) {
    http_response_code(409);
    echo json_encode([
        "success" => false,
        "message" => "Email já cadastrado"
    ]);
    exit();
}

// 2️⃣ Inserir novo cliente
$insert = supabase("POST", "tbl_cliente", [
    "nome"       => $nome,
    "sobrenome"  => $sobrenome,
    "gmail"      => $email,
    "senha"      => $senhaHash
]);

if ($insert["status"] >= 200 && $insert["status"] < 300) {
    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "Conta criada com sucesso!"
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erro ao criar conta",
        "debug"   => $insert
    ]);
}
?>
