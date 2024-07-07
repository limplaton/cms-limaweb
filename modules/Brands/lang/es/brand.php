<?php
 

return [
    'brand' => 'Marca',
    'brands' => 'Marcas',
    'create' => 'Crear marca',
    'update' => 'Actualizar marca',
    'form' => [
        'sections' => [
            'general' => 'General',
            'navigation' => 'Navegación',
            'email' => 'Email',
            'thank_you' => 'Gracias',
            'signature' => 'Firma',
            'pdf' => 'PDF',
        ],
        'is_default' => '¿Esta es la marca por defecto de la empresa?',
        'name' => '¿Cómo se refieren internamente a esta marca?',
        'display_name' => '¿Cómo quiere que se muestre a sus clientes?',
        'primary_color' => 'Elija el color principal de la marca',
        'upload_logo' => 'Cargue el logo de su empresa',
        'navigation' => [
            'upload_logo_info' => 'Si tiene un fondo oscuro, utilice un logo claro. Si tiene un fondo claro, utilice un logo con texto oscuro.',
        ],
        'pdf' => [
            'default_font' => 'Familia de fuentes predeterminada',
            'default_font_info' => 'La fuente :fontName proporciona la cobertura de caracteres Unicode más decente por defecto, asegúrese de seleccionar una fuente adecuada si los caracteres especiales o Unicode no se muestran correctamente en el documento PDF.',
            'size' => 'Tamaño',
            'orientation' => 'Orientación',
        ],
        'email' => [
            'upload_logo_info' => 'Asegúrese de que el logo es adecuado para un fondo blanco; si no se carga ningún logo, se utilizará en su lugar el logo oscuro cargado en Configuración general.',
        ],
        'document' => [
            'send' => [
                'info' => 'Al enviar un documento',
                'subject' => 'Tema predeterminado',
                'message' => 'Mensaje de correo electrónico predeterminado al enviar un documento',
                'button_text' => 'Texto del botón Email',
            ],
            'sign' => [
                'info' => 'Cuando alguien firma su documento',
                'subject' => 'Asunto predeterminado del correo electrónico de agradecimiento',
                'message' => 'Mensaje de correo electrónico para enviar cuando alguien firma su documento',
                'after_sign_message' => 'Después de firmar, ¿qué debe decir el mensaje?',
            ],
            'accept' => [
                'after_accept_message' => 'Después de aceptar (sin firma digital), ¿qué debe decir el mensaje?',
            ],
        ],
        'signature' => [
            'bound_text' => 'Texto encuadernado legalmente',
        ],
    ],
    'delete_documents_usage_warning' => 'La marca ya está asociada a los documentos, por lo que no puede eliminarse.',
    'created' => 'Marca creada con éxito.',
    'updated' => 'Marca actualizada con éxito.',
    'deleted' => 'Marca eliminada con éxito',
];
