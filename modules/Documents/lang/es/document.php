<?php
 

return [
    'document' => 'Documento',
    'documents' => 'Documentos',
    'create' => 'Crear documentos',
    'info' => 'Envíe cotizaciones, propuestas y contratos personalizables para cerrar negocios más rápido.',
    'view' => 'Vista',
    'manage_documents' => 'Gestionar documentos',
    'total_documents' => 'Total de documentos',
    'total_draft_documents' => 'Total de borradores de documentos',
    'deleted' => 'Documento eliminado correctamente',
    'document_details' => 'Detalles del documento',
    'document_activity' => 'Actividad del documento',
    'document_products' => 'Documento de productos',
    'download_pdf' => 'Descargar PDF',
    'view_pdf' => 'Ver PDF en el navegador',
    'accept' => 'Aceptar',
    'sign' => 'Registrar',
    'sent' => 'Documento enviado correctamente',
    'deal_description' => 'Cuando se selecciona una oferta, ésta se asocia automáticamente al documento, los contactos de la oferta se añaden como firmantes y todos los productos de la oferta se añaden al documento.',
    'limited_editing' => 'Este documento es aceptado, las capacidades de edición son limitadas.',
    'title' => 'Título',
    'copy_url' => 'Copiar la URL pública del documento',
    'url_copied' => 'URL copiada al portapapeles',
    'count' => [
        'all' => '1 documento | :count documentos',
    ],
    'sections' => [
        'details' => 'Más información',
        'send' => 'Enviar',
        'signature' => 'Firma',
        'content' => 'Contenido',
        'products' => 'Productos',
    ],
    'status' => [
        'status' => 'Estado',
        'draft' => 'Borrador',
        'sent' => 'Enviado',
        'accepted' => 'Aceptado',
        'lost' => 'Perdido',
    ],
    'send' => [
        'select_brand' => 'Seleccione primero una marca para enviar el documento.',
        'connect_an_email_account' => 'Conecta una cuenta de correo para enviar documentos.',
        'send_from_account' => 'Enviar el documento desde la siguiente cuenta',
        'save_to_schedule' => 'Para programar el envío de documentos, primero deberá guardar el documento',
        'send_subject' => 'Asunto del mensaje',
        'send_body' => 'Mensaje de texto',
        'send_later' => '¿Enviar más tarde?',
        'send' => 'Enviar documento',
        'select_schedule_date' => 'Escoger fecha y hora',
        'schedule' => 'Calendario',
        'is_scheduled' => 'Este documento está programado para ser enviado el :date',
        'send_to_signers' => 'Enviar el documento a los siguientes participantes',
        'send_to_signers_empty' => 'Para enviar el documento a los participantes, añada participantes a través de la sección "Participante".',
    ],
    'sent_by' => 'Enviado por',
    'sent_at' => 'Enviado el :date',
    'signers' => [
        'add' => 'Agregar nuevo titular',
        'no_signers' => 'Sin participantes, agregue participantes para este documento',
        'is_signed' => '¿Firmado?',
        'document_signers' => 'Documentos firmados',
        'signer_name' => 'Nombre',
        'signer_email' => 'Dirección de correo electrónico',
        'signature_date' => 'Fecha',
        'name' => 'Nombre del titular',
        'email' => 'Correo electrónico del titular',
        'enter_full_name' => 'Ingrese el nombre completo del titular',
        'enter_email' => 'Por favor, ingrese su correo electrónico',
        'confirm_email' => 'Confirme su correo electrónico',
    ],
    'accepted_at' => 'Aceptado en',
    'signature' => [
        'no_signature' => 'Sin firma',
        'no_signature_description' => 'Este documento no requiere una firma antes de su aceptación.',
        'e_signature' => 'Usar la firma electrónica',
        'e_signature_description' => 'Este documento requiere firma electrónica antes de su aceptación.',
        'signature' => 'Firma',
        'signatures' => 'Firmas',
        'signed_on' => 'Firmado',
        'sign_ip' => 'Dirección de IP',
        'verification_failed' => 'No hemos podido verificar su dirección de correo electrónico como titular, póngase en contacto con la persona que le envió el documento para que le facilite información sobre la dirección de correo electrónico utilizada.',
        'accept_name' => 'Para aceptar, escriba su nombre a continuación',
    ],
    'reactivated' => 'Documento reactivado',
    'marked_as_lost' => 'Documento marcado correctamente como perdido',
    'marked_as_accepted' => 'Documento marcado correctamente como aceptado',
    'actions' => [
        'mark_as_lost' => 'Marcar como perdido',
        'mark_as_lost_message' => 'Esta acción marcará este documento como perdido y ninguno de los destinatarios podrá acceder a él.',
        'mark_as_accepted' => 'Marcar como aceptado',
        'reactivate' => 'Reactivar',
        'undo_acceptance' => 'Deshacer la confirmación',
    ],
    'cards' => [
        'by_type' => 'Documentos por tipo',
        'by_status' => 'Documentos por estado',
        'sent_by_day' => 'Documentos enviados por día',
    ],
    'recipients' => [
        'add' => 'Añadir nuevo destinatario',
        'enter_full_name' => 'Introduzca el nombre completo del destinatario',
        'enter_email' => 'Introduzca la dirección de correo electrónico del destinatario',
        'no_recipients' => 'No hay destinatarios para enviar el documento.',
        'is_sent' => '¿Enviado?',
        'recipients' => 'Destinatarios',
        'recipient_name' => 'Nombre',
        'recipient_email' => 'Dirección de correo electrónico',
        'name' => 'Nombre del destinatario',
        'email' => 'Correo electrónico del destinatario',
    ],
    'view_type' => [
        'html_view_type' => 'Vista HTML',
        'template_info' => 'Cuando una plantilla tiene un tipo de vista, después de insertarse, el tipo de vista del documento se actualizará con el tipo de plantilla.',
        'nav_top' => [
            'name' => 'Navegación hacia arriba',
            'description' => 'Útil para documentos simples que no requieren navegación a través de encabezados.',
        ],
        'nav_left' => [
            'name' => 'Navegación a la izquierda',
            'description' => 'Útil para documentos que requieren navegación a través de encabezados (:headingTagName).',
        ],
        'nav_left_full_width' => [
            'name' => 'Navegación a la izquierda - Ancho completo',
            'description' => 'La sección de contenido no tiene margen, útil para documentos de ancho completo con encabezados (:headingTagName).',
        ],
    ],
    'type' => [
        'type' => 'Tipo de documento',
        'types' => 'Tipos de documentos',
        'name' => 'Nombre',
        'default_type' => 'Tipo de documento predeterminado',
        'delete_primary_warning' => 'No puede eliminar el tipo de documento principal.',
        'delete_usage_warning' => 'Este tipo ya está asociado a los documentos, por lo que no puede eliminarse.',
        'delete_is_default' => 'Este es un tipo de documento predeterminado, por lo tanto, no se puede eliminar.',
    ],
    'template' => [
        'insert_template' => 'Insertar plantilla',
        'save_as_template' => 'Guardar como plantilla',
        'manage' => 'Gestionar plantillas',
        'template' => 'Plantilla de documento',
        'templates' => 'Plantillas de documento',
        'create' => 'Crear plantilla',
        'name' => 'Nombre de plantilla',
        'deleted' => 'Plantilla eliminada correctamente',
        'share_with_team_members' => '¿Compartir esta plantilla con otros miembros del equipo?',
        'is_shared' => 'Compartido',
    ],
    'workflows' => [
        'triggers' => [
            'status_changed' => 'Estado del documento modificado',
        ],
        'actions' => [
            'fields' => [
                'email_to_contact' => 'Documento de contacto principal',
                'email_to_company' => 'Documento de empresa principal',
                'email_to_owner_email' => 'Correo electrónico del propietario del documento',
                'email_to_creator_email' => 'Correo electrónico del creador del documento',
            ],
        ],
    ],
    'timeline' => [
        'heading' => 'Documento creado',
    ],
    'mail_placeholders' => [
        'assigneer' => 'Nombre del usuario que asignó el documento',
    ],
    'notifications' => [
        'signed' => 'El documento :title ha sido firmado',
        'assigned' => 'Ha sido asignado al documento :title por :user',
        'accepted' => 'El documento :title ha sido aceptado',
        'viewed' => 'El documento :title ha sido visto',
    ],
    'filters' => [
        'status_disabled' => 'El formato se utiliza en el filtro actual, por lo que no puede seleccionar el formato en esta sección.',
    ],
    'activity' => [
        'created' => 'El documento ha sido creado por :user',
        'sent' => 'El documento ha sido enviado :user',
        'marked_as_lost' => ':user marcó el documento como perdido',
        'marked_as_accepted' => ':user marcó el documento como aceptado',
        'marked_as_draft' => ':user marcó el documento como borrado',
        'sent_recipient' => ':name - :email',
        'signed' => 'El documento ha sido firmado por :signer_name',
        'accepted' => 'El documento ha sido aceptado',
        'viewed' => 'El documento ha sido revisado',
        'downloaded' => 'El documento PDF fue descargado',
    ],
];