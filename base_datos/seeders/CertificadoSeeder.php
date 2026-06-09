<?php

namespace Database\Seeders;

use App\Models\Administrador;
use App\Models\Certificado;
use App\Models\ImagenProyecto;
use App\Models\Proyecto;
use Illuminate\Database\Seeder;

class CertificadoSeeder extends Seeder
{
    /**
     * Crea datos de prueba para la sección pública de Certificaciones (CU 4.1 / CU 4.2):
     * un proyecto publicado y un certificado "Vigente" con un PDF real almacenado
     * como BYTEA en la columna archivo_pdf.
     *
     * Es idempotente: si el proyecto o el certificado ya existen (por nombre_obra /
     * codigo_lote), se omiten para no duplicar al re-ejecutar el seeder.
     */
    public function run(): void
    {
        // ------------------------------------------------------------------
        // 1) Administrador dueño del proyecto (FK proyecto.id_admin)
        // ------------------------------------------------------------------
        $admin = Administrador::orderBy('id_admin')->first();

        if (! $admin) {
            $this->command->error('No hay administradores. Corre primero AdminSeeder.');
            return;
        }

        // ------------------------------------------------------------------
        // 2) Proyecto publicado al cual asociar el certificado (idempotente)
        // ------------------------------------------------------------------
        $proyecto = Proyecto::firstOrCreate(
            ['nombre_obra' => 'Edificio Mirador Las Condes'],
            [
                'descripcion_tecnica'  => 'Obra habitacional de prueba para visualizar certificaciones.',
                'region'               => 'Region Metropolitana',
                'ubicacion_geografica' => 'Las Condes, Santiago',
                'anio_ejecucion'       => 2024,
                'estado_publicacion'   => 'publicado',
                'categoria'            => 'Habitacional',
                'id_admin'             => $admin->id_admin,
            ]
        );

        // ------------------------------------------------------------------
        // 2b) Imagen de portada del proyecto.
        //     Un proyecto publicado DEBE tener al menos una imagen (regla del
        //     programa). Solo se agrega si el proyecto aún no tiene ninguna,
        //     para no duplicar al re-ejecutar el seeder.
        // ------------------------------------------------------------------
        if (! $proyecto->imagenesProyecto()->exists()) {
            ImagenProyecto::create([
                'imagen'         => $this->generarPng(800, 500, 51, 65, 85), // slate-700
                'nombre_archivo' => 'portada-mirador.png',
                'tipo_mime'      => 'image/png',
                'id_proyecto'    => $proyecto->id_proyecto,
            ]);
            $this->command->info('Imagen de portada agregada al proyecto.');
        }

        // ------------------------------------------------------------------
        // 3) Certificado "Vigente" con PDF real (idempotente por codigo_lote)
        //    El listado público solo muestra certificados con estado='Vigente'.
        // ------------------------------------------------------------------
        $codigoLote = 'LOTE-A-001';

        if (Certificado::where('codigo_lote', $codigoLote)->exists()) {
            $this->command->warn("El certificado {$codigoLote} ya existe. Se omite la inserción.");
            return;
        }

        $pdf = $this->generarPdf(
            'Certificado de Calidad - Lote A-001',
            $proyecto->nombre_obra
        );

        // El mutator archivo_pdf del modelo Certificado convierte el binario a
        // BYTEA mediante decode(hex) automáticamente.
        Certificado::create([
            'codigo_lote'   => $codigoLote,
            'archivo_pdf'   => $pdf,
            'fecha_emision' => '2024-11-15',
            'estado'        => 'Vigente',
            'id_proyecto'   => $proyecto->id_proyecto,
        ]);

        $this->command->info("Certificado {$codigoLote} creado (estado=Vigente, " . strlen($pdf) . " bytes).");
    }

    /**
     * Genera un PDF mínimo pero VÁLIDO (tabla xref con offsets correctos),
     * sin depender de librerías externas. Sirve para probar Ver/Descargar.
     */
    private function generarPdf(string $titulo, string $subtitulo): string
    {
        $contenido = "BT /F1 22 Tf 72 720 Td ($titulo) Tj ET\n"
                   . "BT /F1 13 Tf 72 690 Td ($subtitulo) Tj ET\n"
                   . "BT /F1 11 Tf 72 660 Td (Documento generado para pruebas. No es un certificado real.) Tj ET";

        $objetos = [];
        $objetos[1] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objetos[2] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        $objetos[3] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] "
                    . "/Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>";
        $objetos[4] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        $objetos[5] = "<< /Length " . strlen($contenido) . " >>\nstream\n$contenido\nendstream";

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objetos as $num => $cuerpo) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "$num 0 obj\n$cuerpo\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $total   = count($objetos) + 1; // +1 por el objeto libre 0
        $pdf .= "xref\n0 $total\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i < $total; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size $total /Root 1 0 R >>\nstartxref\n$xrefPos\n%%EOF";

        return $pdf;
    }

    /**
     * Genera un PNG de color sólido VÁLIDO sin depender de la extensión GD,
     * construyendo los chunks (IHDR/IDAT/IEND) con zlib y CRC32. Sirve como
     * imagen de portada de prueba para el proyecto.
     */
    private function generarPng(int $ancho, int $alto, int $r, int $g, int $b): string
    {
        $firma = "\x89PNG\r\n\x1a\n";

        // IHDR: 8 bits por canal, color type 2 (RGB truecolor), sin interlace.
        $ihdr = pack('N2', $ancho, $alto) . pack('C5', 8, 2, 0, 0, 0);

        // Datos crudos: cada scanline lleva un byte de filtro (0) + píxeles RGB.
        $linea = "\x00" . str_repeat(pack('C3', $r, $g, $b), $ancho);
        $idat  = gzcompress(str_repeat($linea, $alto), 9);

        return $firma
            . $this->pngChunk('IHDR', $ihdr)
            . $this->pngChunk('IDAT', $idat)
            . $this->pngChunk('IEND', '');
    }

    /**
     * Arma un chunk PNG: longitud + tipo + datos + CRC32(tipo+datos).
     */
    private function pngChunk(string $tipo, string $datos): string
    {
        return pack('N', strlen($datos))
            . $tipo
            . $datos
            . pack('N', crc32($tipo . $datos));
    }
}
