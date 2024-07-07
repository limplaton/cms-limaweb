<?php
 

return [
    'calendar' => 'Calendar',
    'calendar_sync' => 'Calendar Sync',
    'reconfigure' => 'Reconfigure',
    'choose_oauth_account' => 'Choose an account type to start syncing calendar events',
    'account_being_connected' => "You're about to connect :email account.",
    'save_events_as' => 'Save calendar event as:',
    'sync_activity_types' => 'Sync the following activity types to calendar:',
    'sync_support_only_primary' => 'It\'s possible to sync only the account primary calendar.',
    'only_future_events_will_be_synced' => 'Only future events will be synced.',
    'events_will_sync_from' => 'Events created from :date will be synced.',
    'events_being_synced_from' => 'Events created from :date are being synced.',

    'missing_outlook_integration' => 'Microsoft application not configured, you must configure your Microsoft application in order to sync Outlook calendar.',

    'missing_google_integration' => 'Google application project not configured, you must configure your Google application project in order to connect to sync Google calendar.',

    'timeline' => [
        'imported_via_calendar_attendee' => 'Contact imported via :user calendar because was added as attendee to an event.',
    ],

    'fullcalendar' => [
        'locale' => [

            'buttonText' => [
                'prev' => 'Previous period',
                'next' => 'Next period',
                'prevYear' => 'Prev year', // not used
                'nextYear' => 'Next year', // not used
                'year' => 'year',
                'today' => 'Today',
                'month' => 'Month',
                'week' => 'Week',
                'day' => 'Day',
                'list' => 'List', // not used
            ],

            'weekText' => 'W',
            'allDayText' => 'All Day',
            'moreLinkText' => 'more',
            'noEventsText' => 'No activities to display',
        ],
    ],
];
