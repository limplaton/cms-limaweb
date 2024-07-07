<?php
 

return [
    'company' => 'Компания',
    'companies' => 'Компании',
    'add' => 'Добавить компанию',
    'dissociate' => 'Отвязать компанию',
    'child' => 'Дочерняя компания | Дочерние компании',
    'create' => 'Создать компанию',
    'export' => 'Экспорт компаний',
    'total' => 'Всего компаний',
    'import' => 'Импорт компаний',
    'create_with' => 'Создать компанию с :name',
    'associate_with' => 'Связать компанию с :name',
    'associate_field_info' => 'Используйте это поле, чтобы найти и связать существующую компанию вместо создания новой.',
    'no_contacts_associated' => 'Компания не имеет связанных контактов.',
    'no_deals_associated' => 'Компания не имеет связанных сделок.',
    'exists_in_trash_by_email' => 'Компания с таким адресом электронной почты уже существует в корзине, вы не сможете создать новую компанию с таким же адресом электронной почты, вы хотите восстановить удаленную компанию?',
    'exists_in_trash_by_name' => 'Компания с таким названием уже существует в корзине, восстановить удаленную компанию?',
    'exists_in_trash_by_phone' => 'Компания (:company) со следующими номерами: :phone_numbers, уже есть в корзине, восстановить удаленную компанию?',
    'possible_duplicate' => 'Возможный дубликат компании :display_name.',
    'count' => [
        'all' => '1 компания | :count компании',
    ],
    'notifications' => [
        'assigned' => 'Вы были назначены компании :name пользователем :user',
    ],
    'cards' => [
        'by_source' => 'Компании по источнику',
        'by_day' => 'Компании по дням',
    ],
    'settings' => [
        'automatically_associate_with_contacts' => 'Автоматически создавать и связывать компании с контактами',
        'automatically_associate_with_contacts_info' => 'Автоматически связывайте контакты с компаниями на основе контактного адреса электронной почты и домена компании.',
    ],
    'industry' => [
        'industries' => 'Отрасли',
        'industry' => 'Отрасль',
    ],
    'filters' => [
        'my' => 'Мои компании',
        'my_recently_assigned' => 'Мои недавно назначенные компании',
    ],
    'mail_placeholders' => [
        'assigneer' => 'Имя пользователя, которому присвоена компания',
    ],
    'workflows' => [
        'triggers' => [
            'created' => 'Компания создана',
        ],
        'actions' => [
            'fields' => [
                'email_to_company' => 'Компания email',
                'email_to_owner_email' => 'Компания ответственный email',
                'email_to_creator_email' => 'Компания создать email',
                'email_to_contact' => 'Компания основной контакт',
            ],
        ],
    ],
    'validation' => [
        'email' => [
            'unique' => 'A company with this email already exist.',
        ],
    ],
    'empty_state' => [
        'title' => 'Вы не создали ни одной компании.',
        'description' => 'Начните с создания новой компании.',
    ],
];
