<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ZipArchive;

class BackupPublic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup-public';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generar un backup comprimido de la carpeta public/';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $publicPath = public_path();
        $backupPath = storage_path('app/public-backup.zip');

        $this->info(' Generando backup de carpeta public...');

        $zip = new \ZipArchive();

        if ($zip->open($backupPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            $this->error(' No se pudo crear el archivo ZIP.');
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($publicPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = ltrim(str_replace($publicPath, '', $filePath), DIRECTORY_SEPARATOR);
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();

        $this->info(" Backup completado con éxito: {$backupPath}");
    }
}
