<?php

return [
    'default' => 'covers',

    'disks' => [
        // Portadas en disco local. Cambiar a S3 no toca dominio ni aplicación:
        // solo este adaptador y una línea de la composición (OCP, artículo IV).
        'covers' => [
            'driver' => 'local',
            'root' => base_path(env('COVERS_DIR', 'storage/app/covers')),
            'throw' => true,
        ],
    ],
];
