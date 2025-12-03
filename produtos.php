<?php
// Configurações da Supabase
$SUPABASE_URL = "https://ecbgnduxbpgxyajevdgz.supabase.co";
$SUPABASE_KEY = "sb_secret_kusL9WUkSpcaperk1hTgIQ_qhV3Wo4u";

// Função auxiliar para chamada de API Supabase
function supabase_fetch($url, $apikey) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $apikey",
        "Authorization: Bearer $apikey",
        "Content-Type: application/json",
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        throw new Exception("CURL ERRO: " . curl_error($ch));
    }
    curl_close($ch);
    return json_decode($response, true);
}

try {
    // 1️⃣ Buscar todos os produtos
    $url = $SUPABASE_URL . "/rest/v1/tbl_produto?select=*";
    $produtos = supabase_fetch($url, $SUPABASE_KEY);

    if (empty($produtos)) {
        echo json_encode([]);
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

    // 3️⃣ Pegar os primeiros até 6
    $resultado = array_slice(array_values($categorias), 0, 6);

    // 4️⃣ Se tiver menos de 6, preencher com produtos extras (ou repetir se necessário)
    if (count($resultado) < 6) {
        $faltam = 6 - count($resultado);
        $ids_existentes = array_column($resultado, 'id_produto');

        // Adicionar produtos novos, se existirem
        foreach ($produtos as $p) {
            if (!in_array($p['id_produto'], $ids_existentes)) {
                $resultado[] = $p;
                $ids_existentes[] = $p['id_produto'];
                if (count($resultado) >= 6) break;
            }
        }

        // Se ainda tiver menos de 6, repetir produtos existentes
        while (count($resultado) < 6) {
            $resultado[] = $resultado[count($resultado) % count($resultado)];
        }
    }

    // 5️⃣ Retornar exatamente 6 produtos no JSON
    echo json_encode(array_slice($resultado, 0, 6));

} catch (Exception $e) {
    echo json_encode(['error' => 'Erro ao buscar produtos: ' . $e->getMessage()]);
}
?>
