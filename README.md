=== PDF Generator por Etiqueta ===
Contributors: Antonio Cambronero
Tags: pdf, dompdf, media-library, taxonomy, custom-post-types
Requires at least: 4.7
Tested up to: 6.4
Stable tag: 1.14

PDF Generator por Etiqueta genera un PDF con todos los posts (o CPT) de una etiqueta seleccionada.
Incluye portada, texto introductorio opcional (HTML permitido), posts con contenido completo,
texto final opcional (HTML permitido) y numeración de páginas. Desde la versión 1.14, incorpora
un interruptor para excluir completamente todas las imágenes del PDF.

== Description ==

Este plugin permite exportar a PDF una colección de posts —de cualquier tipo de contenido—
filtrados por etiquetas de su taxonomía correspondiente. El PDF se guarda automáticamente en
la Biblioteca de Medios.

Características principales:

* Selección de tipo de contenido
* Selección de taxonomía y etiqueta
* Título del PDF personalizado
* Texto introductorio opcional (HTML permitido)
* Texto final opcional (HTML permitido)
* Interruptor para excluir todas las imágenes del PDF
* Numeración de páginas
* PDF almacenado directamente en la Librería de Medios

== Installation ==

1. Sube la carpeta del plugin a `wp-content/plugins/pdf-generator-plugin/`.
2. Instala Dompdf con Composer:

   ```
   cd wp-content/plugins/pdf-generator-plugin
   composer require dompdf/dompdf
   ```

3. Activa **PDF Generator por Etiqueta** en el panel de administración de WordPress.
4. Ve al menú **PDF por Etiqueta** y selecciona:
   - Tipo de contenido
   - Taxonomía
   - Etiqueta
   - Opcional: Título del PDF
   - Opcional: Texto introductorio (HTML permitido)
   - Opcional: Texto final (HTML permitido)
   - Opcional: Activar “Excluir todas las imágenes”
5. Haz clic en **Generar PDF**.

== Frequently Asked Questions ==

= ¿Cómo funciona el interruptor "Excluir todas las imágenes"? =

Si se activa, el PDF no incluirá:
- Imágenes destacadas
- Imágenes de portada
- Imágenes dentro de los posts
- Imágenes en los textos introductorio o final

Todas las etiquetas `<img>` se eliminan del HTML de forma segura.

= ¿Puedo usar HTML en los textos? =

Sí, se permite cualquier HTML que pase por `wp_kses_post`.
Puedes incluir enlaces, listas, párrafos, estilos básicos…

= ¿Cómo personalizo el tamaño de los encabezados? =

Edita el bloque `<style>` dentro de la función `pgeb_generate_pdf()`:
- `h1.title` controla el título de portada
- `h1.post-title` controla el título de cada post
- Puedes añadir reglas CSS adicionales si lo necesitas

== Changelog ==

= 1.14 =
* Añadido interruptor “Excluir todas las imágenes”.
* Eliminación completa de `<img>` cuando la opción está activa.
* Código reorganizado y simplificado.

= 1.13 =
* Soporte para HTML en el texto introductorio y en el texto final.

= 1.12 =
* Se añadieron los campos de texto y checkboxes para intro y final.

= 1.11 =
* Mejoras menores y correcciones visuales.

= 1.10 =
* Título del PDF personalizable.
