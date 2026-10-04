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
            'ubicacionUrl' => $this->urlUbicacionVigente(),
            'faqs' => $this->faqsVigentes(),
            'opiniones' => $this->contenidosPorSeccion('opiniones'),
            'fasesIndustriales' => $this->fasesIndustriales(),
            'productos' => $this->productosPublicos(),
            'banner' => $this->bannerVigente(),
        ]);
    }

    public function producto()
    {
        return view('public.producto', [
            'docsUrl' => $this->urlDocumentacionVigente() ?: '#',
            'ficha' => $this->fichaConectores(),
        ]);
    }

    public function terminos()
    {
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

    // Si la consulta falla devuelve una colección vacía: una sección caída no rompe el inicio.
    private function contenidosPorSeccion(string $seccion)
    {
        try {
            return $this->db->query(Contenido::class)
                ->where('seccion', $seccion)
                ->where('activo', true)
                ->orderBy('orden')
                ->get()
                ->map(function (Contenido $registro) {
                    $registro->setAttribute('imagen_url', $this->urlImagen($registro->archivo, $registro->tipo_mime));
                    $registro->setAttribute('video_url', $this->urlVideo($registro->archivo, $registro->tipo_mime));
                    return $registro;
                });
        } catch (\Throwable $e) {
            return collect();
        }
    }

    private function fasesIndustriales()
    {
        $porNombre = $this->contenidosPorSeccion('fases_industriales')->groupBy('titulo');
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

    // Gana el banner de mayor orden; si empatan, el más reciente.
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
                $banner->setAttribute('video_url', $this->urlVideo($banner->archivo, $banner->tipo_mime));
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
        if ($tipoMime && !in_array($tipoMime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
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

    private function urlVideo(?string $ruta, ?string $tipoMime): ?string
    {
        if (!$ruta || $tipoMime !== 'video/mp4' || str_contains($ruta, '..') || str_starts_with($ruta, '/')) {
            return null;
        }

        try {
            return $this->storage->existe($ruta) ? $this->storage->url($ruta) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function faqsVigentes()
    {
        return $this->contenidosPorSeccion('faq');
    }

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
