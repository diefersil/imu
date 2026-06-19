<?php

/* ============================================================
 * 01. CARREGAMENTO DE ESTILOS DO TEMA
 * ============================================================ */

function my_theme_enqueue_styles() {
	wp_enqueue_style( 'style', get_stylesheet_uri() );
}
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );


/* ============================================================
 * 02. REGISTRO DE MENUS
 * ============================================================ */

register_nav_menus(
	array(
		'primary' => esc_html__( 'Primary menu', 'twentytwentyone' ),
		'footer'  => esc_html__( 'Secondary menu', 'twentytwentyone' ),
	)
);


/* ============================================================
 * 03. SUPORTE A IMAGEM DESTACADA
 * ============================================================ */

add_theme_support( 'post-thumbnails', array( 'post', 'imoveis' ) );


/* ============================================================
 * 04. FILTRO OPCIONAL - PA DISPLAY CONDITIONS
 * ============================================================ */

// add_filter( 'pa_display_conditions_values', function( $apply ) {
// 	return false;
// });


/* ============================================================
 * 05. GOOGLE ANALYTICS - TAG G-D9DED3C7TD
 * ============================================================ */

add_action( 'wp_head', 'ga' );

function ga() {
	?>
	<!-- Google tag (gtag.js) -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=G-D9DED3C7TD"></script>
	<script>
		window.dataLayer = window.dataLayer || [];

		function gtag() {
			dataLayer.push(arguments);
		}

		gtag('js', new Date());
		gtag('config', 'G-D9DED3C7TD');
	</script>
	<?php
}


/* ============================================================
 * 06. GOOGLE FONTS
 * ============================================================ */

add_action( 'wp_footer', 'google_font' );

function google_font() {
	?>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&display=swap" rel="stylesheet">
	<?php
}


/* ============================================================
 * 07. GOOGLE ADSENSE
 * ============================================================ */

add_action( 'wp_head', 'adsense' );

function adsense() {
	?>
	<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-0370842058394618"
		crossorigin="anonymous"></script>
	<?php
}


/* ============================================================
 * 08. JET SEARCH - DEFINE ID NO CAMPO DE BUSCA
 * ============================================================ */

add_action( 'wp_footer', 'set_id_input_search' );

function set_id_input_search() {
	?>
	<script>
		const searchInputId = document.querySelector(".jet-search-filter__input");

		if (searchInputId) {
			searchInputId.setAttribute('id', 'search-ac');
		}
	</script>
	<?php
}


/* ============================================================
 * 09. REMOVE CIDADE DO TEXTO - DESATIVADO
 * ============================================================ */

function remove_city() {
	?>
	<script>
		const cityLinks = document.querySelectorAll('.location a');
		const isHome = document.body.classList.contains('home');

		if (cityLinks.length) {
			cityLinks.forEach(v => {
				let removeCity = v.innerText.replace(/, Unaí - MG| - Unaí - MG| - Unaí, MG/gi, '');

				if (isHome && removeCity.length > 35) {
					v.innerText = removeCity.substr(0, 35) + '...';
				} else {
					v.innerText = removeCity;
				}
			});
		}

		const singleCity = document.querySelectorAll('.pf-body')[0];

		if (singleCity) {
			singleCity.innerHTML = singleCity.innerHTML.replace(/, Unaí - MG| - Unaí - MG| - Unaí, MG/gi, '');
		}
	</script>
	<?php
}

// add_action( 'wp_footer', 'remove_city' );


/* ============================================================
 * 10. CORTA TÍTULO DA EMPRESA - DESATIVADO
 * ============================================================ */

function corta_titulo() {
	?>
	<script>
		const titulo = document.querySelectorAll('.empresa a');

		titulo.forEach(v => {
			if (v.innerText.length >= 32) {
				v.innerText = v.innerText.substr(0, 32) + '...';
			}
		});
	</script>
	<?php
}

// add_action( 'wp_footer', 'corta_titulo' );


/* ============================================================
 * 11. FUNÇÃO JS PARA CORTAR TEXTO - DESATIVADO
 * ============================================================ */

function cut_str() {
	?>
	<script>
		function cut_str(v, number_chars) {
			if (v.innerText.length >= number_chars) {
				v.innerText = v.innerText.substr(0, number_chars) + '...';
			}
		}
	</script>
	<?php
}

// add_action( 'wp_footer', 'cut_str' );


/* ============================================================
 * 12. SUBSTITUI TEXTOS NA LISTAGEM
 * ============================================================ */

add_action( 'wp_footer', 'replace_text' );

function replace_text() {
	?>
	<script>
		document.querySelectorAll('.location a').forEach(v => {
			v.textContent = v.textContent.replace(/, Unaí - MG| - Unaí - MG| - Unaí, MG/gi, '');
		});

		document.querySelectorAll('.location span').forEach(v => {
			v.textContent = v.textContent.replace(/, Unaí - MG| - Unaí - MG| - Unaí, MG/gi, '');
		});

		document.querySelectorAll('.site span').forEach(v => {
			v.textContent = v.textContent.replace(/https:\/\/|http:\/\/|www./g, '');
		});

		document.querySelectorAll('.insta span').forEach(v => {
			v.textContent = '@' + v.textContent.replace(/https:|http:|www.|instagram.com|\//g, '');
		});
	</script>
	<?php
}


/* ============================================================
 * 13. BOTÃO ENTER NO CAMPO DE BUSCA
 * ============================================================ */

add_action( 'wp_footer', 'set_apply_button' );

function set_apply_button() {
	?>
	<script>
		const searchInput = document.querySelector('.jet-search-filter__input');
		const applyButton = document.querySelector('.apply-filters__button');

		if (searchInput && applyButton) {
			searchInput.addEventListener("keyup", function(event) {
				if (event.keyCode === 13) {
					event.preventDefault();
					applyButton.click();
				}
			});
		}
	</script>
	<?php
}


/* ============================================================
 * 14. ESCONDE BLOCO SOBRE SE ESTIVER VAZIO
 * ============================================================ */

add_action( 'wp_footer', 'single_display_none' );

function single_display_none() {
	?>
	<script>
		const aboutBox = document.querySelector('.about');
		const aboutText = document.querySelector('.about p');

		if (aboutBox && (!aboutText || aboutText.textContent.trim() === '')) {
			aboutBox.style.display = 'none';
		}
	</script>
	<?php
}


/* ============================================================
 * 15. ADICIONA ÍCONE DE LUPA NO CAMPO DE BUSCA
 * ============================================================ */

add_action( 'wp_footer', 'search_add_lupa' );

function search_add_lupa() {
	?>
	<script>
		const searchWrapper = document.querySelector('.jet-search-filter__input-wrapper');

		if (searchWrapper) {
			searchWrapper.insertAdjacentHTML(
				'beforeend',
				"<img src='https://guiaunai.com.br/wp-content/uploads/2025/09/icon-search-lupa.png' style='width:16px; position:absolute; right:15px; top:30%;'>"
			);
		}
	</script>
	<?php
}


/* ============================================================
 * 16. INCLUDE DO ARQUIVO FUNCTIONS-AC
 * ============================================================ */

include get_template_directory() . '/functions-ac.php';

add_action( 'wp_footer', 'ac' );


/* ============================================================
 * 17. GOOGLE ANALYTICS / TAG MANAGER - TAG G-XX83SR8MC6
 * ============================================================ */

add_action( 'wp_head', 'tag_manager' );

function tag_manager() {
	?>
	<!-- Google tag (gtag.js) -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=G-XX83SR8MC6"></script>
	<script>
		window.dataLayer = window.dataLayer || [];

		function gtag() {
			dataLayer.push(arguments);
		}

		gtag('js', new Date());
		gtag('config', 'G-XX83SR8MC6');
	</script>
	<?php
}


/* ============================================================
 * 18. SHORTCODE PARA PEGAR STRING DE BUSCA
 * ============================================================ */

add_shortcode( 'search_string', 'get_search_string' );

function get_search_string() {
	global $post;

	if ( $post && (int) $post->ID === 672 && isset( $_GET['_s'] ) ) {
		return sanitize_text_field( wp_unslash( $_GET['_s'] ) );
	}

	return '';
}


/* ============================================================
 * 19. JETENGINE - DEFINE PRIMEIRA IMAGEM DA GALERIA COMO THUMBNAIL
 * ============================================================ */

add_action( 'save_post', 'jetengine_set_first_gallery_image_as_thumbnail', 20, 3 );

function jetengine_set_first_gallery_image_as_thumbnail( $post_id, $post, $update ) {

	// Evita autosave.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Evita revisões.
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	// Opcional: limitar por post type.
	// if ( $post->post_type !== 'seu_post_type' ) {
	// 	return;
	// }

	// Slug do campo gallery do JetEngine.
	$gallery_field = 'galeria';

	// Pega a galeria.
	$gallery = get_post_meta( $post_id, $gallery_field, true );

	if ( empty( $gallery ) ) {
		return;
	}

	$attachment_id = 0;

	// Caso seja array.
	if ( is_array( $gallery ) ) {

		$first_image = reset( $gallery );

		// Formato: array com ID.
		if ( is_array( $first_image ) && isset( $first_image['id'] ) ) {
			$attachment_id = intval( $first_image['id'] );

		// Formato: array com valor direto.
		} elseif ( is_numeric( $first_image ) ) {
			$attachment_id = intval( $first_image );

		// Formato: array com URL.
		} elseif ( is_string( $first_image ) ) {
			$attachment_id = attachment_url_to_postid( $first_image );
		}

	// Caso seja string: "123,456,789".
	} elseif ( is_string( $gallery ) ) {

		$images = explode( ',', $gallery );
		$first_image = trim( reset( $images ) );

		if ( is_numeric( $first_image ) ) {
			$attachment_id = intval( $first_image );
		} else {
			$attachment_id = attachment_url_to_postid( $first_image );
		}
	}

	// Define como imagem destacada.
	if ( $attachment_id > 0 ) {
		set_post_thumbnail( $post_id, $attachment_id );
	}
}


/* ============================================================
 * 20. ADMIN - ADICIONA COLUNA DE THUMBNAIL EM PRODUTOS
 * ============================================================ */

add_filter( 'manage_imoveis_posts_columns', function ( $columns ) {

	$new_columns = array();

	foreach ( $columns as $key => $label ) {
		$new_columns[ $key ] = $label;

		// Adiciona depois do checkbox.
		if ( $key === 'cb' ) {
			$new_columns['thumbnail'] = 'Imagem';
		}
	}

	return $new_columns;
} );


/* ============================================================
 * 21. ADMIN - EXIBE IMAGEM DESTACADA NA COLUNA
 * ============================================================ */

add_action( 'manage_imoveis_posts_custom_column', function ( $column, $post_id ) {

	if ( $column === 'thumbnail' ) {

		if ( has_post_thumbnail( $post_id ) ) {
			echo get_the_post_thumbnail(
				$post_id,
				array( 60, 60 ),
				array(
					'style' => 'width:60px;height:60px;object-fit:cover;border-radius:6px;',
				)
			);
		} else {
			echo '<span style="color:#999;">Sem imagem</span>';
		}
	}

}, 10, 2 );


/* ============================================================
 * 22. ADMIN - DEFINE LARGURA DA COLUNA DE THUMBNAIL
 * ============================================================ */

add_action( 'admin_head', function () {
	echo '
	<style>
		.column-thumbnail {
			width: 80px;
			text-align: center;
		}
	</style>
	';
} );


/* ============================================================
 * 23. JETENGINE - MACRO PERSONALIZADA POR CATEGORIA GET
 * ============================================================ */

/*
add_action( 'jet-engine/register-macros', function() {

	if ( ! class_exists( 'Jet_Engine_Base_Macros' ) ) {
		return;
	}

	class Tipo_Por_Categoria_GET_Macro extends Jet_Engine_Base_Macros {

		public function macros_tag() {
			return 'tipo_por_categoria_get';
		}

		public function macros_name() {
			return 'Tipo por Categoria GET';
		}

		public function macros_callback( $args = array() ) {

			$categoria = isset( $_GET['categoria'] ) ? absint( $_GET['categoria'] ) : 0;

			$mapa = array(
				1218 => 'carros',
				1220 => 'motos',
				1221 => 'caminhoes',
				1222 => 'maquinas',
			);

			return isset( $mapa[ $categoria ] ) ? $mapa[ $categoria ] : '';
		}
	}

	new Tipo_Por_Categoria_GET_Macro();

} );
*/


// Shortcode genérico para mostrar termo de qualquer taxonomia no loop
// Uso: [taxonomia_loop tax="cidade"]
function shortcode_taxonomia_loop($atts) {

    $atts = shortcode_atts(array(
        'tax'       => '',
        'campo'     => 'name', // name ou slug
        'separador' => ', ',
        'todos'     => 'nao',  // sim ou nao
        'uppercase' => 'nao',  // sim ou nao
    ), $atts);

    $post_id = get_the_ID();

    if (!$post_id || empty($atts['tax'])) {
        return '';
    }

    $taxonomy = sanitize_key($atts['tax']);

    $terms = get_the_terms($post_id, $taxonomy);

    if (empty($terms) || is_wp_error($terms)) {
        return '';
    }

    $resultado = array();

    foreach ($terms as $term) {

        if ($atts['campo'] === 'slug') {
            $valor = $term->slug;
        } else {
            $valor = $term->name;
        }

        if ($atts['uppercase'] === 'sim') {
            $valor = mb_strtoupper($valor, 'UTF-8');
        }

        $resultado[] = esc_html($valor);

        // Se não quiser todos, pega só o primeiro termo
        if ($atts['todos'] !== 'sim') {
            break;
        }
    }

    return implode($atts['separador'], $resultado);
}
add_shortcode('taxonomia_loop', 'shortcode_taxonomia_loop');