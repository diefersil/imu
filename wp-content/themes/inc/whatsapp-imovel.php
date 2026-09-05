<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


// =========================================================
// DADOS DE CONTATO DO IMÓVEL
// =========================================================

function imu_get_contato_imovel( $post_id ) {

    $author_id = (int) get_post_field(
        'post_author',
        $post_id
    );

    if ( ! $author_id ) {
        return false;
    }

    $dados = [
        'author_id' => $author_id,
        'nome'      => '',
        'fone'      => '',
        'whatsapp'  => '',
    ];

    // =====================================================
    // AUTOR ID = 4
    // Dados cadastrados diretamente no imóvel
    // =====================================================

    if ( $author_id === 4 ) {

        $dados['nome'] = get_post_meta(
            $post_id,
            'contato_nome',
            true
        );

        $dados['fone'] = get_post_meta(
            $post_id,
            'contato_fone',
            true
        );

        $dados['whatsapp'] = get_post_meta(
            $post_id,
            'contato_whatsapp',
            true
        );

    // =====================================================
    // DEMAIS AUTORES
    // Dados cadastrados no usuário WordPress
    // =====================================================

    } else {

        $user = get_userdata( $author_id );

        if ( ! $user ) {
            return false;
        }

        $dados['nome'] = $user->display_name;

        $dados['fone'] = get_user_meta(
            $author_id,
            'user_fone',
            true
        );

        $dados['whatsapp'] = get_user_meta(
            $author_id,
            'user_whatsapp',
            true
        );
    }

    return $dados;
}


// =========================================================
// URL REAL DO WHATSAPP
// =========================================================

function imu_get_whatsapp_real_url( $post_id ) {

    $dados = imu_get_contato_imovel( $post_id );

    if ( ! $dados ) {
        return '';
    }

    $whatsapp = $dados['whatsapp'];

    if ( empty( $whatsapp ) ) {
        return '';
    }

    // Somente números
    $whatsapp = preg_replace(
        '/\D+/',
        '',
        $whatsapp
    );

    if ( empty( $whatsapp ) ) {
        return '';
    }

    // Adiciona código do Brasil
    if ( substr( $whatsapp, 0, 2 ) !== '55' ) {
        $whatsapp = '55' . $whatsapp;
    }

    $post_title = get_the_title( $post_id );

    // =====================================================
    // MENSAGEM
    // =====================================================

    if ( ! empty( $dados['nome'] ) ) {

        $msg = 'Olá, ' . $dados['nome'] .
               '. Vi seu imóvel: ' . $post_title .
               ', no site Imóveis Unaí e gostaria de saber mais informações.';

    } else {

        $msg = 'Olá. Vi seu imóvel: ' . $post_title .
               ', no site Imóveis Unaí e gostaria de saber mais informações.';
    }

    return 'https://wa.me/' .
           $whatsapp .
           '?text=' .
           rawurlencode( $msg );
}


// =========================================================
// SHORTCODE
// [whatsapp_imovel_url]
// =========================================================

function imu_whatsapp_imovel_url_shortcode() {

    $post_id = get_queried_object_id();

    if ( ! $post_id ) {
        $post_id = get_the_ID();
    }

    if ( ! $post_id ) {
        return '';
    }

    if ( get_post_type( $post_id ) !== 'imoveis' ) {
        return '';
    }

    // Verifica se existe WhatsApp
    $whatsapp_url = imu_get_whatsapp_real_url(
        $post_id
    );

    if ( empty( $whatsapp_url ) ) {
        return '';
    }

    // URL intermediária
    $url = add_query_arg(
        [
            'imu_whatsapp_click' => $post_id,
        ],
        home_url( '/' )
    );

    return esc_url( $url );
}

add_shortcode(
    'whatsapp_imovel_url',
    'imu_whatsapp_imovel_url_shortcode'
);


// =========================================================
// PROCESSA CLIQUE NO WHATSAPP
// =========================================================

add_action( 'template_redirect', function() {

    if ( empty( $_GET['imu_whatsapp_click'] ) ) {
        return;
    }

    $post_id = absint(
        $_GET['imu_whatsapp_click']
    );

    if ( ! $post_id ) {
        return;
    }

    if ( get_post_type( $post_id ) !== 'imoveis' ) {
        return;
    }

    // =====================================================
    // DADOS DO CONTATO
    // =====================================================

    $dados = imu_get_contato_imovel(
        $post_id
    );

    if ( ! $dados ) {
        return;
    }

    // =====================================================
    // URL REAL DO WHATSAPP
    // =====================================================

    $whatsapp_url = imu_get_whatsapp_real_url(
        $post_id
    );

    if ( empty( $whatsapp_url ) ) {
        return;
    }

    // =====================================================
    // DADOS DO IMÓVEL
    // =====================================================

    $titulo = get_the_title(
        $post_id
    );

    $link_imovel = get_permalink(
        $post_id
    );

    // =====================================================
    // DATA / HORA
    // =====================================================

    $data_hora = current_time(
        'd/m/Y H:i:s'
    );

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
    // REFERÊNCIA
    // =====================================================

    $referer = '';

    if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {

        $referer = esc_url_raw(
            wp_unslash(
                $_SERVER['HTTP_REFERER']
            )
        );
    }

    // =====================================================
    // EMAIL DESTINO
    // =====================================================

    $email_destino = get_option(
        'admin_email'
    );

    /*
    Se quiser um e-mail específico:

    $email_destino = 'email@dominio.com';
    */


    // =====================================================
    // ASSUNTO
    // =====================================================

    $assunto =
        'Clique no WhatsApp - ' .
        $titulo;


    // =====================================================
    // CONTEÚDO DO EMAIL
    // =====================================================

    $mensagem = '';

    $mensagem .= "Novo clique no WhatsApp\n\n";

    $mensagem .= "Imóvel: ";
    $mensagem .= $titulo;
    $mensagem .= "\n";

    $mensagem .= "ID do imóvel: ";
    $mensagem .= $post_id;
    $mensagem .= "\n";

    $mensagem .= "URL do imóvel: ";
    $mensagem .= $link_imovel;
    $mensagem .= "\n\n";

    $mensagem .= "Contato: ";
    $mensagem .= $dados['nome'];
    $mensagem .= "\n";

    $mensagem .= "Fone: ";
    $mensagem .= $dados['fone'];
    $mensagem .= "\n";

    $mensagem .= "WhatsApp: ";
    $mensagem .= $dados['whatsapp'];
    $mensagem .= "\n";

    $mensagem .= "Autor ID: ";
    $mensagem .= $dados['author_id'];
    $mensagem .= "\n\n";

    $mensagem .= "Data/Hora: ";
    $mensagem .= $data_hora;
    $mensagem .= "\n";

    $mensagem .= "IP: ";
    $mensagem .= $ip;
    $mensagem .= "\n";

    if ( ! empty( $referer ) ) {

        $mensagem .= "Origem: ";
        $mensagem .= $referer;
        $mensagem .= "\n";
    }


    // =====================================================
    // ENVIA EMAIL
    // =====================================================

    wp_mail(
        $email_destino,
        $assunto,
        $mensagem
    );


    // =====================================================
    // EVITA CACHE
    // =====================================================

    nocache_headers();


    // =====================================================
    // REDIRECIONA PARA WHATSAPP
    // =====================================================

    wp_safe_redirect(
        $whatsapp_url,
        302
    );

    exit;

});