#!/usr/bin/env node

/**
 * 🔍 DIAGNÓSTICO DE CI/CD - CEINFO
 * ================================
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const PROJECT_ROOT = path.resolve(__dirname, '..');

console.log('🚀 INICIANDO DIAGNÓSTICO DE CI/CD - CEINFO');
console.log('==========================================\n');

let checks = 0;
let warnings = 0;
let errors = 0;

function checkFile(filePath, description) {
    const fullPath = path.resolve(PROJECT_ROOT, filePath);
    if (fs.existsSync(fullPath)) {
        console.log(`✅ ${description}: ${filePath} ✓`);
        checks++;
        return true;
    } else {
        console.error(`❌ ${description}: ${filePath} - FALTA`);
        errors++;
        return false;
    }
}

function checkWarning(condition, message) {
    if (condition) {
        console.warn(`⚠️  ${message}`);
        warnings++;
    }
}

// Verificaciones principales
console.log('🔍 Verificando archivos esenciales...');
checkFile('package.json', 'Package.json');
checkFile('package-lock.json', 'Package-lock.json');
checkFile('composer.json', 'Composer.json');
checkFile('composer.lock', 'Composer.lock');
checkFile('phpunit.xml', 'PHPUnit config');

console.log('\n🔍 Verificando workflows...');
checkFile('.github/workflows/qa-dynamic.yml', 'Workflow principal');
checkFile('.github/workflows/pr-check.yml', 'Workflow PR');
checkFile('.github/workflows/security-night-scan.yml', 'Workflow seguridad');

console.log('\n🔍 Verificando scripts...');
checkFile('scripts/coverage-analyzer-fixed.js', 'Coverage analyzer');
checkFile('scripts/security-scanner.js', 'Security scanner');
checkFile('scripts/qa-orchestrator.js', 'QA orchestrator');

// Verificar contenido de composer.json
if (fs.existsSync(path.resolve(PROJECT_ROOT, 'composer.json'))) {
    try {
        const composer = JSON.parse(fs.readFileSync(path.resolve(PROJECT_ROOT, 'composer.json'), 'utf8'));
        if (composer.require && composer.require.php) {
            console.log(`ℹ️  PHP requerido: ${composer.require.php}`);
            checkWarning(composer.require.php.includes('8.1'), 'PHP 8.1 puede causar problemas');
        }
    } catch (e) {
        console.error(`❌ Error leyendo composer.json: ${e.message}`);
        errors++;
    }
}

console.log('\n' + '='.repeat(50));
console.log(`📊 RESUMEN DEL DIAGNÓSTICO:`);
console.log(`✅ Verificaciones exitosas: ${checks}`);
console.log(`⚠️  Advertencias: ${warnings}`);
console.log(`❌ Errores: ${errors}`);

if (errors === 0) {
    console.log('\n🎉 ¡El entorno CI/CD está listo!');
} else {
    console.log('\n🔧 Se requiere atención para algunos elementos.');
}

console.log(`\n🚀 Para continuar:`);
console.log(`   1. Corrige cualquier error mostrado arriba`);
console.log(`   2. Ejecuta: git add . && git commit -m "Fix CI/CD" && git push`);
console.log(`   3. Ve a GitHub Actions para monitorear la ejecución`);
console.log(`   4. Accede al dashboard: http://127.0.0.1:8000/scripts-monitor.html`);

process.exit(errors > 0 ? 1 : 0);
