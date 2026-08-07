<?php
// Configurações da Supabase
$SUPABASE_URL = getenv("SUPABASE_URL") ?: "https://ecbgnduxbpgxyajevdgz.supabase.co";
$SUPABASE_KEY = getenv("SUPABASE_KEY");

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
    $url = $SUPABASE_URL . "/rest/v1/tbl_produto?select=*";
    $produtos = supabase_fetch($url, $SUPABASE_KEY);

    if (!is_array($produtos) || (isset($produtos["code"]) && isset($produtos["message"]))) {
        throw new Exception("Resposta inválida do Supabase: " . json_encode($produtos));
    }

    if (empty($produtos)) {
        echo json_encode([]);
        exit;
    }

    $categorias = [];
    foreach ($produtos as $p) {
        if (!is_array($p) || !isset($p['categoria'], $p['id_produto'])) {
            continue;
        }
        $cat = $p['categoria'];
        if (!isset($categorias[$cat]) || $p['id_produto'] < $categorias[$cat]['id_produto']) {
            $categorias[$cat] = $p;
        }
    }

    $resultado = array_slice(array_values($categorias), 0, 6);

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


        while (count($resultado) > 0 && count($resultado) < 6) {
            $resultado[] = $resultado[count($resultado) % count($resultado)];
        }
    }


    echo json_encode(array_slice($resultado, 0, 6));

} catch (Exception $e) {
    echo json_encode(['error' => 'Erro ao buscar produtos: ' . $e->getMessage()]);
}
?>
