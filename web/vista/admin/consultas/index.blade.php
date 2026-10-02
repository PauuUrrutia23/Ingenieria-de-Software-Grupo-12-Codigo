<x-admin-layout>
    <x-slot name="header">Módulo comercial</x-slot>

    <div x-data="moduloConsultas()">

        <div class="mb-6 -mt-2">
            <p class="ig-eyebrow mb-2">Bandeja de entrada</p>
            <p class="text-mute text-sm">Historial de consultas recibidas desde el Formulario de Contacto.</p>
        </div>

        @if(session('success'))
            <div class="ig-badge-ok flex items-center gap-3 border px-5 py-3 mb-6 text-sm">
                <i data-lucide="check-circle-2" class="w-5 h-5 shrink-0"></i>
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="bg-paper-deep border border-line-strong text-wood-deep text-sm px-5 py-3 mb-6">{{ $errors->first() }}</div>
        @endif

        <div class="ig-card">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[68rem] text-left">
                    <caption class="sr-only">Consultas recibidas con estado, prioridad y responsable</caption>
                    <thead class="bg-paper-deep border-b border-line">
                        <tr>
                            <th scope="col" class="ig-meta px-5 py-3 font-normal whitespace-nowrap">Fecha</th>
                            <th scope="col" class="ig-meta px-5 py-3 font-normal whitespace-nowrap">Visitante</th>
                            <th scope="col" class="ig-meta px-5 py-3 font-normal whitespace-nowrap">Estado</th>
                            <th scope="col" class="ig-meta px-5 py-3 font-normal whitespace-nowrap">Prioridad</th>
                            <th scope="col" class="ig-meta px-5 py-3 font-normal whitespace-nowrap">Responsable</th>
                            <th scope="col" class="ig-meta px-5 py-3 font-normal whitespace-nowrap">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($consultas as $c)
                        <tr class="border-b border-line last:border-b-0 hover:bg-paper transition-colors">
                            <td class="px-5 py-4 text-sm text-mute whitespace-nowrap font-mono">{{ $c->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-4">
                                <p class="text-sm text-carbon">{{ $c->visitante->nombre }} {{ $c->visitante->apellido }}</p>
                                <p class="text-xs text-mute mt-0.5">{{ $c->visitante->email }}</p>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                @php
                                    $badge = match($c->estado) {
                                        'pendiente' => ['ig-badge ig-badge-warn', 'Pendiente'],
                                        'en_proceso' => ['ig-badge', 'En Proceso'],
                                        default => ['ig-badge ig-badge-ok', 'Finalizada'],
                                    };
                                @endphp
                                <span class="{{ $badge[0] }}"><span class="w-1.5 h-1.5 bg-current opacity-60" aria-hidden="true"></span>{{ $badge[1] }}</span>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                @php
                                    $prio = match($c->prioridad) {
                                        'alta' => ['ig-badge ig-badge-warn', 'Alta'],
                                        'media' => ['ig-badge', 'Media'],
                                        'baja' => ['ig-badge ig-badge-mute', 'Baja'],
                                        default => null,
                                    };
                                @endphp
                                @if($prio)
                                    <span class="{{ $prio[0] }}">{{ $prio[1] }}</span>
                                @else
                                    <span class="ig-meta">Sin asignar</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-sm text-mute-deep whitespace-nowrap">{{ $c->adminResponsable->correo ?? 'No asignado' }}</td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                @php
                                    $detalleConsulta = [
                                        'id' => $c->id_consulta,
                                        'fecha' => $c->created_at->format('d/m/Y H:i'),
                                        'nombre' => trim($c->visitante->nombre . ' ' . $c->visitante->apellido),
                                        'email' => $c->visitante->email,
                                        'mensaje' => $c->mensaje,
                                        'estado' => $c->estado,
                                        'prioridad' => $c->prioridad,
                                        'responsable' => $c->adminResponsable->correo ?? null,
                                    ];
                                @endphp
                                <button type="button" class="ig-link-ghost"
                                        @click="abrirDetalle({{ Js::from($detalleConsulta) }})">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                    Ver detalle
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-5 py-16 text-center">
                            <div class="flex flex-col items-center">
                                <i data-lucide="inbox" class="w-12 h-12 text-line-strong mb-3"></i>
                                <p class="text-mute-deep font-medium">Aún no hay registros.</p>
                                <p class="text-mute text-sm mt-1">Las consultas del formulario de contacto aparecerán aquí.</p>
                            </div>
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4 border-t border-line">{{ $consultas->withQueryString()->links() }}</div>
        </div>

        <x-modal show="detalle" titulo="Detalle de la Consulta" ancho="max-w-2xl">
            <div class="space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <span class="ig-meta block mb-1">N°</span>
                        <span x-text="c.id" class="text-sm font-mono text-carbon"></span>
                    </div>
                    <div>
                        <span class="ig-meta block mb-1">Fecha</span>
                        <span x-text="c.fecha" class="text-sm font-mono text-carbon"></span>
                    </div>
                    <div>
                        <span class="ig-meta block mb-1">Responsable</span>
                        <span x-text="c.responsable || 'No asignado'" class="text-sm text-carbon"></span>
                    </div>
                </div>

                <div class="ig-rule"></div>

                <div>
                    <span class="ig-meta block mb-2">Datos de contacto</span>
                    <p class="text-sm text-carbon font-medium" x-text="c.nombre"></p>
                    <a :href="'mailto:' + c.email" class="text-sm text-wood-deep hover:text-carbon underline underline-offset-2" x-text="c.email"></a>
                </div>

                <div>
                    <span class="ig-meta block mb-2">Mensaje</span>
                    <p class="text-sm text-mute-deep whitespace-pre-wrap bg-paper-deep border border-line p-4 leading-relaxed" x-text="c.mensaje"></p>
                </div>

                <div class="ig-rule"></div>

                <form :action="'{{ url('admin/consultas') }}/' + c.id" method="POST" class="space-y-4">
                    @csrf @method('PUT')
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="det-estado" class="ig-label">Estado</label>
                            <select id="det-estado" name="estado" x-model="c.estado" class="ig-field-box cursor-pointer">
                                <option value="pendiente">Pendiente</option>
                                <option value="en_proceso">En Proceso</option>
                                <option value="finalizada">Finalizada</option>
                            </select>
                        </div>
                        <div>
                            <label for="det-prioridad" class="ig-label">Prioridad (interna)</label>
                            <select id="det-prioridad" name="prioridad" x-model="c.prioridad" class="ig-field-box cursor-pointer">
                                <option value="">Seleccione…</option>
                                <option value="baja">Baja</option>
                                <option value="media">Media</option>
                                <option value="alta">Alta</option>
                            </select>
                            <p class="text-xs text-mute mt-2">La prioridad no es visible para el visitante.</p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="detalle = false"
                                class="ig-btn ig-btn-secondary !py-2.5 !px-5">Cerrar</button>
                        <button type="submit"
                                class="ig-btn ig-btn-primary !py-2.5 !px-5">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </x-modal>
    </div>

    <script>
      function moduloConsultas() {
        return {
          detalle: false,
          c: { id: null, fecha: '', nombre: '', email: '', mensaje: '', estado: 'pendiente', prioridad: '', responsable: null },

          init() {
            this.$watch('detalle', () => this.$nextTick(() => window.lucide && lucide.createIcons()));
          },

          abrirDetalle(datos) {
            this.c = { ...datos, prioridad: datos.prioridad || '' };
            this.detalle = true;
          },
        };
      }
    </script>
</x-admin-layout>