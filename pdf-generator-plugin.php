<?php
/**
 * Plugin Name: PDF Generator por Etiqueta
 * Description: Genera un PDF con los posts (o CPT) de una etiqueta seleccionada,
 *              texto introductorio y página final con contenido personalizado (HTML permitido).
 * Version:     1.13
 * Author:      Antonio Cambronero (Blogpocket.com)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Autoload Dompdf (requiere `composer require dompdf/dompdf`)
if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>Falta Dompdf. Ejecuta <code>composer require dompdf/dompdf</code> en la carpeta del plugin.</p></div>';
    });
    return;
}
require_once __DIR__ . '/vendor/autoload.php';
use Dompdf\Dompdf;

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
    $pt           = sanitize_text_field( $_POST['pgeb_post_type']   ?? '' );
    $tx           = sanitize_text_field( $_POST['pgeb_taxonomy']    ?? '' );
    $term         = intval(           $_POST['pgeb_term']         ?? 0 );
    $show_intro   = isset( $_POST['pgeb_show_intro'] )   ? 1 : 0;
    $intro_text   = isset( $_POST['pgeb_intro_text'] )   ? wp_kses_post( $_POST['pgeb_intro_text'] )   : '';
    $show_final   = isset( $_POST['pgeb_show_final'] )   ? 1 : 0;
    $final_text   = isset( $_POST['pgeb_final_text'] )   ? wp_kses_post( $_POST['pgeb_final_text'] )   : '';
    $custom_title = sanitize_text_field( $_POST['pgeb_custom_title'] ?? '' );

    if ( isset( $_POST['pgeb_generate_pdf'] ) && current_user_can('manage_options') ) {
        pgeb_generate_pdf(
            $pt, $tx, $term,
            $show_intro, $intro_text,
            $show_final, $final_text,
            $custom_title
        );
    }

    $post_types = get_post_types(['public' => true], 'objects');
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
            $terms = get_terms([ 'taxonomy' => $tx, 'hide_empty' => false ]);
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
              <input type="checkbox"
                     id="pgeb_show_intro"
                     name="pgeb_show_intro"
                     value="1" <?php checked( $show_intro, 1 ); ?>>
            </td>
          </tr>
          <tr>
            <th><label for="pgeb_intro_text">Texto introductorio</label></th>
            <td>
              <textarea id="pgeb_intro_text"
                        name="pgeb_intro_text"
                        rows="4"
                        class="large-text"><?php echo esc_textarea( $intro_text ); ?></textarea>
            </td>
          </tr>

          <tr>
            <th><label for="pgeb_show_final">Mostrar texto final</label></th>
            <td>
              <input type="checkbox"
                     id="pgeb_show_final"
                     name="pgeb_show_final"
                     value="1" <?php checked( $show_final, 1 ); ?>>
            </td>
          </tr>
          <tr>
            <th><label for="pgeb_final_text">Texto página final</label></th>
            <td>
              <textarea id="pgeb_final_text"
                        name="pgeb_final_text"
                        rows="4"
                        class="large-text"><?php echo esc_textarea( $final_text ); ?></textarea>
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

// Genera el PDF
function pgeb_generate_pdf(
    $post_type,
    $taxonomy,
    $term_id,
    $show_intro,
    $intro_text,
    $show_final,
    $final_text,
    $custom_title
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
    $html  = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>';
    $html .= 'body{font-family:sans-serif;margin:40px;}';
    $html .= 'img{max-width:100%;height:auto;}';
    $html .= 'pre, code{white-space:pre-wrap;word-wrap:break-word;}';
    $html .= '.cover{text-align:center;margin-top:100px;page-break-after:always;}';
    $html .= 'h1.title{font-size:40px;}';
    $html .= 'p.description{font-size:16px;margin-bottom:20px;}';
    $html .= 'h1.post-title{page-break-before:always;font-size:30px;margin-top:0;}';
    $html .= '</style></head><body>';

    // Portada
    $html .= '<div class="cover"><h1 class="title">' . esc_html( $title ) . '</h1>';
    if ( $term->description ) {
        $clean = wp_strip_all_tags( $term->description );
        $html .= '<p class="description">' . esc_html( $clean ) . '</p>';
    }
    $first = $posts[0];
    if ( has_post_thumbnail( $first->ID ) ) {
        $img = get_the_post_thumbnail_url( $first->ID, 'full' );
    } elseif ( preg_match( '/<img[^>]+src=[\'"]([^\'"]+)[\'"]/', $first->post_content, $m ) ) {
        $img = $m[1];
    } else {
        $img = '';
    }
    if ( $img ) {
        if ( $custom_title ) {
            $html .= '<p><img src="' . esc_url( $img ) . '"/></p>';
        } else {
            $html .= '<p><a href="https://lanzatu.blog/microblog/feed" target="_blank"><img src="' . esc_url( $img ) . '"/></a></p>';
        }
    }
    $html .= '</div>';

    // Texto introductorio (HTML permitido)
    if ( $show_intro && $intro_text ) {
        $html .= '<div style="margin-top:20px;">' . wp_kses_post( $intro_text ) . '</div>';
    }

    // Contenido de posts
    foreach ( $posts as $post ) {
        setup_postdata( $post );
        $html .= '<h1 class="post-title">' . get_the_title( $post ) . '</h1>';
        $html .= apply_filters( 'the_content', $post->post_content );
    }
    wp_reset_postdata();

    // Texto página final (HTML permitido)
    if ( $show_final && $final_text ) {
        $html .= '<div style="page-break-before:always;margin:40px;">' . wp_kses_post( $final_text ) . '</div>';
    }

    $html .= '</body></html>';

    // Renderizado con Dompdf
    $dompdf = new Dompdf();
    $dompdf->set_option( 'isRemoteEnabled', true );
    $dompdf->loadHtml( $html );
    $dompdf->setPaper( 'A4', 'portrait' );
    $dompdf->render();

    // Numeración de páginas
    $canvas = $dompdf->get_canvas();
    $font   = $dompdf->getFontMetrics()->get_font( 'Helvetica', 'normal' );
    $size   = 10;
    $w      = $canvas->get_width();
    $h      = $canvas->get_height();
    $canvas->page_text( $w/2, $h - 30, 'Página {PAGE_NUM} de {PAGE_COUNT}', $font, $size );

    // Guardar PDF
    $upload_dir = wp_upload_dir();
    $filename   = sprintf( 'pdf-%s-%s-%s.pdf', $post_type, $term->slug, date( 'YmdHis', current_time( 'timestamp' ) ) );
    $filepath   = trailingslashit( $upload_dir['basedir'] ) . $filename;
    file_put_contents( $filepath, $dompdf->output() );

    // Subir a Biblioteca de Medios
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
