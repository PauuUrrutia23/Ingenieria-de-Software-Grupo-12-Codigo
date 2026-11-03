import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import test from 'node:test';

// CU 18.1, Excepción 1: "Aplicar Filtros" sin ningún criterio no recarga la galería y pide elegir una opción.
const vista = readFileSync(new URL('../../../vista/public/proyectos.blade.php', import.meta.url), 'utf8');
const inicio = vista.indexOf('function galeriaProyectos() {');
const fin = vista.indexOf('</script>', inicio);
assert.ok(inicio >= 0 && fin > inicio, 'La vista debe definir galeriaProyectos');
const codigo = vista.slice(inicio, fin).replace('@js((bool) $hayFiltros)', 'false');

function galeriaCon(campos) {
    const llamadas = [];
    const formulario = { campos };
    const contexto = {
        URLSearchParams, AbortController, console,
        FormData: class { constructor(f) { this.f = f; } *[Symbol.iterator]() { yield* Object.entries(this.f.campos); } },
        document: { getElementById: (id) => (id === 'filtros-proyectos' ? formulario : { innerHTML: '' }) },
        history: { replaceState() {} },
        window: {},
        fetch: async (url) => { llamadas.push(url); return { ok: true, text: async () => '', json: async () => [] }; },
    };
    const galeria = runInNewContext(`${codigo}\ngaleriaProyectos()`, contexto);
    return { galeria, llamadas };
}

test('sin ningún criterio no consulta al servidor y pide elegir una opción', async () => {
    const { galeria, llamadas } = galeriaCon({ q: '   ', region: '', categoria: '' });

    await galeria.aplicarFiltros();

    assert.equal(llamadas.length, 0);
    assert.equal(galeria.aviso, 'Seleccione al menos un criterio para acotar la búsqueda.');
});

test('con una ubicación elegida sí consulta al servidor', async () => {
    const { galeria, llamadas } = galeriaCon({ q: '', region: 'Biobío', categoria: '' });

    await galeria.aplicarFiltros();

    assert.ok(llamadas.length >= 1);
    assert.match(llamadas[0], /region=Bio/);
});
