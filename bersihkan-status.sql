-- Jalankan sekali di phpMyAdmin (database bank_data_bbpjn), SETELAH menjalankan LayananSeeder.
-- Mengosongkan status dokumen pada layanan yang tidak lagi melacak status.
UPDATE dokumen
SET status = NULL
WHERE layanan_id IN (SELECT id FROM layanan WHERE butuh_tindak_lanjut = 0);
