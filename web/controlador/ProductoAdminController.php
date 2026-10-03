<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductoRequest;
use App\Models\Producto;
use App\Services\StorageAdapter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductoAdminController extends Controller
{
    public function __construct(private StorageAdapter $storage)
    {
    }

    public function index()
    {
        $productos = Producto::query()->with('componentes')->orderBy('orden')->orderBy('id_producto', 'desc')->get();
        return view('admin.productos.index', compact('productos'));
    }

    public function detalle(int $producto)
    {
        try {
            $registro = Producto::query()->with('componentes')->find($producto);
            if ($registro && $registro->imagen) {
                $imagen = $this->storage->existe($registro->imagen)
                    ? $this->storage->url($registro->imagen) : null;
            }
        } catch (\Throwable $e) {
            return response()->json(['message' => 'El producto no está disponible temporalmente.'], 503);
        }
        if (!$registro) {
            return response()->json(['message' => 'Este producto ya no está disponible.'], 410);
        }
        return response()->json([
            'id' => $registro->id_producto,
            'nombre' => $registro->nombre,
            'descripcion' => $registro->descripcion,
            'componentes' => $registro->componentes->pluck('nombre'),
            'imagen' => $imagen ?? null,
        ]);
    }

    public function store(ProductoRequest $request)
    {
        $datos = $request->validated();
        $ruta = null;
        try {
            $ruta = $this->storage->guardar($request->file('imagen'), 'productos');
            DB::transaction(function () use ($datos, $ruta, $request) {
                $producto = Producto::create([
                    'nombre' => $datos['nombre'],
                    'descripcion' => $datos['descripcion'],
                    'imagen' => $ruta,
                    'tipo_mime' => $request->file('imagen')->getMimeType(),
                    'id_admin' => Auth::id(),
                ]);
                foreach (array_values($datos['componentes'] ?? []) as $i => $nombre) {
                    $producto->componentes()->create(['nombre' => trim($nombre), 'orden' => $i + 1]);
                }
            });
        } catch (\Throwable $e) {
            if ($ruta) $this->storage->borrar($ruta);
            report($e);
            return back()->withInput()->withErrors(['producto' => 'No se pudo crear el producto.']);
        }
        return redirect()->route('admin.productos.index')->with('success', 'Producto creado.');
    }

    public function update(ProductoRequest $request, int $producto)
    {
        $datos = $request->validated();
        $request->merge(['producto_id' => $producto]);
        $rutaNueva = null;
        $rutaAnterior = null;
        try {
            if ($request->hasFile('imagen')) {
                $rutaNueva = $this->storage->guardar($request->file('imagen'), 'productos');
            }
            $actualizado = DB::transaction(function () use ($datos, $producto, $rutaNueva, $request, &$rutaAnterior) {
                $registro = Producto::query()->whereKey($producto)->lockForUpdate()->first();
                if (!$registro) return false;
                $rutaAnterior = $registro->imagen;
                $cambios = ['nombre' => $datos['nombre'], 'descripcion' => $datos['descripcion']];
                if ($rutaNueva) {
                    $cambios['imagen'] = $rutaNueva;
                    $cambios['tipo_mime'] = $request->file('imagen')->getMimeType();
                }
                $registro->update($cambios);
                $registro->componentes()->delete();
                foreach (array_values($datos['componentes'] ?? []) as $i => $nombre) {
                    $registro->componentes()->create(['nombre' => trim($nombre), 'orden' => $i + 1]);
                }
                return true;
            });
            if (!$actualizado) {
                if ($rutaNueva) $this->storage->borrar($rutaNueva);
                return redirect()->route('admin.productos.index')->withErrors(['producto' => 'El producto ya no está disponible.']);
            }
        } catch (\Throwable $e) {
            if ($rutaNueva) $this->storage->borrar($rutaNueva);
            report($e);
            return back()->withInput()->withErrors(['producto' => 'No se pudo actualizar el producto.']);
        }
        if ($rutaNueva && $rutaAnterior) {
            try {
                $this->storage->borrar($rutaAnterior);
            } catch (\Throwable $e) {
                report($e);
            }
        }
        return redirect()->route('admin.productos.index')->with('success', 'Producto actualizado.');
    }
}
