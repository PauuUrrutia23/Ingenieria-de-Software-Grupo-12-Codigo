<?php

// RNF14: respaldos de la Base de Datos.
return [
    // Carpeta de las copias; debe quedar fuera de public_html.
    'directorio' => env('BACKUP_DIRECTORIO', storage_path('app/backups')),

    // Días que se conserva cada copia antes de borrarla automáticamente.
    'dias_retencion' => 30,

    // Programa mysqldump y sus argumentos previos; en cPanel puede requerir la ruta completa.
    'mysqldump' => [env('MYSQLDUMP_PATH', 'mysqldump')],
];
