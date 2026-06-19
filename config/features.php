<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Self-Service Advertising
    |--------------------------------------------------------------------------
    |
    | When disabled, all self-service ad payment flows and webhook processing
    | are inactive. Existing admin-managed campaigns continue unchanged.
    |
    */
    'self_service_ads' => env('SELF_SERVICE_ADS', false),

];
