<?php
/**
 * Global Application Configuration & Constants
 */

return [
    'name' => getenv('APP_NAME') ?: ($_ENV['APP_NAME'] ?? 'SLSU-Alabat Inventory Management System'),
    'env' => getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'local'),
    'debug' => (bool) (getenv('APP_DEBUG') ?: ($_ENV['APP_DEBUG'] ?? true)),
    'url' => getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? 'http://localhost/slsu-inventory/public'),
    
    // Session & Security Settings
    'session' => [
        'lifetime' => (int) (getenv('SESSION_LIFETIME') ?: ($_ENV['SESSION_LIFETIME'] ?? 900)), // 15 mins default
        'secure_cookie' => (bool) (getenv('SESSION_SECURE_COOKIE') ?: ($_ENV['SESSION_SECURE_COOKIE'] ?? false)),
    ],

    // User Roles Hierarchy Definition
    'roles' => [
        'admin'           => 'System Administrator',
        'custodian'       => 'Property Custodian & Supply Office',
        'executive_admin' => 'Campus Administration / Director',
        'faculty'         => 'Faculty',
        'staff'           => 'Staff',
        'student'         => 'Student',
    ],

    // Operational Borrowing Limits
    'borrowing_limits' => [
        'student' => [
            'max_concurrent_loans' => 3,
            'default_duration_hours' => 4,
        ],
        'faculty' => [
            'max_concurrent_loans' => 10,
            'default_duration_days' => 7,
        ],
    ],
];
