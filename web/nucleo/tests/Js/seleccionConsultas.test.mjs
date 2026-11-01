import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import test from 'node:test';

// RF57, CU 57.1 y 57.2: selección de Consultas en el Módulo comercial.
const vista = readFileSync(new URL('../../../vista/admin/consultas/index.blade.php', import.meta.url), 'utf8');
const inicio = vista.indexOf('function seleccionConsultas(idsPagina) {');
const fin = vista.indexOf('function moduloConsultas(', inicio);
assert.ok(inicio >= 0 && fin > inicio, 'La vista debe definir seleccionConsultas antes de moduloConsultas');

const seleccionConsultas = runInNewContext(`${vista.slice(inicio, fin)}\nseleccionConsultas`, {});

test('marcar consultas una por una indica la cantidad y desmarcar todas la deja en cero', () => {
    const s = seleccionConsultas([4, 7, 9]);
    assert.equal(s.cantidad(), 0);

    s.alternar(7);
    s.alternar(9);
    assert.equal(s.cantidad(), 2);
    assert.ok(s.marcada(7));
    assert.ok(!s.marcada(4));

    s.alternar(7);
    s.alternar(9);
    assert.equal(s.cantidad(), 0);
});

test('la casilla general marca y desmarca todas las consultas de la página', () => {
    const s = seleccionConsultas([4, 7, 9]);

    s.alternarTodas();
    assert.equal(s.cantidad(), 3);
    assert.ok(s.todasMarcadas());

    s.alternarTodas();
    assert.equal(s.cantidad(), 0);
    assert.ok(!s.todasMarcadas());
});

test('desmarcar una consulta deja la casilla general desmarcada y conserva el resto', () => {
    const s = seleccionConsultas([4, 7, 9]);
    s.alternarTodas();

    s.alternar(4);
    assert.ok(!s.todasMarcadas());
    assert.equal(s.cantidad(), 2);
    assert.ok(s.marcada(7) && s.marcada(9));
});

test('una página vacía no queda como todas marcadas', () => {
    const s = seleccionConsultas([]);
    assert.ok(!s.todasMarcadas());
});
