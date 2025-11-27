<?php
include 'database.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $email = $data['email'] ?? '';
    $senha = $data['senha'] ?? '';
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM tbl_cliente WHERE gmail = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario && $senha === $usuario['senha']) { // Em produção, use password_hash()
            unset($usuario['senha']); // Remove a senha da resposta
            echo json_encode([
                'success' => true,
                'usuario' => $usuario
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Email ou senha incorretos'
            ]);
        }
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro no servidor'
        ]);
    }
}
?>