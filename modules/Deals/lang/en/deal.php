<?php
 

return [
    'deal' => 'Deal',
    'deals' => 'Deals',
    'create' => 'Create Deal',
    'add' => 'Add Deal',
    'sort_by' => 'Sort deals by',
    'name' => 'Deal Name',
    'choose_or_create' => 'Choose or create deal',
    'associate_with' => 'Associate with a deal',
    'add_products' => 'Add products',
    'dont_add_products' => 'Don\'t add products',
    'reopen' => 'Reopen',
    'won_date' => 'Won Date',
    'lost_date' => 'Lost Date',
    'status_related_filter_notice' => 'This rule is applicable mostly when filtering deals with status ":status".',

    'status' => [
        'status' => 'Status',
        'won' => 'Won',
        'lost' => 'Lost',
        'open' => 'Open',
    ],

    'been_in_stage_time' => 'Been here for :time',
    'hasnt_been_in_stage' => 'This deal has not been in this stage yet',
    'total_created' => 'Total Created',
    'total_assigned' => 'Total Assigned',
    'import' => 'Import Deals',
    'export' => 'Export Deals',
    'import_in' => 'Import Deals In :pipeline',
    'total' => 'Total Deals',
    'closed_deals' => 'Closed Deals',
    'won_deals' => 'Won Deals',
    'open_deals' => 'Open Deals',
    'lost_deals' => 'Lost Deals',
    'forecast_amount' => 'Forecast Amount',
    'closed_amount' => 'Closed Amount',
    'dissociate' => 'Dissociate Deal',
    'no_companies_associated' => 'The deal has no companies associated.',
    'no_contacts_associated' => 'The deal has no contacts associated.',
    'associate_with' => 'Associate deal with :name',
    'associate_field_info' => 'Use this field to associate existing deal instead of creating new one.',
    'create_with' => 'Create Deal with :name',
    'already_associated' => 'This deal is already associated with the :with.',

    'lost_reasons' => [
        'lost_reason' => 'Lost Reason',
        'lost_reasons' => 'Lost Reasons',
        'name' => 'Name',
        'choose_lost_reason' => 'Choose a lost reason',
        'choose_lost_reason_or_enter' => 'Choose a lost reason or enter manually',
    ],

    'settings' => [
        'lost_reason_is_required' => 'Lost reason is required',
        'lost_reason_is_required_info' => 'When enabled, sales agents will be required to choose or enter lost reason when marking deal as lost.',
        'allow_lost_reason_enter' => 'Allow sales agents to enter custom lost reason',
        'allow_lost_reason_enter_info' => 'When disabled, sales agents will be able to choose only from the predefined list of lost reasons when marking the deal as lost.',
    ],

    'cards' => [
        'by_stage' => 'Deals by stage',
        'lost_in_stage' => 'Lost deals stage',
        'lost_in_stage_info' => 'View in what stage the deals are most lost. The stages shown in the reports are the stages that deal belonged at the time it was marked as lost.',
        'won_in_stage' => 'Won deals stage',
        'won_in_stage_info' => 'View in what stage the deals are most won. The stages shown in the reports are the stages that deal belonged at the time it was marked as won.',
        'closing' => 'Closing deals',
        'closing_info' => 'View the deals that are predicted to be closed based on the selected period and the expected close date, the deals marked as "Won" or "Lost" are excluded from the list.',
        'recently_created' => 'Recently created deals',
        'recently_modified' => 'Recently modified deals',
        'won_by_revenue_by_month' => 'Won deals revenue by month',
        'won_by_date' => 'Won deals by day',
        'assigned_by_sale_agent' => 'Assigned deals by sales agent',
        'assigned_by_sale_agent_info' => 'View the total number of assigned deals for each sale rep. See how much revenue these deals are likely to bring your business. And how much revenue you already have from closed deals.',
        'created_by_sale_agent' => 'Created deals by sales agent',
        'created_by_sale_agent_info' => 'View which sales reps are creating the most deals. See how much revenue these deals are likely to bring your business. And how much revenue you already have from closed deals.',
        'recently_created_info' => 'Showing the last :total created deals in the last :days days, sorted by newest on top.',
        'recently_modified_info' => 'Showing the last :total modified deals in the last :days days.',
        'won_by_month' => 'Won deals by month',
    ],

    'notifications' => [
        'assigned' => 'You have been assigned to a deal :name by :user',
    ],

    'stage' => [
        'weighted_value' => ':weighted_total - :win_probability of :total',
        'changed_date' => 'Stage Changed Date',
        'add' => 'Add New Stage',
        'name' => 'Stage Name',
        'win_probability' => 'Win Probability',
        'delete_usage_warning' => 'The stage is already associated with deals, hence, cannot be deleted.',
    ],

    'deal_amount' => 'Deal amount',
    'deal_expected_close_date' => 'Deal expected close date',

    'count' => [
        'all' => '1 deal | :count deals',
        'open' => ':resource open deals count',
        'won' => ':resource won deals count',
        'lost' => ':resource lost deals count',
        'closed' => ':resource closed deals count',
    ],

    'pipeline' => [
        'name' => 'Pipeline Name',
        'pipeline' => 'Pipeline',
        'pipelines' => 'Pipelines',
        'create' => 'Create Pipeline',
        'edit' => 'Edit Pipeline',
        'updated' => 'Pipeline updated successully',
        'deleted' => 'Pipeline successfully deleted',
        'delete_primary_warning' => 'The primary pipeline cannot be deleted.',
        'delete_usage_warning_deals' => 'The pipeline is already associated with deals, hence, cannot be deleted.',
        'visibility_group' => [
            'primary_restrictions' => 'This is the primary pipeline, hence, visibility cannot be changed.',
        ],
        'reorder' => 'Reorder pipelines',
        'missing_stages' => 'The pipeline does not have any stages.',
    ],

    'actions' => [
        'change_stage' => 'Change Stage',
        'mark_as_open' => 'Mark as open',
        'mark_as_won' => 'Mark as won',
        'mark_as_lost' => 'Mark as lost',
    ],

    'filters' => [
        'my' => 'My Deals',
        'my_recently_assigned' => 'My Recently Assigned Deals',
        'created_this_month' => 'Deals Created This Month',
        'won' => 'Won Deals',
        'lost' => 'Lost Deals',
        'open' => 'Open Deals',
    ],

    'mail_placeholders' => [
        'assigneer' => 'The user name who assigned the deal',
    ],

    'workflows' => [
        'triggers' => [
            'status_changed' => 'Deal Status Changed',
            'stage_changed' => 'Deal Stage Changed',
            'created' => 'Deal Created',
        ],

        'actions' => [
            'mark_associated_activities_as_complete' => 'Mark Associated Activities as Completed',
            'mark_associated_deals_as_won' => 'Mark Associated Deals as Won',
            'mark_associated_deals_as_lost' => 'Mark Associated Deals as Lost',
            'delete_associated_activities' => 'Delete Associated Activities',

            'fields' => [
                'email_to_contact' => 'Deal primary contact',
                'email_to_company' => 'Deal primary company',
                'email_to_owner_email' => 'Deal owner email',
                'email_to_creator_email' => 'Deal creator email',
                'lost_reason' => 'With the following lost reason',
            ],
        ],
    ],

    'timeline' => [
        'stage' => [
            'moved' => ':user moved deal from :previous to :stage stage',
        ],

        'marked_as_lost' => ':user marked deal as lost with the following reason: :reason',
        'marked_as_won' => ':user marked deal as won',
        'marked_as_open' => ':user marked deal as open',
    ],

    'metrics' => [
        'open' => 'Open Deals',
    ],

    'empty_state' => [
        'title' => 'You have not created any deals.',
        'description' => 'Get started by creating a new deal.',
    ],
];
