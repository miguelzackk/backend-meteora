<?php
// index.php - Ponto de entrada principal

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

// Incluir a função supabase centralizada
require_once __DIR__ . "/supabase.php";

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);


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


echo json_encode(["message" => "🚀 API do Meteora está online e funcionando!"]);
?>