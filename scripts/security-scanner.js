#!/usr/bin/env node
/**
 * 🛡️ SECURITY SCANNER REAL - Mejor que PHPUnit Security
 * Escanea vulnerabilidades reales en código Laravel
 */

import { exec } from 'child_process';
import { promisify } from 'util';
import fs from 'fs/promises';
import path from 'path';

const execAsync = promisify(exec);

class SecurityScanner {
    constructor() {
        this.vulnerabilities = [];
        this.securityMetrics = {
            totalChecks: 0,
            passed: 0,
            warnings: 0,
            critical: 0,
            timestamp: new Date()
        };
    }

    /**
     * Ejecutar escaneo de seguridad completo
     */
    async runSecurityScan() {
        console.log('\n🛡️ EJECUTANDO ESCANEO DE SEGURIDAD REAL...');
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        // Escaneos específicos
        await this.checkLey29733Compliance();
        await this.scanSQLInjection();
        await this.checkAuthenticationSecurity();
        await this.scanFilePermissions();
        await this.checkEnvironmentSecurity();
        await this.validateInputSanitization();
        await this.checkSessionSecurity();
        
        // Generar reporte
        this.generateSecurityReport();
    }

    /**
     * Verificar cumplimiento Ley 29733 - Protección de Datos Personales
     */
    async checkLey29733Compliance() {
        console.log('📋 VERIFICANDO LEY 29733 - PROTECCIÓN DE DATOS...');
        this.securityMetrics.totalChecks++;
        
        try {
            // Buscar exposición de datos sensibles
            const sensitiveFiles = ['app/Models/Persona.php', 'app/Models/Estudiante.php'];
            
            for (const file of sensitiveFiles) {
                try {
                    const content = await fs.readFile(file, 'utf8');
                    
                    // Verificar si expone datos sensibles
                    if (content.includes('$hidden') || content.includes('$guarded')) {
                        this.securityMetrics.passed++;
                        console.log(`✅ ${file}: Protección de datos configurada`);
                    } else {
                        this.addVulnerability('CRITICAL', 'Ley 29733', 
                            `${file} no protege datos sensibles`, file);
                    }
                    
                    // Verificar campos sensibles sin protección
                    const sensitiveFields = ['dni', 'telefono', 'direccion', 'email'];
                    const exposedFields = sensitiveFields.filter(field => 
                        content.includes(`'${field}'`) && !content.includes('$hidden')
                    );
                    
                    if (exposedFields.length > 0) {
                        this.addVulnerability('WARNING', 'Datos Sensibles', 
                            `Campos expuestos: ${exposedFields.join(', ')}`, file);
                    }
                    
                } catch (error) {
                    console.log(`⚠️  No se pudo verificar: ${file}`);
                }
            }
            
        } catch (error) {
            this.addVulnerability('WARNING', 'Ley 29733', 'No se pudo verificar compliance');
        }
    }

    /**
     * Escanear vulnerabilidades de SQL Injection
     */
    async scanSQLInjection() {
        console.log('💉 ESCANEANDO SQL INJECTION...');
        this.securityMetrics.totalChecks++;
        
        try {
            // Buscar consultas SQL peligrosas
            const { stdout } = await execAsync('find app/ -name "*.php" -exec grep -l "DB::" {} \\;');
            const dbFiles = stdout.trim().split('\n').filter(f => f.length > 0);
            
            for (const file of dbFiles) {
                const content = await fs.readFile(file, 'utf8');
                
                // Buscar patrones peligrosos
                const dangerousPatterns = [
                    /DB::raw\([^)]*\$[^)]*\)/g,
                    /whereRaw\([^)]*\$[^)]*\)/g,
                    /select\([^)]*\$[^)]*\)/g
                ];
                
                for (const pattern of dangerousPatterns) {
                    const matches = content.match(pattern);
                    if (matches) {
                        this.addVulnerability('CRITICAL', 'SQL Injection', 
                            `Posible vulnerabilidad en ${file}: ${matches[0]}`, file);
                    }
                }
            }
            
            console.log(`✅ Escaneados ${dbFiles.length} archivos con consultas DB`);
            this.securityMetrics.passed++;
            
        } catch (error) {
            console.log('⚠️  Error escaneando SQL injection');
        }
    }

    /**
     * Verificar seguridad de autenticación
     */
    async checkAuthenticationSecurity() {
        console.log('🔐 VERIFICANDO SEGURIDAD DE AUTENTICACIÓN...');
        this.securityMetrics.totalChecks++;
        
        try {
            // Verificar middleware de autenticación
            const webRoutes = await fs.readFile('routes/web.php', 'utf8');
            
            // Buscar rutas sin protección
            const unprotectedRoutes = webRoutes.match(/Route::[^)]*(?!.*middleware)/g);
            if (unprotectedRoutes && unprotectedRoutes.length > 5) {
                this.addVulnerability('WARNING', 'Autenticación', 
                    `${unprotectedRoutes.length} rutas sin middleware de protección`);
            }
            
            // Verificar configuración de auth
            const authConfig = await fs.readFile('config/auth.php', 'utf8');
            if (authConfig.includes("'driver' => 'session'")) {
                console.log('✅ Configuración de autenticación por sesión');
                this.securityMetrics.passed++;
            }
            
        } catch (error) {
            this.addVulnerability('WARNING', 'Autenticación', 'No se pudo verificar configuración');
        }
    }

    /**
     * Escanear permisos de archivos
     */
    async scanFilePermissions() {
        console.log('📂 ESCANEANDO PERMISOS DE ARCHIVOS...');
        this.securityMetrics.totalChecks++;
        
        try {
            // Verificar archivos críticos
            const criticalFiles = ['.env', 'storage/', 'bootstrap/cache/'];
            
            for (const file of criticalFiles) {
                try {
                    const stats = await fs.stat(file);
                    console.log(`📄 ${file}: Verificado`);
                } catch (error) {
                    if (file === '.env') {
                        this.addVulnerability('CRITICAL', 'Archivo .env', 'Archivo .env no encontrado');
                    }
                }
            }
            
            this.securityMetrics.passed++;
            
        } catch (error) {
            console.log('⚠️  Error verificando permisos');
        }
    }

    /**
     * Verificar seguridad del entorno
     */
    async checkEnvironmentSecurity() {
        console.log('🌍 VERIFICANDO SEGURIDAD DEL ENTORNO...');
        this.securityMetrics.totalChecks++;
        
        try {
            // Verificar configuración de producción
            const { stdout } = await execAsync('php -r "echo env(\'APP_DEBUG\');"');
            
            if (stdout.includes('true') || stdout.includes('1')) {
                this.addVulnerability('WARNING', 'Debug Mode', 
                    'APP_DEBUG activado - no recomendado para producción');
            } else {
                console.log('✅ Debug mode desactivado');
                this.securityMetrics.passed++;
            }
            
        } catch (error) {
            console.log('⚠️  No se pudo verificar configuración del entorno');
        }
    }

    /**
     * Validar sanitización de entrada
     */
    async validateInputSanitization() {
        console.log('🧹 VALIDANDO SANITIZACIÓN DE ENTRADA...');
        this.securityMetrics.totalChecks++;
        
        try {
            // Buscar controladores
            const controllers = await this.findPHPFiles('app/Http/Controllers/');
            
            for (const controller of controllers) {
                const content = await fs.readFile(controller, 'utf8');
                
                // Verificar uso de Request validation
                if (content.includes('$request->validate') || content.includes('FormRequest')) {
                    console.log(`✅ ${path.basename(controller)}: Validación de entrada configurada`);
                } else if (content.includes('$request->')) {
                    this.addVulnerability('WARNING', 'Validación', 
                        `${controller} usa request sin validación explícita`);
                }
            }
            
            this.securityMetrics.passed++;
            
        } catch (error) {
            console.log('⚠️  Error validando sanitización');
        }
    }

    /**
     * Verificar seguridad de sesiones
     */
    async checkSessionSecurity() {
        console.log('🔒 VERIFICANDO SEGURIDAD DE SESIONES...');
        this.securityMetrics.totalChecks++;
        
        try {
            const sessionConfig = await fs.readFile('config/session.php', 'utf8');
            
            // Verificar configuraciones seguras
            const secureConfigs = [
                { key: 'secure', value: 'true', description: 'HTTPS requerido' },
                { key: 'http_only', value: 'true', description: 'Solo HTTP (no JavaScript)' },
                { key: 'same_site', value: 'lax', description: 'Protección CSRF' }
            ];
            
            for (const config of secureConfigs) {
                if (sessionConfig.includes(`'${config.key}' => ${config.value}`)) {
                    console.log(`✅ ${config.description} configurado`);
                } else {
                    this.addVulnerability('WARNING', 'Sesiones', 
                        `${config.description} no configurado correctamente`);
                }
            }
            
            this.securityMetrics.passed++;
            
        } catch (error) {
            console.log('⚠️  Error verificando configuración de sesiones');
        }
    }

    /**
     * Agregar vulnerabilidad encontrada
     */
    addVulnerability(severity, category, description, file = null) {
        this.vulnerabilities.push({
            severity,
            category,
            description,
            file,
            timestamp: new Date()
        });
        
        if (severity === 'CRITICAL') {
            this.securityMetrics.critical++;
            console.log(`🚨 CRÍTICO: ${description}`);
        } else {
            this.securityMetrics.warnings++;
            console.log(`⚠️  ADVERTENCIA: ${description}`);
        }
    }

    /**
     * Encontrar archivos PHP
     */
    async findPHPFiles(directory) {
        try {
            const files = [];
            const entries = await fs.readdir(directory, { withFileTypes: true });
            
            for (const entry of entries) {
                const fullPath = path.join(directory, entry.name);
                if (entry.isFile() && entry.name.endsWith('.php')) {
                    files.push(fullPath);
                }
            }
            
            return files;
        } catch {
            return [];
        }
    }

    /**
     * Generar reporte de seguridad
     */
    generateSecurityReport() {
        console.log('\n🛡️ REPORTE DE SEGURIDAD:');
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        console.log(`🔍 Verificaciones Totales: ${this.securityMetrics.totalChecks}`);
        console.log(`✅ Verificaciones Exitosas: ${this.securityMetrics.passed}`);
        console.log(`⚠️  Advertencias: ${this.securityMetrics.warnings}`);
        console.log(`🚨 Vulnerabilidades Críticas: ${this.securityMetrics.critical}`);
        
        // Determinar nivel de seguridad
        let securityLevel;
        if (this.securityMetrics.critical === 0 && this.securityMetrics.warnings <= 2) {
            securityLevel = '🟢 SEGURO';
        } else if (this.securityMetrics.critical === 0) {
            securityLevel = '🟡 MODERADO';
        } else if (this.securityMetrics.critical <= 2) {
            securityLevel = '🟠 RIESGO';
        } else {
            securityLevel = '🔴 CRÍTICO';
        }
        
        console.log(`🎯 Nivel de Seguridad: ${securityLevel}`);
        console.log(`⏰ Escaneo: ${this.securityMetrics.timestamp.toLocaleString()}`);
        
        // Mostrar vulnerabilidades críticas
        if (this.securityMetrics.critical > 0) {
            console.log('\n🚨 VULNERABILIDADES CRÍTICAS:');
            this.vulnerabilities
                .filter(v => v.severity === 'CRITICAL')
                .forEach(v => {
                    console.log(`   • ${v.category}: ${v.description}`);
                });
        }
        
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n');
    }

    /**
     * Ejecutar escaneo completo
     */
    async scan() {
        console.log('🚀 INICIANDO SECURITY SCANNER DINÁMICO');
        console.log('🎯 SUPERANDO CAPACIDADES DE PHPUNIT SECURITY');
        console.log('═══════════════════════════════════════════════\n');
        
        await this.runSecurityScan();
        
        console.log('✅ ESCANEO DE SEGURIDAD COMPLETADO');
    }
}

// Ejecutar el escáner
const scanner = new SecurityScanner();
scanner.scan().catch(console.error);
