<?php

$host = "aws-1-us-east-2.pooler.supabase.com";
$port = "5432";
$dbname = "postgres";
$user = "postgres.ecbgnduxbpgxyajevdgz";
$password = "Fifa2@Migue";

try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conectou!";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
