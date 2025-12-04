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

// --- CONFIGURAÇÃO SUPABASE ---
$SUPABASE_URL = "https://ecbgnduxbpgxyajevdgz.supabase.co";
$SUPABASE_KEY = "sb_secret_kusL9WUkSpcaperk1hTgIQ_qhV3Wo4u";

// Função genérica para chamadas ao Supabase
function supabase($method, $endpoint, $body = null)
{
    global $SUPABASE_URL, $SUPABASE_KEY;

    $url = rtrim($SUPABASE_URL, '/') . '/rest/v1/' . ltrim($endpoint, '/');
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            "apikey: $SUPABASE_KEY",
            "Authorization: Bearer $SUPABASE_KEY",
            "Content-Type: application/json",
            "Prefer: return=representation"
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
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

// Ler o JSON recebido
$data = json_decode(file_get_contents('php://input'), true);

// ✅ Corrigido: front envia "email", banco usa "gmail"
$nome = trim($data["nome"] ?? "");
$sobrenome = trim($data["sobrenome"] ?? "");
$gmail = trim($data["email"] ?? ""); // compatível com frontend
$senha = $data["senha"] ?? "";

// 🔐 (opcional) Criptografar senha
$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

// 🚫 Validação de campos obrigatórios
if (!$nome || !$sobrenome || !$gmail || !$senha) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Campos incompletos"]);
    exit();
}

// 1️⃣ Verificar se já existe cliente com este gmail
$gmailEncoded = rawurlencode($gmail);
$check = supabase("GET", "tbl_cliente?select=id_cliente&gmail=eq.$gmailEncoded");

if (isset($check["data"]) && is_array($check["data"]) && count($check["data"]) > 0) {
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
    "gmail" => $gmail, // mantém nome da coluna do banco
    "senha" => $senhaHash
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
        "debug" => $insert // para visualizar erro nos logs do Railway
    ]);
}
?>
