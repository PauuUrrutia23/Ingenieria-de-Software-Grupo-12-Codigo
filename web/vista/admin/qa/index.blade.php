<x-admin-layout>
    <x-slot name="header">Bitácora Automática de Pruebas</x-slot>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6">
        <p class="text-sm text-slate-500 mb-4">
            Corre la suite real de pruebas automatizadas del sistema (PHPUnit) contra una base de
            datos aislada — tu base de datos real de desarrollo no se toca. Vas a ver cada prueba
            aparecer acá abajo a medida que se ejecuta, en vivo.
        </p>
        <button id="btnRun" class="flex items-center gap-2 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-colors disabled:opacity-50">
            <i data-lucide="play" class="w-4 h-4"></i>
            <span id="btnLabel">Ejecutar pruebas automáticas</span>
        </button>
        <span id="estado" class="ml-4 text-sm font-semibold text-slate-500"></span>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="grid grid-cols-3 divide-x divide-slate-100 border-b border-slate-100 text-center">
            <div class="p-4">
                <div id="numPass" class="text-3xl font-bold text-green-600">0</div>
                <div class="text-xs uppercase text-slate-400 font-semibold tracking-wide">Aprobadas</div>
            </div>
            <div class="p-4">
                <div id="numFail" class="text-3xl font-bold text-red-600">0</div>
                <div class="text-xs uppercase text-slate-400 font-semibold tracking-wide">Fallidas</div>
            </div>
            <div class="p-4">
                <div id="numTotal" class="text-3xl font-bold text-slate-900">0</div>
                <div class="text-xs uppercase text-slate-400 font-semibold tracking-wide">Total corridas</div>
            </div>
        </div>
        <div id="log" class="p-6 font-mono text-[13px] leading-relaxed max-h-[60vh] overflow-y-auto bg-slate-900 text-slate-300">
            <p class="text-slate-500">Presioná "Ejecutar pruebas automáticas" para empezar.</p>
        </div>
    </div>

    <script>
    (function() {
        const btn = document.getElementById('btnRun');
        const btnLabel = document.getElementById('btnLabel');
        const log = document.getElementById('log');
        const estado = document.getElementById('estado');
        const numPass = document.getElementById('numPass');
        const numFail = document.getElementById('numFail');
        const numTotal = document.getElementById('numTotal');

        let pass = 0, fail = 0;

        function appendLine(text, cls) {
            const p = document.createElement('div');
            p.textContent = text;
            if (cls) p.className = cls;
            log.appendChild(p);
            log.scrollTop = log.scrollHeight;
        }

        function classify(line) {
            const trimmed = line.trim();
            if (trimmed.startsWith('PASS')) return 'text-green-400 font-bold mt-3';
            if (trimmed.startsWith('FAIL')) return 'text-red-400 font-bold mt-3';
            if (trimmed.includes('✓')) { pass++; numPass.textContent = pass; numTotal.textContent = pass + fail; return 'text-green-300 pl-4'; }
            if (trimmed.includes('⨯') || trimmed.includes('✘')) { fail++; numFail.textContent = fail; numTotal.textContent = pass + fail; return 'text-red-300 pl-4'; }
            if (trimmed.startsWith('Tests:')) return 'text-white font-bold mt-4 border-t border-gray-600 pt-3';
            if (trimmed.startsWith('FAILED') || trimmed.startsWith('Failed asserting')) return 'text-red-300';
            return 'text-gray-400';
        }

        btn.addEventListener('click', function() {
            btn.disabled = true;
            btnLabel.textContent = 'Ejecutando...';
            estado.textContent = 'Corriendo la suite de tests en el servidor...';
            log.innerHTML = '';
            pass = 0; fail = 0;
            numPass.textContent = '0';
            numFail.textContent = '0';
            numTotal.textContent = '0';

            const source = new EventSource('{{ route('admin.qa.stream') }}');

            source.onmessage = function(event) {
                const data = JSON.parse(event.data);
                if (data.type === 'start') {
                    appendLine('→ Iniciando "php artisan test"...', 'text-gray-400 italic');
                } else if (data.type === 'line') {
                    appendLine(data.text, classify(data.text));
                } else if (data.type === 'done') {
                    source.close();
                    btn.disabled = false;
                    btnLabel.textContent = 'Ejecutar pruebas automáticas';
                    estado.textContent = data.success ? '✅ Suite completa, todo en verde.' : '⚠️ Terminó con fallas — revisá el detalle arriba.';
                    estado.className = 'ml-4 text-sm font-semibold ' + (data.success ? 'text-green-600' : 'text-red-600');
                }
            };

            source.onerror = function() {
                source.close();
                btn.disabled = false;
                btn.textContent = '▶ Ejecutar pruebas automáticas';
                estado.textContent = '❌ Se cortó la conexión con el servidor.';
                estado.className = 'ml-4 text-sm font-semibold text-red-600';
            };
        });
    })();
    </script>
</x-admin-layout>
