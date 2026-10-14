-- Jalankan sekali jika database sudah dibuat sebelum fitur status baru.
ALTER TABLE bookings
    MODIFY status ENUM('menunggu', 'dikonfirmasi', 'sedang_bermain', 'selesai', 'dibatalkan')
    NOT NULL DEFAULT 'menunggu';
