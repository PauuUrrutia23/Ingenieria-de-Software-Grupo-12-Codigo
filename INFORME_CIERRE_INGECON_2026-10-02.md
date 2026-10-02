# Informe de cierre — INGECON Incremento 3

**Repositorio:** `C:\Users\parra\Documents\Qwen\Ingenieria-de-Software-Grupo-12-Codigo`
**Plan:** `PLAN_IMPLEMENTACION_GRANULAR_5_ZIP_RF_I1_I2_I3_INGECON.md` (120 fases / 5 paquetes delta)
**Fecha:** 2026-10-02 · Regla 0.1 respetada: no se ejecutaron operaciones de control de versiones.

Reemplaza a `AUDITORIA_INGECON_2026-10-02.md`.

---

## 1. Verificaciones ejecutadas de verdad hoy (en `web/nucleo`)

| Comprobación | Resultado |
|---|---|
| `php artisan test` (SQLite `:memory:`) | **218 passed / 666 assertions / 0 failures** (83.5 s) |
| `node --test tests/Js/*.test.mjs` | **3 passed / 0 failures** |
| `npm run build` | **PASS** — Vite 5.4.21, 59 módulos (CSS 72.38 kB, JS 108.73 kB) |
| Cadena `P01→P05` aplicada sobre `checkpoint_00` | **FIEL a `checkpoint_05`** (231 archivos, hash por hash; verificado por `herramientas_entrega/verificar_cadena.php`) |
| Extracción de los 5 ZIP (§3.6) | **OK** — documentos presentes, hashes íntegros, sin rutas absolutas, sin `../`, sin secretos/basura (`verificar_zip_extra.php`) |

Nota: el estado final sobre el que se corrió la suite es el árbol del repo de trabajo; `checkpoint_05.json` es su congelado de hashes. Los payloads de los paquetes tienen los mismos hashes, por lo que la cadena reconstruye exactamente ese árbol verificado.

---

## 2. Los cinco paquetes de entrega

Nombres y hashes SHA-256 del ZIP completo (carpeta `entregas_incremento3/`):

| Paquete | Responsable (plan) | Fases | Archivos payload | SHA-256 del ZIP |
|---|---|---|---|---|
| `INGECON_PAQUETE_01_FUNDACIONES.zip` | Paulina | 0-15 | 95 | `36b829b2cadd0be8fd0bb29ca0d4da968ffa518df1bddb312e1aa5d5b2aed36f` |
| `INGECON_PAQUETE_02_PUBLICO_Y_RF_CORREGIDOS.zip` | Tomás | 16-42 | 19 | `c6a61b482760a6f5c2bea10fb9cb1bc641545dfb52c3be00e14b91ecb40ea01d` |
| `INGECON_PAQUETE_03_PROYECTOS_Y_CONTACTO.zip` | Vicente | 43-59 | 15 | `9088f72413d4c01729015f15d3f461bea851da141784650e29fcb9fa90750423` |
| `INGECON_PAQUETE_04_COMERCIAL_Y_GESTION.zip` | Diego | 60-86 | 10 | `e5a37e15f923b35fba5e290fe0c70ae7b1d71dc020f1d2f706e7b5e52dc5cf1c` |
| `INGECON_PAQUETE_05_ADMIN_Y_CIERRE.zip` | Benjamín | 87-120 | 13 | `aabd036c8b1dd4d16f3b8d2ee179952c2db1e05f5a73ff81338a87d516dc388e` |

**Corrección aplicada al Paquete 01 preexistente:** salieron del payload los artefactos de runtime `.phpunit.result.cache`, `bootstrap/cache/packages.php` y `bootstrap/cache/services.php` (§3.5). Su `MANIFEST.json` decía `php_artisan_test: PASS` en contradicción con su propio registro; ahora refleja la verdad del árbol de la fase 15 (los 2 fallos preexistentes de `AuthTest` argon2id/bcrypt). El ZIP fue recomprimido y verificado.

---

## 3. Criterio de reparto de los paquetes 02-05 (desviación registrada y aceptada)

El plan define los paquetes como deltas congelados al cerrar las fases 42/59/86. **Esas congelaciones intermedias no existen** (no hay copias de fin de esas fases), por lo que los paquetes 02-04 no pueden ser snapshots temporales exactos.

Criterio usado (elegido por el usuario): **cada archivo final del delta `ck_01→estado final` se asigna al bloque de área funcional del plan que lo introduce.** El payload es siempre el archivo final real con hash real. Consecuencias verificables:

- Aplicar `P01→P02→P03→P04→P05` en serie sobre el original **reconstruye exactamente el estado final** (probado hash por hash, sin solapes intra-delta).
- Los 5 paquetes son una **cadena serial válida**, no 5 estados autoejecutables por separado: un paquete individual no es un árbol que pase la suite (así lo exige §3.1). La verificación real se hace sobre la cadena completa.
- En cada `MANIFEST.json` está el campo `asignacion_fase` (qué fase justifica cada archivo) y `nota_criterio_reparto`.
- 23 archivos se **re-emit** entre P01 y su bloque posterior (cambiaron en ambos): normal en deltas acumulativos; el paquete posterior deja la versión final.

---

## 4. Lo que NO se acreditó (requiere recursos/criterio fuera de este equipo)

1. **Fase 111 — MySQL 5.6.** No hay instancia de prueba (local solo MariaDB 12.3). La compatibilidad real con MySQL 5.6 no se ejecutó. Las migraciones condicionadas a MySQL (p. ej. la FK `ON DELETE SET NULL`, fase 10) quedan sin prueba en ese motor.
2. **Fases 117-118 — navegadores y responsive.** Inspección hecha solo en el navegador integrado; faltan Chrome, Edge, Brave y el barrido 320-1920 px.
3. **Fases 112-116 — batería manual.** Hay cobertura automatizada de archivos, sesiones, mapa y correo, pero no la prueba manual con archivos reales, dos navegadores y un transporte de correo del entorno objetivo.
4. **Fases 107-109 — documentación y matriz.** `REQUISITOS.md`, `ARQUITECTURA.md` y `MATRIZ_RF_TESTS_INCREMENTO3.md` están actualizados con los 16/23/16 RF por incremento, pero el cotejo de cada CU de Incremento 3 contra el Documento 0 queda pendiente: una prueba de módulo no cubre todas las excepciones de cada CU.
5. **Fases por-fase con registro individual.** La suite final verde no reemplaza el registro de que cada una de las 120 fases corrió su test específico y su regresión en el momento de cerrarse (no se llevaron esos registros durante el avance acelerado).
6. **Advertencia de despliegue (§3.5 / seguridad):** el `DatabaseSeeder` (viaja en P02) y la documentación local fijan una credencial inicial conocida para la cuenta jefe. Es código real del proyecto y no se alteró sin instrucción; debe sustituirse por provisión segura antes de producción.

Ningún RF se declara "completo" solo por tener la suite verde: los puntos 1-5 son cierre todavía abierto.

---

## 5. Herramientas de entrega usadas (`herramientas_entrega/`, fuera del código funcional)

`generar_checkpoint.php` (excluye ahora `bootstrap/cache/*.php`, `.phpunit.result.cache` y `storage/app/exports`), `generar_paquete.php`, `sanear_paquete_01.php`, `sanear_ckpt01.php`, `delta_01_05.php`, `clasificar_5zip.php`, `generar_p2a5.php`, `verificar_cadena.php`, `verificar_zip_extra.php`.
