<?php

return [
    'customer' => [
        'prefix' => env('CUSTOMER_CODE_PREFIX', 'CTM'),
        'pad'    => (int) env('CUSTOMER_CODE_PAD', 4),
    ],
];
