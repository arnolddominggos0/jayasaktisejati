<?php

return [
    // 'internal' (default): portal customer dimatikan, tombol "Buat Akun Portal" disembunyikan
    // 'customer_monolith': portal customer gabung aplikasi ini (pakai tabel users yang sama)
    // 'customer_saas': portal terpisah; tombol tetap disembunyikan di monolith
    'auth_mode' => env('PORTAL_AUTH_MODE', 'internal'),
];
