<?php

echo "<h1>Servidor PHP rodando no Railway! ✔️</h1>";

$host = "db.ecbgnduxbpgxyajevdgz.supabase.co";
$port = "5432";
$dbname = "postgres";
$user = "postgres";
$password = "Fifa2@Migue";

try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<p>Conectou ao Supabase com sucesso! ✔️</p>";

} catch (Exception $e) {
    echo "<p>Erro ao conectar: " . $e->getMessage() . "</p>";
}
