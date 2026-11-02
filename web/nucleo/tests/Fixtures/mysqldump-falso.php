<?php

// mysqldump falso para las pruebas de RNF14: anota cómo fue llamado y escribe un volcado.
$registro = getenv('MYSQLDUMP_FALSO_REGISTRO');
file_put_contents($registro, json_encode(['argumentos' => array_slice($argv, 1), 'mysql_pwd' => getenv('MYSQL_PWD')]));

$destino = null;
foreach ($argv as $argumento) {
    if (str_starts_with($argumento, '--result-file=')) {
        $destino = substr($argumento, strlen('--result-file='));
    }
}
if ($destino) {
    file_put_contents($destino, getenv('MYSQLDUMP_FALSO_FALLA') ? '-- volcado incompleto' : "-- volcado de prueba\nCREATE TABLE consultas (id INT);\n");
}

exit(getenv('MYSQLDUMP_FALSO_FALLA') ? 2 : 0);
