<?php
// Show all errors except warnings, notices, and deprecated
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
?>

<?php
// Quranic Study Platform - Single File Application
// Author: Yasin Ullah
// Country: Pakistan

// --- Configuration ---
define('DB_PATH', __DIR__ . '/database/quran.sqlite');
define('DATA_AM_PATH', __DIR__ . '/data/data.AM');
define('QURAN_AUDIO_BASE_URL', 'https://everyayah.com/data/Alafasy_128kbps/'); // Example audio source
define('DEFAULT_RECITER', 'Alafasy_128kbps');
define('SITE_NAME', 'Quranic Study Platform');
define('ADMIN_EMAIL', 'admin@example.com'); // For notifications

// --- Database Initialization ---
function db_connect() {
    try {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

function db_init() {
    $pdo = db_connect();
    $schema = "
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        email TEXT UNIQUE,
        role TEXT DEFAULT 'user', -- public, user, ulama, admin
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_login DATETIME
    );

    CREATE TABLE IF NOT EXISTS surahs (
        id INTEGER PRIMARY KEY,
        surah_number INTEGER UNIQUE NOT NULL,
        arabic_name TEXT NOT NULL,
        english_name TEXT NOT NULL,
        ayah_count INTEGER NOT NULL,
        revelation_type TEXT -- Mecca, Medina
    );

    CREATE TABLE IF NOT EXISTS ayahs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        surah_id INTEGER NOT NULL,
        ayah_number INTEGER NOT NULL,
        arabic_text TEXT NOT NULL,
        urdu_translation TEXT,
        juz INTEGER,
        hizb INTEGER,
        quarter INTEGER,
        FOREIGN KEY (surah_id) REFERENCES surahs(id)
    );

    CREATE TABLE IF NOT EXISTS translations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ayah_id INTEGER NOT NULL,
        language TEXT NOT NULL, -- e.g., en, ur
        text TEXT NOT NULL,
        contributor_id INTEGER, -- NULL for imported
        status TEXT DEFAULT 'approved', -- pending, approved, rejected
        version INTEGER DEFAULT 1,
        is_default BOOLEAN DEFAULT FALSE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ayah_id) REFERENCES ayahs(id),
        FOREIGN KEY (contributor_id) REFERENCES users(id)
    );

    CREATE TABLE IF NOT EXISTS tafasir (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ayah_id INTEGER NOT NULL,
        title TEXT, -- e.g., Tafsir Ibn Kathir
        text TEXT NOT NULL,
        contributor_id INTEGER,
        status TEXT DEFAULT 'approved', -- pending, approved, rejected
        version INTEGER DEFAULT 1,
        is_default BOOLEAN DEFAULT FALSE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ayah_id) REFERENCES ayahs(id),
        FOREIGN KEY (contributor_id) REFERENCES users(id)
    );

    CREATE TABLE IF NOT EXISTS word_meanings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ayah_id INTEGER NOT NULL,
        word_index INTEGER NOT NULL, -- 0-based index of the word in the Arabic text
        arabic_word TEXT NOT NULL,
        transliteration TEXT,
        meaning TEXT,
        grammar TEXT,
        contributor_id INTEGER,
        status TEXT DEFAULT 'approved', -- pending, approved, rejected
        version INTEGER DEFAULT 1,
        is_default BOOLEAN DEFAULT FALSE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ayah_id) REFERENCES ayahs(id),
        FOREIGN KEY (contributor_id) REFERENCES users(id)
    );

    CREATE TABLE IF NOT EXISTS bookmarks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        ayah_id INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (ayah_id) REFERENCES ayahs(id)
    );

    CREATE TABLE IF NOT EXISTS notes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        ayah_id INTEGER NOT NULL,
        note TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (ayah_id) REFERENCES ayahs(id)
    );

    CREATE TABLE IF NOT EXISTS hifz_progress (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        surah_id INTEGER NOT NULL,
        ayah_id INTEGER NOT NULL, -- Last memorized ayah
        progress_date DATE DEFAULT CURRENT_DATE,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (surah_id) REFERENCES surahs(id),
        FOREIGN KEY (ayah_id) REFERENCES ayahs(id)
    );

    CREATE TABLE IF NOT EXISTS user_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER UNIQUE NOT NULL,
        setting_key TEXT NOT NULL,
        setting_value TEXT,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );

    CREATE TABLE IF NOT EXISTS content_suggestions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        content_type TEXT NOT NULL, -- translation, tafsir, word_meaning
        ayah_id INTEGER,
        word_index INTEGER, -- For word_meaning
        suggested_text TEXT NOT NULL,
        status TEXT DEFAULT 'pending', -- pending, approved, rejected
        reviewed_by INTEGER, -- User ID of reviewer
        reviewed_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (ayah_id) REFERENCES ayahs(id),
        FOREIGN KEY (reviewed_by) REFERENCES users(id)
    );

    CREATE TABLE IF NOT EXISTS site_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        setting_key TEXT UNIQUE NOT NULL,
        setting_value TEXT
    );
    ";

    $pdo->exec($schema);

    // Check if Surahs are already populated
    $stmt = $pdo->query("SELECT COUNT(*) FROM surahs");
    if ($stmt->fetchColumn() == 0) {
        // Populate Surah names and metadata
        $surahs_data = [
            [1, 'الفاتحة', 'Al-Fatiha', 7, 'Mecca'],
            [2, 'البقرة', 'Al-Baqarah', 286, 'Medina'],
            [3, 'آل عمران', 'Al-Imran', 200, 'Medina'],
            [4, 'النساء', 'An-Nisa', 176, 'Medina'],
            [5, 'المائدة', 'Al-Maidah', 120, 'Medina'],
            [6, 'الأنعام', 'Al-An\'am', 165, 'Mecca'],
            [7, 'الأعراف', 'Al-A\'raf', 206, 'Mecca'],
            [8, 'الأنفال', 'Al-Anfal', 75, 'Medina'],
            [9, 'التوبة', 'At-Tawbah', 129, 'Medina'],
            [10, 'يونس', 'Yunus', 109, 'Mecca'],
            [11, 'هود', 'Hud', 123, 'Mecca'],
            [12, 'يوسف', 'Yusuf', 111, 'Mecca'],
            [13, 'الرعد', 'Ar-Ra\'d', 43, 'Medina'],
            [14, 'ابراهيم', 'Ibrahim', 52, 'Mecca'],
            [15, 'الحجر', 'Al-Hijr', 99, 'Mecca'],
            [16, 'النحل', 'An-Nahl', 128, 'Mecca'],
            [17, 'الإسراء', 'Al-Isra', 111, 'Mecca'],
            [18, 'الكهف', 'Al-Kahf', 110, 'Mecca'],
            [19, 'مريم', 'Maryam', 98, 'Mecca'],
            [20, 'طه', 'Ta-Ha', 135, 'Mecca'],
            [21, 'الأنبياء', 'Al-Anbiya', 112, 'Mecca'],
            [22, 'الحج', 'Al-Hajj', 78, 'Medina'],
            [23, 'المؤمنون', 'Al-Mu\'minun', 118, 'Mecca'],
            [24, 'النور', 'An-Nur', 64, 'Medina'],
            [25, 'الفرقان', 'Al-Furqan', 77, 'Mecca'],
            [26, 'الشعراء', 'Ash-Shu\'ara', 227, 'Mecca'],
            [27, 'النمل', 'An-Naml', 93, 'Mecca'],
            [28, 'القصص', 'Al-Qasas', 88, 'Mecca'],
            [29, 'العنكبوت', 'Al-Ankabut', 69, 'Mecca'],
            [30, 'الروم', 'Ar-Rum', 60, 'Mecca'],
            [31, 'لقمان', 'Luqman', 34, 'Mecca'],
            [32, 'السجدة', 'As-Sajdah', 30, 'Mecca'],
            [33, 'الأحزاب', 'Al-Ahzab', 73, 'Medina'],
            [34, 'سبأ', 'Saba', 54, 'Mecca'],
            [35, 'فاطر', 'Fatir', 45, 'Mecca'],
            [36, 'يس', 'Ya-Sin', 83, 'Mecca'],
            [37, 'الصافات', 'As-Saffat', 182, 'Mecca'],
            [38, 'ص', 'Sad', 88, 'Mecca'],
            [39, 'الزمر', 'Az-Zumar', 75, 'Mecca'],
            [40, 'غافر', 'Ghafir', 85, 'Mecca'],
            [41, 'فصلت', 'Fussilat', 54, 'Mecca'],
            [42, 'الشورى', 'Ash-Shuraa', 53, 'Mecca'],
            [43, 'الزخرف', 'Az-Zukhruf', 89, 'Mecca'],
            [44, 'الدخان', 'Ad-Dukhan', 59, 'Mecca'],
            [45, 'الجاثية', 'Al-Jathiyah', 37, 'Mecca'],
            [46, 'الأحقاف', 'Al-Ahqaf', 35, 'Mecca'],
            [47, 'محمد', 'Muhammad', 38, 'Medina'],
            [48, 'الفتح', 'Al-Fath', 29, 'Medina'],
            [49, 'الحجرات', 'Al-Hujurat', 18, 'Medina'],
            [50, 'ق', 'Qaf', 45, 'Mecca'],
            [51, 'الذاريات', 'Adh-Dhariyat', 60, 'Mecca'],
            [52, 'الطور', 'At-Tur', 49, 'Mecca'],
            [53, 'النجم', 'An-Najm', 62, 'Mecca'],
            [54, 'القمر', 'Al-Qamar', 55, 'Mecca'],
            [55, 'الرحمن', 'Ar-Rahman', 78, 'Medina'],
            [56, 'الواقعة', 'Al-Waqi\'ah', 96, 'Mecca'],
            [57, 'الحديد', 'Al-Hadid', 29, 'Medina'],
            [58, 'المجادلة', 'Al-Mujadila', 22, 'Medina'],
            [59, 'الحشر', 'Al-Hashr', 24, 'Medina'],
            [60, 'الممتحنة', 'Al-Mumtahanah', 13, 'Medina'],
            [61, 'الصف', 'As-Saff', 14, 'Medina'],
            [62, 'الجمعة', 'Al-Jumu\'ah', 11, 'Medina'],
            [63, 'المنافقون', 'Al-Munafiqun', 11, 'Medina'],
            [64, 'التغابن', 'At-Taghabun', 18, 'Medina'],
            [65, 'الطلاق', 'At-Talaq', 12, 'Medina'],
            [66, 'التحريم', 'At-Tahrim', 12, 'Medina'],
            [67, 'الملك', 'Al-Mulk', 30, 'Mecca'],
            [68, 'القلم', 'Al-Qalam', 52, 'Mecca'],
            [69, 'الحاقة', 'Al-Haqqah', 52, 'Mecca'],
            [70, 'المعارج', 'Al-Ma\'arij', 44, 'Mecca'],
            [71, 'نوح', 'Nuh', 28, 'Mecca'],
            [72, 'الجن', 'Al-Jinn', 28, 'Mecca'],
            [73, 'المزمل', 'Al-Muzzammil', 20, 'Mecca'],
            [74, 'المدثر', 'Al-Muddaththir', 56, 'Mecca'],
            [75, 'القيامة', 'Al-Qiyamah', 40, 'Mecca'],
            [76, 'الانسان', 'Al-Insan', 31, 'Medina'],
            [77, 'المرسلات', 'Al-Mursalat', 50, 'Mecca'],
            [78, 'النبأ', 'An-Naba', 40, 'Mecca'],
            [79, 'النازعات', 'An-Nazi\'at', 46, 'Mecca'],
            [80, 'عبس', 'Abasa', 42, 'Mecca'],
            [81, 'التكوير', 'At-Takwir', 29, 'Mecca'],
            [82, 'الإنفطار', 'Al-Infitar', 19, 'Mecca'],
            [83, 'المطففين', 'Al-Mutaffifin', 36, 'Mecca'],
            [84, 'الإنشقاق', 'Al-Inshiqaq', 25, 'Mecca'],
            [85, 'البروج', 'Al-Buruj', 22, 'Mecca'],
            [86, 'الطارق', 'At-Tariq', 17, 'Mecca'],
            [87, 'الأعلى', 'Al-A\'la', 19, 'Mecca'],
            [88, 'الغاشية', 'Al-Ghashiyah', 26, 'Mecca'],
            [89, 'الفجر', 'Al-Fajr', 30, 'Mecca'],
            [90, 'البلد', 'Al-Balad', 20, 'Mecca'],
            [91, 'الشمس', 'Ash-Shams', 15, 'Mecca'],
            [92, 'الليل', 'Al-Layl', 21, 'Mecca'],
            [93, 'الضحى', 'Ad-Duhaa', 11, 'Mecca'],
            [94, 'الشرح', 'Ash-Sharh', 8, 'Mecca'],
            [95, 'التين', 'At-Tin', 8, 'Mecca'],
            [96, 'العلق', 'Al-Alaq', 19, 'Mecca'],
            [97, 'القدر', 'Al-Qadr', 5, 'Mecca'],
            [98, 'البينة', 'Al-Bayyinah', 8, 'Medina'],
            [99, 'الزلزلة', 'Az-Zalzalah', 8, 'Medina'],
            [100, 'العاديات', 'Al-Adiyat', 11, 'Mecca'],
            [101, 'القارعة', 'Al-Qari\'ah', 11, 'Mecca'],
            [102, 'التكاثر', 'At-Takathur', 8, 'Mecca'],
            [103, 'العصر', 'Al-Asr', 3, 'Mecca'],
            [104, 'الهمزة', 'Al-Humazah', 9, 'Mecca'],
            [105, 'الفيل', 'Al-Fil', 5, 'Mecca'],
            [106, 'قريش', 'Quraysh', 4, 'Mecca'],
            [107, 'الماعون', 'Al-Ma\'un', 7, 'Mecca'],
            [108, 'الكوثر', 'Al-Kawthar', 3, 'Mecca'],
            [109, 'الكافرون', 'Al-Kafirun', 6, 'Mecca'],
            [110, 'النصر', 'An-Nasr', 3, 'Medina'],
            [111, 'المسد', 'Al-Masad', 5, 'Mecca'],
            [112, 'الإخلاص', 'Al-Ikhlas', 4, 'Mecca'],
            [113, 'الفلق', 'Al-Falaq', 5, 'Mecca'],
            [114, 'الناس', 'An-Nas', 6, 'Mecca']
        ];

        $stmt = $pdo->prepare("INSERT INTO surahs (surah_number, arabic_name, english_name, ayah_count, revelation_type) VALUES (?, ?, ?, ?, ?)");
        foreach ($surahs_data as $surah) {
            $stmt->execute($surah);
        }
    }

    // Check if Ayahs are already populated
    $stmt = $pdo->query("SELECT COUNT(*) FROM ayahs");
    if ($stmt->fetchColumn() == 0) {
        import_quran_data($pdo);
    }

    // Create default admin user if none exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    if ($stmt->fetchColumn() == 0) {
        $password = password_hash('admin123', PASSWORD_DEFAULT); // CHANGE THIS IN PRODUCTION
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', $password, ADMIN_EMAIL, 'admin']);
    }
}

function import_quran_data($pdo) {
    if (!file_exists(DATA_AM_PATH)) {
        error_log("Data file not found: " . DATA_AM_PATH);
        return false;
    }

    $file = file(DATA_AM_PATH, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($file === false) {
        error_log("Failed to read data file: " . DATA_AM_PATH);
        return false;
    }

    $pdo->beginTransaction();
    $ayah_insert_stmt = $pdo->prepare("INSERT INTO ayahs (surah_id, ayah_number, arabic_text, urdu_translation, juz, hizb, quarter) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $translation_insert_stmt = $pdo->prepare("INSERT INTO translations (ayah_id, language, text, is_default) VALUES (?, ?, ?, ?)");

    $current_juz = 1;
    $current_hizb = 1;
    $current_quarter = 1;
    $ayah_counter = 0; // Global ayah counter for Juz/Hizb calculation

    foreach ($file as $line) {
        // Example line: بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ ترجمہ: شروع اللہ کے نام سے جو بڑا مہربان نہایت رحم والا ہے<br/>س 001 آ 001
        if (preg_match('/^(.*?) ترجمہ: (.*?)<br\/>س (\d{3}) آ (\d{3})$/u', $line, $matches)) {
            $arabic_text = trim($matches[1]);
            $urdu_translation = trim($matches[2]);
            $surah_number = (int)$matches[3];
            $ayah_number = (int)$matches[4];

            // Simple Juz/Hizb/Quarter calculation (approximate based on ayah count)
            // This is a simplification; proper calculation requires specific markers.
            // For this example, we'll just increment based on ayah count.
            // A real app would need a separate data source for these markers.
            $ayah_counter++;
            if ($ayah_counter > 0 && $ayah_counter % 20 == 0) { // Approx 20 ayahs per quarter
                 $current_quarter++;
                 if ($current_quarter > 4) {
                     $current_quarter = 1;
                     $current_hizb++;
                     if ($current_hizb > 2) { // Approx 2 hizbs per juz
                         $current_hizb = 1;
                         $current_juz++;
                     }
                 }
            }


            // Find surah_id
            $stmt = $pdo->prepare("SELECT id FROM surahs WHERE surah_number = ?");
            $stmt->execute([$surah_number]);
            $surah = $stmt->fetch();
            $surah_id = $surah ? $surah['id'] : null;

            if ($surah_id) {
                $ayah_insert_stmt->execute([$surah_id, $ayah_number, $arabic_text, $urdu_translation, $current_juz, $current_hizb, $current_quarter]);
                $ayah_id = $pdo->lastInsertId();

                // Insert Urdu translation as a default translation
                $translation_insert_stmt->execute([$ayah_id, 'ur', $urdu_translation, TRUE]);

            } else {
                error_log("Could not find surah_id for Surah " . $surah_number);
            }
        } else {
            error_log("Failed to parse line: " . $line);
        }
    }

    $pdo->commit();
    return true;
}


// --- Authentication and Authorization ---
session_start();

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function get_user_id() {
    return is_logged_in() ? $_SESSION['user_id'] : null;
}

function get_user_role() {
    return is_logged_in() ? $_SESSION['user_role'] : 'public';
}

function has_role($required_role) {
    $user_role = get_user_role();
    $roles = ['public', 'user', 'ulama', 'admin'];
    return array_search($user_role, $roles) >= array_search($required_role, $roles);
}

function login($username, $password) {
    $pdo = db_connect();
    $stmt = $pdo->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['username'] = $username; // Store username for display
        // Update last login time
        //$update_stmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
        //$update_stmt->execute([$user['id']]);
        return true;
    }
    return false;
}

function logout() {
    session_unset();
    session_destroy();
}

function register($username, $password, $email) {
    $pdo = db_connect();
    // Check if username or email already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetchColumn() > 0) {
        return false; // Username or email already taken
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'user')");
    return $stmt->execute([$username, $hashed_password, $email]);
}

// --- CSRF Protection ---
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// --- Data Retrieval Functions ---
function get_surahs() {
    $pdo = db_connect();
    $stmt = $pdo->query("SELECT * FROM surahs ORDER BY surah_number");
    return $stmt->fetchAll();
}

function get_surah_by_number($surah_number) {
    $pdo = db_connect();
    $stmt = $pdo->prepare("SELECT * FROM surahs WHERE surah_number = ?");
    $stmt->execute([$surah_number]);
    return $stmt->fetch();
}

function get_ayahs_by_surah($surah_id) {
    $pdo = db_connect();
    $stmt = $pdo->prepare("SELECT * FROM ayahs WHERE surah_id = ? ORDER BY ayah_number");
    $stmt->execute([$surah_id]);
    return $stmt->fetchAll();
}

function get_ayah_by_surah_ayah($surah_number, $ayah_number) {
    $pdo = db_connect();
    $stmt = $pdo->prepare("
        SELECT a.*, s.arabic_name, s.english_name
        FROM ayahs a
        JOIN surahs s ON a.surah_id = s.id
        WHERE s.surah_number = ? AND a.ayah_number = ?
    ");
    $stmt->execute([$surah_number, $ayah_number]);
    return $stmt->fetch();
}

function get_translations($ayah_id, $status = 'approved') {
    $pdo = db_connect();
    $stmt = $pdo->prepare("SELECT * FROM translations WHERE ayah_id = ? AND status = ? ORDER BY is_default DESC, version DESC");
    $stmt->execute([$ayah_id, $status]);
    return $stmt->fetchAll();
}

function get_tafasir($ayah_id, $status = 'approved') {
    $pdo = db_connect();
    $stmt = $pdo->prepare("SELECT * FROM tafasir WHERE ayah_id = ? AND status = ? ORDER BY is_default DESC, version DESC");
    $stmt->execute([$ayah_id, $status]);
    return $stmt->fetchAll();
}

function get_word_meanings($ayah_id, $status = 'approved') {
    $pdo = db_connect();
    $stmt = $pdo->prepare("SELECT * FROM word_meanings WHERE ayah_id = ? AND status = ? ORDER BY word_index ASC, is_default DESC, version DESC");
    $stmt->execute([$ayah_id, $status]);
    return $stmt->fetchAll();
}

function get_user_bookmarks($user_id) {
    $pdo = db_connect();
    $stmt = $pdo->prepare("
        SELECT b.*, a.surah_id, a.ayah_number, s.arabic_name, s.english_name
        FROM bookmarks b
        JOIN ayahs a ON b.ayah_id = a.id
        JOIN surahs s ON a.surah_id = s.id
        WHERE b.user_id = ? ORDER BY a.surah_id, a.ayah_number
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function get_user_notes($user_id) {
    $pdo = db_connect();
    $stmt = $pdo->prepare("
        SELECT n.*, a.surah_id, a.ayah_number, s.arabic_name, s.english_name
        FROM notes n
        JOIN ayahs a ON n.ayah_id = a.id
        JOIN surahs s ON a.surah_id = s.id
        WHERE n.user_id = ? ORDER BY a.surah_id, a.ayah_number
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function get_user_hifz_progress($user_id) {
    $pdo = db_connect();
    $stmt = $pdo->prepare("
        SELECT hp.*, s.arabic_name, s.english_name, a.ayah_number as last_ayah_number
        FROM hifz_progress hp
        JOIN surahs s ON hp.surah_id = s.id
        JOIN ayahs a ON hp.ayah_id = a.id
        WHERE hp.user_id = ? ORDER BY hp.progress_date DESC, hp.surah_id
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function get_user_setting($user_id, $key, $default = null) {
    $pdo = db_connect();
    $stmt = $pdo->prepare("SELECT setting_value FROM user_settings WHERE user_id = ? AND setting_key = ?");
    $stmt->execute([$user_id, $key]);
    $setting = $stmt->fetch();
    return $setting ? $setting['setting_value'] : $default;
}

function set_user_setting($user_id, $key, $value) {
    $pdo = db_connect();
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO user_settings (user_id, setting_key, setting_value) VALUES (?, ?, ?)");
    return $stmt->execute([$user_id, $key, $value]);
}

function get_content_suggestions($status = 'pending') {
    $pdo = db_connect();
    $sql = "
        SELECT cs.*, u.username as suggested_by, a.surah_id, a.ayah_number, s.arabic_name, s.english_name
        FROM content_suggestions cs
        JOIN users u ON cs.user_id = u.id
        LEFT JOIN ayahs a ON cs.ayah_id = a.id
        LEFT JOIN surahs s ON a.surah_id = s.id
    ";
    $params = [];
    if ($status !== 'all') {
        $sql .= " WHERE cs.status = ?";
        $params[] = $status;
    }
    $sql .= " ORDER BY cs.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_site_setting($key, $default = null) {
    $pdo = db_connect();
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $setting = $stmt->fetch();
    return $setting ? $setting['setting_value'] : $default;
}

function set_site_setting($key, $value) {
    $pdo = db_connect();
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
    return $stmt->execute([$key, $value]);
}

// --- Data Manipulation Functions ---
function add_bookmark($user_id, $ayah_id) {
    $pdo = db_connect();
    // Check if bookmark already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookmarks WHERE user_id = ? AND ayah_id = ?");
    $stmt->execute([$user_id, $ayah_id]);
    if ($stmt->fetchColumn() > 0) {
        return false; // Bookmark already exists
    }
    $stmt = $pdo->prepare("INSERT INTO bookmarks (user_id, ayah_id) VALUES (?, ?)");
    return $stmt->execute([$user_id, $ayah_id]);
}

function remove_bookmark($user_id, $ayah_id) {
    $pdo = db_connect();
    $stmt = $pdo->prepare("DELETE FROM bookmarks WHERE user_id = ? AND ayah_id = ?");
    return $stmt->execute([$user_id, $ayah_id]);
}

function add_note($user_id, $ayah_id, $note) {
    $pdo = db_connect();
    $stmt = $pdo->prepare("INSERT INTO notes (user_id, ayah_id, note) VALUES (?, ?, ?)");
    return $stmt->execute([$user_id, $ayah_id, $note]);
}

function update_note($note_id, $user_id, $note) {
    $pdo = db_connect();
    $stmt = $pdo->prepare("UPDATE notes SET note = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
    return $stmt->execute([$note, $note_id, $user_id]);
}

function delete_note($note_id, $user_id) {
    $pdo = db_connect();
    $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
    return $stmt->execute([$note_id, $user_id]);
}

function update_hifz_progress($user_id, $surah_id, $ayah_id) {
    $pdo = db_connect();
    // Check if progress for this surah exists
    $stmt = $pdo->prepare("SELECT id FROM hifz_progress WHERE user_id = ? AND surah_id = ?");
    $stmt->execute([$user_id, $surah_id]);
    $progress = $stmt->fetch();

    if ($progress) {
        $stmt = $pdo->prepare("UPDATE hifz_progress SET ayah_id = ?, progress_date = CURRENT_DATE WHERE id = ?");
        return $stmt->execute([$ayah_id, $progress['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO hifz_progress (user_id, surah_id, ayah_id) VALUES (?, ?, ?)");
        return $stmt->execute([$user_id, $surah_id, $ayah_id]);
    }
}

function suggest_content($user_id, $content_type, $ayah_id, $suggested_text, $word_index = null) {
    if (!has_role('user')) return false;
    $pdo = db_connect();
    $stmt = $pdo->prepare("INSERT INTO content_suggestions (user_id, content_type, ayah_id, word_index, suggested_text) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$user_id, $content_type, $ayah_id, $word_index, $suggested_text]);
}

function approve_suggestion($suggestion_id, $reviewer_id) {
    if (!has_role('ulama')) return false;
    $pdo = db_connect();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT * FROM content_suggestions WHERE id = ? AND status = 'pending'");
        $stmt->execute([$suggestion_id]);
        $suggestion = $stmt->fetch();

        if (!$suggestion) {
            $pdo->rollBack();
            return false; // Suggestion not found or already reviewed
        }

        $ayah_id = $suggestion['ayah_id'];
        $suggested_text = $suggestion['suggested_text'];
        $contributor_id = $suggestion['user_id']; // The user who suggested it

        // Determine the table and columns based on content_type
        $table = '';
        $columns = ['ayah_id', 'text', 'contributor_id', 'status', 'version', 'is_default'];
        $values = [$ayah_id, $suggested_text, $contributor_id, 'approved', 1, FALSE]; // Default to not default, version 1

        switch ($suggestion['content_type']) {
            case 'translation':
                $table = 'translations';
                // Need to determine language - maybe add language field to suggestions?
                // For now, assume Urdu for user suggestions based on data.AM
                $columns = ['ayah_id', 'language', 'text', 'contributor_id', 'status', 'version', 'is_default'];
                $values = [$ayah_id, 'ur', $suggested_text, $contributor_id, 'approved', 1, FALSE];
                break;
            case 'tafsir':
                $table = 'tafasir';
                 $columns = ['ayah_id', 'title', 'text', 'contributor_id', 'status', 'version', 'is_default'];
                 // Need a title for Tafsir - maybe add title field to suggestions?
                 // For now, use a generic title
                 $values = [$ayah_id, 'User Contribution', $suggested_text, $contributor_id, 'approved', 1, FALSE];
                break;
            case 'word_meaning':
                $table = 'word_meanings';
                $word_index = $suggestion['word_index'];
                // Need arabic_word, transliteration, grammar - maybe add fields to suggestions?
                // For now, just store the meaning text
                 $columns = ['ayah_id', 'word_index', 'meaning', 'contributor_id', 'status', 'version', 'is_default'];
                 $values = [$ayah_id, $word_index, $suggested_text, $contributor_id, 'approved', 1, FALSE];
                break;
            default:
                $pdo->rollBack();
                return false; // Invalid content type
        }

        // Insert the new approved content
        $insert_sql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', array_fill(0, count($values), '?')) . ")";
        $stmt_insert = $pdo->prepare($insert_sql);
        $stmt_insert->execute($values);

        // Update the suggestion status
        $stmt_update = $pdo->prepare("UPDATE content_suggestions SET status = 'approved', reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt_update->execute([$reviewer_id, $suggestion_id]);

        $pdo->commit();
        return true;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error approving suggestion: " . $e->getMessage());
        return false;
    }
}

function reject_suggestion($suggestion_id, $reviewer_id) {
    if (!has_role('ulama')) return false;
    $pdo = db_connect();
    $stmt = $pdo->prepare("UPDATE content_suggestions SET status = 'rejected', reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP WHERE id = ? AND status = 'pending'");
    return $stmt->execute([$reviewer_id, $suggestion_id]);
}

function add_content($content_type, $data, $contributor_id) {
    if (!has_role('ulama')) return false; // Ulama and Admin can add directly
    $pdo = db_connect();
    $pdo->beginTransaction();
    try {
        $status = has_role('admin') ? 'approved' : get_site_setting('ulama_direct_publish', 'approved'); // Configurable Ulama publish

        $table = '';
        $columns = [];
        $values = [];

        switch ($content_type) {
            case 'translation':
                $table = 'translations';
                $columns = ['ayah_id', 'language', 'text', 'contributor_id', 'status', 'version', 'is_default'];
                // Check for existing versions for this ayah/language
                $stmt_version = $pdo->prepare("SELECT MAX(version) as max_version FROM translations WHERE ayah_id = ? AND language = ?");
                $stmt_version->execute([$data['ayah_id'], $data['language']]);
                $max_version = $stmt_version->fetchColumn() ?: 0;
                $new_version = $max_version + 1;

                $values = [$data['ayah_id'], $data['language'], $data['text'], $contributor_id, $status, $new_version, $data['is_default'] ?? FALSE];
                break;
            case 'tafsir':
                $table = 'tafasir';
                 $columns = ['ayah_id', 'title', 'text', 'contributor_id', 'status', 'version', 'is_default'];
                 // Check for existing versions for this ayah/title
                $stmt_version = $pdo->prepare("SELECT MAX(version) as max_version FROM tafasir WHERE ayah_id = ? AND title = ?");
                $stmt_version->execute([$data['ayah_id'], $data['title']]);
                $max_version = $stmt_version->fetchColumn() ?: 0;
                $new_version = $max_version + 1;

                $values = [$data['ayah_id'], $data['title'], $data['text'], $contributor_id, $status, $new_version, $data['is_default'] ?? FALSE];
                break;
            case 'word_meaning':
                $table = 'word_meanings';
                $columns = ['ayah_id', 'word_index', 'arabic_word', 'transliteration', 'meaning', 'grammar', 'contributor_id', 'status', 'version', 'is_default'];
                 // Check for existing versions for this ayah/word_index
                $stmt_version = $pdo->prepare("SELECT MAX(version) as max_version FROM word_meanings WHERE ayah_id = ? AND word_index = ?");
                $stmt_version->execute([$data['ayah_id'], $data['word_index']]);
                $max_version = $stmt_version->fetchColumn() ?: 0;
                $new_version = $max_version + 1;

                $values = [$data['ayah_id'], $data['word_index'], $data['arabic_word'], $data['transliteration'] ?? null, $data['meaning'], $data['grammar'] ?? null, $contributor_id, $status, $new_version, $data['is_default'] ?? FALSE];
                break;
            default:
                $pdo->rollBack();
                return false; // Invalid content type
        }

        // If setting as default, unset previous defaults for this ayah/type/language/title/word_index
        if ($data['is_default'] ?? FALSE) {
             $unset_sql = "UPDATE $table SET is_default = FALSE WHERE ayah_id = ?";
             $unset_params = [$data['ayah_id']];
             if ($content_type === 'translation') {
                 $unset_sql .= " AND language = ?";
                 $unset_params[] = $data['language'];
             } elseif ($content_type === 'tafsir') {
                  $unset_sql .= " AND title = ?";
                  $unset_params[] = $data['title'];
             } elseif ($content_type === 'word_meaning') {
                  $unset_sql .= " AND word_index = ?";
                  $unset_params[] = $data['word_index'];
             }
             $stmt_unset = $pdo->prepare($unset_sql);
             $stmt_unset->execute($unset_params);
        }


        $insert_sql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', array_fill(0, count($values), '?')) . ")";
        $stmt_insert = $pdo->prepare($insert_sql);
        $result = $stmt_insert->execute($values);

        $pdo->commit();
        return $result;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error adding content: " . $e->getMessage());
        return false;
    }
}

function update_content($content_type, $content_id, $data, $user_id) {
     // Only Admin can update existing approved content directly
    if (!has_role('admin')) return false;
    $pdo = db_connect();
    $pdo->beginTransaction();
    try {
        $table = '';
        $update_sql = '';
        $params = [];

        switch ($content_type) {
            case 'translation':
                $table = 'translations';
                $update_sql = "UPDATE translations SET language = ?, text = ?, is_default = ?, status = ? WHERE id = ?";
                $params = [$data['language'], $data['text'], $data['is_default'] ?? FALSE, $data['status'] ?? 'approved', $content_id];
                 // If setting as default, unset others
                if ($data['is_default'] ?? FALSE) {
                    $stmt_ayah = $pdo->prepare("SELECT ayah_id FROM translations WHERE id = ?");
                    $stmt_ayah->execute([$content_id]);
                    $ayah_id = $stmt_ayah->fetchColumn();
                    if ($ayah_id) {
                         $stmt_unset = $pdo->prepare("UPDATE translations SET is_default = FALSE WHERE ayah_id = ? AND language = ? AND id != ?");
                         $stmt_unset->execute([$ayah_id, $data['language'], $content_id]);
                    }
                }
                break;
            case 'tafsir':
                $table = 'tafasir';
                $update_sql = "UPDATE tafasir SET title = ?, text = ?, is_default = ?, status = ? WHERE id = ?";
                $params = [$data['title'], $data['text'], $data['is_default'] ?? FALSE, $data['status'] ?? 'approved', $content_id];
                 // If setting as default, unset others
                 if ($data['is_default'] ?? FALSE) {
                    $stmt_ayah = $pdo->prepare("SELECT ayah_id FROM tafasir WHERE id = ?");
                    $stmt_ayah->execute([$content_id]);
                    $ayah_id = $stmt_ayah->fetchColumn();
                    if ($ayah_id) {
                         $stmt_unset = $pdo->prepare("UPDATE tafasir SET is_default = FALSE WHERE ayah_id = ? AND title = ? AND id != ?");
                         $stmt_unset->execute([$ayah_id, $data['title'], $content_id]);
                    }
                }
                break;
            case 'word_meaning':
                $table = 'word_meanings';
                $update_sql = "UPDATE word_meanings SET arabic_word = ?, transliteration = ?, meaning = ?, grammar = ?, is_default = ?, status = ? WHERE id = ?";
                $params = [$data['arabic_word'], $data['transliteration'] ?? null, $data['meaning'], $data['grammar'] ?? null, $data['is_default'] ?? FALSE, $data['status'] ?? 'approved', $content_id];
                 // If setting as default, unset others
                 if ($data['is_default'] ?? FALSE) {
                    $stmt_ayah = $pdo->prepare("SELECT ayah_id, word_index FROM word_meanings WHERE id = ?");
                    $stmt_ayah->execute([$content_id]);
                    $wm_data = $stmt_ayah->fetch();
                    if ($wm_data) {
                         $stmt_unset = $pdo->prepare("UPDATE word_meanings SET is_default = FALSE WHERE ayah_id = ? AND word_index = ? AND id != ?");
                         $stmt_unset->execute([$wm_data['ayah_id'], $wm_data['word_index'], $content_id]);
                    }
                }
                break;
            default:
                $pdo->rollBack();
                return false; // Invalid content type
        }

        $stmt = $pdo->prepare($update_sql);
        $result = $stmt->execute($params);

        $pdo->commit();
        return $result;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error updating content: " . $e->getMessage());
        return false;
    }
}

function delete_content($content_type, $content_id, $user_id) {
    // Only Admin can delete content
    if (!has_role('admin')) return false;
    $pdo = db_connect();
    $table = '';
    switch ($content_type) {
        case 'translation': $table = 'translations'; break;
        case 'tafsir': $table = 'tafasir'; break;
        case 'word_meaning': $table = 'word_meanings'; break;
        default: return false; // Invalid content type
    }
    $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
    return $stmt->execute([$content_id]);
}


// --- Search Functionality ---
function search_quran($query, $surah_id = null, $juz = null) {
    $pdo = db_connect();
    $sql = "
        SELECT a.id, a.surah_id, a.ayah_number, a.arabic_text, a.urdu_translation, s.arabic_name, s.english_name
        FROM ayahs a
        JOIN surahs s ON a.surah_id = s.id
        WHERE a.arabic_text LIKE ? OR a.urdu_translation LIKE ?
    ";
    $params = ["%$query%", "%$query%"];

    if ($surah_id) {
        $sql .= " AND a.surah_id = ?";
        $params[] = $surah_id;
    }
    if ($juz) {
        $sql .= " AND a.juz = ?";
        $params[] = $juz;
    }

    $sql .= " ORDER BY a.surah_id, a.ayah_number";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function search_content($query, $content_type, $surah_id = null, $juz = null) {
    $pdo = db_connect();
    $table = '';
    $text_column = 'text';
    $join_sql = '';
    $where_sql = '';
    $params = ["%$query%"];

    switch ($content_type) {
        case 'translation':
            $table = 'translations';
            $join_sql = 'JOIN ayahs a ON t.ayah_id = a.id JOIN surahs s ON a.surah_id = s.id';
            $where_sql = 't.text LIKE ? AND t.status = "approved"';
            break;
        case 'tafsir':
            $table = 'tafasir';
            $join_sql = 'JOIN ayahs a ON tf.ayah_id = a.id JOIN surahs s ON a.surah_id = s.id';
            $where_sql = 'tf.text LIKE ? AND tf.status = "approved"';
            break;
        case 'word_meaning':
            $table = 'word_meanings';
            $text_column = 'meaning'; // Search in meaning
            $join_sql = 'JOIN ayahs a ON wm.ayah_id = a.id JOIN surahs s ON a.surah_id = s.id';
            $where_sql = 'wm.meaning LIKE ? AND wm.status = "approved"';
            break;
        case 'notes':
             if (!is_logged_in()) return []; // Notes are user-specific
             $table = 'notes';
             $text_column = 'note';
             $join_sql = 'JOIN ayahs a ON n.ayah_id = a.id JOIN surahs s ON a.surah_id = s.id';
             $where_sql = 'n.note LIKE ? AND n.user_id = ?';
             $params[] = get_user_id();
             break;
        default:
            return []; // Invalid content type
    }

    $sql = "
        SELECT c.*, a.id as ayah_id, a.surah_id, a.ayah_number, s.arabic_name, s.english_name
        FROM $table c
        $join_sql
        WHERE $where_sql
    ";

    if ($surah_id) {
        $sql .= " AND a.surah_id = ?";
        $params[] = $surah_id;
    }
    if ($juz) {
        $sql .= " AND a.juz = ?";
        $params[] = $juz;
    }

    $sql .= " ORDER BY a.surah_id, a.ayah_number";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}


// --- Admin Functions ---
function get_all_users() {
    if (!has_role('admin')) return [];
    $pdo = db_connect();
    $stmt = $pdo->query("SELECT id, username, email, role, created_at, last_login FROM users ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

function update_user_role($user_id, $role) {
    if (!has_role('admin')) return false;
    $pdo = db_connect();
    // Prevent changing the role of the primary admin (ID 1) or self
    if ($user_id == 1 && $user_id != get_user_id()) {
         // Allow changing role of ID 1 only if it's the current admin doing it
         // This is a simple protection, a more robust system might prevent changing ID 1's role at all
         return false;
    }
     if ($user_id == get_user_id() && $role != get_user_role()) {
         // Allow changing own role, but be careful with admin role removal
         // A real system might require re-authentication or prevent removing admin role from self
     }

    $valid_roles = ['public', 'user', 'ulama', 'admin'];
    if (!in_array($role, $valid_roles)) {
        return false;
    }

    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    return $stmt->execute([$role, $user_id]);
}

function delete_user($user_id) {
    if (!has_role('admin')) return false;
    $pdo = db_connect();
     // Prevent deleting the primary admin (ID 1) or self
    if ($user_id == 1 || $user_id == get_user_id()) {
         return false;
    }
    $pdo->beginTransaction();
    try {
        // Delete related data (bookmarks, notes, hifz, settings, suggestions, contributions)
        $stmt_del_bookmarks = $pdo->prepare("DELETE FROM bookmarks WHERE user_id = ?");
        $stmt_del_bookmarks->execute([$user_id]);

        $stmt_del_notes = $pdo->prepare("DELETE FROM notes WHERE user_id = ?");
        $stmt_del_notes->execute([$user_id]);

        $stmt_del_hifz = $pdo->prepare("DELETE FROM hifz_progress WHERE user_id = ?");
        $stmt_del_hifz->execute([$user_id]);

        $stmt_del_settings = $pdo->prepare("DELETE FROM user_settings WHERE user_id = ?");
        $stmt_del_settings->execute([$user_id]);

        $stmt_del_suggestions = $pdo->prepare("DELETE FROM content_suggestions WHERE user_id = ?");
        $stmt_del_suggestions->execute([$user_id]);

        // For contributed content (translations, tafasir, word_meanings),
        // we might want to keep the content but set contributor_id to NULL or a 'deleted user' ID,
        // or delete it if it's not approved. For simplicity here, we'll just set contributor_id to NULL
        $stmt_update_translations = $pdo->prepare("UPDATE translations SET contributor_id = NULL WHERE contributor_id = ?");
        $stmt_update_translations->execute([$user_id]);

        $stmt_update_tafasir = $pdo->prepare("UPDATE tafasir SET contributor_id = NULL WHERE contributor_id = ?");
        $stmt_update_tafasir->execute([$user_id]);

        $stmt_update_word_meanings = $pdo->prepare("UPDATE word_meanings SET contributor_id = NULL WHERE contributor_id = ?");
        $stmt_update_word_meanings->execute([$user_id]);


        // Finally, delete the user
        $stmt_del_user = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $result = $stmt_del_user->execute([$user_id]);

        $pdo->commit();
        return $result;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error deleting user: " . $e->getMessage());
        return false;
    }
}

function backup_database() {
    if (!has_role('admin')) return false;
    $backup_dir = __DIR__ . '/backups';
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0777, true);
    }
    $backup_file = $backup_dir . '/quran_backup_' . date('Ymd_His') . '.sqlite';

    // SQLite backup command
    $command = "sqlite3 " . escapeshellarg(DB_PATH) . " \".backup " . escapeshellarg($backup_file) . "\"";
    exec($command, $output, $return_var);

    if ($return_var === 0) {
        return $backup_file; // Return path to the backup file
    } else {
        error_log("Database backup failed. Command: $command. Output: " . implode("\n", $output));
        return false;
    }
}

function restore_database($backup_file) {
    if (!has_role('admin')) return false;
    if (!file_exists($backup_file)) {
        return false; // Backup file not found
    }

    // Ensure the backup file is within the backups directory for security
    $backup_dir = realpath(__DIR__ . '/backups');
    $backup_file_realpath = realpath($backup_file);
    if (strpos($backup_file_realpath, $backup_dir) !== 0) {
        error_log("Attempted to restore file outside backup directory: " . $backup_file);
        return false; // File is not in the backups directory
    }


    // SQLite restore command
    // IMPORTANT: This will overwrite the current database file!
    // Ensure proper permissions and handle potential data loss.
    // A safer approach might involve stopping the web server or using a temporary file.
    // For this single-file example, we'll use the direct restore command.
    $command = "sqlite3 " . escapeshellarg(DB_PATH) . " \".restore " . escapeshellarg(str_replace('\\', '/', $backup_file)) . "\""; // Use forward slashes for command
    exec($command, $output, $return_var);

    if ($return_var === 0) {
        return true;
    } else {
        error_log("Database restore failed. Command: $command. Output: " . implode("\n", $output));
        return false;
    }
}

function get_backup_files() {
    if (!has_role('admin')) return [];
    $backup_dir = __DIR__ . '/backups';
    if (!is_dir($backup_dir)) {
        return [];
    }
    $files = scandir($backup_dir);
    $backup_files = [];
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sqlite') {
            $backup_files[] = $file;
        }
    }
    rsort($backup_files); // Sort by date (newest first)
    return $backup_files;
}


// --- Helper Functions ---
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function get_surah_name($surah_number, $lang = 'english') {
    $pdo = db_connect();
    $column = ($lang === 'arabic') ? 'arabic_name' : 'english_name';
    $stmt = $pdo->prepare("SELECT $column FROM surahs WHERE surah_number = ?");
    $stmt->execute([$surah_number]);
    $result = $stmt->fetch();
    return $result ? $result[$column] : "Surah $surah_number";
}

function get_ayah_count($surah_number) {
     $pdo = db_connect();
    $stmt = $pdo->prepare("SELECT ayah_count FROM surahs WHERE surah_number = ?");
    $stmt->execute([$surah_number]);
    $result = $stmt->fetch();
    return $result ? $result['ayah_count'] : 0;
}

function get_ayah_id_by_surah_ayah($surah_number, $ayah_number) {
     $pdo = db_connect();
    $stmt = $pdo->prepare("
        SELECT a.id
        FROM ayahs a
        JOIN surahs s ON a.surah_id = s.id
        WHERE s.surah_number = ? AND a.ayah_number = ?
    ");
    $stmt->execute([$surah_number, $ayah_number]);
    $result = $stmt->fetch();
    return $result ? $result['id'] : null;
}

function get_ayah_details_by_id($ayah_id) {
     $pdo = db_connect();
    $stmt = $pdo->prepare("
        SELECT a.*, s.arabic_name, s.english_name, s.surah_number
        FROM ayahs a
        JOIN surahs s ON a.surah_id = s.id
        WHERE a.id = ?
    ");
    $stmt->execute([$ayah_id]);
    return $stmt->fetch();
}

function get_juz_list() {
    $pdo = db_connect();
    $stmt = $pdo->query("SELECT DISTINCT juz FROM ayahs ORDER BY juz");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function get_ayahs_by_juz($juz) {
    $pdo = db_connect();
    $stmt = $pdo->prepare("
        SELECT a.*, s.arabic_name, s.english_name, s.surah_number
        FROM ayahs a
        JOIN surahs s ON a.surah_id = s.id
        WHERE a.juz = ? ORDER BY a.surah_id, a.ayah_number
    ");
    $stmt->execute([$juz]);
    return $stmt->fetchAll();
}

// --- Routing and Request Handling ---
$action = $_GET['action'] ?? 'home';
$message = '';
$error = '';

// Initialize database if it doesn't exist
if (!file_exists(DB_PATH)) {
    if (!is_dir(dirname(DB_PATH))) {
        mkdir(dirname(DB_PATH), 0777, true);
    }
    db_init();
    $message = "Database initialized and Quran data imported.";
} else {
     // Ensure Surahs and Ayahs are populated if DB exists but is empty (e.g., after manual deletion)
     $pdo = db_connect();
     $stmt = $pdo->query("SELECT COUNT(*) FROM surahs");
     if ($stmt->fetchColumn() == 0) {
         db_init(); // Re-run init to populate surahs and ayahs
         $message = "Database structure verified and data populated.";
     }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf_token)) {
        $error = "Invalid CSRF token.";
        // Stay on the current page or redirect to an error page
        // For simplicity, we'll just set the error and let the current action render
    } else {
        switch ($_POST['action'] ?? '') {
            case 'login':
                $username = sanitize_input($_POST['username'] ?? '');
                $password = $_POST['password'] ?? ''; // Don't sanitize password before hashing/verification
                if (login($username, $password)) {
                    redirect('?action=dashboard');
                } else {
                    $error = "Invalid username or password.";
                    $action = 'login'; // Stay on login page with error
                }
                break;
            case 'register':
                $username = sanitize_input($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

                if (empty($username) || empty($password) || empty($confirm_password) || empty($email)) {
                    $error = "All fields are required.";
                } elseif ($password !== $confirm_password) {
                    $error = "Passwords do not match.";
                } elseif (strlen($password) < 8) {
                    $error = "Password must be at least 8 characters long.";
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                     $error = "Invalid email format.";
                } else {
                    if (register($username, $password, $email)) {
                        $message = "Registration successful. You can now log in.";
                        $action = 'login'; // Redirect to login page
                    } else {
                        $error = "Username or email already exists.";
                        $action = 'register'; // Stay on register page with error
                    }
                }
                break;
            case 'add_bookmark':
                if (has_role('user')) {
                    $ayah_id = (int)($_POST['ayah_id'] ?? 0);
                    if ($ayah_id > 0) {
                        if (add_bookmark(get_user_id(), $ayah_id)) {
                            $message = "Ayah bookmarked.";
                        } else {
                            $error = "Failed to add bookmark or already exists.";
                        }
                    } else {
                         $error = "Invalid Ayah ID.";
                    }
                } else {
                    $error = "You must be logged in to bookmark.";
                }
                 // Redirect back to the page they were on, or a default view
                 $redirect_url = $_SERVER['HTTP_REFERER'] ?? '?action=home';
                 redirect($redirect_url);
                break;
            case 'remove_bookmark':
                 if (has_role('user')) {
                    $ayah_id = (int)($_POST['ayah_id'] ?? 0);
                    if ($ayah_id > 0) {
                        if (remove_bookmark(get_user_id(), $ayah_id)) {
                            $message = "Bookmark removed.";
                        } else {
                            $error = "Failed to remove bookmark.";
                        }
                    } else {
                         $error = "Invalid Ayah ID.";
                    }
                } else {
                    $error = "You must be logged in to manage bookmarks.";
                }
                 // Redirect back to the page they were on, or a default view
                 $redirect_url = $_SERVER['HTTP_REFERER'] ?? '?action=home';
                 redirect($redirect_url);
                break;
            case 'add_note':
                 if (has_role('user')) {
                    $ayah_id = (int)($_POST['ayah_id'] ?? 0);
                    $note = sanitize_input($_POST['note'] ?? '');
                    if ($ayah_id > 0 && !empty($note)) {
                        if (add_note(get_user_id(), $ayah_id, $note)) {
                            $message = "Note added.";
                        } else {
                            $error = "Failed to add note.";
                        }
                    } else {
                         $error = "Invalid Ayah ID or empty note.";
                    }
                } else {
                    $error = "You must be logged in to add notes.";
                }
                 // Redirect back to the page they were on, or a default view
                 $redirect_url = $_SERVER['HTTP_REFERER'] ?? '?action=home';
                 redirect($redirect_url);
                break;
            case 'update_note':
                 if (has_role('user')) {
                    $note_id = (int)($_POST['note_id'] ?? 0);
                    $note = sanitize_input($_POST['note'] ?? '');
                    if ($note_id > 0 && !empty($note)) {
                        if (update_note($note_id, get_user_id(), $note)) {
                            $message = "Note updated.";
                        } else {
                            $error = "Failed to update note or you don't own it.";
                        }
                    } else {
                         $error = "Invalid Note ID or empty note.";
                    }
                } else {
                    $error = "You must be logged in to update notes.";
                }
                 // Redirect back to the page they were on, or a default view
                 $redirect_url = $_SERVER['HTTP_REFERER'] ?? '?action=dashboard&view=notes';
                 redirect($redirect_url);
                break;
            case 'delete_note':
                 if (has_role('user')) {
                    $note_id = (int)($_POST['note_id'] ?? 0);
                    if ($note_id > 0) {
                        if (delete_note($note_id, get_user_id())) {
                            $message = "Note deleted.";
                        } else {
                            $error = "Failed to delete note or you don't own it.";
                        }
                    } else {
                         $error = "Invalid Note ID.";
                    }
                } else {
                    $error = "You must be logged in to delete notes.";
                }
                 // Redirect back to the page they were on, or a default view
                 $redirect_url = $_SERVER['HTTP_REFERER'] ?? '?action=dashboard&view=notes';
                 redirect($redirect_url);
                break;
            case 'update_hifz_progress':
                 if (has_role('user')) {
                    $surah_id = (int)($_POST['surah_id'] ?? 0);
                    $ayah_id = (int)($_POST['ayah_id'] ?? 0);
                    if ($surah_id > 0 && $ayah_id > 0) {
                        if (update_hifz_progress(get_user_id(), $surah_id, $ayah_id)) {
                            $message = "Hifz progress updated.";
                        } else {
                            $error = "Failed to update Hifz progress.";
                        }
                    } else {
                         $error = "Invalid Surah or Ayah ID.";
                    }
                } else {
                    $error = "You must be logged in to update Hifz progress.";
                }
                 // Redirect back to the page they were on, or a default view
                 $redirect_url = $_SERVER['HTTP_REFERER'] ?? '?action=dashboard&view=hifz';
                 redirect($redirect_url);
                break;
            case 'suggest_content':
                 if (has_role('user')) {
                    $content_type = sanitize_input($_POST['content_type'] ?? '');
                    $ayah_id = (int)($_POST['ayah_id'] ?? 0);
                    $suggested_text = sanitize_input($_POST['suggested_text'] ?? '');
                    $word_index = isset($_POST['word_index']) ? (int)$_POST['word_index'] : null; // Optional for word_meaning

                    if ($ayah_id > 0 && !empty($suggested_text) && in_array($content_type, ['translation', 'tafsir', 'word_meaning'])) {
                        if (suggest_content(get_user_id(), $content_type, $ayah_id, $suggested_text, $word_index)) {
                            $message = "Suggestion submitted for review.";
                        } else {
                            $error = "Failed to submit suggestion.";
                        }
                    } else {
                         $error = "Invalid input for suggestion.";
                    }
                } else {
                    $error = "You must be logged in to suggest content.";
                }
                 // Redirect back to the page they were on, or a default view
                 $redirect_url = $_SERVER['HTTP_REFERER'] ?? '?action=home';
                 redirect($redirect_url);
                break;
            case 'approve_suggestion':
                 if (has_role('ulama')) {
                    $suggestion_id = (int)($_POST['suggestion_id'] ?? 0);
                    if ($suggestion_id > 0) {
                        if (approve_suggestion($suggestion_id, get_user_id())) {
                            $message = "Suggestion approved and added to content.";
                        } else {
                            $error = "Failed to approve suggestion.";
                        }
                    } else {
                         $error = "Invalid suggestion ID.";
                    }
                } else {
                    $error = "You do not have permission to approve suggestions.";
                }
                 redirect('?action=admin&view=suggestions');
                break;
            case 'reject_suggestion':
                 if (has_role('ulama')) {
                    $suggestion_id = (int)($_POST['suggestion_id'] ?? 0);
                    if ($suggestion_id > 0) {
                        if (reject_suggestion($suggestion_id, get_user_id())) {
                            $message = "Suggestion rejected.";
                        } else {
                            $error = "Failed to reject suggestion.";
                        }
                    } else {
                         $error = "Invalid suggestion ID.";
                    }
                } else {
                    $error = "You do not have permission to reject suggestions.";
                }
                 redirect('?action=admin&view=suggestions');
                break;
            case 'add_content':
                 if (has_role('ulama')) {
                    $content_type = sanitize_input($_POST['content_type'] ?? '');
                    $ayah_id = (int)($_POST['ayah_id'] ?? 0);
                    $data = $_POST; // Pass the whole POST array, sanitize inside add_content if needed
                    $data['ayah_id'] = $ayah_id; // Ensure ayah_id is in data

                    if ($ayah_id > 0 && in_array($content_type, ['translation', 'tafsir', 'word_meaning'])) {
                        if (add_content($content_type, $data, get_user_id())) {
                            $message = ucfirst($content_type) . " added successfully.";
                        } else {
                            $error = "Failed to add " . $content_type . ".";
                        }
                    } else {
                         $error = "Invalid input for adding content.";
                    }
                } else {
                    $error = "You do not have permission to add content.";
                }
                 // Redirect back to admin content management or the ayah page
                 $redirect_url = $_SERVER['HTTP_REFERER'] ?? '?action=admin&view=content';
                 redirect($redirect_url);
                break;
            case 'update_content':
                 if (has_role('admin')) { // Only admin can update approved content
                    $content_type = sanitize_input($_POST['content_type'] ?? '');
                    $content_id = (int)($_POST['content_id'] ?? 0);
                    $data = $_POST; // Pass the whole POST array

                    if ($content_id > 0 && in_array($content_type, ['translation', 'tafsir', 'word_meaning'])) {
                        if (update_content($content_type, $content_id, $data, get_user_id())) {
                            $message = ucfirst($content_type) . " updated successfully.";
                        } else {
                            $error = "Failed to update " . $content_type . ".";
                        }
                    } else {
                         $error = "Invalid input for updating content.";
                    }
                } else {
                    $error = "You do not have permission to update content.";
                }
                 // Redirect back to admin content management or the ayah page
                 $redirect_url = $_SERVER['HTTP_REFERER'] ?? '?action=admin&view=content';
                 redirect($redirect_url);
                break;
            case 'delete_content':
                 if (has_role('admin')) { // Only admin can delete content
                    $content_type = sanitize_input($_POST['content_type'] ?? '');
                    $content_id = (int)($_POST['content_id'] ?? 0);

                    if ($content_id > 0 && in_array($content_type, ['translation', 'tafsir', 'word_meaning'])) {
                        if (delete_content($content_type, $content_id, get_user_id())) {
                            $message = ucfirst($content_type) . " deleted successfully.";
                        } else {
                            $error = "Failed to delete " . $content_type . ".";
                        }
                    } else {
                         $error = "Invalid input for deleting content.";
                    }
                } else {
                    $error = "You do not have permission to delete content.";
                }
                 // Redirect back to admin content management or the ayah page
                 $redirect_url = $_SERVER['HTTP_REFERER'] ?? '?action=admin&view=content';
                 redirect($redirect_url);
                break;
            case 'update_user_role':
                 if (has_role('admin')) {
                    $user_id = (int)($_POST['user_id'] ?? 0);
                    $role = sanitize_input($_POST['role'] ?? '');
                    if ($user_id > 0 && !empty($role)) {
                        if (update_user_role($user_id, $role)) {
                            $message = "User role updated.";
                        } else {
                            $error = "Failed to update user role. Cannot change primary admin role or invalid role.";
                        }
                    } else {
                         $error = "Invalid user ID or role.";
                    }
                } else {
                    $error = "You do not have permission to manage users.";
                }
                 redirect('?action=admin&view=users');
                break;
            case 'delete_user':
                 if (has_role('admin')) {
                    $user_id = (int)($_POST['user_id'] ?? 0);
                    if ($user_id > 0) {
                        if (delete_user($user_id)) {
                            $message = "User deleted.";
                        } else {
                            $error = "Failed to delete user. Cannot delete primary admin or yourself.";
                        }
                    } else {
                         $error = "Invalid user ID.";
                    }
                } else {
                    $error = "You do not have permission to manage users.";
                }
                 redirect('?action=admin&view=users');
                break;
            case 'backup_db':
                 if (has_role('admin')) {
                    $backup_file = backup_database();
                    if ($backup_file) {
                        $message = "Database backed up successfully to: " . basename($backup_file);
                    } else {
                        $error = "Database backup failed.";
                    }
                } else {
                    $error = "You do not have permission to backup the database.";
                }
                 redirect('?action=admin&view=database');
                break;
            case 'restore_db':
                 if (has_role('admin')) {
                    $backup_file = sanitize_input($_POST['backup_file'] ?? '');
                    if (!empty($backup_file)) {
                         $full_backup_path = __DIR__ . '/backups/' . $backup_file;
                        if (restore_database($full_backup_path)) {
                            $message = "Database restored successfully from: " . $backup_file;
                        } else {
                            $error = "Database restore failed. File not found or permission issue.";
                        }
                    } else {
                         $error = "No backup file selected.";
                    }
                } else {
                    $error = "You do not have permission to restore the database.";
                }
                 redirect('?action=admin&view=database');
                break;
            case 'set_site_setting':
                 if (has_role('admin')) {
                    $key = sanitize_input($_POST['setting_key'] ?? '');
                    $value = sanitize_input($_POST['setting_value'] ?? ''); // Value might need different sanitization based on type
                    if (!empty($key)) {
                        if (set_site_setting($key, $value)) {
                            $message = "Setting updated.";
                        } else {
                            $error = "Failed to update setting.";
                        }
                    } else {
                         $error = "Setting key cannot be empty.";
                    }
                } else {
                    $error = "You do not have permission to change site settings.";
                }
                 redirect('?action=admin&view=settings');
                break;
            case 'set_user_setting':
                 if (has_role('user')) {
                    $key = sanitize_input($_POST['setting_key'] ?? '');
                    $value = sanitize_input($_POST['setting_value'] ?? ''); // Value might need different sanitization based on type
                    if (!empty($key)) {
                        if (set_user_setting(get_user_id(), $key, $value)) {
                            $message = "Setting updated.";
                        } else {
                            $error = "Failed to update setting.";
                        }
                    } else {
                         $error = "Setting key cannot be empty.";
                    }
                } else {
                    $error = "You must be logged in to change settings.";
                }
                 redirect('?action=dashboard&view=settings');
                break;
            // Add other POST actions here (e.g., update profile, etc.)
        }
    }
}

// --- View Rendering ---
function render_header($title = SITE_NAME) {
    $user_role = get_user_role();
    $username = $_SESSION['username'] ?? 'Guest';
    $csrf_token = generate_csrf_token(); // Generate token for forms
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?> - <?php echo SITE_NAME; ?></title>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&display=swap">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            :root {
                --primary-color: #007bff;
                --secondary-color: #6c757d;
                --success-color: #28a745;
                --danger-color: #dc3545;
                --warning-color: #ffc107;
                --info-color: #17a2b8;
                --light-color: #f8f9fa;
                --dark-color: #343a40;
                --background-color: #ffffff;
                --text-color: #212529;
                --border-color: #dee2e6;
                --link-color: #007bff;
                --link-hover-color: #0056b3;
            }
            body {
                font-family: sans-serif;
                line-height: 1.6;
                margin: 0;
                padding: 0;
                background-color: var(--background-color);
                color: var(--text-color);
                direction: ltr; /* Default direction */
            }
             body.rtl {
                 direction: rtl;
             }
            .arabic-text {
                font-family: 'Amiri', serif;
                font-size: 1.8em;
                line-height: 2.5;
                text-align: right;
                direction: rtl;
            }
             .urdu-text {
                 font-family: 'Noto Nastaliq Urdu', serif;
                 font-size: 1.2em;
                 line-height: 2;
                 text-align: right;
                 direction: rtl;
             }
            header {
                background-color: var(--primary-color);
                color: white;
                padding: 1rem 0;
                text-align: center;
                position: relative;
            }
            header h1 {
                margin: 0;
                font-size: 2em;
            }
            nav {
                margin-top: 10px;
            }
            nav a {
                color: white;
                text-decoration: none;
                margin: 0 15px;
                font-size: 1.1em;
            }
            nav a:hover {
                text-decoration: underline;
            }
            .container {
                max-width: 1200px;
                margin: 20px auto;
                padding: 0 20px;
            }
            .message, .error {
                padding: 10px;
                margin-bottom: 15px;
                border-radius: 5px;
            }
            .message {
                background-color: var(--success-color);
                color: white;
            }
            .error {
                background-color: var(--danger-color);
                color: white;
            }
            .card {
                background-color: var(--light-color);
                border: 1px solid var(--border-color);
                border-radius: 5px;
                padding: 15px;
                margin-bottom: 15px;
            }
            .card h2 {
                margin-top: 0;
            }
            form label {
                display: block;
                margin-bottom: 5px;
                font-weight: bold;
            }
            form input[type="text"],
            form input[type="email"],
            form input[type="password"],
            form textarea,
            form select {
                width: 100%;
                padding: 8px;
                margin-bottom: 10px;
                border: 1px solid var(--border-color);
                border-radius: 4px;
                box-sizing: border-box; /* Include padding and border in element's total width and height */
            }
             form button, .btn {
                 background-color: var(--primary-color);
                 color: white;
                 padding: 10px 15px;
                 border: none;
                 border-radius: 4px;
                 cursor: pointer;
                 font-size: 1em;
                 text-decoration: none;
                 display: inline-block;
                 margin-right: 5px;
             }
             form button:hover, .btn:hover {
                 background-color: var(--link-hover-color);
             }
             .btn-danger {
                 background-color: var(--danger-color);
             }
             .btn-danger:hover {
                 background-color: #c82333;
             }
             .btn-secondary {
                 background-color: var(--secondary-color);
             }
             .btn-secondary:hover {
                 background-color: #5a6268;
             }

            .surah-list {
                list-style: none;
                padding: 0;
            }
            .surah-list li {
                margin-bottom: 10px;
                border-bottom: 1px solid var(--border-color);
                padding-bottom: 10px;
            }
            .surah-list li a {
                text-decoration: none;
                color: var(--text-color);
                font-size: 1.1em;
            }
            .surah-list li a:hover {
                color: var(--link-hover-color);
            }
            .ayah {
                margin-bottom: 20px;
                padding: 15px;
                border: 1px solid var(--border-color);
                border-radius: 5px;
                background-color: var(--light-color);
            }
            .ayah .ayah-number {
                font-weight: bold;
                margin-bottom: 10px;
                display: block;
                text-align: right;
            }
            .ayah .arabic-text {
                 font-size: 1.8em; /* Adjust as needed */
                 line-height: 2.5;
                 text-align: right;
                 direction: rtl;
                 margin-bottom: 10px;
            }
             .ayah .translation-text {
                 font-family: 'Noto Nastaliq Urdu', serif;
                 font-size: 1.2em; /* Adjust as needed */
                 line-height: 2;
                 text-align: right;
                 direction: rtl;
                 color: var(--secondary-color);
             }
             .ayah .tafsir-text {
                 font-size: 1em;
                 line-height: 1.8;
                 margin-top: 10px;
                 border-top: 1px dashed var(--border-color);
                 padding-top: 10px;
             }
             .ayah .word-meaning {
                 display: inline-block;
                 position: relative;
                 cursor: pointer;
                 margin: 0 2px;
                 border-bottom: 1px dashed var(--secondary-color);
             }
             .ayah .word-meaning:hover .word-tooltip {
                 visibility: visible;
                 opacity: 1;
             }
             .ayah .word-tooltip {
                 visibility: hidden;
                 background-color: var(--dark-color);
                 color: white;
                 text-align: center;
                 border-radius: 6px;
                 padding: 5px 10px;
                 position: absolute;
                 z-index: 1;
                 bottom: 125%; /* Position the tooltip above the text */
                 left: 50%;
                 margin-left: -60px; /* Center the tooltip */
                 opacity: 0;
                 transition: opacity 0.3s;
                 width: 120px; /* Adjust width as needed */
                 direction: ltr; /* Tooltip text direction */
                 font-family: sans-serif;
                 font-size: 0.9em;
             }
             .ayah .word-tooltip::after {
                 content: "";
                 position: absolute;
                 top: 100%; /* At the bottom of the tooltip */
                 left: 50%;
                 margin-left: -5px;
                 border-width: 5px;
                 border-style: solid;
                 border-color: var(--dark-color) transparent transparent transparent;
             }

             .ayah .ayah-actions {
                 margin-top: 10px;
                 text-align: left; /* Adjust based on RTL/LTR */
             }
             body.rtl .ayah .ayah-actions {
                 text-align: right;
             }
             .ayah .ayah-actions button,
             .ayah .ayah-actions a {
                 background: none;
                 border: none;
                 cursor: pointer;
                 color: var(--secondary-color);
                 margin-right: 10px;
                 font-size: 1em;
             }
             .ayah .ayah-actions button:hover,
             .ayah .ayah-actions a:hover {
                 color: var(--primary-color);
             }

             /* Tilawat Mode Styles */
             body.tilawat-mode {
                 background-color: black;
                 color: white;
             }
             body.tilawat-mode .container {
                 max-width: 900px; /* Wider for reading */
             }
             body.tilawat-mode header,
             body.tilawat-mode nav,
             body.tilawat-mode footer,
             body.tilawat-mode .message,
             body.tilawat-mode .error,
             body.tilawat-mode .card,
             body.tilawat-mode .ayah-actions,
             body.tilawat-mode .search-form,
             body.tilawat-mode .admin-panel,
             body.tilawat-mode .dashboard-panel {
                 display: none; /* Hide main UI elements */
             }
             body.tilawat-mode .ayah {
                 background-color: transparent;
                 border: none;
                 padding: 0;
                 margin-bottom: 30px;
             }
             body.tilawat-mode .ayah .ayah-number {
                 color: rgba(255, 255, 255, 0.5);
                 font-size: 0.9em;
             }
             body.tilawat-mode .ayah .arabic-text {
                 color: white;
                 font-size: 2.5em; /* Larger font */
                 line-height: 2.8;
             }
             body.tilawat-mode .ayah .translation-text {
                 color: rgba(255, 255, 255, 0.7);
                 font-size: 1.3em;
                 margin-top: 15px;
             }
             body.tilawat-mode .word-meaning {
                 border-bottom-color: rgba(255, 255, 255, 0.5);
             }
             body.tilawat-mode .word-tooltip {
                 background-color: rgba(0, 0, 0, 0.8);
                 color: white;
             }
             body.tilawat-mode .word-tooltip::after {
                 border-color: rgba(0, 0, 0, 0.8) transparent transparent transparent;
             }

             .tilawat-controls {
                 position: fixed;
                 top: 20px;
                 right: 20px;
                 background-color: rgba(0, 0, 0, 0.7);
                 color: white;
                 padding: 10px;
                 border-radius: 5px;
                 z-index: 1000;
                 display: none; /* Hidden by default */
             }
             body.tilawat-mode .tilawat-controls {
                 display: block; /* Show in tilawat mode */
             }
             .tilawat-controls label,
             .tilawat-controls input,
             .tilawat-controls select,
             .tilawat-controls button {
                 margin-bottom: 5px;
                 display: block;
                 width: 100%;
             }
             .tilawat-controls button {
                 background-color: var(--primary-color);
                 color: white;
                 border: none;
                 padding: 5px;
                 cursor: pointer;
             }
             .tilawat-controls button:hover {
                 background-color: var(--link-hover-color);
             }
             .tilawat-controls input[type="range"] {
                 width: calc(100% - 10px);
             }

             .audio-player {
                 margin-top: 15px;
                 text-align: center;
             }
             .audio-player audio {
                 width: 100%;
             }
             .audio-player button {
                 background: none;
                 border: none;
                 cursor: pointer;
                 font-size: 1.5em;
                 color: var(--primary-color);
                 margin: 0 5px;
             }
             .audio-player button:hover {
                 color: var(--link-hover-color);
             }
             .ayah.playing {
                 background-color: rgba(0, 123, 255, 0.1); /* Highlight playing ayah */
             }
             body.tilawat-mode .ayah.playing {
                 background-color: rgba(0, 123, 255, 0.2);
             }

             /* Admin/Dashboard Styles */
             .admin-panel, .dashboard-panel {
                 display: grid;
                 grid-template-columns: 200px 1fr;
                 gap: 20px;
             }
             .admin-sidebar, .dashboard-sidebar {
                 background-color: var(--light-color);
                 padding: 15px;
                 border-radius: 5px;
             }
             .admin-sidebar ul, .dashboard-sidebar ul {
                 list-style: none;
                 padding: 0;
             }
             .admin-sidebar li, .dashboard-sidebar li {
                 margin-bottom: 10px;
             }
             .admin-sidebar a, .dashboard-sidebar a {
                 text-decoration: none;
                 color: var(--text-color);
                 display: block;
                 padding: 5px;
             }
             .admin-sidebar a:hover, .dashboard-sidebar a:hover {
                 background-color: var(--border-color);
             }
             .admin-content, .dashboard-content {
                 background-color: var(--light-color);
                 padding: 15px;
                 border-radius: 5px;
             }
             table {
                 width: 100%;
                 border-collapse: collapse;
                 margin-bottom: 15px;
             }
             th, td {
                 border: 1px solid var(--border-color);
                 padding: 8px;
                 text-align: left;
             }
             th {
                 background-color: var(--secondary-color);
                 color: white;
             }
             tr:nth-child(even) {
                 background-color: #f2f2f2;
             }
             .form-group {
                 margin-bottom: 15px;
             }
             .form-group label {
                 display: block;
                 margin-bottom: 5px;
                 font-weight: bold;
             }
             .form-group input[type="text"],
             .form-group input[type="email"],
             .form-group input[type="password"],
             .form-group textarea,
             .form-group select {
                 width: calc(100% - 18px); /* Adjust for padding and border */
                 padding: 8px;
                 border: 1px solid var(--border-color);
                 border-radius: 4px;
             }
             .form-actions {
                 margin-top: 15px;
             }

             /* Responsive Design */
             @media (max-width: 768px) {
                 .admin-panel, .dashboard-panel {
                     grid-template-columns: 1fr;
                 }
                 .admin-sidebar, .dashboard-sidebar {
                     margin-bottom: 20px;
                 }
                 nav a {
                     margin: 0 8px;
                 }
                 .ayah .arabic-text {
                     font-size: 1.5em;
                 }
                 .ayah .translation-text {
                     font-size: 1em;
                 }
                 body.tilawat-mode .ayah .arabic-text {
                     font-size: 2em;
                 }
                 body.tilawat-mode .ayah .translation-text {
                     font-size: 1.1em;
                 }
             }

        </style>
         <script>
             // Client-side JS for Tilawat Mode, Audio, etc.
             document.addEventListener('DOMContentLoaded', () => {
                 const body = document.body;
                 const tilawatToggle = document.getElementById('tilawat-toggle');
                 const tilawatControls = document.querySelector('.tilawat-controls');
                 const fontSizeSlider = document.getElementById('font-size-slider');
                 const linesPerPageInput = document.getElementById('lines-per-page');
                 const viewModeSelect = document.getElementById('view-mode');
                 const audioPlayer = document.getElementById('audio-player');
                 const playPauseBtn = document.getElementById('play-pause-btn');
                 const nextAyahBtn = document.getElementById('next-ayah-btn');
                 const prevAyahBtn = document.getElementById('prev-ayah-btn');
                 const reciterSelect = document.getElementById('reciter-select');

                 let currentAyahElement = null;
                 let currentAyahId = null;
                 let isPlaying = false;
                 let audioBaseUrl = '<?php echo QURAN_AUDIO_BASE_URL; ?>'; // Default
                 let currentReciter = '<?php echo DEFAULT_RECITER; ?>';

                 // Load user settings on page load (from localStorage for public, PHP renders for logged in)
                 loadUserSettings();

                 if (tilawatToggle) {
                     tilawatToggle.addEventListener('click', () => {
                         body.classList.toggle('tilawat-mode');
                         // Save tilawat mode state
                         saveUserSetting('tilawat_mode', body.classList.contains('tilawat-mode') ? 'on' : 'off');
                         // Hide/Show controls
                         if (body.classList.contains('tilawat-mode')) {
                             tilawatControls.style.display = 'block';
                             // Scroll to last read position if available
                             const lastReadAyahId = localStorage.getItem('last_read_ayah_id');
                             if (lastReadAyahId) {
                                 const ayahElement = document.querySelector(`.ayah[data-ayah-id="${lastReadAyahId}"]`);
                                 if (ayahElement) {
                                     ayahElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                 }
                             }
                         } else {
                             tilawatControls.style.display = 'none';
                         }
                     });
                 }

                 if (fontSizeSlider) {
                     fontSizeSlider.addEventListener('input', (e) => {
                         const fontSize = e.target.value + 'em';
                         document.querySelectorAll('.ayah .arabic-text').forEach(el => {
                             el.style.fontSize = fontSize;
                         });
                         saveUserSetting('arabic_font_size', e.target.value);
                     });
                 }

                 if (linesPerPageInput) {
                     linesPerPageInput.addEventListener('input', (e) => {
                         // This would require more complex JS to dynamically paginate
                         // For now, just save the setting. Actual pagination needs server-side or more complex JS.
                         saveUserSetting('lines_per_page', e.target.value);
                     });
                 }

                 if (viewModeSelect) {
                     viewModeSelect.addEventListener('change', (e) => {
                         // This would require JS to switch between paginated/continuous
                         // For now, just save the setting.
                         saveUserSetting('reading_view_mode', e.target.value);
                         // Reload the page to apply view mode change (simpler without AJAX)
                         window.location.reload();
                     });
                 }

                 // Audio Playback
                 function formatAyahNumber(num) {
                     return String(num).padStart(3, '0');
                 }

                 function getAudioUrl(surahNumber, ayahNumber, reciter) {
                     const surahPadded = formatAyahNumber(surahNumber);
                     const ayahPadded = formatAyahNumber(ayahNumber);
                     // Example format: 001001.mp3
                     return `${audioBaseUrl}${reciter}/${surahPadded}${ayahPadded}.mp3`;
                 }

                 function playAyah(ayahElement) {
                     if (!audioPlayer) return;

                     const surahNumber = ayahElement.dataset.surahNumber;
                     const ayahNumber = ayahElement.dataset.ayahNumber;
                     const ayahId = ayahElement.dataset.ayahId;

                     if (!surahNumber || !ayahNumber || !ayahId) return;

                     const audioUrl = getAudioUrl(surahNumber, ayahNumber, currentReciter);

                     // Remove 'playing' class from previous ayah
                     if (currentAyahElement) {
                         currentAyahElement.classList.remove('playing');
                     }

                     // Set current ayah and add 'playing' class
                     currentAyahElement = ayahElement;
                     currentAyahId = ayahId;
                     currentAyahElement.classList.add('playing');

                     audioPlayer.src = audioUrl;
                     audioPlayer.play().then(() => {
                         isPlaying = true;
                         if (playPauseBtn) playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
                     }).catch(error => {
                         console.error("Audio playback failed:", error);
                         isPlaying = false;
                         if (playPauseBtn) playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
                         // Try playing the next ayah if playback fails (e.g., file not found)
                         playNextAyah();
                     });

                     // Scroll ayah into view if not visible
                     ayahElement.scrollIntoView({ behavior: 'smooth', block: 'center' });

                     // Save last read position (optional, could be separate from audio)
                     localStorage.setItem('last_read_ayah_id', ayahId);
                 }

                 function pauseAyah() {
                     if (audioPlayer) {
                         audioPlayer.pause();
                         isPlaying = false;
                         if (playPauseBtn) playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
                     }
                 }

                 function togglePlayPause() {
                     if (isPlaying) {
                         pauseAyah();
                     } else {
                         if (audioPlayer.src && audioPlayer.src !== window.location.href) { // Check if a source is set
                             audioPlayer.play().then(() => {
                                 isPlaying = true;
                                 if (playPauseBtn) playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
                             }).catch(error => {
                                 console.error("Audio playback failed:", error);
                                 isPlaying = false;
                                 if (playPauseBtn) playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
                             });
                         } else if (currentAyahElement) {
                             // If no source is set but an ayah is selected, play that ayah
                             playAyah(currentAyahElement);
                         } else {
                             // If no source and no ayah selected, start from the first ayah on the page
                             const firstAyah = document.querySelector('.ayah');
                             if (firstAyah) {
                                 playAyah(firstAyah);
                             }
                         }
                     }
                 }

                 function playNextAyah() {
                     if (!currentAyahElement) {
                         const firstAyah = document.querySelector('.ayah');
                         if (firstAyah) {
                             playAyah(firstAyah);
                         }
                         return;
                     }
                     const nextAyah = currentAyahElement.nextElementSibling;
                     if (nextAyah && nextAyah.classList.contains('ayah')) {
                         playAyah(nextAyah);
                     } else {
                         // End of Surah or page, handle accordingly (e.g., go to next Surah)
                         console.log("End of current section.");
                         pauseAyah(); // Stop playback at the end
                         // Optional: Logic to load next surah/page and play the first ayah
                     }
                 }

                 function playPrevAyah() {
                      if (!currentAyahElement) {
                         const firstAyah = document.querySelector('.ayah');
                         if (firstAyah) {
                             playAyah(firstAyah);
                         }
                         return;
                     }
                     const prevAyah = currentAyahElement.previousElementSibling;
                     if (prevAyah && prevAyah.classList.contains('ayah')) {
                         playAyah(prevAyah);
                     } else {
                         console.log("Beginning of current section.");
                         pauseAyah(); // Stop playback at the beginning
                         // Optional: Logic to load previous surah/page and play the last ayah
                     }
                 }

                 if (playPauseBtn) playPauseBtn.addEventListener('click', togglePlayPause);
                 if (nextAyahBtn) nextAyahBtn.addEventListener('click', playNextAyah);
                 if (prevAyahBtn) prevAyahBtn.addEventListener('click', playPrevAyah);

                 if (audioPlayer) {
                     audioPlayer.addEventListener('ended', () => {
                         // Auto-play next ayah
                         playNextAyah();
                     });
                     audioPlayer.addEventListener('error', (e) => {
                         console.error("Audio error:", e);
                         isPlaying = false;
                         if (playPauseBtn) playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
                         // Try playing the next ayah if there's an error
                         playNextAyah();
                     });
                 }

                 // Click on an ayah to play it
                 document.querySelectorAll('.ayah').forEach(ayahElement => {
                     ayahElement.addEventListener('click', (e) => {
                         // Prevent playing if clicking on interactive elements inside ayah
                         if (e.target.closest('.ayah-actions') || e.target.closest('.word-meaning')) {
                             return;
                         }
                         playAyah(ayahElement);
                     });
                 });

                 // Reciter Selection
                 if (reciterSelect) {
                     reciterSelect.addEventListener('change', (e) => {
                         currentReciter = e.target.value;
                         saveUserSetting('reciter', currentReciter);
                         // If currently playing, restart with the new reciter
                         if (isPlaying && currentAyahElement) {
                             playAyah(currentAyahElement);
                         }
                     });
                 }


                 // User Settings (Client-side persistence for Tilawat mode)
                 function saveUserSetting(key, value) {
                     // For logged-in users, submit a form
                     if ('<?php echo is_logged_in(); ?>') {
                         const form = document.createElement('form');
                         form.method = 'POST';
                         form.action = ''; // Post to the same page

                         const actionInput = document.createElement('input');
                         actionInput.type = 'hidden';
                         actionInput.name = 'action';
                         actionInput.value = 'set_user_setting';
                         form.appendChild(actionInput);

                         const keyInput = document.createElement('input');
                         keyInput.type = 'hidden';
                         keyInput.name = 'setting_key';
                         keyInput.value = key;
                         form.appendChild(keyInput);

                         const valueInput = document.createElement('input');
                         valueInput.type = 'hidden';
                         valueInput.name = 'setting_value';
                         valueInput.value = value;
                         form.appendChild(valueInput);

                         const csrfInput = document.createElement('input');
                         csrfInput.type = 'hidden';
                         csrfInput.name = 'csrf_token';
                         csrfInput.value = document.getElementById('csrf_token').value; // Get token from hidden input
                         form.appendChild(csrfInput);

                         document.body.appendChild(form);
                         form.submit(); // This will cause a page reload
                     } else {
                         // For public users, use localStorage
                         localStorage.setItem('user_setting_' + key, value);
                     }
                 }

                 function loadUserSettings() {
                     // Load settings from server for logged-in users (PHP renders them initially)
                     // For public users, load from localStorage
                     if (!'<?php echo is_logged_in(); ?>') {
                         const tilawatMode = localStorage.getItem('user_setting_tilawat_mode');
                         if (tilawatMode === 'on') {
                             body.classList.add('tilawat-mode');
                             if (tilawatControls) tilawatControls.style.display = 'block';
                         }
                         const fontSize = localStorage.getItem('user_setting_arabic_font_size');
                         if (fontSize && fontSizeSlider) {
                             fontSizeSlider.value = fontSize;
                             document.querySelectorAll('.ayah .arabic-text').forEach(el => {
                                 el.style.fontSize = fontSize + 'em';
                             });
                         }
                         const linesPerPage = localStorage.getItem('user_setting_lines_per_page');
                         if (linesPerPage && linesPerPageInput) {
                             linesPerPageInput.value = linesPerPage;
                         }
                         const viewMode = localStorage.getItem('user_setting_reading_view_mode');
                         if (viewMode && viewModeSelect) {
                             viewModeSelect.value = viewMode;
                         }
                         const reciter = localStorage.getItem('user_setting_reciter');
                         if (reciter && reciterSelect) {
                             reciterSelect.value = reciter;
                             currentReciter = reciter;
                         }
                     } else {
                         // For logged-in users, settings are rendered by PHP initially.
                         // We just need to apply them if the elements exist.
                         if (fontSizeSlider) {
                             const initialFontSize = fontSizeSlider.value;
                             document.querySelectorAll('.ayah .arabic-text').forEach(el => {
                                 el.style.fontSize = initialFontSize + 'em';
                             });
                         }
                         if (document.body.classList.contains('tilawat-mode')) {
                              if (tilawatControls) tilawatControls.style.display = 'block';
                         }
                     }
                 }

                 // Intersection Observer for Continuous Scroll (Basic Implementation)
                 // This would require loading more ayahs as the user scrolls
                 // For this single-file example, we'll just observe the last ayah
                 // and log a message, actual dynamic loading is more complex.
                 if (viewModeSelect && viewModeSelect.value === 'continuous') {
                     const lastAyah = document.querySelector('.ayah:last-child');
                     if (lastAyah) {
                         const observer = new IntersectionObserver((entries) => {
                             entries.forEach(entry => {
                                 if (entry.isIntersecting) {
                                     console.log('Reached the end of the current section. Load more content...');
                                     // Implement logic to load next set of ayahs (requires page reload or more complex JS)
                                     observer.unobserve(entry.target); // Stop observing the current last ayah
                                     // Observe the new last ayah after loading
                                 }
                             });
                         }, { threshold: 0.5 }); // Trigger when 50% of the last ayah is visible

                         observer.observe(lastAyah);
                     }
                 }

                 // Keyboard Navigation (Example: Arrow keys for Tilawat mode)
                 document.addEventListener('keydown', (e) => {
                     if (body.classList.contains('tilawat-mode')) {
                         if (e.key === 'ArrowRight') {
                             playNextAyah(); // Or navigate to next ayah visually
                         } else if (e.key === 'ArrowLeft') {
                             playPrevAyah(); // Or navigate to previous ayah visually
                         } else if (e.key === ' ') { // Spacebar to play/pause
                             e.preventDefault(); // Prevent scrolling
                             togglePlayPause();
                         }
                     }
                 });

                 // Swipe Navigation (Basic Example for touch devices)
                 let touchstartX = 0;
                 let touchendX = 0;

                 function handleGesture() {
                     if (body.classList.contains('tilawat-mode')) {
                         if (touchendX < touchstartX - 50) { // Swiped left
                             playNextAyah(); // Or navigate to next ayah visually
                         }
                         if (touchendX > touchstartX + 50) { // Swiped right
                             playPrevAyah(); // Or navigate to previous ayah visually
                         }
                     }
                 }

                 document.addEventListener('touchstart', e => {
                     touchstartX = e.changedTouches[0].screenX;
                 });

                 document.addEventListener('touchend', e => {
                     touchendX = e.changedTouches[0].screenX;
                     handleGesture();
                 });

             });
         </script>
    </head>
    <body class="<?php echo get_user_setting(get_user_id(), 'reading_direction', 'ltr'); ?> <?php echo get_user_setting(get_user_id(), 'tilawat_mode', 'off') === 'on' ? 'tilawat-mode' : ''; ?>">
        <header>
            <h1><?php echo SITE_NAME; ?></h1>
            <nav>
                <a href="?action=home">Home</a>
                <a href="?action=read">Read Quran</a>
                <a href="?action=search">Search</a>
                <?php if (has_role('user')): ?>
                    <a href="?action=dashboard">Dashboard</a>
                    <?php if (has_role('ulama')): ?>
                         <a href="?action=admin&view=suggestions">Suggestions</a>
                    <?php endif; ?>
                    <?php if (has_role('admin')): ?>
                        <a href="?action=admin">Admin Panel</a>
                    <?php endif; ?>
                    <a href="?action=logout">Logout (<?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>)</a>
                <?php else: ?>
                    <a href="?action=login">Login</a>
                    <a href="?action=register">Register</a>
                <?php endif; ?>
                 <?php if ($action === 'read' || ($action === 'view_surah' && isset($_GET['surah'])) || ($action === 'view_juz' && isset($_GET['juz']))): // Show Tilawat toggle only on reading pages ?>
                     <button id="tilawat-toggle" class="btn-secondary" style="margin-left: 20px;">
                         <i class="fas fa-book-open"></i> Tilawat Mode
                     </button>
                 <?php endif; ?>
            </nav>
        </header>

        <div class="container">
            <?php if ($message): ?>
                <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
             <input type="hidden" id="csrf_token" value="<?php echo $csrf_token; ?>">
    <?php
}

function render_footer() {
    ?>
        </div><!-- .container -->

        <footer>
            <div class="container" style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid var(--border-color); color: var(--secondary-color);">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Developed by Yasin Ullah (Pakistan).</p>
            </div>
        </footer>
    </body>
    </html>
    <?php
}

function render_home() {
    render_header("Home");
    ?>
    <div class="card">
        <h2>Welcome to the Quranic Study Platform</h2>
        <p>This platform allows you to read the Holy Quran, view translations and tafasir, and for registered users, personalize your study experience with bookmarks, notes, and Hifz tracking.</p>
        <p>Verified scholars (Ulama) and Admins can contribute and manage content.</p>
        <?php if (!is_logged_in()): ?>
            <p>Please <a href="?action=login">Login</a> or <a href="?action=register">Register</a> to unlock full features.</p>
        <?php endif; ?>
        <p><a href="?action=read" class="btn">Start Reading</a></p>
    </div>
    <?php
    render_footer();
}

function render_login() {
    render_header("Login");
    ?>
    <div class="card" style="max-width: 400px; margin: 20px auto;">
        <h2>Login</h2>
        <form action="" method="post">
            <input type="hidden" name="action" value="login">
             <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-actions">
                <button type="submit">Login</button>
            </div>
        </form>
        <p style="margin-top: 15px;">Don't have an account? <a href="?action=register">Register here</a>.</p>
    </div>
    <?php
    render_footer();
}

function render_register() {
    render_header("Register");
    ?>
    <div class="card" style="max-width: 400px; margin: 20px auto;">
        <h2>Register</h2>
        <form action="" method="post">
            <input type="hidden" name="action" value="register">
             <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
             <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="form-actions">
                <button type="submit">Register</button>
            </div>
        </form>
        <p style="margin-top: 15px;">Already have an account? <a href="?action=login">Login here</a>.</p>
    </div>
    <?php
    render_footer();
}

function render_dashboard() {
    if (!has_role('user')) {
        redirect('?action=login');
    }
    render_header("Dashboard");
    $view = $_GET['view'] ?? 'overview';
    $user_id = get_user_id();
    ?>
    <div class="dashboard-panel">
        <div class="dashboard-sidebar">
            <h3>Dashboard Menu</h3>
            <ul>
                <li><a href="?action=dashboard&view=overview">Overview</a></li>
                <li><a href="?action=dashboard&view=bookmarks">Bookmarks</a></li>
                <li><a href="?action=dashboard&view=notes">Notes</a></li>
                <li><a href="?action=dashboard&view=hifz">Hifz Progress</a></li>
                 <?php if (has_role('ulama')): ?>
                     <li><a href="?action=dashboard&view=contributions">My Contributions</a></li>
                 <?php endif; ?>
                <li><a href="?action=dashboard&view=settings">Settings</a></li>
            </ul>
        </div>
        <div class="dashboard-content">
            <?php
            switch ($view) {
                case 'overview':
                    echo "<h2>Welcome, " . htmlspecialchars($_SESSION['username']) . "!</h2>";
                    echo "<p>Your role: " . htmlspecialchars(get_user_role()) . "</p>";
                    // Add overview stats here (e.g., total bookmarks, notes count)
                    $pdo = db_connect();
                    $stmt_bookmarks = $pdo->prepare("SELECT COUNT(*) FROM bookmarks WHERE user_id = ?");
                    $stmt_bookmarks->execute([$user_id]);
                    $bookmark_count = $stmt_bookmarks->fetchColumn();

                    $stmt_notes = $pdo->prepare("SELECT COUNT(*) FROM notes WHERE user_id = ?");
                    $stmt_notes->execute([$user_id]);
                    $note_count = $stmt_notes->fetchColumn();

                    echo "<p>Total Bookmarks: " . $bookmark_count . "</p>";
                    echo "<p>Total Notes: " . $note_count . "</p>";

                    // Add last read position if available
                    $last_read_ayah_id = get_user_setting($user_id, 'last_read_ayah_id');
                    if ($last_read_ayah_id) {
                         $ayah_details = get_ayah_details_by_id($last_read_ayah_id);
                         if ($ayah_details) {
                             echo "<p>Last Read: Surah " . htmlspecialchars($ayah_details['english_name']) . " (" . htmlspecialchars($ayah_details['arabic_name']) . ") Ayah " . $ayah_details['ayah_number'] . "</p>";
                             echo '<p><a href="?action=view_surah&surah=' . $ayah_details['surah_number'] . '#ayah-' . $ayah_details['ayah_number'] . '" class="btn btn-secondary">Continue Reading</a></p>';
                         }
                    }

                    // Add Hifz progress summary
                    $hifz_progress = get_user_hifz_progress($user_id);
                    if (!empty($hifz_progress)) {
                        echo "<h3>Recent Hifz Progress</h3>";
                        echo "<ul>";
                        foreach ($hifz_progress as $progress) {
                            echo "<li>Surah " . htmlspecialchars($progress['english_name']) . " (" . htmlspecialchars($progress['arabic_name']) . ") up to Ayah " . $progress['last_ayah_number'] . " (Last updated: " . $progress['progress_date'] . ")</li>";
                        }
                        echo "</ul>";
                    }

                    break;
                case 'bookmarks':
                    echo "<h2>Your Bookmarks</h2>";
                    $bookmarks = get_user_bookmarks($user_id);
                    if (empty($bookmarks)) {
                        echo "<p>You have no bookmarks yet.</p>";
                    } else {
                        echo "<ul>";
                        foreach ($bookmarks as $bookmark) {
                            echo '<li>';
                            echo '<a href="?action=view_surah&surah=' . $bookmark['surah_id'] . '#ayah-' . $bookmark['ayah_number'] . '">';
                            echo 'Surah ' . htmlspecialchars($bookmark['english_name']) . ' (' . htmlspecialchars($bookmark['arabic_name']) . ') Ayah ' . $bookmark['ayah_number'];
                            echo '</a>';
                            // Form to remove bookmark
                            echo '<form action="" method="post" style="display:inline-block; margin-left: 10px;">';
                            echo '<input type="hidden" name="action" value="remove_bookmark">';
                            echo '<input type="hidden" name="ayah_id" value="' . $bookmark['ayah_id'] . '">';
                             echo '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
                            echo '<button type="submit" class="btn-danger btn-sm"><i class="fas fa-trash"></i> Remove</button>';
                            echo '</form>';
                            echo '</li>';
                        }
                        echo "</ul>";
                    }
                    break;
                case 'notes':
                    echo "<h2>Your Notes</h2>";
                    $notes = get_user_notes($user_id);
                    if (empty($notes)) {
                        echo "<p>You have no notes yet.</p>";
                    } else {
                        echo "<ul>";
                        foreach ($notes as $note) {
                            echo '<li>';
                            echo '<strong>Surah ' . htmlspecialchars($note['english_name']) . ' (' . htmlspecialchars($note['arabic_name']) . ') Ayah ' . $note['ayah_number'] . ':</strong> ';
                            echo htmlspecialchars($note['note']);
                            echo '<br><small>Added: ' . $note['created_at'] . ( ($note['updated_at'] != $note['created_at']) ? ' | Updated: ' . $note['updated_at'] : '') . '</small>';
                            // Forms to edit/delete note
                            echo '<form action="" method="post" style="display:inline-block; margin-left: 10px;">';
                            echo '<input type="hidden" name="action" value="delete_note">';
                            echo '<input type="hidden" name="note_id" value="' . $note['id'] . '">';
                             echo '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
                            echo '<button type="submit" class="btn-danger btn-sm"><i class="fas fa-trash"></i> Delete</button>';
                            echo '</form>';
                             // Simple edit form (could be a modal or separate page)
                             echo '<form action="" method="post" style="display:inline-block; margin-left: 10px;">';
                             echo '<input type="hidden" name="action" value="update_note">';
                             echo '<input type="hidden" name="note_id" value="' . $note['id'] . '">';
                             echo '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
                             echo '<textarea name="note" rows="2" cols="30" required>' . htmlspecialchars($note['note']) . '</textarea>';
                             echo '<button type="submit" class="btn-secondary btn-sm"><i class="fas fa-edit"></i> Update</button>';
                             echo '</form>';

                            echo '</li>';
                        }
                        echo "</ul>";
                    }
                    break;
                case 'hifz':
                    echo "<h2>Your Hifz Progress</h2>";
                    $hifz_progress = get_user_hifz_progress($user_id);
                    $surahs = get_surahs();

                    echo "<p>Select a Surah and the last Ayah you have memorized.</p>";
                    echo '<form action="" method="post">';
                    echo '<input type="hidden" name="action" value="update_hifz_progress">';
                     echo '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
                    echo '<div class="form-group">';
                    echo '<label for="surah_id">Surah:</label>';
                    echo '<select name="surah_id" id="surah_id" required>';
                    echo '<option value="">Select Surah</option>';
                    foreach ($surahs as $surah) {
                        echo '<option value="' . $surah['id'] . '">Surah ' . htmlspecialchars($surah['english_name']) . ' (' . htmlspecialchars($surah['arabic_name']) . ')</option>';
                    }
                    echo '</select>';
                    echo '</div>';
                    echo '<div class="form-group">';
                    echo '<label for="ayah_id">Last Memorized Ayah:</label>';
                    // This select needs to be dynamically populated based on selected surah
                    // Without AJAX, we can pre-populate all ayahs or require a page reload after surah selection
                    // For simplicity, we'll pre-populate all ayahs and use JS to filter/show relevant ones.
                    $all_ayahs = $pdo->query("SELECT id, surah_id, ayah_number FROM ayahs ORDER BY surah_id, ayah_number")->fetchAll();
                    echo '<select name="ayah_id" id="ayah_id" required>';
                    echo '<option value="">Select Ayah</option>';
                    foreach ($all_ayahs as $ayah) {
                         echo '<option value="' . $ayah['id'] . '" data-surah-id="' . $ayah['surah_id'] . '" style="display:none;">Ayah ' . $ayah['ayah_number'] . '</option>';
                    }
                    echo '</select>';
                    echo '</div>';
                    echo '<div class="form-actions">';
                    echo '<button type="submit">Update Progress</button>';
                    echo '</div>';
                    echo '</form>';

                    // JS to filter ayah select based on surah selection
                    echo '<script>
                        document.getElementById("surah_id").addEventListener("change", function() {
                            const surahId = this.value;
                            const ayahSelect = document.getElementById("ayah_id");
                            const options = ayahSelect.querySelectorAll("option");

                            options.forEach(option => {
                                if (option.value === "") {
                                    option.style.display = "block"; // Keep the "Select Ayah" option visible
                                } else {
                                    if (option.dataset.surahId === surahId) {
                                        option.style.display = "block";
                                    } else {
                                        option.style.display = "none";
                                    }
                                }
                            });
                            ayahSelect.value = ""; // Reset selected ayah
                        });
                    </script>';


                    echo "<h3>Current Hifz Progress</h3>";
                    if (empty($hifz_progress)) {
                        echo "<p>No Hifz progress recorded yet.</p>";
                    } else {
                        echo "<table>";
                        echo "<thead><tr><th>Surah</th><th>Last Memorized Ayah</th><th>Last Updated</th></tr></thead>";
                        echo "<tbody>";
                        foreach ($hifz_progress as $progress) {
                            echo "<tr>";
                            echo '<td>Surah ' . htmlspecialchars($progress['english_name']) . ' (' . htmlspecialchars($progress['arabic_name']) . ')</td>';
                            echo '<td>Ayah ' . $progress['last_ayah_number'] . '</td>';
                            echo '<td>' . $progress['progress_date'] . '</td>';
                            echo "</tr>";
                        }
                        echo "</tbody>";
                        echo "</table>";
                    }
                    break;
                 case 'contributions':
                     if (has_role('ulama')) {
                         echo "<h2>My Contributions</h2>";
                         // Display contributions made by the current user (translations, tafasir, word meanings)
                         // This requires querying the respective tables filtering by contributor_id
                         $pdo = db_connect();
                         $stmt_trans = $pdo->prepare("
                             SELECT t.*, a.surah_id, a.ayah_number, s.arabic_name, s.english_name
                             FROM translations t
                             JOIN ayahs a ON t.ayah_id = a.id
                             JOIN surahs s ON a.surah_id = s.id
                             WHERE t.contributor_id = ? ORDER BY a.surah_id, a.ayah_number, t.version DESC
                         ");
                         $stmt_trans->execute([$user_id]);
                         $my_translations = $stmt_trans->fetchAll();

                         $stmt_tafasir = $pdo->prepare("
                             SELECT tf.*, a.surah_id, a.ayah_number, s.arabic_name, s.english_name
                             FROM tafasir tf
                             JOIN ayahs a ON tf.ayah_id = a.id
                             JOIN surahs s ON a.surah_id = s.id
                             WHERE tf.contributor_id = ? ORDER BY a.surah_id, a.ayah_number, tf.version DESC
                         ");
                         $stmt_tafasir->execute([$user_id]);
                         $my_tafasir = $stmt_tafasir->fetchAll();

                         $stmt_wm = $pdo->prepare("
                             SELECT wm.*, a.surah_id, a.ayah_number, s.arabic_name, s.english_name
                             FROM word_meanings wm
                             JOIN ayahs a ON wm.ayah_id = a.id
                             JOIN surahs s ON a.surah_id = s.id
                             WHERE wm.contributor_id = ? ORDER BY a.surah_id, a.ayah_number, wm.word_index, wm.version DESC
                         ");
                         $stmt_wm->execute([$user_id]);
                         $my_word_meanings = $stmt_wm->fetchAll();

                         echo "<h3>My Translations</h3>";
                         if (empty($my_translations)) {
                             echo "<p>You have not added any translations yet.</p>";
                         } else {
                             echo "<table>";
                             echo "<thead><tr><th>Ayah</th><th>Language</th><th>Text</th><th>Status</th><th>Version</th><th>Default</th></tr></thead>";
                             echo "<tbody>";
                             foreach ($my_translations as $trans) {
                                 echo "<tr>";
                                 echo '<td><a href="?action=view_surah&surah=' . $trans['surah_id'] . '#ayah-' . $trans['ayah_number'] . '">S' . $trans['surah_id'] . ':A' . $trans['ayah_number'] . '</a></td>';
                                 echo '<td>' . htmlspecialchars($trans['language']) . '</td>';
                                 echo '<td>' . htmlspecialchars(substr($trans['text'], 0, 100)) . '...</td>';
                                 echo '<td>' . htmlspecialchars($trans['status']) . '</td>';
                                 echo '<td>' . $trans['version'] . '</td>';
                                 echo '<td>' . ($trans['is_default'] ? 'Yes' : 'No') . '</td>';
                                 echo "</tr>";
                             }
                             echo "</tbody>";
                             echo "</table>";
                         }

                         echo "<h3>My Tafasir</h3>";
                         if (empty($my_tafasir)) {
                             echo "<p>You have not added any tafasir yet.</p>";
                         } else {
                             echo "<table>";
                             echo "<thead><tr><th>Ayah</th><th>Title</th><th>Text</th><th>Status</th><th>Version</th><th>Default</th></tr></thead>";
                             echo "<tbody>";
                             foreach ($my_tafasir as $tafsir) {
                                 echo "<tr>";
                                 echo '<td><a href="?action=view_surah&surah=' . $tafsir['surah_id'] . '#ayah-' . $tafsir['ayah_number'] . '">S' . $tafsir['surah_id'] . ':A' . $tafsir['ayah_number'] . '</a></td>';
                                 echo '<td>' . htmlspecialchars($tafsir['title']) . '</td>';
                                 echo '<td>' . htmlspecialchars(substr($tafsir['text'], 0, 100)) . '...</td>';
                                 echo '<td>' . htmlspecialchars($tafsir['status']) . '</td>';
                                 echo '<td>' . $tafsir['version'] . '</td>';
                                 echo '<td>' . ($tafsir['is_default'] ? 'Yes' : 'No') . '</td>';
                                 echo "</tr>";
                             }
                             echo "</tbody>";
                             echo "</table>";
                         }

                         echo "<h3>My Word Meanings</h3>";
                         if (empty($my_word_meanings)) {
                             echo "<p>You have not added any word meanings yet.</p>";
                         } else {
                             echo "<table>";
                             echo "<thead><tr><th>Ayah</th><th>Word Index</th><th>Arabic Word</th><th>Meaning</th><th>Status</th><th>Version</th><th>Default</th></tr></thead>";
                             echo "<tbody>";
                             foreach ($my_word_meanings as $wm) {
                                 echo "<tr>";
                                 echo '<td><a href="?action=view_surah&surah=' . $wm['surah_id'] . '#ayah-' . $wm['ayah_number'] . '">S' . $wm['surah_id'] . ':A' . $wm['ayah_number'] . '</a></td>';
                                 echo '<td>' . $wm['word_index'] . '</td>';
                                 echo '<td>' . htmlspecialchars($wm['arabic_word']) . '</td>';
                                 echo '<td>' . htmlspecialchars(substr($wm['meaning'], 0, 100)) . '...</td>';
                                 echo '<td>' . htmlspecialchars($wm['status']) . '</td>';
                                 echo '<td>' . $wm['version'] . '</td>';
                                 echo '<td>' . ($wm['is_default'] ? 'Yes' : 'No') . '</td>';
                                 echo "</tr>";
                             }
                             echo "</tbody>";
                             echo "</table>";
                         }

                     } else {
                         echo "<p>You do not have permission to view this section.</p>";
                     }
                     break;
                case 'settings':
                    echo "<h2>Your Settings</h2>";
                    // User-specific settings form (e.g., reading preferences)
                    $current_direction = get_user_setting($user_id, 'reading_direction', 'ltr');
                    $current_font_size = get_user_setting($user_id, 'arabic_font_size', '1.8');
                    $current_lines_per_page = get_user_setting($user_id, 'lines_per_page', '15');
                    $current_view_mode = get_user_setting($user_id, 'reading_view_mode', 'paginated');
                    $current_reciter = get_user_setting($user_id, 'reciter', DEFAULT_RECITER);

                    echo '<form action="" method="post">';
                    echo '<input type="hidden" name="action" value="set_user_setting">';
                     echo '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';

                    echo '<div class="form-group">';
                    echo '<label for="reading_direction">Reading Direction:</label>';
                    echo '<select name="setting_value" id="reading_direction">';
                    echo '<option value="ltr"' . ($current_direction === 'ltr' ? ' selected' : '') . '>Left-to-Right</option>';
                    echo '<option value="rtl"' . ($current_direction === 'rtl' ? ' selected' : '') . '>Right-to-Left (Arabic/Urdu)</option>';
                    echo '</select>';
                    echo '<input type="hidden" name="setting_key" value="reading_direction">';
                    echo '</div>';
                    echo '<button type="submit">Save Direction</button>';
                    echo '</form>';

                     echo '<form action="" method="post">';
                    echo '<input type="hidden" name="action" value="set_user_setting">';
                     echo '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
                    echo '<div class="form-group">';
                    echo '<label for="arabic_font_size">Arabic Font Size (em):</label>';
                    echo '<input type="number" id="arabic_font_size" name="setting_value" value="' . htmlspecialchars($current_font_size) . '" step="0.1" min="1" max="5">';
                    echo '<input type="hidden" name="setting_key" value="arabic_font_size">';
                    echo '</div>';
                    echo '<button type="submit">Save Font Size</button>';
                    echo '</form>';

                     echo '<form action="" method="post">';
                    echo '<input type="hidden" name="action" value="set_user_setting">';
                     echo '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
                    echo '<div class="form-group">';
                    echo '<label for="lines_per_page">Lines per Page (Tilawat Mode):</label>';
                    echo '<input type="number" id="lines_per_page" name="setting_value" value="' . htmlspecialchars($current_lines_per_page) . '" step="1" min="5" max="50">';
                    echo '<input type="hidden" name="setting_key" value="lines_per_page">';
                    echo '</div>';
                    echo '<button type="submit">Save Lines per Page</button>';
                    echo '</form>';

                     echo '<form action="" method="post">';
                    echo '<input type="hidden" name="action" value="set_user_setting">';
                     echo '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
                    echo '<div class="form-group">';
                    echo '<label for="reading_view_mode">Reading View Mode:</label>';
                    echo '<select name="setting_value" id="reading_view_mode">';
                    echo '<option value="paginated"' . ($current_view_mode === 'paginated' ? ' selected' : '') . '>Paginated</option>';
                    echo '<option value="continuous"' . ($current_view_mode === 'continuous' ? ' selected' : '') . '>Continuous Scroll</option>';
                    echo '</select>';
                    echo '<input type="hidden" name="setting_key" value="reading_view_mode">';
                    echo '</div>';
                    echo '<button type="submit">Save View Mode</button>';
                    echo '</form>';

                     echo '<form action="" method="post">';
                    echo '<input type="hidden" name="action" value="set_user_setting">';
                     echo '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
                    echo '<div class="form-group">';
                    echo '<label for="reciter">Default Reciter:</label>';
                    echo '<select name="setting_value" id="reciter">';
                    // This list should ideally be dynamic from an API or config
                    echo '<option value="Alafasy_128kbps"' . ($current_reciter === 'Alafasy_128kbps' ? ' selected' : '') . '>Mishary Alafasy</option>';
                    echo '<option value="Abdul_Basit_Mujawwad_128kbps"' . ($current_reciter === 'Abdul_Basit_Mujawwad_128kbps' ? ' selected' : '') . '>Abdul Basit (Mujawwad)</option>';
                    // Add more reciters here
                    echo '</select>';
                    echo '<input type="hidden" name="setting_key" value="reciter">';
                    echo '</div>';
                    echo '<button type="submit">Save Reciter</button>';
                    echo '</form>';


                    break;
                default:
                    render_dashboard_overview(); // Default to overview
                    break;
            }
            ?>
        </div>
    </div>
    <?php
    render_footer();
}

function render_read() {
    render_header("Read Quran");
    $surahs = get_surahs();
    $juz_list = get_juz_list();
    ?>
    <div class="card">
        <h2>Read Quran</h2>
        <p>Select a Surah or Juz to start reading.</p>

        <h3>Surah Index</h3>
        <ul class="surah-list">
            <?php foreach ($surahs as $surah): ?>
                <li>
                    <a href="?action=view_surah&surah=<?php echo $surah['surah_number']; ?>">
                        <?php echo $surah['surah_number']; ?>. <?php echo htmlspecialchars($surah['english_name']); ?> (<?php echo htmlspecialchars($surah['arabic_name']); ?>) - <?php echo $surah['ayah_count']; ?> Ayahs
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <h3>Juz Index</h3>
        <ul class="surah-list">
            <?php foreach ($juz_list as $juz): ?>
                <li>
                    <a href="?action=view_juz&juz=<?php echo $juz; ?>">
                        Juz <?php echo $juz; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

     <!-- Tilawat Mode Controls (Hidden by default) -->
     <div class="tilawat-controls">
         <h4>Tilawat Settings</h4>
         <div class="form-group">
             <label for="font-size-slider">Arabic Font Size:</label>
             <input type="range" id="font-size-slider" min="1.5" max="4" step="0.1" value="<?php echo htmlspecialchars(get_user_setting(get_user_id(), 'arabic_font_size', '1.8')); ?>">
         </div>
          <div class="form-group">
             <label for="lines-per-page">Lines per Page:</label>
             <input type="number" id="lines-per-page" min="5" max="50" step="1" value="<?php echo htmlspecialchars(get_user_setting(get_user_id(), 'lines_per_page', '15')); ?>">
         </div>
          <div class="form-group">
             <label for="view-mode">View Mode:</label>
             <select id="view-mode">
                 <option value="paginated" <?php echo get_user_setting(get_user_id(), 'reading_view_mode', 'paginated') === 'paginated' ? 'selected' : ''; ?>>Paginated</option>
                 <option value="continuous" <?php echo get_user_setting(get_user_id(), 'reading_view_mode', 'paginated') === 'continuous' ? 'selected' : ''; ?>>Continuous Scroll</option>
             </select>
         </div>
          <div class="form-group">
             <label for="reciter-select">Reciter:</label>
             <select id="reciter-select">
                 <option value="Alafasy_128kbps" <?php echo get_user_setting(get_user_id(), 'reciter', DEFAULT_RECITER) === 'Alafasy_128kbps' ? 'selected' : ''; ?>>Mishary Alafasy</option>
                 <option value="Abdul_Basit_Mujawwad_128kbps" <?php echo get_user_setting(get_user_id(), 'reciter', DEFAULT_RECiter) === 'Abdul_Basit_Mujawwad_128kbps' ? 'selected' : ''; ?>>Abdul Basit (Mujawwad)</option>
                 <!-- Add more reciters here -->
             </select>
         </div>
          <div class="audio-player">
             <audio id="audio-player" controls style="display: none;"></audio>
             <button id="prev-ayah-btn"><i class="fas fa-backward"></i></button>
             <button id="play-pause-btn"><i class="fas fa-play"></i></button>
             <button id="next-ayah-btn"><i class="fas fa-forward"></i></button>
         </div>
     </div>

    <?php
    render_footer();
}

function render_view_surah($surah_number) {
    $surah = get_surah_by_number($surah_number);
    if (!$surah) {
        render_header("Surah Not Found");
        echo "<div class='error'>Surah not found.</div>";
        render_footer();
        return;
    }

    render_header("Surah " . htmlspecialchars($surah['english_name']) . " (" . htmlspecialchars($surah['arabic_name']) . ")");
    $ayahs = get_ayahs_by_surah($surah['id']);
    $user_id = get_user_id();
    $user_bookmarks = has_role('user') ? get_user_bookmarks($user_id) : [];
    $bookmarked_ayah_ids = array_column($user_bookmarks, 'ayah_id');

    // Get user settings for reading
    $reading_direction = get_user_setting($user_id, 'reading_direction', 'ltr');
    $arabic_font_size = get_user_setting($user_id, 'arabic_font_size', '1.8');
    $lines_per_page = get_user_setting($user_id, 'lines_per_page', '15');
    $view_mode = get_user_setting($user_id, 'reading_view_mode', 'paginated');
    $default_reciter = get_user_setting($user_id, 'reciter', DEFAULT_RECITER);


    ?>
    <h2 style="text-align: center;">Surah <?php echo htmlspecialchars($surah['english_name']); ?> (<?php echo htmlspecialchars($surah['arabic_name']); ?>)</h2>
    <p style="text-align: center;">Ayahs: <?php echo $surah['ayah_count']; ?> | Revelation: <?php echo htmlspecialchars($surah['revelation_type']); ?></p>

     <!-- Tilawat Mode Controls (Hidden by default) -->
     <div class="tilawat-controls">
         <h4>Tilawat Settings</h4>
         <div class="form-group">
             <label for="font-size-slider">Arabic Font Size:</label>
             <input type="range" id="font-size-slider" min="1.5" max="4" step="0.1" value="<?php echo htmlspecialchars($arabic_font_size); ?>">
         </div>
          <div class="form-group">
             <label for="lines-per-page">Lines per Page:</label>
             <input type="number" id="lines-per-page" min="5" max="50" step="1" value="<?php echo htmlspecialchars($lines_per_page); ?>">
         </div>
          <div class="form-group">
             <label for="view-mode">View Mode:</label>
             <select id="view-mode">
                 <option value="paginated" <?php echo $view_mode === 'paginated' ? 'selected' : ''; ?>>Paginated</option>
                 <option value="continuous" <?php echo $view_mode === 'continuous' ? 'selected' : ''; ?>>Continuous Scroll</option>
             </select>
         </div>
          <div class="form-group">
             <label for="reciter-select">Reciter:</label>
             <select id="reciter-select">
                 <option value="Alafasy_128kbps" <?php echo $default_reciter === 'Alafasy_128kbps' ? 'selected' : ''; ?>>Mishary Alafasy</option>
                 <option value="Abdul_Basit_Mujawwad_128kbps" <?php echo $default_reciter === 'Abdul_Basit_Mujawwad_128kbps' ? 'selected' : ''; ?>>Abdul Basit (Mujawwad)</option>
                 <!-- Add more reciters here -->
             </select>
         </div>
          <div class="audio-player">
             <audio id="audio-player" controls style="display: none;"></audio>
             <button id="prev-ayah-btn"><i class="fas fa-backward"></i></button>
             <button id="play-pause-btn"><i class="fas fa-play"></i></button>
             <button id="next-ayah-btn"><i class="fas fa-forward"></i></button>
         </div>
     </div>


    <?php foreach ($ayahs as $ayah):
        $translations = get_translations($ayah['id']);
        $tafasir = get_tafasir($ayah['id']);
        $word_meanings = get_word_meanings($ayah['id']);
        $is_bookmarked = in_array($ayah['id'], $bookmarked_ayah_ids);
        ?>
        <div class="ayah" id="ayah-<?php echo $ayah['ayah_number']; ?>"
             data-ayah-id="<?php echo $ayah['id']; ?>"
             data-surah-number="<?php echo $surah['surah_number']; ?>"
             data-ayah-number="<?php echo $ayah['ayah_number']; ?>"
             data-juz="<?php echo $ayah['juz']; ?>"
             data-hizb="<?php echo $ayah['hizb']; ?>"
             data-quarter="<?php echo $ayah['quarter']; ?>"
             style="direction: <?php echo $reading_direction; ?>;"
        >
            <span class="ayah-number">(<?php echo $ayah['ayah_number']; ?>)</span>
            <div class="arabic-text" style="font-size: <?php echo htmlspecialchars($arabic_font_size); ?>em;">
                 <?php
                 // Display Arabic text with word meanings on hover
                 $arabic_words = explode(' ', $ayah['arabic_text']);
                 foreach ($arabic_words as $index => $word) {
                     $word = trim($word);
                     if (empty($word)) continue;

                     $meaning_found = null;
                     foreach ($word_meanings as $wm) {
                         if ($wm['word_index'] == $index && $wm['is_default']) {
                             $meaning_found = $wm;
                             break;
                         }
                     }

                     if ($meaning_found) {
                         echo '<span class="word-meaning">';
                         echo htmlspecialchars($word);
                         echo '<span class="word-tooltip">';
                         echo '<strong>' . htmlspecialchars($meaning_found['arabic_word']) . '</strong><br>';
                         if (!empty($meaning_found['transliteration'])) {
                             echo '<em>' . htmlspecialchars($meaning_found['transliteration']) . '</em><br>';
                         }
                         echo htmlspecialchars($meaning_found['meaning']);
                         if (!empty($meaning_found['grammar'])) {
                             echo '<br><small>(' . htmlspecialchars($meaning_found['grammar']) . ')</small>';
                         }
                         echo '</span>'; // .word-tooltip
                         echo '</span>'; // .word-meaning
                     } else {
                         echo htmlspecialchars($word) . ' ';
                     }
                 }
                 ?>
            </div>

            <?php
            // Display default translation
            $default_translation = null;
            foreach ($translations as $trans) {
                if ($trans['is_default']) {
                    $default_translation = $trans;
                    break;
                }
            }
            if ($default_translation): ?>
                <div class="translation-text">
                    <?php echo htmlspecialchars($default_translation['text']); ?>
                </div>
            <?php endif; ?>

            <?php
            // Display default tafsir
            $default_tafsir = null;
             foreach ($tafasir as $tf) {
                if ($tf['is_default']) {
                    $default_tafsir = $tf;
                    break;
                }
            }
            if ($default_tafsir): ?>
                <div class="tafsir-text">
                    <strong><?php echo htmlspecialchars($default_tafsir['title'] ?? 'Tafsir'); ?>:</strong>
                    <?php echo nl2br(htmlspecialchars($default_tafsir['text'])); ?>
                </div>
            <?php endif; ?>

            <div class="ayah-actions">
                 <?php if (has_role('user')): ?>
                     <form action="" method="post" style="display:inline-block;">
                         <input type="hidden" name="action" value="<?php echo $is_bookmarked ? 'remove_bookmark' : 'add_bookmark'; ?>">
                         <input type="hidden" name="ayah_id" value="<?php echo $ayah['id']; ?>">
                          <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                         <button type="submit" title="<?php echo $is_bookmarked ? 'Remove Bookmark' : 'Add Bookmark'; ?>">
                             <i class="fas fa-bookmark<?php echo $is_bookmarked ? ' text-primary' : ''; ?>"></i>
                         </button>
                     </form>
                     <button title="Add Note" onclick="showNoteForm(<?php echo $ayah['id']; ?>)"><i class="fas fa-sticky-note"></i></button>
                     <button title="Suggest Content" onclick="showSuggestForm(<?php echo $ayah['id']; ?>)"><i class="fas fa-lightbulb"></i></button>
                     <button title="Mark Hifz Progress" onclick="showHifzForm(<?php echo $surah['id']; ?>, <?php echo $ayah['id']; ?>, '<?php echo htmlspecialchars($surah['english_name']); ?> (<?php echo htmlspecialchars($surah['arabic_name']); ?>)', <?php echo $ayah['ayah_number']; ?>)"><i class="fas fa-check-circle"></i></button>
                 <?php endif; ?>
                 <button title="Play Audio" onclick="playAyah(document.getElementById('ayah-<?php echo $ayah['ayah_number']; ?>'))"><i class="fas fa-volume-up"></i></button>
                 <!-- Add buttons for viewing all translations/tafasir/word meanings -->
                 <a href="?action=view_content&type=translation&ayah_id=<?php echo $ayah['id']; ?>" title="View Translations"><i class="fas fa-language"></i></a>
                 <a href="?action=view_content&type=tafsir&ayah_id=<?php echo $ayah['id']; ?>" title="View Tafasir"><i class="fas fa-book-open"></i></a>
                 <a href="?action=view_content&type=word_meaning&ayah_id=<?php echo $ayah['id']; ?>" title="View Word Meanings"><i class="fas fa-font"></i></a>
            </div>
        </div>
    <?php endforeach; ?>

     <!-- Modals/Forms for Actions (Hidden by default, shown with JS) -->
     <div id="note-form-modal" style="display:none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.5); z-index: 1001;">
         <h3>Add Note</h3>
         <form action="" method="post">
             <input type="hidden" name="action" value="add_note">
             <input type="hidden" name="ayah_id" id="note-ayah-id" value="">
              <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
             <div class="form-group">
                 <label for="note-text">Note:</label>
                 <textarea id="note-text" name="note" rows="4" cols="50" required></textarea>
             </div>
             <div class="form-actions">
                 <button type="submit">Save Note</button>
                 <button type="button" onclick="document.getElementById('note-form-modal').style.display='none';">Cancel</button>
             </div>
         </form>
     </div>

     <div id="suggest-form-modal" style="display:none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.5); z-index: 1001;">
         <h3>Suggest Content</h3>
         <form action="" method="post">
             <input type="hidden" name="action" value="suggest_content">
             <input type="hidden" name="ayah_id" id="suggest-ayah-id" value="">
              <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
             <div class="form-group">
                 <label for="content-type">Content Type:</label>
                 <select id="content-type" name="content_type" required>
                     <option value="">Select Type</option>
                     <option value="translation">Translation</option>
                     <option value="tafsir">Tafsir</option>
                     <option value="word_meaning">Word Meaning</option>
                 </select>
             </div>
              <div class="form-group" id="word-index-group" style="display:none;">
                 <label for="word-index">Word Index (0-based):</label>
                 <input type="number" id="word-index" name="word_index" min="0">
             </div>
             <div class="form-group">
                 <label for="suggested-text">Suggested Text:</label>
                 <textarea id="suggested-text" name="suggested_text" rows="6" cols="50" required></textarea>
             </div>
             <div class="form-actions">
                 <button type="submit">Submit Suggestion</button>
                 <button type="button" onclick="document.getElementById('suggest-form-modal').style.display='none';">Cancel</button>
             </div>
         </form>
     </div>

     <div id="hifz-form-modal" style="display:none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.5); z-index: 1001;">
         <h3>Mark Hifz Progress</h3>
         <form action="" method="post">
             <input type="hidden" name="action" value="update_hifz_progress">
             <input type="hidden" name="surah_id" id="hifz-surah-id" value="">
             <input type="hidden" name="ayah_id" id="hifz-ayah-id" value="">
              <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
             <p>Mark Surah <span id="hifz-surah-name"></span> up to Ayah <span id="hifz-ayah-number"></span> as memorized.</p>
             <div class="form-actions">
                 <button type="submit">Confirm Progress</button>
                 <button type="button" onclick="document.getElementById('hifz-form-modal').style.display='none';">Cancel</button>
             </div>
         </form>
     </div>

     <script>
         function showNoteForm(ayahId) {
             document.getElementById('note-ayah-id').value = ayahId;
             document.getElementById('note-form-modal').style.display = 'block';
         }
         function showSuggestForm(ayahId) {
             document.getElementById('suggest-ayah-id').value = ayahId;
             document.getElementById('suggest-form-modal').style.display = 'block';
         }
          document.getElementById('content-type').addEventListener('change', function() {
              const wordIndexGroup = document.getElementById('word-index-group');
              if (this.value === 'word_meaning') {
                  wordIndexGroup.style.display = 'block';
                  document.getElementById('word-index').setAttribute('required', 'required');
              } else {
                  wordIndexGroup.style.display = 'none';
                  document.getElementById('word-index').removeAttribute('required');
              }
          });

         function showHifzForm(surahId, ayahId, surahName, ayahNumber) {
             document.getElementById('hifz-surah-id').value = surahId;
             document.getElementById('hifz-ayah-id').value = ayahId;
             document.getElementById('hifz-surah-name').textContent = surahName;
             document.getElementById('hifz-ayah-number').textContent = ayahNumber;
             document.getElementById('hifz-form-modal').style.display = 'block';
         }

         // Basic JS for audio playback (already in header script, but ensure it's available)
         // The playAyah function is defined in the header script and used here.
     </script>

    <?php
    render_footer();
}

function render_view_juz($juz_number) {
    render_header("Juz " . htmlspecialchars($juz_number));
    $ayahs = get_ayahs_by_juz($juz_number);
     $user_id = get_user_id();
    $user_bookmarks = has_role('user') ? get_user_bookmarks($user_id) : [];
    $bookmarked_ayah_ids = array_column($user_bookmarks, 'ayah_id');

     // Get user settings for reading
    $reading_direction = get_user_setting($user_id, 'reading_direction', 'ltr');
    $arabic_font_size = get_user_setting($user_id, 'arabic_font_size', '1.8');
    $lines_per_page = get_user_setting($user_id, 'lines_per_page', '15');
    $view_mode = get_user_setting($user_id, 'reading_view_mode', 'paginated');
    $default_reciter = get_user_setting($user_id, 'reciter', DEFAULT_RECITER);

    ?>
    <h2 style="text-align: center;">Juz <?php echo htmlspecialchars($juz_number); ?></h2>

     <!-- Tilawat Mode Controls (Hidden by default) -->
     <div class="tilawat-controls">
         <h4>Tilawat Settings</h4>
         <div class="form-group">
             <label for="font-size-slider">Arabic Font Size:</label>
             <input type="range" id="font-size-slider" min="1.5" max="4" step="0.1" value="<?php echo htmlspecialchars($arabic_font_size); ?>">
         </div>
          <div class="form-group">
             <label for="lines-per-page">Lines per Page:</label>
             <input type="number" id="lines-per-page" min="5" max="50" step="1" value="<?php echo htmlspecialchars($lines_per_page); ?>">
         </div>
          <div class="form-group">
             <label for="view-mode">View Mode:</label>
             <select id="view-mode">
                 <option value="paginated" <?php echo $view_mode === 'paginated' ? 'selected' : ''; ?>>Paginated</option>
                 <option value="continuous" <?php echo $view_mode === 'continuous' ? 'selected' : ''; ?>>Continuous Scroll</option>
             </select>
         </div>
          <div class="form-group">
             <label for="reciter-select">Reciter:</label>
             <select id="reciter-select">
                 <option value="Alafasy_128kbps" <?php echo $default_reciter === 'Alafasy_128kbps' ? 'selected' : ''; ?>>Mishary Alafasy</option>
                 <option value="Abdul_Basit_Mujawwad_128kbps" <?php echo $default_reciter === 'Abdul_Basit_Mujawwad_128kbps' ? 'selected' : ''; ?>>Abdul Basit (Mujawwad)</option>
                 <!-- Add more reciters here -->
             </select>
         </div>
          <div class="audio-player">
             <audio id="audio-player" controls style="display: none;"></audio>
             <button id="prev-ayah-btn"><i class="fas fa-backward"></i></button>
             <button id="play-pause-btn"><i class="fas fa-play"></i></button>
             <button id="next-ayah-btn"><i class="fas fa-forward"></i></button>
         </div>
     </div>

    <?php
    $current_surah_number = null;
    foreach ($ayahs as $ayah):
        if ($ayah['surah_number'] !== $current_surah_number) {
            $current_surah_number = $ayah['surah_number'];
            echo '<h3 style="text-align: center; margin-top: 30px;">Surah ' . htmlspecialchars($ayah['english_name']) . ' (' . htmlspecialchars($ayah['arabic_name']) . ')</h3>';
        }
        $translations = get_translations($ayah['id']);
        $tafasir = get_tafasir($ayah['id']);
        $word_meanings = get_word_meanings($ayah['id']);
        $is_bookmarked = in_array($ayah['id'], $bookmarked_ayah_ids);
        ?>
        <div class="ayah" id="ayah-<?php echo $ayah['ayah_number']; ?>"
             data-ayah-id="<?php echo $ayah['id']; ?>"
             data-surah-number="<?php echo $ayah['surah_number']; ?>"
             data-ayah-number="<?php echo $ayah['ayah_number']; ?>"
             data-juz="<?php echo $ayah['juz']; ?>"
             data-hizb="<?php echo $ayah['hizb']; ?>"
             data-quarter="<?php echo $ayah['quarter']; ?>"
             style="direction: <?php echo $reading_direction; ?>;"
        >
            <span class="ayah-number">(<?php echo $ayah['ayah_number']; ?>)</span>
            <div class="arabic-text" style="font-size: <?php echo htmlspecialchars($arabic_font_size); ?>em;">
                 <?php
                 // Display Arabic text with word meanings on hover
                 $arabic_words = explode(' ', $ayah['arabic_text']);
                 foreach ($arabic_words as $index => $word) {
                     $word = trim($word);
                     if (empty($word)) continue;

                     $meaning_found = null;
                     foreach ($word_meanings as $wm) {
                         if ($wm['word_index'] == $index && $wm['is_default']) {
                             $meaning_found = $wm;
                             break;
                         }
                     }

                     if ($meaning_found) {
                         echo '<span class="word-meaning">';
                         echo htmlspecialchars($word);
                         echo '<span class="word-tooltip">';
                         echo '<strong>' . htmlspecialchars($meaning_found['arabic_word']) . '</strong><br>';
                         if (!empty($meaning_found['transliteration'])) {
                             echo '<em>' . htmlspecialchars($meaning_found['transliteration']) . '</em><br>';
                         }
                         echo htmlspecialchars($meaning_found['meaning']);
                         if (!empty($meaning_found['grammar'])) {
                             echo '<br><small>(' . htmlspecialchars($meaning_found['grammar']) . ')</small>';
                         }
                         echo '</span>'; // .word-tooltip
                         echo '</span>'; // .word-meaning
                     } else {
                         echo htmlspecialchars($word) . ' ';
                     }
                 }
                 ?>
            </div>

            <?php
            // Display default translation
            $default_translation = null;
            foreach ($translations as $trans) {
                if ($trans['is_default']) {
                    $default_translation = $trans;
                    break;
                }
            }
            if ($default_translation): ?>
                <div class="translation-text">
                    <?php echo htmlspecialchars($default_translation['text']); ?>
                </div>
            <?php endif; ?>

            <?php
            // Display default tafsir
            $default_tafsir = null;
             foreach ($tafasir as $tf) {
                if ($tf['is_default']) {
                    $default_tafsir = $tf;
                    break;
                }
            }
            if ($default_tafsir): ?>
                <div class="tafsir-text">
                    <strong><?php echo htmlspecialchars($default_tafsir['title'] ?? 'Tafsir'); ?>:</strong>
                    <?php echo nl2br(htmlspecialchars($default_tafsir['text'])); ?>
                </div>
            <?php endif; ?>

            <div class="ayah-actions">
                 <?php if (has_role('user')): ?>
                     <form action="" method="post" style="display:inline-block;">
                         <input type="hidden" name="action" value="<?php echo $is_bookmarked ? 'remove_bookmark' : 'add_bookmark'; ?>">
                         <input type="hidden" name="ayah_id" value="<?php echo $ayah['id']; ?>">
                          <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                         <button type="submit" title="<?php echo $is_bookmarked ? 'Remove Bookmark' : 'Add Bookmark'; ?>">
                             <i class="fas fa-bookmark<?php echo $is_bookmarked ? ' text-primary' : ''; ?>"></i>
                         </button>
                     </form>
                     <button title="Add Note" onclick="showNoteForm(<?php echo $ayah['id']; ?>)"><i class="fas fa-sticky-note"></i></button>
                     <button title="Suggest Content" onclick="showSuggestForm(<?php echo $ayah['id']; ?>)"><i class="fas fa-lightbulb"></i></button>
                     <button title="Mark Hifz Progress" onclick="showHifzForm(<?php echo $ayah['surah_id']; ?>, <?php echo $ayah['id']; ?>, '<?php echo htmlspecialchars($ayah['english_name']); ?> (<?php echo htmlspecialchars($ayah['arabic_name']); ?>)', <?php echo $ayah['ayah_number']; ?>)"><i class="fas fa-check-circle"></i></button>
                 <?php endif; ?>
                 <button title="Play Audio" onclick="playAyah(document.getElementById('ayah-<?php echo $ayah['ayah_number']; ?>'))"><i class="fas fa-volume-up"></i></button>
                 <!-- Add buttons for viewing all translations/tafasir/word meanings -->
                 <a href="?action=view_content&type=translation&ayah_id=<?php echo $ayah['id']; ?>" title="View Translations"><i class="fas fa-language"></i></a>
                 <a href="?action=view_content&type=tafsir&ayah_id=<?php echo $ayah['id']; ?>" title="View Tafasir"><i class="fas fa-book-open"></i></a>
                 <a href="?action=view_content&type=word_meaning&ayah_id=<?php echo $ayah['id']; ?>" title="View Word Meanings"><i class="fas fa-font"></i></a>
            </div>
        </div>
    <?php endforeach; ?>

     <!-- Modals/Forms (Same as view_surah) -->
     <div id="note-form-modal" style="display:none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.5); z-index: 1001;">
         <h3>Add Note</h3>
         <form action="" method="post">
             <input type="hidden" name="action" value="add_note">
             <input type="hidden" name="ayah_id" id="note-ayah-id" value="">
              <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
             <div class="form-group">
                 <label for="note-text">Note:</label>
                 <textarea id="note-text" name="note" rows="4" cols="50" required></textarea>
             </div>
             <div class="form-actions">
                 <button type="submit">Save Note</button>
                 <button type="button" onclick="document.getElementById('note-form-modal').style.display='none';">Cancel</button>
             </div>
         </form>
     </div>

     <div id="suggest-form-modal" style="display:none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.5); z-index: 1001;">
         <h3>Suggest Content</h3>
         <form action="" method="post">
             <input type="hidden" name="action" value="suggest_content">
             <input type="hidden" name="ayah_id" id="suggest-ayah-id" value="">
              <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
             <div class="form-group">
                 <label for="content-type">Content Type:</label>
                 <select id="content-type" name="content_type" required>
                     <option value="">Select Type</option>
                     <option value="translation">Translation</option>
                     <option value="tafsir">Tafsir</option>
                     <option value="word_meaning">Word Meaning</option>
                 </select>
             </div>
              <div class="form-group" id="word-index-group" style="display:none;">
                 <label for="word-index">Word Index (0-based):</label>
                 <input type="number" id="word-index" name="word_index" min="0">
             </div>
             <div class="form-group">
                 <label for="suggested-text">Suggested Text:</label>
                 <textarea id="suggested-text" name="suggested_text" rows="6" cols="50" required></textarea>
             </div>
             <div class="form-actions">
                 <button type="submit">Submit Suggestion</button>
                 <button type="button" onclick="document.getElementById('suggest-form-modal').style.display='none';">Cancel</button>
             </div>
         </form>
     </div>

     <div id="hifz-form-modal" style="display:none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.5); z-index: 1001;">
         <h3>Mark Hifz Progress</h3>
         <form action="" method="post">
             <input type="hidden" name="action" value="update_hifz_progress">
             <input type="hidden" name="surah_id" id="hifz-surah-id" value="">
             <input type="hidden" name="ayah_id" id="hifz-ayah-id" value="">
              <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
             <p>Mark Surah <span id="hifz-surah-name"></span> up to Ayah <span id="hifz-ayah-number"></span> as memorized.</p>
             <div class="form-actions">
                 <button type="submit">Confirm Progress</button>
                 <button type="button" onclick="document.getElementById('hifz-form-modal').style.display='none';">Cancel</button>
             </div>
         </form>
     </div>

     <script>
         function showNoteForm(ayahId) {
             document.getElementById('note-ayah-id').value = ayahId;
             document.getElementById('note-form-modal').style.display = 'block';
         }
         function showSuggestForm(ayahId) {
             document.getElementById('suggest-ayah-id').value = ayahId;
             document.getElementById('suggest-form-modal').style.display = 'block';
         }
          document.getElementById('content-type').addEventListener('change', function() {
              const wordIndexGroup = document.getElementById('word-index-group');
              if (this.value === 'word_meaning') {
                  wordIndexGroup.style.display = 'block';
                  document.getElementById('word-index').setAttribute('required', 'required');
              } else {
                  wordIndexGroup.style.display = 'none';
                  document.getElementById('word-index').removeAttribute('required');
              }
          });
         function showHifzForm(surahId, ayahId, surahName, ayahNumber) {
             document.getElementById('hifz-surah-id').value = surahId;
             document.getElementById('hifz-ayah-id').value = ayahId;
             document.getElementById('hifz-surah-name').textContent = surahName;
             document.getElementById('hifz-ayah-number').textContent = ayahNumber;
             document.getElementById('hifz-form-modal').style.display = 'block';
         }
     </script>

    <?php
    render_footer();
}


function render_search() {
    render_header("Search Quran");
    $query = sanitize_input($_GET['query'] ?? '');
    $surah_filter = (int)($_GET['surah'] ?? 0);
    $juz_filter = (int)($_GET['juz'] ?? 0);
    $content_type_filter = sanitize_input($_GET['content_type'] ?? 'quran'); // quran, translation, tafsir, word_meaning, notes

    $surahs = get_surahs();
    $juz_list = get_juz_list();
    $results = [];

    if (!empty($query)) {
        switch ($content_type_filter) {
            case 'quran':
                $results = search_quran($query, $surah_filter > 0 ? get_surah_id_by_surah_ayah($surah_filter, 1) : null, $juz_filter > 0 ? $juz_filter : null);
                break;
            case 'translation':
            case 'tafsir':
            case 'word_meaning':
            case 'notes':
                 $results = search_content($query, $content_type_filter, $surah_filter > 0 ? get_surah_id_by_surah_ayah($surah_filter, 1) : null, $juz_filter > 0 ? $juz_filter : null);
                 break;
        }
    }

    ?>
    <div class="card search-form">
        <h2>Search Quran and Content</h2>
        <form action="" method="get">
            <input type="hidden" name="action" value="search">
            <div class="form-group">
                <label for="query">Search Term:</label>
                <input type="text" id="query" name="query" value="<?php echo htmlspecialchars($query); ?>" required>
            </div>
             <div class="form-group">
                 <label for="content_type">Search In:</label>
                 <select name="content_type" id="content_type">
                     <option value="quran" <?php echo $content_type_filter === 'quran' ? 'selected' : ''; ?>>Quran Text (Arabic/Urdu)</option>
                     <option value="translation" <?php echo $content_type_filter === 'translation' ? 'selected' : ''; ?>>Translations</option>
                     <option value="tafsir" <?php echo $content_type_filter === 'tafsir' ? 'selected' : ''; ?>>Tafasir</option>
                     <option value="word_meaning" <?php echo $content_type_filter === 'word_meaning' ? 'selected' : ''; ?>>Word Meanings</option>
                      <?php if (has_role('user')): ?>
                         <option value="notes" <?php echo $content_type_filter === 'notes' ? 'selected' : ''; ?>>My Notes</option>
                     <?php endif; ?>
                 </select>
             </div>
            <div class="form-group">
                <label for="surah">Filter by Surah:</label>
                <select name="surah" id="surah">
                    <option value="0">All Surahs</option>
                    <?php foreach ($surahs as $surah): ?>
                        <option value="<?php echo $surah['surah_number']; ?>" <?php echo $surah_filter == $surah['surah_number'] ? 'selected' : ''; ?>>
                            <?php echo $surah['surah_number']; ?>. <?php echo htmlspecialchars($surah['english_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
             <div class="form-group">
                <label for="juz">Filter by Juz:</label>
                <select name="juz" id="juz">
                    <option value="0">All Juz</option>
                    <?php foreach ($juz_list as $juz): ?>
                        <option value="<?php echo $juz; ?>" <?php echo $juz_filter == $juz ? 'selected' : ''; ?>>
                            Juz <?php echo $juz; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit">Search</button>
            </div>
        </form>
    </div>

    <?php if (!empty($query)): ?>
        <div class="card search-results">
            <h3>Search Results for "<?php echo htmlspecialchars($query); ?>"</h3>
            <?php if (empty($results)): ?>
                <p>No results found.</p>
            <?php else: ?>
                <p>Found <?php echo count($results); ?> results.</p>
                <ul>
                    <?php foreach ($results as $result): ?>
                        <li>
                            <?php
                            $ayah_link = '?action=view_surah&surah=' . $result['surah_id'] . '#ayah-' . $result['ayah_number'];
                            echo '<a href="' . $ayah_link . '">';
                            echo 'Surah ' . htmlspecialchars($result['english_name']) . ' (' . htmlspecialchars($result['arabic_name']) . ') Ayah ' . $result['ayah_number'];
                            echo '</a>';

                            echo '<br>';

                            // Display relevant text snippet with highlighting
                            $text_to_display = '';
                            switch ($content_type_filter) {
                                case 'quran':
                                    // Highlight in both Arabic and Urdu
                                    $arabic_snippet = highlight_search_term($result['arabic_text'], $query);
                                    $urdu_snippet = highlight_search_term($result['urdu_translation'], $query);
                                    echo '<div class="arabic-text" style="font-size: 1.2em;">' . $arabic_snippet . '</div>';
                                    echo '<div class="translation-text" style="font-size: 1em;">' . $urdu_snippet . '</div>';
                                    break;
                                case 'translation':
                                    $text_to_display = highlight_search_term($result['text'], $query);
                                    echo '<p><em>Translation:</em> ' . $text_to_display . '</p>';
                                    break;
                                case 'tafsir':
                                    $text_to_display = highlight_search_term($result['text'], $query);
                                    echo '<p><em>Tafsir (' . htmlspecialchars($result['title'] ?? 'Untitled') . '):</em> ' . $text_to_display . '</p>';
                                    break;
                                case 'word_meaning':
                                    $text_to_display = highlight_search_term($result['meaning'], $query);
                                    echo '<p><em>Word Meaning (' . htmlspecialchars($result['arabic_word']) . '):</em> ' . $text_to_display . '</p>';
                                    break;
                                case 'notes':
                                     $text_to_display = highlight_search_term($result['note'], $query);
                                     echo '<p><em>My Note:</em> ' . $text_to_display . '</p>';
                                     break;
                            }
                            ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php
    render_footer();
}

function highlight_search_term($text, $term) {
    if (empty($term)) return htmlspecialchars($text);
    // Escape special characters in the term for regex
    $escaped_term = preg_quote($term, '/');
    // Use word boundaries (\b) to match whole words, case-insensitive (i)
    // Or use \p{L} for any letter character in Unicode for better Arabic/Urdu matching
    $pattern = '/(' . $escaped_term . ')/ui'; // u for unicode, i for case-insensitive
    $highlighted_text = preg_replace($pattern, '<mark>$1</mark>', htmlspecialchars($text));
    return $highlighted_text;
}


function render_admin_panel() {
    if (!has_role('admin')) {
        redirect('?action=home'); // Or show permission denied error
    }
    render_header("Admin Panel");
    $view = $_GET['view'] ?? 'overview';
    ?>
    <div class="admin-panel">
        <div class="admin-sidebar">
            <h3>Admin Menu</h3>
            <ul>
                <li><a href="?action=admin&view=overview">Overview</a></li>
                <li><a href="?action=admin&view=users">User Management</a></li>
                <li><a href="?action=admin&view=suggestions">Content Suggestions</a></li>
                <li><a href="?action=admin&view=content">Content Management</a></li>
                <li><a href="?action=admin&view=database">Database</a></li>
                <li><a href="?action=admin&view=settings">Site Settings</a></li>
            </ul>
        </div>
        <div class="admin-content">
            <?php
            switch ($view) {
                case 'overview':
                    echo "<h2>Admin Overview</h2>";
                    // Add site statistics here (user count, content count, etc.)
                    $pdo = db_connect();
                    $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                    $ayah_count = $pdo->query("SELECT COUNT(*) FROM ayahs")->fetchColumn();
                    $translation_count = $pdo->query("SELECT COUNT(*) FROM translations WHERE status = 'approved'")->fetchColumn();
                    $tafsir_count = $pdo->query("SELECT COUNT(*) FROM tafasir WHERE status = 'approved'")->fetchColumn();
                    $wm_count = $pdo->query("SELECT COUNT(*) FROM word_meanings WHERE status = 'approved'")->fetchColumn();
                    $pending_suggestions_count = $pdo->query("SELECT COUNT(*) FROM content_suggestions WHERE status = 'pending'")->fetchColumn();

                    echo "<p>Total Users: " . $user_count . "</p>";
                    echo "<p>Total Ayahs: " . $ayah_count . "</p>";
                    echo "<p>Approved Translations: " . $translation_count . "</p>";
                    echo "<p>Approved Tafasir: " . $tafsir_count . "</p>";
                    echo "<p>Approved Word Meanings: " . $wm_count . "</p>";
                    echo "<p>Pending Suggestions: " . $pending_suggestions_count . "</p>";

                    break;
                case 'users':
                    echo "<h2>User Management</h2>";
                    $users = get_all_users();
                    if (empty($users)) {
                        echo "<p>No users found.</p>";
                    } else {
                        echo "<table>";
                        echo "<thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Created At</th><th>Last Login</th><th>Actions</th></tr></thead>";
                        echo "<tbody>";
                        foreach ($users as $user) {
                            echo "<tr>";
                            echo "<td>" . $user['id'] . "</td>";
                            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                            echo '<td>';
                            // Role update form
                            echo '<form action="" method="post" style="display:inline-block;">';
                            echo '<input type="hidden" name="action" value="update_user_role">';
                            echo '<input type="hidden" name="user_id" value="' . $user['id'] . '">';
                             echo '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
                            echo '<select name="role" onchange="this.form.submit()">';
                            $roles = ['public', 'user', 'ulama', 'admin'];
                            foreach ($roles as $role) {
                                echo '<option value="' . $role . '"' . ($user['role'] === $role ? ' selected' : '') . '>' . ucfirst($role) . '</option>';
                            }
                            echo '</select>';
                            echo '</form>';
                            echo '</td>';
                            echo "<td>" . $user['created_at'] . "</td>";
                            echo "<td>" . ($user['last_login'] ?? 'Never') . "</td>";
                            echo '<td>';
                            // Delete user form (with confirmation)
                            if ($user['id'] != 1 && $user['id'] != get_user_id()) { // Prevent deleting primary admin or self
                                echo '<form action="" method="post" style="display:inline-block; margin-left: 10px;" onsubmit="return confirm(\'Are you sure you want to delete user ' . htmlspecialchars($user['username']) . '?\');">';
                                echo '<input type="hidden" name="action" value="delete_user">';
                                echo '<input type="hidden" name="user_id" value="' . $user['id'] . '">';
                                 echo '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
                                echo '<button type="submit" class="btn-danger btn-sm"><i class="fas fa-trash"></i> Delete</button>';
                                echo '</form>';
                            }
                            echo '</td>';
                            echo "</tr>";
                        }
                        echo "</tbody>";
                        echo "</table>";
                    }
                    break;
                case 'suggestions':
                    echo "<h2>Content Suggestions (Pending)</h2>";
                    $suggestions = get_content_suggestions('pending');
                    if (empty($suggestions)) {
                        echo "<p>No pending suggestions.</p>";
                    } else {
                        echo "<table>";
                        echo "<thead><tr><th>ID</th><th>Type</th><th>Ayah</th><th>Suggested By</th><th>Text</th><th>Created At</th><th>Actions</th></tr></thead>";
                        echo "<tbody>";
                        foreach ($suggestions as $suggestion) {
                            echo "<tr>";
                            echo "<td>" . $suggestion['id'] . "</td>";
                            echo "<td>" . htmlspecialchars($suggestion['content_type']) . "</td>";
                            echo '<td>';
                            if ($suggestion['ayah_id']) {
                                echo '<a href="?action=view_surah&surah=' . $suggestion['surah_id'] . '#ayah-' . $suggestion['ayah_number'] . '">';
                                echo 'S' . $suggestion['surah_id'] . ':A' . $suggestion['ayah_number'];
                                if ($suggestion['content_type'] === 'word_meaning' && $suggestion['word_index'] !== null) {
                                    echo ' (Word ' . $suggestion['word_index'] . ')';
                                }
                                echo '</a>';
                            } else {
                                echo 'N/A'; // Should not happen if ayah_id is required for suggestions
                            }
                            echo '</td>';
                            echo "<td>" . htmlspecialchars($suggestion['suggested_by']) . "</td>";
                            echo "<td>" . nl2br(htmlspecialchars(substr($suggestion['suggested_text'], 0, 200))) . (strlen($suggestion['suggested_text']) > 200 ? '...' : '') . "</td>";
                            echo "<td>" . $suggestion['created_at'] . "</td>";
                            echo '<td>';
                            // Approve form
                            echo '<form action="" method="post" style="display:inline-block;">';
                            echo '<input type="hidden" name="action" value="approve_suggestion">';
                            echo '<input type="hidden" name="suggestion_id" value="' . $suggestion['id'] . '">';
                             echo '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
                            echo '<button type="submit" class="btn-success btn-sm"><i class="fas fa-check"></i> Approve</button>';
                            echo '</form>';
                            // Reject form
                            echo '<form action="" method="post" style="display:inline-block; margin-left: 5px;" onsubmit="return confirm(\'Are you sure you want to reject this suggestion?\');">';
                            echo '<input type="hidden" name="action" value="reject_suggestion">';
                            echo '<input type="hidden" name="suggestion_id" value="' . $suggestion['id'] . '">';
                             echo '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
                            echo '<button type="submit" class="btn-danger btn-sm"><i class="fas fa-times"></i> Reject</button>';
                            echo '</form>';
                            echo '</td>';
                            echo "</tr>";
                        }
                        echo "</tbody>";
                        echo "</table>";
                    }

                    echo "<h3>Reviewed Suggestions</h3>";
                    $reviewed_suggestions = get_content_suggestions('all'); // Get all to show approved/rejected
                    $reviewed_suggestions = array_filter($reviewed_suggestions, function($s) { return $s['status'] !== 'pending'; });

                     if (empty($reviewed_suggestions)) {
                        echo "<p>No suggestions have been reviewed yet.</p>";
                    } else {
                        echo "<table>";
                        echo "<thead><tr><th>ID</th><th>Type</th><th>Ayah</th><th>Suggested By</th><th>Status</th><th>Reviewed By</th><th>Reviewed At</th></tr></thead>";
                        echo "<tbody>";
                        foreach ($reviewed_suggestions as $suggestion) {
                            echo "<tr>";
                            echo "<td>" . $suggestion['id'] . "</td>";
                            echo "<td>" . htmlspecialchars($suggestion['content_type']) . "</td>";
                            echo '<td>';
                            if ($suggestion['ayah_id']) {
                                echo '<a href="?action=view_surah&surah=' . $suggestion['surah_id'] . '#ayah-' . $suggestion['ayah_number'] . '">';
                                echo 'S' . $suggestion['surah_id'] . ':A' . $suggestion['ayah_number'];
                                if ($suggestion['content_type'] === 'word_meaning' && $suggestion['word_index'] !== null) {
                                    echo ' (Word ' . $suggestion['word_index'] . ')';
                                }
                                echo '</a>';
                            } else {
                                echo 'N/A';
                            }
                            echo '</td>';
                            echo "<td>" . htmlspecialchars($suggestion['suggested_by']) . "</td>";
                            echo "<td>" . htmlspecialchars($suggestion['status']) . "</td>";
                            echo "<td>" . ($suggestion['reviewed_by'] ? htmlspecialchars($suggestion['reviewed_by']) : 'N/A') . "</td>"; // Need to fetch reviewer username
                            echo "<td>" . ($suggestion['reviewed_at'] ?? 'N/A') . "</td>";
                            echo "</tr>";
                        }
                        echo "</tbody>";
                        echo "</table>";
                    }

                    break;
                case 'content':
                    echo "<h2>Content Management</h2>";
                    // Form to add new content directly (for Admin/Ulama)
                    echo "<h3>Add New Content</h3>";
                    $surahs = get_surahs();
                    $all_ayahs = $pdo->query("SELECT id, surah_id, ayah_number FROM ayahs ORDER BY surah_id, ayah_number")->fetchAll();

                    echo '<form action="" method="post">';
                    echo '<input type="hidden" name="action" value="add_content">';
                     echo '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
                    echo '<div class="form-group">';
                    echo '<label for="add-content-type">Content Type:</label>';
                    echo '<select id="add-content-type" name="content_type" required>';
                    echo '<option value="">Select Type</option>';
                    echo '<option value="translation">Translation</option>';
                    echo '<option value="tafsir">Tafsir</option>';
                    echo '<option value="word_meaning">Word Meaning</option>';
                    echo '</select>';
                    echo '</div>';
                     echo '<div class="form-group">';
                    echo '<label for="add-content-surah">Surah:</label>';
                    echo '<select name="surah_id" id="add-content-surah" required>';
                    echo '<option value="">Select Surah</option>';
                    foreach ($surahs as $surah) {
                        echo '<option value="' . $surah['id'] . '">Surah ' . htmlspecialchars($surah['english_name']) . ' (' . htmlspecialchars($surah['arabic_name']) . ')</option>';
                    }
                    echo '</select>';
                    echo '</div>';
                    echo '<div class="form-group">';
                    echo '<label for="add-content-ayah">Ayah:</label>';
                    echo '<select name="ayah_id" id="add-content-ayah" required>';
                    echo '<option value="">Select Ayah</option>';
                    foreach ($all_ayahs as $ayah) {
                         echo '<option value="' . $ayah['id'] . '" data-surah-id="' . $ayah['surah_id'] . '" style="display:none;">Ayah ' . $ayah['ayah_number'] . '</option>';
                    }
                    echo '</select>';
                    echo '</div>';

                     // Fields specific to content type (initially hidden)
                     echo '<div id="add-translation-fields" style="display:none;">';
                     echo '<div class="form-group"><label for="trans-language">Language:</label><input type="text" id="trans-language" name="language" value="ur"></div>';
                     echo '<div class="form-group"><label for="trans-text">Translation Text:</label><textarea id="trans-text" name="text" rows="4" required></textarea></div>';
                     echo '<div class="form-group"><label><input type="checkbox" name="is_default" value="1"> Set as Default</label></div>';
                     echo '</div>';

                     echo '<div id="add-tafsir-fields" style="display:none;">';
                     echo '<div class="form-group"><label for="tafsir-title">Title:</label><input type="text" id="tafsir-title" name="title"></div>';
                     echo '<div class="form-group"><label for="tafsir-text">Tafsir Text:</label><textarea id="tafsir-text" name="text" rows="6" required></textarea></div>';
                     echo '<div class="form-group"><label><input type="checkbox" name="is_default" value="1"> Set as Default</label></div>';
                     echo '</div>';

                     echo '<div id="add-wm-fields" style="display:none;">';
                     echo '<div class="form-group"><label for="wm-word-index">Word Index (0-based):</label><input type="number" id="wm-word-index" name="word_index" min="0"></div>';
                     echo '<div class="form-group"><label for="wm-arabic-word">Arabic Word:</label><input type="text" id="wm-arabic-word" name="arabic_word"></div>';
                     echo '<div class="form-group"><label for="wm-transliteration">Transliteration:</label><input type="text" id="wm-transliteration" name="transliteration"></div>';
                     echo '<div class="form-group"><label for="wm-meaning">Meaning:</label><textarea id="wm-meaning" name="meaning" rows="3" required></textarea></div>';
                     echo '<div class="form-group"><label for="wm-grammar">Grammar Notes:</label><textarea id="wm-grammar" name="grammar" rows="2"></textarea></div>';
                     echo '<div class="form-group"><label><input type="checkbox" name="is_default" value="1"> Set as Default</label></div>';
                     echo '</div>';


                    echo '<div class="form-actions">';
                    echo '<button type="submit">Add Content</button>';
                    echo '</div>';
                    echo '</form>';

                     // JS to populate ayah select and show/hide fields
                     echo '<script>
                        document.getElementById("add-content-surah").addEventListener("change", function() {
                            const surahId = this.value;
                            const ayahSelect = document.getElementById("add-content-ayah");
                            const options = ayahSelect.querySelectorAll("option");

                            options.forEach(option => {
                                if (option.value === "") {
                                    option.style.display = "block"; // Keep the "Select Ayah" option visible
                                } else {
                                    if (option.dataset.surahId === surahId) {
                                        option.style.display = "block";
                                    } else {
                                        option.style.display = "none";
                                    }
                                }
                            });
                            ayahSelect.value = ""; // Reset selected ayah
                        });

                        document.getElementById("add-content-type").addEventListener("change", function() {
                            document.getElementById("add-translation-fields").style.display = "none";
                            document.getElementById("add-tafsir-fields").style.display = "none";
                            document.getElementById("add-wm-fields").style.display = "none";

                            // Reset required attributes
                            document.querySelectorAll("#add-translation-fields [required], #add-tafsir-fields [required], #add-wm-fields [required]").forEach(el => el.removeAttribute("required"));

                            const selectedType = this.value;
                            if (selectedType === "translation") {
                                document.getElementById("add-translation-fields").style.display = "block";
                                document.querySelectorAll("#add-translation-fields [required]").forEach(el => el.setAttribute("required", "required"));
                            } else if (selectedType === "tafsir") {
                                document.getElementById("add-tafsir-fields").style.display = "block";
                                document.querySelectorAll("#add-tafsir-fields [required]").forEach(el => el.setAttribute("required", "required"));
                            } else if (selectedType === "word_meaning") {
                                document.getElementById("add-wm-fields").style.display = "block";
                                document.querySelectorAll("#add-wm-fields [required]").forEach(el => el.setAttribute("required", "required"));
                            }
                        });
                    </script>';


                    echo "<h3>Existing Content (Approved)</h3>";
                    // Display lists of existing approved content with edit/delete options
                    // This would require fetching all approved content and displaying it,
                    // potentially with pagination or filtering.
                    // For simplicity, we'll just show counts and link to Ayah view.
                    echo "<p>View and manage content by navigating to specific Ayahs or using the search function.</p>";
                    echo "<p>Total Approved Translations: " . $translation_count . "</p>";
                    echo "<p>Total Approved Tafasir: " . $tafsir_count . "</p>";
                    echo "<p>Total Approved Word Meanings: " . $wm_count . "</p>";

                    // Optional: Add tables here to list content with edit/delete buttons
                    // This would be very long tables, so linking to Ayah view is more practical.

                    break;
                case 'database':
                    echo "<h2>Database Management</h2>";
                    echo "<h3>Backup</h3>";
                    echo '<form action="" method="post">';
                    echo '<input type="hidden" name="action" value="backup_db">';
                     echo '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
                    echo '<button type="submit">Create Database Backup</button>';
                    echo '</form>';

                    echo "<h3>Restore</h3>";
                    $backup_files = get_backup_files();
                    if (empty($backup_files)) {
                        echo "<p>No backups found.</p>";
                    } else {
                        echo '<form action="" method="post" onsubmit="return confirm(\'WARNING: Restoring will overwrite the current database. Are you sure?\');">';
                        echo '<input type="hidden" name="action" value="restore_db">';
                         echo '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
                        echo '<div class="form-group">';
                        echo '<label for="backup_file">Select Backup File:</label>';
                        echo '<select name="backup_file" id="backup_file" required>';
                        echo '<option value="">Select a backup file</option>';
                        foreach ($backup_files as $file) {
                            echo '<option value="' . htmlspecialchars($file) . '">' . htmlspecialchars($file) . '</option>';
                        }
                        echo '</select>';
                        echo '</div>';
                        echo '<div class="form-actions">';
                        echo '<button type="submit" class="btn-danger">Restore Database</button>';
                        echo '</div>';
                        echo '</form>';
                    }

                    break;
                case 'settings':
                    echo "<h2>Site Settings</h2>";
                    // Site-wide settings form (e.g., Ulama direct publish)
                    $ulama_direct_publish = get_site_setting('ulama_direct_publish', 'approved'); // Default to approved

                    echo '<form action="" method="post">';
                    echo '<input type="hidden" name="action" value="set_site_setting">';
                     echo '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
                    echo '<div class="form-group">';
                    echo '<label for="ulama_direct_publish">Ulama Direct Publish Status:</label>';
                    echo '<select name="setting_value" id="ulama_direct_publish">';
                    echo '<option value="approved"' . ($ulama_direct_publish === 'approved' ? ' selected' : '') . '>Approved (Publish Directly)</option>';
                    echo '<option value="pending"' . ($ulama_direct_publish === 'pending' ? ' selected' : '') . '>Pending (Require Admin Review)</option>';
                    echo '</select>';
                    echo '<input type="hidden" name="setting_key" value="ulama_direct_publish">';
                    echo '</div>';
                    echo '<div class="form-actions">';
                    echo '<button type="submit">Save Setting</button>';
                    echo '</div>';
                    echo '</form>';

                    break;
                default:
                    render_admin_overview(); // Default to overview
                    break;
            }
            ?>
        </div>
    </div>
    <?php
    render_footer();
}

function render_view_content($type, $ayah_id) {
    $ayah = get_ayah_details_by_id($ayah_id);
    if (!$ayah) {
        render_header("Ayah Not Found");
        echo "<div class='error'>Ayah not found.</div>";
        render_footer();
        return;
    }

    render_header("View " . ucfirst($type) . " for S" . $ayah['surah_number'] . ":A" . $ayah['ayah_number']);

    echo '<h2>' . ucfirst($type) . ' for Surah ' . htmlspecialchars($ayah['english_name']) . ' (' . htmlspecialchars($ayah['arabic_name']) . ') Ayah ' . $ayah['ayah_number'] . '</h2>';
    echo '<p><a href="?action=view_surah&surah=' . $ayah['surah_number'] . '#ayah-' . $ayah['ayah_number'] . '">&larr; Back to Ayah</a></p>';

    $content_items = [];
    $table_headers = [];
    $content_type_singular = '';

    switch ($type) {
        case 'translation':
            $content_items = get_translations($ayah_id, has_role('admin') ? 'all' : 'approved'); // Admins see all statuses
            $table_headers = ['ID', 'Language', 'Text', 'Status', 'Version', 'Default', 'Contributor', 'Actions'];
            $content_type_singular = 'translation';
            break;
        case 'tafsir':
            $content_items = get_tafasir($ayah_id, has_role('admin') ? 'all' : 'approved');
            $table_headers = ['ID', 'Title', 'Text', 'Status', 'Version', 'Default', 'Contributor', 'Actions'];
            $content_type_singular = 'tafsir';
            break;
        case 'word_meaning':
            $content_items = get_word_meanings($ayah_id, has_role('admin') ? 'all' : 'approved');
            $table_headers = ['ID', 'Word Index', 'Arabic Word', 'Transliteration', 'Meaning', 'Grammar', 'Status', 'Version', 'Default', 'Contributor', 'Actions'];
            $content_type_singular = 'word_meaning';
            break;
        default:
            echo "<div class='error'>Invalid content type specified.</div>";
            render_footer();
            return;
    }

    if (empty($content_items)) {
        echo "<p>No " . htmlspecialchars($type) . " found for this Ayah.</p>";
    } else {
        echo "<table>";
        echo "<thead><tr>";
        foreach ($table_headers as $header) {
            echo "<th>" . htmlspecialchars($header) . "</th>";
        }
        echo "</tr></thead>";
        echo "<tbody>";
        foreach ($content_items as $item) {
            echo "<tr>";
            echo "<td>" . $item['id'] . "</td>";
            if ($type === 'translation') {
                echo "<td>" . htmlspecialchars($item['language']) . "</td>";
                echo "<td>" . nl2br(htmlspecialchars($item['text'])) . "</td>";
            } elseif ($type === 'tafsir') {
                echo "<td>" . htmlspecialchars($item['title'] ?? 'Untitled') . "</td>";
                echo "<td>" . nl2br(htmlspecialchars($item['text'])) . "</td>";
            } elseif ($type === 'word_meaning') {
                echo "<td>" . $item['word_index'] . "</td>";
                echo "<td>" . htmlspecialchars($item['arabic_word']) . "</td>";
                echo "<td>" . htmlspecialchars($item['transliteration'] ?? '') . "</td>";
                echo "<td>" . nl2br(htmlspecialchars($item['meaning'])) . "</td>";
                echo "<td>" . nl2br(htmlspecialchars($item['grammar'] ?? '')) . "</td>";
            }
            echo "<td>" . htmlspecialchars($item['status']) . "</td>";
            echo "<td>" . $item['version'] . "</td>";
            echo '<td>' . ($item['is_default'] ? 'Yes' : 'No') . '</td>';
            // Fetch contributor username
            $contributor_username = 'N/A';
            if ($item['contributor_id']) {
                 $pdo = db_connect();
                 $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                 $stmt->execute([$item['contributor_id']]);
                 $contributor = $stmt->fetch();
                 if ($contributor) $contributor_username = htmlspecialchars($contributor['username']);
            }
            echo "<td>" . $contributor_username . "</td>";

            echo '<td>';
            if (has_role('admin')) { // Only admin can edit/delete approved content
                 // Edit form (could be a modal or separate page)
                 // Using a simple link for now, a real edit would need a form
                 echo '<a href="?action=admin&view=content_edit&type=' . $content_type_singular . '&id=' . $item['id'] . '" class="btn btn-secondary btn-sm"><i class="fas fa-edit"></i> Edit</a>';

                 // Delete form (with confirmation)
                 echo '<form action="" method="post" style="display:inline-block; margin-left: 5px;" onsubmit="return confirm(\'Are you sure you want to delete this ' . $content_type_singular . '?\');">';
                 echo '<input type="hidden" name="action" value="delete_content">';
                 echo '<input type="hidden" name="content_type" value="' . $content_type_singular . '">';
                 echo '<input type="hidden" name="content_id" value="' . $item['id'] . '">';
                  echo '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
                 echo '<button type="submit" class="btn-danger btn-sm"><i class="fas fa-trash"></i> Delete</button>';
                 echo '</form>';
            }
            echo '</td>';
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
    }

    render_footer();
}

function render_admin_content_edit($type, $id) {
     if (!has_role('admin')) {
        redirect('?action=home');
    }
    render_header("Edit Content");

    $pdo = db_connect();
    $item = null;
    $table = '';
    $content_type_singular = '';

    switch ($type) {
        case 'translation':
            $table = 'translations';
            $content_type_singular = 'translation';
            break;
        case 'tafsir':
            $table = 'tafasir';
            $content_type_singular = 'tafsir';
            break;
        case 'word_meaning':
            $table = 'word_meanings';
            $content_type_singular = 'word_meaning';
            break;
        default:
            echo "<div class='error'>Invalid content type specified.</div>";
            render_footer();
            return;
    }

    $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();

    if (!$item) {
        echo "<div class='error'>Content item not found.</div>";
        render_footer();
        return;
    }

    $ayah_details = get_ayah_details_by_id($item['ayah_id']);

    echo '<h2>Edit ' . ucfirst($content_type_singular) . ' for S' . $ayah_details['surah_number'] . ':A' . $ayah_details['ayah_number'] . '</h2>';
    echo '<p><a href="?action=view_content&type=' . $content_type_singular . '&ayah_id=' . $item['ayah_id'] . '">&larr; Back to Content List</a></p>';

    echo '<form action="" method="post">';
    echo '<input type="hidden" name="action" value="update_content">';
    echo '<input type="hidden" name="content_type" value="' . $content_type_singular . '">';
    echo '<input type="hidden" name="content_id" value="' . $item['id'] . '">';
     echo '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';

    if ($type === 'translation') {
        echo '<div class="form-group"><label for="edit-trans-language">Language:</label><input type="text" id="edit-trans-language" name="language" value="' . htmlspecialchars($item['language']) . '" required></div>';
        echo '<div class="form-group"><label for="edit-trans-text">Translation Text:</label><textarea id="edit-trans-text" name="text" rows="6" required>' . htmlspecialchars($item['text']) . '</textarea></div>';
        echo '<div class="form-group"><label for="edit-trans-status">Status:</label><select id="edit-trans-status" name="status"><option value="pending"' . ($item['status'] === 'pending' ? ' selected' : '') . '>Pending</option><option value="approved"' . ($item['status'] === 'approved' ? ' selected' : '') . '>Approved</option><option value="rejected"' . ($item['status'] === 'rejected' ? ' selected' : '') . '>Rejected</option></select></div>';
        echo '<div class="form-group"><label><input type="checkbox" name="is_default" value="1" ' . ($item['is_default'] ? 'checked' : '') . '> Set as Default</label></div>';
    } elseif ($type === 'tafsir') {
        echo '<div class="form-group"><label for="edit-tafsir-title">Title:</label><input type="text" id="edit-tafsir-title" name="title" value="' . htmlspecialchars($item['title'] ?? '') . '"></div>';
        echo '<div class="form-group"><label for="edit-tafsir-text">Tafsir Text:</label><textarea id="edit-tafsir-text" name="text" rows="10" required>' . htmlspecialchars($item['text']) . '</textarea></div>';
        echo '<div class="form-group"><label for="edit-tafsir-status">Status:</label><select id="edit-tafsir-status" name="status"><option value="pending"' . ($item['status'] === 'pending' ? ' selected' : '') . '>Pending</option><option value="approved"' . ($item['status'] === 'approved' ? ' selected' : '') . '>Approved</option><option value="rejected"' . ($item['status'] === 'rejected' ? ' selected' : '') . '>Rejected</option></select></div>';
        echo '<div class="form-group"><label><input type="checkbox" name="is_default" value="1" ' . ($item['is_default'] ? 'checked' : '') . '> Set as Default</label></div>';
    } elseif ($type === 'word_meaning') {
        echo '<div class="form-group"><label for="edit-wm-word-index">Word Index (0-based):</label><input type="number" id="edit-wm-word-index" name="word_index" value="' . htmlspecialchars($item['word_index']) . '" min="0" required></div>';
        echo '<div class="form-group"><label for="edit-wm-arabic-word">Arabic Word:</label><input type="text" id="edit-wm-arabic-word" name="arabic_word" value="' . htmlspecialchars($item['arabic_word']) . '" required></div>';
        echo '<div class="form-group"><label for="edit-wm-transliteration">Transliteration:</label><input type="text" id="edit-wm-transliteration" name="transliteration" value="' . htmlspecialchars($item['transliteration'] ?? '') . '"></div>';
        echo '<div class="form-group"><label for="edit-wm-meaning">Meaning:</label><textarea id="edit-wm-meaning" name="meaning" rows="6" required>' . htmlspecialchars($item['meaning']) . '</textarea></div>';
        echo '<div class="form-group"><label for="edit-wm-grammar">Grammar Notes:</label><textarea id="edit-wm-grammar" name="grammar" rows="4">' . htmlspecialchars($item['grammar'] ?? '') . '</textarea></div>';
        echo '<div class="form-group"><label for="edit-wm-status">Status:</label><select id="edit-wm-status" name="status"><option value="pending"' . ($item['status'] === 'pending' ? ' selected' : '') . '>Pending</option><option value="approved"' . ($item['status'] === 'approved' ? ' selected' : '') . '>Approved</option><option value="rejected"' . ($item['status'] === 'rejected' ? ' selected' : '') . '>Rejected</option></select></div>';
        echo '<div class="form-group"><label><input type="checkbox" name="is_default" value="1" ' . ($item['is_default'] ? 'checked' : '') . '> Set as Default</label></div>';
    }

    echo '<div class="form-actions">';
    echo '<button type="submit">Save Changes</button>';
    echo '</div>';
    echo '</form>';


    render_footer();
}


// --- Main Application Flow ---
switch ($action) {
    case 'home':
        render_home();
        break;
    case 'login':
        if (is_logged_in()) {
            redirect('?action=dashboard');
        }
        render_login();
        break;
    case 'register':
        if (is_logged_in()) {
            redirect('?action=dashboard');
        }
        render_register();
        break;
    case 'logout':
        logout();
        redirect('?action=home');
        break;
    case 'dashboard':
        render_dashboard();
        break;
    case 'read':
        render_read();
        break;
    case 'view_surah':
        $surah_number = (int)($_GET['surah'] ?? 0);
        if ($surah_number > 0) {
            render_view_surah($surah_number);
        } else {
            redirect('?action=read'); // Redirect to read page if no surah specified
        }
        break;
    case 'view_juz':
         $juz_number = (int)($_GET['juz'] ?? 0);
        if ($juz_number > 0) {
            render_view_juz($juz_number);
        } else {
            redirect('?action=read'); // Redirect to read page if no juz specified
        }
        break;
    case 'search':
        render_search();
        break;
    case 'admin':
        render_admin_panel();
        break;
    case 'view_content':
         $type = sanitize_input($_GET['type'] ?? '');
         $ayah_id = (int)($_GET['ayah_id'] ?? 0);
         if (!empty($type) && $ayah_id > 0) {
             render_view_content($type, $ayah_id);
         } else {
             redirect('?action=read'); // Or show error
         }
         break;
    case 'admin&view=content_edit': // Handle the edit content action
         $type = sanitize_input($_GET['type'] ?? '');
         $id = (int)($_GET['id'] ?? 0);
         if (!empty($type) && $id > 0) {
             render_admin_content_edit($type, $id);
         } else {
             redirect('?action=admin&view=content'); // Redirect back if invalid params
         }
         break;
    case 'error':
        render_header("Error");
        echo "<div class='error'>An error occurred.</div>";
        render_footer();
        break;
    default:
        render_home(); // Default action
        break;
}

?>