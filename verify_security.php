<?php

// Verificar directamente si Persona.php tiene $hidden
echo "=== VERIFICACIÓN DIRECTA DE SEGURIDAD ===\n";

$personaPath = __DIR__ . '/app/Models/persona.php';

if (!file_exists($personaPath)) {
    echo "❌ Error: Archivo Persona.php no encontrado en: $personaPath\n";
    exit(1);
}

$content = file_get_contents($personaPath);

// Verificar si tiene la propiedad $hidden
$hasHiddenProperty = preg_match('/protected\s+\$hidden\s*=/', $content);

echo "Archivo: $personaPath\n";
echo "Tiene \$hidden: " . ($hasHiddenProperty ? "✅ SÍ" : "❌ NO") . "\n";

if ($hasHiddenProperty) {
    // Extraer los campos definidos en $hidden
    if (preg_match('/protected\s+\$hidden\s*=\s*\[(.*?)\]/s', $content, $matches)) {
        $hiddenContent = $matches[1];
        echo "Campos protegidos encontrados:\n";
        
        $sensitiveFields = ['documento', 'celular', 'correo', 'direccion'];
        $missingFields = [];
        
        foreach ($sensitiveFields as $field) {
            $found = (strpos($hiddenContent, "'$field'") !== false || strpos($hiddenContent, "\"$field\"") !== false);
            echo "  - $field: " . ($found ? "✅ Protegido" : "❌ Faltante") . "\n";
            if (!$found) {
                $missingFields[] = $field;
            }
        }
        
        if (empty($missingFields)) {
            echo "\n✅ RESULTADO: NO SE ENCONTRARON VULNERABILIDADES\n";
            echo "✅ Todos los campos sensibles están correctamente protegidos\n";
        } else {
            echo "\n⚠️ RESULTADO: VULNERABILIDADES PARCIALES\n";
            echo "⚠️ Campos faltantes en \$hidden: " . implode(', ', $missingFields) . "\n";
        }
    }
} else {
    echo "\n❌ RESULTADO: VULNERABILIDAD DETECTADA\n";
    echo "❌ El modelo Persona no tiene la propiedad \$hidden definida\n";
    echo "❌ Tipo: Data Exposure - Campos sensibles sin \$hidden\n";
}

echo "\n=== VERIFICACIÓN COMPLETADA ===\n";
