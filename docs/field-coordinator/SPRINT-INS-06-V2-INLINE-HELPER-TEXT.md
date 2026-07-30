# Sprint INS-06 v2 — Replace Tooltip with Inline Helper Text

**Status:** IMPLEMENTED & tervalidasi penuh, termasuk regression INS-03/INS-04/OPS-08.
**Tanggal:** 24 Juli 2026
**Konteks:** revisi atas Sprint INS-06 (tooltip hover/klik) — tooltip dicabut total, diganti helper text inline yang selalu terlihat.

---

## 1. File yang Berubah

| File | Perubahan |
|---|---|
| `app/Services/InspectionDraftAutoCreate.php` | Method `criteriaTooltip()` **di-rename** menjadi `criteriaHelperText()`. Logic/isi method (baca `config('unit_inspection_templates')`, gabungkan bullet `criteria`) **tidak berubah** — murni rename + docblock diperbarui. |
| `app/Filament/FC/Pages/InspectUnitPage.php` | Field `item_name` di Repeater `items`: `->hintIcon(...)` (ikon info + tooltip hover/klik) **dihapus total**, diganti `->helperText(fn (Get $get) => InspectionDraftAutoCreate::criteriaHelperText(...))` — teks kecil, selalu tampil di bawah field. |
| `config/unit_inspection_templates.php` | Hanya docblock diperbarui (referensi "tooltip" → "helper text"). **Data `criteria` per item tidak berubah sama sekali** — sesuai Scope 3 ("gunakan mapping yang sudah ada"). |
| `resources/views/.../inspect-unit.blade.php` | Hanya komentar penjelas diperbarui (referensi ke `hintIcon`/tooltip → `helperText`). **Tidak ada markup Blade yang ditambah/diubah** — helper text dirender otomatis oleh komponen field Filament, bukan oleh apa pun yang ditulis di file ini. |

**Tidak ada migration, tidak ada perubahan database, tidak ada file baru.**

---

## 2. Cara Helper Text Diambil (Scope 3)

**Sumber data SAMA PERSIS dengan sprint sebelumnya — tidak dibangun ulang.** Sesuai instruksi ("Jika saat ini guidance masih berupa mapping di backend, tetap gunakan mapping tersebut"), perubahan sprint ini murni pada **cara merender**, bukan **dari mana data diambil**:

```
config/unit_inspection_templates.php   (data 'criteria' per item — tidak berubah)
        ↓
InspectionDraftAutoCreate::criteriaHelperText($category, $itemName)   (rename dari criteriaTooltip(), logic sama)
        ↓
InspectUnitPage::form() → TextInput::make('item_name')->helperText(fn (Get $get) => ...)
        ↓
Filament field-wrapper merender otomatis di bawah field, tanpa markup Blade tambahan
```

Blade **tidak pernah menyentuh teks kriteria** — baik sebelum maupun sesudah revisi ini. Mekanisme render berpindah dari `hintIcon()` (ikon + tooltip Alpine/Tippy, perlu hover/klik) ke `helperText()` (baris teks statis di bawah field, dirender langsung oleh Filament's `field-wrapper/helper-text.blade.php`, style bawaan `text-sm text-gray-500 dark:text-gray-400` — kecil dan abu-abu **tanpa CSS kustom apa pun**, otomatis memenuhi Scope 4).

Item tanpa entri `criteria` di config → closure mengembalikan `null` → `helperText()` tidak merender baris apa pun untuk item itu (bukan baris kosong).

---

## 3. Contoh Tampilan

Diambil langsung dari `criteriaHelperText()` dipanggil terhadap item nyata di database (inspection #280, shipment #235, unit #233):

```
Item
Lampu Depan
OK apabila: • Lampu kiri dan kanan menyala normal. • Tidak retak. • Tidak pecah. • Masing-masing terpasang dengan baik.
                                                                              [ OK ]  [ NG ]

Item
Velg
OK apabila: • Tidak penyok / retak. • Tidak baret dalam. • Baut / mur lengkap dan kencang.
                                                                              [ OK ]  [ NG ]

Item
Buku Service
OK apabila: • Tersedia, sesuai unit. • Tidak rusak / sobek. • Data terisi lengkap.
                                                                              [ OK ]  [ NG ]
```

Helper text tampil **langsung**, tanpa hover/klik apa pun — dikonfirmasi lewat pemanggilan langsung `InspectUnitPage::mount()` terhadap 18 item nyata stage pickup: seluruhnya menghasilkan teks helper (tidak ada yang "HIDDEN").

---

## 4. Bukti Tooltip Telah Dihapus (Scope 1)

| Elemen yang wajib hilang | Bukti |
|---|---|
| Ikon info (`heroicon-o-information-circle`) pada checklist | Grep di `InspectUnitPage.php` → **0 match** (sebelumnya 1, pada field `item_name`) |
| Pemanggilan `hintIcon()`/`hintIconTooltip()` | Grep di seluruh `app/` → **0 match** |
| Method lama `criteriaTooltip()` | `method_exists(InspectionDraftAutoCreate::class, 'criteriaTooltip')` → **`false`** — bukan sekadar tidak dipanggil, method-nya sendiri sudah tidak ada (bersih, tidak ada kode mati/duplikat) |
| Interaksi hover/klik untuk membaca standar | Dihapus bersama `hintIcon()` — `helperText()` Filament tidak memiliki mekanisme hover/klik sama sekali, selalu dirender langsung di HTML halaman |

`criteriaHelperText()` (pengganti) **ada dan berfungsi**: `method_exists(..., 'criteriaHelperText')` → **`true`**.

---

## 5. Validasi Teknis

| Uji | Hasil |
|---|---|
| `php -l` pada 3 file PHP yang diubah | ✅ Bersih |
| `config:clear`, `view:clear`, `composer dump-autoload` | ✅ Sukses |
| Rename method bersih (lama hilang, baru ada) | ✅ |
| `criteriaHelperText()` untuk item nyata (Lampu Depan, Ban, Velg) — isi teks identik dengan sebelum rename | ✅ |
| Item tak dikenal / kosong → `null` | ✅ |
| Seluruh 36 pasangan (category, item_name) nyata di database → semua resolve, 0 unresolved | ✅ |
| `InspectUnitPage::mount()` end-to-end (shipment #235 / unit #233, inspection #280, sebagai field_coordinator sungguhan) | ✅ Berhasil, tidak ada exception dari `helperText()` closure |
| Seluruh 18 item checklist nyata pada inspection #280 → helper text tampil untuk semuanya | ✅ |
| `migrate:status` — tidak ada migration baru | ✅ |

### Regression INS-03 / INS-04 (Finalization / Single Entry Point)
- `InspectUnitPage::submit()` tidak disentuh sprint ini — validasi PIC/Jabatan/Signature, gate decision, audit log (`Log::info('INSPECTION FINALIZED', ...)`), lock (`isReadOnly`) semuanya identik.
- Single Entry Point tetap `InspectUnitPage.php` — tidak ada file lain yang menulis data inspeksi.

### Regression OPS-08
```
Shipment #228 (JSS0726SH0001):         handoverCleared=false, loadingCleared=false
Shipment #229 (JSS0726SH0002):         handoverCleared=false, loadingCleared=false
Shipment #230 (OPS08-TRIAL-154113):    handoverCleared=false, loadingCleared=false
Shipment #231 (OPS08-NEG-154323):      handoverCleared=false, loadingCleared=false
Shipment #232 (OPS08-CAP-154323):      handoverCleared=false, loadingCleared=false
Shipment #233 (OPS08-CAPFIX-A-154435): handoverCleared=false, loadingCleared=false
Shipment #234 (OPS08-CAPFIX-B-154436): handoverCleared=false, loadingCleared=false
Shipment #235 (JSS0726SH0003):         handoverCleared=false, loadingCleared=false
```
**Identik byte-for-byte** dengan seluruh baseline sebelumnya (ARCH-01 → ... → INS-06 v1) — `Shipment.php` tidak pernah disentuh sprint ini maupun sprint sebelumnya.

---

## Konfirmasi Batas

- ✅ Tidak ada tooltip/ikon info/popover/hover interaction lagi — dibuktikan lewat grep dan `method_exists()`.
- ✅ Helper text tampil pada setiap item checklist, langsung terlihat tanpa interaksi tambahan.
- ✅ Ukuran kecil + abu-abu — bawaan komponen `helperText()` Filament (`text-sm text-gray-500`), tanpa CSS kustom.
- ✅ Sumber teks tetap mapping backend yang sama (`config/unit_inspection_templates.php` via `InspectionDraftAutoCreate`) — tidak dibangun ulang, tidak di-hardcode di Blade.
- ✅ Tidak ada perubahan Checklist, Approval, Signature, Finalize, Guard, Audit, Database, atau Single Entry Point.
