<?php
/**
 * Plugin Name: Generador de PDF Personalizado
 * Plugin URI: https://www.blogpocket.com/
 * Description: Genera PDFs personalizados a partir de posts, páginas y taxonomías seleccionadas
 * Version: 1.0.0
 * Author: Antonio Cambronero
 * License: GPL v2 or later
 * Text Domain: pdf-generator
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes
define('PDF_GEN_VERSION', '1.0.0');
define('PDF_GEN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PDF_GEN_PLUGIN_URL', plugin_dir_url(__FILE__));

class PDF_Generator {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_pdf_gen_get_terms', array($this, 'ajax_get_terms'));
        add_action('wp_ajax_pdf_gen_generate_pdf', array($this, 'ajax_generate_pdf'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('Generador de PDF', 'pdf-generator'),
            __('Generador PDF', 'pdf-generator'),
            'manage_options',
            'pdf-generator',
            array($this, 'admin_page'),
            'dashicons-media-document',
            30
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_pdf-generator') {
            return;
        }
        
        // Asegurar que los archivos existen
        $this->ensure_assets_exist();
        
        wp_enqueue_style('pdf-gen-admin', PDF_GEN_PLUGIN_URL . 'assets/css/admin.css', array(), PDF_GEN_VERSION);
        wp_enqueue_script('pdf-gen-admin', PDF_GEN_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), PDF_GEN_VERSION, true);
        
        wp_localize_script('pdf-gen-admin', 'pdfGenAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pdf_gen_nonce')
        ));
    }
    
    private function ensure_assets_exist() {
        $css_dir = PDF_GEN_PLUGIN_DIR . 'assets/css';
        $js_dir = PDF_GEN_PLUGIN_DIR . 'assets/js';
        
        // Crear directorios si no existen
        if (!file_exists($css_dir)) {
            wp_mkdir_p($css_dir);
        }
        if (!file_exists($js_dir)) {
            wp_mkdir_p($js_dir);
        }
        
        // Crear archivo CSS si no existe
        $css_file = $css_dir . '/admin.css';
        if (!file_exists($css_file)) {
            $css_content = '.pdf-generator-wrap { max-width: 900px; }
.pdf-gen-container { background: #fff; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 20px; }
#pdf-gen-status .notice { margin-top: 20px; }
.notice-success { border-left-color: #46b450; }
.notice-error { border-left-color: #dc3232; }';
            file_put_contents($css_file, $css_content);
        }
        
        // Crear archivo JS si no existe
        $js_file = $js_dir . '/admin.js';
        if (!file_exists($js_file)) {
            $this->create_admin_js($js_file);
        }
    }
    
    private function create_admin_js($filepath) {
        $js_content = "jQuery(document).ready(function($) {
    console.log('PDF Generator JS cargado correctamente');
    
    // Evento para cargar términos cuando se selecciona una taxonomía
    $('#taxonomy').on('change', function() {
        var taxonomy = $(this).val();
        console.log('Taxonomía seleccionada:', taxonomy);
        
        // Ocultar y limpiar el selector de términos
        $('#term-row').hide();
        $('#term').empty().append('<option value=\"\">Seleccionar término...</option>');
        
        if (taxonomy) {
            console.log('Enviando petición AJAX para obtener términos...');
            
            $.ajax({
                url: pdfGenAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'pdf_gen_get_terms',
                    nonce: pdfGenAjax.nonce,
                    taxonomy: taxonomy
                },
                beforeSend: function() {
                    console.log('Enviando petición...');
                },
                success: function(response) {
                    console.log('Respuesta recibida:', response);
                    
                    if (response.success) {
                        var termSelect = $('#term');
                        termSelect.empty().append('<option value=\"\">Todos los términos</option>');
                        
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, term) {
                                termSelect.append('<option value=\"' + term.value + '\">' + term.label + '</option>');
                            });
                            console.log('Se cargaron ' + response.data.length + ' términos');
                            $('#term-row').show();
                        } else {
                            console.log('La taxonomía no tiene términos');
                            termSelect.append('<option value=\"\" disabled>No hay términos disponibles</option>');
                            $('#term-row').show();
                        }
                    } else {
                        console.error('Error en la respuesta:', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error AJAX:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                }
            });
        }
    });
    
    // Formulario de generación de PDF
    $('#pdf-generator-form').on('submit', function(e) {
        e.preventDefault();
        
        var \$btn = $('#generate-pdf-btn');
        var \$status = $('#pdf-gen-status');
        var \$message = $('#status-message');
        
        \$btn.prop('disabled', true).text('Generando PDF...');
        \$status.hide();
        
        $.ajax({
            url: pdfGenAjax.ajax_url,
            type: 'POST',
            data: $(this).serialize() + '&action=pdf_gen_generate_pdf&nonce=' + pdfGenAjax.nonce,
            success: function(response) {
                \$btn.prop('disabled', false).text('Generar PDF');
                
                if (response.success) {
                    \$status.find('.notice').removeClass('notice-error').addClass('notice-success');
                    \$message.html(response.data.message + ' <a href=\"' + response.data.url + '\" target=\"_blank\">Ver PDF</a>');
                } else {
                    \$status.find('.notice').removeClass('notice-success').addClass('notice-error');
                    \$message.text(response.data);
                }
                
                \$status.show();
            },
            error: function() {
                \$btn.prop('disabled', false).text('Generar PDF');
                \$status.find('.notice').removeClass('notice-success').addClass('notice-error');
                \$message.text('Error en la comunicación con el servidor');
                \$status.show();
            }
        });
    });
    
    console.log('Eventos registrados correctamente');
});";
        file_put_contents($filepath, $js_content);
    }
    
    public function admin_page() {
        ?>
        <div class="wrap pdf-generator-wrap">
            <h1><?php _e('Generador de PDF Personalizado', 'pdf-generator'); ?></h1>
            
            <div class="pdf-gen-container">
                <form id="pdf-generator-form">
                    <?php wp_nonce_field('pdf_gen_nonce', 'pdf_gen_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="post_type"><?php _e('Tipo de Contenido', 'pdf-generator'); ?></label>
                            </th>
                            <td>
                                <select name="post_type" id="post_type" class="regular-text">
                                    <option value=""><?php _e('Seleccionar...', 'pdf-generator'); ?></option>
                                    <?php
                                    $post_types = get_post_types(array('public' => true), 'objects');
                                    foreach ($post_types as $post_type) {
                                        echo '<option value="' . esc_attr($post_type->name) . '">' . esc_html($post_type->label) . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        
                        <tr id="taxonomy-row">
                            <th scope="row">
                                <label for="taxonomy"><?php _e('Taxonomía', 'pdf-generator'); ?></label>
                            </th>
                            <td>
                                <select name="taxonomy" id="taxonomy" class="regular-text">
                                    <option value=""><?php _e('Seleccionar taxonomía...', 'pdf-generator'); ?></option>
                                    <?php
                                    // Obtener todas las taxonomías públicas
                                    $taxonomies = get_taxonomies(array('public' => true), 'objects');
                                    foreach ($taxonomies as $taxonomy) {
                                        echo '<option value="' . esc_attr($taxonomy->name) . '">' . esc_html($taxonomy->label) . '</option>';
                                    }
                                    ?>
                                </select>
                                <p class="description"><?php _e('Selecciona una taxonomía para filtrar (opcional)', 'pdf-generator'); ?></p>
                            </td>
                        </tr>
                        
                        <tr id="term-row" style="display:none;">
                            <th scope="row">
                                <label for="term"><?php _e('Término Específico', 'pdf-generator'); ?></label>
                            </th>
                            <td>
                                <select name="term" id="term" class="regular-text">
                                    <option value=""><?php _e('Seleccionar término...', 'pdf-generator'); ?></option>
                                </select>
                                <p class="description"><?php _e('Selecciona un término específico (opcional)', 'pdf-generator'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="pdf_title"><?php _e('Título del PDF', 'pdf-generator'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="pdf_title" id="pdf_title" class="regular-text" placeholder="<?php _e('Título opcional', 'pdf-generator'); ?>">
                                <p class="description"><?php _e('Deja en blanco para usar el título automático', 'pdf-generator'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary button-large" id="generate-pdf-btn">
                            <?php _e('Generar PDF', 'pdf-generator'); ?>
                        </button>
                    </p>
                </form>
                
                <div id="pdf-gen-status" style="display:none;">
                    <div class="notice">
                        <p id="status-message"></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    

    
    public function ajax_get_terms() {
        check_ajax_referer('pdf_gen_nonce', 'nonce');
        
        $taxonomy = isset($_POST['taxonomy']) ? sanitize_text_field($_POST['taxonomy']) : '';
        
        if (empty($taxonomy)) {
            wp_send_json_error('No se especificó taxonomía');
        }
        
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false
        ));
        
        $output = array();
        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $term) {
                $output[] = array(
                    'value' => $term->term_id,
                    'label' => $term->name
                );
            }
        }
        
        wp_send_json_success($output);
    }
    
    public function ajax_generate_pdf() {
        check_ajax_referer('pdf_gen_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes');
        }
        
        // Verificar diferentes ubicaciones posibles de TCPDF
        $tcpdf_paths = array(
            PDF_GEN_PLUGIN_DIR . 'lib/tcpdf/tcpdf.php',
            PDF_GEN_PLUGIN_DIR . 'lib/TCPDF-main/tcpdf.php',
            PDF_GEN_PLUGIN_DIR . 'lib/TCPDF/tcpdf.php',
        );
        
        $tcpdf_found = false;
        $tcpdf_path = '';
        
        foreach ($tcpdf_paths as $path) {
            if (file_exists($path)) {
                $tcpdf_found = true;
                $tcpdf_path = $path;
                break;
            }
        }
        
        if (!$tcpdf_found) {
            // Mostrar información de debug
            $lib_dir = PDF_GEN_PLUGIN_DIR . 'lib/';
            $debug_info = 'TCPDF no encontrado. Buscado en: ';
            
            if (is_dir($lib_dir)) {
                $dirs = scandir($lib_dir);
                $debug_info .= ' Carpetas encontradas en lib/: ' . implode(', ', array_diff($dirs, array('.', '..')));
            } else {
                $debug_info .= ' La carpeta lib/ no existe.';
            }
            
            wp_send_json_error($debug_info . ' Por favor verifica la instalación de TCPDF.');
        }
        
        // Incluir la librería TCPDF
        require_once($tcpdf_path);
        
        $post_type = sanitize_text_field($_POST['post_type']);
        $taxonomy = isset($_POST['taxonomy']) ? sanitize_text_field($_POST['taxonomy']) : '';
        $term_id = isset($_POST['term']) ? intval($_POST['term']) : 0;
        $pdf_title = isset($_POST['pdf_title']) ? sanitize_text_field($_POST['pdf_title']) : '';
        
        // Construir query de posts
        $args = array(
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        if (!empty($taxonomy) && !empty($term_id)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => $taxonomy,
                    'field' => 'term_id',
                    'terms' => $term_id
                )
            );
        }
        
        $query = new WP_Query($args);
        
        if (!$query->have_posts()) {
            wp_send_json_error('No se encontraron posts con los criterios especificados');
        }
        
        try {
            // Generar el PDF
            $pdf_path = $this->generate_pdf_file($query, $pdf_title);
            
            if ($pdf_path && file_exists($pdf_path)) {
                // Subir a la librería de medios
                $attachment_id = $this->upload_to_media_library($pdf_path);
                
                if ($attachment_id) {
                    $attachment_url = wp_get_attachment_url($attachment_id);
                    wp_send_json_success(array(
                        'message' => 'PDF generado correctamente',
                        'url' => $attachment_url,
                        'attachment_id' => $attachment_id
                    ));
                } else {
                    wp_send_json_error('Error al subir el PDF a la librería de medios');
                }
            } else {
                wp_send_json_error('Error al generar el archivo PDF');
            }
        } catch (Exception $e) {
            wp_send_json_error('Error al generar PDF: ' . $e->getMessage());
        }
    }
    
    private function generate_pdf_file($query, $custom_title = '') {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Configuración del documento
        $pdf->SetCreator('WordPress PDF Generator');
        $pdf->SetAuthor(get_bloginfo('name'));
        
        $posts = $query->posts;
        $first_post = $posts[0];
        
        // Título del documento
        $doc_title = !empty($custom_title) ? $custom_title : $first_post->post_title;
        $pdf->SetTitle($doc_title);
        
        // Configuración de márgenes
        $pdf->SetMargins(20, 20, 20);
        $pdf->SetAutoPageBreak(true, 25);
        
        // Eliminar header y footer por defecto
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // PORTADA
        $pdf->AddPage();
        
        // Obtener imagen destacada o primera imagen del post
        $cover_image = $this->get_post_cover_image($first_post->ID);
        
        // Título en la portada
        $pdf->SetFont('helvetica', 'B', 28);
        $pdf->Cell(0, 40, '', 0, 1); // Espacio superior
        $pdf->MultiCell(0, 15, $doc_title, 0, 'C', 0, 1);
        
        // Imagen de portada
        if ($cover_image) {
            $pdf->Cell(0, 20, '', 0, 1); // Espacio
            $img_width = 120;
            $x = ($pdf->getPageWidth() - $img_width) / 2;
            
            $pdf->Image($cover_image, $x, $pdf->GetY(), $img_width, 0, '', '', '', false, 300, '', false, false, 0);
        }
        
        // CONTENIDO: cada post en una nueva página
        foreach ($posts as $post) {
            $pdf->AddPage();
            
            // Título del post
            $pdf->SetFont('helvetica', 'B', 18);
            $pdf->MultiCell(0, 10, $post->post_title, 0, 'L', 0, 1);
            
            // Fecha de publicación
            $pdf->SetFont('helvetica', 'I', 10);
            $pdf->SetTextColor(100, 100, 100);
            $date = date_i18n(get_option('date_format'), strtotime($post->post_date));
            $pdf->Cell(0, 8, $date, 0, 1);
            $pdf->Ln(5);
            
            // Restaurar color de texto
            $pdf->SetTextColor(0, 0, 0);
            
            // Contenido del post
            $pdf->SetFont('helvetica', '', 11);
            $processed = $this->process_post_content($post->post_content, $pdf);
            $content = $processed['content'];
            $images = $processed['images'];
            
            // Procesar el contenido por partes, insertando imágenes donde corresponda
            $parts = preg_split('/(\[\[IMAGE_\d+\]\])/', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
            
            foreach ($parts as $part) {
                if (preg_match('/\[\[IMAGE_(\d+)\]\]/', $part, $match)) {
                    // Es un marcador de imagen
                    $placeholder = $match[0];
                    if (isset($images[$placeholder])) {
                        $image_path = $images[$placeholder];
                        
                        // Insertar la imagen
                        $max_width = 170; // mm
                        
                        // Obtener dimensiones de la imagen
                        list($img_width, $img_height) = getimagesize($image_path);
                        $img_ratio = $img_height / $img_width;
                        
                        // Calcular altura proporcional
                        $display_width = $max_width;
                        $display_height = $display_width * $img_ratio;
                        
                        // Verificar si necesitamos cambiar de página
                        $y = $pdf->GetY();
                        if ($y + $display_height > $pdf->getPageHeight() - 25) {
                            $pdf->AddPage();
                        }
                        
                        // Obtener posición actual
                        $current_y = $pdf->GetY();
                        
                        // Centrar la imagen
                        $x = ($pdf->getPageWidth() - $display_width) / 2;
                        
                        // Insertar la imagen en la posición actual
                        $pdf->Image($image_path, $x, $current_y, $display_width, 0, '', '', '', false, 300, '', false, false, 0, false, false, false);
                        
                        // IMPORTANTE: Mover el cursor Y después de la imagen
                        $pdf->SetY($current_y + $display_height + 5); // +5mm de margen después de la imagen
                    }
                } else {
                    // Es contenido HTML normal
                    if (trim($part)) {
                        $pdf->writeHTML($part, true, false, true, false, '');
                    }
                }
            }
        }
        
        // Guardar PDF
        $upload_dir = wp_upload_dir();
        $filename = 'pdf-' . time() . '-' . uniqid() . '.pdf';
        $filepath = $upload_dir['path'] . '/' . $filename;
        
        $pdf->Output($filepath, 'F');
        
        return $filepath;
    }
    
    private function get_post_cover_image($post_id) {
        // Intentar obtener imagen destacada
        if (has_post_thumbnail($post_id)) {
            $thumb_id = get_post_thumbnail_id($post_id);
            $image_path = get_attached_file($thumb_id);
            if (file_exists($image_path)) {
                return $image_path;
            }
        }
        
        // Si no hay imagen destacada, buscar la primera imagen en el contenido
        $post = get_post($post_id);
        $content = $post->post_content;
        
        preg_match_all('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $matches);
        
        if (!empty($matches[1])) {
            $image_url = $matches[1][0];
            $attachment_id = attachment_url_to_postid($image_url);
            
            if ($attachment_id) {
                $image_path = get_attached_file($attachment_id);
                if (file_exists($image_path)) {
                    return $image_path;
                }
            }
            
            // Si no es del sitio, intentar descargar
            $upload_dir = wp_upload_dir();
            $tmp_file = $upload_dir['path'] . '/tmp-' . time() . '.jpg';
            
            $image_data = @file_get_contents($image_url);
            if ($image_data) {
                file_put_contents($tmp_file, $image_data);
                return $tmp_file;
            }
        }
        
        return false;
    }
    
    private function process_post_content($content, $pdf) {
        // Aplicar filtros de WordPress para procesar shortcodes y contenido
        $content = apply_filters('the_content', $content);
        $content = str_replace(']]>', ']]&gt;', $content);
        
        // Procesar imágenes: reemplazar por un marcador y guardar información
        preg_match_all('/<img[^>]+>/i', $content, $img_matches);
        $image_placeholders = array();
        
        foreach ($img_matches[0] as $index => $img_tag) {
            // Extraer src
            if (preg_match('/src=["\']([^"\']+)["\']/i', $img_tag, $src_match)) {
                $image_url = $src_match[1];
                $image_path = null;
                
                // Intentar obtener la ruta local de la imagen
                $attachment_id = attachment_url_to_postid($image_url);
                
                if ($attachment_id) {
                    $image_path = get_attached_file($attachment_id);
                } else {
                    // Si la imagen es externa, intentar descargarla
                    $upload_dir = wp_upload_dir();
                    $temp_file = $upload_dir['path'] . '/temp-' . md5($image_url) . '.jpg';
                    
                    if (!file_exists($temp_file)) {
                        $image_data = @file_get_contents($image_url);
                        if ($image_data) {
                            file_put_contents($temp_file, $image_data);
                            $image_path = $temp_file;
                        }
                    } else {
                        $image_path = $temp_file;
                    }
                }
                
                // Guardar información de la imagen
                if ($image_path && file_exists($image_path)) {
                    $placeholder = '[[IMAGE_' . $index . ']]';
                    $image_placeholders[$placeholder] = $image_path;
                    $content = str_replace($img_tag, '<p>' . $placeholder . '</p>', $content);
                } else {
                    // Si no se puede cargar, eliminar la imagen
                    $content = str_replace($img_tag, '', $content);
                }
            }
        }
        
        // Mejorar el formato HTML para TCPDF
        $content = $this->improve_html_for_pdf($content);
        
        return array('content' => $content, 'images' => $image_placeholders);
    }
    
    private function improve_html_for_pdf($content) {
        // Convertir saltos de línea a <br>
        $content = wpautop($content);
        
        // Ajustar tamaños de fuente pequeños a legibles
        // Convertir rem, em, px pequeños a un tamaño mínimo legible
        $content = preg_replace('/font-size:\s*0\.[1-7](rem|em)/i', 'font-size: 9pt', $content);
        $content = preg_replace('/font-size:\s*[1-7]px/i', 'font-size: 9pt', $content);
        $content = preg_replace('/font-size:\s*0\.[8-9](rem|em)/i', 'font-size: 10pt', $content);
        $content = preg_replace('/font-size:\s*[8-9]px/i', 'font-size: 10pt', $content);
        $content = preg_replace('/font-size:\s*1[0-1]px/i', 'font-size: 11pt', $content);
        
        // También procesar estilos inline de spans y divs pequeños
        $content = preg_replace('/<span([^>]*style="[^"]*font-size:\s*0\.[1-7](rem|em)[^"]*"[^>]*)>/i', '<span$1 style="font-size: 9pt;">', $content);
        $content = preg_replace('/<span([^>]*style="[^"]*font-size:\s*[1-7]px[^"]*"[^>]*)>/i', '<span$1 style="font-size: 9pt;">', $content);
        
        // Asegurar que los enlaces sean clickeables
        $content = preg_replace('/<a\s+href=["\']([^"\']+)["\']([^>]*)>/i', '<a href="$1" style="color: #0066cc; text-decoration: underline;"$2>', $content);
        
        // Mejorar estilos de encabezados
        $content = preg_replace('/<h1([^>]*)>/i', '<h1 style="font-size: 24pt; font-weight: bold; margin-top: 10px; margin-bottom: 5px;"$1>', $content);
        $content = preg_replace('/<h2([^>]*)>/i', '<h2 style="font-size: 20pt; font-weight: bold; margin-top: 8px; margin-bottom: 4px;"$1>', $content);
        $content = preg_replace('/<h3([^>]*)>/i', '<h3 style="font-size: 16pt; font-weight: bold; margin-top: 6px; margin-bottom: 3px;"$1>', $content);
        $content = preg_replace('/<h4([^>]*)>/i', '<h4 style="font-size: 14pt; font-weight: bold; margin-top: 5px; margin-bottom: 2px;"$1>', $content);
        $content = preg_replace('/<h5([^>]*)>/i', '<h5 style="font-size: 12pt; font-weight: bold; margin-top: 4px; margin-bottom: 2px;"$1>', $content);
        $content = preg_replace('/<h6([^>]*)>/i', '<h6 style="font-size: 11pt; font-weight: bold; margin-top: 3px; margin-bottom: 2px;"$1>', $content);
        
        // Mejorar estilos de párrafos
        $content = preg_replace('/<p([^>]*)>/i', '<p style="margin-bottom: 10px; line-height: 1.5;"$1>', $content);
        
        // Asegurar que strong y em mantengan su formato
        $content = preg_replace('/<strong([^>]*)>/i', '<strong style="font-weight: bold;"$1>', $content);
        $content = preg_replace('/<b([^>]*)>/i', '<b style="font-weight: bold;"$1>', $content);
        $content = preg_replace('/<em([^>]*)>/i', '<em style="font-style: italic;"$1>', $content);
        $content = preg_replace('/<i([^>]*)>/i', '<i style="font-style: italic;"$1>', $content);
        
        // Mejorar listas
        $content = preg_replace('/<ul([^>]*)>/i', '<ul style="margin-left: 20px; margin-bottom: 10px;"$1>', $content);
        $content = preg_replace('/<ol([^>]*)>/i', '<ol style="margin-left: 20px; margin-bottom: 10px;"$1>', $content);
        $content = preg_replace('/<li([^>]*)>/i', '<li style="margin-bottom: 5px;"$1>', $content);
        
        // Mejorar blockquotes
        $content = preg_replace('/<blockquote([^>]*)>/i', '<blockquote style="margin: 10px 20px; padding: 10px; border-left: 3px solid #ccc; background-color: #f9f9f9;"$1>', $content);
        
        // Establecer un tamaño mínimo para elementos pequeños (small, etc.)
        $content = preg_replace('/<small([^>]*)>/i', '<small style="font-size: 9pt;"$1>', $content);
        
        return $content;
    }
    
    private function upload_to_media_library($filepath) {
        $filename = basename($filepath);
        $upload_dir = wp_upload_dir();
        
        // Mover archivo al directorio de uploads
        $new_filepath = $upload_dir['path'] . '/' . $filename;
        
        if ($filepath !== $new_filepath) {
            copy($filepath, $new_filepath);
            unlink($filepath);
        }
        
        // Preparar el attachment
        $filetype = wp_check_filetype($filename, null);
        $attachment = array(
            'guid' => $upload_dir['url'] . '/' . $filename,
            'post_mime_type' => $filetype['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        // Insertar el attachment
        $attach_id = wp_insert_attachment($attachment, $new_filepath);
        
        // Generar metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $new_filepath);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        return $attach_id;
    }
}

// Inicializar el plugin
new PDF_Generator();

// Crear archivos CSS y JS al activar el plugin
register_activation_hook(__FILE__, 'pdf_gen_activation');

function pdf_gen_activation() {
    // Crear directorios
    $css_dir = PDF_GEN_PLUGIN_DIR . 'assets/css';
    $js_dir = PDF_GEN_PLUGIN_DIR . 'assets/js';
    
    if (!file_exists($css_dir)) {
        wp_mkdir_p($css_dir);
    }
    if (!file_exists($js_dir)) {
        wp_mkdir_p($js_dir);
    }
    
    // Crear archivo CSS
    $css_content = '.pdf-generator-wrap { max-width: 900px; }
.pdf-gen-container { background: #fff; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 20px; }
#pdf-gen-status .notice { margin-top: 20px; }
.notice-success { border-left-color: #46b450; }
.notice-error { border-left-color: #dc3232; }';
    
    file_put_contents($css_dir . '/admin.css', $css_content);
    
    // Crear archivo JS
    $js_content = "jQuery(document).ready(function(\$) {
    console.log('PDF Generator JS cargado correctamente');
    
    \$('#taxonomy').on('change', function() {
        var taxonomy = \$(this).val();
        console.log('Taxonomía seleccionada:', taxonomy);
        
        \$('#term-row').hide();
        \$('#term').empty().append('<option value=\"\">Seleccionar término...</option>');
        
        if (taxonomy) {
            console.log('Cargando términos para:', taxonomy);
            
            \$.ajax({
                url: pdfGenAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'pdf_gen_get_terms',
                    nonce: pdfGenAjax.nonce,
                    taxonomy: taxonomy
                },
                success: function(response) {
                    console.log('Respuesta:', response);
                    
                    if (response.success) {
                        var termSelect = \$('#term');
                        termSelect.empty().append('<option value=\"\">Todos los términos</option>');
                        
                        if (response.data && response.data.length > 0) {
                            \$.each(response.data, function(i, term) {
                                termSelect.append('<option value=\"' + term.value + '\">' + term.label + '</option>');
                            });
                            console.log('Términos cargados:', response.data.length);
                            \$('#term-row').show();
                        } else {
                            console.log('Sin términos');
                            termSelect.append('<option disabled>No hay términos</option>');
                            \$('#term-row').show();
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', status, error);
                }
            });
        }
    });
    
    \$('#pdf-generator-form').on('submit', function(e) {
        e.preventDefault();
        
        var btn = \$('#generate-pdf-btn');
        var status = \$('#pdf-gen-status');
        var message = \$('#status-message');
        
        btn.prop('disabled', true).text('Generando PDF...');
        status.hide();
        
        \$.ajax({
            url: pdfGenAjax.ajax_url,
            type: 'POST',
            data: \$(this).serialize() + '&action=pdf_gen_generate_pdf&nonce=' + pdfGenAjax.nonce,
            success: function(response) {
                btn.prop('disabled', false).text('Generar PDF');
                
                console.log('Respuesta del servidor:', response);
                
                if (response.success) {
                    status.find('.notice').removeClass('notice-error').addClass('notice-success');
                    message.html(response.data.message + ' <a href=\"' + response.data.url + '\" target=\"_blank\">Ver PDF</a>');
                } else {
                    status.find('.notice').removeClass('notice-success').addClass('notice-error');
                    message.text('Error: ' + (response.data || 'Error desconocido'));
                }
                
                status.show();
            },
            error: function(xhr, status, error) {
                btn.prop('disabled', false).text('Generar PDF');
                status.find('.notice').removeClass('notice-success').addClass('notice-error');
                
                console.error('Error AJAX completo:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    statusCode: xhr.status
                });
                
                var errorMsg = 'Error en la comunicación con el servidor';
                if (xhr.responseText) {
                    try {
                        var errorData = JSON.parse(xhr.responseText);
                        errorMsg += ': ' + (errorData.data || errorData.message || xhr.responseText);
                    } catch(e) {
                        errorMsg += ': ' + xhr.responseText.substring(0, 200);
                    }
                }
                
                message.text(errorMsg);
                status.show();
            }
        });
    });
});";
    
    file_put_contents($js_dir . '/admin.js', $js_content);
}
?>
