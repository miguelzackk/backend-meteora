<?php
// Usa a função global supabase() definida no index.php

try {
    $res = supabase("GET", "tbl_produto?select=*");

    if ($res["status"] >= 400) {
        throw new Exception("Erro Supabase: HTTP " . $res["status"]);
    }

    $produtos = $res["data"] ?? [];
    if (empty($produtos)) {
        echo json_encode(['error' => 'Nenhum produto encontrado.']);
        exit;
    }

    // Agrupar e selecionar 1 por categoria
    $categorias = [];
    foreach ($produtos as $p) {
        $cat = $p['categoria'];
        if (!isset($categorias[$cat]) || $p['id_produto'] < $categorias[$cat]['id_produto']) {
            $categorias[$cat] = $p;
        }
    }

    $resultado = array_slice(array_values($categorias), 0, 6);

    // Garantir 6 resultados
    if (count($resultado) < 6) {
        $ids_existentes = array_column($resultado, 'id_produto');
        foreach ($produtos as $p) {
            if (!in_array($p['id_produto'], $ids_existentes)) {
                $resultado[] = $p;
                if (count($resultado) >= 6) break;
            }
        }
        while (count($resultado) < 6) {
            $resultado[] = $resultado[count($resultado) % count($resultado)];
        }
    }

    echo json_encode(array_slice($resultado, 0, 6), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode(['error' => 'Erro ao buscar produtos: ' . $e->getMessage()]);
}
