import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import test from 'node:test';

const vista = readFileSync(new URL('../../../vista/public/index.blade.php', import.meta.url), 'utf8');
const inicio = vista.indexOf('function carruselColaboradores(total) {');
const fin = vista.indexOf('function formularioContacto()', inicio);
assert.ok(inicio >= 0 && fin > inicio, 'La vista debe definir el carrusel de colaboradores');

const carruselColaboradores = runInNewContext(
    `${vista.slice(inicio, fin)}\ncarruselColaboradores`,
    {},
);

test('el carrusel navega con anterior, siguiente y puntos sin salir de sus límites', () => {
    const carrusel = carruselColaboradores(3);
    assert.equal(carrusel.actual, 0);

    carrusel.anterior();
    assert.equal(carrusel.actual, 0);

    carrusel.siguiente();
    assert.equal(carrusel.actual, 1);
    carrusel.siguiente();
    assert.equal(carrusel.actual, 2);
    carrusel.siguiente();
    assert.equal(carrusel.actual, 2);

    carrusel.ir(1);
    assert.equal(carrusel.actual, 1);
    carrusel.ir(1);
    assert.equal(carrusel.actual, 1);

    carrusel.anterior();
    assert.equal(carrusel.actual, 0);
});
