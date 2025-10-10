<?php

return [
    'manado' => [
        'branch_ids' => [2],
        'thresholds' => [
            'dwelling_days' => 5,
            'sailing_days'  => 10,
            'dooring_days'  => 2,
            'total_days'    => [
                'normal' => 19,
                'urgent' => 17,
            ],
        ],
        'coverage_city_ids' => [2, 6, 9, 10, 11, 12, 13, 14, 15, 16, 17],
        'city_overrides' => [],
        'customer_city_overrides' => [],
        'depot_ids' => [],
    ],
];
