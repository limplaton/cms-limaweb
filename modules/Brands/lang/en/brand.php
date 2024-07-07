<?php
 

return [
    'brand' => 'Brand',
    'brands' => 'Brands',
    'create' => 'Create Brand',
    'update' => 'Update Brand',

    'form' => [
        'sections' => [
            'general' => 'General',
            'navigation' => 'Navigation',
            'email' => 'Email',
            'thank_you' => 'Thank You',
            'signature' => 'Signature',
            'pdf' => 'PDF',
        ],

        'is_default' => 'This the the company default brand?',
        'name' => 'How do you refer to this brand internally?',
        'display_name' => 'How do you want it displayed to your clients?',
        'primary_color' => 'Choose the brand primary color',
        'upload_logo' => 'Upload your company logo',

        'navigation' => [
            'background_color' => 'Navigation background color',
            'upload_logo_info' => 'If you have a dark background, use a light logo. If you\'re using a light background color use a logo with dark text.',
        ],

        'pdf' => [
            'default_font' => 'Default font family',
            'default_font_info' => 'The :fontName font gives the most decent Unicode character coverage by default, make sure to select a proper font if special or unicode characters are not displayed properly on the PDF document.',
            'size' => 'Size',
            'orientation' => 'Orientation',
            'orientation_portrait' => 'Portrait',
            'orientation_landscape' => 'Landspace',
        ],

        'email' => [
            'upload_logo_info' => 'Make sure the logo is suitable for a white background, if no logo is uploaded, the dark logo uploaded in General settings will be used instead.',
        ],

        'document' => [
            'send' => [
                'info' => 'When you send a document',
                'subject' => 'Default subject',
                'message' => 'Default email message when you\'re sending a document',
                'button_text' => 'Email button text',
            ],

            'sign' => [
                'info' => 'When someone signs your document',
                'subject' => 'Default subject line for thank you email',
                'message' => 'Email message to send when someone signs your document',
                'after_sign_message' => 'After signing, what should the message say?',
            ],

            'accept' => [
                'after_accept_message' => 'After accepting (without digital signature), what should the message say?',
            ],
        ],

        'signature' => [
            'bound_text' => 'Legal Bound Text',
        ],
    ],

    'delete_documents_usage_warning' => 'The brand is already associated with documents, hence, cannot be deleted.',

    'created' => 'Brand successfully created.',
    'updated' => 'Brand successfully updated.',
    'deleted' => 'Brand successfully deleted',
];
