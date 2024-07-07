<?php
 

return [
    'company' => 'Empresa',
    'companies' => 'Empresas',
    'add' => 'Agregar empresa',
    'dissociate' => 'Empresa disociada',
    'child' => 'Empresa infantil | Empresas infantiles',
    'create' => 'Crear empresa',
    'export' => 'Exportar empresas',
    'total' => 'Total de empresas',
    'import' => 'Importar empresas',
    'create_with' => 'Crear empresa con :name',
    'associate_with' => 'Asociar empresa con :name',
    'associate_field_info' => 'Utilice este campo para buscar y asociar empresas existentes en lugar de crear una nueva.',
    'no_contacts_associated' => 'La empresa no tiene contactos asociados.',
    'no_deals_associated' => 'La empresa no tiene acuerdos asociados.',
    'exists_in_trash_by_email' => 'La empresa con esta dirección de correo electrónico ya existe en la papelera, no podrá crear una nueva empresa con la misma dirección de correo electrónico, ¿desea restaurar la empresa eliminada?',
    'exists_in_trash_by_name' => 'Ya existe una empresa con el mismo nombre en la papelera, ¿desea restaurar la empresa eliminada?',
    'count' => [
        'all' => '1 empresas | :count empresas',
    ],
    'notifications' => [
        'assigned' => 'Has sido asignado a una empresa :name por :user',
    ],
    'cards' => [
        'by_source' => 'Empresas por procedencia',
        'by_day' => 'Empresas por día',
    ],
    'settings' => [
        'automatically_associate_with_contacts' => 'Crear y asociar automáticamente empresas a contactos',
        'automatically_associate_with_contacts_info' => 'Asocie automáticamente contactos con empresas basándose en una dirección de correo electrónico de contacto y un dominio de empresa.',
    ],
    'industry' => [
        'industries' => 'Sectores',
        'industry' => 'Sector',
    ],
    'filters' => [
        'my' => 'Mis empresas',
        'my_recently_assigned' => 'Mis empresas asignadas recientemente',
    ],
    'mail_placeholders' => [
        'assigneer' => 'Nombre del usuario que asignó la empresa',
    ],
    'workflows' => [
        'triggers' => [
            'created' => 'Empresa creada',
        ],
        'actions' => [
            'fields' => [
                'email_to_company' => 'Correo electrónico de empresa',
                'email_to_owner_email' => 'Correo electrónico del propietario',
                'email_to_creator_email' => 'Correo electrónico del creador',
                'email_to_contact' => 'Contacto principal de la empresa',
            ],
        ],
    ],
    'validation' => [
        'email' => [
            'unique' => 'Ya existe una empresa con este correo electrónico.',
        ],
    ],
];
