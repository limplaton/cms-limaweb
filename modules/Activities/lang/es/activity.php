<?php
 

return [
    'activities' => 'Actividades',
    'activity' => 'Actividad',
    'add' => 'Agregar Actividad',
    'description' => 'Descripción',
    'description_info' => 'La descripción es visible para todos los invitados',
    'note' => 'Nota',
    'note_info' => 'Las notas son privadas y visibles solo para los representantes de ventas.',
    'title' => 'Título',
    'due_date' => 'Fecha de vencimiento',
    'end_date' => 'Fecha final',
    'create' => 'Crear actividad',
    'download_ics' => 'Descargar archivo .ics',
    'created' => 'Actividad creada correctamente',
    'updated' => 'Actividad actualizada correctamente',
    'deleted' => 'Actividad eliminada correctamente',
    'export' => 'Exportar actividades',
    'import' => 'Importar actividades',
    'guests' => 'Invitados',
    'guest' => 'Invitado',
    'count_guests' => '1 invitado | :count invitados',
    'create_follow_up_task' => 'Crear tarea de seguimiento',
    'follow_up_with_title' => 'Seguimiento de :with',
    'title_via_create_message' => 'Con respecto al correo electrónico: :subject',
    'reminder_update_info' => 'Debido a que el recordatorio de esta actividad ya ha sido enviado, deberá actualizar la fecha de vencimiento para que se envíe un nuevo recordatorio a la fila.',
    'owner_assigned_date' => 'Fecha de asignación del propietario',
    'reminder_sent_date' => 'Fecha de envío del recordatorio',
    'reminder' => 'Recordatorio',
    'owner' => 'Propietario',
    'mark_as_completed' => 'Marcar como completado',
    'mark_as_incomplete' => 'Marcar como incompleto',
    'is_completed' => 'Está completado',
    'completed_at' => 'Completado en',
    'overdue' => 'Atrasado',
    'doesnt_have_activities' => 'Sin actividades',
    'count' => 'Sin actividades | 1 Actividad | :count Actividades',
    'incomplete_activities' => 'Actividades incompletas',
    'activity_was_due' => 'Esta actividad venció el :date',

    'next_activity_date' => 'Fecha de la próxima actividad',
    'next_activity_date_info' => 'Este campo es de sólo lectura y se actualiza automáticamente en función de las próximas actividades del registro, indica cuándo debe realizarse la siguiente acción del representante de ventas.',

    'cards' => [
        'my_activities' => 'Mis actividades',
        'my_activities_info' => 'Estas tarjetas reflejan las actividades que has agregado como propietario',
        'created_by_agent' => 'Actividades creadas por el agente de ventas',
        'created_by_agent_info' => 'Mira el número de actividades que crea cada agente de ventas. Mira quién crea más actividades y quién crea menos.',
        'upcoming' => 'Próximas actividades',
        'upcoming_info' => 'Esta tarjeta refleja las próximas actividades y la que está atendiendo.',
    ],
    'type' => [
        'default_type' => 'Tipo de actividad predeterminado',
        'delete_primary_warning' => 'No se puede eliminar la clase de actividad primaria.',
        'delete_usage_warning' => 'El tema ya está asociado a las actividades, por lo que no se puede eliminar.',
        'delete_usage_calendars_warning' => 'Este formato se utiliza por defecto al crear actividades a través de calendarios conectados, por lo que no se puede eliminar.',
        'delete_is_default' => 'Este es un tipo de evento predeterminado, por lo tanto, no se puede eliminar.',
        'type' => 'Tipo de evento',
        'types' => 'Tipos de eventos',
        'name' => 'Nombre',
        'icon' => 'Icono',
    ],
    'filters' => [
        'activity_type_disabled' => 'La clase de actividad se utiliza en el filtro actual, por lo que no puede seleccionar la clase de actividad en esta sección.',
        'open' => 'Actividades abiertas',
        'due_today' => 'Vence hoy',
        'due_this_week' => 'Vence esta semana',
        'display' => [
            'has' => 'tiene eventos :value:',
            'overdue' => 'tiene :value: eventos',
            'doesnt_have_activities' => 'no tiene ningún evento',
        ],
        'all' => 'Todos',
        'today' => 'Hoy',
        'tomorrow' => 'Mañana',
        'this_week' => 'Esta semana',
        'next_week' => 'Siguiente semana',
        'done' => 'Realizado',
        'done_empty_state' => 'Aquí se mostrarán las actividades realizadas.',
    ],
    'settings' => [
        'send_contact_email' => 'Se envió la plantilla de correo "El contacto atiende a la actividad" a los contactos',
        'send_contact_email_info' => 'Si está habilitado, cuando el contacto se agrega como invitado en la actividad, se enviará una plantilla de correo con el archivo .ics adjunto y la información de la actividad.',
    ],
    'manage_activities' => 'Gestionar actividades',
    'info' => 'Programar y gestionar eventos con contactos y representantes de ventas.',
    'timeline' => [
        'heading' => 'Se ha creado un evento',
    ],
    'permissions' => [
        'attends_and_owned' => 'Asistentes y propietarios únicamente',
    ],
    'actions' => [
        'update_type' => 'Tipo de actualización',
    ],
    'notifications' => [
        'due' => 'Tu :activity actividad debe realizarse el :date',
        'assigned' => 'Se le ha asignado a la tarea  :name por :user',
        'added_as_guest' => 'Ha sido añadido como invitado al evento',
    ],
    'mail_placeholders' => [
        'assigneer' => 'Nombre del usuario que asignó el evento',
    ],
    'validation' => [
        'end_date' => [
            'less_than_due' => 'La fecha final no debe ser inferior a la fecha de vencimiento.',
        ],
        'end_time' => [
            'required_when_end_date_is_in_future' => 'Debe especificar la hora de finalización cuando la fecha de finalización se encuentra en un día diferente.',
        ],
    ],
    'workflows' => [
        'actions' => [
            'create' => 'Crear evento',
        ],
        'fields' => [
            'create' => [
                'title' => 'Título del evento',
                'note' => 'Agregar nota (opcional)',
            ],
        ],
    ],
    'metrics' => [
        'todays' => 'Eventos de hoy',
    ],
];
