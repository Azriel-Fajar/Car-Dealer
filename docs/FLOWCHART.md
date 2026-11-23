# Flowchart & Diagram — Pemecut Motors

Diagram di bawah ditulis dengan Mermaid, sehingga tampil otomatis di GitHub, dan akan diekspor ke PNG/SVG untuk laporan di Fase 7. Ini adalah draf Fase 1: yang sudah berjalan hanya alur autentikasi dan arsitektur dasar, sisanya adalah rencana dari spesifikasi dan akan diperbarui di akhir setiap fase. Penjelasan teknis ada di [DOKUMENTASI.md](DOKUMENTASI.md).

## Alur Guest → Customer

```mermaid
flowchart TD
    start(["Guest membuka situs"]) --> browse["Jelajah, filter, dan bandingkan mobil"]
    browse --> configure["Configure mobil (disimpan di session)"]
    configure --> checkout_btn["Klik Checkout"]
    checkout_btn --> logged_in{"Sudah login?"}
    logged_in -->|belum| auth_page["Login atau Register"]
    auth_page -->|kembali ke URL intended| checkout_form
    logged_in -->|sudah| checkout_form["Checkout: data kontak"]
    checkout_form --> scheme["Pilih skema bayar (Cash atau Kredit)"]
    scheme --> delivery["Pilih pickup showroom atau delivery"]
    delivery --> review["Review pesanan"]
    review --> order_created["Order dibuat: pending_payment"]
    order_created --> instructions["Halaman instruksi bayar: nominal, rekening, batas waktu"]
    instructions --> upload["Upload bukti (jpg/png/pdf)"]
    upload --> waiting["Status: waiting_verification"]
    waiting --> verify{"Sales memverifikasi"}
    verify -->|disetujui| booking_paid["Status: booking_paid"]
    verify -->|ditolak dengan alasan| reupload["Customer melihat alasan dan upload ulang"]
    reupload --> upload
    booking_paid --> account["Pantau timeline di /account"]
```

## Verifikasi Pembayaran & Proses Order (Sales)

```mermaid
flowchart TD
    queue["Antrean Verifikasi Pembayaran di /dashboard"] --> preview["Lihat bukti transfer di modal preview"]
    preview --> decision{"Bukti valid?"}
    decision -->|ya, approve| booking_paid["Order: booking_paid"]
    decision -->|tidak, reject + alasan| back_pending["Order kembali ke pending_payment"]
    back_pending --> customer_upload["Customer upload ulang bukti"]
    customer_upload --> queue
    booking_paid --> assign["Sales assign order ke diri sendiri"]
    assign --> processing["Status: processing (verifikasi dokumen atau approval leasing)"]
    processing --> ready["Status: ready (unit siap)"]
    ready --> settle["Catat pelunasan secara manual"]
    settle --> completed["Status: completed"]
```

## Alur Admin

```mermaid
flowchart TD
    admin(["Admin login"]) --> dashboard["/dashboard"]
    dashboard --> staff_tasks["Semua tugas sales: verifikasi, orders, test drive, inquiries"]
    dashboard --> master["Master data"]
    dashboard --> users["Users dan role"]
    dashboard --> reports["Laporan"]
    dashboard --> advisor["Kontrol AI Advisor"]
    master --> cars["Cars: info, spesifikasi, warna, opsi, foto, hotspot, stok"]
    master --> brands["Brands"]
    master --> categories["Categories"]
    master --> showrooms["Showrooms"]
    users --> create_sales["Buat akun sales"]
    users --> deactivate["Nonaktifkan akun"]
    reports --> sales_chart["Grafik penjualan per bulan"]
    reports --> revenue["Revenue"]
    reports --> top_model["Top model"]
    advisor --> kill_switch["Kill switch"]
    advisor --> budget["Budget token harian"]
    advisor --> logs["Log dan statistik"]
```

## Booking Test Drive

```mermaid
flowchart TD
    detail["Customer di halaman detail mobil"] --> schedule["Klik Jadwalkan Test Drive"]
    schedule --> logged_in{"Sudah login?"}
    logged_in -->|belum| login["Login atau Register lalu kembali"]
    login --> pick
    logged_in -->|sudah| pick["Pilih showroom dan slot waktu"]
    pick --> requested["Status: requested"]
    requested --> sales_review{"Sales menindaklanjuti"}
    sales_review -->|konfirmasi| confirmed["Status: confirmed"]
    sales_review -->|reschedule| pick
    sales_review -->|batalkan| cancelled["Status: cancelled"]
    confirmed --> done["Test drive berlangsung"]
    done --> completed["Status: completed"]
    confirmed -->|dibatalkan| cancelled
```

## State Diagram Status Order

```mermaid
stateDiagram-v2
    [*] --> pending_payment
    pending_payment --> waiting_verification : upload bukti
    waiting_verification --> booking_paid : approve
    waiting_verification --> pending_payment : reject
    booking_paid --> processing
    processing --> ready
    ready --> completed
    pending_payment --> cancelled
    waiting_verification --> cancelled
    booking_paid --> cancelled
    processing --> cancelled
    ready --> cancelled
    completed --> [*]
    cancelled --> [*]
    note right of cancelled
        Draf, final di Fase 4/5
    end note
```

## State Diagram Status Pembayaran

```mermaid
stateDiagram-v2
    [*] --> submitted : customer upload bukti
    submitted --> approved : sales approve
    submitted --> rejected : sales reject + alasan
    approved --> [*]
    rejected --> [*]
```

## Sequence Autentikasi & Session

```mermaid
sequenceDiagram
    actor Guest
    participant App as Laravel (route /checkout)
    participant Auth as Middleware auth
    participant Session as Session (tabel sessions)
    participant Fortify as Fortify (POST /login)
    participant Redirect as RoleRedirectResponse

    Guest->>App: GET /checkout
    App->>Auth: cek login
    Auth->>Session: simpan url.intended = /checkout
    Auth-->>Guest: redirect ke /login
    Guest->>Fortify: POST /login (email, password)
    Fortify->>Fortify: authenticateUsing: cek password dan is_active
    alt akun nonaktif
        Fortify-->>Guest: error "Akun Anda telah dinonaktifkan"
    else kredensial valid
        Fortify->>Session: regenerate session id
        Fortify->>Redirect: toResponse
        alt role customer
            Redirect->>Session: baca url.intended
            Redirect-->>Guest: redirect ke /checkout (default /account)
        else role admin atau sales
            Redirect->>Session: hapus url.intended
            Redirect-->>Guest: redirect ke /dashboard
        end
    end
```

## Pipeline AI Advisor

```mermaid
flowchart TD
    req(["POST /ai-advisor/messages"]) --> g1{"1. throttle:ai-advisor"}
    g1 -->|limit lewat| r429["429 + pesan ramah dan countdown"]
    g1 -->|lolos| g2{"2. Validasi FormRequest"}
    g2 -->|tidak valid| invalid["422 validasi gagal"]
    g2 -->|valid| g3{"3. Kill switch dan budget harian"}
    g3 -->|mati atau budget habis| off["Advisor nonaktif"]
    g3 -->|aktif| g4{"4. Lockout abuse"}
    g4 -->|terkunci 24 jam| locked["Ditolak: lockout"]
    g4 -->|bebas| g5{"5. Turnstile (pesan pertama guest)"}
    g5 -->|gagal| bot["Ditolak: verifikasi bot gagal"]
    g5 -->|lolos| g6{"6. Cache::lock satu request aktif per sesi"}
    g6 -->|sedang diproses| busy["Ditolak: request paralel"]
    g6 -->|lock didapat| g6b{"6b. AdvisorInputFilter"}
    g6b -->|karakter, pola kode atau link| blocked["status=blocked + block_reason (tidak dikirim ke OpenAI)"]
    g6b -->|prompt injection| flagged_inj["Ditolak + dihitung sebagai flag"]
    g6b -->|bersih| g7{"7. OpenAI Moderation (input)"}
    g7 -->|flagged| flagged_mod["Ditolak + flag, 3 flag per jam jadi lockout 24 jam"]
    g7 -->|aman| g8["8. Susun konteks: katalog published dan riwayat 10 pesan dari session"]
    g8 --> g9["9. Panggil gpt-4o-mini: structured output tanpa tools"]
    g9 --> g10{"10. topic = off_topic?"}
    g10 -->|ya| off_topic["Balasan baku, teks AI dibuang"]
    g10 -->|tidak| g11["11. AdvisorOutputSanitizer"]
    g11 --> g12["12. Buang car_ids yang bukan mobil published"]
    g12 --> g13["13. Tulis ai_chat_logs"]
    off_topic --> g13
    g13 --> g14(["14. Kembalikan teks dan data kartu mobil"])
```

## ERD

```mermaid
erDiagram
    users ||--o{ configurations : "membuat"
    users ||--o{ wishlists : "menyimpan"
    users ||--o{ orders : "memesan"
    users ||--o{ test_drives : "booking"
    users ||--o{ ai_chat_logs : "mengirim"
    brands ||--o{ cars : "memiliki"
    categories ||--o{ cars : "mengelompokkan"
    cars ||--o{ car_images : "memiliki"
    cars ||--o{ car_colors : "memiliki"
    cars ||--o{ car_options : "memiliki"
    cars ||--o{ configurations : "dikonfigurasi"
    cars ||--o{ wishlists : "disimpan"
    cars ||--o{ test_drives : "dijadwalkan"
    car_colors ||--o{ configurations : "dipilih"
    configurations ||--o{ configuration_option : "berisi"
    car_options ||--o{ configuration_option : "dipilih"
    configurations ||--o{ orders : "menjadi"
    showrooms ||--o{ orders : "pickup"
    showrooms ||--o{ test_drives : "lokasi"
    orders ||--o{ order_status_histories : "riwayat"
    orders ||--o{ payments : "dibayar"
    users ||--o{ order_status_histories : "mengubah"
    users ||--o{ payments : "memverifikasi"
    users ||--o{ orders : "ditangani sales"
    users ||--o{ test_drives : "ditangani sales"
    users ||--o{ inquiries : "ditangani"

    %% Fase 1 (sudah ada): users. Tabel lain direncanakan.
    users {
        bigint id PK
        string name
        string email UK
        string phone
        enum role "admin, sales, customer"
        boolean is_active
        datetime email_verified_at
        string password
    }

    %% Direncanakan Fase 2
    brands {
        bigint id PK
        string name
        string slug UK
        string logo_path
    }

    %% Direncanakan Fase 2
    categories {
        bigint id PK
        string name
        string slug UK
    }

    %% Direncanakan Fase 2
    showrooms {
        bigint id PK
        string name
        string city
        string address
        string phone
        decimal lat
        decimal lng
    }

    %% Direncanakan Fase 2
    cars {
        bigint id PK
        bigint brand_id FK
        bigint category_id FK
        string name
        string slug UK
        int year
        bigint base_price
        enum fuel_type
        enum transmission
        enum condition
        int stock
        enum badge
        enum status
        json hotspots
        boolean is_featured
    }

    %% Direncanakan Fase 2
    car_images {
        bigint id PK
        bigint car_id FK
        string path
        int sort_order
        boolean is_cover
    }

    %% Direncanakan Fase 2
    car_colors {
        bigint id PK
        bigint car_id FK
        string name
        string hex
        bigint price_delta
        string image_path
    }

    %% Direncanakan Fase 2
    car_options {
        bigint id PK
        bigint car_id FK
        enum group
        string name
        text description
        bigint price
        string image_path
    }

    %% Direncanakan Fase 4
    configurations {
        bigint id PK
        bigint user_id FK
        bigint car_id FK
        bigint car_color_id FK
        bigint total_price
    }

    %% Direncanakan Fase 4
    configuration_option {
        bigint configuration_id FK
        bigint car_option_id FK
        bigint price
    }

    %% Direncanakan Fase 4
    wishlists {
        bigint id PK
        bigint user_id FK
        bigint car_id FK
    }

    %% Direncanakan Fase 4
    orders {
        bigint id PK
        string code UK
        bigint user_id FK
        bigint configuration_id FK
        bigint showroom_id FK
        bigint sales_id FK
        string car_name
        string color_name
        bigint subtotal
        bigint booking_fee
        enum payment_scheme
        bigint dp_amount
        int tenor_months
        bigint monthly_installment
        enum delivery_method
        string delivery_address
        enum status
        datetime payment_due_at
        text notes
    }

    %% Direncanakan Fase 4
    order_status_histories {
        bigint id PK
        bigint order_id FK
        enum status
        text note
        bigint changed_by FK
    }

    %% Direncanakan Fase 4
    payments {
        bigint id PK
        bigint order_id FK
        enum type
        bigint amount
        string bank_destination
        string sender_name
        string sender_bank
        datetime transfer_date
        string proof_path
        enum status
        bigint verified_by FK
        datetime verified_at
        text rejection_note
    }

    %% Direncanakan Fase 5
    test_drives {
        bigint id PK
        bigint user_id FK
        bigint car_id FK
        bigint showroom_id FK
        bigint sales_id FK
        datetime scheduled_at
        enum status
        text notes
    }

    %% Direncanakan Fase 3
    inquiries {
        bigint id PK
        string name
        string email
        string phone
        string service
        text message
        enum status
        bigint handled_by FK
    }

    %% Direncanakan Fase 6
    ai_chat_logs {
        bigint id PK
        bigint user_id FK
        string ip_hash
        string session_id_hash
        string question
        string reply_excerpt
        json recommended_car_ids
        int prompt_tokens
        int completion_tokens
        enum status
        string block_reason
        datetime created_at
    }

    %% Direncanakan Fase 6
    settings {
        bigint id PK
        string key UK
        text value
    }
```

## Arsitektur

```mermaid
flowchart LR
    subgraph browser["Browser"]
        pages["React pages dan layouts (public, account, dashboard, auth)"]
        motion["lib/motion (Lenis, GSAP)"]
        pages --- motion
    end

    subgraph laravel["Laravel 13"]
        routes["Routes (routes/web.php)"]
        mw["Middleware: role, EnsureUserIsActive, SecurityHeaders"]
        controllers["Controllers dan Fortify"]
        services["Services dan Policies (Fase 2 dst)"]
        routes --> mw --> controllers --> services
    end

    db[("MariaDB: car_dealer")]
    storage_public[/"Storage disk public"/]
    storage_private[/"Storage disk private (bukti transfer)"/]
    openai["OpenAI API (Fase 6)"]

    pages <-->|Inertia| routes
    controllers <--> db
    services <--> db
    services --> storage_public
    services --> storage_private
    services -.->|Fase 6| openai
```
