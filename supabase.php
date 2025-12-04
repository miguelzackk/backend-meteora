<?php
// supabase.php
$SUPABASE_URL = getenv("SUPABASE_URL") ?: "https://ecbgnduxbpgxyajevdgz.supabase.co";
$SUPABASE_KEY = getenv("SUPABASE_KEY") ?: "sb_secret_kusL9WUkSpcaperk1hTgIQ_qhV3Wo4u";

if (!function_exists('supabase')) {
    function supabase($method, $endpoint, $body = null, $options = [])
    {
        global $SUPABASE_URL, $SUPABASE_KEY;
        
        // Opções padrão
        $defaultOptions = [
            'return_representation' => false
        ];
        $options = array_merge($defaultOptions, $options);
        
        $headers = [
            "apikey: $SUPABASE_KEY",
            "Authorization: Bearer $SUPABASE_KEY",
            "Content-Type: application/json"
        ];
        
        // Adicionar header para retornar representação se solicitado
        if ($options['return_representation']) {
            $headers[] = "Prefer: return=representation";
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "$SUPABASE_URL/rest/v1/$endpoint",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            "status" => $code,
            "data" => json_decode($response, true)
        ];
    }
}