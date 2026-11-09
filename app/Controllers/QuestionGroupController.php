<?php

namespace App\Controllers;

class QuestionGroupController
{
    // ============================================
    // INDEX — Daftar semua grup soal
    // ============================================
    public function index()
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        $user = auth();

        if ($user['role'] === 'admin') {
            $stmt = db()->prepare("
                SELECT qg.*, s.name AS subject_name, u.full_name AS creator_name,
                       COUNT(q.id) AS question_count
                FROM question_groups qg
                JOIN subjects s ON qg.subject_id = s.id
                JOIN users u ON qg.created_by = u.id
                LEFT JOIN questions q ON q.group_id = qg.id
                GROUP BY qg.id
                ORDER BY qg.created_at DESC
            ");
            $stmt->execute();
        } else {
            $stmt = db()->prepare("
                SELECT qg.*, s.name AS subject_name, u.full_name AS creator_name,
                       COUNT(q.id) AS question_count
                FROM question_groups qg
                JOIN subjects s ON qg.subject_id = s.id
                JOIN users u ON qg.created_by = u.id
                LEFT JOIN questions q ON q.group_id = qg.id
                WHERE qg.created_by = ?
                GROUP BY qg.id
                ORDER BY qg.created_at DESC
            ");
            $stmt->execute([$user['id']]);
        }

        $groups = $stmt->fetchAll();
        view('question_groups/index', ['groups' => $groups]);
    }

    // ============================================
    // CREATE — Buat grup soal baru
    // ============================================
    public function create()
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $stmt = db()->prepare("
                    INSERT INTO question_groups (name, description, subject_id, type, sub_topic, default_points, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    trim($_POST['name']),
                    !empty($_POST['description']) ? trim($_POST['description']) : null,
                    $_POST['subject_id'],
                    $_POST['type'],
                    !empty($_POST['sub_topic']) ? trim($_POST['sub_topic']) : null,
                    (int)($_POST['default_points'] ?? 1),
                    auth()['id']
                ]);

                $groupId = db()->lastInsertId();
                $_SESSION['success'] = 'Grup soal berhasil dibuat! Sekarang tambahkan soal ke dalam grup.';
                redirect('questionGroup/show/' . $groupId);

            } catch (\Exception $e) {
                $_SESSION['error'] = 'Gagal membuat grup: ' . $e->getMessage();
                redirect('questionGroup/create');
            }
        }

        $subjects = db()->query("SELECT * FROM subjects ORDER BY name")->fetchAll();
        view('question_groups/create', ['subjects' => $subjects]);
    }

    // ============================================
    // SHOW — Lihat isi grup + tambah soal di dalamnya
    // ============================================
    public function show($id)
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        $stmt = db()->prepare("
            SELECT qg.*, s.name AS subject_name
            FROM question_groups qg
            JOIN subjects s ON qg.subject_id = s.id
            WHERE qg.id = ?
        ");
        $stmt->execute([$id]);
        $group = $stmt->fetch();

        if (!$group) {
            $_SESSION['error'] = 'Grup soal tidak ditemukan.';
            redirect('questionGroup');
        }

        // Ambil semua soal dalam grup ini
        $qStmt = db()->prepare("
            SELECT q.*, s.name AS subject_name
            FROM questions q
            JOIN subjects s ON q.subject_id = s.id
            WHERE q.group_id = ?
            ORDER BY q.id ASC
        ");
        $qStmt->execute([$id]);
        $questions = $qStmt->fetchAll();

        view('question_groups/show', [
            'group'     => $group,
            'questions' => $questions
        ]);
    }

    // ============================================
    // EDIT — Edit identitas grup
    // ============================================
    public function edit($id)
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        $stmt = db()->prepare("SELECT * FROM question_groups WHERE id = ?");
        $stmt->execute([$id]);
        $group = $stmt->fetch();

        if (!$group) {
            $_SESSION['error'] = 'Grup soal tidak ditemukan.';
            redirect('questionGroup');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $stmt = db()->prepare("
                    UPDATE question_groups
                    SET name = ?, description = ?, subject_id = ?, type = ?, sub_topic = ?, default_points = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    trim($_POST['name']),
                    !empty($_POST['description']) ? trim($_POST['description']) : null,
                    $_POST['subject_id'],
                    $_POST['type'],
                    !empty($_POST['sub_topic']) ? trim($_POST['sub_topic']) : null,
                    (int)($_POST['default_points'] ?? 1),
                    $id
                ]);

                $_SESSION['success'] = 'Grup soal berhasil diupdate!';
                redirect('questionGroup/show/' . $id);

            } catch (\Exception $e) {
                $_SESSION['error'] = 'Gagal update grup: ' . $e->getMessage();
                redirect('questionGroup/edit/' . $id);
            }
        }

        $subjects = db()->query("SELECT * FROM subjects ORDER BY name")->fetchAll();
        view('question_groups/edit', ['group' => $group, 'subjects' => $subjects]);
    }

    // ============================================
    // DELETE — Hapus grup (soal tidak ikut terhapus, hanya dilepas dari grup)
    // ============================================
    public function delete($id)
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        // Lepas semua soal dari grup (set group_id = NULL)
        db()->prepare("UPDATE questions SET group_id = NULL WHERE group_id = ?")
            ->execute([$id]);

        // Hapus grup
        db()->prepare("DELETE FROM question_groups WHERE id = ?")
            ->execute([$id]);

        $_SESSION['success'] = 'Grup soal dihapus. Soal-soal di dalamnya dipindahkan ke Bank Soal.';
        redirect('questionGroup');
    }

    // ============================================
    // DELETE QUESTION — Hapus permanen soal dari bank soal & semua ujian
    // ============================================
    public function deleteQuestion($groupId, $questionId)
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        // Hapus dari exam_questions (semua ujian)
        db()->prepare("DELETE FROM exam_questions WHERE question_id = ?")
            ->execute([$questionId]);

        // Hapus dari answers jika ada
        db()->prepare("DELETE FROM answers WHERE question_id = ?")
            ->execute([$questionId]);

        // Hapus soalnya
        db()->prepare("DELETE FROM questions WHERE id = ? AND group_id = ?")
            ->execute([$questionId, $groupId]);

        $_SESSION['success'] = 'Soal berhasil dihapus permanen dari bank soal.';
        redirect('questionGroup/show/' . $groupId);
    }

    // ============================================
    // REMOVE QUESTION — Lepas soal dari grup (soal tetap ada di bank soal)
    // ============================================
    public function removeQuestion($groupId, $questionId)
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        db()->prepare("UPDATE questions SET group_id = NULL WHERE id = ? AND group_id = ?")
            ->execute([$questionId, $groupId]);

        $_SESSION['success'] = 'Soal dilepas dari grup dan kembali ke Bank Soal.';
        redirect('questionGroup/show/' . $groupId);
    }

    // ============================================
    // COPY QUESTION — Copy soal ke grup lain
    // ============================================
    public function copyQuestion($questionId)
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $targetGroupId = $_POST['target_group_id'] ?? null;
            $sourceGroupId = $_POST['source_group_id'] ?? null;

            // Ambil data soal asli
            $stmt = db()->prepare("SELECT * FROM questions WHERE id = ?");
            $stmt->execute([$questionId]);
            $q = $stmt->fetch();

            if (!$q) {
                $_SESSION['error'] = 'Soal tidak ditemukan.';
                redirect('questionGroup');
            }

            // Duplikasi soal dengan group_id baru
            $ins = db()->prepare("
                INSERT INTO questions (
                    group_id, subject_id, type, sub_topic, question_text, question_image,
                    audio_file, max_plays,
                    option_a, option_b, option_c, option_d, option_e,
                    correct_answer, option_scores, hide_correct_answer, points, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->execute([
                $targetGroupId ?: null,
                $q['subject_id'],
                $q['type'],
                $q['sub_topic'],
                $q['question_text'],
                $q['question_image'],
                $q['audio_file'],
                $q['max_plays'],
                $q['option_a'],
                $q['option_b'],
                $q['option_c'],
                $q['option_d'],
                $q['option_e'],
                $q['correct_answer'],
                $q['option_scores'] ?? null,
                $q['hide_correct_answer'],
                $q['points'],
                auth()['id']
            ]);

            $_SESSION['success'] = 'Soal berhasil dicopy ke grup tujuan!';

            if ($sourceGroupId) {
                redirect('questionGroup/show/' . $sourceGroupId);
            } else {
                redirect('questionGroup');
            }
        }

        // GET: tampilkan form pilih grup tujuan
        $stmt = db()->prepare("SELECT * FROM questions WHERE id = ?");
        $stmt->execute([$questionId]);
        $question = $stmt->fetch();

        $user = auth();
        if ($user['role'] === 'admin') {
            $groups = db()->query("
                SELECT qg.*, s.name AS subject_name
                FROM question_groups qg
                JOIN subjects s ON qg.subject_id = s.id
                ORDER BY qg.name
            ")->fetchAll();
        } else {
            $stmt2 = db()->prepare("
                SELECT qg.*, s.name AS subject_name
                FROM question_groups qg
                JOIN subjects s ON qg.subject_id = s.id
                WHERE qg.created_by = ?
                ORDER BY qg.name
            ");
            $stmt2->execute([$user['id']]);
            $groups = $stmt2->fetchAll();
        }

        view('question_groups/copy_question', [
            'question' => $question,
            'groups'   => $groups
        ]);
    }

    // ============================================
    // MOVE QUESTION — Pindah soal ke grup lain
    // ============================================
    public function moveQuestion($questionId)
    {
        requireAuth();
        requireRole(['admin', 'guru']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $targetGroupId = $_POST['target_group_id'] ?? null;
            $sourceGroupId = $_POST['source_group_id'] ?? null;

            db()->prepare("UPDATE questions SET group_id = ? WHERE id = ?")
                ->execute([$targetGroupId ?: null, $questionId]);

            $_SESSION['success'] = 'Soal berhasil dipindahkan!';

            if ($sourceGroupId) {
                redirect('questionGroup/show/' . $sourceGroupId);
            } else {
                redirect('questionGroup');
            }
        }

        $stmt = db()->prepare("SELECT * FROM questions WHERE id = ?");
        $stmt->execute([$questionId]);
        $question = $stmt->fetch();

        $user = auth();
        if ($user['role'] === 'admin') {
            $groups = db()->query("
                SELECT qg.*, s.name AS subject_name
                FROM question_groups qg
                JOIN subjects s ON qg.subject_id = s.id
                ORDER BY qg.name
            ")->fetchAll();
        } else {
            $stmt2 = db()->prepare("
                SELECT qg.*, s.name AS subject_name
                FROM question_groups qg
                JOIN subjects s ON qg.subject_id = s.id
                WHERE qg.created_by = ?
                ORDER BY qg.name
            ");
            $stmt2->execute([$user['id']]);
            $groups = $stmt2->fetchAll();
        }

        view('question_groups/move_question', [
            'question' => $question,
            'groups'   => $groups
        ]);
    }
}
