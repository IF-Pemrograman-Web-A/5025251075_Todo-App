# Todo List App With CRUD (PHP)

## Fitur Tambahan

- Data tugas tersimpan di database MariaDB sehingga tetap tersedia setelah halaman di-refresh atau ditutup
- Pengguna dapat menambah, mengedit, menghapus, dan menandai tugas sebagai selesai
- Tugas dipisahkan ke dalam kategori **Personal** dan **Shared**
- Tersedia fitur alarm/notifikasi pengingat tugas melalui Notification API dan Service Worker
- Backend menggunakan PHP dan MariaDB dengan API untuk mengelola data tugas

| Kebutuhan        | Pilihan               |
| ---------------- | --------------------- |
| Backend          | PHP                   |
| Database         | MariaDB               |
| Akses database   | PDO                   |
| Konfigurasi      | `.env`                |
| Web server lokal | Apache                |
| Frontend         | HTML, CSS, JavaScript |
| CRUD             | API PHP terpisah      |

## Direktori project :

```text
├── config
| ├── .env (tidak di push)
│   └── database.php
├── data.sql
├── image.png
├── includes
│   ├── footer.php
│   ├── header.php
│   ├── sidebar.php
│   └── todo-content.php
├── public
│   ├── api
│   │   ├── add.php
│   │   ├── bootstrap.php
│   │   ├── delete.php
│   │   ├── list.php
│   │   ├── update.php
│   │   └── upload.php
│   ├── index.php
│   ├── script.js
│   ├── style.css
│   ├── sw.js
│   └── uploads
└── README.md
```

## Preview :

![](image.png)

## Deploy link : https://if-pemrograman-web-a.github.io/5025251075_Todo-App/

---

### Nama : Muhammad Naufal Syahputra

### NRP : 5025251075
