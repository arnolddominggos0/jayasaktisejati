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
        'stage-asc',
        'stage-desc',
    ],

    'default_route' => 'tam',

];