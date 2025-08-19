<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Persona;
use App\Models\User;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\pagos;
use App\Models\Carrera;
use ReflectionClass;

class HiddenPropertyTest extends TestCase
{
    /**
     * Test específico para verificar que el modelo Persona tiene $hidden configurado
     */
    public function test_persona_model_has_hidden_property()
    {
        $persona = new Persona();
        $reflection = new ReflectionClass($persona);
        
        // Verificar que la propiedad $hidden existe
        $this->assertTrue($reflection->hasProperty('hidden'), 
            'El modelo Persona debe tener la propiedad $hidden definida');
        
        // Obtener la propiedad $hidden
        $hiddenProperty = $reflection->getProperty('hidden');
        $hiddenProperty->setAccessible(true);
        $hiddenFields = $hiddenProperty->getValue($persona);
        
        // Verificar que $hidden no esté vacío
        $this->assertNotEmpty($hiddenFields, 
            'La propiedad $hidden en Persona no debe estar vacía');
        
        // Verificar que es un array
        $this->assertIsArray($hiddenFields, 
            'La propiedad $hidden debe ser un array');
        
        // Verificar campos específicos sensibles
        $expectedHiddenFields = ['documento', 'celular', 'correo', 'direccion'];
        foreach ($expectedHiddenFields as $field) {
            $this->assertContains($field, $hiddenFields, 
                "El campo '$field' debe estar en la lista de campos ocultos de Persona");
        }
        
        echo "\n✅ Persona: Campos ocultos = " . implode(', ', $hiddenFields);
    }

    /**
     * Test para verificar que User tiene $hidden (ya debería tenerlo)
     */
    public function test_user_model_has_hidden_property()
    {
        $user = new User();
        $reflection = new ReflectionClass($user);
        
        $this->assertTrue($reflection->hasProperty('hidden'), 
            'El modelo User debe tener la propiedad $hidden definida');
        
        $hiddenProperty = $reflection->getProperty('hidden');
        $hiddenProperty->setAccessible(true);
        $hiddenFields = $hiddenProperty->getValue($user);
        
        $this->assertNotEmpty($hiddenFields, 
            'La propiedad $hidden en User no debe estar vacía');
        
        // User típicamente oculta password, remember_token, etc.
        $this->assertContains('password', $hiddenFields, 
            'User debe ocultar el campo password');
        
        echo "\n✅ User: Campos ocultos = " . implode(', ', $hiddenFields);
    }

    /**
     * Test para verificar que Docente tiene $hidden
     */
    public function test_docente_model_has_hidden_property()
    {
        $docente = new Docente();
        $reflection = new ReflectionClass($docente);
        
        $this->assertTrue($reflection->hasProperty('hidden'), 
            'El modelo Docente debe tener la propiedad $hidden definida');
        
        $hiddenProperty = $reflection->getProperty('hidden');
        $hiddenProperty->setAccessible(true);
        $hiddenFields = $hiddenProperty->getValue($docente);
        
        $this->assertNotEmpty($hiddenFields, 
            'La propiedad $hidden en Docente no debe estar vacía');
        
        echo "\n✅ Docente: Campos ocultos = " . implode(', ', $hiddenFields);
    }

    /**
     * Test para verificar que Estudiante tiene $hidden
     */
    public function test_estudiante_model_has_hidden_property()
    {
        $estudiante = new Estudiante();
        $reflection = new ReflectionClass($estudiante);
        
        $this->assertTrue($reflection->hasProperty('hidden'), 
            'El modelo Estudiante debe tener la propiedad $hidden definida');
        
        $hiddenProperty = $reflection->getProperty('hidden');
        $hiddenProperty->setAccessible(true);
        $hiddenFields = $hiddenProperty->getValue($estudiante);
        
        $this->assertNotEmpty($hiddenFields, 
            'La propiedad $hidden en Estudiante no debe estar vacía');
        
        echo "\n✅ Estudiante: Campos ocultos = " . implode(', ', $hiddenFields);
    }

    /**
     * Test para verificar modelos que NO tienen $hidden (deberían fallar)
     */
    public function test_pagos_model_missing_hidden_property()
    {
        $pagos = new pagos();
        $reflection = new ReflectionClass($pagos);
        
        if ($reflection->hasProperty('hidden')) {
            $hiddenProperty = $reflection->getProperty('hidden');
            $hiddenProperty->setAccessible(true);
            $hiddenFields = $hiddenProperty->getValue($pagos);
            
            if (!empty($hiddenFields)) {
                echo "\n✅ Pagos: Campos ocultos = " . implode(', ', $hiddenFields);
                $this->assertTrue(true, 'Pagos tiene $hidden configurado');
            } else {
                echo "\n⚠️ Pagos: Tiene \$hidden pero está vacío";
                $this->fail('El modelo Pagos tiene $hidden vacío - debería tener campos sensibles protegidos');
            }
        } else {
            echo "\n❌ Pagos: NO tiene \$hidden";
            $this->fail('El modelo Pagos NO tiene la propiedad $hidden - vulnerabilidad de seguridad');
        }
    }

    /**
     * Test para verificar modelo Carrera que no tiene $hidden
     */
    public function test_carrera_model_missing_hidden_property()
    {
        $carrera = new Carrera();
        $reflection = new ReflectionClass($carrera);
        
        if ($reflection->hasProperty('hidden')) {
            $hiddenProperty = $reflection->getProperty('hidden');
            $hiddenProperty->setAccessible(true);
            $hiddenFields = $hiddenProperty->getValue($carrera);
            
            if (!empty($hiddenFields)) {
                echo "\n✅ Carrera: Campos ocultos = " . implode(', ', $hiddenFields);
                $this->assertTrue(true, 'Carrera tiene $hidden configurado');
            } else {
                echo "\n⚠️ Carrera: Tiene \$hidden pero está vacío";
                $this->fail('El modelo Carrera tiene $hidden vacío');
            }
        } else {
            echo "\n❌ Carrera: NO tiene \$hidden";
            $this->fail('El modelo Carrera NO tiene la propiedad $hidden - posible vulnerabilidad');
        }
    }

    /**
     * Test general para verificar serialización segura de Persona
     */
    public function test_persona_serialization_hides_sensitive_fields()
    {
        $persona = new Persona([
            'tipo_documento' => 'DNI',
            'documento' => '12345678',
            'nombres' => 'Test',
            'apellido_paterno' => 'Usuario',
            'celular' => '999888777',
            'correo' => 'test@example.com',
            'direccion' => 'Calle Test 123',
            'genero' => 'M',
            'edad' => 30,
        ]);

        // Verificar que toArray() oculta campos sensibles
        $personaArray = $persona->toArray();
        
        // Campos que NO deben estar presentes
        $this->assertArrayNotHasKey('documento', $personaArray, 
            'documento debe estar oculto en serialización');
        $this->assertArrayNotHasKey('celular', $personaArray, 
            'celular debe estar oculto en serialización');
        $this->assertArrayNotHasKey('correo', $personaArray, 
            'correo debe estar oculto en serialización');
        $this->assertArrayNotHasKey('direccion', $personaArray, 
            'direccion debe estar oculto en serialización');
        
        // Campos que SÍ deben estar presentes
        $this->assertArrayHasKey('nombres', $personaArray, 
            'nombres debe estar visible en serialización');
        $this->assertArrayHasKey('apellido_paterno', $personaArray, 
            'apellido_paterno debe estar visible en serialización');
        
        echo "\n✅ Persona serialización: Campos visibles = " . implode(', ', array_keys($personaArray));
    }

    /**
     * Test para verificar makeVisible() funciona correctamente
     */
    public function test_persona_make_visible_functionality()
    {
        $persona = new Persona([
            'documento' => '12345678',
            'nombres' => 'Test',
            'correo' => 'test@example.com'
        ]);

        // Por defecto, campos sensibles ocultos
        $defaultArray = $persona->toArray();
        $this->assertArrayNotHasKey('documento', $defaultArray);
        $this->assertArrayNotHasKey('correo', $defaultArray);

        // Con makeVisible, campos específicos visibles
        $visibleArray = $persona->makeVisible(['documento'])->toArray();
        $this->assertArrayHasKey('documento', $visibleArray, 
            'documento debe ser visible cuando se usa makeVisible');
        $this->assertArrayNotHasKey('correo', $visibleArray, 
            'correo debe seguir oculto si no se especifica en makeVisible');
        
        echo "\n✅ Persona makeVisible: Funcionando correctamente";
    }
}
