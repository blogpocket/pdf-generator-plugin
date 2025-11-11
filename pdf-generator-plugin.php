<?php
/**
 * Plugin Name: PDF Generator por Etiqueta
 * Description: Genera un PDF con los posts (o CPT) de una etiqueta seleccionada, con título opcional,
 *              texto introductorio y página final personalizados (HTML permitido). Incluye interruptor para excluir todas las imágenes.
 * Version:     1.14
 * Author:      Antonio
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Autoload Dompdf (requiere `composer require dompdf/dompdf`)
if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>Falta Dompdf. Ejecuta <code>composer require dompdf/dompdf</code> en la carpeta del plugin.</p></div>';
    });
    return;
}
require_once __DIR__ . '/vendor/autoload.php';
use Dompdf\Dompdf;

/**
 * Elimina TODAS las imágenes de un bloque HTML (<img ...>)
 */
function pgeb_remove_images( $html ) {
    return preg_replace('/<img[^>]*>/i', '', (string) $html);
}

// Menú de administración
add_action('admin_menu', function() {
    add_menu_page(
        'PDF por Etiqueta',
        'PDF por Etiqueta',
        'manage_options',
        'pgeb_pdf_settings',
        'pgeb_settings_page',
        'dashicons-media-document',
        80
    );
});

// Página de ajustes
function pgeb_settings_page() {
    $pt             = sanitize_text_field( $_POST['pgeb_post_type']   ?? '' );
    $tx             = sanitize_text_field( $_POST['pgeb_taxonomy']    ?? '' );
    $term           = intval(           $_POST['pgeb_term']         ?? 0 );
    $custom_title   = sanitize_text_field( $_POST['pgeb_custom_title'] ?? '' );

    $show_intro     = isset( $_POST['pgeb_show_intro'] ) ? 1 : 0;
    $intro_text     = isset( $_POST['pgeb_intro_text'] ) ? wp_kses_post( $_POST['pgeb_intro_text'] ) : '';

    $show_final     = isset( $_POST['pgeb_show_final'] ) ? 1 : 0;
    $final_text     = isset( $_POST['pgeb_final_text'] ) ? wp_kses_post( $_POST['pgeb_final_text'] ) : '';

    // Interruptor: excluir imágenes
    $exclude_images = isset( $_POST['pgeb_exclude_images'] ) ? 1 : 0;

    if ( isset( $_POST['pgeb_generate_pdf'] ) && current_user_can( 'manage_options' ) ) {
        pgeb_generate_pdf(
            $pt, $tx, $term,
            $custom_title,
            $show_intro, $intro_text,
            $show_final, $final_text,
            $exclude_images
        );
    }

    $post_types = get_post_types( [ 'public' => true ], 'objects' );
    ?>
    <div class="wrap">
      <h1>Generar PDF por Etiqueta</h1>
      <form method="post">
        <table class="form-table">

          <tr>
            <th><label for="pgeb_post_type">Tipo de contenido</label></th>
            <td>
              <select id="pgeb_post_type" name="pgeb_post_type" onchange="this.form.submit()">
                <option value="">-- Selecciona --</option>
                <?php foreach ( $post_types as $o ) : ?>
                    <option value="<?php echo esc_attr( $o->name ); ?>" <?php selected( $pt, $o->name ); ?>>
                        <?php echo esc_html( $o->labels->singular_name ); ?>
                    </option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>

          <?php if ( $pt ) :
            $taxes = get_object_taxonomies( $pt, 'objects' );
          ?>
          <tr>
            <th><label for="pgeb_taxonomy">Taxonomía</label></th>
            <td>
              <select id="pgeb_taxonomy" name="pgeb_taxonomy" onchange="this.form.submit()">
                <option value="">-- Selecciona --</option>
                <?php foreach ( $taxes as $t ) : ?>
                    <option value="<?php echo esc_attr( $t->name ); ?>" <?php selected( $tx, $t->name ); ?>>
                        <?php echo esc_html( $t->labels->name ); ?>
                    </option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>
          <?php endif; ?>

          <?php if ( $tx ) :
            $terms = get_terms( [ 'taxonomy' => $tx, 'hide_empty' => false ] );
          ?>
          <tr>
            <th><label for="pgeb_term">Etiqueta</label></th>
            <td>
              <select id="pgeb_term" name="pgeb_term" onchange="this.form.submit()">
                <option value="0">-- Selecciona --</option>
                <?php foreach ( $terms as $t ) : ?>
                    <option value="<?php echo esc_attr( $t->term_id ); ?>" <?php selected( $term, $t->term_id ); ?>>
                        <?php echo esc_html( $t->name ); ?>
                    </option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>
          <?php endif; ?>

          <?php if ( $pt && $tx && $term ) : ?>
          <tr>
            <th><label for="pgeb_custom_title">Título del PDF</label></th>
            <td>
              <input type="text"
                     id="pgeb_custom_title"
                     name="pgeb_custom_title"
                     class="regular-text"
                     value="<?php echo esc_attr( $custom_title ); ?>"
                     placeholder="Opcional: título personalizado">
            </td>
          </tr>

          <tr>
            <th><label for="pgeb_show_intro">Mostrar texto introductorio</label></th>
            <td>
              <input type="checkbox" id="pgeb_show_intro" name="pgeb_show_intro" value="1" <?php checked( $show_intro, 1 ); ?>>
            </td>
          </tr>
          <tr>
            <th><label for="pgeb_intro_text">Texto introductorio (HTML permitido)</label></th>
            <td>
              <textarea id="pgeb_intro_text" name="pgeb_intro_text" rows="4" class="large-text"><?php echo esc_textarea( $intro_text ); ?></textarea>
            </td>
          </tr>

          <tr>
            <th><label for="pgeb_show_final">Mostrar texto final</label></th>
            <td>
              <input type="checkbox" id="pgeb_show_final" name="pgeb_show_final" value="1" <?php checked( $show_final, 1 ); ?>>
            </td>
          </tr>
          <tr>
            <th><label for="pgeb_final_text">Texto página final (HTML permitido)</label></th>
            <td>
              <textarea id="pgeb_final_text" name="pgeb_final_text" rows="4" class="large-text"><?php echo esc_textarea( $final_text ); ?></textarea>
            </td>
          </tr>

          <tr>
            <th><label for="pgeb_exclude_images">Excluir todas las imágenes</label></th>
            <td>
              <label>
                <input type="checkbox" id="pgeb_exclude_images" name="pgeb_exclude_images" value="1" <?php checked( $exclude_images, 1 ); ?>>
                No incluir ninguna imagen (portada, intro/final y posts)
              </label>
            </td>
          </tr>
          <?php endif; ?>

        </table>

        <?php if ( $pt && $tx && $term ) : ?>
          <p class="submit">
            <button type="submit" name="pgeb_generate_pdf" class="button button-primary">Generar PDF</button>
          </p>
        <?php endif; ?>

      </form>
    </div>
    <?php
}

// Generación del PDF (con interruptor para excluir imágenes)
function pgeb_generate_pdf(
    $post_type,
    $taxonomy,
    $term_id,
    $custom_title,
    $show_intro, $intro_text,
    $show_final, $final_text,
    $exclude_images
) {
    $term = get_term( $term_id, $taxonomy );
    if ( ! $term || is_wp_error( $term ) ) {
        echo '<div class="notice notice-error"><p>Etiqueta no válida.</p></div>';
        return;
    }

    $posts = get_posts([
        'post_type'      => $post_type,
        'tax_query'      => [[ 'taxonomy' => $taxonomy, 'terms' => $term_id ]],
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'ASC',
    ]);
    if ( empty( $posts ) ) {
        echo '<div class="notice notice-warning"><p>No hay posts para esta etiqueta.</p></div>';
        return;
    }

    $title = $custom_title ?: $term->name;

    // Estilos base
    $html  = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>';
    $html .= 'body{font-family:sans-serif;margin:40px;}';
    $html .= 'pre, code{white-space:pre-wrap;word-wrap:break-word;}';
    $html .= '.cover{text-align:center;margin-top:100px;page-break-after:always;}';
    $html .= 'h1.title{font-size:24px;}';
    $html .= 'p.description{font-size:16px;margin-bottom:20px;}';
    $html .= 'h1.post-title{page-break-before:always;font-size:20px;margin-top:0;}';
    $html .= '</style></head><body>';

    // Portada (imagen sólo si NO se excluyen imágenes)
    $html .= '<div class="cover"><h1 class="title">' . esc_html( $title ) . '</h1>';
    if ( $term->description ) {
        $html .= '<p class="description">' . esc_html( wp_strip_all_tags( $term->description ) ) . '</p>';
    }

    if ( ! $exclude_images ) {
        $first = $posts[0];
        if ( has_post_thumbnail( $first->ID ) ) {
            $cover_img = get_the_post_thumbnail_url( $first->ID, 'full' );
        } elseif ( preg_match( '/<img[^>]+src=[\'"]([^\'"]+)[\'"]/', $first->post_content, $m ) ) {
            $cover_img = $m[1];
        } else {
            $cover_img = '';
        }
        if ( $cover_img ) {
            if ( $custom_title ) {
                $html .= '<p><img src="' . esc_url( $cover_img ) . '"/></p>';
            } else {
                $html .= '<p><a href="https://lanzatu.blog/microblog/feed" target="_blank"><img src="' . esc_url( $cover_img ) . '"/></a></p>';
            }
        }
    }
    $html .= '</div>';

    // Intro (HTML; si se excluyen imágenes, limpiamos <img>)
    if ( $show_intro && $intro_text ) {
        $intro_block = $exclude_images ? pgeb_remove_images( $intro_text ) : $intro_text;
        $html .= '<div style="margin-top:20px;">' . wp_kses_post( $intro_block ) . '</div>';
    }

    // Posts
    foreach ( $posts as $post ) {
        setup_postdata( $post );
        $content = apply_filters( 'the_content', $post->post_content );
        if ( $exclude_images ) {
            $content = pgeb_remove_images( $content );
        }
        $html .= '<h1 class="post-title">' . get_the_title( $post ) . '</h1>';
        $html .= $content;
    }
    wp_reset_postdata();

    // Final (HTML; si se excluyen imágenes, limpiamos <img>)
    if ( $show_final && $final_text ) {
        $final_block = $exclude_images ? pgeb_remove_images( $final_text ) : $final_text;
        $html .= '<div style="page-break-before:always;margin:40px;">' . wp_kses_post( $final_block ) . '</div>';
    }

    $html .= '</body></html>';

    // Dompdf
    $dompdf = new Dompdf();
    $dompdf->set_option( 'isRemoteEnabled', true );
    $dompdf->loadHtml( $html );
    $dompdf->setPaper( 'A4', 'portrait' );
    $dompdf->render();

    // Numeración
    $canvas = $dompdf->get_canvas();
    $font   = $dompdf->getFontMetrics()->get_font( 'Helvetica', 'normal' );
    $size   = 10;
    $w      = $canvas->get_width();
    $h      = $canvas->get_height();
    $canvas->page_text( $w/2, $h - 30, 'Página {PAGE_NUM} de {PAGE_COUNT}', $font, $size );

    // Guardar PDF en Medios
    $upload_dir = wp_upload_dir();
    $filename   = sprintf( 'pdf-%s-%s-%s.pdf', $post_type, $term->slug, date( 'YmdHis', current_time( 'timestamp' ) ) );
    $filepath   = trailingslashit( $upload_dir['basedir'] ) . $filename;
    file_put_contents( $filepath, $dompdf->output() );

    $filetype   = wp_check_filetype( $filename, null );
    $attachment = [
        'post_mime_type' => $filetype['type'],
        'post_title'     => sanitize_file_name( 'PDF ' . $title ),
        'post_status'    => 'inherit',
    ];
    $attach_id  = wp_insert_attachment( $attachment, $filepath );
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $meta       = wp_generate_attachment_metadata( $attach_id, $filepath );
    wp_update_attachment_metadata( $attach_id, $meta );

    $url = trailingslashit( $upload_dir['url'] ) . $filename;
    echo '<div class="notice notice-success"><p>PDF generado correctamente: <a href="' . esc_url( $url ) . '" target="_blank">Ver PDF</a></p></div>';
}
