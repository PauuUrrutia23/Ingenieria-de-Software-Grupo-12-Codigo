<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use App\Models\Colaborador;
use App\Models\Contenido;
use App\Models\Proyecto;
use App\Models\Producto;
use App\Services\StorageAdapter;

class InstitucionalCtrl extends Controller
{
    private const SECCION_DOCUMENTACION = 'documentacion';
    private const SECCION_TERMINOS = 'terminos';
    private const SECCION_FICHA_CONECTORES = 'ficha_conectores';
    private const SECCION_UBICACION = 'ubicacion';
    private const FASES_INDUSTRIALES = [
        'Descortezado',
        'Impregnación Vacío-Presión',
        'Ensamblado',
    ];

    public function __construct(private DBRouterController $db, private StorageAdapter $storage)
    {
    }

    public function home()
    {
        try {
            $proyectosRecientes = $this->db->query(Proyecto::class)
                ->where('estado_publicacion', 'publicado')
                ->orderBy('anio_ejecucion', 'desc')->with('imagenes')->take(3)->get();
        } catch (\Throwable $e) {
            $proyectosRecientes = collect();
        }
        try {
            $certificados = $this->db->query(Certificado::class)->where('estado', 'vigente')->take(3)->get();
        } catch (\Throwable $e) {
            $certificados = collect();
        }
        try {
            $proveedores = $this->db->query(Colaborador::class)->get();
        } catch (\Throwable $e) {
            $proveedores = collect();
        }

        return view('public.index', [
            'proyectos_recientes' => $proyectosRecientes,
            'certificados' => $certificados,
            'proveedores' => $proveedores,
            // RF03 (Fase 27): el enlace de ubicación se resuelve desde BD; se prepara
            // aquí (dato de backend). El footer lo consume en la Fase 28.
            'ubicacionUrl' => $this->urlUbicacionVigente(),
            // RF14 (Fase 31): la FAQ vigente llega a la vista; su fallo no rompe el home.
            'faqs' => $this->faqsVigentes(),
            // RF15 (Fase 33): opiniones vigentes, con excepción aislada.
            'opiniones' => $this->contenidosPorSeccion('opiniones'),
            // RF16 (Fase 35): filas vigentes agrupadas por etapa, sin inventar etapas ausentes.
            'fasesIndustriales' => $this->fasesIndustriales(),
            'productos' => $this->productosPublicos(),
            'banner' => $this->bannerVigente(),
        ]);
    }

    public function producto()
    {
        // RF10 (Fase 18-19): la ficha de conectores se recupera de Contenido, no hardcodeada.
        return view('public.producto', [
            'docsUrl' => $this->urlDocumentacionVigente() ?: '#',
            'ficha' => $this->fichaConectores(),
        ]);
    }

    public function terminos()
    {
        // RF02 / CU2.1 (Fase 16): la URL vigente del documento de términos se resuelve
        // desde BD (Contenido, sección 'terminos', último activo). Se pasa a la vista
        // para que el pie de página la use; null = no hay documento configurado.
        return view('legal.terminos', [
            'terminosUrl' => $this->urlTerminosVigente(),
        ]);
    }

    public function documentacionConectores()
    {
        $url = $this->urlDocumentacionVigente();

        if (!$url) {
            return redirect()->route('public.producto')->with(
                'doc_no_disponible',
                'La documentación técnica no está disponible temporalmente. Intente nuevamente más tarde.'
            );
        }

        return redirect()->away($url);
    }

    private function urlDocumentacionVigente(): ?string
    {
        try {
            $registro = $this->db->query(Contenido::class)
                ->where('seccion', self::SECCION_DOCUMENTACION)
                ->where('activo', true)
                ->orderBy('id_contenido', 'desc')
                ->first();
        } catch (\Throwable $e) {
            return null;
        }

        $url = $registro->enlace ?? env('DOCS_CONECTORES_URL');

        return ($url && $url !== '#') ? $url : null;
    }

    /**
     * RF02 / CU2.1 — URL vigente del documento de Términos.
     * Regla determinista: Contenido de sección 'terminos', activo, el de id más alto.
     * Si la consulta BD falla, se captura y se retorna null (estado controlado, sin 500).
     */
    private function urlTerminosVigente(): ?string
    {
        try {
            $registro = $this->db->query(Contenido::class)
                ->where('seccion', self::SECCION_TERMINOS)
                ->where('activo', true)
                ->orderBy('id_contenido', 'desc')
                ->first();
        } catch (\Throwable $e) {
            return null;
        }

        $url = $registro->enlace ?? null;

        return ($url && $url !== '#') ? $url : null;
    }

    /**
     * RF03 (Fase 27) — URL de ubicación (Google Maps) vigente desde Contenido.
     * Regla determinista: sección 'ubicacion', activo, el de id más alto. BD falla -> null.
     */
    private function urlUbicacionVigente(): ?string
    {
        try {
            $registro = $this->db->query(Contenido::class)
                ->where('seccion', self::SECCION_UBICACION)
                ->where('activo', true)
                ->orderBy('id_contenido', 'desc')
                ->first();
        } catch (\Throwable $e) {
            return null;
        }

        $url = $registro->enlace ?? null;

        return ($url && $url !== '#') ? $url : null;
    }

    /**
     * Colección de Contenido vigente de una sección (activo, por 'orden'). El fallo de BD
     * se captura y devuelve una colección vacía, de modo que cada bloque de la home se
     * degrada de forma independiente sin tumbar la página. Fuente común de RF14/RF15/RF16/RF55.
     */
    private function contenidosPorSeccion(string $seccion)
    {
        try {
            return $this->db->query(Contenido::class)
                ->where('seccion', $seccion)
                ->where('activo', true)
                ->orderBy('orden')
                ->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /** Filas de cada etapa en el orden industrial vigente; una etapa puede tener varias imágenes. */
    private function fasesIndustriales()
    {
        $porNombre = $this->contenidosPorSeccion('fases_industriales')
            ->map(function (Contenido $registro) {
                $registro->setAttribute('imagen_url', $this->urlImagen($registro->archivo, $registro->tipo_mime));
                return $registro;
            })
            ->groupBy('titulo');
        $ordenadas = [];

        foreach (self::FASES_INDUSTRIALES as $nombre) {
            if ($porNombre->has($nombre)) {
                $ordenadas[$nombre] = $porNombre->get($nombre);
            }
        }

        return collect($ordenadas);
    }

    private function productosPublicos()
    {
        try {
            return $this->db->query(Producto::class)
                ->with('componentes')
                ->where('activo', true)
                ->orderBy('orden')
                ->orderBy('id_producto')
                ->get()
                ->map(function (Producto $producto) {
                    $producto->setAttribute('imagen_url', $this->urlImagen($producto->imagen, $producto->tipo_mime));
                    return $producto;
                });
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /** El banner con mayor orden gana; a igualdad de orden gana el más reciente. */
    private function bannerVigente(): ?Contenido
    {
        try {
            $banner = $this->db->query(Contenido::class)
                ->where('seccion', 'banner')
                ->where('activo', true)
                ->orderBy('orden', 'desc')
                ->orderBy('id_contenido', 'desc')
                ->first();

            if ($banner) {
                $banner->setAttribute('imagen_url', $this->urlImagen($banner->archivo, $banner->tipo_mime));
            }

            return $banner;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function urlImagen(?string $ruta, ?string $tipoMime): ?string
    {
        if (!$ruta || str_contains($ruta, '..') || str_starts_with($ruta, '/')) {
            return null;
        }
        if ($tipoMime && !in_array($tipoMime, ['image/jpeg', 'image/png'], true)) {
            return null;
        }

        try {
            if (str_starts_with($ruta, 'img/')) {
                return is_file(public_path($ruta)) ? asset($ruta) : null;
            }

            return $this->storage->existe($ruta) ? $this->storage->url($ruta) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * RF14 (Fase 31) — preguntas frecuentes vigentes desde Contenido ('faq', activo, por orden).
     * Se captura el fallo de forma independiente: si esta consulta revienta, el resto del
     * home se sigue renderizando con la lista vacía (no 500).
     */
    private function faqsVigentes()
    {
        try {
            return $this->db->query(Contenido::class)
                ->where('seccion', 'faq')
                ->where('activo', true)
                ->orderBy('orden')
                ->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /**
     * RF10 (Fase 18) — ficha de conectores metálicos recuperada de Contenido.
     *
     * Fuente única de verdad: filas de sección 'ficha_conectores', una por bloque lógico,
     * discriminadas por 'titulo'. Las listas (especificaciones y miniaturas) viajan
     * serializadas como JSON en 'cuerpo' (decisión de serialización del plan, sin
     * crear una entidad nueva). Si la BD falla o falta un bloque, se devuelve un
     * valor de respaldo y una imagen placeholder: la página nunca revienta (no 500).
     */
    private function fichaConectores(): array
    {
        $porTitulo = [];
        try {
            $registros = $this->db->query(Contenido::class)
                ->where('seccion', self::SECCION_FICHA_CONECTORES)
                ->where('activo', true)
                ->orderBy('orden')
                ->get();
            foreach ($registros as $r) {
                $porTitulo[$r->titulo] = $r;
            }
        } catch (\Throwable $e) {
            $porTitulo = [];
        }

        $lista = function (?string $json): array {
            $datos = json_decode((string) $json, true);
            return is_array($datos) ? $datos : [];
        };

        $principal = $porTitulo['imagen_principal'] ?? null;
        $srcPrincipal = ($principal && $principal->archivo) ? $principal->archivo : 'img/conector-pieza-sola.jpg';
        $altPrincipal = ($principal && $principal->cuerpo) ? $principal->cuerpo
            : 'Conector metálico galvanizado en forma de U, con alas perforadas para clavos y base atornillable, sobre fondo blanco';

        return [
            'descripcion' => $porTitulo['descripcion']->cuerpo ?? '',
            'especificaciones' => $lista($porTitulo['especificaciones']->cuerpo ?? null),
            'aplicaciones' => $lista($porTitulo['aplicaciones']->cuerpo ?? null),
            'imagen_principal' => ['src' => $srcPrincipal, 'alt' => $altPrincipal],
            'miniaturas' => $lista($porTitulo['miniaturas']->cuerpo ?? null),
        ];
    }

    public function colaboradores()
    {
        try {
            $colaboradores = $this->db->query(Colaborador::class)->orderBy('nombre_comercial')->get();
        } catch (\Throwable $e) {
            $colaboradores = collect();
        }

        return view('public.colaboradores', compact('colaboradores'));
    }
}
