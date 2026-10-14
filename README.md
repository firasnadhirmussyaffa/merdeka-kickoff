# Merdeka Kickoff

Website booking sewa lapangan mini soccer sederhana menggunakan HTML, CSS, JavaScript, PHP, dan MySQL/MariaDB.

## Fitur

- Beranda publik yang menampilkan lapangan aktif dan harga dari database.
- Daftar akun, login, logout, session login, serta cookie untuk mengingat username.
- Akun pertama yang didaftarkan otomatis menjadi admin; akun berikutnya menjadi pelanggan.
- Dashboard berbeda untuk admin dan pelanggan.
- CRUD lapangan (khusus admin).
- CRUD booking: buat, lihat, ubah, dan hapus. Pelanggan hanya mengelola booking miliknya; admin mengelola semua booking.
- Pengecekan jam tersedia sesuai lapangan, tanggal, dan durasi yang dipilih.
- Pemeriksaan bentrok di server, harga total otomatis, dan status booking.
- Alur status booking: menunggu → dikonfirmasi → sedang bermain → selesai. Status dibatalkan membebaskan jadwal.
- Laporan yang dapat difilter dan dicetak ke PDF memakai FPDF.

## Menjalankan di komputer lokal

1. Jalankan Apache dan MySQL dari XAMPP.
2. Buka `http://localhost/phpmyadmin`.
3. Impor file `database/merdeka_kickoff.sql`. Database dan tiga tabel berelasi akan dibuat.
4. Periksa host, username, dan password MySQL di `koneksi.php` (pengaturan awal `localhost`, `root`, tanpa password).
5. Simpan folder ini di dalam `htdocs` sebagai `merdeka_kickoff`.
6. Buka `http://localhost/merdeka_kickoff/`.
7. Daftarkan akun pertama. Akun pertama menjadi admin. Gunakan akun ini untuk mengelola lapangan, booking, dan laporan.
8. Daftarkan akun lain untuk mencoba fitur pelanggan.

Jika database sudah dibuat sebelum status `sedang bermain` dan `selesai` ditambahkan, jalankan `database/update_status_booking.sql` satu kali di phpMyAdmin. File SQL utama sudah memakai status baru untuk pemasangan database baru.

Untuk mengubah pengguna menjadi admin/pelanggan setelah akun pertama dibuat, buka tabel `users` di phpMyAdmin dan ubah kolom `role`.

## Struktur database

- `users`: akun pengguna dan peran admin/pelanggan.
- `lapangan`: nama, deskripsi, harga per jam, dan status lapangan.
- `bookings`: pemesan, lapangan, tanggal, jam, durasi, harga, status, dan keterangan.

Relasi: `bookings.user_id` terhubung ke `users.id`, dan `bookings.lapangan_id` terhubung ke `lapangan.id`.

Booking menunggu, dikonfirmasi, sedang bermain, dan selesai tetap menahan slot waktunya. Booking dibatalkan tidak menahan slot. Admin mengubah status secara manual dari menu Edit booking.

CRUD lengkap tersedia pada data lapangan dan booking: tambah, tampilkan, ubah, dan hapus (total delapan aksi CRUD lintas dua jenis data).

## Demo session dan cookie

- `login.php` memanggil `session_start()` dengan jelas, lalu menyimpan `user_id`, `nama`, `username`, dan `role` di `$_SESSION`.
- Checkbox “Ingat username di perangkat ini” menyimpan username di cookie `ingat_username` selama 30 hari. Password tidak pernah disimpan di cookie.
- `cek_login.php` mengarahkan pengguna yang belum login dan membatasi halaman pengelolaan lapangan/laporan untuk admin.
- `logout.php` menghapus session dan cookie username.
- Form booking memperbarui daftar jam tersedia berdasarkan lapangan, tanggal, dan durasi; pemeriksaan bentrok tetap dijalankan kembali di PHP saat booking disimpan.

## Catatan

- Laporan FPDF memakai library dari [FPDF](https://www.fpdf.org/), yang disertakan di folder `fpdf/`.
- Harga dan bentrok jadwal selalu diperiksa kembali oleh PHP, bukan hanya JavaScript di browser.
- Bila tabel lapangan telah memiliki booking, database mencegah lapangan dihapus. Nonaktifkan lapangan untuk menghentikan booking baru tanpa menghapus riwayat.
