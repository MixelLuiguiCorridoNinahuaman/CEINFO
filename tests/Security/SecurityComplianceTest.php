<?php

namespace Tests\Security;

use Tests\TestCase;
use App\Models\User;
use App\Models\Estudiante;
use App\Models\Persona;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SecurityComplianceTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Test: Cumplimiento Ley 29733 - Protección de Datos Personales
     * Equivalente a: npm run security:scan
     */
    public function test_ley_29733_proteccion_datos_personales()
    {
        // Crear usuario con permisos limitados
        $user = User::factory()->create(['role' => 'estudiante']);
        $persona = Persona::factory()->create();
        $estudiante = Estudiante::factory()->create(['id_persona' => $persona->id_persona]);
        
        // Test: Acceso sin autorización debe ser denegado
        $response = $this->get('/api/estudiantes');
        $response->assertStatus(401);
        
        // Test: Usuario autenticado solo ve datos permitidos
        $response = $this->actingAs($user)
                        ->get('/api/estudiantes');
        
        // Verificar que datos sensibles NO se exponen
        $response->assertJsonMissing([
            'dni' => $persona->dni,
            'telefono' => $persona->telefono,
            'direccion' => $persona->direccion
        ]);
    }
    
    /**
     * Test: Validación de entrada contra inyección SQL
     */
    public function test_sql_injection_protection()
    {
        $user = User::factory()->create();
        
        // Intentar inyección SQL en búsqueda
        $maliciousInput = "'; DROP TABLE estudiantes; --";
        
        $response = $this->actingAs($user)
                        ->get('/api/estudiantes?search=' . urlencode($maliciousInput));
        
        // Debe retornar resultados seguros, no error de SQL
        $response->assertStatus(200);
        
        // Verificar que la tabla sigue existiendo
        $this->assertDatabaseHas('estudiantes', []);
    }
    
    /**
     * Test: Autenticación y autorización de rutas administrativas
     */
    public function test_admin_routes_require_authentication()
    {
        $adminRoutes = [
            '/admin/estudiantes',
            '/admin/docentes',
            '/admin/cursos',
            '/admin/horarios'
        ];
        
        foreach ($adminRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/login');
        }
    }
}
