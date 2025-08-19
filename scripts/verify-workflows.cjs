#!/usr/bin/env node

/**
 * 🔍 VERIFICADOR DE WORKFLOWS - CEINFO
 * =====================================
 * 
 * Script para verificar y limpiar problemas en workflows de GitHub Actions
 */

const fs = require('fs');
const path = require('path');

const PROJECT_ROOT = path.resolve(__dirname, '..');
const WORKFLOWS_DIR = path.resolve(PROJECT_ROOT, '.github/workflows');

console.log('🔍 VERIFICANDO WORKFLOWS DE GITHUB ACTIONS');
console.log('===========================================\n');

function verifyWorkflow(filePath) {
    console.log(`📋 Verificando: ${path.basename(filePath)}`);
    
    try {
        const content = fs.readFileSync(filePath, 'utf8');
        
        // Verificaciones básicas
        const checks = [
            { name: 'Tiene nombre', test: content.includes('name:') },
            { name: 'Tiene triggers', test: content.includes('on:') },
            { name: 'Tiene jobs', test: content.includes('jobs:') },
            { name: 'Sin caracteres extraños', test: !/[^\x00-\x7F]/g.test(content) },
            { name: 'Indentación correcta', test: !content.includes('\t') },
            { name: 'Actions actualizadas', test: !content.includes('@v1') && !content.includes('@v2') }
        ];
        
        let allPassed = true;
        checks.forEach(check => {
            if (check.test) {
                console.log(`  ✅ ${check.name}`);
            } else {
                console.log(`  ❌ ${check.name}`);
                allPassed = false;
            }
        });
        
        return allPassed;
        
    } catch (error) {
        console.log(`  ❌ Error leyendo archivo: ${error.message}`);
        return false;
    }
}

function main() {
    if (!fs.existsSync(WORKFLOWS_DIR)) {
        console.log('❌ Directorio .github/workflows no encontrado');
        return;
    }
    
    const workflowFiles = fs.readdirSync(WORKFLOWS_DIR)
        .filter(file => file.endsWith('.yml') || file.endsWith('.yaml'));
    
    if (workflowFiles.length === 0) {
        console.log('⚠️  No se encontraron archivos de workflow');
        return;
    }
    
    console.log(`📂 Encontrados ${workflowFiles.length} workflows:\n`);
    
    let allWorkflowsValid = true;
    
    workflowFiles.forEach(file => {
        const filePath = path.resolve(WORKFLOWS_DIR, file);
        const isValid = verifyWorkflow(filePath);
        
        if (!isValid) {
            allWorkflowsValid = false;
        }
        
        console.log('');
    });
    
    console.log('='.repeat(50));
    
    if (allWorkflowsValid) {
        console.log('🎉 ¡Todos los workflows están correctos!');
        console.log('\n💡 Si VS Code sigue mostrando errores:');
        console.log('   1. Presiona Ctrl+Shift+P');
        console.log('   2. Ejecuta "Developer: Reload Window"');
        console.log('   3. O reinicia VS Code completamente');
    } else {
        console.log('⚠️  Algunos workflows tienen problemas');
        console.log('   Revisa los errores mostrados arriba');
    }
    
    // Información adicional para debugging
    console.log(`\n📊 Información del sistema:`);
    console.log(`   - Directorio workflows: ${WORKFLOWS_DIR}`);
    console.log(`   - Archivos encontrados: ${workflowFiles.join(', ')}`);
    console.log(`   - Encoding: UTF-8`);
}

main();
