<?php
 

return [
    'activities' => 'Activities',
    'activity' => 'Activity',
    'add' => 'Add Activity',
    'description' => 'Description',
    'description_info' => 'The description is visible to all guests',
    'note' => 'Note',
    'note_info' => 'Notes are private and visible only for the sales reps.',
    'title' => 'Title',
    'due_date' => 'Due Date',
    'end_date' => 'End Date',
    'create' => 'Create Activity',
    'download_ics' => 'Download .ics file',
    'created' => 'Activity successfully created',
    'updated' => 'Activity successfully updated',
    'deleted' => 'Activity successfully deleted',
    'export' => 'Export Activities',
    'import' => 'Import Activities',
    'guests' => 'Guests',
    'guest' => 'Guest',
    'count_guests' => '1 guest | :count guests',
    'create_follow_up_task' => 'Create follow up task',
    'follow_up_with_title' => 'Follow up with :with',
    'title_via_create_message' => 'Regarding an email: :subject',
    'reminder_update_info' => 'Because the reminder for this activity is already sent, you will need to update the due date in order a new reminder to be pushed to the queue.',
    'owner_assigned_date' => 'Owner Assigned Date',
    'reminder_sent_date' => 'Reminder Sent Date',
    'reminder' => 'Reminder',
    'owner' => 'Owner',
    'mark_as_completed' => 'Mark as completed',
    'mark_as_incomplete' => 'Mark as incomplete',
    'is_completed' => 'Is Completed',
    'completed_at' => 'Completed At',
    'overdue' => 'Overdue',
    'doesnt_have_activities' => 'No Activities',
    'count' => 'No Activities | 1 Activity | :count Activities',
    'incomplete_activities' => 'Incomplete Activities',

    'activity_was_due' => 'This activity was due at :date',

    'next_activity_date' => 'Next Activity Date',
    'next_activity_date_info' => 'This field is read only and is automatically updated based on the record upcoming activities, indicates when the sale rep next action should be taken.',

    'cards' => [
        'my_activities' => 'My activities',
        'my_activities_info' => 'This cards reflects the activities that you are added as owner',
        'created_by_agent' => 'Activities created by sales agent',
        'created_by_agent_info' => 'View the number of activities each sales agent is created. See who is creating the most activities and who is creating the least.',
        'upcoming' => 'Upcoming activities',
        'upcoming_info' => 'This card reflects the activities that are upcoming and the one that you are attending to.',
    ],

    'type' => [
        'default_type' => 'Default Activity Type',
        'delete_primary_warning' => 'You cannot delete primary activity type.',
        'delete_usage_warning' => 'The type is already associated with activities, hence, cannot be deleted.',
        'delete_usage_calendars_warning' => 'This type is used as default type when creating activities via connected calendars, hence, cannot be deleted.',
        'delete_is_default' => 'This is a default activity type, hence, cannot be deleted.',
        'type' => 'Activity Type',
        'types' => 'Activity Types',
        'name' => 'Name',
        'icon' => 'Icon',
    ],

    'filters' => [
        'activity_type_disabled' => 'Activity type is used in the current filter, hence, you cannot select activity type from this section',
        'open' => 'Open Activities',
        'due_today' => 'Due Today',
        'due_this_week' => 'Due This Week',

        'display' => [
            'has' => 'has activities :value:',
            'overdue' => 'has :value: activities',
            'doesnt_have_activities' => 'does not have any activities',
        ],

        'all' => 'All',
        'today' => 'Today',
        'tomorrow' => 'Tomorrow',
        'this_week' => 'This Week',
        'next_week' => 'Next Week',
        'done' => 'Done',
        'done_empty_state' => 'Done activities will be shown here.',
    ],

    'settings' => [
        'send_contact_email' => 'Sent "Contact attends to activity" mail template to contacts',
        'send_contact_email_info' => 'If enabled, when contact is added as guest on activity, a mail template will be sent with attached .ics file and activity information.',
    ],

    'manage_activities' => 'Manage Activities',
    'info' => 'Schedule and manage activities with contacts and sales reps.',

    'timeline' => [
        'heading' => 'An activity has been created',
    ],

    'permissions' => [
        'attends_and_owned' => 'Attends and owned only',
    ],

    'actions' => [
        'update_type' => 'Update type',
    ],

    'notifications' => [
        'due' => 'Your :activity activity is due on :date',
        'assigned' => 'You have been assigned to activity :name by :user',
        'added_as_guest' => 'You have been added as guest to activity',
    ],

    'mail_placeholders' => [
        'assigneer' => 'The user name who assigned the activity',
    ],

    'validation' => [
        'end_date' => [
            'less_than_due' => 'The end date must not be less then the due date.',
        ],
        'end_time' => [
            'required_when_end_date_is_in_future' => 'You must specify end time when the end date is on a different day.',
        ],
    ],

    'workflows' => [
        'actions' => [
            'create' => 'Create Activity',
        ],
        'fields' => [
            'create' => [
                'title' => 'With activity title',
                'note' => 'Add note (optional)',
            ],
        ],
    ],

    'metrics' => [
        'todays' => 'Today\'s Activities',
    ],

    'empty_state' => [
        'title' => 'You have not created any activities.',
        'description' => 'Get started by creating a new activity.',
    ],
];
