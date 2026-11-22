# Dokumentasi Backend Voucher

Branch: `feature/jelita-backend`. Basis data: PostgreSQL 17 (Docker). Semua endpoint berawalan `/api` dan membalas JSON.

## Batasan sementara (menunggu keputusan tim)

- Belum ada login. `user_id` dikirim lewat request, jadi siapa pun bisa bertindak atas nama user lain. Setelah autentikasi tersedia, `user_id` diambil dari sesi atau token.
- Belum ada peran admin. Endpoint admin dijaga header `X-Admin-Token` yang nilainya diambil dari `.env` (`VOUCHER_ADMIN_TOKEN`).
- Pembayaran hanya simulasi (tidak ada payment gateway).

## Skema database

### `vouchers`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| code | varchar(50) | UNIQUE |
| title | varchar(255) | |
| description | text | nullable |
| type | varchar(10) | `free` atau `paid` |
| price | integer | default 0 |
| stock | integer | default 0 |
| max_per_user | smallint | default 1 |
| valid_from, valid_until | timestamp | nullable |
| is_active | boolean | default true |

CHECK constraint:
- `vouchers_type_check`: type hanya `free` atau `paid`
- `vouchers_stock_check`: `stock >= 0`
- `vouchers_price_check`: `free` harus berharga 0, `paid` harus lebih dari 0
- `vouchers_valid_check`: `valid_until` harus setelah `valid_from`

### `user_vouchers` (voucher milik pengguna)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| user_id | FK users | ON DELETE CASCADE |
| voucher_id | FK vouchers | ON DELETE RESTRICT |
| status | varchar(10) | `active`, `used`, `expired` (CHECK) |
| claimed_at, used_at, expires_at | timestamp | |

Index: `(user_id, status)` dan `voucher_id`.

### `voucher_transactions`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| user_id | FK users | ON DELETE CASCADE |
| voucher_id | FK vouchers | ON DELETE RESTRICT |
| user_voucher_id | FK user_vouchers | nullable, ON DELETE SET NULL |
| type | varchar(10) | `claim` atau `purchase` (CHECK) |
| amount | integer | `>= 0` (CHECK) |
| payment_status | varchar(10) | `pending`, `paid`, `failed` (CHECK) |
| payment_ref | varchar(60) | UNIQUE, nullable |

Index: `(user_id, created_at)` dan `voucher_id`.

Relasi: satu voucher punya banyak `user_vouchers` dan `voucher_transactions`. Voucher yang sudah punya riwayat tidak bisa dihapus (FK RESTRICT).

## Endpoint publik

| Method | Path | Fungsi |
|---|---|---|
| GET | `/api/vouchers` | Daftar voucher aktif dan belum kedaluwarsa (10 per halaman) |
| GET | `/api/vouchers/{id}` | Detail voucher aktif |
| POST | `/api/vouchers/{id}/claim` | Claim voucher gratis |
| POST | `/api/vouchers/{id}/purchase` | Beli voucher berbayar (pembayaran simulasi) |
| POST | `/api/user-vouchers/{id}/redeem` | Pakai voucher milik pengguna |
| GET | `/api/voucher-history?user_id=` | Riwayat voucher dan transaksi pengguna |

Endpoint POST dibatasi 30 permintaan per menit.

### Contoh
```
POST /api/vouchers/5/claim          body: user_id=1
201 {"data":{"id":8,"user_id":1,"voucher_id":5,"status":"active", ... }}

POST /api/vouchers/5/claim          (kedua kali)
422 {"message":"Batas klaim per pengguna tercapai."}

POST /api/vouchers/6/purchase       body: user_id=1&simulate_payment=fail
402 {"data":{"type":"purchase","amount":25000,"payment_status":"failed", ... }}

POST /api/vouchers/6/purchase       body: user_id=1&simulate_payment=success
201 {"data":{"type":"purchase","payment_status":"paid","user_voucher_id":9, ... }}

POST /api/user-vouchers/8/redeem    body: user_id=1
200 {"data":{"id":8,"status":"used","used_at":"..."}}

POST /api/user-vouchers/8/redeem    (kedua kali)
422 {"message":"Voucher sudah dipakai atau kedaluwarsa."}

POST /api/user-vouchers/9/redeem    body: user_id=2   (milik user lain)
422 {"message":"Voucher milik pengguna tidak ditemukan."}
```

`simulate_payment` bernilai `success` (default) atau `fail`. Kode respons: 201 berhasil dibuat, 402 pembayaran simulasi gagal, 422 aturan bisnis atau validasi tidak terpenuhi, 404 voucher tidak ditemukan.

## Endpoint admin

Semua membutuhkan header `X-Admin-Token`. Tanpa token yang benar: 401. Jika token belum diatur di `.env`: 503.

| Method | Path | Fungsi |
|---|---|---|
| GET | `/api/admin/vouchers` | Semua voucher termasuk nonaktif (20 per halaman) |
| GET | `/api/admin/vouchers/{id}` | Detail |
| POST | `/api/admin/vouchers` | Buat voucher |
| PUT/PATCH | `/api/admin/vouchers/{id}` | Ubah voucher |
| DELETE | `/api/admin/vouchers/{id}` | Hapus voucher (409 jika sudah punya riwayat; nonaktifkan dengan `is_active=0`) |

Validasi: voucher `free` harus berharga 0, `paid` harus berharga lebih dari 0, `valid_until` harus setelah `valid_from`. Aturan ini sama dengan CHECK constraint di database.

Catatan pengujian: pada PowerShell 5.1, kirim body dengan `-ContentType "application/json"` dan `ConvertTo-Json`. Tanpa `Content-Type`, body request PUT dapat tidak terbaca dan tidak ada yang berubah.

## Penanganan race condition

Claim dan pembelian dijalankan dalam satu transaksi (`DB::transaction`). Baris voucher dikunci dengan `SELECT ... FOR UPDATE` (`lockForUpdate()`), lalu validasi (aktif, masa berlaku, stok, batas per pengguna) dan pengurangan stok dilakukan di dalam kunci itu. Permintaan lain untuk voucher yang sama menunggu giliran. Lapis kedua: `CHECK (stock >= 0)` di database.

### Hasil uji
- Skenario: 20 pengguna berbeda mengklaim satu voucher gratis (stok 5) pada detik yang sama, memakai 20 proses PHP terpisah.
- Hasil: 5 berhasil dan 15 gagal dengan pesan "Stok voucher habis."
- Pemeriksaan database: `stock = 0` dan jumlah baris `user_vouchers` untuk voucher itu = 5.
- Uji constraint: insert dengan `stock = -1` ditolak (`vouchers_stock_check`).

## Struktur kode

- Migration: `create_vouchers_table`, `create_user_vouchers_table`, `create_voucher_transactions_table`
- Model: `Voucher`, `UserVoucher`, `VoucherTransaction`
- Logika bisnis: `app/Services/VoucherService.php`
- Controller: `VoucherController`, `AdminVoucherController`
- Middleware: `EnsureAdminToken`
- Route: `routes/api.php` (didaftarkan lewat satu baris `api:` di `bootstrap/app.php`)
- Konfigurasi: `config/voucher.php`, variabel `.env` `VOUCHER_ADMIN_TOKEN`