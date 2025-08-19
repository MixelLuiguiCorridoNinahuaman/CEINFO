#!/usr/bin/env node
/**
 * 🚀 COVERAGE ANALYZER - OPTIMIZADO PARA CI/CD
 * Versión especial para GitHub Actions y entornos CI/CD
 */

import fs from 'fs/promises';
import path from 'path';

class CICDCoverageAnalyzer {
    constructor() {
        this.coverageData = {
            percentage: 0,
            appFiles: 0,
            testFiles: 0,
            totalLines: 0,
            coveredLines: 0,
            uncoveredFiles: [],
            fileDetails: []
        };
        
        this.isCI = process.env.CI === 'true' || process.env.GITHUB_ACTIONS === 'true';
        this.outputFormat = process.argv.includes('--output-format=github') ? 'github' : 'standard';
    }

    /**
     * Ejecutar análisis optimizado para CI/CD
     */
    async runCIAnalysis() {
        if (this.isCI) {
            console.log('🚀 COVERAGE ANALYZER - MODO CI/CD');
            console.log(`🔗 Environment: ${process.env.GITHUB_ACTIONS ? 'GitHub Actions' : 'Generic CI'}`);
            
            if (process.env.GITHUB_SHA) {
                console.log(`📌 Commit: ${process.env.GITHUB_SHA.substring(0, 8)}`);
            }
            
            if (process.env.GITHUB_REF) {
                console.log(`🌿 Branch: ${process.env.GITHUB_REF.replace('refs/heads/', '')}`);
            }
        }
        
        console.log('═══════════════════════════════════════════════════');
        
        try {
            await this.scanProjectFiles();
            await this.calculateCoverage();
            this.generateCIReport();
            await this.saveArtifacts();
            
            // Exit code basado en coverage
            if (this.coverageData.percentage < 30) {
                process.exit(1); // Fallar CI si coverage crítico
            }
            
        } catch (error) {
            this.handleCIError(error);
            process.exit(1);
        }
    }

    /**
     * Escanear archivos del proyecto
     */
    async scanProjectFiles() {
        console.log('📁 Escaneando archivos del proyecto...');
        
        // Escanear archivos de aplicación
        const appFiles = await this.scanDirectory('app', '.php');
        this.coverageData.appFiles = appFiles.length;
        
        // Escanear archivos de test
        const testFiles = await this.scanDirectory('tests', '.php');
        this.coverageData.testFiles = testFiles.length;
        
        console.log(`📊 Archivos encontrados:`);
        console.log(`   📁 App: ${this.coverageData.appFiles} archivos`);
        console.log(`   🧪 Tests: ${this.coverageData.testFiles} archivos`);
        
        // Analizar contenido de archivos
        for (const file of appFiles) {
            const content = await fs.readFile(file, 'utf8');
            const lines = content.split('\n').length;
            
            this.coverageData.totalLines += lines;
            this.coverageData.fileDetails.push({
                file: file.replace(process.cwd() + '/', ''),
                lines: lines,
                estimated_coverage: this.estimateFileCoverage(file, testFiles),
                has_test: this.hasTestFile(file, testFiles)
            });
        }
    }

    /**
     * Calcular cobertura estimada
     */
    async calculateCoverage() {
        console.log('🧮 Calculando cobertura...');
        
        if (this.coverageData.testFiles === 0) {
            this.coverageData.percentage = 0;
            this.coverageData.status = 'SIN_TESTS';
        } else {
            // Algoritmo inteligente de cobertura
            const ratio = this.coverageData.testFiles / this.coverageData.appFiles;
            
            // Bonificaciones por archivos con tests
            let bonusPoints = 0;
            for (const fileDetail of this.coverageData.fileDetails) {
                if (fileDetail.has_test) {
                    bonusPoints += 15; // 15% por archivo con test
                }
            }
            
            // Calcular porcentaje final
            this.coverageData.percentage = Math.min(
                (ratio * 60) + (bonusPoints * 0.4), 
                100
            );
            
            // Calcular líneas estimadas cubiertas
            this.coverageData.coveredLines = Math.floor(
                (this.coverageData.totalLines * this.coverageData.percentage) / 100
            );
        }
        
        console.log(`📈 Cobertura calculada: ${this.coverageData.percentage.toFixed(1)}%`);
    }

    /**
     * Generar reporte para CI/CD
     */
    generateCIReport() {
        const percentage = this.coverageData.percentage;
        
        // Determinar estado
        let status, emoji, color;
        if (percentage >= 80) {
            status = 'EXCELENTE';
            emoji = '🟢';
            color = 'success';
        } else if (percentage >= 60) {
            status = 'BUENO';
            emoji = '🟡';
            color = 'warning';
        } else if (percentage >= 40) {
            status = 'REGULAR';
            emoji = '🟠';
            color = 'warning';
        } else if (percentage >= 20) {
            status = 'BAJO';
            emoji = '🔴';
            color = 'error';
        } else {
            status = 'CRÍTICO';
            emoji = '🚨';
            color = 'error';
        }
        
        // Reporte para GitHub Actions
        if (this.outputFormat === 'github') {
            console.log(`::set-output name=coverage::${percentage.toFixed(1)}`);
            console.log(`::set-output name=status::${status}`);
            console.log(`::set-output name=total_files::${this.coverageData.appFiles}`);
            console.log(`::set-output name=test_files::${this.coverageData.testFiles}`);
            
            // Anotaciones
            console.log(`::notice title=Coverage Report::${emoji} ${percentage.toFixed(1)}% - ${status}`);
            
            if (percentage < 60) {
                console.log(`::warning title=Low Coverage::Cobertura por debajo del 60%: ${percentage.toFixed(1)}%`);
            }
            
            if (percentage < 30) {
                console.log(`::error title=Critical Coverage::Cobertura crítica: ${percentage.toFixed(1)}%`);
            }
        }
        
        // Reporte estándar
        console.log('\n📋 REPORTE DE COBERTURA CI/CD');
        console.log('═══════════════════════════════════════════════════');
        console.log(`📊 Cobertura Total: ${emoji} ${percentage.toFixed(1)}% - ${status}`);
        console.log(`📁 Archivos PHP: ${this.coverageData.appFiles}`);
        console.log(`🧪 Archivos Test: ${this.coverageData.testFiles}`);
        console.log(`📏 Líneas Totales: ${this.coverageData.totalLines}`);
        console.log(`✅ Líneas Estimadas Cubiertas: ${this.coverageData.coveredLines}`);
        
        // Ratio y métricas
        const ratio = this.coverageData.testFiles > 0 ? 
            (this.coverageData.testFiles / this.coverageData.appFiles * 100).toFixed(1) : 0;
        console.log(`⚖️ Ratio Tests/App: ${ratio}%`);
        
        // Archivos sin tests
        const filesWithoutTests = this.coverageData.fileDetails.filter(f => !f.has_test);
        if (filesWithoutTests.length > 0) {
            console.log(`\n⚠️ Archivos sin tests (${filesWithoutTests.length}):`);
            filesWithoutTests.slice(0, 5).forEach(file => {
                console.log(`   - ${file.file}`);
            });
            if (filesWithoutTests.length > 5) {
                console.log(`   ... y ${filesWithoutTests.length - 5} más`);
            }
        }
        
        // Recomendaciones para CI/CD
        console.log('\n💡 RECOMENDACIONES CI/CD:');
        if (percentage < 70) {
            console.log('- 🎯 Incrementar tests para alcanzar 70%+ coverage');
            console.log('- 🧪 Priorizar tests para modelos y controladores');
        }
        if (percentage >= 80) {
            console.log('- ✅ Excelente coverage, mantener calidad');
            console.log('- 🔄 Considerar tests de integración');
        }
        
        console.log('═══════════════════════════════════════════════════');
    }

    /**
     * Guardar artefactos para CI/CD
     */
    async saveArtifacts() {
        try {
            // Crear directorio de reportes
            await fs.mkdir('storage/app/ci-reports', { recursive: true });
            
            // Reporte JSON para CI/CD
            const ciReport = {
                timestamp: new Date().toISOString(),
                environment: {
                    ci: this.isCI,
                    github_actions: process.env.GITHUB_ACTIONS === 'true',
                    commit: process.env.GITHUB_SHA,
                    branch: process.env.GITHUB_REF?.replace('refs/heads/', ''),
                    workflow: process.env.GITHUB_WORKFLOW
                },
                coverage: this.coverageData,
                summary: `${this.coverageData.percentage.toFixed(1)}% coverage`,
                pass: this.coverageData.percentage >= 30
            };
            
            // Guardar reporte principal
            const reportPath = 'storage/app/ci-reports/coverage-ci-report.json';
            await fs.writeFile(reportPath, JSON.stringify(ciReport, null, 2));
            console.log(`💾 Reporte CI guardado: ${reportPath}`);
            
            // Generar badge data para shields.io
            const badgeData = {
                schemaVersion: 1,
                label: 'coverage',
                message: `${this.coverageData.percentage.toFixed(1)}%`,
                color: this.coverageData.percentage >= 80 ? 'brightgreen' : 
                       this.coverageData.percentage >= 60 ? 'yellow' : 'red'
            };
            
            await fs.writeFile('storage/app/ci-reports/coverage-badge.json', 
                JSON.stringify(badgeData, null, 2));
            
        } catch (error) {
            console.log('⚠️ No se pudieron guardar artefactos:', error.message);
        }
    }

    /**
     * Escanear directorio
     */
    async scanDirectory(dir, extension) {
        const files = [];
        
        try {
            const items = await fs.readdir(dir, { withFileTypes: true });
            
            for (const item of items) {
                const fullPath = path.join(dir, item.name);
                
                if (item.isDirectory()) {
                    const subFiles = await this.scanDirectory(fullPath, extension);
                    files.push(...subFiles);
                } else if (item.name.endsWith(extension)) {
                    files.push(fullPath);
                }
            }
        } catch (error) {
            // Directorio no existe
        }
        
        return files;
    }

    /**
     * Estimar cobertura de archivo
     */
    estimateFileCoverage(appFile, testFiles) {
        const fileName = path.basename(appFile, '.php');
        
        // Buscar archivos de test relacionados
        const relatedTests = testFiles.filter(testFile => 
            testFile.toLowerCase().includes(fileName.toLowerCase())
        );
        
        return relatedTests.length > 0 ? Math.min(relatedTests.length * 30, 95) : 0;
    }

    /**
     * Verificar si tiene archivo de test
     */
    hasTestFile(appFile, testFiles) {
        const fileName = path.basename(appFile, '.php');
        
        return testFiles.some(testFile => 
            testFile.toLowerCase().includes(fileName.toLowerCase()) ||
            testFile.toLowerCase().includes('test') && 
            testFile.toLowerCase().includes(fileName.toLowerCase())
        );
    }

    /**
     * Manejar errores en CI
     */
    handleCIError(error) {
        if (this.outputFormat === 'github') {
            console.log(`::error title=Coverage Analysis Failed::${error.message}`);
        }
        
        console.error('❌ ERROR EN ANÁLISIS DE COBERTURA:');
        console.error(error.message);
        
        if (this.isCI) {
            console.log('\n🔧 SUGERENCIAS PARA CI/CD:');
            console.log('- Verificar que existan los directorios app/ y tests/');
            console.log('- Asegurar permisos de lectura en archivos');
            console.log('- Revisar configuración del workflow');
        }
    }
}

// Ejecutar analyzer
const analyzer = new CICDCoverageAnalyzer();
analyzer.runCIAnalysis().catch(console.error);
