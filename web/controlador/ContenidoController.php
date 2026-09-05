<?php
namespace App\Http\Controllers;

use App\Models\Contenido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ContenidoController extends Controller
{
    public const SECCIONES = ['faq', 'opiniones', 'banner', 'fases_industriales'];

    public const NOMBRES_SECCION = [
        'faq' => 'Preguntas Frecuentes',
        'opiniones' => 'Opiniones de Clientes',
        'banner' => 'Banner de Inicio',
        'fases_industriales' => 'Fases Industriales',
    ];

    /**
     * Campos obligatorios por sección. Cada sub-caso de uso de RF43 define los suyos
     * y exige rechazar el guardado si vienen vacíos:
     *   CU 43.2 FAQ            → Pregunta (titulo) + Respuesta (cuerpo)
     *   CU 43.3 Banner         → Texto Descriptivo (cuerpo)
     *   CU 43.4 Fases          → Nombre de la Fase (titulo) + Texto (cuerpo)
     *   CU 43.5 Opiniones      → Nombre del Cliente (titulo) + Testimonio (cuerpo)
     */
    public const CAMPOS_OBLIGATORIOS = [
        'faq' => ['titulo' => 'la pregunta', 'cuerpo' => 'la respuesta'],
        'banner' => ['cuerpo' => 'el texto descriptivo'],
        'fases_industriales' => ['titulo' => 'el nombre de la fase', 'cuerpo' => 'el texto de la fase'],
        'opiniones' => ['titulo' => 'el nombre del cliente', 'cuerpo' => 'el testimonio'],
    ];

    /** Reglas de validación para una sección concreta. */
    private function reglas(string $seccion, bool $esAlta): array
    {
        $obligatorios = self::CAMPOS_OBLIGATORIOS[$seccion] ?? [];

        return [
            'titulo' => [isset($obligatorios['titulo']) ? 'required' : 'nullable', 'string', 'max:200'],
            'cuerpo' => [isset($obligatorios['cuerpo']) ? 'required' : 'nullable', 'string'],
            'enlace' => ['nullable', 'url', 'max:300'],
            'archivo' => [$esAlta ? 'nullable' : 'nullable', 'file', 'mimes:jpeg,png,webp,jpg,mp4', 'max:5120'],
            'orden' => ['nullable', 'integer'],
        ];
    }

    /** Nombres legibles por sección, para que el mensaje de error tenga sentido (RNF10). */
    private function atributos(string $seccion): array
    {
        return self::CAMPOS_OBLIGATORIOS[$seccion] ?? [];
    }

    /**
     * CU 34.5 - Accediendo al Panel de gestión (contenido multimedia).
     */
    public function index(Request $request)
    {
        $seccionActual = $request->get('seccion', 'faq');
        if (!in_array($seccionActual, self::SECCIONES)) {
            $seccionActual = 'faq';
        }

        $contenidos = Contenido::where('seccion', $seccionActual)->orderBy('orden')->get();

        return view('admin.contenido.index', [
            'contenidos' => $contenidos,
            'seccionActual' => $seccionActual,
            'secciones' => self::SECCIONES,
            'nombresSeccion' => self::NOMBRES_SECCION,
        ]);
    }

    /**
     * CU 43.2 / 43.3 / 43.4 / 43.5 - Añadiendo contenido multimedia (formulario).
     */
    public function create(Request $request)
    {
        $seccion = $request->get('seccion', 'faq');
        if (!in_array($seccion, self::SECCIONES)) {
            $seccion = 'faq';
        }

        return view('admin.contenido.create', [
            'seccion' => $seccion,
            'nombresSeccion' => self::NOMBRES_SECCION,
        ]);
    }

    /**
     * CU 43.2 / 43.3 / 43.4 / 43.5 - Guardando el nuevo contenido.
     */
    public function store(Request $request)
    {
        // La sección se valida primero: de ella dependen los campos obligatorios.
        $seccion = $request->validate([
            'seccion' => ['required', 'in:' . implode(',', self::SECCIONES)],
        ])['seccion'];

        $data = $request->validate(
            $this->reglas($seccion, true),
            [],
            $this->atributos($seccion)
        );
        $data['seccion'] = $seccion;

        $data['id_admin'] = Auth::id();
        $data['activo'] = true;
        $data['orden'] = $data['orden'] ?? 0;

        if ($request->hasFile('archivo')) {
            $data['archivo'] = $request->file('archivo')->store('contenido', 'public');
            $data['tipo_mime'] = $request->file('archivo')->getMimeType();
        }

        Contenido::create($data);

        return redirect()->route('admin.contenido.index', ['seccion' => $data['seccion']])
            ->with('success', 'Contenido agregado correctamente.');
    }

    /**
     * CU 43.6 / 43.7 / 43.8 / 43.9 - Actualizando contenido multimedia (formulario).
     */
    public function edit(Contenido $contenido)
    {
        return view('admin.contenido.edit', [
            'contenido' => $contenido,
            'nombresSeccion' => self::NOMBRES_SECCION,
        ]);
    }

    /**
     * CU 43.6 / 43.7 / 43.8 / 43.9 - Guardando la actualización.
     */
    public function update(Request $request, Contenido $contenido)
    {
        // CU 43.6-43.9: al actualizar rigen los mismos campos obligatorios de la sección.
        $data = $request->validate(
            $this->reglas($contenido->seccion, false),
            [],
            $this->atributos($contenido->seccion)
        );

        if ($request->hasFile('archivo')) {
            if ($contenido->archivo) {
                Storage::disk('public')->delete($contenido->archivo);
            }
            $data['archivo'] = $request->file('archivo')->store('contenido', 'public');
            $data['tipo_mime'] = $request->file('archivo')->getMimeType();
        }

        $contenido->update($data);

        return redirect()->route('admin.contenido.index', ['seccion' => $contenido->seccion])
            ->with('success', 'Contenido actualizado correctamente.');
    }

    /**
     * CU 44.1 - 44.5 - Eliminando contenido multimedia.
     */
    public function destroy(Contenido $contenido)
    {
        $seccion = $contenido->seccion;

        if ($contenido->archivo) {
            Storage::disk('public')->delete($contenido->archivo);
        }
        $contenido->delete();

        return redirect()->route('admin.contenido.index', ['seccion' => $seccion])
            ->with('success', 'Contenido eliminado correctamente.');
    }
}
