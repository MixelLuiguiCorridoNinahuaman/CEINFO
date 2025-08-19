<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Persona;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PersonaDataProtectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que simula un endpoint de API pública que debe ocultar datos sensibles
     */
    public function test_api_public_endpoint_hides_sensitive_data()
    {
        // Crear una persona de prueba
        $persona = Persona::create([
            'tipo_documento' => 'DNI',
            'documento' => '12345678',
            'nombres' => 'Ana',
            'apellido_paterno' => 'González',
            'apellido_materno' => 'Ruiz',
            'celular' => '987654321',
            'correo' => 'ana.gonzalez@example.com',
            'direccion' => 'Jr. Lima 789',
            'genero' => 'F',
            'edad' => 28,
        ]);

        // Simular respuesta de API pública (sin makeVisible)
        $response = $persona->toArray();
        
        // Verificar que datos públicos están presentes
        $this->assertArrayHasKey('nombres', $response);
        $this->assertArrayHasKey('apellido_paterno', $response);
        $this->assertArrayHasKey('apellido_materno', $response);
        $this->assertArrayHasKey('genero', $response);
        $this->assertArrayHasKey('edad', $response);
        
        // Verificar que datos sensibles están ocultos
        $this->assertArrayNotHasKey('documento', $response);
        $this->assertArrayNotHasKey('celular', $response);
        $this->assertArrayNotHasKey('correo', $response);
        $this->assertArrayNotHasKey('direccion', $response);
        
        // Verificar valores de datos públicos
        $this->assertEquals('Ana', $response['nombres']);
        $this->assertEquals('González', $response['apellido_paterno']);
        $this->assertEquals('F', $response['genero']);
        $this->assertEquals(28, $response['edad']);
    }

    /**
     * Test que simula un endpoint administrativo con acceso controlado a datos sensibles
     */
    public function test_admin_endpoint_can_access_sensitive_data_when_needed()
    {
        $persona = Persona::create([
            'tipo_documento' => 'DNI',
            'documento' => '87654321',
            'nombres' => 'Pedro',
            'apellido_paterno' => 'Martín',
            'apellido_materno' => 'Flores',
            'celular' => '912345678',
            'correo' => 'pedro.martin@example.com',
            'direccion' => 'Av. Central 456',
            'genero' => 'M',
            'edad' => 35,
        ]);

        // Simular endpoint administrativo que necesita acceso a contacto
        $adminResponse = $persona->makeVisible(['correo', 'celular'])->toArray();
        
        // Verificar que datos de contacto están visibles para admin
        $this->assertArrayHasKey('correo', $adminResponse);
        $this->assertArrayHasKey('celular', $adminResponse);
        $this->assertEquals('pedro.martin@example.com', $adminResponse['correo']);
        $this->assertEquals('912345678', $adminResponse['celular']);
        
        // Verificar que otros datos sensibles siguen ocultos
        $this->assertArrayNotHasKey('documento', $adminResponse);
        $this->assertArrayNotHasKey('direccion', $adminResponse);
    }

    /**
     * Test que verifica la protección en colecciones de modelos
     */
    public function test_collection_hides_sensitive_data()
    {
        // Crear múltiples personas
        Persona::create([
            'tipo_documento' => 'DNI',
            'documento' => '11111111',
            'nombres' => 'Luis',
            'apellido_paterno' => 'Castro',
            'apellido_materno' => 'Vega',
            'celular' => '999111111',
            'correo' => 'luis.castro@example.com',
            'direccion' => 'Calle 1',
            'genero' => 'M',
            'edad' => 25,
        ]);

        Persona::create([
            'tipo_documento' => 'DNI',
            'documento' => '22222222',
            'nombres' => 'Carmen',
            'apellido_paterno' => 'Ríos',
            'apellido_materno' => 'Silva',
            'celular' => '999222222',
            'correo' => 'carmen.rios@example.com',
            'direccion' => 'Calle 2',
            'genero' => 'F',
            'edad' => 30,
        ]);

        // Obtener colección de personas
        $personas = Persona::all();
        $personasArray = $personas->toArray();

        // Verificar que cada persona en la colección oculta datos sensibles
        foreach ($personasArray as $personaData) {
            $this->assertArrayNotHasKey('documento', $personaData);
            $this->assertArrayNotHasKey('celular', $personaData);
            $this->assertArrayNotHasKey('correo', $personaData);
            $this->assertArrayNotHasKey('direccion', $personaData);
            
            // Verificar que datos públicos están presentes
            $this->assertArrayHasKey('nombres', $personaData);
            $this->assertArrayHasKey('apellido_paterno', $personaData);
        }
    }

    /**
     * Test que verifica el cumplimiento con RGPD/Ley de Protección de Datos
     */
    public function test_data_protection_compliance()
    {
        $persona = new Persona();
        
        // Obtener campos ocultos
        $reflection = new \ReflectionClass($persona);
        $hiddenProperty = $reflection->getProperty('hidden');
        $hiddenProperty->setAccessible(true);
        $hiddenFields = $hiddenProperty->getValue($persona);
        
        // Campos que deben estar protegidos según RGPD y normativas locales
        $requiredProtectedFields = [
            'documento',    // Documento de identidad - Dato personal identificativo
            'celular',      // Teléfono - Dato de contacto personal
            'correo',       // Email - Dato de contacto personal
            'direccion',    // Dirección - Información de ubicación personal
        ];
        
        foreach ($requiredProtectedFields as $field) {
            $this->assertContains($field, $hiddenFields, 
                "Campo '$field' debe estar protegido para cumplir con regulaciones de protección de datos");
        }
        
        // Verificar que hay al menos 4 campos protegidos
        $this->assertGreaterThanOrEqual(4, count($hiddenFields), 
            'Debe haber al menos 4 campos sensibles protegidos');
    }
}
