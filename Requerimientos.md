**Requerimientos**

|  |  |
| --- | --- |
| RF01 | El sistema debe permitir al Visitante enviar una Consulta de contacto, asegurando que la información sea registrada en la Base de Datos para su gestión. |
| RF02 | El sistema debe permitir al Visitante ingresar datos en el Formulario de Contacto utilizando exclusivamente los campos definidos: Nombre, Apellido, Email, Mensaje, Fecha y Adjunto.  *Ver Ejemplo en****Anexo 1, Figura 14.1.2:****“Formulario de contacto”* |
| RF07 | El sistema debe notificar al Visitante mediante un Mensaje del Sistema cuando uno o más de los campos ingresados no cumplan con las validaciones establecidas  *(Ver Ejemplo en****Anexo 1, Figura 14.1.3:****“Formulario: Campo inválido”* |

|  |  |
| --- | --- |
| RF12 | El sistema debe mostrar un icono de menú en la interfaz de la página de inicio que, al ser activado mediante una Acción de Usuario, despliegue el Menú Lateral con Enlaces que redirijan a las secciones disponibles del sitio.  *Ver Ejemplo en****Anexo 1, Figura 14.1.6:****“Menú lateral”)* |
| RF13 | El sistema debe mantener visible la Barra de Navegación Fija durante el desplazamiento vertical y, al seleccionar una opción, desplazar la vista hacia la sección correspondiente dentro de la misma página.  *Ver Ejemplo en****Anexo 1, Figura 14.1.7****: “Barra de acceso rápido”* |
| RF20 | El sistema debe permitir al Visitante en una sección de proyectos ingresar una cadena de texto en el menú desplegable y procesarla para filtrar proyectos comparando los campos Nombre de la Obra y Ubicación Geográfica, mostrando únicamente coincidencias parciales o totales. |
| RF21 | El sistema debe permitir al Visitante filtrar proyectos por tipo Habitacional, Industrial o Agrícola, mostrando exclusivamente aquellos que coincidan con la categoría seleccionada. |
| RF24 | El sistema debe mostrar una Ventana Modal con las especificaciones técnicas de un proyecto al detectar una Acción de Usuario sobre su imagen, incluyendo Nombre de la Obra, Descripción Técnica y Ubicación Geográfica.  *Ver Ejemplo en****Anexo 1, Figura 14.1.13:****“Proyectos: Detalles técnicos”* |
| RF25 | El sistema debe permitir al Usuario acceder a la sección “Certificaciones” y visualizar documentos en formato Archivo PDF Estándar almacenados en la Base de Datos.  *Ver Ejemplo en****Anexo 1, Figura 14.1.14:****“Certificados”* |
| RF26 | El sistema debe permitir descargar los certificados en formato Archivo PDF Estándar mediante una Acción de Usuario. |
| RF28 | El sistema debe permitir al Personal de Administración autenticar su identidad ingresando sus Credenciales de acceso en una Ventana Modal para acceder al Panel de Gestión.  *Ver Ejemplo en****Anexo 1, Figura 14.1.15:****“Administrador: Inicio De Sesión”* |
| RF33 | El sistema debe permitir al Personal de Administración cerrar sesión, invalidando el Token de Sesión y redirigiendo a la página de inicio |
| RF34 | El sistema debe proteger la cuenta del Personal de Administración bloqueando temporalmente su acceso por 60 minutos y notificándole a su correo tras 5 intentos fallidos de inicio de sesión. |
| RF46 | El sistema debe permitir al Personal de Administración registrar un nuevo Colaborador desde el Módulo de Colaboradores al realizar una Acción de usuario sobre el botón “Agregar Colaborador”, desplegando un Formulario de Colaborador dentro de una Ventana Modal para ingresar los campos Nombre Comercial y Logotipo, validando que cumple los formatos correctos.  *Ver Ejemplo en****Anexo 1, Figura 14.1.20:****“Administrador: Edición De Colaboradores”* |
| RF49 | El sistema debe permitir al Personal de Administración registrar un nuevo Proyecto desde el Módulo de Proyectos. Al realizar una Acción de Usuario sobre el botón "Nuevo Proyecto", el sistema debe desplegar el Formulario de Proyecto en una Ventana Modal para ingresar los campos Nombre de Proyecto y Fotografías para luego guardarlos en la Base de Datos.  *Ver Ejemplo en****Anexo 1, Figura 14.1.21:****“Administrador: Módulo de Proyectos”* |
| RF50 | El sistema debe permitir al Personal de Administración modificar los detalles de un Proyecto existente. Al interactuar con el botón "Editar Información" en la tarjeta del proyecto, el sistema debe desplegar el Formulario de Proyecto en una Ventana Modal con los datos actuales precargados para su actualización en la Base de Datos. |