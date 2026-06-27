<?php

/*
|--------------------------------------------------------------------------
| Permission catalogue (single source of truth)
|--------------------------------------------------------------------------
| Module => [ slug => human label ].
| Add a module/permission here and run `php artisan db:seed --class=RbacSeeder`
| (or the app boots it) to register it — the Roles matrix picks it up automatically.
*/

return [
    'Dashboard' => [
        'dashboard.view' => 'View Dashboard',
    ],
    'Products' => [
        'products.view'   => 'View Products',
        'products.create' => 'Create Products',
        'products.edit'   => 'Edit Products',
        'products.delete' => 'Delete Products',
    ],
    'Categories' => [
        'categories.view'             => 'View Categories',
        'categories.create'           => 'Create Categories',
        'categories.edit'             => 'Edit Categories',
        'categories.delete'           => 'Delete Categories',
        'categories.manage_featured'  => 'Manage Featured Categories',
    ],
    'Orders' => [
        'orders.view'          => 'View Orders',
        'orders.create'        => 'Create Orders',
        'orders.edit'          => 'Edit Orders',
        'orders.delete'        => 'Delete Orders',
        'orders.update_status' => 'Update Order Status',
        'orders.edit_customer' => 'Edit Customer Info',
        'orders.edit_items'    => 'Edit Order Items',
        'orders.add_products'  => 'Add Products to Orders',
    ],
    'Customers' => [
        'customers.view'        => 'View Customers',
        'customers.create'      => 'Create Customers',
        'customers.edit'        => 'Edit Customers',
        'customers.delete'      => 'Delete Customers',
        'customers.view_orders' => 'View Customer Orders',
    ],
    'Users' => [
        'users.view'   => 'View Users',
        'users.create' => 'Create Users',
        'users.edit'   => 'Edit Users',
        'users.delete' => 'Delete Users',
    ],
    'Roles' => [
        'roles.view'   => 'View Roles',
        'roles.create' => 'Create Roles',
        'roles.edit'   => 'Edit Roles',
        'roles.delete' => 'Delete Roles',
    ],
    'Settings' => [
        'settings.view' => 'View Settings',
        'settings.edit' => 'Edit Settings',
    ],
    'Reports' => [
        'reports.view'      => 'View Reports',
        'reports.export'    => 'Export Reports',
        'reports.inventory' => 'View Inventory Reports',
        'reports.pos'       => 'View POS Reports',
        'reports.pos_export'=> 'Export POS Reports',
    ],

    'Inventory' => [
        'inventory.view'      => 'View Inventory',
        'inventory.update'    => 'Update Inventory',
        'inventory.logs'      => 'View Inventory Logs',
        'inventory.analytics' => 'View Inventory Analytics',
        'inventory.export'    => 'Export Inventory Data',
    ],

    'POS' => [
        'pos.view'      => 'View POS',
        'pos.create'    => 'Create POS Orders',
        'pos.edit'      => 'Edit POS Orders',
        'pos.delete'    => 'Delete POS Orders',
        'pos.orders'    => 'View POS Orders',
        'pos.customers' => 'Manage POS Customers',
    ],

    'Invoice' => [
        'invoice.view'       => 'View Invoice',
        'invoice.download'   => 'Download Invoice',
        'invoice.print'      => 'Print Invoice',
        'invoice.regenerate' => 'Regenerate Invoice',
    ],

    'Invoice Settings' => [
        'invoice_settings.view' => 'View Invoice Settings',
        'invoice_settings.edit' => 'Edit Invoice Settings',
    ],

    'Chatbot' => [
        'chatbot.settings.view'    => 'View Chatbot Settings',
        'chatbot.settings.edit'    => 'Edit Chatbot Settings',
        'chatbot.toggle'           => 'Enable/Disable Chatbot System',
        'chatbot.orders.view'      => 'View Chatbot Orders',
        'chatbot.orders.edit'      => 'Edit Chatbot Orders',
        'chatbot.orders.delete'    => 'Delete Chatbot Orders',
        'chatbot.analytics.view'   => 'View Chatbot Analytics',
        'chatbot.analytics.export' => 'Export Chatbot Reports',
    ],
    'CMS' => [
        'cms.view'   => 'View Pages',
        'cms.create' => 'Create Pages',
        'cms.edit'   => 'Edit Pages',
        'cms.delete' => 'Delete Pages',
    ],
    'Media' => [
        'media.view'   => 'View Media',
        'media.upload' => 'Upload Media',
        'media.delete' => 'Delete Media',
    ],
    'Marketing' => [
        'marketing.view'   => 'View Campaigns',
        'marketing.create' => 'Create Campaigns',
        'marketing.edit'   => 'Edit Campaigns',
        'marketing.delete' => 'Delete Campaigns',
    ],
    'Accounts' => [
        'accounting.view'   => 'View Accounts',
        'accounting.manage' => 'Manage Transactions & Settings',
        'accounting.import' => 'Import Accounting Data',
    ],
    'Audit Logs' => [
        'audit.view'   => 'View Audit Logs',
        'audit.delete' => 'Clear Audit Logs',
    ],
];
