<#
    Habilita en php.ini las extensiones que Ingecon necesita para correr
    (Argon2id/sodium vía las funciones nativas de PHP, GD para procesar
    logotipos e imágenes, SQLite para la BD de desarrollo, etc.).

    Muchas instalaciones "de fábrica" (en particular la de Scoop) traen estas
    líneas presentes en php.ini pero comentadas con ";" — este script las
    descomenta si hace falta. Es seguro ejecutarlo repetidas veces: si ya
    están habilitadas, no hace nada.
#>
param(
    [Parameter(Mandatory = $true)]
    [string]$PhpExe
)

$requeridas = @(
    'fileinfo',   # RNF04/RNF06: validar el tipo real de los archivos (finfo)
    'gd',         # Redimensionar/leer imágenes de proyectos y colaboradores
    'mbstring',   # Manejo de cadenas UTF-8 (nombres, mensajes)
    'curl',       # Peticiones salientes (verificación de dominios de correo, etc.)
    'openssl',    # Hashing y generación de tokens seguros
    'pdo_mysql',  # Motor de la BD del proyecto (MySQL, igual que el hosting)
    'mysqli',     # Utilidades de MySQL para herramientas externas
    'pdo_sqlite', # Alternativa liviana para pruebas locales
    'sqlite3'
)

try {
    $salidaIni = & $PhpExe --ini 2>$null
} catch {
    Write-Host "  [AVISO] No se pudo ejecutar PHP para leer su configuración." -ForegroundColor Yellow
    exit 0
}

$lineaIni = $salidaIni | Select-String 'Loaded Configuration File:\s*(.+)'
if (-not $lineaIni) {
    Write-Host "  [AVISO] PHP no reporta un php.ini cargado; se omite el ajuste de extensiones." -ForegroundColor Yellow
    exit 0
}

$iniPath = $lineaIni.Matches[0].Groups[1].Value.Trim()
if ($iniPath -eq '(none)' -or [string]::IsNullOrWhiteSpace($iniPath) -or -not (Test-Path $iniPath)) {
    Write-Host "  [AVISO] No hay un php.ini activo; se omite el ajuste de extensiones." -ForegroundColor Yellow
    exit 0
}

$contenido = Get-Content -Raw -Path $iniPath
$original = $contenido
$habilitadas = @()

foreach ($ext in $requeridas) {
    $patron = "(?m)^;\s*extension\s*=\s*$ext\s*$"
    if ($contenido -match $patron) {
        $contenido = [System.Text.RegularExpressions.Regex]::Replace($contenido, $patron, "extension=$ext")
        $habilitadas += $ext
    }
}

if ($contenido -ne $original) {
    Set-Content -NoNewline -Path $iniPath -Value $contenido
    Write-Host ("  Extensiones habilitadas en php.ini: " + ($habilitadas -join ', '))
} else {
    Write-Host "  OK: extensiones ya estaban habilitadas."
}

# Aviso final si alguna sigue sin aparecer activa (puede requerir reinstalar PHP).
$activas = (& $PhpExe -m 2>$null) | ForEach-Object { $_.ToLower() }
$faltantes = $requeridas | Where-Object { $activas -notcontains $_.ToLower() }
if ($faltantes) {
    Write-Host ("  [AVISO] Siguen sin aparecer activas: " + ($faltantes -join ', ') + ". Puede requerir revisar la instalación de PHP.") -ForegroundColor Yellow
}
