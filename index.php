<?php
// Carrega variáveis do .env (somente local; no Railway já vem carregado automaticamente)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// -----------------------------
// VARIÁVEIS DO SUPABASE
// -----------------------------
$SUPABASE_URL  = $_ENV['SUPABASE_URL']  ?? "";
$SUPABASE_KEY  = $_ENV['SUPABASE_KEY']  ?? "";

if (!$SUPABASE_URL || !$SUPABASE_KEY) {
    die("<h3>❌ Erro: SUPABASE_URL ou SUPABASE_KEY não foram definidas no .env</h3>");
}

// -----------------------------
// FUNÇÃO PARA REQUISIÇÕES REST
// -----------------------------
function supabaseGET($table)
{
    global $SUPABASE_URL, $SUPABASE_KEY;

    $url = "$SUPABASE_URL/rest/v1/$table?select=*";

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $SUPABASE_KEY",
        "Authorization: Bearer $SUPABASE_KEY",
        "Content-Type: application/json"
    ]);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) return "Erro CURL: $error";
    return $response;
}

// -----------------------------
// TESTE DE LISTAGEM
// -----------------------------
$resultado = supabaseGET("produtos"); // troque para sua tabela real

echo "<h1>REST do Supabase funcionando! ✔️</h1>";
echo "<h3>Resposta da API:</h3>";
echo "<pre>$resultado</pre>";
