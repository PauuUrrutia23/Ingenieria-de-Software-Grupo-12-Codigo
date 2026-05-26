{{--
    Modal de Login — Ingecon
    Inclusión: @include('auth.login-modal')  en el layout principal
    Apertura:  $dispatch('abrir-login') desde cualquier componente Alpine
               o window.dispatchEvent(new CustomEvent('abrir-login')) desde JS puro
--}}

<div
    x-data="loginModal()"
    x-show="abierto"
    x-cloak
    @abrir-login.window="abrir()"
    @keydown.escape.window="cerrar()"
    class="fixed inset-0 z-50 flex items-center justify-center"
    aria-modal="true"
    role="dialog"
    aria-labelledby="modal-login-titulo"
>
    {{-- Overlay oscuro --}}
    <div
        class="absolute inset-0 bg-black/60 backdrop-blur-sm"
        @click="cerrar()"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    {{-- Panel del modal --}}
    <div
        class="relative z-10 w-full max-w-md mx-4 bg-white rounded-2xl shadow-2xl overflow-hidden"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.stop
    >
        {{-- Cabecera --}}
        <div class="bg-slate-900 px-8 py-6 flex items-center justify-between">
            <div>
                <h2
                    id="modal-login-titulo"
                    class="text-white text-xl font-bold tracking-wide"
                >
                    Panel de Gestión
                </h2>
                <p class="text-slate-400 text-sm mt-0.5">Ingresa tus credenciales para continuar</p>
            </div>
            <button
                @click="cerrar()"
                class="text-slate-400 hover:text-white transition-colors p-1 rounded-lg"
                aria-label="Cerrar modal"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Formulario --}}
        <div class="px-8 py-7">

            {{-- Mensaje de error general (bloqueo, cuenta desactivada) --}}
            <div
                x-show="errorGeneral"
                x-cloak
                class="mb-5 bg-red-50 border border-red-200 rounded-lg px-4 py-3 flex items-start gap-3"
                role="alert"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-500 mt-0.5 shrink-0"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
                <p class="text-red-700 text-sm" x-text="errorGeneral"></p>
            </div>

            {{-- Campo: Correo electrónico --}}
            <div class="mb-5">
                <label
                    for="login-correo"
                    class="block text-sm font-semibold text-slate-700 mb-1.5"
                >
                    Correo electrónico
                </label>
                <input
                    id="login-correo"
                    type="email"
                    x-model="form.correo"
                    @input="limpiarError('correo')"
                    :class="errores.correo ? 'border-red-400 focus:ring-red-300' : 'border-slate-300 focus:ring-slate-300'"
                    class="w-full px-4 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 transition-colors placeholder:text-slate-400"
                    placeholder="admin@ingecon.cl"
                    autocomplete="email"
                    inputmode="email"
                    :disabled="cargando"
                >
                <p
                    x-show="errores.correo"
                    x-cloak
                    x-text="errores.correo"
                    class="mt-1.5 text-xs text-red-600"
                ></p>
            </div>

            {{-- Campo: Contraseña --}}
            <div class="mb-6">
                <label
                    for="login-password"
                    class="block text-sm font-semibold text-slate-700 mb-1.5"
                >
                    Contraseña
                </label>
                <div class="relative">
                    <input
                        id="login-password"
                        :type="mostrarPassword ? 'text' : 'password'"
                        x-model="form.password"
                        @input="limpiarError('password')"
                        @keydown.enter="enviar()"
                        :class="errores.password ? 'border-red-400 focus:ring-red-300' : 'border-slate-300 focus:ring-slate-300'"
                        class="w-full px-4 py-2.5 pr-11 rounded-lg border text-sm focus:outline-none focus:ring-2 transition-colors placeholder:text-slate-400"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        :disabled="cargando"
                    >
                    {{-- Toggle visibilidad contraseña --}}
                    <button
                        type="button"
                        @click="mostrarPassword = !mostrarPassword"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors"
                        :aria-label="mostrarPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                    >
                        <svg x-show="!mostrarPassword" xmlns="http://www.w3.org/2000/svg"
                             class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/>
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <svg x-show="mostrarPassword" x-cloak xmlns="http://www.w3.org/2000/svg"
                             class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                        </svg>
                    </button>
                </div>
                <p
                    x-show="errores.password"
                    x-cloak
                    x-text="errores.password"
                    class="mt-1.5 text-xs text-red-600"
                ></p>
            </div>

            {{-- Botón de envío --}}
            <button
                type="button"
                @click="enviar()"
                :disabled="cargando"
                class="w-full bg-slate-900 hover:bg-slate-700 disabled:bg-slate-400 text-white font-semibold text-sm py-3 rounded-lg transition-colors flex items-center justify-center gap-2"
            >
                <svg
                    x-show="cargando"
                    x-cloak
                    class="animate-spin w-4 h-4 text-white"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                >
                    <circle class="opacity-25" cx="12" cy="12" r="10"
                            stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span x-text="cargando ? 'Verificando...' : 'Ingresar'"></span>
            </button>

        </div>
    </div>
</div>

@once
@push('scripts')
<script>
    function loginModal() {
        return {
            abierto: false,
            cargando: false,
            mostrarPassword: false,
            errorGeneral: '',
            form: {
                correo: '',
                password: '',
            },
            errores: {
                correo: '',
                password: '',
            },

            abrir() {
                this.resetear();
                this.abierto = true;
                // Enfocar el campo correo tras la animación de apertura
                this.$nextTick(() => {
                    document.getElementById('login-correo')?.focus();
                });
            },

            cerrar() {
                this.abierto = false;
                this.resetear();
            },

            resetear() {
                this.form = { correo: '', password: '' };
                this.errores = { correo: '', password: '' };
                this.errorGeneral = '';
                this.cargando = false;
                this.mostrarPassword = false;
            },

            limpiarError(campo) {
                this.errores[campo] = '';
                this.errorGeneral = '';
            },

            async enviar() {
                // Evitar doble envío
                if (this.cargando) return;

                // Validación client-side básica
                let valido = true;
                this.errores = { correo: '', password: '' };
                this.errorGeneral = '';

                if (! this.form.correo) {
                    this.errores.correo = 'El correo es obligatorio.';
                    valido = false;
                } else if (! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.correo)) {
                    this.errores.correo = 'Ingresa un correo electrónico válido.';
                    valido = false;
                }

                if (! this.form.password) {
                    this.errores.password = 'La contraseña es obligatoria.';
                    valido = false;
                }

                if (! valido) return;

                this.cargando = true;

                try {
                    const response = await fetch('/login', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            correo: this.form.correo,
                            password: this.form.password,
                        }),
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Login exitoso — redirigir al panel
                        window.location.href = data.redirect;
                        return; // No resetear cargando (la página cambia)
                    }

                    // Mostrar error según campo afectado
                    if (data.campo && this.errores.hasOwnProperty(data.campo)) {
                        this.errores[data.campo] = data.message;
                    } else {
                        this.errorGeneral = data.message ?? 'Error al iniciar sesión. Intenta nuevamente.';
                    }

                } catch (err) {
                    this.errorGeneral = 'Error de conexión. Verifica tu red e intenta nuevamente.';
                    console.error('Error en login:', err);
                } finally {
                    this.cargando = false;
                    // Limpiar contraseña siempre tras intento fallido
                    this.form.password = '';
                }
            },
        };
    }
</script>
@endpush
@endonce
