<?php
// Exibir erros em modo debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Lidar com requisições pré-flight (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --- CONFIGURAÇÃO SUPABASE ---
$SUPABASE_URL = "https://ecbgnduxbpgxyajevdgz.supabase.co";
$SUPABASE_KEY = "sb_secret_kusL9WUkSpcaperk1hTgIQ_qhV3Wo4u";

// Função auxiliar de comunicação com Supabase
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

// --- BLOCO PRINCIPAL ---
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Método inválido"]);
    exit();
}

// Lê JSON enviado
$data = json_decode(file_get_contents("php://input"), true);

$id_cliente = $data["id_cliente"] ?? null;
$nome = trim($data["nome"] ?? "");
$sobrenome = trim($data["sobrenome"] ?? "");
$senha = $data["senha"] ?? null;

// Validação
if (!$id_cliente) {
    echo json_encode(["success" => false, "message" => "ID do cliente não informado"]);
    exit();
}

// Montar corpo da atualização
$updateBody = [
    "nome" => $nome,
    "sobrenome" => $sobrenome
];

// Atualiza senha apenas se informada
if ($senha && strlen($senha) > 0) {
    // 🔒 Aqui você pode aplicar hash se quiser armazenar senhas seguras
    // $updateBody["senha"] = password_hash($senha, PASSWORD_DEFAULT);
    $updateBody["senha"] = $senha;
}

// 1️⃣ Atualizar cliente no Supabase
$response = supabase(
    "PATCH",
    "tbl_cliente?id_cliente=eq.$id_cliente",
    $updateBody
);

// 2️⃣ Verificar resultado
if ($response["status"] >= 400) {
    echo json_encode([
        "success" => false,
        "message" => "Erro ao atualizar perfil",
        "debug" => $response
    ]);
    exit();
}

// 3️⃣ Retorno final
echo json_encode([
    "success" => true,
    "message" => "Perfil atualizado com sucesso!"
]);
?>
