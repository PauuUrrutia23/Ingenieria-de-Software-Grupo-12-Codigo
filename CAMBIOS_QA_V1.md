# Correcciones QA V1

## 1. Registros duplicados al hacer varios clics en «Guardar»

**Problema:** al hacer doble clic o varios clics seguidos en «Guardar», se creaban varios registros iguales.

**Cambio** (`web/vista/components/admin-layout.blade.php`):
- Un único manejador de `submit` a nivel de documento controla todos los formularios del panel.
- Al primer envío válido el formulario se marca como «enviando» y sus botones de envío se deshabilitan.
- Cualquier envío adicional del mismo formulario se cancela.
- Si otro manejador ya canceló el envío (por ejemplo, la validación de imágenes), el formulario no se bloquea.
- Si el navegador restaura la página desde caché (botón «Atrás»), los botones se desbloquean.

---

## 2. Aviso incorrecto al cambiar la visibilidad de un proyecto desde el listado

**Problema:** aparecía el aviso «Los cambios no se guardarán» aunque el cambio de visibilidad ya se había realizado.

**Cambio** (`web/vista/admin/proyectos/index.blade.php`, `web/vista/components/admin-layout.blade.php`):
- El select de visibilidad ahora pregunta «¿Desea publicar este proyecto?» u «¿Desea ocultar este proyecto?» antes de enviar.
- Si el usuario cancela, el select vuelve a su valor anterior.
- El formulario lleva el atributo `data-sin-aviso`, por lo que ya no activa la alerta de cambios sin guardar del navegador.

---

## 3. Aviso de cambios sin guardar al cancelar la edición de un colaborador

**Problema:** al presionar «Cancelar», el sistema advertía que se perderían cambios.

**Cambio** (`web/vista/components/admin-layout.blade.php`, `web/vista/admin/colaboradores/edit.blade.php`, `colaboradores/create.blade.php`, `certificados/create.blade.php`, `proyectos/create.blade.php`):
- Los enlaces «Cancelar» llevan el atributo `data-cancelar`.
- Al hacer clic en uno de ellos se limpia el estado de cambios pendientes y no se muestra ningún aviso.
- Los enlaces `mailto:` y `tel:` también quedan excluidos del aviso, porque no abandonan la página.

---

## 4. Datos de un colaborador distintos tras guardar la edición

**Problema:** al editar y guardar un colaborador, algunos datos quedaban distintos de los ingresados.

**Cambio** (`web/controlador/AdminController.php`, `web/vista/admin/colaboradores/index.blade.php`, `web/vista/admin/colaboradores/edit.blade.php`):
- `colaboradoresUpdate` escribe únicamente los campos del formulario: `nombre_comercial`, `sitio_web` y, solo si se sube uno nuevo, `logotipo` y `tipo_mime`.
- Si la validación falla, el modal de edición se reabre con los valores que el usuario escribió (se agregó el campo oculto `_colaborador_id`).
- La página de edición independiente usa `old('nombre_comercial', ...)` para conservar lo escrito.
- Al abrir el modal de eliminar, el objeto seleccionado incluye `sitio_web` vacío para no dejar valores indefinidos.

---

## 5. Error de validación deja inutilizable el formulario de edición de productos

**Problema:** si ocurría un error de validación (por ejemplo, nombre vacío), el formulario volvía en un estado incorrecto y fallaba al guardar de nuevo.

**Causa:** el formulario no enviaba el ID del producto, así que al volver con el error el campo `old('producto_id')` quedaba vacío y la URL de envío salía sin ID.

**Cambio** (`web/vista/admin/productos/index.blade.php`):
- Se agregó el campo oculto `producto_id` (`:value="producto.id"`) al formulario de edición.
- El modal se reabre con el ID, el nombre, la descripción y los componentes que el usuario había escrito.

---

## 6. Eliminar productos

**Problema:** el módulo permitía crear y editar productos, pero no eliminarlos.

**Cambio** (`web/nucleo/routes/web.php`, `web/controlador/ProductoAdminController.php`, `web/vista/admin/productos/index.blade.php`):
- Nueva ruta `DELETE admin/productos/{producto}` (`admin.productos.destroy`), dentro del grupo con permiso `editar_contenido`.
- Nuevo método `ProductoAdminController::destroy`: en una transacción bloquea el registro, elimina sus componentes y el producto, y después borra la imagen del disco.
- Si el producto ya no existe, redirige al listado con el mensaje «El producto ya no está disponible.»
- Si falla la eliminación, vuelve con el mensaje «No se pudo eliminar el producto.»
- Cada producto del listado tiene un botón **Eliminar** que abre un modal de confirmación («Dejará de mostrarse en el sitio y esta acción no se puede deshacer»).

---

## 9. El detalle de un proyecto no se abre correctamente desde la página de inicio

**Problema:** al hacer clic en un proyecto desde la página de inicio se abría la galería en lugar del modal con el detalle.

**Cambio** (`web/vista/public/index.blade.php`, `web/vista/public/proyectos.blade.php`):
- Las tarjetas de proyectos recientes (destacado, secundarios y lista) enlazan a `/proyectos?proyecto={id}`.
- Al cargar la galería, si la URL trae el parámetro `proyecto`, se abre directamente el modal con la ficha de ese proyecto.
- Al cerrar el modal se elimina el parámetro de la URL, de modo que recargar la página no lo reabre.

---

## 10. Marcadores del mapa con ubicaciones incorrectas al filtrar o hacer zoom

**Problema:** al filtrar proyectos, los marcadores se descuadraban y no marcaban la zona correcta al acercar o alejar el mapa.

**Causa:** el mapa y los marcadores de Leaflet estaban guardados dentro del estado reactivo de Alpine, cuyo proxy interfiere con el cálculo de posiciones.

**Cambio** (`web/vista/public/proyectos.blade.php`):
- El mapa (`mapaLeaflet`), la capa de marcadores (`capaMarcadores`) y el índice de marcadores (`marcadoresPorId`) pasan a variables fuera de Alpine.
- Los marcadores viven en una `L.layerGroup`; al filtrar se vacía la capa completa y se redibuja.
- Se llama a `invalidateSize()` antes de ajustar la vista con `setView` o `fitBounds`.

---

## 11. Límite de tamaño de las imágenes

**Problema:** el límite de 2 MB por fotografía era insuficiente; las imágenes del cliente superan los 3 MB.

**Cambio:**
- `Rnf17::IMAGEN_MAX_KB` pasa de 2048 a 5120 (5 MB). El límite de logos de colaboradores sigue en 500 KB.
- Se actualizaron los textos y las validaciones en el navegador de `proyectos/create`, `proyectos/index`, `productos/index` y `certificados/index` (de 2 MB a 5 MB).
- `post_max_size` pasa de 64M a 128M en `web/nucleo/public/.user.ini` y `scripts/php-config/ingecon-upload.ini`, para que quepan 15 fotos de 5 MB en un mismo envío.

---

## 12. Hora del sistema atrasada unas tres horas

**Problema:** las horas del panel no coincidían con la hora de Chile (una consulta enviada a las 15:00 se mostraba a las 12:00).

**Cambio** (`web/nucleo/config/app.php`, `web/nucleo/.env.example`):
- `timezone` pasa de `'UTC'` a `env('APP_TIMEZONE', 'America/Santiago')`.
- Se agregó `APP_TIMEZONE=America/Santiago` a `.env.example`.

---

## 13. Fecha de «Última actualización» de los Términos

**Problema:** la fecha siempre mostraba el día actual y cambiaba cada día.

**Cambio** (`web/controlador/InstitucionalCtrl.php`, `web/vista/legal/terminos.blade.php`):
- Nueva constante `TERMINOS_ULTIMA_ACTUALIZACION = '2026-10-03'`, la fecha del último cambio del documento.
- La vista muestra esa fecha (`$ultimaActualizacion`) en lugar de `now()`.
- Para actualizarla, se edita esa constante cuando cambie el documento.

---

## 14. Las fases industriales no se muestran correctamente

**Problema:** las fases con títulos distintos a los esperados no aparecían en el sitio, y tampoco las que superaban el límite de tres.

**Cambio** (`web/controlador/InstitucionalCtrl.php`):
- Se eliminó la lista fija de tres títulos (`FASES_INDUSTRIALES`).
- `fasesIndustriales()` devuelve todos los registros activos de la sección, agrupados por título.
- Los registros con el mismo título forman una sola fase (texto más sus imágenes o videos).
- El orden es por `orden` y luego por `id_contenido`.

---

## 15. Cambiar la contraseña por la misma contraseña anterior

**Problema:** al ingresar la contraseña actual como contraseña nueva, el sistema aceptaba el cambio y mostraba «Actualizada correctamente».

**Cambio** (`web/controlador/AuthController.php`):
- En `passwordUpdate`, después de verificar la contraseña actual, se compara la nueva contra la guardada.
- Si coinciden, no se actualiza y se muestra el error «La nueva contraseña debe ser distinta de la actual.»
