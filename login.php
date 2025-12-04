<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

require __DIR__ . "/supabase.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Método inválido"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$email = $data["email"] ?? "";
$senha = $data["senha"] ?? "";

// Validação simples
if (!$email || !$senha) {
    echo json_encode(["success" => false, "message" => "Campos incompletos"]);
    exit();
}

// 1️⃣ Buscar usuário pelo email
$response = supabase(
    "GET",
    "tbl_cliente?select=*&gmail=eq.$email"
);

if ($response["status"] >= 400) {
    echo json_encode(["success" => false, "message" => "Erro ao acessar o banco", "debug" => $response]);
    exit();
}

$usuarios = $response["data"] ?? [];

if (empty($usuarios)) {
    echo json_encode(["success" => false, "message" => "Email ou senha incorretos"]);
    exit();
}

$usuario = $usuarios[0];

// 2️⃣ Comparar senha (sem hash, como você pediu)
if ($senha !== $usuario["senha"]) {
    echo json_encode(["success" => false, "message" => "Email ou senha incorretos"]);
    exit();
}

// 3️⃣ Remover senha antes de retornar
unset($usuario["senha"]);

echo json_encode([
    "success" => true,
    "usuario" => $usuario
]);
