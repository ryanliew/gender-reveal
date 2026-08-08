<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Host Access
    |--------------------------------------------------------------------------
    |
    | The host control screen lives at /host/{secret}. The secret keeps the
    | URL unguessable, and the PIN is required again for every reveal or
    | reset action so a leaked URL alone cannot spoil the party.
    |
    */

    'host_secret' => env('PARTY_HOST_SECRET', 'change-me-secret'),

    'host_pin' => env('PARTY_HOST_PIN', '0000'),

];
