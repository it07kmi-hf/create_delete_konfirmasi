<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SAP Python RFC Service Configuration  
    |--------------------------------------------------------------------------
    |
    | Configuration untuk koneksi ke Python RFC Service yang menangani
    | komunikasi dengan SAP system
    |
    */

    // ✅ URL Python RFC Service (NIK Configuration Service - Insert/Delete) - Port 5042
    'nik_api_url' => env('SAP_NIK_API_URL', 'http://192.168.254.242:5042'),

    // ✅ URL Python RFC Service (NIK Confirmation Service - Display/Sync) - Port 5040
    'nik_conf_api_url' => env('SAP_NIK_CONF_API_URL', 'http://192.168.254.242:5040'),

    // ✅ Timeout untuk HTTP request ke Python service (seconds)
    'timeout' => env('SAP_TIMEOUT', 30),

    // ✅ Connection retry settings
    'retry_times' => env('SAP_RETRY_TIMES', 3),
    'retry_sleep' => env('SAP_RETRY_SLEEP', 1000), // milliseconds

    // ✅ SAP Test Credentials (untuk background sync commands)
    'username' => env('SAP_TEST_USERNAME', ''),
    'password' => env('SAP_TEST_PASSWORD', ''),

    /*
    |--------------------------------------------------------------------------
    | SAP Connection Details (For Reference)
    |--------------------------------------------------------------------------
    |
    | These are configured in Python RFC Service (sap_nik_conf_service.py)
    | Listed here for documentation purposes only
    |
    */

    'connection' => [
        'ashost' => env('SAP_ASHOST', '192.168.254.154'),
        'sysnr' => env('SAP_SYSNR', '01'),
        'client' => env('SAP_CLIENT', '300'),
        'lang' => env('SAP_LANG', 'EN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Function Module Configuration
    |--------------------------------------------------------------------------
    */

    'function_modules' => [
        'nik_insert' => 'Z_RFC_INSERT_NIK_CONF',      // Insert/Update/Delete NIK Configuration
        'nik_display' => 'Z_RFC_DISPLAY_NIK_CONF',    // Display NIK Confirmation List
    ],
];