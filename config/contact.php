    <?php

    return [
        /*
        |--------------------------------------------------------------------------
        | Contact Information
        |--------------------------------------------------------------------------
        |
        | Default contact information for PT Jaya Sakti Sejati.
        | Used across the website for WhatsApp, phone, and email.
        |
        */

        'email' => 'jayasaktisejati1@gmail.com',

        'website' => 'jayasaktisejati.com',

        /*
        |--------------------------------------------------------------------------
        | Branch Offices
        |--------------------------------------------------------------------------
        |
        | Contact details for each branch office.
        | Used in Mode Pengiriman and Kantor Kami sections.
        |
        */

        'branches' => [
            'surabaya' => [
                'name' => 'Surabaya (Kantor Pusat)',
                'short_name' => 'Surabaya',
                'whatsapp' => '6281265559397',
                'contact_person' => 'Admin Surabaya',
                'address' => 'Jl. Kalianget No. 110, Perak Utara, Kec. Pabean Cantian, Surabaya, Jawa Timur 60165'
            ],
            'jakarta' => [
                'name' => 'Jakarta (Kantor Cabang)',
                'short_name' => 'Jakarta',
                'whatsapp' => '6285270909923',
                'contact_person' => 'Admin Jakarta (Pak Zeki)',
                'address' => 'Komp. Lodan Center Blok T 01-02, Jl. Lodan Raya No. 2, Ancol, Jakarta Utara 14440',
            ],
            'manado' => [
                'name' => 'Manado (Kantor Cabang)',
                'short_name' => 'Manado',
                'whatsapp' => '624318142227',
                'contact_person' => 'Admin Manado',
                'address' => 'Jl. Kombos Atas No.84Kairagi Satu, Kec. Mapanget, Kota Manado, Sulawesi Utara',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Default Branch
        |--------------------------------------------------------------------------
        |
        | Default branch to use for general inquiries (Mode Pengiriman, etc.)
        |
        */

        'default_branch' => 'surabaya',

        /*
        |--------------------------------------------------------------------------
        | WhatsApp Message Templates
        |--------------------------------------------------------------------------
        |
        | Pre-defined message templates for different shipping modes.
        |
        */

        'whatsapp_templates' => [
            'door_to_door' => 'Halo, saya ingin menanyakan tentang pengiriman Door to Door. Bisa dibantu?',
            'port_to_door' => 'Halo, saya ingin menanyakan tentang pengiriman Port to Door. Bisa dibantu?',
            'port_to_port' => 'Halo, saya ingin menanyakan tentang pengiriman Port to Port. Bisa dibantu?',
            'door_to_port' => 'Halo, saya ingin menanyakan tentang pengiriman Door to Port. Bisa dibantu?',
            'lcl' => 'Halo, saya ingin menanyakan tentang pengiriman LCL Consolidation. Bisa dibantu?',
            'fcl' => 'Halo, saya ingin menanyakan tentang pengiriman FCL Full Container. Bisa dibantu?',
            'project_cargo' => 'Halo, saya ingin menanyakan tentang Project Cargo. Bisa dibantu?',
            'general' => 'Halo, saya ingin menanyakan informasi tentang layanan pengiriman. Bisa dibantu?',
        ],
    ];
