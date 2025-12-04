<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

// 🌐 Variáveis do Supabase (Railway)
$SUPABASE_URL = getenv("SUPABASE_URL");
$SUPABASE_KEY = getenv("SUPABASE_KEY");

// 🔧 Função de conexão com Supabase
function supabase($method, $endpoint, $body = null)
{
    global $SUPABASE_URL, $SUPABASE_KEY;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "$SUPABASE_URL/rest/v1/$endpoint",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "apikey: $SUPABASE_KEY",
            "Authorization: Bearer $SUPABASE_KEY",
            "Content-Type: application/json"
        ],
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_SSL_VERIFYPEER => false, // Railway SSL fix
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        "status" => $code,
        "data" => json_decode($response, true)
    ];
}

// 🚦 Roteamento simples
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

// 👉 Rotas específicas
if (str_contains($path, "produtos-categoria")) {
    include "produtos-categoria.php";
    exit;
}

if (preg_match("/produto(\.php)?$/", $path)) {
    include "produto.php";
    exit;
}

if (preg_match("/produtos(\.php)?$/", $path)) {
    include "produtos.php";
    exit;
}

if (preg_match("/categorias(\.php)?$/", $path)) {
    include "categorias.php";
    exit;
}

if (preg_match("/busca(\.php)?$/", $path)) {
    include "busca.php";
    exit;
}

if (preg_match("/cadastro(\.php)?$/", $path)) {
    include "cadastro.php";
    exit;
}

if (preg_match("/login(\.php)?$/", $path)) {
    include "login.php";
    exit;
}

if (preg_match("/atualizar-estoque(\.php)?$/", $path)) {
    include "atualizar-estoque.php";
    exit;
}

if (preg_match("/atualizar-perfil(\.php)?$/", $path)) {
    include "atualizar-perfil.php";
    exit;
}

if (preg_match("/finalizar-compra(\.php)?$/", $path)) {
    include "finalizar-compra.php";
    exit;
}

if (preg_match("/listar-historico(\.php)?$/", $path)) {
    include "listar-historico.php";
    exit;
}

// 🟢 Resposta padrão
echo json_encode(["message" => "🚀 API do Meteora está online e funcionando!"]);
