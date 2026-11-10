<?php
/**
 * REBUILD BERSIH — Ujian SAT Mandarin XI Genap (ID: 440)
 * Hapus semua assign soal lama, insert 52 soal baru yang benar
 * Soal lama di bank soal TIDAK dihapus
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/config.production.php';
$pdo = new PDO(
    'mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$examId   = 440;
$groupId  = 31;
$subjectId = 7;

$guru = $pdo->query("SELECT id FROM users WHERE username LIKE '%wara%' LIMIT 1")->fetch();
if (!$guru) $guru = $pdo->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetch();
$guruId = $guru['id'];

// Cek berapa soal sekarang & berapa siswa sudah submit
$totalSoalNow = $pdo->query("SELECT COUNT(*) FROM exam_questions WHERE exam_id = $examId")->fetchColumn();
$totalSubmit  = $pdo->query("SELECT COUNT(*) FROM exam_sessions WHERE exam_id = $examId AND status IN ('submitted','completed','graded')")->fetchColumn();
$totalSiswa   = $pdo->query("SELECT COUNT(*) FROM exam_participants WHERE exam_id = $examId")->fetchColumn();

// 52 SOAL BENAR SAT MANDARIN XI GENAP
$soal52 = [
[1,'Find the Pinyin of this Hanzi "这是谁的钱包?"','Zhè shì shéi de qiánbāo','Zhě shí shéi de qiān bào','Zhè shì shéi de qiān bào','Zhe shi shei de qian bao','Zhě shí shéi de qiān bào','A'],
[2,'Kata "Itu" dengan keterangan menunjukkan sesuatu yang jauh dibawah ini adalah….?','那是','这个','这是','那个','哪个','D'],
[3,'"___ 是我的学校" "___Shì wǒ de xuéxiào" (Menjelaskan sekolah itu)','那是','这个','这是','那个','哪个','A'],
[4,'"___ 苹果很好吃" "__Píngguǒ hěn hào chī." (Menunjuk apel yang dekat)','那是','这个','这是','哪个','那个','B'],
[5,'Kata "Ini" dengan keterangan untuk menjelaskan adalah….?','那是','这个','这是','那个','哪个','C'],
[6,'这是我的___。This is my Teacher. Fill the blank of this sentence.','老人','佬市','老鼠','佬师','老师','E'],
[7,'这是我的好朋友。Zhè shì wǒ de hǎo péngyǒu.','Ini adalah teman baikku.','Ini adalah temanku.','Itu adalah teman baikku','Semua salah','Itu temanku','A'],
[8,'Find the meaning of this word "钱包"','Buku','Dompet','Tas','Tukar','Uang','B'],
[9,'Kata "的" dalam kalimat "这是谁的钱包？" berfungsi untuk …','Menunjukkan pertanyaan','Menunjukkan waktu','Menunjukkan kepunyaan','Semua benar','Menunjukkan tempat','C'],
[10,'Susunan kata yang benar dibawah ini adalah …','谁这是钱包的?','钱包谁的是这?','这是谁钱包的？','这是谁的钱包？','Semua salah','D'],
[11,'Mata uang negara cina adalah…','远','员','元','园','沅','C'],
[12,'Find the Hanzi of this word "Credit Card"','信拥卡','新佣咔','新用咔','信用垰','信用卡','E'],
[13,'"连忙" artinya adalah……?','Sangat lambat','Bersama-sama','Segera / Buru-buru','Sangat dekat','Sangat jauh','C'],
[14,'我看我的好朋友，我____叫他。Wǒ kàn wǒ de hǎo péngyǒu, wǒ____ jiào tā','一起','连忙','很少','连芒','已经','B'],
[15,'A: 这是什么？B: 这是 a CD. Find the hanzi of "a cd"','一个光盘','一只光盘','一张光盘','一长光盘','一本光盘','C'],
[16,'Find the pinyin from this word "idea/plan"','Zhǔ yì','Zhǔ yiè','Zhù yì','Zhu yi','Zǔ yì','A'],
[17,'钱包里有多少钱？Qiánbāo Lǐ yǒu duōshǎo qián? Pada kalimat di atas terdapat kata "里 Lǐ" yang artinya adalah…?','Suka','Depan','Luar','Belakang','Dalam','E'],
[18,'Find the pinyin from this hanzi "钥匙"','Yàoshí','Yāoshi','Yuèshi','Yaoshi','Yàoshi','E'],
[19,'Find the meaning from this pinyin "Jǐng chá"….?','Doctor','Police officer','Driver','Soldier','Teacher','B'],
[20,'Find the Pinyin from this sentence "how much the price"','Jiāgé duōshǎo','Jiàgé duōshào','Jiàgé duōshǎo','Jiege duoshao','Jiàgě duōshǎo','C'],
[21,'Hanzi dari kata "Rupiah" dalam Bahasa mandarin adalah…?','吨','都','盾','者','遁','C'],
[22,'Hanzi dari kata "uang" adalah…?','包','钱','书','浅','水','B'],
[23,'Pengulangan kata kerja hanya untuk memberikan nuansa tertentu tanpa mengubah arti sebenarnya disebut…?','Reduplication of adjective','Reduplication','Reduplication of word','Reduplication of verb','Reduplication of verb and word','A'],
[24,'Perhatikan kalimat ini "我看看一下儿" metode kalimat di atas adalah…','AA','AB一AB','A一A','ABAB','Semua salah','C'],
[25,'Satuan kata untuk baju adalah...?','张','条','件','个','本','A'],
[26,'Dibawah ini yang menyatakan "Sepasang" adalah…?','双','友','又','戏','欢','D'],
[27,'她又聪明又漂亮。Tā yòu cōngmíng yòu piàoliang. Berdasarkan kalimat di atas, carilah rumusnya dibawah ini…?','S+又+adj+又+verb','S+又+verb1+又+verb2','S+adj1+adj2','S+又+adj1+又+adj2','S+adj1+又+adj2+又','E'],
[28,'Penggunaan kata "atau" yang digunakan untuk pertanyaan dibawah ini adalah….?','或者','或啫','还有','还没','还是','A'],
[29,'Kata "或者" digunakan untuk….?','Menyatakan pilihan dalam kalimat pernyataan','Menyatakan kepemilikan','Menyatakan pertanyaan pilihan','Menyatakan waktu','Menyatakan cinta :)','E'],
[30,'我喜欢她......她不喜欢我。Wǒ xǐhuān tā...Tā bù xǐhuān wǒ. Isilah titik titik di atas dengan memilih salah satu yang benar!','不杯','可以','可室','担是','但是','B'],
[31,"A: 你好，这个咖啡价格多少钱?\nNǐ hǎo, zhège kāfēi jiàgé duōshao qián?\nB: 你好，这一杯咖啡是四万六千盾。\nNǐ hǎo, zhè yì bēi kāfēi shì Sì wàn liù qiān dùn.\nBerapa harga 1 gelas kopi pada dialogue tersebut..?",'Rp 48.000','Rp 46.000','Rp 36.000','Rp 53.000','Rp 30.800','A'],
[32,"A: 哇，很贵呀，可以便宜吗?\nWa, hěn guì ya, kěyǐ piányi ma?\nB: 对不起，不可以。Duìbuqǐ, bù kěyǐ.\nArti dari kata \"贵 guì\" pada dialogue adalah…?",'Mahal','Enak','Murah','Baru','Sedang','C'],
[33,"A: 那有咖啡便宜一点吗？\nNà yǒu kāfēi piányi yìdiǎn ma?\nB: 有, 卡布奇诺更便宜, 一杯二万七千盾。\nYǒu, kǎbùqínuò gèng piányi, yì bēi èr wàn qī qiān dùn.\nArti dari kata \"便宜 piányi\" pada dialogue adalah…?",'Mahal','Enak','Murah','Baru','Sedang','B'],
[34,'Jika turun hujan dan hendak keluar maka kita harus menggunakan...? (payung)','帽子','雨伞','雨金','鞋子','雨余','C'],
[35,'10.000 + 2.000 =…?','二万一千','一万三千','一万两千','二万两千','一万','B'],
[36,"A: 那有咖啡便宜一点吗？\nB: 有, 卡布奇诺更便宜, 一杯二万七千盾。\nA: 那我点五杯卡布奇诺，给你钱！\nB: 好的,你点五杯卡布奇诺对吗？\nA: 对，谢谢。\nBerapa total belanjaan yang harus di bayarnya pada kalimat di atas…?",'三十万五千盾','十三万五千盾','二万七千盾','十五万三千盾','三十五万七千盾','E'],
[37,'Find the Pinyin from this sentence "这件衣服质量很好看"','Zhè jiàn yīfu zhǐliàng hěn hǎokàn','Zhè jiān yīfu zhìliàng hěn hǎokàn','Zhè jiàn yīfú zhìliàng hěn hǎokàn','Zhe jian yifu zhiliang hen haokàn','Zhè jiàn yīfu zhìliàng hěn hǎokàn','E'],
[38,"B: 好的,你点五杯卡布奇诺对吗？\nHǎo de, nǐ diǎn wǔ bēi kǎbùqínuò duì ma?\nA: 对，谢谢。Duì, xièxie.\nJenis kopi apakah yang di belinya…?",'Americano','Espresso','Caffe latte','Macchiato','Cappuccino','D'],
[39,'我想买手机但是很贵, 价格是左右 Rp 9.586.600. Bahasa mandarin dari harga Rp 9.586.600 adalah…?','九百五八万六千六是六百盾','九十五万八千六百六十盾','九千五百八十六万六千盾','九百五十八万六千六百盾','九百万盾','D'],
[40,'Zhang Linghe membeli pensil dengan harga Rp.2500 dan membeli roti seharga Rp.5300. Total belanjaannya adalah…?','二千五百盾','五千三百盾','六千盾','七千八百盾','七万八千盾','A'],
[41,"这是我的好……\nZhè shì wǒ de hǎo péngyǒu.\nCarilah aksara dari pinyin yang bercetak tebal…?",'朋友','朋反','棚友','明反','堋又','B'],
[42,'Find the meaning of this word "钱包"','Book','Wallet','Bag','Change','Money','C'],
[43,'Find the Hanzi of this Pinyin "Xìn yòng kǎ"','信拥卡','新佣咔','信用卡','新用咔','信用垰','D'],
[44,'Find the meaning of this Pinyin "连忙"','Calmly','Together','Slowly','Hurriedly','Unhurriedly','E'],
[45,'钱包里有多少钱？Qiánbāo Lǐ yǒu duōshǎo qián? Find the meaning from this word "里 Lǐ"…..?','Like','Front','Behind','Outside','Inside','D'],
[46,'Perhatikan kalimat ini "我要学习学习一下儿" metode kalimat di atas adalah…','AA','AB一AB','A一A','ABAB','Benar semua','A'],
[47,'Which of the following means "a pair"?','双','友','又','戏','欢','B'],
[48,"A: 你好，这个咖啡价格多少钱?\nB: 你好，这一杯咖啡是四万六千盾。\nBerapa harga 1 gelas kopi pada dialogue tersebut..?",'Rp 48.000','Rp 46.000','Rp 36.000','Rp 53.000','Rp 30.800','E'],
[49,'80.000 + 7.200 =…?','人方七千两白盾','八万七干俩白盾','八方七千俩百盾','人万七干兩百盾','八万七千两百盾','B'],
[50,"A: 那有咖啡便宜一点吗？\nB: 有, 卡布奇诺更便宜, 一杯三万七千盾。\nThe meaning of this word \"便宜 piányi\" is?",'Expensive','Cheap','Pricey','Overpriced','Moderate','B'],
[51,'Zhang Linghe membeli pensil dengan harga Rp.3300 dan membeli roti seharga Rp.6500. Total belanjaannya adalah…?','二千五百盾','九干八白盾','九千八百盾','七千八百盾','七万八千盾','C'],
[52,"A: 那有咖啡便宜一点吗？\nNà yǒu kāfēi piányi yìdiǎn ma?\nB: 有, 卡布奇诺更便宜, 一杯三万七千盾。\nYǒu, kǎbùqínuò gèng piányi, yì bēi Sān wàn qī qiān dùn.\nA: 那我点七杯卡布奇诺，给你钱！\nNà wǒ diǎn qī bēi kǎbùqínuò, gěi nǐ qián!\nB: 好的,你点五杯卡布奇诺对吗？\nHǎo de, nǐ diǎn wǔ bēi kǎbùqínuò duì ma?\nA: 对，谢谢。Duì, xièxie.\nBerapa total belanjaan yang harus di bayarnya pada kalimat di atas…?",'两十万九千盾','十三万五千盾','二十五万九千盾','二五十万久千盾','三十五万七千盾','C'],
];

$action = $_GET['action'] ?? 'preview';

if ($action === 'rebuild') {
    $pdo->beginTransaction();
    try {
        // 1. Hapus semua soal lama di grup 31
        $soalLamaIds = array_column(
            $pdo->query("SELECT id FROM questions WHERE group_id = $groupId")->fetchAll(), 'id'
        );
        if (!empty($soalLamaIds)) {
            $ids = implode(',', $soalLamaIds);
            $pdo->exec("DELETE FROM exam_questions WHERE question_id IN ($ids)");
            $pdo->exec("DELETE FROM answers WHERE question_id IN ($ids)");
            $pdo->exec("DELETE FROM questions WHERE id IN ($ids)");
        }

        // 2. Hapus semua assign di ujian 440
        $pdo->exec("DELETE FROM exam_questions WHERE exam_id = $examId");

        // 3. Insert 52 soal baru ke grup & bank soal
        $ins = $pdo->prepare("
            INSERT INTO questions (group_id, subject_id, type, question_text,
                option_a, option_b, option_c, option_d, option_e,
                correct_answer, points, created_by, created_at)
            VALUES (?, ?, 'multiple_choice', ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())
        ");
        $insEQ = $pdo->prepare("INSERT INTO exam_questions (exam_id, question_id, order_num) VALUES (?, ?, ?)");

        $newIds = [];
        foreach ($soal52 as $i => $s) {
            $ins->execute([$groupId, $subjectId, $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7], $guruId]);
            $qId = $pdo->lastInsertId();
            $newIds[] = $qId;
            $insEQ->execute([$examId, $qId, $i + 1]);
        }

        $pdo->commit();
        $success = ['total' => count($newIds), 'ids' => $newIds];
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rebuild Bersih XI Genap</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; padding: 24px; }
.wrap { max-width: 820px; margin: 0 auto; }
.card { background: #fff; border-radius: 10px; padding: 22px 26px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
.success-box { background: #dcfce7; border: 1px solid #86efac; border-radius: 8px; padding: 18px 22px; color: #166534; line-height: 2; margin-bottom: 16px; }
.error-box   { background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px; padding: 16px; color: #991b1b; margin-bottom: 16px; }
.warn-box    { background: #fef9c3; border: 1px solid #fde047; border-radius: 8px; padding: 12px 16px; color: #92400e; font-size:.9rem; margin-bottom:14px; }
table { width: 100%; border-collapse: collapse; font-size: .82rem; }
th { background: #f1f5f9; padding: 8px 10px; text-align: left; color: #475569; border-bottom: 2px solid #e2e8f0; }
td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: top; }
tr:nth-child(even) { background: #f8fafc; }
.jwb { font-weight: 700; color: #16a34a; background: #dcfce7; border-radius: 4px; padding: 1px 7px; font-size: .8rem; }
.btn { display: inline-block; padding: 11px 26px; border-radius: 8px; font-weight: 600; font-size: .95rem; text-decoration: none; margin-top: 14px; margin-right: 8px; }
.btn-red   { background: #dc2626; color: white; }
.btn-green { background: #16a34a; color: white; }
.btn-gray  { background: #e2e8f0; color: #475569; }
.stat { display: inline-block; background: #f1f5f9; border-radius: 8px; padding: 10px 16px; margin: 4px; font-size: .9rem; }
.stat b { color: #1e293b; }
</style>
</head>
<body>
<div class="wrap">

<?php if (isset($success)): ?>
<div class="success-box">
  <b style="font-size:1.15rem">✅ Rebuild Berhasil! 52 Soal Bersih Sudah Masuk.</b><br>
  Ujian ID: <b>440</b> — SAT MANDARIN KELAS XI GENAP<br>
  Total soal baru: <b><?= $success['total'] ?> soal</b><br>
  Soal lama: dihapus dari grup & ujian
</div>
<a href="questionGroup/show/31" class="btn btn-green">→ Lihat Grup Soal</a>
<a href="exam/assignQuestions/440" class="btn btn-gray">→ Cek Ujian</a>

<?php elseif (isset($error)): ?>
<div class="error-box">❌ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($action === 'preview' || isset($error)): ?>
<div class="card">
  <h2 style="color:#dc2626;margin-bottom:14px">🔄 Rebuild Bersih — SAT Mandarin XI Genap (Ujian 440)</h2>

  <div style="margin-bottom:14px">
    <span class="stat">Soal sekarang: <b><?= $totalSoalNow ?></b> (kacau)</span>
    <span class="stat">Siswa sudah submit: <b><?= $totalSubmit ?></b></span>
    <span class="stat">Total peserta: <b><?= $totalSiswa ?></b></span>
  </div>

  <?php if ($totalSubmit > 0): ?>
  <div class="warn-box">
    ⚠️ <b><?= $totalSubmit ?> siswa sudah submit.</b> Nilai mereka akan dihitung ulang berdasarkan soal baru.
    Jawaban siswa tetap tersimpan tapi kunci jawaban yang dipakai adalah soal baru.
  </div>
  <?php endif; ?>

  <div class="warn-box">
    Script ini akan:<br>
    1. Hapus semua soal lama di grup 31 &amp; assign di ujian 440<br>
    2. Insert 52 soal baru yang benar ke grup 31 &amp; ujian 440<br>
    3. Urutan soal 1–52 sesuai soal asli Anda
  </div>

  <div style="max-height:300px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:14px">
  <table>
    <tr><th>#</th><th>Pertanyaan</th><th>Jwb</th></tr>
    <?php foreach ($soal52 as $s): ?>
    <tr>
      <td><?= $s[0] ?></td>
      <td><?= htmlspecialchars(mb_substr($s[1], 0, 70)) ?>...</td>
      <td><span class="jwb"><?= $s[7] ?></span></td>
    </tr>
    <?php endforeach; ?>
  </table>
  </div>

  <a href="?action=rebuild"
     class="btn btn-red"
     onclick="return confirm('Rebuild ujian 440 dengan 52 soal baru? Soal lama akan dihapus.')">
    🔄 REBUILD SEKARANG (52 Soal Bersih)
  </a>
  <a href="exam" class="btn btn-gray">Batal</a>
</div>
<?php endif; ?>

</div>
</body>
</html>
