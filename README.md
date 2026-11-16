# RESTful API

| #   | Ketentuan                                                                       | Implementasi                                                              |
| --- | ------------------------------------------------------------------------------- | ------------------------------------------------------------------------- |
| 1   | Backend CRUD → REST API dengan method yang benar (GET, POST, PUT/PATCH, DELETE) | `GET/POST /api/todos`, `GET/PUT/PATCH/DELETE /api/todos/{id}`             |
| 2   | Response code **benar** untuk tiap endpoint                                     | `200`, `201` (+ header `Location`), `204`                                 |
| 3   | Response code **salah** untuk tiap endpoint (data error, user tidak ada, dll.)  | `400`, `401`, `403`, `404`, `405`, `409`, `413`, `415`, `422`, `500`      |
| 4   | (opsional) Autentikasi                                                          | **JWT** (HS256): `POST /api/auth/login` → `Authorization: Bearer <token>` |

## Arsitektur

```text
Browser (script.js)
   │  fetch  GET|POST|PUT|PATCH|DELETE  /api/...   + Authorization: Bearer <JWT>
   ▼
public/api/.htaccess ──► public/api/index.php   (front controller)
                              │
                              ├─ Request::capture()       baca method, path, header, body JSON
                              ├─ Router::match()          404 / 405 (+Allow) / handler
                              ├─ Jwt::verify()            401 bila token tidak ada/salah/kedaluwarsa
                              ▼
                        Controller  (AuthController · TodoController · UploadController)
                              │  validasi input → TodoValidator (422 + detail per-field)
                              │  cek kepemilikan → 403 / 404
                              ▼
                        Repository (TodoRepository · UserRepository)  ── PDO prepared statement ──► MySQL
                              ▼
                        Response::success / error / noContent  → JSON
```

Satu lapisan = satu tanggung jawab: **Router** hanya memetakan URL, **Controller** mengatur alur & status code,
**Validator** memeriksa input, **Repository** satu-satunya tempat SQL, **Response** menjaga format JSON seragam.

### Struktur direktori

```text
├── composer.json            (+ autoload PSR-4  App\ → src/)
├── data.sql                 skema + data demo (users kini punya password_hash)
├── migration-e06.sql        ALTER TABLE untuk yang sudah punya database E05
├── config/
│   ├── database.php         koneksi PDO (error kini berupa JSON)
│   └── .env.example         DB_* + JWT_SECRET + JWT_TTL  → salin jadi config/.env
├── src/
│   ├── Http/
│   │   ├── Request.php      method, path, query, header, body JSON (415/400)
│   │   ├── Response.php     success() / error() / noContent()
│   │   ├── Router.php       pencocokan route + 404/405
│   │   └── ApiException.php exception → status code + pesan + errors
│   ├── Auth/Jwt.php         buat & verifikasi token HS256
│   ├── Validation/TodoValidator.php   full() untuk POST/PUT, partial() untuk PATCH
│   ├── Repositories/        TodoRepository.php, UserRepository.php
│   └── Controllers/         AuthController.php, TodoController.php, UploadController.php
├── includes/                header / sidebar / todo-content (+ form login) / footer
└── public/
    ├── index.php            halaman utama
    ├── script.js            memanggil REST API (+ login, token Bearer)
    ├── style.css            TIDAK diubah
    ├── sw.js, manifest.webmanifest, offline.html   PWA E05 (cache dinaikkan ke v2)
    ├── uploads/             hasil upload gambar
    └── api/
        ├── index.php        front controller + daftar route
        └── .htaccess        rewrite semua /api/* ke index.php
```

### Perubahan database

Hanya satu kolom: `users.password_hash VARCHAR(255) NOT NULL` (diperlukan untuk login JWT).
Tabel `todos` tidak berubah. Dua user demo memakai password `password123`.

## Daftar endpoint

Semua body request & response berformat JSON. Response sukses: `{"success":true,"message"?,"data":...}`;
response error: `{"success":false,"message":"...","errors"?:{"field":"pesan"}}`.

| Method     | URL                                         | Auth | Sukses             | Error yang mungkin                                                     |
| ---------- | ------------------------------------------- | :--: | ------------------ | ---------------------------------------------------------------------- |
| POST       | `/api/auth/register`                        |  –   | `201`              | `409` nama/email sudah ada · `422` data tidak valid                    |
| POST       | `/api/auth/login`                           |  –   | `200` + token      | `401` email/password salah · `422` kosong                              |
| GET        | `/api/auth/me`                              |  ✔   | `200`              | `401`                                                                  |
| GET        | `/api/todos` (`?visibility=&completed=&q=`) |  ✔   | `200`              | `401` · `422` filter salah                                             |
| GET        | `/api/todos/{id}`                           |  ✔   | `200`              | `400` id bukan angka · `401` · `403` personal milik orang lain · `404` |
| POST       | `/api/todos`                                |  ✔   | `201` + `Location` | `400` JSON rusak · `401` · `415` · `422`                               |
| PUT        | `/api/todos/{id}` (ganti penuh)             |  ✔   | `200`              | `400` · `401` · `403` · `404` · `415` · `422`                          |
| PATCH      | `/api/todos/{id}` (ubah sebagian)           |  ✔   | `200`              | `400` · `401` · `403` · `404` · `415` · `422`                          |
| DELETE     | `/api/todos/{id}`                           |  ✔   | `204` (tanpa body) | `400` · `401` · `403` · `404`                                          |
| POST       | `/api/uploads`                              |  ✔   | `201`              | `401` · `413` >5 MB · `422` bukan JPEG                                 |
| (apa saja) | URL tidak dikenal                           |      |                    | `404` · method salah → `405` + header `Allow`                          |

Aturan akses: todo hanya bisa diubah/dihapus pemiliknya (`403` jika bukan). Todo `shared` milik orang lain
boleh dibaca lewat `GET /api/todos/{id}`, todo `personal` tidak. `GET /api/todos` hanya mengembalikan todo milik user yang login.

Field todo: `title`_ (≤255), `due_date`_ (`YYYY-MM-DD`), `description`, `notification_time` (`YYYY-MM-DD HH:MM:SS`),
`image_path` (hasil `/api/uploads`), `is_completed` (boolean), `visibility` (`personal` | `shared`). \*wajib untuk POST/PUT.

## Cara mencoba

### A. Lewat aplikasi (browser)

1. Buka `http://localhost:8000` → muncul form **Login**. Masuk dengan `naufal@example.com` / `password123`.
2. Tambah todo (POST), centang selesai (PATCH), **Edit** judul (PATCH), **Delete** (DELETE). Cek tab _Network_ DevTools untuk melihat method & status code.
3. **Logout** menghapus token. Token rusak/kedaluwarsa otomatis mengembalikan ke form login (respons `401`).

### B. Lewat curl (menguji status code benar & salah)

```bash
B=http://localhost:8000/api
J='Content-Type: application/json'

# Login → ambil token
TOKEN=$(curl -s -X POST $B/auth/login -H "$J" \
  -d '{"email":"naufal@example.com","password":"password123"}' | php -r 'echo json_decode(stream_get_contents(STDIN))->data->token;')
A="Authorization: Bearer $TOKEN"

# ---- Status code BENAR
curl -i $B/todos -H "$A"                                                              # 200
curl -i -X POST $B/todos -H "$A" -H "$J" -d '{"title":"Coba REST","due_date":"2026-12-01"}'   # 201 + Location
curl -i $B/todos/1 -H "$A"                                                            # 200
curl -i -X PUT   $B/todos/1 -H "$A" -H "$J" -d '{"title":"Ganti","due_date":"2026-12-02"}'    # 200
curl -i -X PATCH $B/todos/1 -H "$A" -H "$J" -d '{"is_completed":true}'                # 200
curl -i -X DELETE $B/todos/4 -H "$A"                                                  # 204 (tanpa body)

# ---- Status code SALAH
curl -i $B/todos                                                                      # 401 tanpa token
curl -i -X POST $B/auth/login -H "$J" -d '{"email":"naufal@example.com","password":"x"}'      # 401
curl -i -X POST $B/todos -H "$A" -H "$J" -d '{"title":"","due_date":"2026-02-31"}'    # 422 + detail per-field
curl -i -X POST $B/todos -H "$A" -H "$J" -d '{rusak'                                  # 400 JSON rusak
curl -i -X POST $B/todos -H "$A" -H 'Content-Type: text/plain' -d 'x'                 # 415
curl -i $B/todos/9999 -H "$A"                                                         # 404 todo tidak ada
curl -i $B/todos/abc  -H "$A"                                                         # 400 id tidak valid
curl -i $B/todos/3    -H "$A"                                                         # 403 todo personal milik Demo User
curl -i -X DELETE $B/todos -H "$A"                                                    # 405 + Allow: GET, POST
curl -i $B/foo -H "$A"                                                                # 404 endpoint tidak ada
curl -i -X POST $B/auth/register -H "$J" -d '{"name":"Naufal","email":"naufal@example.com","password":"12345678"}'  # 409
```

| Skenario              | Request                          | Status |
| --------------------- | -------------------------------- | :----: |
| Daftar todo           | `GET /api/todos`                 |  200   |
| Buat todo             | `POST /api/todos`                |  201   |
| Ubah penuh / sebagian | `PUT` / `PATCH /api/todos/{id}`  |  200   |
| Hapus                 | `DELETE /api/todos/{id}`         |  204   |
| Tanpa/invalid token   | `GET /api/todos`                 |  401   |
| Validasi gagal        | `POST /api/todos` (judul kosong) |  422   |
| Todo tidak ada        | `GET /api/todos/9999`            |  404   |
| Todo milik orang lain | `DELETE /api/todos/3`            |  403   |
| Method salah          | `DELETE /api/todos`              |  405   |

## Catatan

- Semua query memakai prepared statement; nama kolom pada UPDATE berasal dari whitelist validator, bukan input user.
- Password disimpan dengan `password_hash()` (bcrypt); pesan login salah sengaja sama untuk email/password agar akun tidak bisa ditebak.
- JWT hanya menerima `alg=HS256` (menolak `alg=none`), diverifikasi dengan `hash_equals`, dan punya masa berlaku (`JWT_TTL`).
- `image_path` hanya diterima jika berpola `uploads/<32 hex>.jpg` (mencegah path traversal).
- Detail error internal hanya masuk log server, client hanya menerima pesan umum (`500`).

---

### Nama : Muhammad Naufal Syahputra

### NRP : 5025251075
