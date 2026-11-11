<x-admin-layout>
    <x-slot name="header">Administración</x-slot>

    @php
        $permisos = ['editar_contenido' => 'Editar Contenido', 'exportar_consultas' => 'Exportar Consultas', 'eliminar_consultas' => 'Eliminar Consultas'];
    @endphp

    <div x-data="{ crear: @js($errors->any() && old('_modal') === 'crear'), eliminar: false, seleccionado: { id: null, correo: '' } }">
        @if(session('success'))<p class="ig-badge-ok border px-5 py-3 mb-5">{{ session('success') }}</p>@endif
        @if($errors->any())<p class="bg-paper-deep border border-line-strong px-5 py-3 mb-5 text-wood-deep">{{ $errors->first() }}</p>@endif

        <div class="flex justify-between items-center gap-4 mb-6">
            <p class="ig-lede">Cuentas del personal de administración.</p>
            <button type="button" class="ig-btn ig-btn-primary" @click="crear = true">Agregar Administrador</button>
        </div>

        <div class="ig-card overflow-x-auto">
            <table class="w-full min-w-[35rem] text-left">
                <thead class="bg-paper-deep border-b border-line"><tr>
                    <th scope="col" class="ig-meta p-4">Correo Institucional</th>
                    @foreach($permisos as $etiqueta)
                        <th scope="col" class="ig-meta p-4 text-center">{{ $etiqueta }}</th>
                    @endforeach
                    <th scope="col" class="ig-meta p-4 text-right">Acciones</th>
                </tr></thead>
                <tbody>
                @foreach($administradores as $admin)
                    <tr class="border-b border-line last:border-b-0">
                        <td class="p-4 text-sm">{{ $admin->correo }}</td>
                        @if($admin->rol === 'admin_jefe')
                            <td colspan="{{ count($permisos) }}" class="p-4 text-sm text-center text-mute">Administrador jefe: todos los permisos</td>
                        @else
                            @foreach($permisos as $permiso => $etiqueta)
                                <td class="p-4 text-center">
                                    {{-- CU 59.1: el cambio se guarda al marcar o desmarcar la casilla. --}}
                                    <form action="{{ route('admin.administradores.permisos', $admin->id_admin) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="permiso" value="{{ $permiso }}">
                                        <input type="hidden" name="activo" value="0">
                                        <input type="checkbox" name="activo" value="1" class="h-4 w-4 cursor-pointer accent-carbon"
                                               aria-label="{{ $etiqueta }}: {{ $admin->correo }}"
                                               @checked($admin->{'perm_' . $permiso}) onchange="this.form.submit()">
                                    </form>
                                </td>
                            @endforeach
                        @endif
                        <td class="p-4 text-right">
                            @if($admin->rol !== 'admin_jefe' && $admin->id_admin !== Auth::id())
                                <button type="button" class="ig-link-ghost" aria-label="Eliminar {{ $admin->correo }}"
                                        @click="seleccionado = { id: {{ $admin->id_admin }}, correo: @js($admin->correo) }; eliminar = true">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i> Eliminar Cuenta
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="border-t border-line px-6 py-4">{{ $administradores->links() }}</div>
        </div>

        <x-modal show="crear" titulo="Agregar administrador" ancho="max-w-md" :errores="old('_modal') === 'crear'">
            <form action="{{ route('admin.administradores.store') }}" method="POST" class="space-y-5">
                @csrf
                <input type="hidden" name="_modal" value="crear">
                <div><label for="admin-correo" class="ig-label">Correo institucional</label><input id="admin-correo" type="email" name="correo" value="{{ old('correo') }}" required class="ig-input" placeholder="nombre@ingeconltda.cl"></div>
                <div><label for="admin-password" class="ig-label">Contraseña</label><input id="admin-password" type="password" name="password" required minlength="8" autocomplete="new-password" class="ig-input"></div>
                <div><label for="admin-confirmacion" class="ig-label">Confirmar contraseña</label><input id="admin-confirmacion" type="password" name="password_confirmation" required autocomplete="new-password" class="ig-input"></div>
                <p class="ig-meta">Mínimo 8 caracteres, con mayúscula, minúscula, número y símbolo.</p>
                <div class="flex justify-end gap-3"><button type="button" class="ig-btn ig-btn-secondary" @click="crear = false">Cancelar</button><button type="submit" class="ig-btn ig-btn-primary">Crear cuenta</button></div>
            </form>
        </x-modal>

        <x-modal show="eliminar" titulo="Eliminar administrador" ancho="max-w-md">
            <p class="text-mute-deep mb-6">¿Confirma que desea eliminar <strong x-text="seleccionado.correo"></strong>? Sus registros de negocio se reasignarán al administrador jefe y sus sesiones se cerrarán.</p>
            <form :action="`{{ url('admin/administradores') }}/${seleccionado.id}`" method="POST" class="flex justify-end gap-3">
                @csrf @method('DELETE')
                <button type="button" class="ig-btn ig-btn-secondary" @click="eliminar = false">Cancelar</button>
                <button type="submit" class="ig-btn ig-btn-primary">Confirmar eliminación</button>
            </form>
        </x-modal>
    </div>
</x-admin-layout>
