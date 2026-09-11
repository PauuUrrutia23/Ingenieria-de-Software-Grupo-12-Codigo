<x-admin-layout>
    <x-slot name="header">Módulo comercial</x-slot>

    <div x-data="moduloConsultas()">

        <p class="text-slate-500 text-sm -mt-2 mb-6">Historial de consultas recibidas desde el Formulario de Contacto.</p>

        @if(session('success'))
            <div class="mb-6 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium px-5 py-3 rounded-xl">
                <i data-lucide="check-circle-2" class="w-5 h-5 shrink-0"></i>
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm px-5 py-3 rounded-xl">{{ $errors->first() }}</div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Visitante</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Prioridad</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Responsable</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($consultas as $c)
                    <tr class="hover:bg-slate-50/60 transition-colors">
                        <td class="px-6 py-4 text-sm text-slate-500 whitespace-nowrap">{{ $c->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-4">
                            <p class="text-sm font-semibold text-slate-900">{{ $c->visitante->nombre }} {{ $c->visitante->apellido }}</p>
                            <p class="text-xs text-slate-400">{{ $c->visitante->email }}</p>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $badge = match($c->estado) {
                                    'pendiente' => ['bg-yellow-100 text-yellow-700', 'Pendiente'],
                                    'en_proceso' => ['bg-blue-100 text-blue-700', 'En Proceso'],
                                    default => ['bg-green-100 text-green-700', 'Finalizada'],
                                };
                            @endphp
                            <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full {{ $badge[0] }}">{{ $badge[1] }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $prio = match($c->prioridad) {
                                    'alta' => ['bg-red-100 text-red-700', 'Alta'],
                                    'media' => ['bg-amber-100 text-amber-700', 'Media'],
                                    'baja' => ['bg-slate-100 text-slate-600', 'Baja'],
                                    default => null,
                                };
                            @endphp
                            @if($prio)
                                <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full {{ $prio[0] }}">{{ $prio[1] }}</span>
                            @else
                                <span class="text-xs text-slate-400">Sin asignar</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500">{{ $c->adminResponsable->correo ?? 'No asignado' }}</td>
                        <td class="px-6 py-4">
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
                            <button type="button" class="flex items-center gap-1.5 text-sm font-semibold text-slate-700 hover:text-slate-900"
                                    @click="abrirDetalle({{ Js::from($detalleConsulta) }})">
                                <i data-lucide="eye" class="w-4 h-4 text-slate-400"></i>
                                Ver detalle
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center">
                            <i data-lucide="inbox" class="w-12 h-12 text-slate-300 mb-3"></i>
                            <p class="text-slate-500 font-medium">Aún no hay registros.</p>
                        </div>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-6 py-4 border-t border-slate-100">{{ $consultas->withQueryString()->links() }}</div>
        </div>

        <x-modal show="detalle" titulo="Detalle de la Consulta" ancho="max-w-2xl">
            <div class="space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">N°</span>
                        <span x-text="c.id" class="text-slate-800"></span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Fecha</span>
                        <span x-text="c.fecha" class="text-slate-800"></span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Responsable</span>
                        <span x-text="c.responsable || 'No asignado'" class="text-slate-800"></span>
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-5">
                    <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Datos de contacto</span>
                    <p class="font-semibold text-slate-900" x-text="c.nombre"></p>
                    <a :href="'mailto:' + c.email" class="text-sm text-slate-600 hover:text-slate-900 underline" x-text="c.email"></a>
                </div>

                <div class="border-t border-slate-100 pt-5">
                    <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Mensaje</span>
                    <p class="text-slate-700 whitespace-pre-wrap bg-slate-50 border border-slate-100 rounded-xl p-4 text-sm" x-text="c.mensaje"></p>
                </div>

                <form :action="'{{ url('admin/consultas') }}/' + c.id" method="POST"
                      class="border-t border-slate-100 pt-5 space-y-4">
                    @csrf @method('PUT')
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="det-estado" class="block text-sm font-semibold text-slate-700 mb-1.5">Estado</label>
                            <select id="det-estado" name="estado" x-model="c.estado"
                                    class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slate-300 cursor-pointer">
                                <option value="pendiente">Pendiente</option>
                                <option value="en_proceso">En Proceso</option>
                                <option value="finalizada">Finalizada</option>
                            </select>
                        </div>
                        <div>
                            <label for="det-prioridad" class="block text-sm font-semibold text-slate-700 mb-1.5">Prioridad (interna)</label>
                            <select id="det-prioridad" name="prioridad" x-model="c.prioridad"
                                    class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slate-300 cursor-pointer">
                                <option value="">Seleccione…</option>
                                <option value="baja">Baja</option>
                                <option value="media">Media</option>
                                <option value="alta">Alta</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="detalle = false"
                                class="px-5 py-2.5 text-sm font-medium text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">Cerrar</button>
                        <button type="submit"
                                class="px-5 py-2.5 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold rounded-lg transition-colors">Guardar cambios</button>
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
