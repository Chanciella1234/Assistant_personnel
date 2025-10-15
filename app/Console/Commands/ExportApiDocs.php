<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportApiDocs extends Command
{
    /**
     * Nom de la commande Artisan
     */
    protected $signature = 'docs:export';

    /**
     * Description
     */
    protected $description = 'Génère une version statique (HTML+CSS+JS) de la documentation Scribe pour partage hors serveur';

    public function handle()
    {
        $this->info('🚀 Génération de la documentation API avec Scribe...');
        $this->call('scribe:generate');

        $this->info('📁 Préparation du dossier exportable...');

        // Dossier d'export
        $exportPath = public_path('docs_export');
        if (File::exists($exportPath)) {
            File::deleteDirectory($exportPath);
        }
        File::makeDirectory($exportPath, 0755, true);

        // Copier les assets (CSS, JS, images)
        File::copyDirectory(public_path('vendor/scribe'), $exportPath . '/vendor/scribe');

        // Copier la vue générée
        $indexView = resource_path('views/scribe/index.blade.php');
        $indexHtml = file_get_contents($indexView);

        // Corriger les chemins (de /vendor/... à ./vendor/...)
        $indexHtml = str_replace('/vendor/scribe/', './vendor/scribe/', $indexHtml);

        // Sauvegarder dans docs_export/index.html
        file_put_contents($exportPath . '/index.html', $indexHtml);

        $this->info('✅ Documentation exportée avec succès dans : ' . $exportPath);
        $this->info('➡️ Tu peux compresser le dossier et l’envoyer au dev front-end.');
    }
}
