<?php

/**
 * CONFIGURAÇÃO DOS SITES
 *
 * Edite este arquivo para adicionar/remover sites e ajustar seletores.
 */
$sites = [
    [
        "nome_site" => "Prime Imóveis - Locação",
        "usuario" => "imoveisunai",
        "cidade" => "Unaí",
        "uf" => "MG",
        "categoria" => "",
        "tags" => "",
        "contato" => "(38) 99970-6070",
        "periodo" => 30,
        "url" => [
            "https://primeimoveisunai.com.br/imoveis",
            "https://primeimoveisunai.com.br/imoveis/pagina/2",
            "https://primeimoveisunai.com.br/imoveis/negociacao/locacao"
        ],
        "numero_registros" => 20,
        "numero_maximo_por_url" => 10,
        "frequencia" => [
            "tipo" => "sempre",
            "horario_inicio" => "00:00",
            "horario_fim" => "02:00"
        ],
        "verificar_string" => "",
        "seletores" => [
            "card" => "//div[contains(@class,'property-main')]",
            "card_nome" => ".//h3[contains(@class,'property-title')]",
            "card_cidade" => "",
            "card_uf" => "",
            "card_contato" => "",
            "card_localizacao" => "",
            "preco" => ".//div[contains(@class,'property-price')]//span",
            "card_imagem_url" => ".//img[contains(@class,'img-fluid')]",
            "card_url" => ".//a",
            "galeria" => "//img[contains(@class,'img-fluid')]",
            "descricao" => "//div[contains(@class,'inner-box property-dsc')]"
        ]
    ],

    [
        "nome_site" => "Terra Fértil",
        "usuario" => "imoveisunai",
        "cidade" => "Unaí",
        "uf" => "MG",
        "categoria" => "",
        "tags" => "",
        "contato" => "(38) 99958-5454",
        "periodo" => 30,
        "url" => [
            "https://www.terrafertilimobiliaria.com.br/imoveis",
            "https://www.terrafertilimobiliaria.com.br/imoveis/a-venda",
            "https://www.terrafertilimobiliaria.com.br/imoveis/para-alugar",
            "https://www.terrafertilimobiliaria.com.br/imoveis/novos",
            "www.terrafertilimobiliaria.com.br/imoveis/a-venda/fazenda"
        ],
        "numero_registros" => 15,
        "numero_maximo_por_url" => 3,
        "frequencia" => [
            "tipo" => "sempre",
            "horario_inicio" => "02:00",
            "horario_fim" => "04:00"
        ],
        "verificar_string" => "",
        "seletores" => [
            "card" => "//a[contains(@class,'card-with-buttons') and contains(@class,'borderHover')]",
            "card_nome" => ".//p[contains(@class,'card-with-buttons__title')]",
            "card_cidade" => "",
            "card_uf" => "",
            "card_contato" => "",
            "card_localizacao" => ".//h2[contains(@class,'card-with-buttons__heading')]",
            "preco" => ".//*[contains(@class,'card-with-buttons__value')]",
            "card_imagem_url" => ".//li[contains(@class,'cards_digital_carousel-item-0')]//img",
            "card_url" => ".",
            "galeria" => "//div[contains(@class,'overflow-image-gallery')]//img",
            "descricao" => "//div[contains(@class,'details')]"
        ]
    ],

    [
        "nome_site" => "Sucesso Imóveis - Geral",
        "usuario" => "imoveisunai",
        "cidade" => "Unaí",
        "uf" => "MG",
        "categoria" => "",
        "tags" => "",
        "contato" => "(38) 99935-9555",
        "periodo" => 30,
        "url" => [
            "https://sucessoimoveis.imb.br/imoveis",
            "https://sucessoimoveis.imb.br/imoveis/page/2",
            "https://sucessoimoveis.imb.br/imoveis/page/3"
        ],
        "numero_registros" => 30,
        "numero_maximo_por_url" => 10,
        "frequencia" => [
            "tipo" => "horario",
            "horario_inicio" => "04:00",
            "horario_fim" => "06:00"
        ],
        "verificar_string" => "",
        "seletores" => [
            "card" => "//div[contains(@class,'g5ere__property-item-inner')]",
            "card_nome" => ".//h3[contains(@class,'g5ere__loop-property-title')]",
            "card_cidade" => "",
            "card_uf" => "",
            "card_contato" => "",
            "card_localizacao" => "",
            "preco" => ".//span[contains(@class,'g5ere__lpp-price')]",
            "card_imagem_url" => ".//div[contains(@class,'g5ere__property-featured')]//a[contains(@style,'background-image')]",
            "card_url" => ".//a[contains(@class,'g5core__entry-thumbnail')]",
            "galeria" => "//div[contains(@class,'g5core__entry-thumbnail')]//img",
            "descricao" => "//div[contains(@class,'g5ere__property-block-description')]"
        ]
    ],

    [
        "nome_site" => "Área 38",
        "usuario" => "imoveisunai",
        "cidade" => "Paracatu",
        "uf" => "MG",
        "categoria" => "",
        "tags" => "",
        "contato" => "(38) 3671-0038",
        "periodo" => 30,
        "url" => [
            "https://area38.com.br/busca?tipo=Fazenda",
        ],
        "numero_registros" => 5,
        "numero_maximo_por_url" => 5,
        "frequencia" => [
            "tipo" => "nunca"
        ],
        "verificar_string" => "",
        "seletores" => [
            "card" => "//a[contains(@class,'mb-2')]",
            "card_nome" => ".//h4[contains(@class,'text-lg')]",
            "card_cidade" => "",
            "card_uf" => "",
            "card_contato" => "",
            "card_localizacao" => ".//div[contains(@class,'container-endereco')]//span",
            "preco" => ".//h5[contains(@class,'text-lg')]",
            "card_imagem_url" => ".//img[contains(@class,'w-full')]",
            "card_url" => ".",
            "galeria" => "//img[contains(@class,'transition-all')]",
            "descricao" => "//p[contains(@class,'my-5')]//span"
        ]
    ],

    [
        "nome_site" => "Morado Imóveis",
        "usuario" => "imoveisunai",
        "cidade" => "Paracatu",
        "uf" => "MG",
        "categoria" => "",
        "tags" => "",
        "contato" => "(38) 99856-5306",
        "periodo" => 30,
        "url" => [
            "https://moradoimoveis.com.br/busca",
            "https://moradoimoveis.com.br/imoveis/aluguel",
            "https://moradoimoveis.com.br/imoveis/venda",
            "https://moradoimoveis.com.br/imoveis/venda/fazenda"
        ],
        "numero_registros" => 48,
        "numero_maximo_por_url" => 12,
        "frequencia" => [
            "tipo" => "horario",
            "horario_inicio" => "23:00",
            "horario_fim" => "12:00"
        ],
        "verificar_string" => "",
        "seletores" => [
            "card" => "//div[contains(@class,'ImovelItem')]",
            "card_nome" => ".//a[contains(@class,'Title')]",
            "card_cidade" => "",
            "card_uf" => "",
            "card_contato" => "",
            "card_localizacao" => ".//div[contains(@class,'container-endereco')]//span",
            "preco" => ".//span[contains(@class,'ValorMoeda')]",
            "card_imagem_url" => ".//img[contains(@class,'BannerImage')]",
            "card_url" => ".//a",
            "galeria" => "//div[contains(@class,'ms-lightbox') and @data-img]",
            "descricao" => "//div[contains(@class,'central_left')]"
        ]
    ],

    [
        "nome_site" => "Novo Lar",
        "usuario" => "imoveisunai",
        "cidade" => "Unaí",
        "uf" => "MG",
        "categoria" => "",
        "tags" => "",
        "contato" => "(38) 99879-9441",
        "periodo" => 30,
        "url" => [
            "https://novolarimobiliariaunai.com.br/imoveis/",
            "https://novolarimobiliariaunai.com.br/imoveis/chacara",
            "https://novolarimobiliariaunai.com.br/imoveis/fazenda",
            "https://moradoimoveis.com.br/imoveis/venda/sitio"
        ],
        "numero_registros" => 40,
        "numero_maximo_por_url" => 12,
        "frequencia" => [
            "tipo" => "horario",
            "horario_inicio" => "04:00",
            "horario_fim" => "06:00"
        ],
        "verificar_string" => "",
        "seletores" => [
            "card" => "//a[div[contains(@class,'resultado')]]",
            "card_nome" => ".//h3[contains(@class,'tipo')]",
            "card_cidade" => "",
            "card_uf" => "",
            "card_contato" => "",
            "card_localizacao" => ".//div[contains(@class,'container-endereco')]//span",
            "preco" => ".//div[contains(@class,'valor')]//h5",
            "card_imagem_url" => ".//div[contains(@class,'foto')]//img",
            "card_url" => ".",
            "galeria" => "//div[contains(@class,'fotorama')]//img",
            "descricao" => "//div[contains(@class,'descricao_imovel')]"
        ]
    ]
];
