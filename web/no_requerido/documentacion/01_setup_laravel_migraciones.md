# 01 — Setup Laravel 11 + Migraciones PostgreSQL 16
## Plataforma Web Ingecon — Especificación Técnica

> **Destinatario:** Modelo de lenguaje generador de código.  
> **Propósito:** Proveer instrucciones completas y sin ambigüedad para instalar, configurar y migrar la base de datos de la plataforma Ingecon sobre Laravel 11 + PostgreSQL 16.  
> **Stack fijo:** Laravel 11 · PHP 8.3 · PostgreSQL 16 · Argon2id · Spatie Laravel Excel · Nginx.

---

## 1. Instalación del Proyecto Laravel 11

### 1.1 Crear el proyecto

```bash
composer create-project laravel/laravel ingecon-app "^11.0"
cd ingecon-app
```

### 1.2 Verificar versión de PHP

```bash
php -v
# Debe reportar PHP 8.3.x
```

---

## 2. Configuración del Archivo `.env`

Editar el archivo `.env` en la raíz del proyecto. Reemplazar **todos** los valores de base de datos con los siguientes:

```ini
APP_NAME="Ingecon"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ingecon_db
DB_USERNAME=ingecon_user
DB_PASSWORD=secret_password

BROADCAST_CONNECTION=log
CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database
SESSION_LIFETIME=120

MAIL_MAILER=log
```

> **Nota:** `APP_KEY` se genera automáticamente con `php artisan key:generate`. Ejecutar después de configurar el `.env`.

```bash
php artisan key:generate
```

---

## 3. Configuración del Driver de Hashing Argon2id

### 3.1 Publicar configuración de hashing

```bash
php artisan config:publish hashing
```

Si el comando no está disponible en Laravel 11, copiar manualmente:

```bash
cp vendor/laravel/framework/src/Illuminate/Hashing/config/hashing.php config/hashing.php
```

### 3.2 Editar `config/hashing.php`

Localizar la clave `'driver'` y cambiarla a `'argon2id'`. El archivo completo debe quedar así:

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    | Supported: "bcrypt", "argon", "argon2id"
    */
    'driver' => 'argon2id',

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => true,
    ],

    'argon' => [
        'memory' => 65536,
        'threads' => 1,
        'time' => 4,
        'verify' => true,
    ],

    'argon2id' => [
        'memory' => 65536,
        'threads' => 1,
        'time' => 4,
        'verify' => true,
    ],

];
```

> **Importante:** Los parámetros `memory=65536` (64 MB), `threads=1`, `time=4` son adecuados para producción en servidores con al menos 256 MB de RAM disponible para PHP-FPM.

---

## 4. Instalación de Dependencias

### 4.1 Spatie Laravel Excel

```bash
composer require maatwebsite/excel
```

Publicar la configuración:

```bash
php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider" --tag=config
```

### 4.2 League Flysystem Local (incluido en Laravel, verificar)

```bash
composer require league/flysystem-local "^3.0"
```

### 4.3 Verificar que el driver pgsql de PHP está habilitado

```bash
php -m | grep pdo_pgsql
# Debe imprimir: pdo_pgsql
```

Si no aparece, habilitar en `php.ini`:

```ini
extension=pdo_pgsql
extension=pgsql
```

---

## 5. Creación de la Base de Datos en PostgreSQL 16

Ejecutar como superusuario de PostgreSQL:

```sql
CREATE DATABASE ingecon_db
    WITH ENCODING 'UTF8'
    LC_COLLATE = 'es_CL.UTF-8'
    LC_CTYPE   = 'es_CL.UTF-8'
    TEMPLATE = template0;

CREATE USER ingecon_user WITH ENCRYPTED PASSWORD 'secret_password';

GRANT ALL PRIVILEGES ON DATABASE ingecon_db TO ingecon_user;

-- PostgreSQL 15+ requiere también:
\c ingecon_db
GRANT ALL ON SCHEMA public TO ingecon_user;
```

---

## 6. Migraciones

> **Convención de nombres:** Los archivos de migración usan timestamps incrementales comenzando en `2024_01_01_000001`. El orden es crítico para respetar dependencias de claves foráneas.  
> **Ubicación:** `database/migrations/`  
> **Comando de ejecución:** `php artisan migrate`

---

### 6a. `2024_01_01_000001_create_administradores_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrador', function (Blueprint $table) {
            $table->id('id_admin');                              // BIGINT PK autoincrement
            $table->string('correo', 150)->unique();             // VARCHAR(150) UNIQUE
            $table->string('password_hash', 255);                // VARCHAR(255)
            $table->smallInteger('intentos_fallidos')->default(0); // SMALLINT
            $table->timestamp('bloqueado_hasta')->nullable();    // TIMESTAMP nullable
            $table->boolean('activo')->default(true);            // BOOLEAN
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administrador');
    }
};
```

---

### 6b. `2024_01_01_000002_create_visitantes_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitante', function (Blueprint $table) {
            $table->id('id_visitante');           // BIGINT PK autoincrement
            $table->string('nombre', 80);          // VARCHAR(80)
            $table->string('apellido', 80);        // VARCHAR(80)
            $table->string('email', 150)->unique(); // VARCHAR(150) UNIQUE
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitante');
    }
};
```

---

### 6c. `2024_01_01_000003_create_sesiones_table.php`

> **Dependencia FK:** `administrador.id_admin`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesion', function (Blueprint $table) {
            $table->id('id_sesion');                             // BIGINT PK autoincrement
            $table->string('token_hash', 255)->unique();         // VARCHAR(255) UNIQUE
            $table->timestamp('fecha_inicio');                   // TIMESTAMP NOT NULL
            $table->string('estado', 20);                        // VARCHAR(20)
            $table->unsignedBigInteger('id_admin');              // FK BIGINT

            $table->foreign('id_admin')
                  ->references('id_admin')
                  ->on('administrador')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesion');
    }
};
```

---

### 6d. `2024_01_01_000004_create_consultas_table.php`

> **Dependencias FK:** `visitante.id_visitante`, `administrador.id_admin`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consulta', function (Blueprint $table) {
            $table->id('id_consulta');                            // BIGINT PK autoincrement
            $table->text('mensaje');                              // TEXT
            $table->timestamp('fecha_consulta');                  // TIMESTAMP
            $table->string('estado', 20);                         // VARCHAR(20)
            $table->string('prioridad', 10);                      // VARCHAR(10)
            $table->unsignedBigInteger('id_visitante');           // FK BIGINT
            $table->unsignedBigInteger('id_admin_responsable')->nullable();  // FK BIGINT nullable — se asigna después de crear la consulta

            $table->foreign('id_visitante')
                  ->references('id_visitante')
                  ->on('visitante')
                  ->onDelete('cascade');

            $table->foreign('id_admin_responsable')
                  ->references('id_admin')
                  ->on('administrador')
                  ->onDelete('set null');  // Si se elimina el admin, la consulta queda sin responsable (no se borra)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consulta');
    }
};
```

---

### 6e. `2024_01_01_000005_create_archivos_adjuntos_table.php`

> **Dependencia FK:** `consulta.id_consulta`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archivo_adjunto', function (Blueprint $table) {
            $table->id('id_adjunto');                   // BIGINT PK autoincrement
            $table->binary('archivo_pdf');              // BYTEA
            $table->string('nombre_archivo', 180);      // VARCHAR(180)
            $table->string('tipo_mime', 80);            // VARCHAR(80)
            $table->unsignedBigInteger('id_consulta'); // FK BIGINT

            $table->foreign('id_consulta')
                  ->references('id_consulta')
                  ->on('consulta')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archivo_adjunto');
    }
};
```

---

### 6f. `2024_01_01_000006_create_proyectos_table.php`

> **Dependencia FK:** `administrador.id_admin`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proyecto', function (Blueprint $table) {
            $table->id('id_proyecto');                                        // BIGINT PK autoincrement
            $table->string('nombre_obra', 150);                               // VARCHAR(150) — obligatorio (RF49)
            $table->text('descripcion_tecnica')->nullable();                  // TEXT nullable — se completa al editar (RF50)
            $table->string('region', 80)->nullable();                         // VARCHAR(80) nullable — se completa al editar
            $table->string('ubicacion_geografica', 150)->nullable();          // VARCHAR(150) nullable — se completa al editar
            $table->smallInteger('anio_ejecucion')->nullable();               // SMALLINT nullable — se completa al editar
            $table->string('estado_publicacion', 20)->default('borrador');    // VARCHAR(20) — 'borrador' por defecto al crear (RF49)
            $table->string('categoria', 50)->nullable();                      // VARCHAR(50) nullable — se completa al editar
            $table->unsignedBigInteger('id_admin');                           // FK BIGINT

            $table->foreign('id_admin')
                  ->references('id_admin')
                  ->on('administrador')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyecto');
    }
};
```

---

### 6g. `2024_01_01_000007_create_imagenes_proyecto_table.php`

> **Dependencia FK:** `proyecto.id_proyecto`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imagen_proyecto', function (Blueprint $table) {
            $table->id('id_imagen');                     // BIGINT PK autoincrement
            $table->binary('imagen');                    // BYTEA
            $table->string('nombre_archivo', 180);       // VARCHAR(180)
            $table->string('tipo_mime', 80);             // VARCHAR(80)
            $table->unsignedBigInteger('id_proyecto');  // FK BIGINT

            $table->foreign('id_proyecto')
                  ->references('id_proyecto')
                  ->on('proyecto')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imagen_proyecto');
    }
};
```

---

### 6h. `2024_01_01_000008_create_certificados_table.php`

> **Dependencia FK:** `proyecto.id_proyecto`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificado', function (Blueprint $table) {
            $table->id('id_certificado');                // BIGINT PK autoincrement
            $table->string('codigo_lote', 80);           // VARCHAR(80)
            $table->binary('archivo_pdf');               // BYTEA
            $table->date('fecha_emision');               // DATE
            $table->string('estado', 20);                // VARCHAR(20)
            $table->unsignedBigInteger('id_proyecto');  // FK BIGINT

            $table->foreign('id_proyecto')
                  ->references('id_proyecto')
                  ->on('proyecto')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificado');
    }
};
```

---

### 6i. `2024_01_01_000009_create_colaboradores_table.php`

> **Dependencia FK:** `administrador.id_admin`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colaborador', function (Blueprint $table) {
            $table->id('id_colaborador');                // BIGINT PK autoincrement
            $table->string('nombre_comercial', 120);     // VARCHAR(120)
            $table->binary('logotipo');                  // BYTEA
            $table->string('tipo_mime_logotipo', 80)->default('image/png'); // VARCHAR(80) — necesario para el Data URI en el accessor
            $table->unsignedBigInteger('id_admin');     // FK BIGINT

            $table->foreign('id_admin')
                  ->references('id_admin')
                  ->on('administrador')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colaborador');
    }
};
```

---

### 6j. Ejecutar todas las migraciones

```bash
php artisan migrate
```

Para reiniciar completamente en entorno de desarrollo:

```bash
php artisan migrate:fresh
```

---

## 7. Seeder Inicial — `AdminSeeder`

### 7.1 Crear el seeder

```bash
php artisan make:seeder AdminSeeder
```

### 7.2 Código completo de `database/seeders/AdminSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Crea el administrador inicial de la plataforma Ingecon.
     * Usa Argon2id configurado en config/hashing.php.
     */
    public function run(): void
    {
        // Evitar duplicados si se ejecuta más de una vez
        $existe = DB::table('administrador')
            ->where('correo', 'admin@ingecon.cl')
            ->exists();

        if ($existe) {
            $this->command->warn('El administrador admin@ingecon.cl ya existe. Se omite la inserción.');
            return;
        }

        DB::table('administrador')->insert([
            'correo'            => 'admin@ingecon.cl',
            'password_hash'     => Hash::make('Ingecon2024!'),  // Hash Argon2id
            'intentos_fallidos' => 0,
            'bloqueado_hasta'   => null,
            'activo'            => true,
        ]);

        $this->command->info('Administrador inicial creado: admin@ingecon.cl');
    }
}
```

> **Nota de seguridad:** La contraseña `'Ingecon2024!'` es solo para el entorno inicial de desarrollo. En producción, cambiarla inmediatamente tras el primer login o pasarla via variable de entorno usando `env('ADMIN_INITIAL_PASSWORD')`.

### 7.3 Registrar el seeder en `DatabaseSeeder` (opcional pero recomendado)

Editar `database/seeders/DatabaseSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
        ]);
    }
}
```

### 7.4 Ejecutar el seeder

```bash
# Solo AdminSeeder:
php artisan db:seed --class=AdminSeeder

# O todos los seeders registrados en DatabaseSeeder:
php artisan db:seed
```

### 7.5 Verificar el registro creado

```bash
php artisan tinker
>>> \Illuminate\Support\Facades\DB::table('administrador')->first();
```

La salida debe mostrar el registro con `correo = admin@ingecon.cl`, `activo = true` y `intentos_fallidos = 0`.

---

## 8. Configuración de Nginx

### 8.1 Archivo de configuración del sitio

**Ubicación:** `/etc/nginx/sites-available/ingecon`

```nginx
server {
    listen 80;
    server_name ingecon.cl www.ingecon.cl;

    # Redirigir todo HTTP a HTTPS en producción
    # return 301 https://$host$request_uri;

    root /var/www/ingecon-app/public;
    index index.php index.html;

    charset utf-8;

    # Logs
    access_log /var/log/nginx/ingecon_access.log;
    error_log  /var/log/nginx/ingecon_error.log;

    # Tamaño máximo de subida (PDFs y BYTEA)
    client_max_body_size 20M;

    # Routing SPA-friendly para Laravel
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Procesamiento de PHP via PHP-FPM
    location ~ \.php$ {
        fastcgi_pass   unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include        fastcgi_params;
        fastcgi_read_timeout 120;
    }

    # Denegar acceso a archivos ocultos (.env, .git, etc.)
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Assets estáticos — cache agresivo
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Bloquear acceso directo a storage privado
    location ^~ /storage/ {
        deny all;
    }
}
```

### 8.2 Habilitar el sitio y recargar Nginx

```bash
# Crear enlace simbólico en sites-enabled
sudo ln -s /etc/nginx/sites-available/ingecon /etc/nginx/sites-enabled/ingecon

# Verificar configuración sin errores
sudo nginx -t

# Recargar Nginx
sudo systemctl reload nginx
```

### 8.3 Permisos del directorio Laravel

```bash
sudo chown -R www-data:www-data /var/www/ingecon-app
sudo chmod -R 755 /var/www/ingecon-app
sudo chmod -R 775 /var/www/ingecon-app/storage
sudo chmod -R 775 /var/www/ingecon-app/bootstrap/cache
```

---

## 9. Resumen de Comandos en Orden de Ejecución

```bash
# 1. Crear proyecto
composer create-project laravel/laravel ingecon-app "^11.0"
cd ingecon-app

# 2. Configurar .env (editar manualmente con valores de sección 2)

# 3. Generar APP_KEY
php artisan key:generate

# 4. Instalar dependencias
composer require maatwebsite/excel
composer require league/flysystem-local "^3.0"

# 5. Publicar configuraciones
php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider" --tag=config
php artisan config:publish hashing   # Luego editar config/hashing.php

# 6. Ejecutar migraciones
php artisan migrate

# 7. Ejecutar seeder inicial
php artisan db:seed --class=AdminSeeder

# 8. Configurar Nginx (ver sección 8)
```

---

## 10. Tabla de Correspondencia de Tipos de Datos

| Tipo SQL (diseño) | Tipo Eloquent Blueprint | Resultado PostgreSQL 16 |
|---|---|---|
| `BIGINT` (PK) | `$table->id('nombre_pk')` | `BIGINT` + `SERIAL` |
| `BIGINT` (FK) | `$table->unsignedBigInteger('col')` | `BIGINT UNSIGNED` |
| `VARCHAR(n)` | `$table->string('col', n)` | `VARCHAR(n)` |
| `TEXT` | `$table->text('col')` | `TEXT` |
| `BYTEA` | `$table->binary('col')` | `BYTEA` |
| `SMALLINT` | `$table->smallInteger('col')` | `SMALLINT` |
| `BOOLEAN` | `$table->boolean('col')` | `BOOLEAN` |
| `TIMESTAMP` | `$table->timestamp('col')` | `TIMESTAMP(0)` |
| `DATE` | `$table->date('col')` | `DATE` |

> **Advertencia sobre BYTEA:** Laravel's `$table->binary()` en PostgreSQL crea correctamente columnas `BYTEA`. Sin embargo, al leer datos binarios desde Eloquent, el valor vendrá como un stream de PHP. Usar `stream_get_contents($model->columna)` para obtener el string binario en los controladores.

---

## 11. Configuración PDO para BYTEA (obligatorio)

Laravel con PostgreSQL puede devolver columnas `BYTEA` como streams o como strings según la versión del driver. Para garantizar comportamiento consistente, agregar la opción `PDO::PGSQL_ATTR_DISABLE_PREPARES` en `config/database.php`:

```php
// config/database.php — dentro de 'connections' => ['pgsql' => [...]]
'options' => [
    PDO::ATTR_EMULATE_PREPARES => true,
    // Fuerza que BYTEA se devuelva como string en lugar de resource stream.
    // Sin esto, stream_get_contents() puede fallar en algunas versiones de pgsql.
],
```

El bloque completo de la conexión pgsql debe quedar así:

```php
'pgsql' => [
    'driver'   => 'pgsql',
    'host'     => env('DB_HOST', '127.0.0.1'),
    'port'     => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'ingecon_db'),
    'username' => env('DB_USERNAME', 'ingecon_user'),
    'password' => env('DB_PASSWORD', ''),
    'charset'  => 'utf8',
    'prefix'   => '',
    'schema'   => 'public',
    'sslmode'  => 'prefer',
    'options'  => [
        PDO::ATTR_EMULATE_PREPARES => true,
    ],
],
```

> **Nota:** Con `PDO::ATTR_EMULATE_PREPARES => true`, los accessors de los modelos Eloquent (`ArchivoAdjunto`, `ImagenProyecto`, `Certificado`, `Colaborador`) recibirán el BYTEA como string PHP directamente, por lo que el check `is_resource($raw)` en los accessors seguirá siendo necesario para compatibilidad con entornos sin esta opción.
