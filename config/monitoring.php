<?php

return [

    'page_size' => 50,

    'poll_interval' => 60,

    'cache_ttl' => 30,

    'demurrage_days' => 7,

    'stuck_days' => 3,

    'exception_types' => [
        'delay',
        'ng',
        'hold',
        'demurrage',
        'missing_voyage',
        'pdi_pending',
    ],

    'group_modes' => [
        'flat',
        'sppb',
        'voyage',
    ],

    'sort_options' => [
        'exception-first',
        'age-desc',
        'age-asc',
        'progress-desc',
        'progress-asc',
        'eta-asc',
        'eta-desc',
        'voyage-asc',
        'voyage-desc',
        'customer-asc',
        'customer-desc',
        'stage-asc',
        'stage-desc',
    ],

    'status_options' => [
        'active',
        'finished',
        'all',
    ],

    'page_size_options' => [
        25,
        50,
        100,
        200,
    ],

    'default_route' => 'tam',

];