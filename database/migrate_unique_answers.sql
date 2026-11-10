-- ============================================================================
-- MIGRASI: Tambah UNIQUE KEY (session_id, question_id) pada tabel answers
-- CAT Enterprise - Navigasi bertingkat (subject → pdf → nomor soal)
--
-- CATATAN:
-- 1. JALANKAN HANYA SETELAH REVIEW DAN PERSYATUJUAN.
-- 2. Sebelum menambah UNIQUE KEY, skrip ini membersihkan duplikat
--    (session_id, question_id) dengan menjaga baris pertama (id terkecil)
--    dan menghapus baris duplikat berikutnya.
-- 3. File ini HANYA berisi pernyataan SQL. Tidak dijalankan otomatis oleh kode.
-- ============================================================================

-- Langkah 1: Lihat duplikat sebelum dibersihkan (read-only, untuk review)
-- SELECT session_id, question_id, COUNT(*) AS cnt
-- FROM answers
-- GROUP BY session_id, question_id
-- HAVING cnt > 1;

-- Langkah 2: Bersihkan duplikat — sisakan satu baris (id terkecil) per pasangan
-- (session_id, question_id). Hapus baris duplikat dengan id lebih besar.
DELETE a2 FROM answers a2
JOIN answers a1
  ON a1.session_id = a2.session_id
 AND a1.question_id = a2.question_id
 AND a1.id < a2.id;

-- Langkah 3: Tambah UNIQUE KEY untuk mencegah duplikat di masa depan
ALTER TABLE answers
  ADD UNIQUE KEY uk_session_question (session_id, question_id);

-- ============================================================================
-- ROLLBACK (jika diperlukan):
-- ALTER TABLE answers DROP INDEX uk_session_question;
-- ============================================================================
