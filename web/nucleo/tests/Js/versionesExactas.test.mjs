import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

// RNF22: las librerías que se sirven al Visitante tienen la versión exacta fijada.
const paquete = JSON.parse(readFileSync(new URL('../../package.json', import.meta.url), 'utf8'));
const LIBRERIAS = ['@fontsource/inter', '@fontsource/outfit', '@fontsource/ibm-plex-mono', 'lucide', 'leaflet'];

test('las fuentes, los íconos y el mapa usan una versión exacta', () => {
    for (const nombre of LIBRERIAS) {
        const version = paquete.dependencies?.[nombre];
        assert.ok(version, `${nombre} debe estar en dependencies`);
        assert.match(version, /^\d+\.\d+\.\d+$/, `${nombre} debe fijar una versión exacta, no "${version}"`);
    }
});
