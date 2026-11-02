<x-admin-layout>
    <x-slot name="header">Historial de Cambios</x-slot>

    <div x-data="moduloHistorial()">
        <div class="mb-6 -mt-2">
            <p class="ig-eyebrow mb-2">Auditoría</p>
            <p class="text-mute text-sm">Acciones realizadas por el Personal de Administración, desde la más reciente.</p>
        </div>

        @php
            $criterios = array_filter(['tipo' => $tipo !== 'todos' ? $tipo : null, 'q' => $q, 'responsable' => $responsable,
                'modulo' => $modulo, 'desde' => $desde, 'hasta' => $hasta]);
            $accesos = ['todos' => 'Todos', 'exportaciones' => 'Exportaciones', 'eliminaciones' => 'Eliminaciones',
                'modificaciones' => 'Modificaciones', 'creaciones' => 'Creaciones', 'visualizaciones' => 'Vista de detalles'];
            $hayFiltros = $criterios !== [];
        @endphp

        {{-- CU 15.2: accesos rápidos por tipo de acción; se aplican de inmediato. --}}
        <nav class="flex flex-wrap gap-2 mb-4" aria-label="Filtrar por tipo de acción">
            @foreach($accesos as $clave => $etiqueta)
                <a href="{{ route('admin.historial.index', array_merge($criterios, ['tipo' => $clave === 'todos' ? null : $clave])) }}"
                   class="ig-btn {{ $tipo === $clave ? 'ig-btn-primary' : 'ig-btn-secondary' }} !py-1.5 !px-3"
                   @if($tipo === $clave) aria-current="true" @endif>{{ $etiqueta }}</a>
            @endforeach
        </nav>

        <form action="{{ route('admin.historial.index') }}" method="GET" class="flex flex-wrap gap-3 mb-4 items-end"
              x-data="{ desde: @js(old('desde', $desde)), hasta: @js(old('hasta', $hasta)) }">
            @if($tipo !== 'todos')<input type="hidden" name="tipo" value="{{ $tipo }}">@endif
            <div>
                <label for="historial-desde" class="ig-label">Desde</label>
                <input id="historial-desde" type="date" name="desde" x-model="desde" :max="hasta || null"
                       @change="$el.form.requestSubmit()" class="ig-input @error('desde') border-[#b4403f] @enderror">
            </div>
            <div>
                <label for="historial-hasta" class="ig-label">Hasta</label>
                <input id="historial-hasta" type="date" name="hasta" x-model="hasta" :min="desde || null"
                       @change="$el.form.requestSubmit()" class="ig-input @error('desde') border-[#b4403f] @enderror">
            </div>
            <div class="min-w-[14rem] flex-1">
                <label for="historial-q" class="ig-label">Buscar por administrador, módulo o acción</label>
                <input id="historial-q" name="q" value="{{ $q }}" maxlength="150" class="ig-input">
            </div>
            <div>
                <label for="historial-responsable" class="ig-label">Responsable</label>
                <select id="historial-responsable" name="responsable" class="ig-input">
                    <option value="">Todos</option>
                    @foreach($responsables as $correo)
                        <option value="{{ $correo }}" @selected($responsable === $correo)>{{ $correo }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="historial-modulo" class="ig-label">Módulo</label>
                <select id="historial-modulo" name="modulo" class="ig-input">
                    <option value="">Todos</option>
                    @foreach(\App\Models\HistorialCambio::MODULOS as $clave => $etiqueta)
                        <option value="{{ $clave }}" @selected($modulo === $clave)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="ig-btn ig-btn-primary">Filtrar</button>
            @if($hayFiltros)
                <a href="{{ route('admin.historial.index') }}" class="ig-btn ig-btn-secondary">Limpiar</a>
            @endif
        </form>
        @error('desde')<p class="text-sm text-[#8c2f2f] mb-4" role="alert">{{ $message }}</p>@enderror

        <p class="ig-meta mb-4">{{ $registros->total() === 1 ? '1 registro encontrado' : $registros->total() . ' registros encontrados' }}</p>
        <p x-show="aviso" x-cloak x-text="aviso" role="status" class="text-sm text-wood-deep mb-4"></p>

        <div class="ig-card">
            @if($registros->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[60rem] text-left">
                        <caption class="sr-only">Registros del Historial de Cambios, desde el más reciente</caption>
                        <thead class="bg-paper-deep border-b border-line">
                            <tr>
                                <th scope="col" class="ig-meta px-5 py-3 font-normal">Fecha y hora</th>
                                <th scope="col" class="ig-meta px-5 py-3 font-normal">Responsable</th>
                                <th scope="col" class="ig-meta px-5 py-3 font-normal">Acción</th>
                                <th scope="col" class="ig-meta px-5 py-3 font-normal">Módulo</th>
                                <th scope="col" class="ig-meta px-5 py-3 font-normal">Descripción</th>
                                <th scope="col" class="ig-meta px-5 py-3 font-normal">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($registros as $r)
                                <tr data-registro="{{ $r->id_historial }}" class="border-b border-line last:border-b-0">
                                    <td class="px-5 py-4 text-sm text-mute whitespace-nowrap font-mono">{{ $r->fecha_hora->format('d/m/Y H:i') }}</td>
                                    <td class="px-5 py-4 text-sm text-carbon">
                                        {{ $r->correo_responsable }}
                                        @if($r->id_admin === null)<span class="block ig-meta mt-0.5">Cuenta eliminada</span>@endif
                                    </td>
                                    <td class="px-5 py-4 text-sm">{{ $r->accion_etiqueta }}</td>
                                    <td class="px-5 py-4 text-sm">{{ $r->modulo_etiqueta }}</td>
                                    <td class="px-5 py-4 text-sm text-mute-deep">{{ $r->descripcion }}</td>
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        <button type="button" class="ig-link-ghost" @click="abrirDetalle({{ $r->id_historial }})">
                                            <i data-lucide="eye" class="w-4 h-4"></i> Ver detalles
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-4 border-t border-line">{{ $registros->links() }}</div>
            @else
                <div class="flex flex-col items-center px-5 py-16 text-center">
                    <i data-lucide="history" class="w-12 h-12 text-line-strong mb-3"></i>
                    <p class="text-mute-deep font-medium">
                        @if($errorCarga) El Historial de Cambios no puede mostrarse temporalmente.
                        @elseif($hayFiltros) Ningún registro cumple los criterios de filtrado.
                        @else Aún no hay registros en el Historial de Cambios.
                        @endif
                    </p>
                </div>
            @endif
        </div>

        {{-- CU 15.3: detalle del registro; se cierra con el botón o con Escape. --}}
        <x-modal show="detalle" titulo="Detalle del registro" ancho="max-w-2xl">
            <div class="space-y-5">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div><dt class="ig-meta mb-1">Fecha y hora</dt><dd class="text-sm font-mono text-carbon" x-text="d.fecha"></dd></div>
                    <div>
                        <dt class="ig-meta mb-1">Responsable</dt>
                        <dd class="text-sm text-carbon"><span x-text="d.responsable"></span>
                            <span x-show="d.cuenta_eliminada" class="block ig-meta">Cuenta eliminada</span></dd>
                    </div>
                    <div><dt class="ig-meta mb-1">Acción</dt><dd class="text-sm text-carbon" x-text="d.accion"></dd></div>
                    <div><dt class="ig-meta mb-1">Módulo</dt><dd class="text-sm text-carbon" x-text="d.modulo"></dd></div>
                    <div><dt class="ig-meta mb-1">Registro asociado</dt><dd class="text-sm font-mono text-carbon" x-text="d.id_registro ? 'N° ' + d.id_registro : 'No aplica'"></dd></div>
                </dl>

                <div class="ig-rule"></div>

                <p x-show="d.cambios.length === 0" class="text-sm text-mute">Esta acción no registra valores anteriores ni nuevos.</p>
                <table x-show="d.cambios.length > 0" class="w-full text-left text-sm">
                    <thead class="border-b border-line">
                        <tr>
                            <th scope="col" class="ig-meta py-2 pr-4 font-normal">Campo</th>
                            <th scope="col" class="ig-meta py-2 pr-4 font-normal" x-show="d.cambios.some(c => c.anterior !== null)">Valor anterior</th>
                            <th scope="col" class="ig-meta py-2 font-normal" x-show="d.cambios.some(c => c.nuevo !== null)">Valor nuevo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="c in d.cambios" :key="c.campo">
                            <tr class="border-b border-line last:border-b-0">
                                <td class="py-2 pr-4 font-mono text-xs text-mute-deep" x-text="c.campo"></td>
                                <td class="py-2 pr-4 text-carbon" x-show="d.cambios.some(x => x.anterior !== null)" x-text="c.anterior ?? '—'"></td>
                                <td class="py-2 text-carbon" x-show="d.cambios.some(x => x.nuevo !== null)" x-text="c.nuevo ?? '—'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </x-modal>
    </div>

    <script>
      function moduloHistorial() {
        return {
          detalle: false,
          aviso: '',
          d: { fecha: '', responsable: '', cuenta_eliminada: false, accion: '', modulo: '', id_registro: null, cambios: [] },

          async abrirDetalle(id) {
            this.aviso = 'Cargando detalle del registro…';
            try {
              const respuesta = await fetch(`/admin/historial/${id}/detalle`, { headers: { 'Accept': 'application/json' } });
              if (respuesta.status === 401 || respuesta.status === 419) {
                window.location.href = '{{ route('login') }}';
                return;
              }
              if (!respuesta.ok) throw new Error('detalle no disponible');
              this.d = await respuesta.json();
              this.detalle = true;
              this.aviso = '';
            } catch (error) {
              this.aviso = 'El detalle no puede mostrarse temporalmente.';
            }
          },
        };
      }
    </script>
</x-admin-layout>
