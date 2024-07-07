<?php
 

return [
    'brand' => 'Marca',
    'brands' => 'Marcas',
    'create' => 'Criar marca',
    'update' => 'Atualizar marca',
    'form' => [
        'sections' => [
            'general' => 'Em geral',
            'navigation' => 'Navegação',
            'email' => 'E-mail',
            'thank_you' => 'Obrigado',
            'signature' => 'Assinatura',
            'pdf' => 'PDF',
        ],
        'is_default' => 'Esta é a marca padrão da empresa?',
        'name' => 'Como você se refere a esta marca internamente?',
        'display_name' => 'Como você quer que seja exibido para seus clientes?',
        'primary_color' => 'Escolha a cor primária da marca',
        'upload_logo' => 'Envie o logotipo da sua empresa',
        'navigation' => [
            'upload_logo_info' => 'Se você tiver um fundo escuro, use um logotipo claro. Se você estiver usando uma cor de fundo clara, use um logotipo com texto escuro.',
        ],
        'pdf' => [
            'default_font' => 'Família de fontes padrão',
            'default_font_info' => 'The :fontName fonte fornece a cobertura de caracteres Unicode mais decente por padrão, certifique-se de selecionar uma fonte adequada se caracteres especiais ou unicode não forem exibidos corretamente no documento PDF.',
            'size' => 'Tamanho',
            'orientation' => 'Orientação',
        ],
        'email' => [
            'upload_logo_info' => 'Certifique-se de que o logotipo é adequado para um fundo branco, se nenhum logotipo for carregado, o logotipo escuro carregado nas configurações gerais será usado.',
        ],
        'document' => [
            'send' => [
                'info' => 'Ao enviar um documento',
                'subject' => 'Assunto padrão',
                'message' => 'Mensagem de e-mail padrão quando você está enviando um documento',
                'button_text' => 'Texto do botão de e-mail',
            ],
            'sign' => [
                'info' => 'Quando alguém assina seu documento',
                'subject' => 'Linha de assunto padrão para e-mail de agradecimento',
                'message' => 'Mensagem de e-mail a ser enviada quando alguém assinar seu documento',
                'after_sign_message' => 'Depois de assinar, o que a mensagem deve dizer?',
            ],
            'accept' => [
                'after_accept_message' => 'Depois de aceitar (sem assinatura digital), o que a mensagem deve dizer?',
            ],
        ],
        'signature' => [
            'bound_text' => 'Limite legal do texto',
        ],
    ],
    'delete_documents_usage_warning' => 'A marca já está associada a documentos, portanto, não pode ser excluída.',
    'created' => 'Marca criada com sucesso.',
    'updated' => 'Marca atualizada com sucesso.',
    'deleted' => 'Marca excluída com sucesso.',
];
