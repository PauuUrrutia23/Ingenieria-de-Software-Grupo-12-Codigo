<x-admin-layout>
    <x-slot name="header">Detalle de Consulta #{{ $consulta->id_consulta }}</x-slot>

    @if(session('success'))
        <div class="ig-badge-ok flex items-center gap-3 border px-5 py-3 mb-6 text-sm">
            <i data-lucide="check-circle-2" class="w-5 h-5 shrink-0"></i>
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6 -mt-2">
        <a href="{{ route('admin.consultas.index') }}" class="ig-link-ghost">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Volver a la bandeja
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
        <div class="md:col-span-2 space-y-6">
            <section class="ig-card">
                <header class="flex flex-wrap items-center justify-between gap-3 bg-paper-deep border-b border-line px-5 py-3">
                    <h2 class="ig-eyebrow">Mensaje del visitante</h2>
                    @php
                        $badge = match($consulta->estado) {
                            'pendiente' => ['ig-badge ig-badge-warn', 'Pendiente'],
                            'en_proceso' => ['ig-badge', 'En Proceso'],
                            default => ['ig-badge ig-badge-ok', 'Finalizada'],
                        };
                    @endphp
                    <span class="{{ $badge[0] }}">{{ $badge[1] }}</span>
                    @if($consulta->notificacion_admin_pendiente)
                        <span class="ig-badge ig-badge-warn">Notificación pendiente</span>
                    @endif
                </header>
                <div class="p-5">
                    <p class="text-sm leading-relaxed text-mute-deep whitespace-pre-wrap">{{ $consulta->mensaje }}</p>
                    <p class="ig-meta mt-5">Recibida {{ $consulta->created_at->format('d/m/Y') }} · {{ $consulta->created_at->format('H:i') }}</p>
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="ig-card">
                <header class="bg-paper-deep border-b border-line px-5 py-3">
                    <h2 class="ig-eyebrow">Datos de contacto</h2>
                </header>
                <dl class="p-5 space-y-4 text-sm">
                    <div>
                        <dt class="ig-meta mb-1">Nombre</dt>
                        <dd class="text-carbon">{{ $consulta->visitante->nombre }} {{ $consulta->visitante->apellido }}</dd>
                    </div>
                    <div class="pt-4 border-t border-line">
                        <dt class="ig-meta mb-1">Email</dt>
                        <dd>
                            <a href="mailto:{{ $consulta->visitante->email }}"
                               class="text-wood-deep hover:text-carbon underline underline-offset-2 break-all">{{ $consulta->visitante->email }}</a>
                        </dd>
                    </div>
                    <div class="pt-4 border-t border-line">
                        <dt class="ig-meta mb-1">Fecha</dt>
                        <dd class="font-mono text-carbon">{{ $consulta->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                </dl>
            </section>

            <section class="ig-card">
                <header class="bg-paper-deep border-b border-line px-5 py-3">
                    <h2 class="ig-eyebrow">Gestión comercial</h2>
                </header>
                <form action="{{ route('admin.consultas.update', $consulta) }}" method="POST" class="p-5 space-y-5">
                    @csrf @method('PUT')

                    <div>
                        <label for="estado" class="ig-label">Estado actual</label>
                        <select id="estado" name="estado" class="ig-field-box cursor-pointer">
                            <option value="pendiente" {{ $consulta->estado == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                            <option value="en_proceso" {{ $consulta->estado == 'en_proceso' ? 'selected' : '' }}>En Proceso</option>
                            <option value="finalizada" {{ $consulta->estado == 'finalizada' ? 'selected' : '' }}>Finalizada</option>
                        </select>
                    </div>

                    <div>
                        <label for="prioridad" class="ig-label">Prioridad (interna)</label>
                        <select id="prioridad" name="prioridad" class="ig-field-box cursor-pointer">
                            <option value="">Seleccione...</option>
                            <option value="baja" {{ $consulta->prioridad == 'baja' ? 'selected' : '' }}>Baja</option>
                            <option value="media" {{ $consulta->prioridad == 'media' ? 'selected' : '' }}>Media</option>
                            <option value="alta" {{ $consulta->prioridad == 'alta' ? 'selected' : '' }}>Alta</option>
                        </select>
                        <p class="ig-meta mt-2">No visible para el visitante.</p>
                    </div>

                    <div class="border-t border-line pt-4">
                        <p class="ig-meta mb-1">Responsable asignado</p>
                        <p class="text-sm break-all {{ $consulta->adminResponsable?->correo ? 'text-carbon' : 'text-mute italic' }}">
                            {{ $consulta->adminResponsable?->correo ?? 'Sin responsable' }}
                        </p>
                    </div>

                    <button type="submit" class="ig-btn ig-btn-primary w-full !py-2.5">Guardar Cambios</button>
                </form>
            </section>
        </div>
    </div>
</x-admin-layout>
