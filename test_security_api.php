<?php
// Script de prueba para verificar el endpoint de seguridad

$url = 'http://127.0.0.1:8000/api/security/analysis';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_error($ch)) {
    echo "Error de cURL: " . curl_error($ch) . "\n";
} else {
    echo "Código HTTP: " . $httpCode . "\n";
    echo "Respuesta del API:\n";
    echo $response . "\n";
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data) {
            echo "\n=== ANÁLISIS DE SEGURIDAD ===\n";
            echo "Total de vulnerabilidades: " . $data['total_vulnerabilities'] . "\n";
            echo "Estado: " . $data['status'] . "\n";
            echo "Timestamp: " . $data['analysis_timestamp'] . "\n";
            
            if (!empty($data['vulnerabilities'])) {
                echo "\nVulnerabilidades encontradas:\n";
                foreach ($data['vulnerabilities'] as $vuln) {
                    echo "- {$vuln['file']}: {$vuln['type']} ({$vuln['severity']}) - {$vuln['description']}\n";
                }
            } else {
                echo "\n✅ No se encontraron vulnerabilidades. El código está seguro.\n";
            }
        }
    }
}

curl_close($ch);
