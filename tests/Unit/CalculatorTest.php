<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CalculatorTest extends TestCase
{
    /**
     * Test básico de suma - VISUALIZACIÓN DINÁMICA
     */
    public function test_suma_basica(): void
    {
        $resultado = 2 + 3;
        $this->assertEquals(5, $resultado, 'La suma de 2 + 3 debe ser 5');
    }
    
    /**
     * Test de validación de email - ANÁLISIS DINÁMICO
     */
    public function test_validacion_email(): void
    {
        $email = "test@example.com";
        $this->assertTrue(filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
    }
    
    /**
     * Test de array - COBERTURA DINÁMICA
     */
    public function test_array_contains(): void
    {
        $array = ['manzana', 'banana', 'naranja'];
        $this->assertContains('banana', $array);
    }
    
    /**
     * Test de string - PROFILING DINÁMICO
     */
    public function test_string_length(): void
    {
        $texto = "Laravel Framework";
        $this->assertEquals(17, strlen($texto));
    }
}
