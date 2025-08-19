<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Persona;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PersonaSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que verifica si el modelo Persona oculta campos sensibles
     * Esta prueba detecta la vulnerabilidad de Data Exposure
     */
    public function test_persona_model_hides_sensitive_fields()
    {
        // Crear una instancia de Persona con datos de prueba
        $persona = new Persona([
            'tipo_documento' => 'DNI',
            'documento' => '12345678',
            'nombres' => 'Juan',
            'apellido_paterno' => 'Pérez',
            'apellido_materno' => 'García',
            'celular' => '987654321',
            'correo' => 'juan.perez@example.com',
            'direccion' => 'Av. Principal 123',
            'genero' => 'M',
            'edad' => 25,
        ]);

        // Convertir el modelo a array para verificar qué campos están expuestos
        $personaArray = $persona->toArray();
        
        // Verificar que campos sensibles NO estén presentes en la salida JSON/Array
        $this->assertArrayNotHasKey('documento', $personaArray, 
            'El campo documento (DNI) debe estar oculto para proteger datos personales');
        
        $this->assertArrayNotHasKey('celular', $personaArray, 
            'El campo celular debe estar oculto para proteger datos de contacto');
        
        $this->assertArrayNotHasKey('correo', $personaArray, 
            'El campo correo debe estar oculto para proteger datos de contacto');
        
        $this->assertArrayNotHasKey('direccion', $personaArray, 
            'El campo direccion debe estar oculto para proteger datos personales');
    }

    /**
     * Test que verifica que el modelo Persona tiene la propiedad $hidden definida
     */
    public function test_persona_model_has_hidden_property()
    {
        $persona = new Persona();
        $reflection = new \ReflectionClass($persona);
        
        // Verificar que la propiedad $hidden existe
        $this->assertTrue($reflection->hasProperty('hidden'), 
            'El modelo Persona debe tener la propiedad $hidden definida');
        
        // Obtener la propiedad $hidden
        $hiddenProperty = $reflection->getProperty('hidden');
        $hiddenProperty->setAccessible(true);
        $hiddenFields = $hiddenProperty->getValue($persona);
        
        // Verificar que $hidden no esté vacío
        $this->assertNotEmpty($hiddenFields, 
            'La propiedad $hidden no debe estar vacía');
        
        // Verificar que contenga campos sensibles específicos
        $expectedHiddenFields = ['documento', 'celular', 'correo', 'direccion'];
        foreach ($expectedHiddenFields as $field) {
            $this->assertContains($field, $hiddenFields, 
                "El campo '$field' debe estar en la lista de campos ocultos");
        }
    }

    /**
     * Test que simula una respuesta JSON de API para verificar seguridad
     */
    public function test_persona_json_response_security()
    {
        // Crear una persona en la base de datos
        $persona = Persona::create([
            'tipo_documento' => 'DNI',
            'documento' => '12345678',
            'nombres' => 'María',
            'apellido_paterno' => 'López',
            'apellido_materno' => 'Martínez',
            'celular' => '987654321',
            'correo' => 'maria.lopez@example.com',
            'direccion' => 'Calle Secundaria 456',
            'genero' => 'F',
            'edad' => 30,
        ]);

        // Simular respuesta JSON como la que se enviaría a una API
        $jsonResponse = $persona->toJson();
        $responseData = json_decode($jsonResponse, true);
        
        // Verificar que datos sensibles no estén en la respuesta JSON
        $this->assertArrayNotHasKey('documento', $responseData, 
            'DNI no debe aparecer en respuestas JSON de APIs públicas');
        
        $this->assertArrayNotHasKey('celular', $responseData, 
            'Número de celular no debe aparecer en respuestas JSON de APIs públicas');
        
        $this->assertArrayNotHasKey('correo', $responseData, 
            'Correo electrónico no debe aparecer en respuestas JSON de APIs públicas');
        
        $this->assertArrayNotHasKey('direccion', $responseData, 
            'Dirección no debe aparecer en respuestas JSON de APIs públicas');
        
        // Verificar que datos públicos SÍ estén presentes
        $this->assertArrayHasKey('nombres', $responseData, 
            'Los nombres deben estar presentes en la respuesta');
        
        $this->assertArrayHasKey('apellido_paterno', $responseData, 
            'El apellido paterno debe estar presente en la respuesta');
    }

    /**
     * Test que verifica el comportamiento con makeVisible()
     */
    public function test_persona_makeVisible_functionality()
    {
        $persona = new Persona([
            'documento' => '12345678',
            'nombres' => 'Carlos',
            'correo' => 'carlos@example.com'
        ]);

        // Por defecto, campos sensibles deben estar ocultos
        $defaultArray = $persona->toArray();
        $this->assertArrayNotHasKey('documento', $defaultArray);
        $this->assertArrayNotHasKey('correo', $defaultArray);

        // Con makeVisible, deben ser visibles solo cuando se especifique
        $visibleArray = $persona->makeVisible(['documento', 'correo'])->toArray();
        $this->assertArrayHasKey('documento', $visibleArray);
        $this->assertArrayHasKey('correo', $visibleArray);
    }
}
