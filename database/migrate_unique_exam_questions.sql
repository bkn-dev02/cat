-- ============================================================================
-- MIGRASI: Tambah UNIQUE KEY (exam_id, question_id) pada tabel exam_questions
-- CAT Enterprise - Mencegah soal duplikat dalam satu ujian
--
-- CATATAN:
-- 1. JALANKAN HANYA SETELAH REVIEW DAN PERSYATUJUAN.
-- 2. Sebelum menambah UNIQUE KEY, skrip ini membersihkan duplikat
--    (exam_id, question_id) dengan menjaga baris pertama (id terkecil)
--    dan menghapus baris duplikat berikutnya.
-- 3. File ini HANYA berisi pernyataan SQL. Tidak dijalankan otomatis oleh kode.
-- ============================================================================

-- Langkah 1: Lihat duplikat sebelum dibersihkan (read-only, untuk review)
-- SELECT exam_id, question_id, COUNT(*) AS cnt
-- FROM exam_questions
-- GROUP BY exam_id, question_id
-- HAVING cnt > 1;

-- Langkah 2: Bersihkan duplikat — sisakan satu baris (id terkecil) per pasangan
-- (exam_id, question_id). Hapus baris duplikat dengan id lebih besar.
DELETE eq2 FROM exam_questions eq2
JOIN exam_questions eq1
  ON eq1.exam_id = eq2.exam_id
 AND eq1.question_id = eq2.question_id
 AND eq1.id < eq2.id;

-- Langkah 3: Tambah UNIQUE KEY untuk mencegah duplikat di masa depan
ALTER TABLE exam_questions
  ADD UNIQUE KEY uk_exam_question (exam_id, question_id);

-- ============================================================================
-- ROLLBACK (jika diperlukan):
-- ALTER TABLE exam_questions DROP INDEX uk_exam_question;
-- ============================================================================
