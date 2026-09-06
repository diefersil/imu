<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


// =========================================================
// CONFIGURAÇÕES
// =========================================================

define(
    'IMU_WHATSAPP_FORM_NAME',
    'imu_whatsapp'
);

define(
    'IMU_WHATSAPP_EMAIL_DESTINO',
    'diefersil@gmail.com'
);


// =========================================================
// DADOS DE CONTATO DO IMÓVEL
//
// AUTOR ID = 4
// contato_nome
// contato_fone
// contato_whatsapp
//
// DEMAIS AUTORES
// display_name
// user_fone
// user_whatsapp
// =========================================================

function imu_get_contato_imovel( $post_id ) {

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

    $author_id = (int) get_post_field(
        'post_author',
        $post_id
    );


    if ( ! $author_id ) {
        return false;
    }


    $dados = [

        'author_id' => $author_id,

        'nome' => '',

        'fone' => '',

        'whatsapp' => '',
    ];


    // =====================================================
    // AUTOR ID = 4
    // Dados cadastrados diretamente no imóvel
    // =====================================================

    if ( $author_id === 4 ) {


        // Nome

        $dados['nome'] = get_post_meta(
            $post_id,
            'contato_nome',
            true
        );


        // Fone

        $dados['fone'] = get_post_meta(
            $post_id,
            'contato_fone',
            true
        );


        // WhatsApp

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


        $user = get_userdata(
            $author_id
        );


        if ( ! $user ) {
            return false;
        }


        // Nome

        $dados['nome'] =
            $user->display_name;


        // Fone

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
// NORMALIZA NÚMERO DE WHATSAPP
// =========================================================

function imu_normalizar_whatsapp( $numero ) {

    $numero = preg_replace(
        '/\D+/',
        '',
        (string) $numero
    );


    if ( empty( $numero ) ) {
        return '';
    }


    // =====================================================
    // CÓDIGO DO BRASIL
    // =====================================================

    if ( substr( $numero, 0, 2 ) !== '55' ) {

        $numero =
            '55' .
            $numero;
    }


    return $numero;
}


// =========================================================
// MONTA URL REAL DO WHATSAPP
// =========================================================

function imu_get_whatsapp_real_url(
    $post_id,
    $nome_visitante = ''
) {

    $post_id = absint(
        $post_id
    );


    if ( ! $post_id ) {
        return '';
    }


    // =====================================================
    // CONTATO DO ANUNCIANTE
    // =====================================================

    $dados = imu_get_contato_imovel(
        $post_id
    );


    if ( ! $dados ) {
        return '';
    }


    // =====================================================
    // WHATSAPP DO ANUNCIANTE
    // =====================================================

    $whatsapp = imu_normalizar_whatsapp(
        $dados['whatsapp']
    );


    if ( empty( $whatsapp ) ) {
        return '';
    }


    // =====================================================
    // TÍTULO DO IMÓVEL
    // =====================================================

    $post_title = get_the_title(
        $post_id
    );


    // =====================================================
    // MENSAGEM
    // =====================================================

    $msg = 'Olá';


    // Nome do anunciante

    if ( ! empty( $dados['nome'] ) ) {

        $msg .=
            ', ' .
            $dados['nome'];
    }


    $msg .= '. ';


    // Nome do visitante

    if ( ! empty( $nome_visitante ) ) {

        $msg .=
            'Meu nome é ' .
            $nome_visitante .
            '. ';
    }


    $msg .=
        'Vi seu imóvel: [' .
        $post_title .
        '], no site Imóveis Unaí e gostaria de saber mais informações.';


    // =====================================================
    // URL
    // =====================================================

    return
        'https://wa.me/' .
        $whatsapp .
        '?text=' .
        rawurlencode( $msg );
}


// =========================================================
// SHORTCODE
//
// [whatsapp_imovel_url]
//
// Este shortcode continua podendo ser utilizado no
// botão/container que abre o formulário deslizante.
// =========================================================

function imu_whatsapp_imovel_url_shortcode() {

    // =====================================================
    // ID DO IMÓVEL ATUAL
    // =====================================================

    $post_id = get_queried_object_id();


    if ( ! $post_id ) {

        $post_id = get_the_ID();
    }


    if ( ! $post_id ) {
        return '';
    }


    // =====================================================
    // POST TYPE
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
    // Você pode continuar usando esta URL no botão.
    // O popup/form Elementor será aberto pelo próprio
    // Elementor.
    // =====================================================

    return esc_url(
        add_query_arg(
            'imu_whatsapp',
            $post_id,
            get_permalink( $post_id )
        )
    );
}


// =========================================================
// REGISTRA SHORTCODE
// =========================================================

add_shortcode(
    'whatsapp_imovel_url',
    'imu_whatsapp_imovel_url_shortcode'
);


// =========================================================
// VALIDA FORMULÁRIO ELEMENTOR
//
// Form Name:
// imu_whatsapp
// =========================================================

add_action(
    'elementor_pro/forms/validation',
    function( $record, $ajax_handler ) {


        // =================================================
        // FORM NAME
        // =================================================

        $form_name = $record->get_form_settings(
            'form_name'
        );


        if (
            IMU_WHATSAPP_FORM_NAME !==
            $form_name
        ) {

            return;
        }


        // =================================================
        // CAMPOS
        // =================================================

        $raw_fields = $record->get(
            'fields'
        );


        $fields = [];


        foreach (
            $raw_fields as
            $id => $field
        ) {

            $fields[ $id ] =
                isset( $field['value'] )
                ? $field['value']
                : '';
        }


        // =================================================
        // NOME
        // =================================================

        $nome = isset( $fields['nome'] )
            ? trim( $fields['nome'] )
            : '';


        if ( empty( $nome ) ) {

            $ajax_handler->add_error(
                'nome',
                'Informe seu nome.'
            );
        }


        // =================================================
        // EMAIL
        // =================================================

        $email = isset( $fields['email'] )
            ? trim( $fields['email'] )
            : '';


        if (
            empty( $email ) ||
            ! is_email( $email )
        ) {

            $ajax_handler->add_error(
                'email',
                'Informe um e-mail válido.'
            );
        }


        // =================================================
        // WHATSAPP
        // =================================================

        $whatsapp = isset( $fields['whatsapp'] )
            ? preg_replace(
                '/\D+/',
                '',
                $fields['whatsapp']
            )
            : '';


        if (
            empty( $whatsapp ) ||
            strlen( $whatsapp ) < 10
        ) {

            $ajax_handler->add_error(
                'whatsapp',
                'Informe um WhatsApp válido.'
            );
        }


        // =================================================
        // IMÓVEL
        // =================================================

        $post_id = isset( $fields['imovel_id'] )
            ? absint( $fields['imovel_id'] )
            : 0;


        if (
            ! $post_id ||
            get_post_type( $post_id ) !== 'imoveis'
        ) {

            $ajax_handler->add_error(
                'imovel_id',
                'Não foi possível identificar o imóvel.'
            );
        }

    },
    10,
    2
);


// =========================================================
// CAPTURA ENVIO DO FORMULÁRIO ELEMENTOR
//
// Executado depois que o formulário foi processado.
// =========================================================

add_action(
    'elementor_pro/forms/new_record',
    function( $record, $ajax_handler ) {


        // =================================================
        // IDENTIFICA O FORMULÁRIO
        // =================================================

        $form_name = $record->get_form_settings(
            'form_name'
        );


        if (
            IMU_WHATSAPP_FORM_NAME !==
            $form_name
        ) {

            return;
        }


        // =================================================
        // PEGA OS CAMPOS
        // =================================================

        $raw_fields = $record->get(
            'fields'
        );


        $fields = [];


        foreach (
            $raw_fields as
            $id => $field
        ) {

            $fields[ $id ] =
                isset( $field['value'] )
                ? $field['value']
                : '';
        }


        // =================================================
        // DADOS DO VISITANTE
        // =================================================

        $nome = isset( $fields['nome'] )
            ? sanitize_text_field(
                $fields['nome']
            )
            : '';


        $email = isset( $fields['email'] )
            ? sanitize_email(
                $fields['email']
            )
            : '';


        $whatsapp_visitante =
            isset( $fields['whatsapp'] )
            ? sanitize_text_field(
                $fields['whatsapp']
            )
            : '';


        // =================================================
        // ID DO IMÓVEL
        // =================================================

        $post_id =
            isset( $fields['imovel_id'] )
            ? absint(
                $fields['imovel_id']
            )
            : 0;


        if ( ! $post_id ) {
            return;
        }


        if (
            get_post_type( $post_id )
            !== 'imoveis'
        ) {

            return;
        }


        // =================================================
        // ID DO AUTOR
        // =================================================

        $autor_id =
            (int) get_post_field(
                'post_author',
                $post_id
            );


        // =================================================
        // DATA
        // =================================================

        $data =
            current_time(
                'mysql'
            );


        // =================================================
        // IP
        // =================================================

        $ip = '';


        if (
            ! empty(
                $_SERVER['REMOTE_ADDR']
            )
        ) {

            $ip =
                sanitize_text_field(
                    wp_unslash(
                        $_SERVER['REMOTE_ADDR']
                    )
                );
        }


        // =================================================
        // CATEGORIA
        // =================================================

        $categorias =
            wp_get_post_terms(
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

            $categoria =
                implode(
                    ', ',
                    $categorias
                );
        }


        // =================================================
        // DADOS DO CONTATO / ANUNCIANTE
        // =================================================

        $dados_contato =
            imu_get_contato_imovel(
                $post_id
            );


        if ( ! $dados_contato ) {
            return;
        }


        // =================================================
        // GRAVA NA NOSSA TABELA
        // =================================================
        //
        // A função de registros será ajustada para receber:
        //
        // imóvel
        // nome
        // email
        // whatsapp
        //
        // Autor, categoria, data e IP são calculados
        // dentro da própria função de registros.
        // =================================================

        if (
            function_exists(
                'imu_registrar_clique_whatsapp'
            )
        ) {

            imu_registrar_clique_whatsapp(
                $post_id,
                $nome,
                $email,
                $whatsapp_visitante
            );

        }


        // =================================================
        // DADOS DO IMÓVEL
        // =================================================

        $titulo =
            get_the_title(
                $post_id
            );


        $link_imovel =
            get_permalink(
                $post_id
            );


        // =================================================
        // EMAIL DE DESTINO
        // =================================================

        $email_destino =
            IMU_WHATSAPP_EMAIL_DESTINO;


        // =================================================
        // ASSUNTO
        // =================================================

        $assunto =
            'Novo Lead WhatsApp - ' .
            $titulo;


        // =================================================
        // CONTEÚDO DO EMAIL
        // =================================================

        $mensagem_email = '';


        $mensagem_email .=
            "NOVO LEAD - IMÓVEIS UNAÍ\n\n";


        // =================================================
        // VISITANTE
        // =================================================

        $mensagem_email .=
            "DADOS DO INTERESSADO\n";

        $mensagem_email .=
            "------------------------------\n";


        $mensagem_email .=
            "Nome: " .
            $nome .
            "\n";


        $mensagem_email .=
            "E-mail: " .
            $email .
            "\n";


        $mensagem_email .=
            "WhatsApp: " .
            $whatsapp_visitante .
            "\n\n";


        // =================================================
        // IMÓVEL
        // =================================================

        $mensagem_email .=
            "DADOS DO IMÓVEL\n";

        $mensagem_email .=
            "------------------------------\n";


        $mensagem_email .=
            "Imóvel: " .
            $titulo .
            "\n";


        $mensagem_email .=
            "ID do imóvel: " .
            $post_id .
            "\n";


        $mensagem_email .=
            "Categoria: " .
            $categoria .
            "\n";


        $mensagem_email .=
            "URL: " .
            $link_imovel .
            "\n\n";


        // =================================================
        // ANUNCIANTE
        // =================================================

        $mensagem_email .=
            "DADOS DO ANUNCIANTE\n";

        $mensagem_email .=
            "------------------------------\n";


        $mensagem_email .=
            "Contato: " .
            $dados_contato['nome'] .
            "\n";


        $mensagem_email .=
            "Fone: " .
            $dados_contato['fone'] .
            "\n";


        $mensagem_email .=
            "WhatsApp: " .
            $dados_contato['whatsapp'] .
            "\n";


        $mensagem_email .=
            "Autor ID: " .
            $autor_id .
            "\n\n";


        // =================================================
        // ACESSO
        // =================================================

        $mensagem_email .=
            "DADOS DO ACESSO\n";

        $mensagem_email .=
            "------------------------------\n";


        $mensagem_email .=
            "Data/Hora: " .
            mysql2date(
                'd/m/Y H:i:s',
                $data
            ) .
            "\n";


        $mensagem_email .=
            "IP: " .
            $ip .
            "\n";


        // =================================================
        // ENVIA EMAIL
        // =================================================

        wp_mail(
            $email_destino,
            $assunto,
            $mensagem_email
        );


        // =================================================
        // MONTA WHATSAPP DO ANUNCIANTE
        // =================================================

        $whatsapp_url =
            imu_get_whatsapp_real_url(
                $post_id,
                $nome
            );


        if ( empty( $whatsapp_url ) ) {
            return;
        }


        // =================================================
        // REDIRECIONAMENTO ELEMENTOR AJAX
        //
        // Não usamos wp_redirect() aqui porque o formulário
        // do Elementor é enviado via AJAX.
        //
        // O redirect_url é devolvido para o frontend do
        // Elementor.
        // =================================================

        $ajax_handler->add_response_data(
            'redirect_url',
            $whatsapp_url
        );

    },
    10,
    2
);


// =========================================================
// FORM WHATSAPP - SHOW / HIDE
// =========================================================

add_action( 'wp_footer', function() {

    if ( ! is_singular( 'imoveis' ) ) {
        return;
    }

    ?>

    <style>

    /* =====================================================
       FORMULÁRIO INICIALMENTE FECHADO
    ===================================================== */

    .imu-form-whatsapp {
        display: none;
        position: relative;
    }


    /* =====================================================
       FORMULÁRIO ABERTO
    ===================================================== */

    .imu-form-whatsapp.ativo {
        display: block;
    }


    /* =====================================================
       BOTÃO X
    ===================================================== */

    .imu-fechar-form-whatsapp {

        position: absolute;

        top: 5px;
        right: 5px;

        width: 30px;
        height: 30px;

        padding: 0;

        border: 0;

        background: transparent;

        font-size: 26px;

        line-height: 30px;

        cursor: pointer;

        z-index: 10;
    }

    </style>


    <script>

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            // =============================================
            // FORMULÁRIO
            // =============================================

            const form = document.querySelector(
                '.imu-form-whatsapp'
            );


            if (!form) {
                return;
            }


            // =============================================
            // CRIA BOTÃO X
            // =============================================

            const fechar = document.createElement(
                'button'
            );


            fechar.type =
                'button';


            fechar.className =
                'imu-fechar-form-whatsapp';


            fechar.innerHTML =
                '&times;';


            fechar.setAttribute(
                'aria-label',
                'Fechar formulário'
            );


            form.prepend(
                fechar
            );


            // =============================================
            // ABRIR / FECHAR AO CLICAR NO WHATSAPP
            // =============================================

            document.addEventListener(
                'click',
                function (event) {

                    const botao =
                        event.target.closest(
                            '.imu-abrir-form-whatsapp'
                        );


                    if (!botao) {
                        return;
                    }


                    event.preventDefault();


                    form.classList.toggle(
                        'ativo'
                    );

                }
            );


            // =============================================
            // BOTÃO X
            // =============================================

            fechar.addEventListener(
                'click',
                function () {

                    form.classList.remove(
                        'ativo'
                    );

                }
            );

        }
    );

    </script>

    <?php

}, 99);