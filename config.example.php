<?php
// Copy this file to config.php and set values for your hosting account.
return [
    'db' => [
        'host'    => 'localhost',
        'port'    => 3306,
        'name'    => 'your_database_name',
        'user'    => 'your_database_user',
        'pass'    => 'your_database_password',
        'charset' => 'utf8mb4',
    ],
    'site_url' => 'https://shopictgadgets.co.ke',
    'visitor_cookie' => 'onsus_vid',
    'cloudinary' => [
        'cloud_name' => '',
        'api_key'    => '',
        'api_secret' => '',
        'folder'     => 'shopict',
    ],
    'mail' => [
        'driver'     => 'log',
        'host'       => '',
        'port'       => 465,
        'encryption' => 'ssl',
        'username'   => '',
        'password'   => '',
        'from_email' => '',
        'from_name'  => 'Shop ICT Gadgets',
    ],
];
