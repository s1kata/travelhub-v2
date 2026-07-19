<?php
return [
    'debug' => false,

    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'garant77li',
        'user' => 'garant77li',
        'pass' => '8909qpzM',
        'charset' => 'utf8mb4',
    ],

    // Сначала env, потом fallback
    'jwt_secret' => 'KJr+f91X21KfBSg+6nAKxVGWLtZjCmbJR1gf4NDgF00CeiTcHGhqxir+LPh9AcQX',
'uon_api_key' => getenv('UON_API_KEY') ?: 'sb8V0iM9BqqZX44gM21u1777975308',
'tinkoff_terminal_key' => getenv('TINKOFF_TERMINAL_KEY') ?: '1773332116483',
'tinkoff_password' => getenv('TINKOFF_PASSWORD') ?: 'H7!7mXlXMQmuAsik',
'app_url' => rtrim(getenv('APP_URL') ?: 'https://travelhub63.ru', '/'),
'api_url' => rtrim(getenv('API_URL') ?: 'https://travelhub63.ru', '/'),
    'access_ttl' => 3600,
    'refresh_ttl' => 31536000,

    'tables' => [
        'users' => 'mobile_users',
        'refresh_tokens' => 'mobile_refresh_tokens',
        'password_reset_tokens' => 'mobile_password_reset_tokens',
    ],

    'columns' => [
        'id' => 'id',
        'email' => 'email',
        'password' => 'password',
        'full_name' => 'full_name',
        'phone' => 'phone',
        'is_active' => 'is_active',
        'created_at' => 'created_at',
        'updated_at' => 'updated_at',
        'last_login_at' => 'last_login_at',
        'deleted_at' => 'deleted_at',
        'passport_json' => 'passport_json',
    ],

    'password_algo' => 'bcrypt',
    'site_url' => 'https://travelhub63.ru',
    'send_reset_email' => true,
    'allow_cors' => true,
    'health_check_token' => 'cd4d9b575b4d83189b84e0ca6eb8d94018b14848d0129f9da012e88d2d4e43ad',
'jwt_issuer' => 'travelhub-auth',
'allowed_origins' => [
    'https://travelhub63.ru',
],
];