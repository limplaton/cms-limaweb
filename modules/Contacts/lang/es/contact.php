<?php
 

return [
    'contact' => 'Contacto',
    'contacts' => 'Contactos',
    'convert' => 'Convertir en contacto',
    'create' => 'Crear contacto',
    'add' => 'Agregar contacto',
    'total' => 'Total de contactos',
    'import' => 'Importar Contactos',
    'export' => 'Exportar Contactos',
    'no_companies_associated' => 'El contacto no tiene empresas asociadas.',
    'no_deals_associated' => 'El contacto no tiene ninguna oferta asociada.',
    'works_at' => ':job_title en :company',
    'create_with' => 'Crear contacto con :name',
    'associate_with' => 'Asociar contacto con :name',
    'associated_company' => 'Empresa de contacto asociada',
    'dissociate' => 'Disociar el contacto',
    'exists_in_trash_by_email' => 'El contacto con esta dirección de correo electrónico ya existe en la papelera, no podrá crear un nuevo contacto con la misma dirección de correo electrónico, ¿desea restaurar el contacto eliminado?',
    'associate_field_info' => 'Utilice este campo para buscar y asociar un contacto existente en lugar de crear uno nuevo.',
    'cards' => [
        'recently_created' => 'Contactos creados recientemente',
        'recently_created_info' => 'Muestra el :total de contactos creados en los últimos :days, ordenados por el más reciente en la parte superior.',
        'by_day' => 'Contactos por día',
        'by_source' => 'Contactos por origen',
    ],
    'count' => [
        'all' => '1 contacto | :count contactos',
    ],
    'notifications' => [
        'assigned' => 'Ha sido asignado a un contacto :name por :user',
    ],
    'filters' => [
        'my' => 'Mis contactos',
        'my_recently_assigned' => 'Mis contactos asignados recientemente',
    ],
    'mail_placeholders' => [
        'assigneer' => 'Nombre del usuario que asignó el contacto',
    ],
    'workflows' => [
        'triggers' => [
            'created' => 'Contacto creado',
        ],
        'actions' => [
            'fields' => [
                'email_to_contact' => 'Correo electrónico de contacto',
                'email_to_owner_email' => 'Correo electrónico del propietario',
                'email_to_creator_email' => 'Correo electrónico del creador',
                'email_to_company' => 'Contactar con la empresa principal',
            ],
        ],
    ],
    'validation' => [
        'email' => [
            'unique' => 'Ya existe un contacto o miembro del equipo con este correo electrónico.',
        ],
        'phone' => [
            'unique' => 'Ya existe un contacto con este número de teléfono.',
        ],
    ],
];
