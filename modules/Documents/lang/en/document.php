<?php
 

return [
    'document' => 'Document',
    'documents' => 'Documents',
    'create' => 'Create Document',
    'info' => 'Send customizeable quotes, proposals and contracts to close deals faster.',
    'view' => 'View',
    'manage_documents' => 'Manage Documents',
    'total_documents' => 'Total Documents',
    'total_draft_documents' => 'Total Draft Documents',
    'deleted' => 'Document deleted successfully',
    'document_details' => 'Document Details',
    'document_activity' => 'Document Activity',
    'document_products' => 'Document Products',
    'download_pdf' => 'Download PDF',
    'view_pdf' => 'View PDF in Browser',
    'accept' => 'Accept',
    'sign' => 'Sign',
    'sent' => 'Document sent successfully',
    'deal_description' => 'When deal is selected, the deal will be automatically associated with the document, deal contacts will be added as signers, and all deal products will be added to the document.',

    'settings' => [
        'inherits_setting_from_brand' => 'Inherits from brand',
    ],

    'products_snippet_missing' => 'To ensure that the products appear in the document preview and PDF, make sure to add them to the content via the :icon icon.',

    'signatures_snippet_missing' => 'To ensure that the signatures appear in the document PDF, make sure to add them to the content via the :icon icon.',

    'will_use_placeholders_from_record' => 'The placeholders added in the document content related to the :resourceName resource will be taken form this record',

    'placeholders_replacement_info' => 'The placeholders of the document related to resources are replaced based on the first associated record, make sure to add associations to the document.',

    'limited_editing' => 'This document is accepted, editing abilities are limited.',

    'title' => 'Title',

    'copy_url' => 'Copy document public URL',
    'url_copied' => 'URL copied to clipboard',

    'count' => [
        'all' => '1 document | :count documents',
    ],

    'sections' => [
        'details' => 'Details',
        'send' => 'Send',
        'signature' => 'Signature',
        'send' => 'Send',
        'content' => 'Content',
        'products' => 'Products',
    ],

    'status' => [
        'status' => 'Status',

        'draft' => 'Draft',
        'sent' => 'Sent',
        'accepted' => 'Accepted',
        'lost' => 'Lost',
    ],

    'send' => [
        'select_brand' => 'Select a brand first in order to send the document.',
        'connect_an_email_account' => 'Connect an mail account in order to send documents.',
        'send_from_account' => 'Send the document from the following account',
        'save_to_schedule' => 'In order to schedule document sending, you will need to save the document first',
        'send_subject' => 'Message Subject',
        'send_body' => 'Message Text',
        'send_later' => 'Send later?',
        'send' => 'Send Document',
        'select_schedule_date' => 'Select date and time',
        'schedule' => 'Schedule',
        'is_scheduled' => 'This document is scheduled to be sent at :date',
        'send_to_signers' => 'Send the document to following signers',
        'send_to_signers_empty' => 'In order to send the document to the signers, add signers via the "Signature" section.',

    ],

    'sent_by' => 'Sent by',
    'sent_at' => 'Sent at :date',

    'signers' => [
        'add' => 'Add new signer',
        'no_signers' => 'No signers, add signers for this document.',
        'is_signed' => 'Signed?',
        'document_signers' => 'Document Signers',
        'signer_name' => 'Name',
        'signer_email' => 'E-Mail Address',
        'signature_date' => 'Date',
        'name' => 'Signer Name',
        'email' => 'Signer Email',
        'enter_full_name' => 'Enter the signer full name',
        'enter_email' => 'Enter the signer e-mail address',

        'confirm_email' => 'Confirm your e-mail address',
        'enter_email' => 'Please enter your e-mail address',
    ],

    'accepted_at' => 'Accepted At',

    'signature' => [
        'no_signature' => 'No Signature',
        'no_signature_description' => 'This document does not require a signature before acceptance.',
        'e_signature' => 'Use e-signature',
        'e_signature_description' => 'This document require e-signature before acceptance.',

        'signature' => 'Signature',
        'signatures' => 'Signatures',
        'signed_on' => 'Signed on',
        'sign_ip' => 'IP Address',

        'verification_failed' => 'We were unable to verify your email address as a signer, contact the person that sent you the document to give information about the email address that is used.',
        'accept_name' => 'To accept, type your name below',
    ],

    'deleted' => 'Document successfully deleted',
    'reactivated' => 'Document reactivated',
    'marked_as_lost' => 'Document successfully marked as lost',
    'marked_as_accepted' => 'Document successfully marked as accepted',

    'actions' => [
        'mark_as_lost' => 'Mark as Lost',
        'mark_as_lost_message' => 'This action will mark this document as lost and none of the recipients will no longer be able to access it',
        'mark_as_accepted' => 'Mark as Accepted',
        'reactivate' => 'Reactivate',
        'undo_acceptance' => 'Undo acceptance',
    ],

    'cards' => [
        'by_type' => 'Documents by type',
        'by_status' => 'Documents by status',
        'sent_by_day' => 'Sent documents by day',
    ],

    'recipients' => [
        'add' => 'Add new recipient',
        'enter_full_name' => 'Enter the recipient full name',
        'enter_email' => 'Enter the recipient e-mail address',
        'no_recipients' => 'No recipients to send to document to.',
        'is_sent' => 'Sent?',
        'recipients' => 'Recipients',
        'additional_recipients' => 'Additional recipients',
        'recipient_name' => 'Name',
        'recipient_email' => 'E-Mail Address',
        'name' => 'Recipient Name',
        'email' => 'Recipient Email',
    ],

    'view_type' => [
        'html_view_type' => 'HTML view type',
        'template_info' => 'When a template has a view type, after inserted, the document view type will be updated with the template type.',
        'nav_top' => [
            'name' => 'Navigation Top',
            'description' => 'Useful for simple documents that require no navigation via headings.',
        ],
        'nav_left' => [
            'name' => 'Navigation Left',
            'description' => 'Useful for documents that require navigation via headings (:headingTagName).',
        ],
        'nav_left_full_width' => [
            'name' => 'Navigation Left - Full Width',
            'description' => 'The content section has no margin, useful for full width documents with headings (:headingTagName).',
        ],
    ],

    'type' => [
        'type' => 'Document Type',
        'types' => 'Document Types',
        'name' => 'Name',
        'default_type' => 'Default Document Type',
        'delete_primary_warning' => 'You cannot delete primary document type.',
        'delete_usage_warning' => 'The type is already associated with documents, hence, cannot be deleted.',
        'delete_is_default' => 'This is a default document type, hence, cannot be deleted.',
        'cannot_change_visibility_on_default' => 'This is a default company wide type, hence, the visibility cannot be changed.',
    ],

    'template' => [
        'insert_template' => 'Insert Template',
        'save_as_template' => 'Save as Template',
        'manage' => 'Manage templates',
        'template' => 'Document Template',
        'templates' => 'Document Templates',
        'create' => 'Create Template',
        'name' => 'Template Name',
        'deleted' => 'Template successfully deleted',
        'share_with_team_members' => 'Share this template with other team members?',
        'is_shared' => 'Shared',

        'empty_state' => [
            'title' => 'You have not created any templates.',
            'description' => 'Create documents faster by using predefined templates.',
        ],
    ],

    'workflows' => [
        'triggers' => [
            'status_changed' => 'Document Status Changed',
        ],

        'actions' => [
            'fields' => [
                'email_to_contact' => 'Document primary contact',
                'email_to_company' => 'Document primary company',
                'email_to_owner_email' => 'Document owner email',
                'email_to_creator_email' => 'Document creator email',
            ],
        ],
    ],

    'timeline' => [
        'heading' => 'Document Created',
    ],

    'mail_placeholders' => [
        'assigneer' => 'The user name who assigned the document',
    ],

    'notifications' => [
        'signed' => 'The :title document has been signed',
        'assigned' => 'You have been assigned to document :title by :user',
        'accepted' => 'The :title document has been accepted',
        'viewed' => 'The :title document has been viewed',
    ],

    'filters' => [
        'status_disabled' => 'Status is used in the current filter, hence, you cannot select status from this section',
    ],

    'activity' => [
        'created' => 'The document has been created by :user',
        'sent' => 'The document has been sent by :user',
        'marked_as_lost' => ':user marked the document as lost',
        'marked_as_accepted' => ':user marked the document as accepted',
        'marked_as_draft' => ':user marked the document as draft',
        'sent_recipient' => ':name - :email',
        'signed' => 'The document signed by :signer_name',
        'accepted' => 'The document was accepted',
        'viewed' => 'The document was viewed',
        'downloaded' => 'The document PDF was downloaded',
    ],

    'empty_state' => [
        'title' => 'You have not created any documents.',
        'description' => 'Close deals faster by sending good looking trackable documents.',
    ],
];
