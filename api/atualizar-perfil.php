<?php
include 'database.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Responder requisições OPTIONS (pré-flight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $id_cliente = $data['id_cliente'] ?? null;
    $nome = $data['nome'] ?? '';
    $sobrenome = $data['sobrenome'] ?? '';
    $senha = $data['senha'] ?? null;

    if (!$id_cliente) {
        echo json_encode(["success" => false, "message" => "ID do cliente não informado"]);
        exit;
    }

    try {
        if ($senha) {
            $stmt = $pdo->prepare("UPDATE tbl_cliente SET nome = ?, sobrenome = ?, senha = ? WHERE id_cliente = ?");
            $stmt->execute([$nome, $sobrenome, $senha, $id_cliente]);
        } else {
            $stmt = $pdo->prepare("UPDATE tbl_cliente SET nome = ?, sobrenome = ? WHERE id_cliente = ?");
            $stmt->execute([$nome, $sobrenome, $id_cliente]);
        }

        echo json_encode(["success" => true, "message" => "Perfil atualizado com sucesso!"]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Erro no servidor: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Método inválido"]);
}
