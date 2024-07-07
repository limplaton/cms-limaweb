<?php
 

return [
    'create' => 'Crear flujo de trabajo',
    'workflows' => 'Flujos de trabajo',
    'title' => 'Título',
    'description' => 'Descripción',
    'created' => 'Flujo de trabajo creado correctamente',
    'updated' => 'Flujo de trabajo actualizado correctamente',
    'deleted' => 'Flujo de trabajo eliminado correctamente',
    'when' => 'Cuando',
    'then' => 'Luego',
    'field_change_to' => 'Para',
    'total_executions' => 'Ejecuciones: :total',
    'info' => 'La herramienta de flujos de trabajo automatiza sus procesos de ventas. Los procesos internos que se pueden automatizar incluyen la creación de actividades, el envío de correos electrónicos, la activación de solicitudes HTTP, etc.',
    'validation' => [
        'invalid_webhook_url' => 'La URL del webhook no debe comenzar con "https://" o "http://"',
    ],
    'actions' => [
        'webhook' => 'Activar webhook',
        'webhook_url_info' => 'Debe ser una URL completa, válida y de acceso público.',
    ],
    'fields' => [
        'with_header_name' => 'Con nombre de encabezado (opcional)',
        'with_header_value' => 'Con valor de encabezado (opcional)',
        'for_owner' => 'Para: Titular (persona responsable)',
        'dates' => [
            'now' => 'Con fecha de vencimiento: en este momento',
            'in_1_day' => 'Con fecha de vencimiento: en un día',
            'in_2_days' => 'Con fecha de vencimiento: en dos días',
            'in_3_days' => 'Con fecha de vencimiento: en tres días',
            'in_4_days' => 'Con fecha de vencimiento: en cuatro días',
            'in_5_days' => 'Con fecha de vencimiento: en cinco días',
        ],
    ],
];
