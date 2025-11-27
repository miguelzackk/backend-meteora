<?php
$SUPABASE_URL = "https://ecbgnduxbpgxyajevdgz.supabase.co";
$SUPABASE_KEY = "sb_secret_kusL9WUkSpcaperk1hTgIQ_qhV3Wo4u";

$url = $SUPABASE_URL . "/rest/v1/tbl_produto?select=*";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "apikey: $SUPABASE_KEY",
    "Authorization: Bearer $SUPABASE_KEY",
    "Content-Type: application/json",
]);
$response = curl_exec($ch);

if ($response === false) {
    echo "CURL ERRO: " . curl_error($ch);
    exit;
}

var_dump($response);
exit;
