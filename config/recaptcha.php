<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google reCAPTCHA Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for Google reCAPTCHA v2
    |
    */

    'site_key' => env('RECAPTCHA_SITE_KEY'),
    'secret_key' => env('RECAPTCHA_SECRET_KEY'),

    // Skip reCAPTCHA validation in these environments
    'skip_environments' => ['local', 'testing'],

    // Verification endpoint
    'verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
];
