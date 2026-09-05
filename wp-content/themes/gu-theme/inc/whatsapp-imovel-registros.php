<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


// =========================================================
// CONFIGURAÇÃO DA TABELA
// =========================================================

function imu_whatsapp_nome_tabela() {

    global $wpdb;

    return $wpdb->prefix . 'imu_whatsapp_cliques';
}


// =========================================================
// CRIAR / ATUALIZAR TABELA
// =========================================================

function imu_whatsapp_criar_tabela() {

    global $wpdb;

    $tabela = imu_whatsapp_nome_tabela();

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$tabela} (

        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        imovel_id BIGINT UNSIGNED NOT NULL,

        data_clique DATETIME NOT NULL,

        ip VARCHAR(45) NOT NULL DEFAULT '',

        autor_id BIGINT UNSIGNED NOT NULL DEFAULT 0,

        categoria TEXT NULL,

        PRIMARY KEY (id),

        KEY imovel_id (imovel_id),

        KEY autor_id (autor_id),

        KEY data_clique (data_clique)

    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta( $sql );

    update_option(
        'imu_whatsapp_db_version',
        '1.0'
    );
}


// =========================================================
// VERIFICA SE A TABELA PRECISA SER CRIADA
// =========================================================

add_action( 'init', function() {

    $versao = get_option(
        'imu_whatsapp_db_version'
    );

    if ( $versao !== '1.0' ) {

        imu_whatsapp_criar_tabela();
    }

});


// =========================================================
// REGISTRAR CLIQUE
// =========================================================

function imu_registrar_clique_whatsapp( $post_id ) {

    global $wpdb;

    $post_id = absint( $post_id );

    if ( ! $post_id ) {
        return false;
    }

    if ( get_post_type( $post_id ) !== 'imoveis' ) {
        return false;
    }


    // =====================================================
    // AUTOR DO IMÓVEL
    // =====================================================

    $autor_id = (int) get_post_field(
        'post_author',
        $post_id
    );


    // =====================================================
    // CATEGORIA DO IMÓVEL
    //
    // Taxonomia utilizada: categoria
    // =====================================================

    $categorias = wp_get_post_terms(
        $post_id,
        'categoria',
        [
            'fields' => 'names',
        ]
    );

    $categoria = '';

    if (
        ! is_wp_error( $categorias ) &&
        ! empty( $categorias )
    ) {

        $categoria = implode(
            ', ',
            $categorias
        );
    }


    // =====================================================
    // IP
    // =====================================================

    $ip = '';

    if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {

        $ip = sanitize_text_field(
            wp_unslash(
                $_SERVER['REMOTE_ADDR']
            )
        );
    }


    // =====================================================
    // GRAVA NO BANCO
    // =====================================================

    $resultado = $wpdb->insert(

        imu_whatsapp_nome_tabela(),

        [
            'imovel_id'   => $post_id,
            'data_clique' => current_time( 'mysql' ),
            'ip'          => $ip,
            'autor_id'    => $autor_id,
            'categoria'   => $categoria,
        ],

        [
            '%d',
            '%s',
            '%s',
            '%d',
            '%s',
        ]
    );


    return $resultado;
}


// =========================================================
// MENU ADMIN
// Imóveis → Cliques WhatsApp
// =========================================================

add_action( 'admin_menu', function() {

    add_submenu_page(

        'edit.php?post_type=imoveis',

        'Cliques WhatsApp',

        'Cliques WhatsApp',

        'manage_options',

        'imu-whatsapp-cliques',

        'imu_whatsapp_pagina_admin'

    );

});


// =========================================================
// PÁGINA ADMIN
// =========================================================

function imu_whatsapp_pagina_admin() {

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    global $wpdb;

    $tabela = imu_whatsapp_nome_tabela();


    // =====================================================
    // PAGINAÇÃO
    // =====================================================

    $por_pagina = 50;

    $pagina_atual = isset( $_GET['paged'] )
        ? max( 1, absint( $_GET['paged'] ) )
        : 1;

    $offset =
        ( $pagina_atual - 1 )
        * $por_pagina;


    // =====================================================
    // TOTAL DE REGISTROS
    // =====================================================

    $total = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$tabela}"
    );


    // =====================================================
    // BUSCA REGISTROS
    // =====================================================

    $registros = $wpdb->get_results(

        $wpdb->prepare(

            "SELECT *
             FROM {$tabela}
             ORDER BY data_clique DESC
             LIMIT %d OFFSET %d",

            $por_pagina,
            $offset

        )

    );


    // =====================================================
    // TOTAL DE PÁGINAS
    // =====================================================

    $total_paginas = max(
        1,
        ceil(
            $total / $por_pagina
        )
    );

    ?>

    <div class="wrap">

        <h1>
            Cliques no WhatsApp
        </h1>

        <p>
            Total de cliques registrados:
            <strong>
                <?php echo number_format_i18n( $total ); ?>
            </strong>
        </p>


        <table class="wp-list-table widefat fixed striped">

            <thead>

                <tr>

                    <th style="width:70px;">
                        ID
                    </th>

                    <th>
                        Imóvel
                    </th>

                    <th style="width:170px;">
                        Data
                    </th>

                    <th style="width:150px;">
                        IP
                    </th>

                    <th>
                        Autor
                    </th>

                    <th>
                        Categoria
                    </th>

                </tr>

            </thead>


            <tbody>

            <?php

            if ( ! empty( $registros ) ) :

                foreach ( $registros as $registro ) :

                    // =====================================
                    // IMÓVEL
                    // =====================================

                    $titulo_imovel = get_the_title(
                        $registro->imovel_id
                    );

                    if ( ! $titulo_imovel ) {

                        $titulo_imovel =
                            'Imóvel #' .
                            $registro->imovel_id;
                    }


                    // =====================================
                    // AUTOR
                    // =====================================

                    $autor = get_userdata(
                        $registro->autor_id
                    );

                    $nome_autor = '';

                    if ( $autor ) {

                        $nome_autor =
                            $autor->display_name;

                    } else {

                        $nome_autor =
                            'Autor #' .
                            $registro->autor_id;
                    }


                    // =====================================
                    // LINK DO IMÓVEL
                    // =====================================

                    $link_imovel = get_permalink(
                        $registro->imovel_id
                    );

                    ?>

                    <tr>

                        <td>

                            <?php
                            echo esc_html(
                                $registro->id
                            );
                            ?>

                        </td>


                        <td>

                            <?php if ( $link_imovel ) : ?>

                                <a
                                    href="<?php echo esc_url( $link_imovel ); ?>"
                                    target="_blank"
                                >

                                    <strong>

                                        <?php
                                        echo esc_html(
                                            $titulo_imovel
                                        );
                                        ?>

                                    </strong>

                                </a>

                            <?php else : ?>

                                <?php
                                echo esc_html(
                                    $titulo_imovel
                                );
                                ?>

                            <?php endif; ?>


                            <br>


                            <small>

                                ID:
                                <?php
                                echo esc_html(
                                    $registro->imovel_id
                                );
                                ?>

                            </small>

                        </td>


                        <td>

                            <?php

                            echo esc_html(

                                mysql2date(
                                    'd/m/Y H:i:s',
                                    $registro->data_clique
                                )

                            );

                            ?>

                        </td>


                        <td>

                            <?php
                            echo esc_html(
                                $registro->ip
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo esc_html(
                                $nome_autor
                            );
                            ?>

                            <br>

                            <small>

                                ID:
                                <?php
                                echo esc_html(
                                    $registro->autor_id
                                );
                                ?>

                            </small>

                        </td>


                        <td>

                            <?php
                            echo esc_html(
                                $registro->categoria
                            );
                            ?>

                        </td>

                    </tr>

                    <?php

                endforeach;

            else :

                ?>

                <tr>

                    <td colspan="6">

                        Nenhum clique registrado ainda.

                    </td>

                </tr>

                <?php

            endif;

            ?>

            </tbody>

        </table>


        <?php

        // =================================================
        // PAGINAÇÃO
        // =================================================

        if ( $total_paginas > 1 ) {

            echo '<div style="margin-top:20px;">';

            echo paginate_links(

                [
                    'base' => add_query_arg(
                        'paged',
                        '%#%'
                    ),

                    'format'  => '',

                    'current' => $pagina_atual,

                    'total'   => $total_paginas,

                    'prev_text' => '« Anterior',

                    'next_text' => 'Próxima »',
                ]

            );

            echo '</div>';
        }

        ?>

    </div>

    <?php
}