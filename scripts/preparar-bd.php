<?php

/**
 * Prepara la base de datos MySQL antes de las migraciones.
 *
 * Lee las credenciales del .env de web/nucleo/ y, si la base indicada en
 * DB_DATABASE todavia no existe, la crea con utf8mb4_unicode_ci (la misma
 * colacion que declara config/database.php).
 *
 * No pide ni guarda contrasenas: usa las que ya estan en el .env. Si la
 * conexion falla, explica en una linea que hay que corregir.
 *
 * Uso:  php scripts/preparar-bd.php  [ruta-al-.env]
 * Codigos de salida: 0 todo listo / 1 hay algo que corregir.
 */

$env = $argv[1] ?? __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
    . 'web' . DIRECTORY_SEPARATOR . 'nucleo' . DIRECTORY_SEPARATOR . '.env';

if (!is_file($env)) {
    fwrite(STDERR, "  [ERROR] No se encontro el archivo .env en: $env\n");
    exit(1);
}

// --- leer el .env sin dependencias ---
$cfg = [];
foreach (file($env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
    $linea = trim($linea);
    if ($linea === '' || $linea[0] === '#' || !str_contains($linea, '=')) {
        continue;
    }
    [$clave, $valor] = explode('=', $linea, 2);
    $cfg[trim($clave)] = trim(trim($valor), "\"'");
}

$conexion = $cfg['DB_CONNECTION'] ?? 'mysql';

if ($conexion !== 'mysql') {
    echo "  OK: DB_CONNECTION=$conexion, no hay base MySQL que preparar.\n";
    exit(0);
}

if (!extension_loaded('pdo_mysql')) {
    fwrite(STDERR, "  [ERROR] Falta la extension pdo_mysql de PHP.\n");
    fwrite(STDERR, "          Descomenta 'extension=pdo_mysql' en el php.ini y vuelve a ejecutar.\n");
    exit(1);
}

$host = $cfg['DB_HOST'] ?? '127.0.0.1';
$puerto = $cfg['DB_PORT'] ?? '3306';
$base = $cfg['DB_DATABASE'] ?? '';
$usuario = $cfg['DB_USERNAME'] ?? 'root';
$clave = $cfg['DB_PASSWORD'] ?? '';

if ($base === '') {
    fwrite(STDERR, "  [ERROR] DB_DATABASE esta vacio en el .env.\n");
    exit(1);
}

try {
    // Conexion al servidor, sin nombre de base: la base puede no existir todavia.
    $pdo = new PDO("mysql:host=$host;port=$puerto", $usuario, $clave, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10,
    ]);
} catch (PDOException $e) {
    $codigo = (int) $e->getCode();
    fwrite(STDERR, "  [ERROR] No se pudo conectar a MySQL en $host:$puerto.\n");
    if ($codigo === 1045) {
        fwrite(STDERR, "          Usuario o contrasena incorrectos. Abre web\\nucleo\\.env y escribe\n");
        fwrite(STDERR, "          tu contrasena de MySQL en la linea DB_PASSWORD=\n");
    } elseif ($codigo === 2002) {
        fwrite(STDERR, "          El servidor no responde. Revisa que el servicio MySQL este iniciado.\n");
    } else {
        fwrite(STDERR, "          " . $e->getMessage() . "\n");
    }
    exit(1);
}

$existia = (bool) $pdo->query(
    "SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = " . $pdo->quote($base)
)->fetchColumn();

if ($existia) {
    echo "  OK: la base de datos '$base' ya existe.\n";
    exit(0);
}

// El nombre viene del .env local, no de una entrada del Usuario; aun asi se
// acota a lo que MySQL admite como identificador antes de interpolarlo.
if (!preg_match('/^[A-Za-z0-9_]+$/', $base)) {
    fwrite(STDERR, "  [ERROR] El nombre '$base' no es valido para una base de datos.\n");
    fwrite(STDERR, "          Use solo letras, numeros y guion bajo en DB_DATABASE.\n");
    exit(1);
}

$pdo->exec("CREATE DATABASE `$base` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo "  Base de datos '$base' creada (utf8mb4_unicode_ci).\n";
exit(0);
