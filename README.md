=== PDF Generator por Etiqueta ===
Contributors: Antonio Cambronero
Tags: pdf, dompdf, media-library, taxonomy, custom-post-types
Requires at least: 4.7
Tested up to: 6.4
Stable tag: 1.13

PDF Generator por Etiqueta genera un PDF con todos los posts (o CPT) de una etiqueta seleccionada. Ahora permite:
* Portada con título (etiqueta o personalizado).
* Texto introductorio personalizado (HTML permitido) si se activa.
* Contenido de posts con ajuste de imágenes y bloques de código.
* Texto final personalizado (HTML permitido) si se activa.
* Numeración de páginas.

== Installation ==

1. Copia el plugin en `wp-content/plugins/pdf-generator-plugin/`.
2. Instala Dompdf:
   ```bash
   cd wp-content/plugins/pdf-generator-plugin
   composer require dompdf/dompdf
   ```
3. Activa **PDF Generator por Etiqueta** en Plugins.
4. Accede a **PDF por Etiqueta**:
   - Selecciona tipo, taxonomía y etiqueta.
   - Opcional: escribe **Título del PDF**.
   - Marca **Mostrar texto introductorio** y escribe HTML en el textarea.
   - Marca **Mostrar texto final** y escribe HTML en el textarea.
   - Haz clic en **Generar PDF**.

== Frequently Asked Questions ==

= ¿Puedo usar HTML en los textos? =
Sí, puedes incluir etiquetas HTML válidas (enlaces, listas, etc.) en los campos de texto introductorio y final.

= ¿Cómo evito desbordes? =
Se aplican:
```css
img { max-width:100%; height:auto; }
pre, code { white-space: pre-wrap; word-wrap: break-word; }
```

= ¿Cómo personalizar estilos? =
Edita el bloque `<style>` en `pgeb_generate_pdf()`. Ajusta `h1.title`, `h1.post-title` o añade reglas CSS.

== Changelog ==

= 1.13 =
* Permite HTML en texto introductorio y texto final (wp_kses_post).
* Versión inicial de campos de texto HTML.

= 1.12 =
* Campos de texto y checkbox para intro y final.
* Refactor de generación de PDF.

= 1.11 = ...

