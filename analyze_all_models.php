<?php

require __DIR__ . '/vendor/autoload.php';

// Simular el endpoint para ver qué detecta
$vulnerabilities = [];
$personaPath = __DIR__ . '/app/Models/persona.php';

// Verificar Persona.php específicamente
if (file_exists($personaPath)) {
    $content = file_get_contents($personaPath);
    $hasHiddenProperty = preg_match('/protected\s+\$hidden\s*=/', $content);
    
    if (!$hasHiddenProperty) {
        $vulnerabilities[] = [
            'file' => 'app/Models/Persona.php',
            'type' => 'Data Exposure',
            'severity' => 'Medium',
            'description' => 'Campos sensibles sin $hidden'
        ];
    } else {
        echo "✅ Persona.php: Tiene \$hidden correctamente configurado\n";
    }
}

// Analizar todos los demás modelos
$modelsPath = __DIR__ . '/app/Models';
$modelFiles = glob($modelsPath . '/*.php');

echo "=== ANÁLISIS DE TODOS LOS MODELOS ===\n";

foreach ($modelFiles as $modelFile) {
    $modelName = basename($modelFile, '.php');
    $modelContent = file_get_contents($modelFile);
    
    echo "\n--- Analizando: $modelName.php ---\n";
    
    // Verificar si modelos con campos potencialmente sensibles tienen $hidden
    $hasSensitiveKeywords = preg_match('/(email|password|token|secret|key|phone|address|dni|documento|celular|correo|direccion)/i', $modelContent);
    $hasHidden = preg_match('/protected\s+\$hidden\s*=/', $modelContent);
    $hasFillable = preg_match('/protected\s+\$fillable\s*=/', $modelContent);
    
    echo "  - Tiene campos sensibles: " . ($hasSensitiveKeywords ? "✅ SÍ" : "❌ NO") . "\n";
    echo "  - Tiene \$hidden: " . ($hasHidden ? "✅ SÍ" : "❌ NO") . "\n";
    echo "  - Tiene \$fillable: " . ($hasFillable ? "✅ SÍ" : "❌ NO") . "\n";
    
    if ($hasSensitiveKeywords && !$hasHidden) {
        $vulnerabilities[] = [
            'file' => 'app/Models/' . $modelName . '.php',
            'type' => 'Potential Data Exposure',
            'severity' => 'Low',
            'description' => 'Modelo con campos potencialmente sensibles sin $hidden'
        ];
        echo "  ⚠️ VULNERABILIDAD DETECTADA\n";
    } else {
        echo "  ✅ SEGURO\n";
    }
}

echo "\n=== RESUMEN FINAL ===\n";
echo "Total de vulnerabilidades encontradas: " . count($vulnerabilities) . "\n";

if (count($vulnerabilities) > 0) {
    echo "\nVulnerabilidades detectadas:\n";
    foreach ($vulnerabilities as $vuln) {
        echo "- {$vuln['file']}: {$vuln['type']} ({$vuln['severity']}) - {$vuln['description']}\n";
    }
} else {
    echo "\n✅ No se encontraron vulnerabilidades. Todos los modelos están seguros.\n";
}
