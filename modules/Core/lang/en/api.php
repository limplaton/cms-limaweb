<?php
 

return [
    'api' => 'API',
    'access' => 'API Access',
    'access_tokens' => 'Access Tokens',
    'personal_access_tokens' => 'Personal Access Tokens',
    'personal_access_token' => 'Personal Access Token',
    'create_token' => 'Create New Token',
    'no_tokens' => 'You have not created any personal access tokens.',
    'token_name' => 'Name',
    'revoke_token' => 'Revoke',
    'token_last_used' => 'Last Used',
    'after_token_created_info' => "Here is your new personal access token. This is the only time it will be shown so don't lose it!
    You may now use this token to make API requests.",
    'empty_state' => [
        'description' => 'Use tokens to make API requests outside of the application.',
    ],
    'token_delete_warning' => 'Please note that if you delete your API token, it will immediately invalidate the token. This means any external services or code integrations using this token will stop to function. Ensure to remove or replace the token in all your implementations to prevent disruptions.',
];
