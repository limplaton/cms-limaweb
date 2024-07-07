<?php
 

return [
    'settings' => 'Settings',
    'updated' => 'Settings Updated',
    'general_settings' => 'General Settings',
    'company_information' => 'Company Information',
    'update_user_account_info' => "Updating these settings won't affect your user account settings as these settings are general, update the same settings in your user account instead if you are looking to update these options.",
    'general' => 'General',
    'system' => 'System',
    'system_email' => 'System Email Account',
    'system_email_configured' => 'Account configured by other user',
    'system_email_info' => 'Select Inbox connected email account that will be used to send system related mails like user assigned to contact, activity due reminder, user invites, etc...',
    'choose_logo' => 'Choose Logo',
    'date_format' => 'Date Format',
    'time_format' => 'Time Format',
    'go_to_settings' => 'Go to settings',

    'privacy_policy_info' => "If you don't have privacy policy, you can configure one here, view the privacy policy at the following URL: :url",

    'phones' => [
        'require_calling_prefix' => 'Require calling prefix on phone numbers',
        'require_calling_prefix_info' => 'Most call integrations are requiring phone numbers to be in E.164 format. Enabling this option will make sure that no phone numbers are entered without a country-specific calling prefix.',
    ],

    'recaptcha' => [
        'recaptcha' => 'reCaptcha',
        'site_key' => 'Site Key',
        'secret_key' => 'Secret Key',
        'ignored_ips' => 'Ignored IP Addresses',
        'ignored_ips_info' => 'Enter coma separated IP addresses that you want the reCaptcha to skip validation.',
        'dont_get_locked' => "Don't get locked",
        'ensure_recaptcha_works' => 'To ensure the reCaptcha configuration works well, always perform a test login via Incognito Mode while keeping the current window active.',
    ],

    'security' => [
        'security' => 'Security',
        'disable_password_forgot' => 'Disable password forgot feature',
        'disable_password_forgot_info' => 'When enabled, the password forgot feature will be disabled.',
        'block_bad_visitors' => 'Block bad visitors',
        'block_bad_visitors_info' => 'If enabled, a list of bad user agents, ip addresses and referrers will be checked for each guest visitor.',
    ],

    'tools' => [
        'tools' => 'Tools',
        'run' => 'Run Tool',
        'executed' => 'Action executed successfully',

        'clear-cache' => 'Clear application cache.',
        'storage-link' => 'Create a symbolic link from "public/storage" to "storage/app/public".',
        'optimize' => 'Cache the application bootstrap files like config and routes.',
        'seed-mailable-templates' => 'Seed the application mailable templates.',
    ],
];
