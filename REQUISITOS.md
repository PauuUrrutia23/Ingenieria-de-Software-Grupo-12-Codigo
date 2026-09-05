# Ingecon — Requisitos, Casos de Uso y Stack (Incremento 1 + 2)

> Consolidado a partir de: `Grupo 12 - Documento_0.docx`, `Grupo 12 - incremento1.docx`,
> `Grupo 12 - Incremento 2.docx`, `Dimensión Técnica Ingecon_v1.2_mod.docx`.
> Contiene **todo** lo que debe estar implementado para considerar la app "completa hasta el
> Incremento 2": 38 de los 53 Requerimientos Funcionales (16 del Incremento 1 + 22 del
> Incremento 2), los 17 Requerimientos No Funcionales, y los 64 Casos de Uso asociados con
> sus excepciones (22 del Incremento 1 + 42 del Incremento 2, este último con varios RF
> desglosados en sub-casos de uso según `Casos_de_Uso_Incremento_2_WIP.docx`).

---

## 0. Stack Tecnológico (Dimensión Técnica v1.2)

| Capa | Tecnología |
|---|---|
| Lógica de negocio | **Laravel 10** (PHP, hasta 8.1), patrón MVC, **Eloquent ORM** |
| Presentación | **HTML5 + Tailwind CSS + Alpine.js** (reactividad ligera, sin frameworks pesados) |
| Datos | **MySQL 5.6** (motor del hosting compartido cPanel) — sin NoSQL |
| Mapa | **Leaflet.js + OpenStreetMap** (42 KB, sin APIs de pago tipo Google Maps) |
| Correo | **sendmail** (binario del servidor) + reenviador cPanel — sin SMTP de terceros |
| Hash de contraseñas | **Argon2id** (extensión `sodium`) con **bcrypt** como fallback si `sodium` no está disponible |
| Validación de PDF | Extensión nativa **`finfo`** de PHP (verifica MIME real + cabecera del archivo) |
| Exportación CSV/Excel | **Spatie Simple Excel** |
| Respaldos | **Cron Jobs + `mysqldump`**, retención 7 días, fuera de `public_html` |
| Control de versiones | **Git** |
| Hosting | cPanel compartido, plan `hnegocio`, servidor `dohko` (Apache 2.4.67, AutoSSL) |

**Prohibido explícitamente:** NoSQL, CMS/no-code (WordPress, Wix), microservicios, APIs de pago (Google Maps, SMTP comercial).

**Reglas duras a respetar en el código:**
- Imágenes de proyecto: máx. **15 por obra**, máx. **5 MB** cada una (RT-02, CU48.1).
- Logotipos: máx. **500 KB**; Fotografías: máx. **2 MB** (RNF17).
- Bloqueo de cuenta: **5 intentos fallidos → 60 min de bloqueo** + correo de aviso (RF33).
- Consultas: máx. **5 consultas pendientes por visitante en 24 h** (CU 1.1, Excepción 3).
- Contraseña: mín. 8 caracteres, 1 mayúscula, 1 minúscula, 1 número (+ carácter especial en CU28.1).
- Paginación de consultas: bloques de **10 registros** (RF36).
- PDF debe cumplir **ISO 32000-1** (RNF06) y validarse con `finfo`, no por extensión.

---

## 1. Roles del sistema (definiciones DS-01 a DS-04)

- **Visitante**: usuario público sin autenticación.
- **Administrador**: usuario interno autenticado, gestiona contenido/consultas.
- **Administrador Jefe**: máximo privilegio, cuenta sembrada directo en BD, además crea/elimina cuentas de Administrador (fuera de alcance de Incremento 1-2, es Incremento 3+).
- **Personal de Administración (DS-04)**: término que agrupa a Administrador + Administrador Jefe.

---

## 2. Requerimientos Funcionales — Incremento 1 (16 RF, Prioridad 1)

| ID | Descripción |
|---|---|
| **RF01** | Permitir al Visitante enviar una Consulta de contacto, registrada en BD. |
| **RF06** | Notificar al Visitante mediante Mensaje del Sistema cuando campos del formulario no cumplan validaciones. |
| **RF11** | Mostrar ícono de menú en inicio que despliega el Menú Lateral con enlaces a Proyectos, Certificaciones y Colaboradores (contenido desde BD). |
| **RF12** | Mantener visible la Barra de Navegación Fija durante el scroll; al seleccionar opción, desplaza a la sección correspondiente. |
| **RF19** | Filtrar proyectos por texto libre, comparando Nombre de Obra y Ubicación Geográfica (coincidencia parcial/total). |
| **RF20** | Filtrar proyectos por categoría: Construcción, Industrial o Terminaciones/Servicios. |
| **RF23** | Mostrar Ventana Modal con especificaciones técnicas de un proyecto (Nombre, Descripción Técnica, Ubicación) al hacer clic en su imagen. |
| **RF24** | Mostrar tarjeta por Certificación vigente (Imagen, Nombre Normativa, Descripción, Organismo Certificador con enlace a nueva pestaña). |
| **RF25** | Permitir descargar el PDF de una Certificación (previsualización en nueva pestaña); si no tiene documento, ocultar la opción. |
| **RF27** | Autenticar al Personal de Administración con credenciales en Ventana Modal para acceder al Panel de Gestión. |
| **RF32** | Cerrar sesión del Personal de Administración, invalidando el Token de Sesión, redirige a inicio. |
| **RF33** | Bloquear temporalmente (60 min) la cuenta y notificar por correo tras 5 intentos fallidos de login. |
| **RF45** | Registrar un nuevo Colaborador (Nombre Comercial + Logotipo) vía Formulario en Ventana Modal. |
| **RF47** | Eliminar un registro de Colaborador desde el Módulo de Colaboradores. |
| **RF48** | Registrar un nuevo Proyecto (Nombre + Fotografías) en estado "Borrador" vía Formulario en Ventana Modal. |
| **RF49** | Editar los detalles de un Proyecto existente (datos precargados en el Formulario). |

## 3. Requerimientos Funcionales — Incremento 2 (22 RF, Prioridad 2)

| ID | Descripción |
|---|---|
| **RF02** | Permitir acceso al documento de Términos y Condiciones / Política de Privacidad desde enlace en el pie de página (nueva pestaña). |
| **RF04** | Exigir aceptación de Términos y Condiciones (checkbox) para habilitar el envío del Formulario de Contacto. |
| **RF05** | Solicitar confirmación de envío (Ventana Modal) antes de enviar el Formulario de Contacto. |
| **RF08** | Permitir vaciar simultáneamente todos los campos del Formulario (botón "Limpiar"). |
| **RF09** | Confirmar envío exitoso de la consulta mediante Ventana Modal, solo tras verificar coincidencia de ID en BD. |
| **RF10** | Mostrar enlace en el encabezado de inicio que redirige a la documentación técnica de Conectores Metálicos. |
| **RF18** | Filtrar proyectos por Ubicación Geográfica (menú desplegable + botón "Aplicar Filtros"). |
| **RF21** | Permitir aplicar múltiples filtros de proyectos simultáneamente, actualizando resultados dinámicamente. |
| **RF26** | Registrar una nueva Certificación (Nombre Normativa, Descripción, Imagen, Organismo Certificador + enlace, PDF opcional) vía Ventana Modal. |
| **RF28** | Permitir cambiar contraseña estando autenticado (Contraseña Actual, Nueva, Confirmación + reglas de seguridad). |
| **RF29** | Iniciar recuperación de contraseña ("Olvidé mi contraseña") desde Ventana Modal. |
| **RF30** | Generar y enviar Enlace seguro de recuperación al correo institucional. |
| **RF31** | Restablecer contraseña mediante Formulario validado por Token de Sesión del enlace de recuperación. |
| **RF34** | Mostrar en el Menú Lateral (solo Personal de Administración) accesos a Módulo comercial, Gestión de proyectos, Colaboradores, Panel de gestión y Certificados. |
| **RF36** | Paginar el historial de Consultas del Módulo comercial en bloques de 10 registros. |
| **RF39** | Leer el contenido completo de una Consulta específica en Ventana Modal. |
| **RF41** | Actualizar el estado de una Consulta a "Pendiente", "En Proceso" o "Finalizada". |
| **RF43** | Añadir/actualizar contenido multimedia (imágenes, videos, textos) en FAQ, Opiniones, Banner de Inicio y Fases Industriales. |
| **RF44** | Eliminar registros de imagen/video/descripción de las secciones informativas (con confirmación previa). |
| **RF46** | Editar un Colaborador existente (Formulario precargado en Ventana Modal). |
| **RF50** | Gestionar visibilidad de Proyecto (Borrador = oculto / Publicado = visible en interfaz pública) desde menú desplegable en la tarjeta. |
| **RF51** | Eliminar un Proyecto (borra permanentemente registro + imágenes de BD y de la vista). |

## 4. Requerimientos No Funcionales (comunes a Incremento 1 y 2)

| ID | Categoría | Descripción |
|---|---|---|
| RNF01 | Seguridad | Contraseñas siempre con Hash Criptográfico unidireccional, nunca texto plano. |
| RNF02 | Sesión | Autenticación con Token de Sesión, expiración automática por inactividad/cierre. |
| RNF03 | Autorización | Acceso al Panel de Gestión restringido exclusivamente al Personal de Administración. |
| RNF04 | Control | Verificar tipo/tamaño/formato de archivos antes de almacenar; rechazar si no es PDF estándar. |
| RNF05 | Protección | Validar y sanitizar todos los datos de entrada (anti código malicioso / XSS-SQLi). |
| RNF06 | Estandarización | Documentos deben cumplir formato PDF (ISO 32000-1). |
| RNF07 | Disponibilidad | ≥ 99.5% mensual para interfaz pública y Panel de Gestión. |
| RNF08 | Adaptabilidad | Interfaz responsiva: escritorio, móvil, tablet. |
| RNF09 | Rapidez | Respuesta a Acción de Usuario ≤ 2 segundos en condiciones normales. |
| RNF10 | Usabilidad | Mensajes del Sistema claros, sin exponer info técnica/errores internos. |
| RNF11 | Automatización | Calcular y mostrar años de experiencia de la empresa dinámicamente (desde 1994). |
| RNF12 | Almacenamiento | Persistencia garantizada de todos los datos en BD. |
| RNF13 | Validación | Validar todos los datos de entrada antes de procesarlos. |
| RNF14 | Respaldo | Copia de seguridad de BD al menos cada 24 horas. |
| RNF15 | Rendimiento | Tiempo de respuesta < 3 s con ≥ 100 usuarios concurrentes. |
| RNF16 | Compatibilidad | Compatible con navegadores modernos (Chrome, Edge, Brave recientes). |
| RNF17 | Control | Peso máximo de archivos gráficos: 500 KB (Logotipos), 2 MB (Fotografías). |

---

## 5. Casos de Uso — Incremento 1

> Fuente: `Grupo 12 - incremento1.docx`, sección 5 (contenido ya redactado y completo).

### CU 1.1 – Enviando consulta (RF01)
- **Actor:** Visitante
- **Resumen:** Enviar consulta de contacto desde la página pública, registrando datos del visitante y la consulta.
- **Precondición:** Visitante en la página pública, Formulario de Contacto visible.
- **Descripción:** El sistema presenta el formulario → Visitante ingresa Nombre, Apellido, Email, Mensaje → sistema valida → si es válido, registra en BD y confirma.
- **Excepciones:**
  1. Campos obligatorios en blanco → error, reintentar.
  2. Mensaje < 10 caracteres → error, reintentar.
  3. Visitante supera 5 consultas pendientes en las últimas 24h → bloqueo del insert, aviso de límite alcanzado.
  4. Dominio de correo inválido → rechaza transacción, pide correo válido.
  5. Formato de correo inválido (ej. `texto@texto.texto`) → error, reintentar.
  6. Conexión a internet inestable → error, reintentar.
- **Poscondición:** Consulta registrada en BD.

### CU 6.1 – Mostrando error de validación (RF06)
- **Actor:** Visitante
- **Resumen:** Informar al Visitante cuando datos del formulario no cumplen reglas de validación.
- **Descripción:** Vista envía datos al Controlador → contrasta reglas de validación en BD → si falla, retorna mensajes por campo, no registra hasta corregir.
- **Excepciones:**
  1. Controlador no logra procesar la información → error, reintentar.
  2. Errores de validación desde infraestructura → mensajes por campo, formulario activo.
  3. Respuesta de validación sin detalle por campo → mensaje general.
  4. BD MySQL sin capacidad de almacenamiento → aviso de congestión temporal del servidor.

### CU 11.1 – Desplegando Menú Lateral (RF11)
- **Actor:** Visitante
- **Resumen:** Abrir Menú Lateral y navegar a páginas dedicadas de cada sección (contenido desde BD).
- **Excepciones:**
  1. Falla la consulta a BD → contenido vacío, estado informativo.
  2. Sección sin registros → estado vacío.

### CU 11.2 – Visualizando Colaboradores (dependiente de CU 11.1)
- **Actor:** Visitante
- **Resumen:** Acceder a la página de Colaboradores y ver logos/nombres desde BD.
- **Excepciones:**
  1. Falla consulta BD → colección vacía, estado informativo.
  2. No existen colaboradores registrados → mensaje "aún no hay colaboradores".

### CU 12.1 – Navegando en la Barra de Acceso Rápido (RF12)
- **Actor:** Visitante
- **Resumen:** Desplazarse entre secciones mediante barra de navegación siempre visible.
- **Excepciones:**
  1. Falla consulta BD de alguna sección → esa sección en estado vacío, barra operativa.
  2. Sección sin registros → sección vacía, sin afectar navegación.

### CU 19.1 – Filtrando proyectos por texto (RF19)
- **Actor:** Visitante
- **Resumen:** Buscar proyectos con texto libre comparando Nombre de Obra y Ubicación.
- **Excepciones:**
  1. Cadena solo espacios / insuficiente → limpia filtro, muestra todos los publicados.
  2. Falla consulta BD → búsqueda no disponible temporalmente.
  3. Sin coincidencias → galería vacía con mensaje.
  4. Imágenes de algunos proyectos no cargan → se omite solo la miniatura afectada.

### CU 20.1 – Filtrando proyectos por categoría (RF20)
- **Actor:** Visitante
- **Resumen:** Filtrar galería por categoría (Construcción, Industrial, Terminaciones/Servicios).
- **Excepciones:**
  1. Categoría sin proyectos publicados → estado "No se encontraron proyectos" + opción ver todos.
  2. Falla BD → sin resultados, sección operativa.

### CU 23.1 – Consultando especificaciones de proyecto (RF23)
- **Actor:** Visitante
- **Resumen:** Ver ficha técnica + imágenes de una obra en Ventana Modal sin perder posición en galería.
- **Dependencias:** CU 19.1, CU 20.1
- **Excepciones:**
  1. Proyecto pasó a Borrador o fue eliminado mientras se navegaba → cierra modal sin interrumpir galería.
  2. Falla consulta de detalle → cierra modal, sección operativa.
  3. Visitante cierra modal antes de terminar de cargar → cancela visualización.

### CU 24.1 – Visualizando Certificaciones (RF24)
- **Actor:** Visitante
- **Resumen:** Ver tarjetas de certificaciones vigentes y acceder al sitio del organismo certificador.
- **Excepciones:**
  1. No existen certificaciones → estado vacío informativo.
  2. Falla consulta BD → colección vacía, estado vacío.

### CU 25.1 – Descargando Certificados técnicos (RF25)
- **Actor:** Visitante
- **Resumen:** Descargar el PDF de certificación ya abierto en previsualización.
- **Dependencia:** CU 25.2
- **Excepciones:**
  1. Pérdida de conexión durante transferencia → descarta archivo parcial, reintentar.
  2. Enlace temporal caducado → se reemite el archivo desde BD.
  3. Navegador bloquea descarga → informar autorizar en el navegador.
  4. Certificado sin nombre de archivo válido → sistema genera nombre seguro.

### CU 25.2 – Previsualizando Certificado PDF en el navegador
- **Actor:** Visitante
- **Dependencia:** CU 24.1
- **Excepciones:**
  1. Certificado no existe en BD → documento no disponible.
  2. PDF vacío/nulo en BD → previsualización no disponible.
  3. Falla BD → previsualización no disponible temporalmente.

### CU 27.1 – Autenticando Personal de Administración (RF27)
- **Actor:** Personal de Administración
- **Descripción:** Modal de autenticación → credenciales al Controlador → valida contra BD → crea Sesión y concede acceso.
- **Excepciones:**
  1. Modal no carga → indisponibilidad temporal.
  2. Cuenta bloqueada temporalmente → informa tiempo restante.
  3. Credenciales no coinciden → mensaje genérico de acceso denegado.
  4. BD no responde → no crea sesión, registra incidente.

### CU 27.2 – Rechazando credenciales incorrectas
- **Actor:** Personal de Administración
- **Dependencias:** CU 27.1, CU 33.1
- **Excepciones:**
  1. Credenciales correctas → concede acceso (termina flujo de rechazo).
  2. Cuenta llega a 5 intentos fallidos → bloqueo temporal.
  3. No existe cuenta asociada al correo → mensaje genérico (no revela si existe).
  4. BD no permite actualizar contador de intentos → registra falla, informa error.

### CU 32.1 – Cerrando Sesión (RF32)
- **Actor:** Personal de Administración
- **Dependencia:** CU 27.1
- **Excepciones:**
  1. Sesión ya expiró antes del cierre → cierra sesión local, redirige a inicio.
  2. Token de Sesión no coincide con registro activo → deniega operación protegida.
  3. BD no permite actualizar estado de Sesión → registra inconsistencia, cierra sesión local igual.
  4. Solicitud de cierre no pasa validación de seguridad → rechaza acción.

### CU 33.1 – Bloqueando Cuenta por Seguridad (RF33)
- **Actor:** Personal de Administración
- **Dependencia:** CU 27.1
- **Descripción:** Al alcanzar 5 intentos fallidos, bloquea 60 min y notifica al correo institucional.
- **Excepciones:**
  1. BD no permite actualizar contador → mantiene denegado, registra incidente.
  2. Bloqueo ya activo → informa tiempo restante sin reiniciar periodo.
  3. Correo institucional no disponible → aplica bloqueo, omite solo la notificación.
  4. Servicio de correo no responde → mantiene bloqueo, notificación queda pendiente.

### CU 45.1 – Registrando nuevos Colaboradores (RF45)
- **Actor:** Personal de Administración
- **Dependencia:** CU 27.1
- **Excepciones:**
  1. Nombre Comercial vacío → rechaza antes de tocar BD.
  2. Logotipo con formato no permitido → rechaza, pide corregir archivo.
  3. BD no permite almacenar → error, formulario permanece abierto.
  4. Sesión administrativa expira durante el registro → redirige a login antes de guardar.

### CU 45.2 – Listando Colaboradores en el Módulo
- **Actor:** Personal de Administración
- **Dependencia:** CU 27.1
- **Excepciones:**
  1. Falla consulta BD → mensaje "listado no disponible".
  2. No existen colaboradores → estado vacío.

### CU 47.1 – Eliminando Colaborador (RF47)
- **Actor:** Personal de Administración
- **Dependencia:** CU 27.1
- **Excepciones:**
  1. Colaborador ya no existe (eliminado previamente) → actualiza listado.
  2. BD no permite eliminar → informa falla, mantiene tarjeta visible.
  3. Sesión administrativa expiró → deniega, redirige a login.

### CU 48.1 – Registrando proyectos como borrador (RF48)
- **Actor:** Personal de Administración
- **Dependencia:** CU 27.1
- **Excepciones:**
  1. Nombre de Proyecto vacío → rechaza antes de tocar BD.
  2. Imagen > 5 MB → rechaza.
  3. Más de 15 imágenes adjuntas → bloquea, informa límite.
  4. Imagen con formato inválido o dañada → rechaza, pide formato válido.
  5. BD no puede almacenar proyecto/imagen → error, formulario abierto.
  6. Ninguna imagen pudo procesarse → revierte (elimina proyecto), pide reintentar.
  7. Sesión administrativa expiró → deniega, redirige a login.

### CU 48.2 – Publicando Proyectos
- **Actor:** Personal de Administración
- **Dependencia:** CU 27.1
- **Excepciones:**
  1. Campos obligatorios incompletos → detiene, señala datos faltantes.
  2. Foto > 5 MB → rechaza, pide imagen más liviana.
  3. Más de 15 fotografías → impide carga del excedente.
  4. Archivo no es imagen válida / formato incompatible → descarta, pide JPG/PNG/WebP.
  5. Inactividad > 2 horas en el panel → restringe acceso, pide reingresar credenciales.
  6. Tiempo de seguridad de la conexión caducado → remueve permisos temporales, redirige a inicio.

### CU 48.3 – Listando Proyectos en el Módulo
- **Actor:** Personal de Administración
- **Dependencia:** CU 27.1
- **Excepciones:**
  1. Falla consulta BD → mensaje "listado no disponible".
  2. No existen proyectos registrados → estado vacío.

### CU 49.1 – Editando información del proyecto (RF49)
- **Actor:** Personal de Administración
- **Dependencia:** CU 27.1
- **Excepciones:**
  1. Proyecto no se puede recuperar de BD → cancela edición.
  2. Cambios no cumplen validaciones → errores, formulario abierto.
  3. Imágenes nuevas superan límite o formato inválido → rechaza archivos afectados.
  4. Sesión administrativa expira durante edición → redirige a login, evita guardar cambios no autorizados.

---

## 6. Casos de Uso — Incremento 2

> Fuente: `Casos_de_Uso_Incremento_2_WIP.docx` (versión de trabajo más reciente y detallada,
> reemplaza lo que se había tomado de `Documento_0.docx` en la primera pasada — ver nota de
> comparación al final de esta sección). Cubre los 22 RF de Incremento 2, pero varios se
> desglosan en sub-casos de uso más granulares (28→28.1/28.2, 31→31.1/31.2, 34→34.1...34.6,
> 36→36.1/36.2, 43→43.1...43.9, 44→44.1...44.5), totalizando **42 CU**.

### CU 2.1 – Visualizando Términos, Condiciones y Políticas de Privacidad (RF02)
- **Actor:** Visitante
- **Descripción:** Acción sobre el enlace del pie de página → Controlador recupera la URL vigente del documento desde BD → Vista abre el documento en nueva pestaña.
- **Excepciones:**
  1. Enlace no responde por error de renderizado del pie de página → informa y solicita reintentar.
  2. Falla la consulta BD de la URL del documento → informa que no está disponible temporalmente.
  3. Navegador bloquea la apertura de la nueva pestaña → aviso para permitir ventanas emergentes o enlace directo alternativo.
- **Poscondición:** Documento se muestra en nueva pestaña.

### CU 4.1 – Aceptando Términos, Condiciones y Políticas de Privacidad (RF04)
- **Actor:** Visitante
- **Dependencia:** CU 1.1
- **Descripción:** Casilla de aceptación junto al botón "Enviar"; al marcarla se habilita el envío.
- **Excepciones:**
  1. Casilla no se renderiza correctamente → informa y solicita recargar página.
  2. Visitante desmarca la casilla tras marcarla → deshabilita nuevamente "Enviar".
  3. Intenta enviar sin marcar la casilla → bloquea envío del lado del Controlador, resalta casilla.

### CU 5.1 – Solicitando confirmación de envío (RF05)
- **Actor:** Visitante
- **Dependencia:** CU 1.1
- **Descripción:** Al presionar "Enviar" con formulario válido, despliega Ventana Modal de confirmación antes de transmitir al Controlador.
- **Excepciones:**
  1. Formulario tiene errores de validación pendientes → no despliega el modal, señala campos con error.
  2. Visitante cierra el modal sin elegir opción → se interpreta como cancelación, formulario se conserva activo.
  3. Confirma el envío pero la conexión se interrumpe antes de llegar al Controlador → informa que no se completó, solicita reintentar.

### CU 8.1 – Limpiando formulario (RF08)
- **Actor:** Visitante
- **Dependencia:** CU 1.1
- **Descripción:** Botón "Limpiar" vacía Nombre, Apellido, Email y Mensaje; además restablece la casilla de T&C y deshabilita "Enviar".
- **Excepciones:**
  1. Se presiona "Limpiar" sin haber ingresado datos → sin efectos visibles.
  2. Se presiona "Limpiar" mientras un envío está en curso → se ignora hasta finalizar la operación.

### CU 9.1 – Mostrando confirmación de envío (RF09)
- **Actor:** Visitante
- **Dependencia:** CU 5.1
- **Descripción:** Tras registrar la Consulta, verifica que la ID retornada coincida con la registrada en BD antes de mostrar el modal de éxito.
- **Excepciones:**
  1. BD no logra registrar la Consulta → informa que el envío no pudo completarse.
  2. ID retornada no coincide con la registrada → no muestra confirmación, solicita reintentar envío.
  3. Visitante cierra el modal antes de leerlo → limpia el formulario y vuelve al estado inicial.

### CU 10.1 – Accediendo a documentación técnica (RF10)
- **Actor:** Visitante
- **Descripción:** Enlace del encabezado solicita la URL vigente al Controlador (desde BD) y redirige.
- **Excepciones:**
  1. Enlace no responde por error de carga del encabezado → informa que la acción no pudo ejecutarse.
  2. URL registrada en BD no disponible o eliminada → informa que la documentación no está disponible temporalmente.
  3. Navegador bloquea la redirección → ofrece enlace directo manual.

### CU 18.1 – Filtrando proyectos por ubicación (RF18)
- **Actor:** Visitante
- **Dependencia:** CU 20.1
- **Descripción:** Menú desplegable de Ubicación Geográfica + botón "Aplicar Filtros" → Controlador filtra por ubicación y estado Publicado.
- **Excepciones:**
  1. Se presiona "Aplicar Filtros" sin seleccionar ubicación → galería sin cambios, solicita seleccionar opción.
  2. Falla la consulta a BD → filtrado no disponible temporalmente.
  3. Ubicación seleccionada sin proyectos publicados → estado "No se encontraron proyectos".

### CU 21.1 – Aplicando filtros combinados de proyectos (RF21)
- **Actor:** Visitante
- **Dependencias:** CU 19.1, CU 20.1, CU 18.1
- **Descripción:** Combina texto + categoría + ubicación en una sola consulta contra BD, actualiza dinámicamente sin recargar.
- **Excepciones:**
  1. Criterios contradictorios sin intersección válida → informa que no hay proyectos que cumplan todos los criterios.
  2. Consulta combinada falla por indisponibilidad del servicio → mantiene el último resultado válido mostrado.
  3. Visitante cambia un criterio mientras una consulta anterior está en proceso → cancela la solicitud previa, procesa solo la combinación más reciente.

### CU 26.1 – Registrando Certificados (RF26)
- **Actor:** Personal de Administración
- **Dependencia:** CU 27.1
- **Descripción:** Formulario en Ventana Modal (Nombre Normativa, Descripción, Imagen, Organismo Certificador + enlace, PDF opcional).
- **Excepciones:**
  1. Nombre de la Normativa u Organismo Certificador vacíos → rechaza antes de tocar BD, resalta campos obligatorios.
  2. Imagen Representativa con formato inválido o excede peso permitido → descarta archivo, solicita una válida.
  3. PDF adjunto dañado o no corresponde al formato → rechaza el archivo, permite continuar sin el documento.
  4. BD no permite almacenar la Certificación → error, formulario permanece abierto, registra falla.

### CU 28.1 – Solicitando el cambio de contraseña (RF28, parte 1)
- **Actor:** Personal de Administración
- **Dependencia:** CU 27.1
- **Descripción:** Opción "Cambiar Contraseña" → Controlador verifica sesión activa → despliega Formulario (Contraseña Actual, Nueva, Confirmación).
- **Excepciones:**
  1. Opción no disponible en el Panel → informa indisponibilidad temporal, solicita reintentar.
  2. Sesión administrativa expiró → deniega acceso al formulario, redirige a login.

### CU 28.2 – Confirmando el cambio de contraseña (RF28, parte 2)
- **Actor:** Personal de Administración
- **Dependencia:** CU 28.1
- **Descripción:** Al enviar, valida Contraseña Actual, coincidencia Nueva/Confirmación y reglas de seguridad; si todo pasa, actualiza BD.
- **Excepciones:**
  1. Algún campo vacío → rechaza antes de contactar al Controlador, resalta campos faltantes.
  2. Contraseña Actual incorrecta → rechaza el cambio, informa el motivo.
  3. Nueva Contraseña no coincide con su Confirmación → rechaza, solicita reingresar.
  4. Nueva Contraseña no cumple reglas de seguridad → rechaza, indica requisitos.
  5. BD no permite actualizar → error, formulario permanece abierto.

### CU 29.1 – Iniciando recuperación desde ventana modal (RF29)
- **Actor:** Personal de Administración
- **Dependencia:** CU 27.1
- **Descripción:** Botón "Olvidé mi contraseña" en el modal de login → despliega Formulario de Recuperación (campo Email) en Ventana Modal.
- **Excepciones:**
  1. Modal de recuperación no carga correctamente → informa indisponibilidad temporal, mantiene acceso al login habitual.

### CU 30.1 – Enviando enlace seguro de recuperación (RF30)
- **Actor:** Personal de Administración
- **Dependencia:** CU 29.1
- **Descripción:** Verifica correo en BD, genera Enlace seguro asociado a un Token de Sesión y lo envía al correo institucional.
- **Excepciones:**
  1. Campo Email vacío o formato inválido → rechaza, solicita correo válido.
  2. Correo no corresponde a cuenta registrada → mensaje genérico de confirmación (no revela si existe, por seguridad).
  3. BD no permite generar/almacenar el Token de Sesión → error, solicita reintentar.
  4. Servicio de correo institucional no responde → registra incidente, informa que el envío podría demorar.

### CU 31.1 – Validando el Enlace seguro de recuperación (RF31, parte 1)
- **Actor:** Personal de Administración
- **Dependencia:** CU 30.1
- **Descripción:** Al abrir el enlace recibido, valida el Token de Sesión asociado antes de mostrar el Formulario de Restablecimiento.
- **Excepciones:**
  1. Token expiró → rechaza acceso, informa que debe solicitar un nuevo enlace.
  2. Token ya fue utilizado previamente → invalida el intento, solicita nueva solicitud de recuperación.

### CU 31.2 – Restableciendo la contraseña desde el Formulario (RF31, parte 2)
- **Actor:** Personal de Administración
- **Dependencia:** CU 31.1
- **Descripción:** Ingresa Nueva Contraseña + Confirmación → valida coincidencia y reglas de seguridad → actualiza BD e invalida el Token utilizado.
- **Excepciones:**
  1. Nueva Contraseña no coincide con Confirmación → rechaza, solicita reingresar.
  2. No cumple reglas de seguridad → rechaza, indica requisitos.
  3. BD no permite actualizar la contraseña o invalidar el Token → error, solicita reintentar.

### CU 34.1 – Navegando por módulos administrativos (RF34, overview)
- **Actor:** Personal de Administración
- **Dependencia:** CU 27.1
- **Descripción:** Menú Lateral con enlaces "Módulo comercial", "Gestión de proyectos", "Colaboradores", "Panel de gestión" y "Certificados" (exclusivo del Personal de Administración); redirige al flujo CU 34.2–34.6 según selección.
- **Excepciones:**
  1. Sin permisos para algún módulo → oculta el enlace correspondiente.
  2. Cambios sin guardar en el módulo actual → solicita confirmar abandono antes de navegar.
  3. Sesión administrativa expiró → deniega acceso, redirige a login.
  4. Consulta inicial del módulo falla → estado vacío informativo.

### CU 34.2 – Accediendo al Módulo comercial
- **Actor:** Personal de Administración · **Dependencia:** CU 34.1
- **Descripción:** Consulta Consultas registradas en BD y renderiza el historial.
- **Excepciones:** 1. Sesión expiró → login. 2. Falla consulta BD → estado vacío informativo. 3. Sin consultas registradas → mensaje "aún no hay registros".

### CU 34.3 – Accediendo al Módulo de Gestión de proyectos
- **Actor:** Personal de Administración · **Dependencia:** CU 34.1
- **Descripción:** Consulta Proyectos registrados y renderiza tarjetas con su estado de visibilidad.
- **Excepciones:** 1. Sesión expiró → login. 2. Falla consulta BD → estado vacío informativo. 3. Sin proyectos registrados → mensaje "aún no hay registros".

### CU 34.4 – Accediendo al Módulo de Colaboradores
- **Actor:** Personal de Administración · **Dependencia:** CU 34.1
- **Descripción:** Consulta Colaboradores registrados y renderiza listado con Nombre Comercial y Logotipo.
- **Excepciones:** 1. Sesión expiró → login. 2. Falla consulta BD → estado vacío informativo. 3. Sin colaboradores registrados → mensaje "aún no hay registros".

### CU 34.5 – Accediendo al Panel de gestión
- **Actor:** Personal de Administración · **Dependencia:** CU 34.1
- **Descripción:** Consulta contenido multimedia registrado (FAQ, Opiniones, Banner, Fases Industriales) y lo renderiza.
- **Excepciones:** 1. Sesión expiró → login. 2. Falla consulta BD → estado vacío informativo. 3. Sin contenido registrado → mensaje "aún no hay registros".

### CU 34.6 – Accediendo al Módulo de Certificados
- **Actor:** Personal de Administración · **Dependencia:** CU 34.1
- **Descripción:** Consulta Certificaciones registradas y renderiza listado con Organismo Certificador.
- **Excepciones:** 1. Sesión expiró → login. 2. Falla consulta BD → estado vacío informativo. 3. Sin certificaciones registradas → mensaje "aún no hay registros".

### CU 36.1 – Listando el primer bloque de Consultas (RF36, parte 1)
- **Actor:** Personal de Administración
- **Dependencia:** CU 27.1
- **Descripción:** Primer bloque de 10 Consultas ordenadas por fecha + total de páginas.
- **Excepciones:**
  1. Consulta inicial a BD falla → informa que el historial no está disponible.
  2. Sin Consultas registradas → estado vacío informativo.

### CU 36.2 – Navegando entre bloques de Consultas (RF36, parte 2)
- **Actor:** Personal de Administración
- **Dependencia:** CU 36.1
- **Descripción:** Controles de paginación solicitan el bloque anterior/siguiente de 10 registros.
- **Excepciones:**
  1. Página solicitada fuera de rango → devuelve el bloque más cercano válido, corrige indicador.
  2. Consulta del nuevo bloque falla → conserva el bloque mostrado previamente.

### CU 39.1 – Consultando Detalle de una Consulta (RF39)
- **Actor:** Personal de Administración
- **Dependencia:** CU 36.1
- **Descripción:** Selecciona una Consulta del listado → Ventana Modal con contenido completo.
- **Excepciones:**
  1. Consulta ya fue eliminada previamente → informa no disponible, actualiza listado.
  2. Consulta del detalle falla por indisponibilidad del servicio → informa que el detalle no puede mostrarse temporalmente.

### CU 41.1 – Actualizando estado de consultas (RF41)
- **Actor:** Personal de Administración
- **Dependencia:** CU 39.1
- **Descripción:** Selector "Pendiente" / "En Proceso" / "Finalizada" dentro del modal de detalle; valida transición y actualiza BD.
- **Excepciones:**
  1. Selecciona el mismo estado ya vigente → no genera transacción.
  2. BD no permite actualizar el estado → error, conserva estado anterior visible.

### CU 43.1 – Añadiendo y Actualizando contenido multimedia (RF43, overview)
- **Actor:** Personal de Administración
- **Dependencia:** CU 27.1
- **Descripción:** Selecciona sección (FAQ, Opiniones, Banner de Inicio, Fases Industriales) y operación (añadir/actualizar) → redirige a CU 43.2–43.9.
- **Excepciones:**
  1. Módulo no carga correctamente → informa indisponibilidad, solicita recargar.
  2. Sin permisos sobre alguna sección → oculta la opción correspondiente.

**Sub-casos de "Añadir" (uno por sección, mismo patrón: Formulario → validar → registrar en BD → actualizar vista):**
- **CU 43.2** – Añadiendo en Preguntas Frecuentes (Pregunta + Respuesta + multimedia opcional). Dependencia: CU 43.1.
  - Excepciones: 1) Pregunta/Respuesta vacías → rechaza. 2) Multimedia excede peso o formato no soportado → descarta archivo. 3) BD no permite almacenar → error, formulario abierto.
- **CU 43.3** – Añadiendo en Banner (Imagen/Video + Texto Descriptivo). Dependencia: CU 43.1.
  - Excepciones: 1) Archivo excede peso o formato incompatible → rechaza. 2) Texto Descriptivo vacío → rechaza. 3) BD no permite almacenar → error.
- **CU 43.4** – Añadiendo en Fases Industriales (Nombre Fase + Texto + Imagen/Video). Dependencia: CU 43.1.
  - Excepciones: 1) Nombre/Texto vacíos → rechaza. 2) Archivo excede peso/formato no compatible → rechaza. 3) BD no permite almacenar → error.
- **CU 43.5** – Añadiendo en Opiniones (Nombre Cliente + Testimonio + imagen opcional). Dependencia: CU 43.1.
  - Excepciones: 1) Nombre/Testimonio vacíos → rechaza. 2) Multimedia excede peso/formato no soportado → descarta. 3) BD no permite almacenar → error.

**Sub-casos de "Actualizar" (uno por sección, mismo patrón: precarga Formulario → editar → validar → actualizar BD):**
- **CU 43.6** – Actualizando en Preguntas Frecuentes. Dependencia: CU 43.2.
  - Excepciones: 1) Entrada eliminada previamente → informa, actualiza listado. 2) Falla consulta de datos actuales → cancela edición. 3) Campos vacíos o archivo con formato no permitido → rechaza guardado. 4) BD no permite actualizar → error.
- **CU 43.7** – Actualizando en Banner. Dependencia: CU 43.3.
  - Excepciones: 1) Elemento eliminado previamente → informa, actualiza listado. 2) Falla consulta de datos actuales → cancela. 3) Archivo excede peso o Texto vacío → rechaza. 4) BD no permite actualizar → error.
- **CU 43.8** – Actualizando en Fases Industriales. Dependencia: CU 43.4.
  - Excepciones: 1) Fase eliminada previamente → informa, actualiza listado. 2) Falla consulta de datos actuales → cancela. 3) Archivo con formato no permitido o Texto vacío → rechaza. 4) BD no permite actualizar → error.
- **CU 43.9** – Actualizando en Opiniones. Dependencia: CU 43.5.
  - Excepciones: 1) Testimonio eliminado previamente → informa, actualiza listado. 2) Falla consulta de datos actuales → cancela. 3) Campos vacíos o archivo con formato no permitido → rechaza. 4) BD no permite actualizar → error.

### CU 44.1 – Eliminando Contenido Multimedia (RF44, overview)
- **Actor:** Personal de Administración
- **Dependencia:** CU 27.1
- **Descripción:** Ícono "Eliminar" + Ventana Modal de confirmación → Controlador verifica sesión y elimina de BD.
- **Excepciones:**
  1. Registro ya eliminado previamente → informa, actualiza listado.
  2. BD no permite eliminar → error, mantiene registro visible.
  3. Sesión administrativa expira durante la confirmación → deniega, redirige a login.

**Sub-casos por sección (mismo patrón: ícono "Eliminar" → confirmar en modal → eliminar registro + archivo multimedia asociado de BD):**
- **CU 44.2** – Eliminando en Preguntas Frecuentes. Dependencia: CU 44.1. Excepciones: 1) Ya eliminada por otra sesión → informa. 2) BD no permite eliminar entrada/archivo → error.
- **CU 44.3** – Eliminando en Banner. Dependencia: CU 44.1. Excepciones: 1) Ya eliminado por otra sesión → informa. 2) BD no permite eliminar → error.
- **CU 44.4** – Eliminando en Fases Industriales. Dependencia: CU 44.1. Excepciones: 1) Ya eliminada por otra sesión → informa. 2) BD no permite eliminar → error.
- **CU 44.5** – Eliminando en Opiniones. Dependencia: CU 44.1. Excepciones: 1) Ya eliminado por otra sesión → informa. 2) BD no permite eliminar testimonio/archivo → error.

### CU 46.1 – Editando colaboradores (RF46)
- **Actor:** Personal de Administración
- **Dependencias:** CU 27.1, CU 45.1
- **Descripción:** Botón "Editar" → Formulario precargado (Nombre Comercial, Logotipo) en Ventana Modal → valida y actualiza BD.
- **Excepciones:**
  1. Colaborador eliminado previamente → informa, actualiza directorio.
  2. Falla consulta de datos actuales → cancela proceso de edición.
  3. Nombre Comercial vacío o Logotipo con formato no permitido → rechaza guardado, resalta campo.
  4. Sesión administrativa expira durante la edición → redirige a login, evita guardar cambios no autorizados.

### CU 50.1 – Editando visibilidad del proyecto (RF50)
- **Actor:** Personal de Administración
- **Dependencias:** CU 27.1, CU 48.1
- **Descripción:** Menú desplegable en la tarjeta (Borrador ⇄ Publicado); Publicado = visible al público, Borrador = oculto.
- **Excepciones:**
  1. Selecciona el mismo estado ya vigente → sin transacción, tarjeta sin cambios.
  2. BD no permite actualizar el estado de visibilidad → error, conserva estado anterior.

### CU 51.1 – Eliminando proyecto (RF51)
- **Actor:** Personal de Administración
- **Dependencias:** CU 27.1, CU 48.1
- **Descripción:** Ícono "Papelera" + confirmación en modal → elimina permanentemente registro e imágenes asociadas de BD.
- **Excepciones:**
  1. Proyecto ya eliminado previamente por otra sesión → informa, actualiza listado.
  2. Sesión administrativa expira durante la confirmación → deniega, redirige a login.
  3. BD no permite eliminar el registro o alguna imagen asociada → error, mantiene tarjeta visible, registra falla.

> **Nota de comparación con `Documento_0.docx`:** el contenido temático coincide (mismos 22
> RF cubiertos), pero el WIP es una revisión más nueva: cambia/añade excepciones en casi
> todos los CU (p. ej. CU 18.1 pasa de 2 a 3 excepciones, CU 30.1 de 2 a 4), corrige
> dependencias (CU 18.1 ahora depende de CU 20.1, antes de "Ninguna") y —el cambio más
> relevante para el diseño— **desglosa 6 requerimientos en 20 sub-casos de uso adicionales**
> (RF28, RF31, RF34, RF36, RF43, RF44). Se usó el WIP como fuente definitiva.

---


## 7. Notas para la reconstrucción

1. **Entidades del modelo de datos** (según MERE de Incremento 1-2): `Visitante`, `Consulta`,
   `Administrador`, `Sesion`, `Recuperacion_Password`, `Proyecto`, `Imagen_Proyecto`,
   `Certificado`, `Colaborador`, `Contenido`. 3FN, sin atributos repetidos ni dependencias
   transitivas.
2. **RF07** (correo automático de confirmación al Visitante) y **RF13, RF14, RF15, RF16,
   RF17** (carrusel de colaboradores, FAQ, opiniones, fases industriales, video de fases) son
   Prioridad 3/4 — **no** son parte de Incremento 1-2, quedan pendientes.
3. El Administrador Jefe y la gestión de cuentas (CU 52.1, CU 53.1) tampoco son parte de
   Incremento 1-2.
4. Todos los flujos "Personal de Administración" dependen de **CU 27.1** (autenticación) como
   precondición transversal — implementar el middleware de auth de Laravel primero.
