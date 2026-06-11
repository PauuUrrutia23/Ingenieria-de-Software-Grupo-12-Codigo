@extends('layouts.admin')

@section('title', 'Colaboradores — Panel Ingecon')

@section('content')

<div x-data="adminColaboradores()" x-init="cargarColaboradores()">

    {{-- ====================================================================
         CABECERA CON BOTÓN "AGREGAR COLABORADOR"
         ==================================================================== --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Colaboradores</h1>
            <p class="text-slate-500 text-sm mt-0.5">
                Empresas y marcas asociadas a los proyectos de Ingecon.
            </p>
        </div>
        <button
            type="button"
            @click="modalAgregar = true"
            class="flex items-center gap-2 bg-slate-900 hover:bg-slate-700
                   text-white font-semibold text-sm px-5 py-2.5 rounded-xl
                   transition-colors"
        >
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
                 aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Agregar Colaborador
        </button>
    </div>

    {{-- ====================================================================
         SPINNER DE CARGA INICIAL
         ==================================================================== --}}
    <div x-show="cargando" x-cloak
         class="flex justify-center items-center py-20">
        <svg class="animate-spin w-8 h-8 text-slate-400"
             xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10"
                    stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
        </svg>
    </div>

    {{-- ====================================================================
         GRILLA DE TARJETAS DE COLABORADORES
         ==================================================================== --}}
    <div
        x-show="!cargando"
        class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-5"
        role="list"
        aria-label="Listado de colaboradores"
    >

        <template x-for="c in colaboradores" :key="c.id_colaborador">
            <article
                class="bg-white rounded-xl p-5 shadow-sm border border-slate-100
                       text-center hover:shadow-md transition-shadow"
                role="listitem"
            >
                {{-- Logotipo --}}
                <div class="w-24 h-24 mx-auto mb-3 flex items-center justify-center
                            rounded-lg bg-slate-50 overflow-hidden">
                    <img
                        x-show="c.logotipo_base64"
                        :src="c.logotipo_base64"
                        :alt="`Logotipo de ${c.nombre_comercial}`"
                        class="w-full h-full object-contain"
                        loading="lazy"
                    >
                    {{-- Placeholder sin logotipo --}}
                    <div x-show="!c.logotipo_base64"
                         class="text-slate-300"
                         aria-hidden="true">
                        <svg class="w-10 h-10" xmlns="http://www.w3.org/2000/svg"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor"
                             stroke-width="1">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182
                                     0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25
                                     0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5
                                     0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5
                                     1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z"/>
                        </svg>
                    </div>
                </div>

                {{-- Nombre comercial --}}
                <p class="text-slate-900 font-semibold text-sm leading-snug line-clamp-2"
                   x-text="c.nombre_comercial"
                   :title="c.nombre_comercial">
                </p>

            </article>
        </template>

        {{-- Estado vacío --}}
        <div
            x-show="colaboradores.length === 0 && !cargando"
            x-cloak
            class="col-span-full flex flex-col items-center justify-center
                   py-20 text-center"
        >
            <svg class="w-14 h-14 text-slate-300 mb-4" xmlns="http://www.w3.org/2000/svg"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.25">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94
                         4.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112
                         21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12
                         0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995
                         5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0
                         003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0
                         11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0
                         014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
            </svg>
            <p class="text-slate-500 font-medium mb-1">No hay colaboradores registrados</p>
            <p class="text-slate-400 text-sm mb-5">
                Agrega el primer colaborador usando el botón superior.
            </p>
            <button
                type="button"
                @click="modalAgregar = true"
                class="px-5 py-2.5 bg-slate-900 text-white text-sm font-semibold
                       rounded-xl hover:bg-slate-700 transition-colors"
            >
                Agregar primer colaborador
            </button>
        </div>

    </div>

    {{-- ====================================================================
         MODAL AGREGAR COLABORADOR (RF46 — CU 7.3)
         ==================================================================== --}}
    <div
        x-show="modalAgregar"
        x-cloak
        @keydown.escape.window="cerrarModal()"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-label="Agregar colaborador"
    >

        {{-- Overlay --}}
        <div
            @click="cerrarModal()"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-black/60 backdrop-blur-sm"
            aria-hidden="true"
        ></div>

        {{-- Panel --}}
        <div
            @click.stop
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative z-10 bg-white rounded-2xl shadow-2xl w-full max-w-md
                   max-h-[90vh] overflow-y-auto"
        >

            {{-- Cabecera --}}
            <div class="flex items-center justify-between px-6 py-4
                        border-b border-slate-100">
                <h2 class="font-bold text-slate-900 text-lg">
                    Agregar Colaborador
                </h2>
                <button
                    @click="cerrarModal()"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700
                           hover:bg-slate-100 transition-colors"
                    aria-label="Cerrar modal"
                >
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                         aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Cuerpo --}}
            <div class="px-6 py-5 space-y-5">

                {{-- Error general del servidor --}}
                <div
                    x-show="errores.general"
                    x-cloak
                    class="bg-red-50 border border-red-200 rounded-lg px-4 py-3
                           text-red-700 text-sm"
                    role="alert"
                    x-text="errores.general"
                ></div>

                {{-- Campo: Nombre comercial --}}
                <div>
                    <label
                        for="colab-nombre"
                        class="block text-sm font-semibold text-slate-700 mb-1.5"
                    >
                        Nombre comercial
                        <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input
                        id="colab-nombre"
                        type="text"
                        x-model="form.nombre_comercial"
                        @input="errores.nombre_comercial = ''"
                        :class="errores.nombre_comercial
                            ? 'border-red-400 focus:ring-red-300'
                            : 'border-slate-300 focus:ring-slate-300'"
                        class="w-full px-4 py-2.5 rounded-lg border text-sm
                               focus:outline-none focus:ring-2 transition-colors
                               placeholder:text-slate-400 disabled:bg-slate-100"
                        placeholder="Ej: Aceros del Pacífico S.A."
                        maxlength="120"
                        :disabled="enviando"
                        aria-describedby="colab-nombre-error"
                    >
                    <p
                        id="colab-nombre-error"
                        x-show="errores.nombre_comercial"
                        x-cloak
                        x-text="errores.nombre_comercial"
                        class="mt-1.5 text-xs text-red-600"
                        role="alert"
                    ></p>
                </div>

                {{-- Campo: Logotipo --}}
                <div>
                    <label
                        class="block text-sm font-semibold text-slate-700 mb-1.5"
                    >
                        Logotipo
                        <span class="text-red-500" aria-hidden="true">*</span>
                        <span class="font-normal text-slate-400">
                            (JPG, PNG, SVG, WebP · máx. 2 MB)
                        </span>
                    </label>

                    {{-- Preview del logotipo --}}
                    <div
                        x-show="logotipoPreview"
                        x-cloak
                        class="mb-3 flex flex-col items-center"
                    >
                        <div class="w-32 h-32 rounded-xl border border-slate-200
                                    bg-slate-50 overflow-hidden flex items-center
                                    justify-center">
                            <img
                                :src="logotipoPreview"
                                alt="Vista previa del logotipo"
                                class="w-full h-full object-contain"
                            >
                        </div>
                        <button
                            type="button"
                            @click="limpiarLogotipo()"
                            class="mt-2 text-xs text-red-500 hover:text-red-700
                                   transition-colors"
                            aria-label="Quitar logotipo seleccionado"
                        >
                            Quitar imagen
                        </button>
                    </div>

                    {{-- Zona de carga --}}
                    <label
                        for="colab-logotipo"
                        :class="[
                            'flex flex-col items-center justify-center gap-2 px-4 py-6',
                            'border-2 border-dashed rounded-xl cursor-pointer transition-colors',
                            errores.logotipo || errorLogotipo
                                ? 'border-red-400 bg-red-50'
                                : 'border-slate-300 hover:border-slate-400 bg-slate-50',
                            enviando ? 'opacity-60 pointer-events-none' : ''
                        ]"
                    >
                        <svg class="w-8 h-8 text-slate-400" xmlns="http://www.w3.org/2000/svg"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor"
                             stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0
                                     0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                        </svg>
                        <span class="text-sm text-slate-500">
                            <span x-show="!logotipoPreview">
                                Haz clic para seleccionar el logotipo
                            </span>
                            <span x-show="logotipoPreview" x-cloak>
                                Haz clic para cambiar el logotipo
                            </span>
                        </span>
                    </label>

                    <input
                        id="colab-logotipo"
                        type="file"
                        accept="image/jpeg,image/png,image/svg+xml,image/webp"
                        @change="previewLogotipo($event)"
                        class="sr-only"
                        :disabled="enviando"
                        aria-describedby="colab-logotipo-error"
                    >

                    {{-- Error logotipo frontend --}}
                    <p
                        x-show="errorLogotipo"
                        x-cloak
                        x-text="errorLogotipo"
                        class="mt-1.5 text-xs text-red-600"
                        role="alert"
                    ></p>

                    {{-- Error logotipo servidor --}}
                    <p
                        id="colab-logotipo-error"
                        x-show="errores.logotipo"
                        x-cloak
                        x-text="errores.logotipo"
                        class="mt-1.5 text-xs text-red-600"
                        role="alert"
                    ></p>

                </div>

            </div>

            {{-- Footer con botones --}}
            <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-3">

                <button
                    type="button"
                    @click="cerrarModal()"
                    :disabled="enviando"
                    class="px-5 py-2.5 text-sm font-medium text-slate-600
                           border border-slate-200 rounded-lg hover:bg-slate-50
                           transition-colors disabled:opacity-50"
                >
                    Cancelar
                </button>

                <button
                    type="button"
                    @click="submitAgregar()"
                    :disabled="enviando"
                    class="flex items-center gap-2 px-5 py-2.5 bg-slate-900
                           hover:bg-slate-700 disabled:bg-slate-400 text-white
                           text-sm font-semibold rounded-lg transition-colors"
                >
                    <svg
                        x-show="enviando"
                        x-cloak
                        class="animate-spin w-4 h-4"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                              d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span x-text="enviando ? 'Guardando...' : 'Guardar'"></span>
                </button>

            </div>

        </div>

    </div>

</div>{{-- /x-data --}}

@endsection

@push('scripts')
<script>
function adminColaboradores() {
    return {
        // -----------------------------------------------------------------
        // Estado
        // -----------------------------------------------------------------
        colaboradores:  [],
        cargando:       true,
        enviando:       false,

        modalAgregar:   false,
        logotipoPreview: null,
        archivoLogotipo: null,
        errorLogotipo:  '',

        form: {
            nombre_comercial: '',
        },
        errores: {},

        // -----------------------------------------------------------------
        // cargarColaboradores() — GET /admin/colaboradores
        // -----------------------------------------------------------------

        /**
         * Carga la lista de colaboradores del admin autenticado.
         * Llamado en x-init al montar el componente.
         */
        async cargarColaboradores() {
            this.cargando = true;
            try {
                const res = await fetch('/admin/colaboradores', {
                    headers: {
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                if (res.ok) {
                    this.colaboradores = await res.json();
                } else if (res.status === 401) {
                    window.location.href = '/';
                } else {
                    console.error('Error al cargar colaboradores:', res.status);
                }
            } catch (err) {
                console.error('Error de red al cargar colaboradores:', err);
            } finally {
                this.cargando = false;
            }
        },

        // -----------------------------------------------------------------
        // previewLogotipo(event) — Valida y genera preview local
        // -----------------------------------------------------------------

        /**
         * Valida el archivo de logotipo seleccionado y genera un Data URL
         * local usando FileReader para la vista previa sin subir al servidor.
         *
         * Validaciones:
         *   - Tipo MIME debe comenzar con 'image/'
         *   - Tamaño máximo: 2 MB (2 * 1024 * 1024 bytes)
         *
         * @param {Event} event  Evento change del input[type=file]
         */
        previewLogotipo(event) {
            this.errorLogotipo = '';
            this.errores.logotipo = '';

            const archivo = event.target.files[0];

            if (!archivo) return;

            if (!archivo.type.startsWith('image/')) {
                this.errorLogotipo =
                    'El archivo seleccionado no es una imagen válida (JPG, PNG, SVG, WebP).';
                event.target.value = '';
                this.archivoLogotipo = null;
                this.logotipoPreview = null;
                return;
            }

            const maxBytes = 2 * 1024 * 1024;
            if (archivo.size > maxBytes) {
                this.errorLogotipo =
                    `El logotipo no puede superar los 2 MB. El archivo pesa ${(archivo.size / 1024 / 1024).toFixed(1)} MB.`;
                event.target.value = '';
                this.archivoLogotipo = null;
                this.logotipoPreview = null;
                return;
            }

            this.archivoLogotipo = archivo;

            const reader = new FileReader();
            reader.onload = (e) => {
                this.logotipoPreview = e.target.result;
            };
            reader.onerror = () => {
                this.errorLogotipo = 'No se pudo leer el archivo. Intenta nuevamente.';
                this.archivoLogotipo = null;
            };
            reader.readAsDataURL(archivo);
        },

        // -----------------------------------------------------------------
        // limpiarLogotipo() — Quita el archivo seleccionado
        // -----------------------------------------------------------------

        limpiarLogotipo() {
            this.logotipoPreview  = null;
            this.archivoLogotipo  = null;
            this.errorLogotipo    = '';
            this.errores.logotipo = '';
            const input = document.getElementById('colab-logotipo');
            if (input) input.value = '';
        },

        // -----------------------------------------------------------------
        // submitAgregar() — Valida y envía el formulario
        // -----------------------------------------------------------------

        /**
         * Valida el formulario en el frontend y envía los datos via FormData.
         * Al éxito, agrega el nuevo colaborador al inicio de la lista reactivamente.
         */
        async submitAgregar() {
            if (this.enviando) return;

            this.errores      = {};
            this.errorLogotipo = '';
            let valido        = true;

            if (!this.form.nombre_comercial.trim()) {
                this.errores.nombre_comercial = 'El nombre comercial es obligatorio.';
                valido = false;
            } else if (this.form.nombre_comercial.trim().length > 120) {
                this.errores.nombre_comercial =
                    'El nombre comercial no puede superar los 120 caracteres.';
                valido = false;
            }

            if (!this.archivoLogotipo) {
                this.errorLogotipo = 'El logotipo es obligatorio.';
                valido = false;
            }

            if (!valido) return;

            this.enviando = true;

            const fd = new FormData();
            fd.append('nombre_comercial', this.form.nombre_comercial.trim());
            fd.append('logotipo', this.archivoLogotipo, this.archivoLogotipo.name);

            try {
                const res = await fetch('/admin/colaboradores', {
                    method: 'POST',
                    headers: {
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: fd,
                });

                const data = await res.json();

                if (data.success) {
                    this.colaboradores.unshift(data.colaborador);
                    this.enviando = false;
                    this.cerrarModal();
                    return;
                }

                if (res.status === 401) {
                    window.location.href = '/';
                    return;
                }

                if (data.errors) {
                    for (const [campo, msgs] of Object.entries(data.errors)) {
                        this.errores[campo] = Array.isArray(msgs) ? msgs[0] : msgs;
                    }
                } else {
                    this.errores.general =
                        data.message ?? 'Ocurrió un error al guardar el colaborador.';
                }

            } catch (err) {
                this.errores.general =
                    'Error de conexión. Por favor intenta nuevamente.';
                console.error('Error en submitAgregar:', err);
            } finally {
                this.enviando = false;
            }
        },

        // -----------------------------------------------------------------
        // cerrarModal() — Resetea y cierra el modal
        // -----------------------------------------------------------------

        /**
         * Cierra el modal y resetea todo el estado del formulario.
         * No cierra si hay un envío en curso.
         */
        cerrarModal() {
            if (this.enviando) return;

            this.modalAgregar    = false;
            this.logotipoPreview = null;
            this.archivoLogotipo = null;
            this.errorLogotipo   = '';
            this.errores         = {};
            this.form            = { nombre_comercial: '' };

            const input = document.getElementById('colab-logotipo');
            if (input) input.value = '';
        },
    };
}
</script>
@endpush
