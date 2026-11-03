<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductoRequest;
use App\Models\Producto;
use App\Services\Historial;
use App\Services\StorageAdapter;
use Illuminate\Support\Facades\Auth;

class ProductoAdminController extends Controller
{
    public function __construct(private DBRouterController $db, private StorageAdapter $storage)
    {
    }

    public function index()
    {
        $productos = $this->cargarListado(
            fn () => $this->db->query(Producto::class)->with('componentes')->orderBy('orden')->orderBy('id_producto', 'desc')->get(),
            collect()
        );
        return view('admin.productos.index', compact('productos'));
    }

    public function detalle(int $producto)
    {
        try {
            $registro = $this->db->query(Producto::class)->with('componentes')->find($producto);
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
            $this->db->transaccion(function () use ($datos, $ruta, $request) {
                $producto = $this->db->create(Producto::class, [
                    'nombre' => $datos['nombre'],
                    'descripcion' => $datos['descripcion'],
                    'imagen' => $ruta,
                    'tipo_mime' => $request->file('imagen')->getMimeType(),
                    'id_admin' => Auth::id(),
                ]);
                foreach (array_values($datos['componentes'] ?? []) as $i => $nombre) {
                    $producto->componentes()->create(['nombre' => trim($nombre), 'orden' => $i + 1]);
                }
                Historial::registrar('creacion', 'productos', $producto->id_producto, null, $producto->getAttributes());
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
        $rutaNueva = null;
        $rutaAnterior = null;
        try {
            if ($request->hasFile('imagen')) {
                $rutaNueva = $this->storage->guardar($request->file('imagen'), 'productos');
            }
            $actualizado = $this->db->transaccion(function () use ($datos, $producto, $rutaNueva, $request, &$rutaAnterior) {
                $registro = $this->db->query(Producto::class)->whereKey($producto)->lockForUpdate()->first();
                if (!$registro) return false;
                $rutaAnterior = $registro->imagen;
                $cambios = ['nombre' => $datos['nombre'], 'descripcion' => $datos['descripcion']];
                if ($rutaNueva) {
                    $cambios['imagen'] = $rutaNueva;
                    $cambios['tipo_mime'] = $request->file('imagen')->getMimeType();
                }
                $antes = $registro->getAttributes();
                $this->db->update($registro, $cambios);
                $registro->componentes()->delete();
                foreach (array_values($datos['componentes'] ?? []) as $i => $nombre) {
                    $registro->componentes()->create(['nombre' => trim($nombre), 'orden' => $i + 1]);
                }
                Historial::registrar('modificacion', 'productos', $registro->id_producto, $antes, $registro->getAttributes());
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
