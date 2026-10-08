# Referencia visual del sitio WordPress

Comparación realizada el 8 de octubre de 2026 contra https://zaziltunich.com/,
portada, Romance, blog y Cenote Museo. Se inspeccionaron los estilos de Elementor
 y medidas renderizadas en escritorio (1440 px) y móvil (375 px).

- Fuente principal: Montserrat; títulos de páginas interiores: Amiri.
- Cabecera y contenido: contenedor máximo de 1140 px; logo 110 px / 70 px móvil.
- Portada: título 36/36 y subtítulo 30/30; móvil 24/24 y 16/16.
- Introducción: titular 30/44; cuerpo 16/24. Móvil: titular 20/27.
- Cabeceras interiores: 350 px; blog/producto Montserrat 33/33; páginas Amiri 50/50.
- Romance: dos columnas con separación de 20 px; cuerpo 15/22.5 e iconos 40 px.
- Blog: títulos de tarjetas 18/24 y contenido 15/22.5.

`public/assets/css/reference.css` centraliza los ajustes finales, después de
`app.css`. `fonts.css` utiliza copias locales de las fuentes originales con sus
licencias OFL. Los recursos incluyen una versión basada en su modificación para
que una actualización no conserve los estilos anteriores en la caché.

`ContentLayout::columns()` recupera columnas perdidas al importar bloques que
contienen una sola galería y texto, sin modificar la base de datos. Los bloques
con varias galerías conservan su estructura. Esta adaptación no reemplaza las
funciones de WordPress, Elementor o WooCommerce: la reserva usa el sistema propio.
El aviso de revisión, noindex y bloqueo de reservas reales permanecen activos.

Validación local: 42 pruebas correctas, sintaxis PHP y recursos/rutas ES/EN HTTP
200. Portada móvil inspeccionada visualmente, sin desbordamiento horizontal.
La última revisión visual de escritorio no pudo completarse porque el navegador
dejó de estar disponible; las medidas proceden de la inspección previa de la referencia.
