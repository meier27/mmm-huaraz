name: responsive-performance description: Optimizar el diseño responsive, accesibilidad, carga de imágenes, mapas y rendimiento de Laravel.

Responsive Performance — Rendimiento y accesibilidad
1. Objetivo

Crear una web rápida, usable y accesible en dispositivos móviles y computadoras.

2. Diseño responsive

Aplicar mobile-first.

Comprobar:

Teléfonos pequeños.
Teléfonos grandes.
Tablets.
Portátiles.
Escritorios.

Prevenir:

Desbordamiento horizontal.
Texto demasiado pequeño.
Formularios difíciles de completar.
Menús inaccesibles.
Botones demasiado pequeños.
Contenido oculto.
Tablas inutilizables en móviles.
3. Rendimiento frontend
Compilar los recursos con Vite.
Evitar JavaScript innecesario.
Reutilizar componentes.
Optimizar CSS.
Evitar dependencias duplicadas.
Usar imágenes comprimidas.
Declarar dimensiones de imágenes.
Aplicar lazy loading a imágenes no prioritarias.
No aplicar lazy loading indiscriminadamente a la imagen principal.
Reducir solicitudes externas.
Cargar mapas únicamente cuando aporten valor.
4. Rendimiento backend
Evitar consultas N+1.
Aplicar eager loading cuando corresponda.
Paginar listados extensos.
Usar índices apropiados.
Revisar consultas lentas.
Aplicar caché solo cuando sea beneficiosa.
Evitar trabajo costoso en cada renderizado Livewire.
No almacenar en caché información privada sin aislamiento adecuado.
5. Mapas
Mostrar la ubicación confirmada.
Proporcionar enlaces externos a Google Maps.
Evaluar OpenStreetMap como alternativa.
Evitar múltiples bibliotecas cartográficas.
No exponer claves privadas.
Diferenciar la sede de la iglesia anfitriona.
No inventar coordenadas.
6. Accesibilidad

Utilizar WCAG 2.2 AA como objetivo.

HTML semántico.
Etiquetas de formularios.
Contraste.
Navegación por teclado.
Foco visible.
Texto alternativo.
Estados accesibles.
Mensajes de error claros.
Respeto a movimiento reducido.
7. Medición

Cuando sea posible:

Ejecutar Lighthouse.
Revisar Core Web Vitals.
Medir tiempos de respuesta.
Examinar el tamaño de los recursos.
Revisar consultas de base de datos.

No afirmar mejoras porcentuales sin mediciones comparables.

8. Criterios de aceptación

La web debe funcionar en móviles, mantener tiempos de carga razonables y evitar recursos pesados que no aporten valor.