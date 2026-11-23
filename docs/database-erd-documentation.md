# SumateraMall — Entity Relationship Diagram (ERD) & Database Documentation

Dokumentasi ini menjelaskan arsitektur basis data relasional **SumateraMall** (PostgreSQL 17), mencakup modul katalog wilayah & mall, interaksi pengguna & chatbot, modul reservasi restoran, serta validasi lima objek basis data akademik (**Trigger, Function, Procedure, View, dan Index**).

---

## 1. Entity Relationship Diagram (ERD)

~~~mermaid
erDiagram
    PROVINCES ||--o{ CITIES : "has"
    CITIES ||--o{ MALLS : "contains"
    MALLS ||--o{ STORES : "hosts"
    STORES }o--o{ CATEGORIES : "classified_via_store_categories"
    USERS ||--o{ FAVORITES : "bookmarks"
    USERS ||--o{ RESERVATIONS : "makes"
    USERS ||--o{ RESERVATION_STATUS_LOGS : "changes_status"
    STORES ||--o{ RESERVATIONS : "receives"
    RESERVATIONS ||--o{ RESERVATION_STATUS_LOGS : "tracks_history"

    PROVINCES {
        bigint id PK
        varchar name
        varchar resource_uri
        timestamp created_at
        timestamp updated_at
    }

    CITIES {
        bigint id PK
        bigint province_id FK
        varchar name
        varchar resource_uri
        timestamp created_at
        timestamp updated_at
    }

    MALLS {
        bigint id PK
        bigint city_id FK
        varchar name
        text address
        varchar opening_hours
        integer floors
        varchar category
        text area
        text website
        text description
        varchar source_namespace
        varchar resource_uri
        timestamp created_at
        timestamp updated_at
    }

    CATEGORIES {
        bigint id PK
        varchar name
        varchar resource_uri
        timestamp created_at
        timestamp updated_at
    }

    STORES {
        bigint id PK
        bigint mall_id FK
        varchar name
        text category_text
        text logo_url
        text location
        text description
        varchar status
        varchar opening_hours
        varchar phone
        numeric rating
        varchar source_namespace
        varchar resource_uri
        timestamp created_at
        timestamp updated_at
    }

    STORE_CATEGORIES {
        bigint store_id PK,FK
        bigint category_id PK,FK
    }

    USERS {
        bigint id PK
        varchar name
        varchar email UK
        timestamp email_verified_at
        varchar password
        varchar remember_token
        timestamp created_at
        timestamp updated_at
    }

    FAVORITES {
        bigint id PK
        bigint user_id FK
        varchar session_id
        varchar resource_uri
        varchar resource_type
        timestamp created_at
        timestamp updated_at
    }

    ITINERARIES {
        bigint id PK
        varchar session_id
        varchar title
        json payload
        timestamp starts_at
        timestamp created_at
        timestamp updated_at
    }

    CHAT_LOGS {
        bigint id PK
        varchar session_id
        text user_message
        text bot_reply
        varchar intent_detected
        text sparql_query_used
        timestamp created_at
        timestamp updated_at
    }

    RESERVATIONS {
        bigint id PK
        varchar reservation_code UK
        bigint user_id FK
        bigint store_id FK
        date reservation_date
        time reservation_time
        smallint guest_count
        varchar contact_name
        varchar contact_phone
        text special_requests
        varchar status
        text cancellation_reason
        text admin_notes
        bigint reviewed_by FK
        timestamp reviewed_at
        timestamp cancelled_at
        timestamp created_at
        timestamp updated_at
    }

    RESERVATION_STATUS_LOGS {
        bigint id PK
        bigint reservation_id FK
        bigint changed_by FK
        varchar from_status
        varchar to_status
        text note
        timestamp created_at
    }
~~~

---

## 2. Kamus Data Utama & Relasi Tabel

### A. Modul Wilayah & Katalog Mall (`provinces`, `cities`, `malls`, `categories`, `stores`, `store_categories`)
* **`provinces` & `cities`**: Hierarki geografis 10 provinsi dan kota/kabupaten di Pulau Sumatra (`cities.province_id -> provinces.id`).
* **`malls`**: Daftar pusat perbelanjaan per kota (`malls.city_id -> cities.id`) dilengkapi atribut alamat, jam operasional, jumlah lantai, dan URI semantik (`resource_uri`).
* **`stores` & `store_categories`**: Daftar tenant/restoran di dalam mall (`stores.mall_id -> malls.id`) yang terhubung *many-to-many* ke `categories` melalui tabel pivot `store_categories`.

### B. Modul Interaksi Pengguna & Chatbot (`users`, `favorites`, `itineraries`, `chat_logs`)
* **`users`**: Menyimpan akun pengguna terdaftar yang terhubung ke fitur favorit dan reservasi.
* **`favorites` & `itineraries`**: Menyimpan daftar mall/toko favorit dan rencana perjalanan multi-mall berbasis `user_id` maupun `session_id`.
* **`chat_logs`**: Log percakapan asisten chatbot beserta hasil deteksi intent (`intent_detected`) dan query SPARQL yang dijalankan.

### C. Modul Reservasi Restoran (`reservations`, `reservation_status_logs`)
* **`reservations`**: Menyimpan data pemesanan meja/kunjungan pada tenant/restoran:
  * `reservation_code` (Unique): Kode bukti reservasi otomatis (format `RSV-YYYYMMDD-XXXXXX`).
  * `user_id` (`FK -> users.id`): Pemilik reservasi.
  * `store_id` (`FK -> stores.id`): Restoran/tenant tujuan reservasi.
  * `status`: Siklus status reservasi (`pending`, `confirmed`, `rejected`, `cancelled`, `completed`).
  * `reviewed_by` (`FK -> users.id`): Admin/pengelola yang memverifikasi reservasi.
* **`reservation_status_logs`**: Tabel audit riwayat perubahan status reservasi (`reservation_id -> reservations.id`, `changed_by -> users.id`) yang mencatat status asal (`from_status`), status baru (`to_status`), catatan (`note`), dan waktu perubahan (`created_at`).

---

## 3. Validasi 5 Objek Basis Data PostgreSQL

Skrip implementasi tersedia di `database/sql/sumateramall-postgresql-academic-components.sql` dan telah divalidasi pada **PostgreSQL 17**:

| No | Jenis Objek | Nama Objek | Target Tabel | Fungsi & Hasil Validasi |
| :- | :--- | :--- | :--- | :--- |
| 1 | **Trigger** | `trg_chat_logs_validate_insert` & `trg_reservations_status_audit` | `chat_logs`, `reservations` | Memvalidasi input chat tidak kosong & otomatis mencatat log perubahan status reservasi ke `reservation_status_logs`. |
| 2 | **Function** | `fn_chat_count_by_session()` & `fn_store_active_reservation_count()` | `chat_logs`, `reservations` | Menghitung total interaksi per sesi chat dan jumlah reservasi aktif (`pending`/`confirmed`) per toko. |
| 3 | **Procedure** | `sp_expire_pending_reservations()` | `reservations` | Memperbarui status reservasi `pending` yang telah melewati batas tanggal menjadi `cancelled` secara batch. |
| 4 | **View** | `vw_chat_intent_activity` & `vw_store_reservation_summary` | `chat_logs`, `stores`, `malls`, `reservations` | Menyajikan ringkasan statistik intent harian dan rekap jumlah reservasi serta total tamu terkonfirmasi per toko. |
| 5 | **Index** | `idx_chat_logs_session_created` & `idx_reservations_store_date_status` | `chat_logs`, `reservations` | Mempercepat pencarian riwayat percakapan per sesi dan pengecekan jadwal reservasi per toko & tanggal. |