<?php
// Temporary users list used until a database is available.
// Format: username => [ 'password' => 'plainpass', 'role' => 'role_key', 'name' => 'Full Name' ]
// Roles: 'admin', 'finance', 'studentservices'

$temp_users = [
    // Administration (3 users)
    'admin01' => ['password' => 'adminpass1', 'role' => 'admin', 'name' => 'Alice Admin'],
    'admin02' => ['password' => 'adminpass2', 'role' => 'admin', 'name' => 'Bob Admin'],
    'admin03' => ['password' => 'adminpass3', 'role' => 'admin', 'name' => 'Carol Admin'],

    // Finance (3 users)
    'finance01' => ['password' => 'financepass1', 'role' => 'finance', 'name' => 'Frank Finance'],
    'finance02' => ['password' => 'financepass2', 'role' => 'finance', 'name' => 'Fiona Finance'],
    'finance03' => ['password' => 'financepass3', 'role' => 'finance', 'name' => 'Fred Finance'],

    // Student Services (3 users)
    'service01' => ['password' => 'servicepass1', 'role' => 'studentservices', 'name' => 'Sam Service'],
    'service02' => ['password' => 'servicepass2', 'role' => 'studentservices', 'name' => 'Sally Service'],
    'service03' => ['password' => 'servicepass3', 'role' => 'studentservices', 'name' => 'Sue Service'],
];

// Note: passwords are stored in plain text only for quick testing. Replace with
// proper hashed passwords and a database-backed user store when ready.

