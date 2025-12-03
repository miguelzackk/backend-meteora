<?php
// ==========================
// CONFIGURAÇÕES INICIAIS
// ==========================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configurações da Supabase
$SUPABASE_URL = "https://ecbgnduxbpgxyajevdgz.supabase.co";
$SUPABASE_KEY = "sb_secret_kusL9WUkSpcaperk1hTgIQ_qhV3Wo4u";

// ==========================
// FUNÇÃO AUXILIAR
// ==========================
function supabase_fetch($url, $apikey) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $apikey",
        "Authorization: Bearer $apikey",
        "Content-Type: application/json",
    ]);

    // ✅ Debug e SSL fix (para Railway)
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);

    if ($response === false) {
        throw new Exception("CURL ERRO: " . curl_error($ch));
    }

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status >= 400) {
        throw new Exception("Supabase retornou código HTTP $status");
    }

    $decoded = json_decode($response, true);
    if ($decoded === null) {
        throw new Exception("Falha ao decodificar JSON da resposta: " . $response);
    }

    return $decoded;
}

// ==========================
// LÓGICA PRINCIPAL
// ==========================
try {
    // 1️⃣ Buscar todos os produtos via Supabase
    $url = $SUPABASE_URL . "/rest/v1/tbl_produto?select=*";
    $produtos = supabase_fetch($url, $SUPABASE_KEY);

    if (empty($produtos)) {
        echo json_encode(['error' => 'Nenhum produto encontrado.']);
        exit;
    }

    // 2️⃣ Agrupar por categoria e pegar o produto com menor id_produto
    $categorias = [];
    foreach ($produtos as $p) {
        $cat = $p['categoria'];
        if (!isset($categorias[$cat]) || $p['id_produto'] < $categorias[$cat]['id_produto']) {
            $categorias[$cat] = $p;
        }
    }

    // 3️⃣ Pegar até 6 produtos únicos
    $resultado = array_slice(array_values($categorias), 0, 6);

    // 4️⃣ Completar até 6, se necessário
    if (count($resultado) < 6) {
        $faltam = 6 - count($resultado);
        $ids_existentes = array_column($resultado, 'id_produto');

        // Adicionar produtos extras diferentes
        foreach ($produtos as $p) {
            if (!in_array($p['id_produto'], $ids_existentes)) {
                $resultado[] = $p;
                $ids_existentes[] = $p['id_produto'];
                if (count($resultado) >= 6) break;
            }
        }

        // Se ainda faltar, repetir produtos existentes
        while (count($resultado) < 6) {
            $resultado[] = $resultado[count($resultado) % max(count($resultado), 1)];
        }
    }

    // 5️⃣ Retornar exatamente 6 produtos no JSON
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_slice($resultado, 0, 6), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar produtos: ' . $e->getMessage()]);
}
?>
