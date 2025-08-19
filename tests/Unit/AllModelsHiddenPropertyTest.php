<?php

namespace Tests\Unit;

use Tests\TestCase;
use ReflectionClass;

class AllModelsHiddenPropertyTest extends TestCase
{
    /**
     * Test que verifica TODOS los modelos en el sistema
     */
    public function test_all_models_hidden_property_analysis()
    {
        $modelsPath = app_path('Models');
        $modelFiles = glob($modelsPath . '/*.php');
        
        $results = [];
        
        foreach ($modelFiles as $modelFile) {
            $modelName = basename($modelFile, '.php');
            $className = "App\\Models\\$modelName";
            
            if (!class_exists($className)) {
                continue;
            }
            
            $model = new $className();
            $reflection = new ReflectionClass($model);
            
            $result = [
                'model' => $modelName,
                'has_hidden' => false,
                'hidden_fields' => [],
                'hidden_count' => 0,
                'status' => 'unknown'
            ];
            
            if ($reflection->hasProperty('hidden')) {
                $hiddenProperty = $reflection->getProperty('hidden');
                $hiddenProperty->setAccessible(true);
                $hiddenFields = $hiddenProperty->getValue($model);
                
                $result['has_hidden'] = true;
                $result['hidden_fields'] = $hiddenFields ?? [];
                $result['hidden_count'] = count($result['hidden_fields']);
                
                if ($result['hidden_count'] > 0) {
                    $result['status'] = 'secure';
                } else {
                    $result['status'] = 'empty_hidden';
                }
            } else {
                $result['status'] = 'no_hidden';
            }
            
            $results[] = $result;
        }
        
        // Mostrar resultados
        echo "\n=== ANÁLISIS COMPLETO DE MODELOS ===\n";
        
        $secure = $empty = $missing = 0;
        
        foreach ($results as $result) {
            $status_icon = match($result['status']) {
                'secure' => '✅',
                'empty_hidden' => '⚠️',
                'no_hidden' => '❌',
                default => '❓'
            };
            
            echo sprintf(
                "%s %s: %s (%d campos ocultos)\n",
                $status_icon,
                $result['model'],
                $result['status'],
                $result['hidden_count']
            );
            
            if (!empty($result['hidden_fields'])) {
                echo "    Campos: " . implode(', ', $result['hidden_fields']) . "\n";
            }
            
            match($result['status']) {
                'secure' => $secure++,
                'empty_hidden' => $empty++,
                'no_hidden' => $missing++,
                default => null
            };
        }
        
        echo "\n=== RESUMEN ===\n";
        echo "✅ Modelos seguros: $secure\n";
        echo "⚠️ Modelos con \$hidden vacío: $empty\n";
        echo "❌ Modelos sin \$hidden: $missing\n";
        echo "📊 Total de modelos: " . count($results) . "\n";
        
        // Assertions
        $this->assertGreaterThan(0, $secure, 'Debe haber al menos algunos modelos seguros');
        
        // El test pasa independientemente, solo reporta el estado
        $this->assertTrue(true, 'Análisis completado - revisar output para detalles');
    }
    
    /**
     * Test específico para validar que Persona está corregido
     */
    public function test_persona_specifically_is_secure()
    {
        $persona = new \App\Models\Persona();
        $reflection = new ReflectionClass($persona);
        
        // Debe tener $hidden
        $this->assertTrue($reflection->hasProperty('hidden'), 
            'Persona debe tener la propiedad $hidden');
        
        $hiddenProperty = $reflection->getProperty('hidden');
        $hiddenProperty->setAccessible(true);
        $hiddenFields = $hiddenProperty->getValue($persona);
        
        // Debe tener campos específicos
        $expectedFields = ['documento', 'celular', 'correo', 'direccion'];
        
        foreach ($expectedFields as $field) {
            $this->assertContains($field, $hiddenFields, 
                "Persona debe ocultar el campo '$field'");
        }
        
        echo "\n✅ PERSONA VALIDADO: " . implode(', ', $hiddenFields);
    }
    
    /**
     * Test que valida serialización de Persona no expone datos sensibles
     */
    public function test_persona_json_serialization_security()
    {
        $persona = new \App\Models\Persona([
            'tipo_documento' => 'DNI',
            'documento' => '12345678',
            'nombres' => 'Juan',
            'apellido_paterno' => 'Pérez',
            'celular' => '987654321',
            'correo' => 'juan@test.com',
            'direccion' => 'Av. Test 123',
            'genero' => 'M',
            'edad' => 30,
        ]);
        
        // Test toArray()
        $arrayData = $persona->toArray();
        $this->assertArrayNotHasKey('documento', $arrayData);
        $this->assertArrayNotHasKey('celular', $arrayData);
        $this->assertArrayNotHasKey('correo', $arrayData);
        $this->assertArrayNotHasKey('direccion', $arrayData);
        
        // Test toJson()
        $jsonData = json_decode($persona->toJson(), true);
        $this->assertArrayNotHasKey('documento', $jsonData);
        $this->assertArrayNotHasKey('celular', $jsonData);
        $this->assertArrayNotHasKey('correo', $jsonData);
        $this->assertArrayNotHasKey('direccion', $jsonData);
        
        // Verificar que datos públicos SÍ están
        $this->assertArrayHasKey('nombres', $arrayData);
        $this->assertArrayHasKey('apellido_paterno', $arrayData);
        
        echo "\n✅ PERSONA JSON: Datos sensibles ocultos correctamente";
    }
}
