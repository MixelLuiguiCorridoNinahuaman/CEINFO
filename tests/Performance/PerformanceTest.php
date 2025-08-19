<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Models\Estudiante;
use App\Models\Persona;
use App\Models\CursoHorario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Test: Performance de consulta de estudiantes con relaciones
     * Equivalente a: npm run performance:profile
     */
    public function test_estudiantes_query_performance()
    {
        // Crear datos de prueba
        Persona::factory(50)->create()->each(function ($persona) {
            Estudiante::factory()->create(['id_persona' => $persona->id_persona]);
        });
        
        // Medir tiempo de ejecución
        $start = microtime(true);
        
        DB::enableQueryLog();
        
        // Consulta optimizada con eager loading
        $estudiantes = Estudiante::with(['persona'])
                                ->where('estado', 'activo')
                                ->get();
        
        $queries = DB::getQueryLog();
        $end = microtime(true);
        $executionTime = ($end - $start) * 1000; // en milisegundos
        
        // Assertions de performance
        $this->assertLessThan(500, $executionTime, 
            "Consulta de estudiantes tardó {$executionTime}ms (límite: 500ms)");
        
        $this->assertLessThan(5, count($queries), 
            'Demasiadas consultas SQL: ' . count($queries));
        
        $this->assertGreaterThan(0, $estudiantes->count(), 
            'La consulta debe retornar resultados');
    }
    
    /**
     * Test: Performance del endpoint de horarios más utilizado
     */
    public function test_horarios_endpoint_performance()
    {
        // Crear datos de prueba
        CursoHorario::factory(20)->create();
        
        $start = microtime(true);
        
        // Simular petición AJAX como DataTables
        $response = $this->get('/curso-horarios/listar');
        
        $end = microtime(true);
        $responseTime = ($end - $start) * 1000;
        
        // Validaciones de performance
        $response->assertStatus(200);
        $this->assertLessThan(1000, $responseTime, 
            "Endpoint de horarios tardó {$responseTime}ms (límite: 1000ms)");
        
        // Verificar estructura de respuesta optimizada
        $data = $response->json();
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
    }
    
    /**
     * Test: Memoria utilizada en operaciones masivas
     */
    public function test_memory_usage_bulk_operations()
    {
        $memoryBefore = memory_get_usage();
        
        // Operación masiva: crear muchos registros
        $personas = Persona::factory(100)->create();
        $estudiantes = $personas->map(function ($persona) {
            return Estudiante::factory()->create(['id_persona' => $persona->id_persona]);
        });
        
        $memoryAfter = memory_get_usage();
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // MB
        
        // Límite de memoria: 32MB para 100 registros
        $this->assertLessThan(32, $memoryUsed, 
            "Operación masiva utilizó {$memoryUsed}MB (límite: 32MB)");
    }
    
    /**
     * Test: Performance de búsqueda con filtros
     */
    public function test_search_filters_performance()
    {
        // Crear datos diversos para búsqueda
        Persona::factory(30)->create()->each(function ($persona) {
            Estudiante::factory()->create([
                'id_persona' => $persona->id_persona,
                'estado' => collect(['activo', 'inactivo'])->random()
            ]);
        });
        
        $start = microtime(true);
        
        DB::enableQueryLog();
        
        // Búsqueda compleja con filtros
        $resultados = Estudiante::with('persona')
                               ->whereHas('persona', function ($query) {
                                   $query->where('nombre', 'like', '%a%');
                               })
                               ->where('estado', 'activo')
                               ->orderBy('created_at', 'desc')
                               ->paginate(10);
        
        $queries = DB::getQueryLog();
        $end = microtime(true);
        $searchTime = ($end - $start) * 1000;
        
        // Validaciones
        $this->assertLessThan(800, $searchTime, 
            "Búsqueda con filtros tardó {$searchTime}ms (límite: 800ms)");
        
        $this->assertLessThan(10, count($queries), 
            'Búsqueda generó demasiadas consultas: ' . count($queries));
    }
}
