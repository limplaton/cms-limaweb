<?php
 

return [
    'permissions' => 'Permissions',
    'role' => 'Role',
    'roles' => 'Roles',
    'name' => 'Name',
    'create' => 'Create Role',
    'edit' => 'Edit Role',
    'created' => 'Role successfully created',
    'updated' => 'Role successfully updated',
    'deleted' => 'Role successfully deleted',

    'granted' => 'Granted',
    'revoked' => 'Revoked',

    'capabilities' => [
        'access' => 'Access',
        'view' => 'View',
        'delete' => 'Delete',
        'bulk_delete' => 'Bulk Delete',
        'edit' => 'Edit',
        'all' => 'All :resourceName',
        'owning_only' => 'Owned Only',
    ],

    'view_non_authorized_after_record_create' => "Your account is not authorized to view this record as you are not the owner of the record, after redirecting from this page, you won't be able to access the record.",

    'empty_state' => [
        'title' => 'No roles',
        'description' => 'Get started by creating a new role.',
    ],
];
