<?php
/**
 * config.local.example.php - Template for config.local.php.
 *
 * This file IS tracked in git and must never contain a real credential.
 *
 * Setup, on every machine and on the server:
 *
 *     cp includes/config.local.example.php includes/config.local.php
 *
 * then fill in the real values. config.local.php is gitignored, so the
 * secrets stay out of the public repository.
 */

return [
    // true prints PHP errors to the page. Leave false anywhere the public
    // can reach; set it true only on a local copy while debugging.
    'debug' => false,

    'db' => [
        'host' => 'localhost',
        'name' => 'your_database_name',
        'user' => 'your_database_user',
        'pass' => 'your_database_password',
    ],

    // sk_test_... while testing, sk_live_... in production.
    'paystack_secret' => 'sk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',

    'smtp' => [
        'host'       => 'smtp.gmail.com',
        'port'       => 587,
        'username'   => 'you@example.com',
        'password'   => 'your_smtp_or_app_password',
        'from_email' => 'you@example.com',
        'from_name'  => 'NACOS FPE CHAPTER',
    ],
];
