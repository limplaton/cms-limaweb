<?php
 

return [
    'company' => 'Empresa',
    'companies' => 'Empresas',
    'add' => 'Adicionar Empresa',
    'dissociate' => 'Dissociar Empresa',
    'child' => 'Empresa Filha | Empresas Filha',
    'create' => 'Criar Empresa',
    'export' => 'Exportar Empresas',
    'total' => 'Total de Empresas',
    'import' => 'Importar Empresas',
    'create_with' => 'Criar Empresa com :name',
    'associate_with' => 'Associar Empresa com :name',
    'associate_field_info' => 'Use este campo para localizar e associar uma empresa existente em vez de criar uma nova.',
    'no_contacts_associated' => 'A empresa não possui contatos associados.',
    'no_deals_associated' => 'A empresa não tem negócios associados.',
    'exists_in_trash_by_email' => 'A empresa com este endereço de e-mail já existe na lixeira, você não poderá criar uma nova empresa com o mesmo endereço de e-mail, gostaria de restaurar a empresa na lixeira?',
    'exists_in_trash_by_name' => 'Já existe uma empresa com o mesmo nome na lixeira. Deseja restaurar a empresa descartada?',
    'possible_duplicate' => 'Possível duplicata de empresa :display_name.',
    'count' => [
        'all' => '1 empresa | :count empresas',
    ],
    'notifications' => [
        'assigned' => 'Você foi atribuído a empresa :name por :user',
    ],
    'cards' => [
        'by_source' => 'Empresas por fonte',
        'by_day' => 'Empresas por dia',
    ],
    'settings' => [
        'automatically_associate_with_contacts' => 'Crie e associe automaticamente empresas a contatos',
        'automatically_associate_with_contacts_info' => 'Associe automaticamente contatos a empresas com base em um endereço de e-mail de contato e um domínio da empresa.',
    ],
    'industry' => [
        'industries' => 'Indústrias',
        'industry' => 'Indústria',
    ],
    'filters' => [
        'my' => 'Minhas Empresas',
        'my_recently_assigned' => 'Minhas Empresas Recentemente Designadas',
    ],
    'mail_placeholders' => [
        'assigneer' => 'O nome de usuário que atribuiu a empresa',
    ],
    'workflows' => [
        'triggers' => [
            'created' => 'Empresa Criada',
        ],
        'actions' => [
            'fields' => [
                'email_to_company' => 'E-mail da empresa',
                'email_to_owner_email' => 'E-mail do proprietário da empresa',
                'email_to_creator_email' => 'E-mail do criador da empresa',
                'email_to_contact' => 'Contato principal da empresa',
            ],
        ],
    ],
    'validation' => [
        'email' => [
            'unique' => 'Já existe uma empresa com este e-mail.',
        ],
    ],
    'empty_state' => [
        'title' => 'Você não criou nenhuma empresa.',
        'description' => 'Comece criando uma nova empresa.',
    ],
    'timeline' => [
        'deleted' => 'A empresa foi excluída por :causer',
        'restored' => 'A empresa foi restaurada da lixeira por :causer',
        'created' => 'A empresa foi criada por :causer',
        'updated' => 'A empresa foi atualizada por :causer',
        'attached' => 'Empresa associada a :user',
        'detached' => 'Empresa dissociada de :user',
        'associate_trashed' => 'O :companyName associado da empresa foi movido para a lixeira por :user',
    ],
];
