<?php
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $nome = $data['nome'] ?? '';
    $sobrenome = $data['sobrenome'] ?? '';
    $email = $data['email'] ?? '';
    $senha = $data['senha'] ?? '';
    
    try {
        // Verificar se email já existe
        $stmt = $pdo->prepare("SELECT id_cliente FROM tbl_cliente WHERE gmail = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            echo json_encode([
                'success' => false,
                'message' => 'Email já cadastrado'
            ]);
            return;
        }
        
        // Inserir novo usuário
        $stmt = $pdo->prepare("INSERT INTO tbl_cliente (nome, sobrenome, gmail, senha) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nome, $sobrenome, $email, $senha]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Conta criada com sucesso'
        ]);
        
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao criar conta: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido'
    ]);
}
?>