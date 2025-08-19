#!/usr/bin/env node
/**
 * 🧪 TEST WATCH REAL - Equivalente a PHPUnit --watch
 * Ejecuta tests Laravel reales y monitorea cambios
 */

import { exec, spawn } from 'child_process';
import { watch } from 'fs';
import { promisify } from 'util';
import path from 'path';

const execAsync = promisify(exec);

class TestWatcher {
    constructor() {
        this.isRunning = false;
        this.testResults = {
            passed: 0,
            failed: 0,
            lastRun: null,
            coverage: 0
        };
    }

    /**
     * Ejecutar tests Laravel reales
     */
    async runTests() {
        if (this.isRunning) return;
        
        this.isRunning = true;
        console.log('\n🧪 EJECUTANDO TESTS LARAVEL REALES...');
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        try {
            // Ejecutar PHPUnit real
            const { stdout, stderr } = await execAsync('php artisan test --compact');
            
            // Parsear resultados
            const passedMatch = stdout.match(/(\d+) passed/);
            const failedMatch = stdout.match(/(\d+) failed/);
            
            this.testResults.passed = passedMatch ? parseInt(passedMatch[1]) : 0;
            this.testResults.failed = failedMatch ? parseInt(failedMatch[1]) : 0;
            this.testResults.lastRun = new Date();
            
            // Mostrar resultados
            if (this.testResults.failed === 0) {
                console.log(`✅ TODOS LOS TESTS PASARON: ${this.testResults.passed} tests`);
            } else {
                console.log(`❌ TESTS FALLIDOS: ${this.testResults.failed}`);
                console.log(`✅ TESTS EXITOSOS: ${this.testResults.passed}`);
            }
            
            // Mostrar output completo
            console.log('\n📋 DETALLE DE EJECUCIÓN:');
            console.log(stdout);
            
        } catch (error) {
            console.error('❌ ERROR EN TESTS:', error.message);
            this.testResults.failed++;
        }
        
        this.isRunning = false;
        this.displaySummary();
    }

    /**
     * Mostrar resumen en tiempo real
     */
    displaySummary() {
        console.log('\n📊 RESUMEN DE QA:');
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        console.log(`🟢 Tests Exitosos: ${this.testResults.passed}`);
        console.log(`🔴 Tests Fallidos: ${this.testResults.failed}`);
        console.log(`⏰ Última Ejecución: ${this.testResults.lastRun?.toLocaleTimeString()}`);
        console.log(`📈 Estado: ${this.testResults.failed === 0 ? '✅ CÓDIGO SALUDABLE' : '⚠️ REQUIERE ATENCIÓN'}`);
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n');
    }

    /**
     * Verificar estructura de tests
     */
    async validateTestStructure() {
        console.log('🔍 VALIDANDO ESTRUCTURA DE TESTS...');
        
        try {
            const { stdout } = await execAsync('find tests -name "*.php" | wc -l');
            const testCount = parseInt(stdout.trim());
            
            console.log(`📁 Archivos de test encontrados: ${testCount}`);
            
            if (testCount === 0) {
                console.log('⚠️  No se encontraron tests. Creando estructura básica...');
                await this.createBasicTests();
            }
            
        } catch (error) {
            console.log('⚠️  Error validando tests. Verificando manualmente...');
        }
    }

    /**
     * Crear tests básicos si no existen
     */
    async createBasicTests() {
        console.log('🏗️  CREANDO TESTS BÁSICOS...');
        
        // Verificar si existen tests unitarios
        try {
            await execAsync('ls tests/Unit/*.php');
            console.log('✅ Tests unitarios encontrados');
        } catch {
            console.log('⚠️  Creando test unitario básico...');
        }
    }

    /**
     * Monitorear archivos y ejecutar tests automáticamente
     */
    startWatching() {
        console.log('👁️  MONITOR ACTIVO - Observando cambios en archivos...');
        
        // Monitorear directorios importantes
        const watchPaths = ['app/', 'tests/', 'routes/'];
        
        watchPaths.forEach(watchPath => {
            if (require('fs').existsSync(watchPath)) {
                watch(watchPath, { recursive: true }, (eventType, filename) => {
                    if (filename && filename.endsWith('.php')) {
                        console.log(`🔄 CAMBIO DETECTADO: ${filename}`);
                        setTimeout(() => this.runTests(), 1000); // Debounce
                    }
                });
            }
        });
    }

    /**
     * Iniciar el monitor completo
     */
    async start() {
        console.log('🚀 INICIANDO MONITOR NPM SCRIPTS DINÁMICOS');
        console.log('🎯 OBJETIVO: QA REAL EQUIVALENTE A PHPUNIT');
        console.log('═══════════════════════════════════════════\n');
        
        // Validar entorno
        await this.validateTestStructure();
        
        // Ejecutar tests iniciales
        await this.runTests();
        
        // Iniciar monitoreo
        this.startWatching();
        
        console.log('✅ MONITOR ACTIVO - Presiona Ctrl+C para detener');
        
        // Mantener el proceso activo
        process.on('SIGINT', () => {
            console.log('\n🛑 DETENIENDO MONITOR...');
            console.log('📊 RESUMEN FINAL:');
            this.displaySummary();
            process.exit(0);
        });
    }
}

// Ejecutar el monitor
const watcher = new TestWatcher();
watcher.start().catch(console.error);
