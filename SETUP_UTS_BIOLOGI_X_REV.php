<?php
require_once 'config.php';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Setup UTS-BIOLOGI-X-REV</title></head><body>";
echo "<h2>🔧 SETUP UTS-BIOLOGI-X-REV</h2>";
echo "<hr>";

try {
    // 1. Pastikan subject BIOLOGI ada
    echo "<h3>1️⃣ Cek Subject BIOLOGI</h3>";
    $stmt = $pdo->prepare("INSERT IGNORE INTO subjects (name, created_at) VALUES ('BIOLOGI', NOW())");
    $stmt->execute();
    
    $stmt = $pdo->prepare("SELECT id FROM subjects WHERE name = 'BIOLOGI' LIMIT 1");
    $stmt->execute();
    $subject = $stmt->fetch(PDO::FETCH_ASSOC);
    $subject_id = $subject['id'];
    echo "<p>✅ Subject BIOLOGI ID: $subject_id</p>";
    
    // 2. Buat ujian baru
    echo "<h3>2️⃣ Buat Ujian Baru</h3>";
    $token = 'EXAM-' . strtoupper(substr(md5(rand() . time()), 0, 10));
    
    $stmt = $pdo->prepare("
        INSERT INTO exams (
            title, subject_id, duration, start_time, end_time, token, 
            randomize_questions, exam_type, status, created_by, created_at
        ) VALUES (
            'UTS-BIOLOGI-X-REV', ?, 90, '2026-03-15 08:00:00', '2026-03-15 10:00:00', ?,
            0, 'single', 'draft', 1, NOW()
        )
    ");
    $stmt->execute([$subject_id, $token]);
    $exam_id = $pdo->lastInsertId();
    
    echo "<p>✅ Exam created with ID: $exam_id</p>";
    echo "<p>📝 Token: $token</p>";
    
    // 3. Data soal (25 soal)
    echo "<h3>3️⃣ Insert 25 Soal</h3>";
    
    $questions = [
        ['Di halaman sekolah terdapat pohon mangga, rumput, kupu-kupu, tanah, air, dan cahaya matahari. Kelompok termasuk komponen biotik adalah ....', 'Pohon mangga, tanah, dan air', 'Rumput, kupu-kupu, dan cahaya matahari', 'Pohon mangga, rumput, dan kupu-kupu', 'Tanah, air, dan cahaya matahari', 'Kupu-kupu, air, dan tanah', 'C'],
        ['Perhatikan pernyataan berikut! 1) Intensitas cahaya mempengaruhi proses fotosintesis 2) Belalang memakan daun padi. 3) Suhu lingkungan memengaruhi aktivitas katak. 4) Air dibutuhkan tumbuhan untuk pertumbuhan. Pernyataan yang menunjukkan peran komponen abiotik dalam ekosistem adalah ....', '(1) dan (2)', '(1) dan (3)', '(1), (3), dan (4)', '(2) dan (3)', '(2), (3), dan (4)', 'C'],
        ['Tumbuhan benalu hidup menempel pada pohon mangga dan menyerap sari makanan dari inangnya. Interaksi tersebut merupakan contoh…', 'Mutualisme', 'Parasitisme', 'Komensalisme', 'Kompetisi', 'Netralisme', 'B'],
        ['Di hutan tropis tanaman liana merambat pada batang pohon lain untuk mencapai bagian atas hutan untuk memperoleh cahaya matahari yang lebih optimal. Tanaman tersebut tidak menyerap air maupun unsur hara dari jaringan pohon yang ditumpanginya melainkan hanya menggunakannya sebagai penopang untuk tumbuh ke atas. Jenis interaksi yang terjadi pada kedua tumbuhan tersebut adalah…', 'Muatualisme', 'Parasitisme', 'Komensalisme', 'Kompetisi', 'Amensalisme', 'C'],
        ['Urutan rantai makanan yang benar adalah…', 'Rumput → elang → tikus → ular', 'Rumput → tikus → ular → elang', 'Tikus → rumput → ular → elang', 'Elang → ular → tikus → rumput', 'Ular → tikus → rumput → elang', 'B'],
        ['Dalam suatu ekosistem energi yang tersedia pada setiap tingkat trofik semakin berkurang karena…', 'Energi tersimpan seluruhnya dalam tubuh organisme', 'Energi hilang melalui panas dan respirasi', 'Energi kembali ke produsen', 'Energi berubah menjadi materi', 'Energi tidak dapat berpindah', 'B'],
        ['Mengapa energi dalam ekosistem hanya mengalir satu arah dan tidak dapat berputar kembali (siklus) seperti halnya karbon, air, nitrogen, fosfor dan sulfur?', 'Energi hilang setelah terpakai', 'Energi tersebar merata pada semua organisme', 'Energi dikonversi menjadi berbagai bentuk energi yang lain', 'Energi meningkat sejalan dengan naiknya tingkat trofik', 'Energi tidak dapat digunakan jika sudah terkonversi menjadi panas', 'C'],
        ['Berdasarkan jaring-jaring makanan diatas, elang berperan sebagai…..', 'Konsumen tingkat I', 'Produsen', 'Konsumen puncak', 'Dekomposer', 'Konsumen tingkat II', 'C'],
        ['Jika populasi belalang meningkat drastis kemungkinan yang terjadi adalah…', 'Jumlah rumput berkurang', 'Jumlah elang menurun', 'Jumlah bakteri berkurang', 'Jumlah katak berkurang', 'Jumlah jamur menghilang', 'A'],
        ['Dalam sebuah ekosistem tumbuhan menjadi pemasok energi bagi makhluk hidup lainnya karena…', 'Menggunakan energi matahari untuk menghasilkan senyawa organic', 'Menggunakan energi matahari untuk menguraikan air', 'Menguraikan senyawa organic menjadi anorganik', 'Menyimpan energi kimia dalam bentuk anorganik', 'Menghasilkan oksigen untuk oksidasi zat makanan.', 'A'],
        ['Suatu ekosistem hutan memiliki produktivitas primer kotor sebesar 12.000 kJ/m2/tahun. Jika 40% energi digunakan untuk respirasi tumbuhan, maka produktivitas primer bersih ekosistem tersebut adalah…', '4.800 kJ/m2/tahun', '6.000 kJ/m2/tahun', '8.400 kJ/m2/tahun', '10.800 kJ/m2/tahun', '7.200 kJ/m2/tahun', 'E'],
        ['Piramida jumlah menggambarkan…', 'Jumlah energi pada setiap tingkat trofik', 'Berat total organisme pada setiap tingkat trofik', 'Kecepatan perpindahan energi dalam ekosistem', 'Proses fotosintesis pada produsen', 'Jumlah individu pada setiap tingkat trofik', 'E'],
        ['Di suatu ekosistem padang rumput, energi yang tersedia pada tingkat produsen adalah 20.000 J. Jika rata-rata efisiensi perpindahan energi antar tingkat trofik adalah 10%, maka pernyataan yang paling tepat adalah…', 'Energi pada konsumen tingkat I sebesar 18.000 J', 'Energi pada konsumen tingkat III lebih besar dari konsumen tingkat II', 'Jumlah energi pada setiap tingkat trofik tetap mengikuti hukum kekekalan energi', 'Energi pada konsumen tingkat III sebesar 2.000 J', 'Energi pada konsumen tingkat II sebesar 200 J', 'E'],
        ['Dalam ekosistem perairan sering ditemukan piramida biomassa berbentuk terbalik. Fenomena ini dapat terjadi karena…', 'Laju respirasi konsumen rendah', 'Produsen memiliki ukuran tubuh yang lebih kecil', 'Energi pada tingkat trofik konsumen lebih besar', 'Ukuran tubuh konsumen lebih kecil dari pada produsen', 'Jumlah predator lebih banyak dari pada produsen', 'B'],
        ['Dalam suatu ekosistem padang rumput, biomassa produsen jauh lebih besar dibandingkan biomassa herbivor. Hal ini menunjukkan bahwa ....', 'Sebagian besar biomassa pada produsen tidak berpindah ke tingkat trofik berikutnya', 'Semua biomassa produsen dimakan habis oleh herbivor', 'Herbivor memiliki massa tubuh lebih besar dari produsen', 'Energi dalam ekosistem bersifat siklus', 'Jumlah individu herbivor selalu lebih banyak dari produsen', 'A'],
        ['Suatu wilayah hutan dialihfungsikan menjadi kawasan pemukiman dengan banyak permukaan tertutup beton dan aspal. Dampak yang mungkin terjadi terhadap daur air adalah ...', 'Meningkatnya infiltrasi sehingga cadangan air tanah bertambah', 'Menurunnya limpasan air dan meningkatnya transpirasi', 'Bertambahnya evaporasi karena permukaan tanah semakin luas', 'Meningkatnya limpasan air serta berkurangnya peresapan air', 'Tidak terjadi perubahan karena daur air bersifat tetap', 'D'],
        ['Dalam suatu daerah pegunungan terjadi penurunan signifikan jumlah vegetasi akibat kebakaran hutan. Kondisi ini menyebabkan perubahan pada beberapa tahapan daur air. Kombinasi perubahan yang paling mungkin terjadi adalah ....', 'Transpirasi meningkat, infiltrasi meningkat, limpasan menurun', 'Transpirasi menurun, infiltrasi menurun, limpasan meningkat', 'Evaporasi menurun, kondensasi berhenti, presipitasi hilang', 'Infiltrasi meningkat, air tanah bertambah, banjir berkurang', 'Kondensasi meningkat tanpa dipengaruhi perubahan vegetasi', 'B'],
        ['Perhatikan gambar siklus berikut! Peran komponen X dalam siklus diatas adalah…', 'Menyediakan energi untuk produsen', 'Menghasilkan karbon dioksida untuk respirasi organisme', 'Menguraikan organisme yang mati menjadi bahan organik', 'Menyerap emisi pabrik agar udara tetap bersih', 'Menyerap CO₂ untuk membentuk senyawa organik', 'E'],
        ['Sebuah pabrik mengoperasikan mesin menggunakan dua jenis bahan bakar, yaitu batu bara dan bahan bakar minyak. Karena harga batu bara lebih murah dibandingkan bahan bakar minyak, banyak pabrik memilih menggunakan batu bara sebagai sumber energi. Namun, pembakaran batu bara menghasilkan emisi gas buangan yang lebih tinggi dibandingkan bahan bakar minyak. Jika pabrik terus-menerus menggunakan batu bara sebagai bahan bakar, maka kemungkinan yang akan terjadi adalah …', 'Pembakaran batu bara menghasilkan gas karbon monoksida yang dapat meningkatkan suhu bumi', 'Gas buangan karbondioksida menyebakan terjadinya efek rumah kaca sehingga suhu bumi meningkat', 'Gas nitrogen hasil pembakaran menyebabkan asfiksi pada manusia khususnya manusia lanjut usia', 'Gas karbon monoksida hasil pembakaran menyebakan penipisan ozon di atmosfer', 'Dihasikan gas CFC yang akan menyebabkan penipisan lapisan ozon di atmosfer', 'B'],
        ['Peran mikrobia pengurai dalam siklus nitrogen adalah…', 'Memfiksasi N2 menjadi amonia', 'Mendenitrifikasi amonia dan mengembailkan N2 ke atmosfer', 'Mengubah amonia menjadi nitrat sehingga dapat diserap oleh tumbuhan', 'Membebaskan amonia dari senyawa organic dan mengembalikannya ke tanah', 'Mengubah nitrogen organik menjadi nitrogen anorganik', 'D'],
        ['Seorang petani menanam tanaman kacang tanah secara terus-menerus di lahannya tanpa menambahkan pupuk nitrogen. Setelah beberapa musim tanam, kandungan nitrogen tanah tetap cukup untuk pertumbuhan tanaman. Hal ini terjadi karena adanya bakteri yang hidup bersimbiosis pada akar tanaman tersebut dan mampu mengubah nitrogen bebas di atmosfer menjadi senyawa yang dapat dimanfaatkan tumbuhan. Bakteri yang berperan dalam proses tersebut adalah …', 'Nitrosomonas yang mengubah amonia menjadi nitrit', 'Nitrobacter yang mengubah nitrit menjadi nitrat', 'Rhizobium yang mengikat nitrogen bebas menjadi nitrat', 'Pseudomonas yang mengubah nitrat menjadi nitrogen bebas', 'Bacillus yang menguraikan protein menjadi amonia', 'C'],
        ['Dalam siklus sulfur, beberapa senyawa sulfur seperti H2S dari pembusukan organisme di rawa atau sedimen laut dapat dilepaskan ke atmosfer. Di atmosfer senyawa tersebut mengalami oksidasi menjadi SO₂ atau sulfat sebelum kembali ke permukaan bumi melalui hujan. Berdasarkan proses tersebut, fungsi utama fase atmosfer dalam siklus sulfur adalah ....', 'Mengubah sulfur menjadi unsur yang dapat digunakan langsung oleh hewan', 'Mempercepat pembentukan batuan sedimen yang mengandung sulfur', 'Tempat perubahan kimia sulfur dan penyebarannya ke wilayah lain', 'Aktivitas fotosintesis tumbuhan di udara', 'Mengubah sulfur menjadi karbon yang diperlukan organisme', 'C'],
        ['Suatu daerah industri mengalami peningkatan emisi SO₂ akibat pembakaran bahan bakar fosil. Setelah beberapa tahun, tanah di sekitar wilayah tersebut menjadi lebih asam dan beberapa jenis tumbuhan mengalami penurunan pertumbuhan. Berdasarkan siklus sulfur dampak tersebut paling tepat dijelaskan oleh proses….', 'Konversi SO₂ menjadi sulfat yang turun sebagai hujan asam', 'Reduksi sulfat oleh bakteri yang menghasilkan H₂S di tanah', 'Pengendapan sulfur dalam bentuk mineral di lapisan tanah', 'Penyerapan langsung SO₂ oleh akar tanaman sebagai nutrien', 'Penguraian bahan organic yang menghasilkan sulfur bebas.', 'A'],
        ['Lahan pertanian Pak Doni terdapat banyak daun dan sisa tanaman yang jatuh ke tanah kemudian mengalami pembusukan. Setelah beberapa waktu, kandungan fosfat anorganik di tanah meningkat sehingga dapat diserap oleh akar tumbuhan. Dalam siklus fosfor proses yang menyebakan fosfor dari bahan organic tersebut kembali tersedia bagi tumbuhan adalah…', 'Pelapukan batuan', 'Mineralisasi forfor oleh bakteri pengurai', 'Asimilasi fosfat oleh tumbuhan', 'Pengendapan fosfat di tanah', 'Erosi tanah yang membawa fosfat.', 'B'],
        ['Pengadaan pohon di jalan kota dapat membantu menurunkan suhu udara perkotaan, hal ini disebabkan oleh….', 'Produksi oksigen pada proses fotosintesis', 'Penaunang dari pohon yang rindang', 'Pemanfaatan CO2 dalam proses fotosintesis', 'Dampak dari proses transpirasi', 'Penyerapan unsur hara dari tanah oleh akar tumbuhan', 'D']
    ];
    
    $success = 0;
    $failed = 0;
    
    foreach ($questions as $index => $q) {
        try {
            // Insert question
            $stmt = $pdo->prepare("
                INSERT INTO questions (subject_id, type, question_text, option_a, option_b, option_c, option_d, option_e, correct_answer, points, created_by, created_at)
                VALUES (?, 'multiple_choice', ?, ?, ?, ?, ?, ?, ?, 4, 1, NOW())
            ");
            $stmt->execute([$subject_id, $q[0], $q[1], $q[2], $q[3], $q[4], $q[5], $q[6]]);
            $question_id = $pdo->lastInsertId();
            
            // Link to exam
            $stmt = $pdo->prepare("INSERT INTO exam_questions (exam_id, question_id) VALUES (?, ?)");
            $stmt->execute([$exam_id, $question_id]);
            
            $success++;
            echo "<p>✅ Soal " . ($index + 1) . " berhasil (ID: $question_id, Jawaban: {$q[6]})</p>";
        } catch (Exception $e) {
            $failed++;
            echo "<p>❌ Soal " . ($index + 1) . " gagal: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>📊 Ringkasan</h3>";
    echo "<p>✅ Berhasil: $success soal</p>";
    echo "<p>❌ Gagal: $failed soal</p>";
    
    // Verifikasi
    echo "<hr>";
    echo "<h3>🔍 Verifikasi Database</h3>";
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM exam_questions WHERE exam_id = ?");
    $stmt->execute([$exam_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total soal terhubung ke exam: <strong>{$result['total']}</strong></p>";
    
    echo "<hr>";
    echo "<p>✅ <strong>SELESAI!</strong> Ujian UTS-BIOLOGI-X-REV berhasil dibuat dengan $success soal.</p>";
    echo "<p>Kunci jawaban: C C B C B B C C A A E E E B A D B E B D C C A B D</p>";
    
} catch (Exception $e) {
    echo "<p>❌ ERROR: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
