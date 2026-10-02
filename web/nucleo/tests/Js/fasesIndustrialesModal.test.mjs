import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import test from 'node:test';

const vista = readFileSync(new URL('../../../vista/public/index.blade.php', import.meta.url), 'utf8');
const inicio = vista.indexOf('function fasesIndustriales() {');
const fin = vista.indexOf('function carruselColaboradores(total)', inicio);
assert.ok(inicio >= 0 && fin > inicio, 'La vista debe definir el modal de fases');

const document = { body: { style: { overflow: 'auto' } } };
const fasesIndustriales = runInNewContext(
    `${vista.slice(inicio, fin)}\nfasesIndustriales`,
    { document },
);

test('el modal abre, bloquea scroll y restaura foco y scroll al cerrar', () => {
    const componente = fasesIndustriales();
    let focoModal = false;
    let focoDisparador = false;
    componente.$refs = { cerrarImagen: { focus: () => { focoModal = true; } } };
    componente.$nextTick = (callback) => callback();
    const boton = {
        dataset: { src: '/storage/contenido/fase.jpg', descripcion: 'Rollizos.', alt: 'Descortezado' },
        focus: () => { focoDisparador = true; },
    };

    componente.abrirImagen(boton);
    assert.equal(componente.imagenAbierta.src, boton.dataset.src);
    assert.equal(componente.imagenAbierta.descripcion, 'Rollizos.');
    assert.equal(document.body.style.overflow, 'hidden');
    assert.equal(focoModal, true);

    componente.cerrarImagen();
    assert.equal(componente.imagenAbierta, null);
    assert.equal(document.body.style.overflow, 'auto');
    assert.equal(focoDisparador, true);
});

test('no abre modal cuando falta la URL de imagen', () => {
    const componente = fasesIndustriales();
    componente.abrirImagen({ dataset: { src: '' } });
    assert.equal(componente.imagenAbierta, null);
});
