<?php

namespace Tests\Unit;

use App\Support\Rnf17;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * FASE 12 — la fuente única RNF17 fija límites y formatos sin tocar aún controladores.
 */
class Rnf17Test extends TestCase
{
    public function test_constantes_vigentes()
    {
        $this->assertSame(500, Rnf17::LOGO_MAX_KB);
        $this->assertSame(2048, Rnf17::IMAGEN_MAX_KB);
        $this->assertSame(['jpg', 'jpeg', 'png'], Rnf17::MIMES_IMAGEN);
        $this->assertStringNotContainsString('webp', Rnf17::mimesImagen());
        $this->assertStringNotContainsString('mp4', Rnf17::mimesImagen());
    }

    public function test_reglas_aceptan_jpg_y_png_validos()
    {
        $jpg = UploadedFile::fake()->image('obra.jpg');
        $png = UploadedFile::fake()->image('obra.png');

        $this->assertFalse(Validator::make(['a' => $jpg], ['a' => Rnf17::reglasImagen()])->fails());
        $this->assertFalse(Validator::make(['a' => $png], ['a' => Rnf17::reglasImagen()])->fails());
    }

    public function test_reglas_rechazan_webp()
    {
        $webp = UploadedFile::fake()->createWithContent('obra.webp', 'contenido-falso-webp');

        $this->assertTrue(Validator::make(['a' => $webp], ['a' => Rnf17::reglasImagen()])->fails());
    }

    public function test_reglas_rechazan_imagen_mayor_a_2mb()
    {
        $grande = UploadedFile::fake()->image('obra.jpg')->size(2100); // kB

        $this->assertTrue(Validator::make(['a' => $grande], ['a' => Rnf17::reglasImagen()])->fails());
    }

    public function test_logo_rechaza_mas_de_500kb_y_acepta_por_debajo()
    {
        $logoGrande = UploadedFile::fake()->image('logo.png')->size(600);
        $logoOk = UploadedFile::fake()->image('logo.png')->size(499);

        $this->assertTrue(Validator::make(['a' => $logoGrande], ['a' => Rnf17::reglasLogo()])->fails());
        $this->assertFalse(Validator::make(['a' => $logoOk], ['a' => Rnf17::reglasLogo()])->fails());
    }

    public function test_mensaje_limite_formatea_mb_y_kb()
    {
        $this->assertSame('2 MB', Rnf17::mensajeLimite(2048));
        $this->assertSame('500 KB', Rnf17::mensajeLimite(500));
    }
}
