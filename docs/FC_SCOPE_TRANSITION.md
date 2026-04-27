# FC Scope Transition (Pre-Merge Gate)

## 1) Gate lint + test commands

Jalankan command berikut sebelum merge:

```bash
./vendor/bin/pint --test
php artisan test --filter=FC
php artisan test --filter=Shipment
php artisan test --filter=Tracking
php artisan test --filter=Print
```

## 2) Bukti response terbaru AuthController

### Contoh response `POST /api/login`

```json
{
  "user": {
    "id": 12,
    "name": "FC MDO",
    "email": "fc.mdo@example.com",
    "branch_id": 3,
    "scope_branch_id": 3,
    "effective_branch_id": 3
  },
  "branch": {
    "id": 3,
    "code": "MDO",
    "name": "Manado"
  },
  "roles": ["field_coordinator"],
  "is_super_admin": false,
  "is_office_admin": false,
  "is_field_coordinator": true,
  "is_customer": false,
  "token": "<sanctum_token>"
}
```

### Contoh response `GET /api/me`

```json
{
  "user": {
    "id": 12,
    "name": "FC MDO",
    "email": "fc.mdo@example.com",
    "branch_id": 3,
    "scope_branch_id": 3,
    "effective_branch_id": 3
  },
  "branch": {
    "id": 3,
    "code": "MDO",
    "name": "Manado"
  },
  "roles": ["field_coordinator"]
}
```

## 3) Note migration client

- Gunakan `effective_branch_id` sebagai **source of truth** baru untuk scoping client.
- `branch_id` adalah field **legacy** dan dipertahankan sementara untuk kompatibilitas transisi.

## 4) Troubleshooting cepat untuk error 500 di `/fc/briefing-sessions`

Jika halaman FC briefing tiba-tiba 500 setelah rollout scope canonical, cek poin ini:

1. Pastikan migration canonical scope sudah jalan (`users.scope_branch_id`, `users.scope_unit_id`, `users.scope_unit_type`).
2. Pastikan data FC sudah backfill canonical scope (minimal `scope_branch_id`).
3. Cek log aplikasi:

```bash
tail -n 200 storage/logs/laravel.log
```

4. Validasi status migration:

```bash
php artisan migrate:status
```

## 5) Posisi terhadap roadmap FC-05

- Ini **bukan ganti tujuan** setelah FC-05.
- Ini adalah gate transisi wajib (stabilisasi + migrasi client) sebelum masuk task lanjutan.
- Urutan ringkas: FC-05 selesai → gate transisi hijau → baru lanjut FC-06/FC-07.

