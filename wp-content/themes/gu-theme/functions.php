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


function ga() {
	?>
	<!-- Google tag (gtag.js) -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=G-YSCEVGGKC3"></script>
	<script>
		window.dataLayer = window.dataLayer || [];

		function gtag() {
			dataLayer.push(arguments);
		}

		gtag('js', new Date());
		gtag('config', 'G-YSCEVGGKC3');
	</script>
	<?php
}
add_action( 'wp_head', 'ga' );


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
 * 08. JET SEARCH - CAMPO DE BUSCA
 *     08.1 Define ID no campo visível
 *     08.2 Adiciona ícone de lupa no campo visível
 *     08.3 Enter redireciona para /busca/?_s=
 * ============================================================ */

/* ------------------------------------------------------------
 * 08.1 DEFINE ID NO CAMPO DE BUSCA VISÍVEL
 * ------------------------------------------------------------ */

add_action( 'wp_footer', 'set_id_input_search' );

function set_id_input_search() {
	?>
	<script>
	(function () {
		const searchInputId = Array.from(
			document.querySelectorAll('.jet-search-filter__input')
		).find(function(input) {
			return input.offsetParent !== null;
		});

		if (searchInputId) {
			searchInputId.setAttribute('id', 'search-ac');
		}
	})();
	</script>
	<?php
}

/* ------------------------------------------------------------
 * 08.2 ADICIONA ÍCONE DE LUPA NO CAMPO VISÍVEL
 * ------------------------------------------------------------ */

add_action( 'wp_footer', 'search_add_lupa' );

function search_add_lupa() {
	?>
	<script>
	(function () {
		const searchInput = Array.from(
			document.querySelectorAll('.jet-search-filter__input')
		).find(function(input) {
			return input.offsetParent !== null;
		});

		if (!searchInput) {
			return;
		}

		const searchWrapper = searchInput.closest('.jet-search-filter__input-wrapper');

		if (!searchWrapper) {
			return;
		}

		if (searchWrapper.querySelector('.imu-search-lupa')) {
			return;
		}

		searchWrapper.style.position = 'relative';

		searchWrapper.insertAdjacentHTML(
			'beforeend',
			"<img class='imu-search-lupa' src='https://guiaunai.com.br/wp-content/uploads/2025/09/icon-search-lupa.png' style='width:16px;height:16px;position:absolute;right:15px;top:50%;transform:translateY(-50%);pointer-events:none;'>"
		);
	})();
	</script>
	<?php
}



/* ------------------------------------------------------------
 * 08.3 ENTER REDIRECIONA PARA A PÁGINA DE BUSCA
 * ------------------------------------------------------------ */

add_action( 'wp_footer', 'imu_search_redirect' );

function imu_search_redirect() {
    ?>
    <script>
    document.addEventListener('keydown', function(e) {

        if (e.key !== 'Enter') {
            return;
        }

        const input = e.target.closest('#search-ac');

        if (!input) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        const termo = input.value.trim();

        if (!termo) {
            return;
        }

        window.location.href =
            'https://imoveisunai.com.br/busca/?_s=' +
            encodeURIComponent(termo);

    }, true);
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


// =========================================================
// WHATSAPP DOS IMÓVEIS
// =========================================================

require_once get_stylesheet_directory()
    . '/inc/whatsapp-imovel.php';


/* ============================================================
 * 13. FUNÇÃO JS PARA CORTAR TEXTO - DESATIVADO
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
 * 14. SUBSTITUI TEXTOS NA LISTAGEM
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
 * 15. ESCONDE BLOCO SOBRE SE ESTIVER VAZIO
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
 * 16. INCLUDE DO ARQUIVO FUNCTIONS-AC
 * ============================================================ */

include get_template_directory() . '/functions-ac.php';

add_action( 'wp_footer', 'ac' );


/* ============================================================
 * 17. GOOGLE ANALYTICS / TAG MANAGER - TAG G-XX83SR8MC6
 * ============================================================ */

//add_action( 'wp_head', 'tag_manager' );

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
 * 23. LIMPA SEPARADORES DAS ETIQUETAS NO LOOP
 * ============================================================ */

add_action('wp_footer', 'limpar_loop_etiquetas');

function limpar_loop_etiquetas() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {

        document.querySelectorAll('.loop-etiquetas').forEach(function (el) {

            var walker = document.createTreeWalker(
                el,
                NodeFilter.SHOW_TEXT,
                null,
                false
            );

            var node;

            while (node = walker.nextNode()) {

                var texto = node.nodeValue;

                // Ignora nós vazios, quebras de linha ou espaços soltos
                if (!texto.trim()) {
                    continue;
                }

                // Remove do início: "| | ", "| " ou espaço
                node.nodeValue = texto.replace(/^\s*(?:\|\s*\|\s*|\|\s*)?/, '');

                // Para depois de limpar o primeiro texto real
                break;
            }

        });

    });
    </script>
    <?php
}

/* ============================================================
 * 24. SHORTCODE GENÉRICO DE TAXONOMIA NO LOOP
 * ============================================================ */

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

/* ============================================================
 * 25. ATIVAR EDITOR NO CPT IMÓVEIS
 * ============================================================ */

/**
 * Força o editor no CPT imoveis
 */
function imu_forcar_editor_imoveis() {

    add_post_type_support( 'imoveis', 'editor' );
    add_post_type_support( 'imoveis', 'author' );
}

add_action( 'admin_init', 'imu_forcar_editor_imoveis', 999 );


/* ============================================================
 * 26. REDIRECIONAR TAXONOMIAS PARA BUSCA
 * ============================================================ */


function imu_redirect_taxonomy_filter_to_busca() {

    // Só executa nas taxonomias desejadas
    if ( ! is_tax( array( 'categoria', 'negociacao', 'cidade' ) ) ) {
        return;
    }

    // Só redireciona se o JetSmartFilters estiver ativo na URL
    if ( empty( $_GET['jsf'] ) ) {
        return;
    }

    $jsf = sanitize_text_field(
        wp_unslash( $_GET['jsf'] )
    );

    // Confirma que é o filtro do JetEngine
    if ( $jsf !== 'jet-engine:listing-filter' ) {
        return;
    }

    // Precisa existir o parâmetro tax
    if ( empty( $_GET['tax'] ) ) {
        return;
    }

    $tax_filter = sanitize_text_field(
        wp_unslash( $_GET['tax'] )
    );

    /*
     * Exemplo:
     *
     * categoria:1398,1392
     * cidade:123
     * negociacao:456
     */

    $url = add_query_arg(
        array(
            'tax' => $tax_filter,
        ),
        home_url( '/busca/' )
    );

    wp_safe_redirect( $url, 302 );
    exit;
}

add_action(
    'template_redirect',
    'imu_redirect_taxonomy_filter_to_busca',
    1
);

/* ------------------------------------------------------------
 * META DESCRIPTION E OG DESCRIPTION - SINGLE DE IMÓVEIS
 * ------------------------------------------------------------ */

add_action( 'wp_head', 'imu_meta_description_imoveis', 5 );

function imu_meta_description_imoveis() {

    /* ------------------------------------------------------------
     * SOMENTE SINGLE DO CPT IMÓVEIS
     * ------------------------------------------------------------ */

    if ( ! is_singular( 'imoveis' ) ) {
        return;
    }


    /* ------------------------------------------------------------
     * PEGA O ID DO IMÓVEL
     * ------------------------------------------------------------ */

    $post_id = get_queried_object_id();

    if ( ! $post_id ) {
        return;
    }


    /* ------------------------------------------------------------
     * PEGA O CONTEÚDO DO THE_CONTENT
     * ------------------------------------------------------------ */

    $content = get_post_field(
        'post_content',
        $post_id
    );

    if ( empty( $content ) ) {
        return;
    }


    /* ------------------------------------------------------------
     * REMOVE SHORTCODES
     * ------------------------------------------------------------ */

    $content = strip_shortcodes( $content );


    /* ------------------------------------------------------------
     * TRANSFORMA QUEBRAS HTML EM ESPAÇOS
     * ------------------------------------------------------------ */

    $content = preg_replace(
        '/<br\s*\/?>/i',
        ' ',
        $content
    );


    /* ------------------------------------------------------------
     * TRANSFORMA TAGS DE BLOCO EM ESPAÇOS
     * ------------------------------------------------------------ */

    $content = preg_replace(
        '/<\/?(p|div|section|article|header|footer|aside|li|ul|ol|h[1-6]|table|tbody|thead|tfoot|tr|td|th|blockquote)[^>]*>/i',
        ' ',
        $content
    );


    /* ------------------------------------------------------------
     * REMOVE AS DEMAIS TAGS HTML
     * ------------------------------------------------------------ */

    $content = wp_strip_all_tags( $content );


    /* ------------------------------------------------------------
     * CONVERTE ENTIDADES HTML
     * ------------------------------------------------------------ */

    $content = html_entity_decode(
        $content,
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );


    /* ------------------------------------------------------------
     * REMOVE PALAVRAS E TEXTOS INDESEJADOS
     * ------------------------------------------------------------ */

    $content = preg_replace(
        '/(?<!\p{L})(?:descrição|ver\s+mais)(?!\p{L})/iu',
        ' ',
        $content
    );


    /* ------------------------------------------------------------
     * REMOVE ESPAÇOS ESPECIAIS
     * ------------------------------------------------------------ */

    $content = str_replace(
        array(
            "\xc2\xa0",
            '&nbsp;'
        ),
        ' ',
        $content
    );


    /* ------------------------------------------------------------
     * NORMALIZA ESPAÇOS
     * ------------------------------------------------------------ */

    $content = preg_replace(
        '/\s+/u',
        ' ',
        $content
    );

    $content = trim( $content );

    if ( empty( $content ) ) {
        return;
    }


    /* ------------------------------------------------------------
     * LIMITA A DESCRIPTION A 160 CARACTERES
     * ------------------------------------------------------------ */

    $limite = 160;

    if ( mb_strlen( $content, 'UTF-8' ) > $limite ) {

        $description = mb_substr(
            $content,
            0,
            $limite,
            'UTF-8'
        );


        /* ------------------------------------------------------------
         * EVITA CORTAR A ÚLTIMA PALAVRA
         * ------------------------------------------------------------ */

        $ultimo_espaco = mb_strrpos(
            $description,
            ' ',
            0,
            'UTF-8'
        );

        if ( $ultimo_espaco !== false ) {

            $description = mb_substr(
                $description,
                0,
                $ultimo_espaco,
                'UTF-8'
            );
        }

        $description .= '...';

    } else {

        $description = $content;
    }


    /* ------------------------------------------------------------
     * META DESCRIPTION
     * ------------------------------------------------------------ */

    echo "\n";

    echo '<meta name="description" content="' .
        esc_attr( $description ) .
        '">' . "\n";


    /* ------------------------------------------------------------
     * OPEN GRAPH DESCRIPTION
     * ------------------------------------------------------------ */

    echo '<meta property="og:description" content="' .
        esc_attr( $description ) .
        '">' . "\n";
}

/* ------------------------------------------------------------
 * Shortcode de Preço
 * ------------------------------------------------------------ */

function imu_preco_shortcode( $atts ) {

    $atts = shortcode_atts( [
        'decimal' => '0',
        'pretext' => '1',
    ], $atts );

    $valor = get_post_meta( get_the_ID(), 'preco', true );

    if ( $valor === '' ) {
        return '';
    }

    $valor = str_replace( [ 'R$', ' ', '.' ], '', $valor );
    $valor = str_replace( ',', '.', $valor );

    $decimais = $atts['decimal'] === '1' ? 2 : 0;
    $preco = number_format( (float) $valor, $decimais, ',', '.' );

    return $atts['pretext'] === '1' ? 'R$ ' . $preco : $preco;
}

add_shortcode( 'imu_preco', 'imu_preco_shortcode' );


/* ------------------------------------------------------------
 * CSS - No single se tiver menos de 3 imagens na galeria, centralizar ela
 * ------------------------------------------------------------ */

add_action( 'wp_footer', function() {

    if ( ! is_singular( 'imoveis' ) ) return;
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {

        if (document.querySelectorAll('img.swiper-slide-image').length < 6) {

            document.head.insertAdjacentHTML('beforeend', `
                <style>
                    .elementor-27881 .elementor-element.elementor-element-fd988c8 .swiper-wrapper {
                        justify-content: center !important;
                    }
                </style>
            `);

        }

    });
    </script>
    <?php

});

/* ------------------------------------------------------------
 * CSS - Enviar email quando cadastra um imovel
 * ------------------------------------------------------------ */


add_action('transition_post_status', function ($new_status, $old_status, $post) {

    if (!$post || empty($post->ID)) {
        return;
    }

    if ($post->post_type !== 'imoveis') {
        return;
    }

    // Envia apenas quando o imóvel entra em publicado.
    if ($new_status !== 'publish' || $old_status === 'publish') {
        return;
    }

    $post_id = (int) $post->ID;

    // Evita envio duplicado.
    if (get_post_meta($post_id, '_imu_email_imovel_enviado', true) === 'sim') {
        return;
    }

    $titulo = get_the_title($post_id);
    $permalink = get_permalink($post_id);

    $categorias = [];
    $termos = get_the_terms($post_id, 'categoria');

    if (!empty($termos) && !is_wp_error($termos)) {
        foreach ($termos as $termo) {
            $categorias[] = $termo->name;
        }
    }

    $categoria = !empty($categorias) ? implode(', ', $categorias) : 'Sem categoria';

    $para = 'diefersil@gmail.com';
    $assunto = 'Novo imóvel cadastrado: ' . $titulo;

    $mensagem = "Título: " . $titulo . "\n";
    $mensagem .= "Categoria: " . $categoria . "\n";
    $mensagem .= "Link: " . $permalink . "\n";

    $enviado = wp_mail($para, $assunto, $mensagem);

    if ($enviado) {
        update_post_meta($post_id, '_imu_email_imovel_enviado', 'sim');
    }

}, 10, 3);



