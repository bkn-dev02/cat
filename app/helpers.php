<?php

function config($key) {
    static $config = null;
    if ($config === null) {
        // Config.php ada di root directory
        $configFile = __DIR__ . '/../config.php';
        
        if (!file_exists($configFile)) {
            // Log error untuk debugging
            error_log("Config file not found at: $configFile");
            die('Configuration error. Please check server logs.');
        }
        
        try {
            $config = require $configFile;
            if (!is_array($config)) {
                throw new Exception('Config file must return an array');
            }
        } catch (Exception $e) {
            error_log("Config load error: " . $e->getMessage());
            die('Configuration error. Please check server logs.');
        }
    }
    return $config[$key] ?? null;
}

function app_path_url(string $path = ''): string {
    $scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $basePath = ($scriptDirectory === '.' || $scriptDirectory === '/') ? '' : rtrim($scriptDirectory, '/');
    $path = ltrim($path, '/');
    return $basePath . ($path === '' ? '/' : '/' . $path);
}

function asset_url(string $path = ''): string {
    return app_path_url('assets/' . ltrim($path, '/'));
}

function db() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $host = config('db_host');
            $dbname = config('db_name');
            $user = config('db_user');
            $pass = config('db_pass');
            
            if (!$host || !$dbname || !$user) {
                throw new Exception('Database configuration incomplete');
            }
            
            $pdo = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            
            // Set MySQL timezone ke WIB (Asia/Jakarta)
            $pdo->exec("SET time_zone = '+07:00'");
            
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());

            if (config('environment') === 'local') {
                http_response_code(500);
                die('Database connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
            }

            die("Database connection failed. Please try again later.");
        } catch (Exception $e) {
            error_log("Database config error: " . $e->getMessage());
            die("Database configuration error. Please check server logs.");
        }
    }
    return $pdo;
}

function answersForSessions(array $sessionIds): array {
    $sessionIds = array_values(array_unique(array_filter(
        $sessionIds,
        static function ($sessionId) {
            return $sessionId !== null && $sessionId !== '';
        }
    )));

    if (!$sessionIds) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($sessionIds), '?'));
    $stmt = db()->prepare("
        SELECT session_id, question_id, answer
        FROM answers
        WHERE session_id IN ($placeholders)
    ");
    $stmt->execute($sessionIds);

    $answersBySession = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $answersBySession[$row['session_id']][$row['question_id']] = $row['answer'];
    }

    return $answersBySession;
}

function view($file, $data = []) {
    extract($data);
    require VIEW_PATH . '/' . $file . '.php';
}

function redirect($url) {
    header('Location: ' . config('app_url') . '/' . $url);
    exit;
}

function auth() {
    return $_SESSION['user'] ?? null;
}

function isLoggedIn() {
    return isset($_SESSION['user']);
}

/**
 * Cek apakah request berasal dari ExamBro (browser ujian resmi).
 * ExamBro mengirimkan:
 *   - User-Agent mengandung "BKN-CAT-SecureBrowser"
 *   - Header X-Exam-Browser: BKN-CAT-SecureBrowser
 */
function isExamBro(): bool
{
    $userAgent  = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $examHeader = $_SERVER['HTTP_X_EXAM_BROWSER'] ?? '';

    return strpos($userAgent, 'BKN-CAT-SecureBrowser') !== false
        || $examHeader === 'BKN-CAT-SecureBrowser';
}

function requireAuth() {
    if (!isLoggedIn()) {
        redirect('auth/login');
    }

    // Pemeriksaan ExamBro sementara dinonaktifkan.
    $user = auth();
    // if (isset($user['role']) && in_array($user['role'], ['student', 'siswa']) && !isExamBro()) {
    //     session_destroy();
    //     requireExamBro();
    // }

    // ── Validasi single-session untuk siswa ──────────────────────────────
    // Jika ada session_id lain yang login dengan akun yang sama, paksa logout
    // if (isset($user['role']) && in_array($user['role'], ['student', 'siswa'])) {
    //     $currentPhpSession = session_id();

    //     // Ambil active_session_id terbaru dari DB (bukan dari cache $_SESSION)
    //     try {
    //         $stmt = db()->prepare("SELECT active_session_id FROM users WHERE id = ?");
    //         $stmt->execute([$user['id']]);
    //         $row = $stmt->fetch();
    //         $dbSessionId = $row['active_session_id'] ?? null;

    //         if ($dbSessionId && $dbSessionId !== $currentPhpSession) {
    //             // Sesi ini tidak valid lagi — ada login baru dari perangkat lain
    //             session_destroy();
    //             header('Location: ' . config('app_url') . '/auth/login?reason=concurrent');
    //             exit;
    //         }
    //     } catch (Exception $e) {
    //         // Jika gagal query, biarkan lanjut (graceful degradation)
    //         error_log("requireAuth single-session check error: " . $e->getMessage());
    //     }
    // }
    // ── Akhir validasi single-session ────────────────────────────────────
}

/**
 * Tampilkan halaman blokir dan hentikan eksekusi.
 * Dipanggil ketika siswa mencoba akses bukan dari ExamBro.
 */
function requireExamBro(): void
{
    // Buang semua output buffer yang mungkin sudah ada
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code(403);
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');

    echo '<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Akses Ditolak</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:\'Segoe UI\',sans-serif}
body{min-height:100vh;display:flex;align-items:center;justify-content:center;
     background:linear-gradient(135deg,#1e293b,#0f172a);padding:2rem}
.card{background:#fff;border-radius:1.5rem;padding:3rem 2.5rem;max-width:460px;
      width:100%;text-align:center;box-shadow:0 25px 50px rgba(0,0,0,.4)}
.icon{width:90px;height:90px;background:#fee2e2;border-radius:50%;display:inline-flex;
      align-items:center;justify-content:center;margin-bottom:1.5rem;font-size:2.5rem}
h2{font-weight:800;color:#1e293b;margin-bottom:.5rem}
p{color:#64748b;font-size:.95rem;line-height:1.6;margin-bottom:1rem}
.box{background:#eff6ff;border:1px solid #bfdbfe;border-radius:1rem;
     padding:1.25rem 1.5rem;text-align:left;margin:1.25rem 0}
.box strong{color:#1d4ed8;display:block;margin-bottom:.5rem}
ol{margin:0;padding-left:1.25rem;color:#1e3a5f;font-size:.9rem;line-height:2}
</style>
</head>
<body>
<div class="card">
  <div class="icon">🔒</div>
  <h2>Akses Ditolak</h2>
  <p>Halaman ujian <strong>hanya dapat diakses melalui ExamBro</strong>.<br>
     Browser biasa tidak diizinkan.</p>
  <div class="box">
    <strong>Cara Masuk yang Benar:</strong>
    <ol>
      <li>Buka aplikasi <strong>BKN CAT</strong> yang telah diunduh</li>
      <li>Login menggunakan username &amp; password Anda</li>
    </ol>
  </div>
  <p style="font-size:.8rem;color:#94a3b8">
    Belum punya ExamBro? Hubungi pengawas ujian.
  </p>
</div>
</body>
</html>';
    exit;
}
function requireRole($roles) {
    requireAuth();
    $user = auth();
    if (!in_array($user['role'], (array)$roles)) {
        $_SESSION['error'] = 'Akses ditolak';
        redirect('dashboard');
    }
}

function renderQuestionText(string $text): string
{
    if ($text === '') return '';
    
    if (strip_tags($text) !== $text) {
        $text = str_replace('&amp;nbsp;', ' ', $text);
        $text = str_replace('&nbsp;',     ' ', $text);
        $text = str_replace('&amp;amp;',  '&amp;', $text);
        $text = str_replace('&amp;lt;',   '&lt;',  $text);
        $text = str_replace('&amp;gt;',   '&gt;',  $text);

        $text = preg_replace_callback(
            '/(?<=>)([^<]+)(?=<)/',
            function($m) {
                return autoConvertMath($m[1]);
            },
            $text
        );
        return $text;
    }
    
    $text = autoConvertMath($text);
    $text = nl2br($text);
    return $text;
}
/**
 * Potong HTML dengan aman tanpa merusak tag yang terbuka.
 */
function truncateHtml(string $html, int $limit = 150): string
{
    if (mb_strlen(strip_tags($html)) <= $limit) return $html;

    // Izinkan hanya b, i, u, strong, em
    $html = strip_tags($html, '<b><i><u><strong><em>');

    $plainLength = 0;
    $result = '';
    $openTags = [];

    preg_match_all('/<[^>]+>|[^<]+/', $html, $tokens);

    foreach ($tokens[0] as $token) {
        if ($plainLength >= $limit) break;

        if ($token[0] === '<') {
            $result .= $token;
            if (preg_match('/<\/(\w+)/', $token, $m)) {
                array_pop($openTags);
            } elseif (preg_match('/<(\w+)/', $token, $m)) {
                $openTags[] = $m[1];
            }
        } else {
            $remaining = $limit - $plainLength;
            if (mb_strlen($token) > $remaining) {
                $result .= mb_substr($token, 0, $remaining) . '...';
                $plainLength = $limit;
            } else {
                $result .= $token;
                $plainLength += mb_strlen($token);
            }
        }
    }

    foreach (array_reverse($openTags) as $tag) {
        $result .= '</' . $tag . '>';
    }

    return $result;
}

// Fungsi waktu Indonesia
function waktuSekarang() {
    return date('Y-m-d H:i:s');
}

function formatWaktuIndonesia($datetime, $format = 'default') {
    if (empty($datetime)) return '-';
    
    // Pastikan timezone WIB
    $date = new DateTime($datetime);
    $date->setTimezone(new DateTimeZone('Asia/Jakarta'));
    
    $hari = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin', 
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    
    $bulan = [
        'January' => 'Januari',
        'February' => 'Februari',
        'March' => 'Maret',
        'April' => 'April',
        'May' => 'Mei',
        'June' => 'Juni',
        'July' => 'Juli',
        'August' => 'Agustus',
        'September' => 'September',
        'October' => 'Oktober',
        'November' => 'November',
        'December' => 'Desember'
    ];
    
    switch ($format) {
        case 'lengkap':
            $namaHari = $hari[$date->format('l')];
            $namaBulan = $bulan[$date->format('F')];
            return $namaHari . ', ' . $date->format('j') . ' ' . $namaBulan . ' ' . $date->format('Y') . ' ' . $date->format('H:i') . ' WIB';
            
        case 'd M Y':
            $namaBulan = $bulan[$date->format('F')];
            return $date->format('j') . ' ' . substr($namaBulan, 0, 3) . ' ' . $date->format('Y');
            
        case 'd M Y, H:i':
            $namaBulan = $bulan[$date->format('F')];
            return $date->format('j') . ' ' . substr($namaBulan, 0, 3) . ' ' . $date->format('Y') . ', ' . $date->format('H:i');
            
        default:
            return $date->format('d/m/Y H:i');
    }
}

// Fungsi untuk auto convert math expressions
function convertMathExpressions($text) {
   if ($text === '' || $text === null) return $text;
    
    // Sudah ada delimiter MathJax standar — tidak perlu diapa-apakan
    // Tapi bersihkan spasi di dalam $ delimiter: "$ ^-1 $" → "$^{-1}$"
    
    // Fix spasi tepat setelah $ pembuka dan sebelum $ penutup
    $text = preg_replace('/\$\s+/', '$', $text);
    $text = preg_replace('/\s+\$/', '$', $text);
    
    // Fix eksponen tanpa kurung kurawal di dalam $...$
    // ^-2 → ^{-2}, ^2 → ^{2}  (tapi ^{2} tidak diubah lagi)
    $text = preg_replace_callback('/\$([^$\n]+?)\$/', function($m) {
        $inner = $m[1];
        $inner = preg_replace('/\^(-?\d+)(?!\})/', '^{$1}', $inner);
        return '$' . trim($inner) . '$';
    }, $text);
    
    return $text;
}

// Alias for backward compatibility
function autoConvertMath($text) {
    if ($text === '' || $text === null) return '';
    
    $isHtml = $text !== strip_tags($text);
    
    $convertMath = function($t) {
        $t = preg_replace('/(?<![:\w])(\d+)\/(\d+)/', '$\\frac{$1}{$2}$', $t);
        $t = preg_replace('/([a-zA-Z0-9])²/', '$$1^2$', $t);
        $t = preg_replace('/([a-zA-Z0-9])³/', '$$1^3$', $t);
        $subs = [
            '₀'=>'0','₁'=>'1','₂'=>'2','₃'=>'3','₄'=>'4',
            '₅'=>'5','₆'=>'6','₇'=>'7','₈'=>'8','₉'=>'9',
        ];
        foreach ($subs as $ch => $num) {
            $t = preg_replace(
                '/([a-zA-Z0-9])' . preg_quote($ch, '/') . '/',
                '$$1_{' . $num . '}$', $t
            );
        }
        $supers = [
            '⁰'=>'0','¹'=>'1','²'=>'2','³'=>'3','⁴'=>'4',
            '⁵'=>'5','⁶'=>'6','⁷'=>'7','⁸'=>'8','⁹'=>'9',
        ];
        foreach ($supers as $ch => $num) {
            $t = preg_replace(
                '/([a-zA-Z0-9])' . preg_quote($ch, '/') . '/',
                '$$1^{' . $num . '}$', $t
            );
        }
        return $t;
    };
    
    if ($isHtml) {
        $text = str_replace('&amp;nbsp;', ' ', $text);  
        $text = str_replace('&amp;amp;',  '&amp;',  $text);
        $text = str_replace('&amp;lt;',   '&lt;',   $text);
        $text = str_replace('&amp;gt;',   '&gt;',   $text);
        $text = preg_replace_callback(
            '/(?<![="\'])>([^<]+)</',
            function($m) use ($convertMath) {
                return '>' . $convertMath($m[1]) . '<';
            },
            $text
        );
        if (!preg_match('/^\s*</', $text)) {
            $text = preg_replace_callback(
                '/^([^<]+)/',
                function($m) use ($convertMath) {
                    return $convertMath($m[1]);
                },
                $text
            );
        }
    } else {
        // Plain text — JANGAN htmlspecialchars dulu, konversi math dulu
        $converted = $convertMath($text);
        
        // Pisahkan bagian math ($...$) dan non-math, escape hanya bagian non-math
        $parts = preg_split('/(\$\$[^$]+\$\$|\$[^$]+\$)/', $converted, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result = '';
        foreach ($parts as $part) {
            if (preg_match('/^\$/', $part)) {
                // Bagian math — jangan di-escape
                $result .= $part;
            } else {
                // Bagian non-math — escape HTML
                $result .= htmlspecialchars($part, ENT_QUOTES, 'UTF-8');
            }
        }
        $text = $result;
    }
    
    return $text;
}

// Fungsi untuk convert datetime ke format datetime-local HTML5
function toDatetimeLocal($datetime) {
    if (empty($datetime)) return '';
    
    try {
        $date = new DateTime($datetime);
        $date->setTimezone(new DateTimeZone('Asia/Jakarta'));
        return $date->format('Y-m-d\TH:i');
    } catch (Exception $e) {
        return '';
    }
}

// Fungsi untuk convert dari datetime-local ke database format
function fromDatetimeLocal($datetimeLocal) {
    if (empty($datetimeLocal)) return null;
    
    try {
        $date = new DateTime($datetimeLocal, new DateTimeZone('Asia/Jakarta'));
        return $date->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return null;
    }
}