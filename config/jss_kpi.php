<?php

return [
    'manado' => [
        'customer_ids' => [1],
        'thresholds' => [
            'dwelling_days' => 6,
            'sailing_days'  => 10,
            'dooring_days'  => 3,
            'total_days'    => [
                'normal' => 19,
                'urgent' => 18,
            ],
            'etd_gap_max' => 6,
        ],
    ],
];

