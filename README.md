# Generador de PDF Personalizado para WordPress

Plugin de WordPress que permite generar PDFs personalizados a partir de posts, páginas y taxonomías seleccionadas, con una interfaz de administración intuitiva.

## 📋 Características

- **Interfaz de administración simple e intuitiva**
- **Selección flexible de contenido:**
  - Tipos de contenido (Entradas, Páginas, Custom Post Types)
  - Taxonomías (Categorías, Etiquetas, taxonomías personalizadas)
  - Términos específicos dentro de cada taxonomía
- **Generación automática de portada** con título e imagen destacada
- **Formato profesional del contenido:**
  - Preserva encabezados (H1-H6)
  - Mantiene negritas, cursivas y enlaces
  - Respeta listas ordenadas y desordenadas
  - Incluye blockquotes con estilo
  - Ajusta automáticamente tamaños de fuente pequeños para legibilidad
- **Procesamiento inteligente de imágenes:**
  - Ajuste automático al ancho de página
  - Mantiene proporciones originales
  - Soporte para imágenes locales y externas
  - Alta resolución (300 DPI)
- **Cada post en una página nueva** con título y fecha de publicación
- **Guardado automático** en la Librería de Medios de WordPress

## 🚀 Instalación

### Requisitos previos

- WordPress 5.0 o superior
- PHP 7.2 o superior
- Librería TCPDF

### Pasos de instalación

1. **Descargar el plugin:**

   Descarga el zip y súbelo a tu instalación de WordPress.
   
3. **Instalar TCPDF:**
   
   Descarga TCPDF desde [su repositorio oficial](https://github.com/tecnickcom/TCPDF) y colócala en la carpeta del plugin:
   
   ```bash
   cd wp-content/plugins/pdf-generator
   mkdir -p lib
   cd lib
   git clone https://github.com/tecnickcom/TCPDF.git tcpdf
   ```
   
   O descarga el ZIP y descomprímelo en `lib/tcpdf/`

4. **Estructura de carpetas:**
   ```
   wp-content/plugins/pdf-generator/
   ├── pdf-generator.php
   ├── lib/
   │   └── tcpdf/
   │       ├── tcpdf.php
   │       ├── config/
   │       ├── fonts/
   │       └── ...
   ├── assets/
   │   ├── css/
   │   │   └── admin.css (generado automáticamente)
   │   └── js/
   │       └── admin.js (generado automáticamente)
   └── README.md
   ```

5. **Activar el plugin:**
   - Ve a WordPress Admin → Plugins
   - Busca "Generador de PDF Personalizado"
   - Haz clic en "Activar"

## 📖 Uso

### Interfaz de administración

1. En el panel de WordPress, ve a **Generador PDF** en el menú lateral
2. Selecciona el **Tipo de Contenido** (Entradas, Páginas, etc.)
3. *Opcional:* Selecciona una **Taxonomía** (Categorías, Etiquetas, etc.)
4. *Opcional:* Si seleccionaste una taxonomía, elige un **Término Específico**
5. *Opcional:* Añade un **Título personalizado** para el PDF
6. Haz clic en **Generar PDF**

### Estructura del PDF generado

- **Portada:** Título del documento + imagen destacada del primer post
- **Contenido:** Cada post en una página nueva con:
  - Título del post
  - Fecha de publicación
  - Contenido completo con formato
  - Imágenes ajustadas y centradas

### Ejemplos de uso

**Generar PDF de todos los posts de una categoría:**
```
Tipo de Contenido: Entradas
Taxonomía: Categorías
Término: Noticias
```

**Generar PDF de todas las páginas:**
```
Tipo de Contenido: Páginas
Taxonomía: (vacío)
Término: (vacío)
```

**Generar PDF de posts con una etiqueta específica:**
```
Tipo de Contenido: Entradas
Taxonomía: Etiquetas
Término: Tutorial
```

## 🎨 Características de formato

### Texto
- **Encabezados:** H1-H6 con tamaños jerárquicos
- **Negritas y cursivas:** Totalmente preservadas
- **Enlaces:** Clickeables con color azul
- **Listas:** Con viñetas o números
- **Citas (blockquotes):** Con borde y fondo gris claro
- **Tamaño mínimo de fuente:** 9pt para legibilidad

### Imágenes
- Ancho máximo: 170mm (ajustado a página A4)
- Resolución: 300 DPI
- Centradas automáticamente
- Proporción original mantenida
- Salto de página automático si no cabe

## 🔧 Personalización

### Modificar tamaños de fuente

Edita la función `improve_html_for_pdf()` en `pdf-generator.php`:

```php
$content = preg_replace('/<h1([^>]*)>/i', '<h1 style="font-size: 24pt; ..."$1>', $content);
```

### Cambiar márgenes

Modifica en la función `generate_pdf_file()`:

```php
$pdf->SetMargins(20, 20, 20); // izquierda, arriba, derecha
```

### Ajustar ancho de imágenes

En la función donde se procesan las imágenes:

```php
$max_width = 170; // mm - Cambia este valor
```

## 🐛 Resolución de problemas

### El selector de términos no aparece
1. Desactiva y reactiva el plugin
2. Limpia la caché del navegador
3. Verifica la consola del navegador (F12) para errores JavaScript

### Error "TCPDF no encontrado"
- Verifica que la carpeta `lib/tcpdf/` existe
- Asegúrate de que el archivo `tcpdf.php` está en esa ubicación
- Verifica permisos de lectura en la carpeta

### Las imágenes no aparecen
- Verifica que las imágenes están en la librería de medios
- Comprueba permisos de lectura en `wp-content/uploads/`
- Revisa el registro de errores de PHP

### Timeout al generar PDF
Si tienes muchos posts:
- Aumenta el `max_execution_time` en PHP
- Filtra por taxonomía/término para reducir posts
- Contacta con tu hosting para aumentar límites

## 📝 Changelog

### Version 1.0.0
- Lanzamiento inicial
- Interfaz de administración
- Generación de PDFs con formato completo
- Soporte para imágenes
- Guardado en librería de medios

## 🤝 Contribuir

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia GPL v2 o posterior - ver el archivo [LICENSE](LICENSE) para más detalles.

## 👤 Autor

Antonio Cambronero (Blogpocket.com) - [@blogpocket](https://github.com/blogpocket)

## 🙏 Agradecimientos

- [TCPDF](https://github.com/tecnickcom/TCPDF) - Librería para generación de PDFs
- Comunidad de WordPress por su excelente documentación

## 📞 Soporte

¿Problemas o preguntas? Abre un [issue](https://github.com/blogpocket/wp-pdf-generator/issues) en GitHub.

---

**Nota:** Este plugin requiere la librería TCPDF que debe instalarse por separado debido a su tamaño.
