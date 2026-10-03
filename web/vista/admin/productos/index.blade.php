<x-admin-layout>
    <x-slot name="header">Panel de Gestión · Productos</x-slot>

    <div x-data="gestionProductos()">
        <nav class="flex gap-6 border-b border-line mb-6" aria-label="Secciones del panel">
            <a href="{{ route('admin.contenido.index') }}" class="pb-3 text-sm text-mute">Contenido del sitio</a>
            <span class="pb-3 border-b-2 border-wood text-sm font-medium">Productos</span>
        </nav>

        @if(session('success'))<p class="ig-badge-ok border px-5 py-3 mb-5">{{ session('success') }}</p>@endif
        @if($errors->any())<p class="bg-paper-deep border border-line-strong px-5 py-3 mb-5 text-wood-deep">{{ $errors->first() }}</p>@endif
        <p x-show="aviso" x-cloak x-text="aviso" role="status" class="text-sm text-wood-deep mb-5"></p>

        <div class="flex justify-between items-center gap-4 mb-6">
            <p class="ig-meta">{{ $productos->count() }} productos</p>
            <button type="button" class="ig-btn ig-btn-primary" @click="crear = true">Agregar producto</button>
        </div>

        <div class="ig-card divide-y divide-line">
            @forelse($productos as $producto)
                <div class="p-5 flex flex-wrap justify-between gap-4 items-center">
                    <div>
                        <h2 class="font-display text-xl">{{ $producto->nombre }}</h2>
                        <p class="text-sm text-mute">{{ $producto->componentes->count() }} componentes</p>
                    </div>
                    <button type="button" class="ig-btn ig-btn-secondary" @click="abrirEditar({{ $producto->id_producto }})">Editar</button>
                </div>
            @empty
                <p class="p-8 text-mute">Aún no hay productos.</p>
            @endforelse
        </div>

        <x-modal show="crear" titulo="Agregar producto" ancho="max-w-2xl" :errores="old('formulario_producto') === 'crear'">
            <form action="{{ route('admin.productos.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf
                <input type="hidden" name="formulario_producto" value="crear">
                <div><label class="ig-label" for="nuevo-nombre">Nombre</label><input id="nuevo-nombre" name="nombre" value="{{ old('formulario_producto') === 'crear' ? old('nombre') : '' }}" required maxlength="150" class="ig-input"></div>
                <div><label class="ig-label" for="nueva-descripcion">Descripción</label><textarea id="nueva-descripcion" name="descripcion" required class="ig-input" rows="4">{{ old('formulario_producto') === 'crear' ? old('descripcion') : '' }}</textarea></div>
                <div><label class="ig-label" for="nueva-imagen">Imagen JPG o PNG, máximo 2 MB</label><input id="nueva-imagen" name="imagen" type="file" accept=".jpg,.jpeg,.png" required class="ig-input"></div>
                <div>
                    <label class="ig-label">Componentes</label>
                    <template x-for="(nombre, i) in componentesCrear" :key="i">
                        <div class="flex gap-2 mb-2"><input name="componentes[]" x-model="componentesCrear[i]" maxlength="150" required class="ig-input"><button type="button" class="ig-btn ig-btn-secondary" @click="componentesCrear.splice(i, 1)" aria-label="Quitar componente">Quitar</button></div>
                    </template>
                    <button type="button" class="ig-link-ghost" @click="componentesCrear.push('')">Agregar componente</button>
                </div>
                <div class="flex justify-end gap-3"><button type="button" class="ig-btn ig-btn-secondary" @click="crear = false">Cancelar</button><button type="submit" class="ig-btn ig-btn-primary">Guardar producto</button></div>
            </form>
        </x-modal>

        <x-modal show="editar" titulo="Editar producto" ancho="max-w-2xl" :errores="old('formulario_producto') === 'editar'">
            <form :action="`{{ url('admin/productos') }}/${producto.id}`" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf @method('PUT')
                <input type="hidden" name="formulario_producto" value="editar">
                <div><label class="ig-label" for="editar-nombre">Nombre</label><input id="editar-nombre" name="nombre" x-model="producto.nombre" required maxlength="150" class="ig-input"></div>
                <div><label class="ig-label" for="editar-descripcion">Descripción</label><textarea id="editar-descripcion" name="descripcion" x-model="producto.descripcion" required class="ig-input" rows="4"></textarea></div>
                <div><label class="ig-label" for="editar-imagen">Reemplazar imagen, si corresponde</label><input id="editar-imagen" name="imagen" type="file" accept=".jpg,.jpeg,.png" class="ig-input"></div>
                <div>
                    <label class="ig-label">Componentes</label>
                    <template x-for="(nombre, i) in producto.componentes" :key="i">
                        <div class="flex gap-2 mb-2"><input name="componentes[]" x-model="producto.componentes[i]" maxlength="150" required class="ig-input"><button type="button" class="ig-btn ig-btn-secondary" @click="producto.componentes.splice(i, 1)" aria-label="Quitar componente">Quitar</button></div>
                    </template>
                    <button type="button" class="ig-link-ghost" @click="producto.componentes.push('')">Agregar componente</button>
                </div>
                <div class="flex justify-end gap-3"><button type="button" class="ig-btn ig-btn-secondary" @click="editar = false">Cancelar</button><button type="submit" class="ig-btn ig-btn-primary">Guardar cambios</button></div>
            </form>
        </x-modal>
    </div>

    <script>
      function gestionProductos() {
        return {
          crear: @js(old('formulario_producto') === 'crear'),
          editar: @js(old('formulario_producto') === 'editar'),
          aviso: '',
          componentesCrear: @js(old('formulario_producto') === 'crear' ? old('componentes', []) : []),
          producto: { id: @js(old('producto_id')), nombre: @js(old('formulario_producto') === 'editar' ? old('nombre', '') : ''), descripcion: @js(old('formulario_producto') === 'editar' ? old('descripcion', '') : ''), componentes: @js(old('formulario_producto') === 'editar' ? old('componentes', []) : []) },
          async abrirEditar(id) {
            this.aviso = 'Cargando producto…';
            try {
              const respuesta = await fetch(`/admin/productos/${id}/detalle`, { headers: { 'Accept': 'application/json' } });
              if (respuesta.status === 410) { this.aviso = 'El producto ya no está disponible. Actualice el listado.'; return; }
              if (!respuesta.ok) throw new Error('detalle no disponible');
              this.producto = await respuesta.json();
              this.editar = true;
              this.aviso = '';
            } catch (error) {
              this.aviso = 'No se pudo cargar el producto.';
            }
          },
        };
      }
    </script>
</x-admin-layout>
