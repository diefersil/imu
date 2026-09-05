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
    // Usa dados cadastrados diretamente no imóvel
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
    // Usa dados cadastrados no usuário WordPress
    // =====================================================

    } else {

        $user = get_userdata( $author_id );

        if ( ! $user ) {
            return false;
        }

        // Nome
        $dados['nome'] = $user->display_name;

        // Telefone
        $dados['fone'] = get_user_meta(
            $author_id,
            'user_fone',
            true
        );

        // WhatsApp
        $dados['whatsapp'] = get_user_meta(
            $author_id,
            'user_whatsapp',
            true
        );
    }


    return $dados;
}


// =========================================================
// MONTA URL REAL DO WHATSAPP
// =========================================================

function imu_get_whatsapp_real_url( $post_id ) {

    $dados = imu_get_contato_imovel(
        $post_id
    );

    if ( ! $dados ) {
        return '';
    }

    $whatsapp = $dados['whatsapp'];


    // =====================================================
    // VERIFICA WHATSAPP
    // =====================================================

    if ( empty( $whatsapp ) ) {
        return '';
    }


    // Remove espaços, parênteses, traços etc.
    $whatsapp = preg_replace(
        '/\D+/',
        '',
        $whatsapp
    );

    if ( empty( $whatsapp ) ) {
        return '';
    }


    // =====================================================
    // ADICIONA CÓDIGO DO BRASIL
    // =====================================================

    if ( substr( $whatsapp, 0, 2 ) !== '55' ) {

        $whatsapp = '55' . $whatsapp;
    }


    // =====================================================
    // TÍTULO DO IMÓVEL
    // =====================================================

    $post_title = get_the_title(
        $post_id
    );


    // =====================================================
    // MENSAGEM DO WHATSAPP
    // =====================================================

    if ( ! empty( $dados['nome'] ) ) {

        $msg =
            'Olá, ' .
            $dados['nome'] .
            '. Vi seu imóvel: ' .
            $post_title .
            ', no site Imóveis Unaí e gostaria de saber mais informações.';

    } else {

        $msg =
            'Olá. Vi seu imóvel: ' .
            $post_title .
            ', no site Imóveis Unaí e gostaria de saber mais informações.';
    }


    // =====================================================
    // URL FINAL DO WHATSAPP
    // =====================================================

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

    // =====================================================
    // IDENTIFICA O IMÓVEL ATUAL
    // =====================================================

    $post_id = get_queried_object_id();

    if ( ! $post_id ) {

        $post_id = get_the_ID();
    }

    if ( ! $post_id ) {
        return '';
    }


    // =====================================================
    // GARANTE QUE É POST TYPE IMOVEIS
    // =====================================================

    if ( get_post_type( $post_id ) !== 'imoveis' ) {
        return '';
    }


    // =====================================================
    // VERIFICA SE EXISTE WHATSAPP
    // =====================================================

    $whatsapp_url = imu_get_whatsapp_real_url(
        $post_id
    );

    if ( empty( $whatsapp_url ) ) {
        return '';
    }


    // =====================================================
    // URL INTERMEDIÁRIA
    //
    // O clique passa primeiro pelo WordPress,
    // envia o email e depois vai para o WhatsApp.
    // =====================================================

    $url = add_query_arg(
        [
            'imu_whatsapp_click' => $post_id,
        ],
        home_url( '/' )
    );


    return esc_url( $url );
}


// =========================================================
// REGISTRA SHORTCODE
// =========================================================

add_shortcode(
    'whatsapp_imovel_url',
    'imu_whatsapp_imovel_url_shortcode'
);


// =========================================================
// PROCESSA CLIQUE NO WHATSAPP
// =========================================================

add_action(
    'template_redirect',
    function() {


        // =================================================
        // VERIFICA PARÂMETRO
        // =================================================

        if ( empty( $_GET['imu_whatsapp_click'] ) ) {
            return;
        }


        // =================================================
        // ID DO IMÓVEL
        // =================================================

        $post_id = absint(
            $_GET['imu_whatsapp_click']
        );

        if ( ! $post_id ) {
            return;
        }


        // =================================================
        // GARANTE POST TYPE IMOVEIS
        // =================================================

        if ( get_post_type( $post_id ) !== 'imoveis' ) {
            return;
        }


        // =================================================
        // DADOS DO CONTATO
        // =================================================

        $dados = imu_get_contato_imovel(
            $post_id
        );

        if ( ! $dados ) {
            return;
        }


        // =================================================
        // URL REAL DO WHATSAPP
        // =================================================

        $whatsapp_url = imu_get_whatsapp_real_url(
            $post_id
        );

        if ( empty( $whatsapp_url ) ) {
            return;
        }


        // =================================================
        // DADOS DO IMÓVEL
        // =================================================

        $titulo = get_the_title(
            $post_id
        );

        $link_imovel = get_permalink(
            $post_id
        );


        // =================================================
        // DATA / HORA
        // =================================================

        $data_hora = current_time(
            'd/m/Y H:i:s'
        );


        // =================================================
        // IP DO VISITANTE
        // =================================================

        $ip = '';

        if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {

            $ip = sanitize_text_field(
                wp_unslash(
                    $_SERVER['REMOTE_ADDR']
                )
            );
        }


        // =================================================
        // PÁGINA DE ORIGEM
        // =================================================

        $referer = '';

        if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {

            $referer = esc_url_raw(
                wp_unslash(
                    $_SERVER['HTTP_REFERER']
                )
            );
        }


        // =================================================
        // EMAIL DE DESTINO
        // =================================================

        $email_destino = 'diefersil@gmail.com';


        // =================================================
        // ASSUNTO
        // =================================================

        $assunto =
            'Clique no WhatsApp - ' .
            $titulo;


        // =================================================
        // CONTEÚDO DO EMAIL
        // =================================================

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


        // =================================================
        // DADOS DO CONTATO
        // =================================================

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


        // =================================================
        // DADOS DO CLIQUE
        // =================================================

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


        // =================================================
        // ENVIA EMAIL
        // =================================================

        wp_mail(
            $email_destino,
            $assunto,
            $mensagem
        );


        // =================================================
        // EVITA CACHE
        // =================================================

        nocache_headers();


        // =================================================
        // REDIRECIONA PARA WHATSAPP
        //
        // IMPORTANTE:
        // Usa wp_redirect() porque wa.me é domínio externo.
        // wp_safe_redirect() bloquearia o redirecionamento.
        // =================================================

        wp_redirect(
            $whatsapp_url,
            302
        );

        exit;
    }
);