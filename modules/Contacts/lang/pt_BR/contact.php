<?php
 

return [
    'contact' => 'Contato',
    'contacts' => 'Contatos',
    'convert' => 'Converter para Contato',
    'create' => 'Criar Contato',
    'add' => 'Adicionar Contato',
    'total' => 'Total de Contatos',
    'import' => 'Importar Contatos',
    'export' => 'Exportar Contatos',
    'no_companies_associated' => 'O contato não possui empresas associadas.',
    'no_deals_associated' => 'O contato não tem negócios associados.',
    'works_at' => ':job_title na :company',
    'create_with' => 'Criar contato com :name',
    'associate_with' => 'Associar contato com :name',
    'associated_company' => 'Empresa de contato associada',
    'dissociate' => 'Dissociar contato',
    'exists_in_trash_by_email' => 'O contato com este endereço de e-mail já existe na lixeira, você não poderá criar um novo contato com o mesmo endereço de e-mail, gostaria de restaurar o contato na lixeira?',
    'possible_duplicate' => 'Possível duplicata de contato :display_name.',
    'associate_field_info' => 'Use este campo para localizar e associar um contato existente em vez de criar um novo.',
    'cards' => [
        'recently_created' => 'Contatos criados recentemente',
        'recently_created_info' => 'Mostrando os últimos :total de contatos criados nos últimos :days dias, classificados por mais novos no topo.',
        'by_day' => 'Contatos por dia',
        'by_source' => 'Contatos por fonte',
    ],
    'count' => [
        'all' => '1 contato | :count contatos',
    ],
    'notifications' => [
        'assigned' => 'Você foi atribuído ao contato :name por :user',
    ],
    'filters' => [
        'my' => 'Meus Contatos',
        'my_recently_assigned' => 'Meus Contatos Recentemente Atribuídos',
    ],
    'mail_placeholders' => [
        'assigneer' => 'O nome de usuário que atribuiu o contato',
    ],
    'workflows' => [
        'triggers' => [
            'created' => 'Contato Criado',
        ],
        'actions' => [
            'fields' => [
                'email_to_contact' => 'E-mail do Contato',
                'email_to_owner_email' => 'Contato de e-mail do proprietário',
                'email_to_creator_email' => 'Contato de e-mail do criador',
                'email_to_company' => 'Empresa principal do contato',
            ],
        ],
    ],
    'validation' => [
        'email' => [
            'unique' => 'Já existe um contato ou membro da equipe com este e-mail.',
        ],
        'phone' => [
            'unique' => 'Já existe um contato com este número de telefone.',
        ],
    ],
    'empty_state' => [
        'title' => 'Você não criou nenhum contato.',
        'description' => 'Comece a organizar as pessoas agora.',
    ],
    'timeline' => [
        'deleted' => 'O contato foi deletado por :causer',
        'restored' => 'O contato foi restaurado da lixeira por :causer',
        'created' => 'O contato foi criado por :causer',
        'updated' => 'O contato foi atualizado por :causer',
        'imported_via_calendar_attendee' => 'Contato importado via calendário de :user porque foi adicionado como participante de um evento.',
        'attached' => ':user associou um contato',
        'detached' => ':user dissociou um contato',
        'associate_trashed' => 'O contato :contactName associado foi movido para a lixeira por :user',
    ],
];
