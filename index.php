<?php
// Quranic Insights Platform - Single File PHP Application
// Author: Yasin Ullah
// Version: 1.0.0

// --- CONFIGURATION & INITIALIZATION ---
session_start();
define('DB_FILE', __DIR__ . '/quran_insights.sqlite');
define('DATA_AM_FILE', __DIR__ . '/data.AM'); // Path to your data.AM file
define('APP_NAME', 'Quranic Insights Platform');
define('APP_AUTHOR', 'Yasin Ullah');
define('APP_VERSION', '1.0.0');
define('DEFAULT_PASSWORD_ADMIN', 'admin123'); // Change in production

// --- DATABASE SETUP & CONNECTION ---
function get_db() {
    static $db = null;
    if ($db === null) {
        $db_exists = file_exists(DB_FILE);
        try {
            $db = new PDO('sqlite:' . DB_FILE);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            if (!$db_exists) {
                initialize_database($db);
            }
            // Ensure foreign key constraints are enabled for SQLite (important for ON DELETE CASCADE)
            $db->exec('PRAGMA foreign_keys = ON;');
        } catch (PDOException $e) {
            error_log("Database connection/initialization error: " . $e->getMessage());
            die("A critical database error occurred. Please check server logs or contact an administrator. Ensure the directory is writable by the web server for SQLite database creation.");
        }
    }
    return $db;
}

function initialize_database($db) {
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        user_id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        email TEXT UNIQUE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_admin INTEGER DEFAULT 0
    );");

    $db->exec("CREATE TABLE IF NOT EXISTS quran_text (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        surah_id INTEGER NOT NULL,
        ayah_id INTEGER NOT NULL,
        arabic_text TEXT NOT NULL,
        urdu_translation TEXT,
        UNIQUE(surah_id, ayah_id)
    );");

    $db->exec("CREATE TABLE IF NOT EXISTS surah_info (
        surah_id INTEGER PRIMARY KEY,
        surah_name_arabic TEXT,
        surah_name_english TEXT,
        revelation_place TEXT,
        total_ayahs INTEGER
    );");

    $db->exec("CREATE TABLE IF NOT EXISTS user_settings (
        user_id INTEGER PRIMARY KEY,
        last_read_surah INTEGER,
        last_read_ayah INTEGER,
        tilawat_lines_per_page INTEGER DEFAULT 10,
        tilawat_font_size INTEGER DEFAULT 36,
        tilawat_show_translation INTEGER DEFAULT 0,
        tilawat_view_mode TEXT DEFAULT 'paginated',
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    );");

    $db->exec("CREATE TABLE IF NOT EXISTS bookmarks (
        bookmark_id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        surah_id INTEGER NOT NULL,
        ayah_id INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (surah_id, ayah_id) REFERENCES quran_text(surah_id, ayah_id) ON DELETE CASCADE,
        UNIQUE(user_id, surah_id, ayah_id)
    );");

    $db->exec("CREATE TABLE IF NOT EXISTS notes (
        note_id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        surah_id INTEGER NOT NULL,
        ayah_id INTEGER NOT NULL,
        note_text TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (surah_id, ayah_id) REFERENCES quran_text(surah_id, ayah_id) ON DELETE CASCADE,
        UNIQUE(user_id, surah_id, ayah_id)
    );");

    $db->exec("CREATE TABLE IF NOT EXISTS hifz_progress (
        hifz_id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        surah_id INTEGER NOT NULL,
        ayah_id INTEGER NOT NULL,
        status TEXT CHECK(status IN ('not_started', 'in_progress', 'memorized', 'needs_review')) DEFAULT 'not_started',
        last_reviewed DATETIME,
        next_review DATETIME,
        interval_days INTEGER DEFAULT 1,
        ease_factor REAL DEFAULT 2.5,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (surah_id, ayah_id) REFERENCES quran_text(surah_id, ayah_id) ON DELETE CASCADE,
        UNIQUE(user_id, surah_id, ayah_id)
    );");
    
    populate_surah_info($db);

    // Create a default admin user if no users exist
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() == 0) {
        $admin_username = 'admin';
        $admin_password_hash = password_hash(DEFAULT_PASSWORD_ADMIN, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password_hash, is_admin) VALUES (?, ?, 1)");
        $stmt->execute([$admin_username, $admin_password_hash]);
        $admin_user_id = $db->lastInsertId();
        // Initialize settings for admin user
        $db->prepare("INSERT OR IGNORE INTO user_settings (user_id) VALUES (?)")->execute([$admin_user_id]);
    }
}

function populate_surah_info($db) {
    $surahs = [
        ['id' => 1, 'name_ar' => 'الفاتحة', 'name_en' => 'Al-Fatiha', 'place' => 'Makkah', 'ayahs' => 7],
        ['id' => 2, 'name_ar' => 'البقرة', 'name_en' => 'Al-Baqarah', 'place' => 'Madinah', 'ayahs' => 286],
        ['id' => 3, 'name_ar' => 'آل عمران', 'name_en' => 'Aal-i-Imran', 'place' => 'Madinah', 'ayahs' => 200],
        ['id' => 4, 'name_ar' => 'النساء', 'name_en' => 'An-Nisa', 'place' => 'Madinah', 'ayahs' => 176],
        ['id' => 5, 'name_ar' => 'المائدة', 'name_en' => 'Al-Maidah', 'place' => 'Madinah', 'ayahs' => 120],
        ['id' => 6, 'name_ar' => 'الأنعام', 'name_en' => 'Al-Anam', 'place' => 'Makkah', 'ayahs' => 165],
        ['id' => 7, 'name_ar' => 'الأعراف', 'name_en' => 'Al-Araf', 'place' => 'Makkah', 'ayahs' => 206],
        ['id' => 8, 'name_ar' => 'الأنفال', 'name_en' => 'Al-Anfal', 'place' => 'Madinah', 'ayahs' => 75],
        ['id' => 9, 'name_ar' => 'التوبة', 'name_en' => 'At-Tawbah', 'place' => 'Madinah', 'ayahs' => 129],
        ['id' => 10, 'name_ar' => 'يونس', 'name_en' => 'Yunus', 'place' => 'Makkah', 'ayahs' => 109],
        ['id' => 11, 'name_ar' => 'هود', 'name_en' => 'Hud', 'place' => 'Makkah', 'ayahs' => 123],
        ['id' => 12, 'name_ar' => 'يوسف', 'name_en' => 'Yusuf', 'place' => 'Makkah', 'ayahs' => 111],
        ['id' => 13, 'name_ar' => 'الرعد', 'name_en' => 'Ar-Rad', 'place' => 'Madinah', 'ayahs' => 43],
        ['id' => 14, 'name_ar' => 'ابراهيم', 'name_en' => 'Ibrahim', 'place' => 'Makkah', 'ayahs' => 52],
        ['id' => 15, 'name_ar' => 'الحجر', 'name_en' => 'Al-Hijr', 'place' => 'Makkah', 'ayahs' => 99],
        ['id' => 16, 'name_ar' => 'النحل', 'name_en' => 'An-Nahl', 'place' => 'Makkah', 'ayahs' => 128],
        ['id' => 17, 'name_ar' => 'الإسراء', 'name_en' => 'Al-Isra', 'place' => 'Makkah', 'ayahs' => 111],
        ['id' => 18, 'name_ar' => 'الكهف', 'name_en' => 'Al-Kahf', 'place' => 'Makkah', 'ayahs' => 110],
        ['id' => 19, 'name_ar' => 'مريم', 'name_en' => 'Maryam', 'place' => 'Makkah', 'ayahs' => 98],
        ['id' => 20, 'name_ar' => 'طه', 'name_en' => 'Taha', 'place' => 'Makkah', 'ayahs' => 135],
        ['id' => 21, 'name_ar' => 'الأنبياء', 'name_en' => 'Al-Anbiya', 'place' => 'Makkah', 'ayahs' => 112],
        ['id' => 22, 'name_ar' => 'الحج', 'name_en' => 'Al-Hajj', 'place' => 'Madinah', 'ayahs' => 78],
        ['id' => 23, 'name_ar' => 'المؤمنون', 'name_en' => 'Al-Muminun', 'place' => 'Makkah', 'ayahs' => 118],
        ['id' => 24, 'name_ar' => 'النور', 'name_en' => 'An-Nur', 'place' => 'Madinah', 'ayahs' => 64],
        ['id' => 25, 'name_ar' => 'الفرقان', 'name_en' => 'Al-Furqan', 'place' => 'Makkah', 'ayahs' => 77],
        ['id' => 26, 'name_ar' => 'الشعراء', 'name_en' => 'Ash-Shuara', 'place' => 'Makkah', 'ayahs' => 227],
        ['id' => 27, 'name_ar' => 'النمل', 'name_en' => 'An-Naml', 'place' => 'Makkah', 'ayahs' => 93],
        ['id' => 28, 'name_ar' => 'القصص', 'name_en' => 'Al-Qasas', 'place' => 'Makkah', 'ayahs' => 88],
        ['id' => 29, 'name_ar' => 'العنكبوت', 'name_en' => 'Al-Ankabut', 'place' => 'Makkah', 'ayahs' => 69],
        ['id' => 30, 'name_ar' => 'الروم', 'name_en' => 'Ar-Rum', 'place' => 'Makkah', 'ayahs' => 60],
        ['id' => 31, 'name_ar' => 'لقمان', 'name_en' => 'Luqman', 'place' => 'Makkah', 'ayahs' => 34],
        ['id' => 32, 'name_ar' => 'السجدة', 'name_en' => 'As-Sajdah', 'place' => 'Makkah', 'ayahs' => 30],
        ['id' => 33, 'name_ar' => 'الأحزاب', 'name_en' => 'Al-Ahzab', 'place' => 'Madinah', 'ayahs' => 73],
        ['id' => 34, 'name_ar' => 'سبإ', 'name_en' => 'Saba', 'place' => 'Makkah', 'ayahs' => 54],
        ['id' => 35, 'name_ar' => 'فاطر', 'name_en' => 'Fatir', 'place' => 'Makkah', 'ayahs' => 45],
        ['id' => 36, 'name_ar' => 'يس', 'name_en' => 'Ya-Sin', 'place' => 'Makkah', 'ayahs' => 83],
        ['id' => 37, 'name_ar' => 'الصافات', 'name_en' => 'As-Saffat', 'place' => 'Makkah', 'ayahs' => 182],
        ['id' => 38, 'name_ar' => 'ص', 'name_en' => 'Sad', 'place' => 'Makkah', 'ayahs' => 88],
        ['id' => 39, 'name_ar' => 'الزمر', 'name_en' => 'Az-Zumar', 'place' => 'Makkah', 'ayahs' => 75],
        ['id' => 40, 'name_ar' => 'غافر', 'name_en' => 'Ghafir', 'place' => 'Makkah', 'ayahs' => 85],
        ['id' => 41, 'name_ar' => 'فصلت', 'name_en' => 'Fussilat', 'place' => 'Makkah', 'ayahs' => 54],
        ['id' => 42, 'name_ar' => 'الشورى', 'name_en' => 'Ash-Shuraa', 'place' => 'Makkah', 'ayahs' => 53],
        ['id' => 43, 'name_ar' => 'الزخرف', 'name_en' => 'Az-Zukhruf', 'place' => 'Makkah', 'ayahs' => 89],
        ['id' => 44, 'name_ar' => 'الدخان', 'name_en' => 'Ad-Dukhan', 'place' => 'Makkah', 'ayahs' => 59],
        ['id' => 45, 'name_ar' => 'الجاثية', 'name_en' => 'Al-Jathiyah', 'place' => 'Makkah', 'ayahs' => 37],
        ['id' => 46, 'name_ar' => 'الأحقاف', 'name_en' => 'Al-Ahqaf', 'place' => 'Makkah', 'ayahs' => 35],
        ['id' => 47, 'name_ar' => 'محمد', 'name_en' => 'Muhammad', 'place' => 'Madinah', 'ayahs' => 38],
        ['id' => 48, 'name_ar' => 'الفتح', 'name_en' => 'Al-Fath', 'place' => 'Madinah', 'ayahs' => 29],
        ['id' => 49, 'name_ar' => 'الحجرات', 'name_en' => 'Al-Hujurat', 'place' => 'Madinah', 'ayahs' => 18],
        ['id' => 50, 'name_ar' => 'ق', 'name_en' => 'Qaf', 'place' => 'Makkah', 'ayahs' => 45],
        ['id' => 51, 'name_ar' => 'الذاريات', 'name_en' => 'Adh-Dhariyat', 'place' => 'Makkah', 'ayahs' => 60],
        ['id' => 52, 'name_ar' => 'الطور', 'name_en' => 'At-Tur', 'place' => 'Makkah', 'ayahs' => 49],
        ['id' => 53, 'name_ar' => 'النجم', 'name_en' => 'An-Najm', 'place' => 'Makkah', 'ayahs' => 62],
        ['id' => 54, 'name_ar' => 'القمر', 'name_en' => 'Al-Qamar', 'place' => 'Makkah', 'ayahs' => 55],
        ['id' => 55, 'name_ar' => 'الرحمن', 'name_en' => 'Ar-Rahman', 'place' => 'Madinah', 'ayahs' => 78],
        ['id' => 56, 'name_ar' => 'الواقعة', 'name_en' => 'Al-Waqiah', 'place' => 'Makkah', 'ayahs' => 96],
        ['id' => 57, 'name_ar' => 'الحديد', 'name_en' => 'Al-Hadid', 'place' => 'Madinah', 'ayahs' => 29],
        ['id' => 58, 'name_ar' => 'المجادلة', 'name_en' => 'Al-Mujadila', 'place' => 'Madinah', 'ayahs' => 22],
        ['id' => 59, 'name_ar' => 'الحشر', 'name_en' => 'Al-Hashr', 'place' => 'Madinah', 'ayahs' => 24],
        ['id' => 60, 'name_ar' => 'الممتحنة', 'name_en' => 'Al-Mumtahanah', 'place' => 'Madinah', 'ayahs' => 13],
        ['id' => 61, 'name_ar' => 'الصف', 'name_en' => 'As-Saf', 'place' => 'Madinah', 'ayahs' => 14],
        ['id' => 62, 'name_ar' => 'الجمعة', 'name_en' => 'Al-Jumuah', 'place' => 'Madinah', 'ayahs' => 11],
        ['id' => 63, 'name_ar' => 'المنافقون', 'name_en' => 'Al-Munafiqun', 'place' => 'Madinah', 'ayahs' => 11],
        ['id' => 64, 'name_ar' => 'التغابن', 'name_en' => 'At-Taghabun', 'place' => 'Madinah', 'ayahs' => 18],
        ['id' => 65, 'name_ar' => 'الطلاق', 'name_en' => 'At-Talaq', 'place' => 'Madinah', 'ayahs' => 12],
        ['id' => 66, 'name_ar' => 'التحريم', 'name_en' => 'At-Tahrim', 'place' => 'Madinah', 'ayahs' => 12],
        ['id' => 67, 'name_ar' => 'الملك', 'name_en' => 'Al-Mulk', 'place' => 'Makkah', 'ayahs' => 30],
        ['id' => 68, 'name_ar' => 'القلم', 'name_en' => 'Al-Qalam', 'place' => 'Makkah', 'ayahs' => 52],
        ['id' => 69, 'name_ar' => 'الحاقة', 'name_en' => 'Al-Haqqah', 'place' => 'Makkah', 'ayahs' => 52],
        ['id' => 70, 'name_ar' => 'المعارج', 'name_en' => 'Al-Maarij', 'place' => 'Makkah', 'ayahs' => 44],
        ['id' => 71, 'name_ar' => 'نوح', 'name_en' => 'Nuh', 'place' => 'Makkah', 'ayahs' => 28],
        ['id' => 72, 'name_ar' => 'الجن', 'name_en' => 'Al-Jinn', 'place' => 'Makkah', 'ayahs' => 28],
        ['id' => 73, 'name_ar' => 'المزمل', 'name_en' => 'Al-Muzzammil', 'place' => 'Makkah', 'ayahs' => 20],
        ['id' => 74, 'name_ar' => 'المدثر', 'name_en' => 'Al-Muddaththir', 'place' => 'Makkah', 'ayahs' => 56],
        ['id' => 75, 'name_ar' => 'القيامة', 'name_en' => 'Al-Qiyamah', 'place' => 'Makkah', 'ayahs' => 40],
        ['id' => 76, 'name_ar' => 'الانسان', 'name_en' => 'Al-Insan', 'place' => 'Madinah', 'ayahs' => 31],
        ['id' => 77, 'name_ar' => 'المرسلات', 'name_en' => 'Al-Mursalat', 'place' => 'Makkah', 'ayahs' => 50],
        ['id' => 78, 'name_ar' => 'النبإ', 'name_en' => 'An-Naba', 'place' => 'Makkah', 'ayahs' => 40],
        ['id' => 79, 'name_ar' => 'النازعات', 'name_en' => 'An-Naziat', 'place' => 'Makkah', 'ayahs' => 46],
        ['id' => 80, 'name_ar' => 'عبس', 'name_en' => 'Abasa', 'place' => 'Makkah', 'ayahs' => 42],
        ['id' => 81, 'name_ar' => 'التكوير', 'name_en' => 'At-Takwir', 'place' => 'Makkah', 'ayahs' => 29],
        ['id' => 82, 'name_ar' => 'الإنفطار', 'name_en' => 'Al-Infitar', 'place' => 'Makkah', 'ayahs' => 19],
        ['id' => 83, 'name_ar' => 'المطففين', 'name_en' => 'Al-Mutaffifin', 'place' => 'Makkah', 'ayahs' => 36],
        ['id' => 84, 'name_ar' => 'الإنشقاق', 'name_en' => 'Al-Inshiqaq', 'place' => 'Makkah', 'ayahs' => 25],
        ['id' => 85, 'name_ar' => 'البروج', 'name_en' => 'Al-Buruj', 'place' => 'Makkah', 'ayahs' => 22],
        ['id' => 86, 'name_ar' => 'الطارق', 'name_en' => 'At-Tariq', 'place' => 'Makkah', 'ayahs' => 17],
        ['id' => 87, 'name_ar' => 'الأعلى', 'name_en' => 'Al-Ala', 'place' => 'Makkah', 'ayahs' => 19],
        ['id' => 88, 'name_ar' => 'الغاشية', 'name_en' => 'Al-Ghashiyah', 'place' => 'Makkah', 'ayahs' => 26],
        ['id' => 89, 'name_ar' => 'الفجر', 'name_en' => 'Al-Fajr', 'place' => 'Makkah', 'ayahs' => 30],
        ['id' => 90, 'name_ar' => 'البلد', 'name_en' => 'Al-Balad', 'place' => 'Makkah', 'ayahs' => 20],
        ['id' => 91, 'name_ar' => 'الشمس', 'name_en' => 'Ash-Shams', 'place' => 'Makkah', 'ayahs' => 15],
        ['id' => 92, 'name_ar' => 'الليل', 'name_en' => 'Al-Layl', 'place' => 'Makkah', 'ayahs' => 21],
        ['id' => 93, 'name_ar' => 'الضحى', 'name_en' => 'Ad-Duhaa', 'place' => 'Makkah', 'ayahs' => 11],
        ['id' => 94, 'name_ar' => 'الشرح', 'name_en' => 'Ash-Sharh', 'place' => 'Makkah', 'ayahs' => 8],
        ['id' => 95, 'name_ar' => 'التين', 'name_en' => 'At-Tin', 'place' => 'Makkah', 'ayahs' => 8],
        ['id' => 96, 'name_ar' => 'العلق', 'name_en' => 'Al-Alaq', 'place' => 'Makkah', 'ayahs' => 19],
        ['id' => 97, 'name_ar' => 'القدر', 'name_en' => 'Al-Qadr', 'place' => 'Makkah', 'ayahs' => 5],
        ['id' => 98, 'name_ar' => 'البينة', 'name_en' => 'Al-Bayyinah', 'place' => 'Madinah', 'ayahs' => 8],
        ['id' => 99, 'name_ar' => 'الزلزلة', 'name_en' => 'Az-Zalzalah', 'place' => 'Madinah', 'ayahs' => 8],
        ['id' => 100, 'name_ar' => 'العاديات', 'name_en' => 'Al-Adiyat', 'place' => 'Makkah', 'ayahs' => 11],
        ['id' => 101, 'name_ar' => 'القارعة', 'name_en' => 'Al-Qariah', 'place' => 'Makkah', 'ayahs' => 11],
        ['id' => 102, 'name_ar' => 'التكاثر', 'name_en' => 'At-Takathur', 'place' => 'Makkah', 'ayahs' => 8],
        ['id' => 103, 'name_ar' => 'العصر', 'name_en' => 'Al-Asr', 'place' => 'Makkah', 'ayahs' => 3],
        ['id' => 104, 'name_ar' => 'الهمزة', 'name_en' => 'Al-Humazah', 'place' => 'Makkah', 'ayahs' => 9],
        ['id' => 105, 'name_ar' => 'الفيل', 'name_en' => 'Al-Fil', 'place' => 'Makkah', 'ayahs' => 5],
        ['id' => 106, 'name_ar' => 'قريش', 'name_en' => 'Quraysh', 'place' => 'Makkah', 'ayahs' => 4],
        ['id' => 107, 'name_ar' => 'الماعون', 'name_en' => 'Al-Maun', 'place' => 'Makkah', 'ayahs' => 7],
        ['id' => 108, 'name_ar' => 'الكوثر', 'name_en' => 'Al-Kawthar', 'place' => 'Makkah', 'ayahs' => 3],
        ['id' => 109, 'name_ar' => 'الكافرون', 'name_en' => 'Al-Kafirun', 'place' => 'Makkah', 'ayahs' => 6],
        ['id' => 110, 'name_ar' => 'النصر', 'name_en' => 'An-Nasr', 'place' => 'Madinah', 'ayahs' => 3],
        ['id' => 111, 'name_ar' => 'المسد', 'name_en' => 'Al-Masad', 'place' => 'Makkah', 'ayahs' => 5],
        ['id' => 112, 'name_ar' => 'الإخلاص', 'name_en' => 'Al-Ikhlas', 'place' => 'Makkah', 'ayahs' => 4],
        ['id' => 113, 'name_ar' => 'الفلق', 'name_en' => 'Al-Falaq', 'place' => 'Makkah', 'ayahs' => 5],
        ['id' => 114, 'name_ar' => 'الناس', 'name_en' => 'An-Nas', 'place' => 'Makkah', 'ayahs' => 6],
    ];
    $stmt = $db->prepare("INSERT OR IGNORE INTO surah_info (surah_id, surah_name_arabic, surah_name_english, revelation_place, total_ayahs) VALUES (?, ?, ?, ?, ?)");
    foreach ($surahs as $s) {
        $stmt->execute([$s['id'], $s['name_ar'], $s['name_en'], $s['place'], $s['ayahs']]);
    }
}

// --- HELPER FUNCTIONS ---
function h($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return is_logged_in() && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function get_user_settings($user_id) {
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM user_settings WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$settings) {
        $db->prepare("INSERT OR IGNORE INTO user_settings (user_id) VALUES (?)")->execute([$user_id]);
        $stmt->execute([$user_id]); // Re-fetch after insert
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    $defaults = [
        'user_id' => $user_id, // Ensure user_id is part of the array
        'last_read_surah' => 1,
        'last_read_ayah' => 1,
        'tilawat_lines_per_page' => 10,
        'tilawat_font_size' => 36,
        'tilawat_show_translation' => 0,
        'tilawat_view_mode' => 'paginated',
    ];
    return array_merge($defaults, $settings ?: []);
}

function update_user_setting($user_id, $key, $value) {
    $db = get_db();
    // Ensure user_settings row exists
    get_user_settings($user_id); 
    
    // Validate key to prevent SQL injection if key comes from less trusted source
    $allowed_keys = ['last_read_surah', 'last_read_ayah', 'tilawat_lines_per_page', 'tilawat_font_size', 'tilawat_show_translation', 'tilawat_view_mode'];
    if (!in_array($key, $allowed_keys)) {
        error_log("Attempt to update invalid user setting key: $key");
        return false;
    }
    
    $stmt = $db->prepare("UPDATE user_settings SET {$key} = ? WHERE user_id = ?");
    return $stmt->execute([$value, $user_id]);
}

function import_data_am() {
    if (!file_exists(DATA_AM_FILE)) {
        return "Error: data.AM file not found at " . h(DATA_AM_FILE) . ". Please create it or update the path.";
    }
    $content = file_get_contents(DATA_AM_FILE);
    if ($content === false) {
        return "Error: Could not read data.AM file.";
    }

    $db = get_db();
    preg_match_all('/(.*?)\s*ترجمہ:\s*(.*?)\s*<br\/>س\s*(\d+)\s*آ\s*(\d+)/u', $content, $matches, PREG_SET_ORDER);

    if (empty($matches)) {
        return "Error: No matching data found in data.AM file. Check format. Example: [Arabic Text] ترجمہ: [Urdu Translation]<br/>س 1 آ 1";
    }

    $stmt_insert = $db->prepare("INSERT OR IGNORE INTO quran_text (arabic_text, urdu_translation, surah_id, ayah_id) VALUES (?, ?, ?, ?)");
    $stmt_update_total_ayahs = $db->prepare("UPDATE surah_info SET total_ayahs = MAX(total_ayahs, ?) WHERE surah_id = ?");
    
    $db->beginTransaction();
    $count = 0;
    $ayah_counts = [];

    foreach ($matches as $match) {
        $arabic_text = trim($match[1]);
        $urdu_translation = trim($match[2]);
        $surah_id = (int)$match[3];
        $ayah_id = (int)$match[4];

        if (empty($arabic_text) || $surah_id <= 0 || $ayah_id <= 0) {
            continue;
        }
        if ($stmt_insert->execute([$arabic_text, $urdu_translation, $surah_id, $ayah_id])) {
            $count++;
            // Track max ayah_id for each surah
            if (!isset($ayah_counts[$surah_id])) {
                $ayah_counts[$surah_id] = 0;
            }
            $ayah_counts[$surah_id] = max($ayah_counts[$surah_id], $ayah_id);
        }
    }
    
    // Update total_ayahs in surah_info based on imported data
    foreach ($ayah_counts as $surah_id => $max_ayah) {
        $stmt_check_surah = $db->prepare("SELECT total_ayahs FROM surah_info WHERE surah_id = ?");
        $stmt_check_surah->execute([$surah_id]);
        $current_total = $stmt_check_surah->fetchColumn();
        if ($current_total === false) { // Surah not in surah_info, maybe add it or log error
            // For now, assume surah_info is pre-populated with all 114 surahs
            // If not, this would be a place to insert a new surah_info entry or update it
        }
        // Only update if imported data has more ayahs than pre-set (or if pre-set is 0/null)
        if ($current_total === false || $max_ayah > (int)$current_total) {
            $stmt_update_total_ayahs->execute([$max_ayah, $surah_id]);
        }
    }

    $db->commit();
    return "Successfully imported/updated $count ayahs from data.AM. Ayah counts in surah_info table might have been updated.";
}

// --- ACTION HANDLERS / ROUTING ---
$action = $_GET['action'] ?? 'home';
$page_title = APP_NAME;
$output_buffer = ''; // Main content output buffer

try {
    $db = get_db();
} catch (Exception $e) { // Catching generic Exception if PDOException was missed
    error_log("Critical Error: " . $e->getMessage());
    die("A critical error occurred. Application cannot proceed.");
}

$csrf_token = generate_csrf_token();
$current_user_id = get_user_id();
$user_settings = $current_user_id ? get_user_settings($current_user_id) : null;
$initial_js_user_settings = "null";
if ($user_settings) {
    $js_safe_settings = [];
    foreach ($user_settings as $key => $value) {
        if ($key === 'user_id') continue; // Don't expose user_id directly like this
        $js_safe_settings[$key] = is_numeric($value) ? (float)$value : $value; // Ensure numeric values are numbers
    }
    $initial_js_user_settings = json_encode($js_safe_settings);
}

// --- POST Request Handling ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $output_buffer = "<p class='error'>CSRF token validation failed. Please try again.</p>";
    } else {
        // Process POST actions
        if ($action === 'login') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $stmt = $db->prepare("SELECT user_id, username, password_hash, is_admin FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'];
                get_user_settings($user['user_id']); // Ensure settings are initialized
                header("Location: ?action=home");
                exit;
            } else {
                $output_buffer = "<p class='error'>Invalid username or password.</p>";
            }
        } elseif ($action === 'register') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $email = trim($_POST['email'] ?? '');

            if (empty($username) || empty($password)) {
                $output_buffer = "<p class='error'>Username and password are required.</p>";
            } elseif (strlen($password) < 6) { // Min length 6 for demo, 8+ recommended
                $output_buffer = "<p class='error'>Password must be at least 6 characters long.</p>";
            } else {
                $stmt = $db->prepare("SELECT user_id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $output_buffer = "<p class='error'>Username already taken.</p>";
                } else {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt_insert_user = $db->prepare("INSERT INTO users (username, password_hash, email) VALUES (?, ?, ?)");
                    try {
                        $stmt_insert_user->execute([$username, $password_hash, $email]);
                        $user_id = $db->lastInsertId();
                        $db->prepare("INSERT INTO user_settings (user_id) VALUES (?)")->execute([$user_id]);
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['username'] = $username;
                        $_SESSION['is_admin'] = 0;
                        header("Location: ?action=home");
                        exit;
                    } catch (PDOException $e) {
                        if ($e->errorInfo[1] == 1062 || $e->errorInfo[1] == 23000 || str_contains(strtolower($e->getMessage()), "unique constraint failed")) { // SQLite specific code for unique constraint
                            $output_buffer = "<p class='error'>Username or Email already exists.</p>";
                        } else {
                            $output_buffer = "<p class='error'>Registration failed. Please try again later.</p>";
                            error_log("Registration error: " . $e->getMessage());
                        }
                    }
                }
            }
        } elseif ($action === 'admin_import_data' && is_admin()) {
            $import_message = import_data_am();
            $output_buffer = "<div class='" . (str_starts_with($import_message, "Error:") ? "error" : "success") . "'>" . h($import_message) . "</div>";
        } elseif ($action === 'admin_restore' && is_admin() && isset($_FILES['backup_file'])) {
            if ($_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['backup_file']['tmp_name'];
                // Close current DB connection - This is tricky with static PDO instance.
                // For simplicity, we try to copy. Real solution might need external script or app restart.
                // unset($db); $db = null; // Attempt to release lock, may not work reliably
                if (file_exists(DB_FILE)) {
                    unlink(DB_FILE); // Delete old DB file
                }
                if (move_uploaded_file($tmp_name, DB_FILE)) {
                    $output_buffer = "<p class='success'>Database restored successfully. Please refresh the page. If issues persist, re-login may be required.</p>";
                    // Re-initialize DB connection static var with the new file for current request if possible
                    // This is complex. Better to just inform user and let next request handle it.
                } else {
                    $output_buffer = "<p class='error'>Failed to restore database. Check file permissions and ensure no active connections.</p>";
                }
            } else {
                $output_buffer = "<p class='error'>Error uploading backup file: " . h($_FILES['backup_file']['error']) . "</p>";
            }
        } elseif ($action === 'save_user_tilawat_settings' && is_logged_in()) {
            header('Content-Type: application/json');
            $response = ['status' => 'error', 'message' => 'Invalid data.'];
            if ($current_user_id) {
                $updated_any = false;
                if(isset($_POST['lines_per_page'])) {
                    update_user_setting($current_user_id, 'tilawat_lines_per_page', (int)$_POST['lines_per_page']);
                    $updated_any = true;
                }
                if(isset($_POST['font_size'])) {
                     update_user_setting($current_user_id, 'tilawat_font_size', (int)$_POST['font_size']);
                     $updated_any = true;
                }
                if(isset($_POST['show_translation'])) {
                    update_user_setting($current_user_id, 'tilawat_show_translation', (int)$_POST['show_translation']);
                    $updated_any = true;
                }
                if(isset($_POST['view_mode']) && in_array($_POST['view_mode'], ['paginated', 'continuous'])) {
                    update_user_setting($current_user_id, 'tilawat_view_mode', $_POST['view_mode']);
                    $updated_any = true;
                }
                if ($updated_any) {
                    $response = ['status' => 'success', 'message' => 'Settings saved.'];
                }
            }
            echo json_encode($response);
            exit;
        } elseif ($action === 'update_last_read' && is_logged_in()) {
            header('Content-Type: application/json');
            $response = ['status' => 'error', 'message' => 'Invalid data.'];
            if ($current_user_id && isset($_POST['surah_id']) && isset($_POST['ayah_id'])) {
                update_user_setting($current_user_id, 'last_read_surah', (int)$_POST['surah_id']);
                update_user_setting($current_user_id, 'last_read_ayah', (int)$_POST['ayah_id']);
                $response = ['status' => 'success', 'message' => 'Last read position updated.'];
            }
            echo json_encode($response);
            exit;
        } elseif ($action === 'toggle_bookmark' && is_logged_in()) {
            header('Content-Type: application/json');
            $surah_id = (int)($_POST['surah_id'] ?? 0);
            $ayah_id = (int)($_POST['ayah_id'] ?? 0);
            $response = ['status' => 'error', 'message' => 'Invalid Ayah.'];

            if ($current_user_id && $surah_id > 0 && $ayah_id > 0) {
                $stmt_check = $db->prepare("SELECT bookmark_id FROM bookmarks WHERE user_id = ? AND surah_id = ? AND ayah_id = ?");
                $stmt_check->execute([$current_user_id, $surah_id, $ayah_id]);
                if ($bookmark = $stmt_check->fetch()) {
                    // Exists, remove it
                    $stmt_delete = $db->prepare("DELETE FROM bookmarks WHERE bookmark_id = ?");
                    $stmt_delete->execute([$bookmark['bookmark_id']]);
                    $response = ['status' => 'success', 'action' => 'removed', 'message' => 'Bookmark removed.'];
                } else {
                    // Doesn't exist, add it
                    $stmt_add = $db->prepare("INSERT INTO bookmarks (user_id, surah_id, ayah_id) VALUES (?, ?, ?)");
                    $stmt_add->execute([$current_user_id, $surah_id, $ayah_id]);
                    $response = ['status' => 'success', 'action' => 'added', 'message' => 'Bookmark added.'];
                }
            }
            echo json_encode($response);
            exit;
        }
    }
}

// --- GET Request Handling & Page Content Generation ---
if ($action === 'logout') {
    session_destroy();
    header("Location: ?action=login");
    exit;
} elseif ($action === 'admin_backup' && is_admin()) {
    if (file_exists(DB_FILE)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="quran_insights_backup_' . date('Y-m-d_H-i-s') . '.sqlite"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize(DB_FILE));
        readfile(DB_FILE);
        exit;
    } else {
        $output_buffer = "<p class='error'>Database file not found for backup.</p>";
        $action = 'admin_dashboard'; // Fallback to dashboard
    }
}

// Page rendering starts after all potential redirects or file downloads
ob_start(); // Start output buffering for the main content

switch ($action) {
    case 'home':
        $page_title = "Home - " . APP_NAME;
        echo "<h2>Welcome to " . APP_NAME . "!</h2>";
        echo "<p>Your companion for Quranic study, memorization, and reflection. Authored by ".APP_AUTHOR.".</p>";
        if (is_logged_in() && $user_settings && $user_settings['last_read_surah']) {
            $stmt_lr_surah = $db->prepare("SELECT surah_name_english FROM surah_info WHERE surah_id = ?");
            $stmt_lr_surah->execute([$user_settings['last_read_surah']]);
            $lr_surah_name = $stmt_lr_surah->fetchColumn();
            echo "<p>Your last read position: <a href='?action=surah&id={$user_settings['last_read_surah']}#ayah-{$user_settings['last_read_surah']}-{$user_settings['last_read_ayah']}'>Surah {$lr_surah_name}, Ayah {$user_settings['last_read_ayah']}</a></p>";
        }
        // Check if Quran data exists
        $stmt_quran_check = $db->query("SELECT COUNT(*) FROM quran_text");
        if ($stmt_quran_check->fetchColumn() == 0) {
            echo "<p class='warning'>Quran data is not yet imported. ";
            if (is_admin()) {
                echo "Please go to the <a href='?action=admin_dashboard'>Admin Dashboard</a> to import data from <code>data.AM</code>.";
            } else {
                echo "Please ask an administrator to import the Quran data.";
            }
            echo "</p>";
        }
        break;

    case 'login':
        if (is_logged_in()) { header("Location: ?action=home"); exit; }
        $page_title = "Login - " . APP_NAME;
        echo "<h2>Login</h2>";
        if (!empty($output_buffer)) echo $output_buffer; // Display errors from POST
        echo "<form method='POST' action='?action=login'>";
        echo "<input type='hidden' name='csrf_token' value='{$csrf_token}'>";
        echo "<label for='username'>Username:</label><input type='text' name='username' id='username' required><br>";
        echo "<label for='password'>Password:</label><input type='password' name='password' id='password' required><br>";
        echo "<input type='submit' value='Login'>";
        echo "</form>";
        echo "<p>Don't have an account? <a href='?action=register'>Register here</a>.</p>";
        break;

    case 'register':
        if (is_logged_in()) { header("Location: ?action=home"); exit; }
        $page_title = "Register - " . APP_NAME;
        echo "<h2>Register</h2>";
        if (!empty($output_buffer)) echo $output_buffer; // Display errors from POST
        echo "<form method='POST' action='?action=register'>";
        echo "<input type='hidden' name='csrf_token' value='{$csrf_token}'>";
        echo "<label for='username'>Username:</label><input type='text' name='username' id='username' required><br>";
        echo "<label for='email'>Email (Optional):</label><input type='email' name='email' id='email'><br>";
        echo "<label for='password'>Password:</label><input type='password' name='password' id='password' minlength='6' required><br>";
        echo "<input type='submit' value='Register'>";
        echo "</form>";
        echo "<p>Already have an account? <a href='?action=login'>Login here</a>.</p>";
        break;

    case 'admin_dashboard':
        if (!is_admin()) { echo "<p class='error'>Access Denied.</p>"; break; }
        $page_title = "Admin Dashboard - " . APP_NAME;
        echo "<h2>Admin Dashboard</h2>";
        if (!empty($output_buffer)) echo $output_buffer; // Display messages from POST actions

        echo "<h3>Data Management</h3>";
        echo "<form method='POST' action='?action=admin_import_data' style='margin-bottom: 20px;'>";
        echo "<input type='hidden' name='csrf_token' value='{$csrf_token}'>";
        echo "<p>Import Quran data from <code>data.AM</code> file. Ensure the file is located at: <code>" . h(DATA_AM_FILE) . "</code>. Re-importing will add new ayahs if they don't exist and may update surah total ayah counts.</p>";
        echo "<input type='submit' value='Import from data.AM'>";
        echo "</form>";

        echo "<h3>Database Backup & Restore</h3>";
        echo "<p><a href='?action=admin_backup'><button type='button'>Backup Database</button></a></p>";
        echo "<form method='POST' action='?action=admin_restore' enctype='multipart/form-data' style='margin-top: 10px;'>";
        echo "<input type='hidden' name='csrf_token' value='{$csrf_token}'>";
        echo "<label for='backup_file'>Restore Database (SQLite file):</label>";
        echo "<input type='file' name='backup_file' id='backup_file' accept='.sqlite,.db,.sqlite3' required><br>";
        echo "<input type='submit' value='Restore Backup'>";
        echo "</form>";
        echo "<p style='font-size:0.9em; color:#ccc;'>Note: Restoring will overwrite the current database. Ensure you have a backup if needed. The application might require a page refresh or re-login after restore.</p>";
        break;

    case 'surahs_list':
        $page_title = "Surahs - " . APP_NAME;
        $stmt = $db->query("SELECT surah_id, surah_name_arabic, surah_name_english, total_ayahs, revelation_place FROM surah_info ORDER BY surah_id ASC");
        $surahs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h2>All Surahs</h2><div style='display: flex; flex-wrap: wrap; gap: 10px;'>";
        if (empty($surahs)) {
            echo "<p>No Surah information found. Please ensure surah_info table is populated.</p>";
        } else {
            foreach($surahs as $s) {
                echo "<div class='surah-card' style='border: 1px solid #1f4068; padding: 10px; border-radius: 5px; width: 220px; background-color: #1c2e56; text-align:center;'>";
                echo "<h3><a href='?action=surah&id={$s['surah_id']}'>{$s['surah_id']}. {$s['surah_name_arabic']} <br>({$s['surah_name_english']})</a></h3>";
                echo "<p style='font-size:0.9em;'>{$s['total_ayahs']} Ayahs, {$s['revelation_place']}</p>";
                echo "</div>";
            }
        }
        echo "</div>";
        break;

    case 'surah':
        $surah_id_req = (int)($_GET['id'] ?? 0);
        if ($surah_id_req <= 0) { echo "<p class='error'>Invalid Surah ID.</p>"; break; }

        $stmt_surah_info = $db->prepare("SELECT * FROM surah_info WHERE surah_id = ?");
        $stmt_surah_info->execute([$surah_id_req]);
        $surah_info = $stmt_surah_info->fetch(PDO::FETCH_ASSOC);

        if ($surah_info) {
            $page_title = "Surah {$surah_info['surah_name_english']} - " . APP_NAME;
            echo "<h2>Surah {$surah_info['surah_name_arabic']} ({$surah_info['surah_name_english']})</h2>";
            echo "<p>{$surah_info['revelation_place']} - {$surah_info['total_ayahs']} Ayahs</p>";
            echo "<div id='ayah-list-container'>";

            $stmt_ayahs = $db->prepare("SELECT ayah_id, arabic_text, urdu_translation FROM quran_text WHERE surah_id = ? ORDER BY ayah_id ASC");
            $stmt_ayahs->execute([$surah_id_req]);
            $ayahs = $stmt_ayahs->fetchAll(PDO::FETCH_ASSOC);

            if ($ayahs) {
                if ($surah_id_req != 1 && $surah_id_req != 9) { // No Bismillah for Al-Fatiha (part of it) or At-Tawbah
                     echo "<div class='ayah-container' style='text-align:center;'><p class='arabic-text' style='font-size: 2em;'>بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</p></div>";
                }
                foreach ($ayahs as $ayah) {
                    echo "<div class='ayah-container' id='ayah-{$surah_info['surah_id']}-{$ayah['ayah_id']}'>";
                    echo "<p class='arabic-text'>" . h($ayah['arabic_text']) . " <span class='ayah-number-display'>﴿{$ayah['ayah_id']}﴾</span></p>";
                    echo "<p class='urdu-translation'>" . h($ayah['urdu_translation']) . "</p>";
                    echo "<div class='ayah-meta'>S{$surah_info['surah_id']}:A{$ayah['ayah_id']}";
                    if(is_logged_in()){
                        // Check if bookmarked
                        $stmt_check_bm = $db->prepare("SELECT bookmark_id FROM bookmarks WHERE user_id = ? AND surah_id = ? AND ayah_id = ?");
                        $stmt_check_bm->execute([$current_user_id, $surah_info['surah_id'], $ayah['ayah_id']]);
                        $is_bookmarked = $stmt_check_bm->fetch() ? 'active' : '';
                        $bookmark_text = $is_bookmarked ? 'Bookmarked' : 'Bookmark';
                        echo " | <button class='bookmark-btn {$is_bookmarked}' data-surah='{$surah_info['surah_id']}' data-ayah='{$ayah['ayah_id']}'>{$bookmark_text}</button>";
                        // TODO: Add Note, Hifz status display/controls
                    }
                    echo "</div></div>";
                }
            } else {
                echo "<p>No ayahs found for this Surah. Please import data via the admin panel.</p>";
            }
            echo "</div>"; // end ayah-list-container
        } else {
            echo "<p class='error'>Surah not found.</p>";
        }
        break;

    case 'last_read':
        if (!is_logged_in()) { echo "<p class='error'>Please login to see your last read position.</p>"; break; }
        if ($user_settings && $user_settings['last_read_surah'] && $user_settings['last_read_ayah']) {
            header("Location: ?action=surah&id={$user_settings['last_read_surah']}#ayah-{$user_settings['last_read_surah']}-{$user_settings['last_read_ayah']}");
            exit;
        } else {
            echo "<p>No last read position found. Start reading to set one.</p>";
        }
        break;
    
    case 'bookmarks':
        if (!is_logged_in()) { echo "<p class='error'>Please login to manage bookmarks.</p>"; break; }
        $page_title = "My Bookmarks - " . APP_NAME;
        echo "<h2>My Bookmarks</h2>";
        $stmt = $db->prepare("
            SELECT b.surah_id, b.ayah_id, si.surah_name_english, qt.arabic_text 
            FROM bookmarks b
            JOIN surah_info si ON b.surah_id = si.surah_id
            JOIN quran_text qt ON b.surah_id = qt.surah_id AND b.ayah_id = qt.ayah_id
            WHERE b.user_id = ? 
            ORDER BY b.surah_id, b.ayah_id
        ");
        $stmt->execute([$current_user_id]);
        $bookmarks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($bookmarks) {
            echo "<ul>";
            foreach ($bookmarks as $bm) {
                $ayah_preview = mb_substr($bm['arabic_text'], 0, 50) . (mb_strlen($bm['arabic_text']) > 50 ? "..." : "");
                echo "<li><a href='?action=surah&id={$bm['surah_id']}#ayah-{$bm['surah_id']}-{$bm['ayah_id']}'>Surah {$bm['surah_name_english']} (S{$bm['surah_id']}:A{$bm['ayah_id']})</a> - <span class='arabic-text' style='font-size:0.8em;'>{$ayah_preview}</span></li>";
            }
            echo "</ul>";
        } else {
            echo "<p>You have no bookmarks yet.</p>";
        }
        break;

    // API-like actions for JS
    case 'get_quran_data_for_tilawat':
        if (!is_logged_in()) { header('HTTP/1.1 401 Unauthorized'); echo json_encode(['error' => 'Unauthorized']); exit; }
        header('Content-Type: application/json');
        
        $surah_id = isset($_GET['surah_id']) ? (int)$_GET['surah_id'] : ($user_settings['last_read_surah'] ?? 1);
        $start_ayah = isset($_GET['start_ayah']) ? (int)$_GET['start_ayah'] : ($user_settings['last_read_ayah'] ?? 1);
        $lines_per_page = isset($_GET['lines']) ? (int)$_GET['lines'] : ($user_settings['tilawat_lines_per_page'] ?? 10);
        // $page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // For paginated view
        $mode = $_GET['mode'] ?? ($user_settings['tilawat_view_mode'] ?? 'paginated');
        
        $limit = max(1, (int)$lines_per_page); // Ensure limit is at least 1

        $data = ['ayahs' => [], 'surah_info' => null, 'current_surah_id' => $surah_id, 'current_start_ayah' => $start_ayah, 'next_surah_id' => null, 'next_ayah_id' => null];
        
        $stmt_surah_info = $db->prepare("SELECT * FROM surah_info WHERE surah_id = ?");
        $stmt_surah_info->execute([$surah_id]);
        $data['surah_info'] = $stmt_surah_info->fetch(PDO::FETCH_ASSOC);

        if (!$data['surah_info']) {
            echo json_encode(['error' => 'Surah not found']);
            exit;
        }

        // Fetch Ayahs starting from surah_id, ayah_id across surah boundaries
        $query = "SELECT surah_id, ayah_id, arabic_text, urdu_translation 
                  FROM quran_text 
                  WHERE (surah_id = :surah_id AND ayah_id >= :start_ayah) OR surah_id > :surah_id
                  ORDER BY surah_id ASC, ayah_id ASC 
                  LIMIT :limit";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':surah_id', $surah_id, PDO::PARAM_INT);
        $stmt->bindParam(':start_ayah', $start_ayah, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        
        $stmt->execute();
        $ayahs_fetched = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $data['ayahs'] = $ayahs_fetched;

        if (!empty($ayahs_fetched)) {
            $last_fetched_ayah_obj = end($ayahs_fetched);
            
            // Determine start of next chunk
            $stmt_next_start = $db->prepare(
                "SELECT surah_id, ayah_id FROM quran_text 
                 WHERE (surah_id = :current_s_id AND ayah_id > :current_a_id) OR surah_id > :current_s_id
                 ORDER BY surah_id ASC, ayah_id ASC LIMIT 1"
            );
            $stmt_next_start->execute([
                ':current_s_id' => $last_fetched_ayah_obj['surah_id'],
                ':current_a_id' => $last_fetched_ayah_obj['ayah_id']
            ]);
            $next_start = $stmt_next_start->fetch(PDO::FETCH_ASSOC);
            if ($next_start) {
                $data['next_surah_id'] = (int)$next_start['surah_id'];
                $data['next_ayah_id'] = (int)$next_start['ayah_id'];
            } else { // Reached end of Quran
                $data['next_surah_id'] = null;
                $data['next_ayah_id'] = null;
            }
        }
        echo json_encode($data);
        exit;

    case 'get_surah_ayah_counts':
        // if (!is_logged_in()) { header('HTTP/1.1 401 Unauthorized'); echo json_encode(['error' => 'Unauthorized']); exit; }
        header('Content-Type: application/json');
        $stmt = $db->query("SELECT surah_id, surah_name_arabic, surah_name_english, total_ayahs FROM surah_info ORDER BY surah_id ASC");
        $surahs_info_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach($surahs_info_raw as $s) {
            $result[$s['surah_id']] = [
                'id' => (int)$s['surah_id'], // Ensure ID is int
                'name_ar' => $s['surah_name_arabic'],
                'name_en' => $s['surah_name_english'],
                'total_ayahs' => (int)$s['total_ayahs'] // Ensure total_ayahs is int
            ];
        }
        echo json_encode($result);
        exit;

    default:
        $page_title = "Not Found - " . APP_NAME;
        echo "<h2>Page Not Found</h2><p>The requested page or action '<code>" . h($action) . "</code>' was not found.</p>";
        break;
}

$main_content = ob_get_clean(); // Get buffered content

// Render the full page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($page_title); ?></title>
    <meta name="author" content="<?php echo APP_AUTHOR; ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400;1,700&family=Noto+Nastaliq+Urdu:wght@400..700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-bg: #0d1b2a; /* Dark blue */
            --secondary-bg: #1b263b; /* Slightly lighter blue */
            --accent-bg: #415a77; /* Muted blue */
            --text-color: #e0e1dd; /* Light grey/off-white */
            --accent-color: #778da9; /* Lighter muted blue */
            --highlight-color: #ff6b6b; /* Soft red for highlights/errors */
            --success-color: #50C878; /* Emerald green for success */

            --font-arabic: 'Amiri', 'Times New Roman', serif;
            --font-urdu: 'Noto Nastaliq Urdu', 'Times New Roman', serif;
            --font-ui: 'Roboto', sans-serif;
        }
        body { 
            font-family: var(--font-ui); 
            margin: 0; 
            background-color: var(--primary-bg); 
            color: var(--text-color); 
            line-height: 1.6; 
        }
        .container { 
            width: 90%; 
            max-width: 1200px; 
            margin: 20px auto; 
            overflow: hidden; 
            padding: 20px; 
            background-color: var(--secondary-bg); 
            box-shadow: 0 0 15px rgba(0,0,0,0.3);
            border-radius: 8px;
        }
        header.main-header { 
            background: linear-gradient(90deg, var(--primary-bg), var(--accent-bg)); 
            color: var(--text-color); 
            padding: 1rem 0; 
            text-align: center; 
            border-bottom: 3px solid var(--accent-color);
        }
        header.main-header h1 { margin: 0; font-size: 2.2em; font-weight: 700; }
        header.main-header h1 a { color: var(--text-color); text-decoration: none; }
        header.main-header nav { margin-top: 10px; }
        header.main-header nav a { 
            color: var(--text-color); 
            text-decoration: none; 
            padding: 8px 15px; 
            margin: 0 5px;
            border-radius: 4px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        header.main-header nav a:hover, header.main-header nav a.active { 
            background-color: var(--accent-color); 
            color: var(--primary-bg);
        }
        .user-auth { 
            text-align: right; 
            padding: 10px 20px; 
            background: var(--primary-bg); 
            color: var(--text-color); 
            font-size: 0.9em;
        }
        .user-auth a { color: var(--accent-color); text-decoration: none; margin-left: 10px; }
        .user-auth a:hover { color: var(--text-color); }

        .arabic-text { font-family: var(--font-arabic); font-size: 2em; direction: rtl; text-align: right; line-height: 1.9; color: #fff; }
        .urdu-translation { font-family: var(--font-urdu); font-size: 1.6em; direction: rtl; text-align: right; color: #bdc3c7; line-height: 1.8; margin-top: 8px; }
        .ayah-number-display { font-family: var(--font-ui); font-size: 0.7em; color: var(--accent-color); margin: 0 5px;}
        .ayah-container { border-bottom: 1px solid var(--accent-bg); padding: 20px 0; }
        .ayah-meta { font-size: 0.9em; color: var(--accent-color); margin-top: 12px; text-align: left; direction: ltr; }
        .ayah-meta button { margin-left: 10px; font-size: 0.9em; padding: 3px 8px; }
        
        button, input[type="submit"], input[type="button"] {
            background-color: var(--accent-color); color: var(--primary-bg); padding: 10px 18px; border: none; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease, transform 0.1s ease; font-size: 1em; font-weight: bold;
        }
        button:hover, input[type="submit"]:hover, input[type="button"]:hover { background-color: var(--text-color); color: var(--primary-bg); }
        button:active, input[type="submit"]:active, input[type="button"]:active { transform: translateY(1px); }
        
        input[type="text"], input[type="password"], input[type="email"], input[type="number"], textarea, select {
            padding: 10px; margin: 8px 0 15px 0; border: 1px solid var(--accent-bg); border-radius: 4px; background-color: var(--primary-bg); color: var(--text-color); width: calc(100% - 22px); font-size: 1em;
        }
        input:focus, textarea:focus, select:focus { outline: none; border-color: var(--accent-color); box-shadow: 0 0 5px var(--accent-color); }
        label { display: block; margin-bottom: 5px; color: var(--text-color); font-weight: bold; }

        .error { color: var(--highlight-color); background-color: rgba(255, 107, 107, 0.1); padding: 12px; border-radius: 4px; margin-bottom: 15px; border-left: 3px solid var(--highlight-color); }
        .success { color: var(--success-color); background-color: rgba(80, 200, 120, 0.1); padding: 12px; border-radius: 4px; margin-bottom: 15px; border-left: 3px solid var(--success-color); }
        .warning { color: #f39c12; background-color: rgba(243, 156, 18, 0.1); padding: 12px; border-radius: 4px; margin-bottom: 15px; border-left: 3px solid #f39c12;}

        /* Tilawat Mode Styles */
        #tilawat-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: black; color: white; z-index: 1000; overflow-y: auto;
            padding: 20px; box-sizing: border-box;
        }
        #tilawat-content-wrapper { max-width: 800px; margin: 0 auto; }
        #tilawat-content { font-family: var(--font-arabic); text-align: right; direction: rtl; }
        #tilawat-content .ayah-group { margin-bottom: 1.5em; padding-bottom: 1em; border-bottom: 1px dashed #444; }
        #tilawat-content .tilawat-arabic { line-height: 2; }
        #tilawat-content .tilawat-translation { font-family: var(--font-urdu); line-height: 1.8; opacity: 0.85; font-size: 0.65em; color: #ccc; }
        #tilawat-content .tilawat-surah-header { font-size: 1.5em; color: var(--accent-color); border-bottom: 1px solid var(--accent-color); padding-bottom: 0.5em; margin-bottom: 1em; text-align: center;}
        #tilawat-content .tilawat-bismillah { text-align: center; font-size: 1.2em; margin-bottom: 1em; }

        #tilawat-controls-container {
            position: fixed; top: 0; right: -300px; /* Start off-screen */
            width: 280px; height: 100%; background-color: rgba(30,30,30,0.95);
            padding: 20px; box-sizing: border-box; z-index: 1010; color: white; 
            transition: right 0.3s ease-in-out; overflow-y: auto;
        }
        #tilawat-controls-container.open { right: 0; }
        #tilawat-controls-container h4 { margin-top:0; border-bottom: 1px solid #555; padding-bottom: 10px; }
        #tilawat-controls-container label, 
        #tilawat-controls-container input, 
        #tilawat-controls-container select, 
        #tilawat-controls-container button {
            display: block; margin-bottom: 10px; width: 100%; box-sizing: border-box;
        }
        #tilawat-controls-container input[type="checkbox"] { width: auto; display: inline-block; margin-right: 5px; vertical-align: middle;}
        #tilawat-controls-container .control-row label { color: white; margin-bottom: 3px; font-size: 0.9em;}
        #tilawat-controls-container input, #tilawat-controls-container select { background-color: #444; color: white; border: 1px solid #666; }

        #toggle-tilawat-controls-btn { 
            position: fixed; top: 15px; right: 15px; z-index: 1020; 
            background: rgba(255,255,255,0.2); border: none; color: white; 
            padding: 10px; cursor: pointer; border-radius: 50%; font-size: 1.2em;
            display: none; /* Shown when tilawat-overlay is active */
            width: 40px; height: 40px; line-height: 20px; text-align: center;
        }
        #exit-tilawat-mode-btn { margin-top: 20px; background-color: var(--highlight-color); color:white; }
        
        #tilawat-navigation { text-align:center; margin-top:30px; padding-bottom: 20px; }
        #tilawat-navigation button { margin: 0 10px; }
        #tilawat-page-info { margin: 0 15px; font-size: 0.9em; }
        #tilawat-load-more-trigger { height: 50px; margin: 20px 0; opacity:0; } /* For IntersectionObserver */

        .main-footer { text-align:center; padding: 30px 0 15px 0; color: var(--accent-color); font-size: 0.9em; border-top: 1px solid var(--accent-bg); margin-top: 30px;}
        #toggle-tilawat-mode-main-icon { cursor:pointer; font-size:1.5em; margin-left: 15px; vertical-align: middle; }

        @media (max-width: 768px) {
            header.main-header h1 { font-size: 1.8em; }
            .arabic-text { font-size: 1.6em; }
            .urdu-translation { font-size: 1.3em; }
            .container { width: 95%; padding: 15px; }
            header.main-header nav a { padding: 6px 10px; margin: 0 2px; font-size: 0.9em;}
            #tilawat-controls-container { width: 250px; right: -270px; } /* Adjust for smaller screens */
            #tilawat-controls-container.open { right: 0; }
        }
		.surah-card {
    background: #d36fb9 !important;
}
    </style>
</head>
<body>
    <div class="user-auth">
        <?php if (is_logged_in()): ?>
            Welcome, <?php echo h($_SESSION['username']); ?>! |
            <a href="?action=profile">Profile</a> |
            <a href="?action=logout">Logout</a>
            <?php if (is_admin()): ?>
                | <a href="?action=admin_dashboard">Admin</a>
            <?php endif; ?>
        <?php else: ?>
            <a href="?action=login">Login</a> |
            <a href="?action=register">Register</a>
        <?php endif; ?>
        <span id="toggle-tilawat-mode-main-icon" title="Tilawat Mode"></span> <!-- Book icon -->
    </div>

    <header class="main-header">
        <h1><a href="?action=home"><?php echo APP_NAME; ?></a></h1>
        <nav>
            <a href="?action=home" class="<?php echo ($action === 'home' ? 'active' : ''); ?>">Home</a>
            <a href="?action=surahs_list" class="<?php echo ($action === 'surahs_list' ? 'active' : ''); ?>">Surahs</a>
            <!-- <a href="?action=juz_list">Juz</a> -->
            <?php if(is_logged_in()): ?>
            <a href="?action=bookmarks" class="<?php echo ($action === 'bookmarks' ? 'active' : ''); ?>">Bookmarks</a>
            <!-- <a href="?action=hifz_dashboard">Hifz Companion</a> -->
            <a href="?action=last_read" class="<?php echo ($action === 'last_read' ? 'active' : ''); ?>">Last Read</a>
            <?php endif; ?>
            <!-- <a href="?action=search_page">Search</a> -->
        </nav>
    </header>

    <div class="container">
        <?php echo $main_content; // Output the page-specific content ?>
    </div>

    <footer class="main-footer">
        <p>© <?php echo date('Y'); ?> <?php echo APP_AUTHOR; ?> - <?php echo APP_NAME; ?>. Version <?php echo APP_VERSION; ?></p>
    </footer>

    <!-- Tilawat Mode Overlay -->
    <div id="tilawat-overlay">
        <button id="toggle-tilawat-controls-btn" title="Toggle Controls">⚙</button> <!-- Gear icon -->
        
        <div id="tilawat-controls-container"> <!-- Renamed from tilawat-controls for clarity -->
            <h4>Tilawat Settings</h4>
            <div class="control-row">
                <label for="tilawat-surah-select">Surah:</label>
                <select id="tilawat-surah-select"></select>
            </div>
            <div class="control-row">
                <label for="tilawat-ayah-select">Ayah:</label>
                <select id="tilawat-ayah-select"></select>
            </div>
            <button id="tilawat-go-to-ayah-btn">Go</button>
            <hr style="border-color: #555; margin: 15px 0;">
            <div class="control-row">
                <label for="tilawat-lines-per-page-input">Lines/Page (approx.):</label>
                <input type="number" id="tilawat-lines-per-page-input" value="10" min="1" max="50">
            </div>
            <div class="control-row">
                <label for="tilawat-font-size-slider">Font Size: <span id="tilawat-font-size-value-span">36px</span></label>
                <input type="range" id="tilawat-font-size-slider" min="18" max="72" value="36" style="width:100%;">
            </div>
            <div class="control-row" style="align-items: center;">
                <input type="checkbox" id="tilawat-show-translation-checkbox" style="width: auto; margin-right: 8px;">
                <label for="tilawat-show-translation-checkbox" style="margin-bottom:0; display:inline;">Show Translation</label>
            </div>
            <div class="control-row">
                <label for="tilawat-view-mode-select">View Mode:</label>
                <select id="tilawat-view-mode-select">
                    <option value="paginated">Paginated</option>
                    <option value="continuous">Continuous Scroll</option>
                </select>
            </div>
            <hr style="border-color: #555; margin: 15px 0;">
            <button id="exit-tilawat-mode-btn">Exit Tilawat Mode</button>
        </div>
        
        <div id="tilawat-content-wrapper">
            <div id="tilawat-content">
                <!-- Quran text will be loaded here -->
            </div>
            <div id="tilawat-navigation">
                <button id="tilawat-prev-page-btn">Previous</button>
                <span id="tilawat-page-info-span"></span>
                <button id="tilawat-next-page-btn">Next</button>
            </div>
            <div id="tilawat-load-more-trigger"></div> <!-- For IntersectionObserver -->
        </div>
    </div>
    
    <script>
        // Pass initial user settings from PHP to JavaScript
        const initialUserSettings = <?php echo $initial_js_user_settings ?: 'null'; ?>;
        const CSRF_TOKEN = '<?php echo $csrf_token; ?>';
        const IS_LOGGED_IN = <?php echo json_encode(is_logged_in()); ?>;
    </script>
    <script>
    // Embedded JavaScript (Main Application Logic)
    document.addEventListener('DOMContentLoaded', function() {
        // Tilawat Mode Elements
        const tilawatOverlay = document.getElementById('tilawat-overlay');
        const tilawatContent = document.getElementById('tilawat-content');
        const tilawatControlsContainer = document.getElementById('tilawat-controls-container');
        const toggleTilawatControlsBtn = document.getElementById('toggle-tilawat-controls-btn');
        const exitTilawatModeBtn = document.getElementById('exit-tilawat-mode-btn');
        const toggleTilawatModeMainIcon = document.getElementById('toggle-tilawat-mode-main-icon');

        // Tilawat Controls
        const tilawatSurahSelect = document.getElementById('tilawat-surah-select');
        const tilawatAyahSelect = document.getElementById('tilawat-ayah-select');
        const tilawatGoToAyahBtn = document.getElementById('tilawat-go-to-ayah-btn');
        const linesPerPageInput = document.getElementById('tilawat-lines-per-page-input');
        const fontSizeSlider = document.getElementById('tilawat-font-size-slider');
        const fontSizeValueSpan = document.getElementById('tilawat-font-size-value-span');
        const showTranslationCheckbox = document.getElementById('tilawat-show-translation-checkbox');
        const viewModeSelect = document.getElementById('tilawat-view-mode-select');
        
        // Tilawat Navigation
        const prevPageBtn = document.getElementById('tilawat-prev-page-btn');
        const nextPageBtn = document.getElementById('tilawat-next-page-btn');
        const pageInfoSpan = document.getElementById('tilawat-page-info-span');
        const loadMoreTrigger = document.getElementById('tilawat-load-more-trigger');

        let surahAyahDataGlobal = {}; // Stores all surah info {id: {name_ar, name_en, total_ayahs}}

        let currentTilawatState = {
            surah_id: 1,
            ayah_id: 1, // Current starting ayah for fetch
            page: 1,    // For paginated mode: current page number
            linesPerPage: 10,
            fontSize: 36,
            showTranslation: false,
            viewMode: 'paginated',
            isLoading: false,
            // For continuous scroll, these track the start of the *next* chunk
            nextChunkSurahId: null, 
            nextChunkAyahId: null,
            // For paginated, these track the start of the *previous* page
            prevPageStartSurahId: null,
            prevPageStartAyahId: null,
        };
        
        // Initialize from PHP-provided settings if available
        if (IS_LOGGED_IN && initialUserSettings) {
            currentTilawatState.linesPerPage = initialUserSettings.tilawat_lines_per_page || 10;
            currentTilawatState.fontSize = initialUserSettings.tilawat_font_size || 36;
            currentTilawatState.showTranslation = !!initialUserSettings.tilawat_show_translation; // Convert 0/1 to boolean
            currentTilawatState.viewMode = initialUserSettings.tilawat_view_mode || 'paginated';
            currentTilawatState.surah_id = initialUserSettings.last_read_surah || 1;
            currentTilawatState.ayah_id = initialUserSettings.last_read_ayah || 1;
        }
        
        async function fetchAllSurahDataOnce() {
            if (Object.keys(surahAyahDataGlobal).length > 0) return; // Already fetched
            try {
                const response = await fetch('?action=get_surah_ayah_counts');
                if (!response.ok) throw new Error('Failed to fetch surah data (status: '+response.status+')');
                surahAyahDataGlobal = await response.json();
                populateSurahSelect();
            } catch (error) {
                console.error("Error fetching Surah/Ayah counts:", error);
                // Maybe show an error in UI, but for now, Tilawat selectors won't populate well.
            }
        }

        function populateSurahSelect() {
            tilawatSurahSelect.innerHTML = '';
            for (const id in surahAyahDataGlobal) {
                const surah = surahAyahDataGlobal[id];
                const option = document.createElement('option');
                option.value = surah.id; // Use surah.id from fetched data
                option.textContent = `${surah.id}. ${surah.name_ar} (${surah.name_en})`;
                tilawatSurahSelect.appendChild(option);
            }
            // Set to current state or default after populating
            tilawatSurahSelect.value = currentTilawatState.surah_id;
            populateAyahSelectForSurah(currentTilawatState.surah_id);
        }

        function populateAyahSelectForSurah(surahId) {
            tilawatAyahSelect.innerHTML = '';
            const surah = surahAyahDataGlobal[surahId];
            if (surah && surah.total_ayahs > 0) {
                for (let i = 1; i <= surah.total_ayahs; i++) {
                    const option = document.createElement('option');
                    option.value = i;
                    option.textContent = `Ayah ${i}`;
                    tilawatAyahSelect.appendChild(option);
                }
                // Set to current state or default. If current ayah_id is beyond new surah's total, reset to 1.
                if (currentTilawatState.surah_id == surahId && currentTilawatState.ayah_id <= surah.total_ayahs) {
                     tilawatAyahSelect.value = currentTilawatState.ayah_id;
                } else {
                     tilawatAyahSelect.value = 1; // Default to Ayah 1 if current ayah is out of bounds
                }
            } else {
                 // Add a disabled option if no ayahs or surah not found
                const option = document.createElement('option');
                option.value = "";
                option.textContent = "No Ayahs";
                option.disabled = true;
                tilawatAyahSelect.appendChild(option);
            }
        }
        
        function applyTilawatVisualSettings() {
            tilawatContent.style.fontSize = `${currentTilawatState.fontSize}px`;
            fontSizeValueSpan.textContent = `${currentTilawatState.fontSize}px`;
            fontSizeSlider.value = currentTilawatState.fontSize;

            const translationElements = tilawatContent.querySelectorAll('.tilawat-translation');
            translationElements.forEach(el => {
                el.style.display = currentTilawatState.showTranslation ? 'block' : 'none';
                el.style.fontSize = `${Math.max(12, currentTilawatState.fontSize * 0.65)}px`; // Relative size
            });
            
            if (currentTilawatState.viewMode === 'paginated') {
                document.getElementById('tilawat-navigation').style.display = 'block';
                loadMoreTrigger.style.display = 'none';
            } else { // continuous
                document.getElementById('tilawat-navigation').style.display = 'none';
                loadMoreTrigger.style.display = 'block';
            }
        }

        async function loadTilawatData(isFreshLoad = true) {
            if (currentTilawatState.isLoading) return;
            currentTilawatState.isLoading = true;
            if (isFreshLoad) tilawatContent.innerHTML = '<p style="text-align:center;">Loading...</p>';

            let fetchUrl = `?action=get_quran_data_for_tilawat&surah_id=${currentTilawatState.surah_id}&start_ayah=${currentTilawatState.ayah_id}&lines=${currentTilawatState.linesPerPage}&mode=${currentTilawatState.viewMode}`;
            // `page` param is not used here as API returns based on surah/ayah/lines directly, handling pagination/chunking logic

            try {
                const response = await fetch(fetchUrl);
                if (!response.ok) throw new Error(`Network error: ${response.status}`);
                const data = await response.json();

                if (data.error) {
                    console.error("Error from API:", data.error);
                    tilawatContent.innerHTML = `<p class='error'>Error: ${data.error}</p>`;
                    return;
                }

                if (isFreshLoad) tilawatContent.innerHTML = ''; // Clear "Loading..." or previous content

                let displayedSurahId = -1; // Track current surah for headers/Bismillah

                data.ayahs.forEach((ayah, index) => {
                    // Display Surah Header and Bismillah if it's a new Surah in this chunk
                    if (ayah.surah_id !== displayedSurahId) {
                        displayedSurahId = ayah.surah_id;
                        const surahInfo = surahAyahDataGlobal[ayah.surah_id];
                        if (surahInfo) {
                            const surahHeader = document.createElement('h3');
                            surahHeader.className = 'tilawat-surah-header';
                            surahHeader.textContent = `سورة ${surahInfo.name_ar}`;
                            tilawatContent.appendChild(surahHeader);
                        }
                        // Add Bismillah if not Al-Fatiha (1) or At-Tawbah (9), and it's the first ayah of the surah
                        if (ayah.ayah_id === 1 && ayah.surah_id !== 1 && ayah.surah_id !== 9) {
                            const bismillah = document.createElement('p');
                            bismillah.className = 'tilawat-arabic tilawat-bismillah';
                            bismillah.textContent = 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ';
                            tilawatContent.appendChild(bismillah);
                        }
                    }

                    const ayahDiv = document.createElement('div');
                    ayahDiv.className = 'ayah-group';
                    ayahDiv.id = `tilawat-ayah-${ayah.surah_id}-${ayah.ayah_id}`;
                    
                    const arabicP = document.createElement('p');
                    arabicP.className = 'tilawat-arabic';
                    const ayahNumFormatted = `﴿${ayah.ayah_id}﴾`; // Standard Ayah number display
                    arabicP.innerHTML = `${ayah.arabic_text} <span class="ayah-number-display">${ayahNumFormatted}</span>`;
                    
                    const urduP = document.createElement('p');
                    urduP.className = 'tilawat-translation';
                    urduP.textContent = ayah.urdu_translation || ""; // Ensure not null
                    
                    ayahDiv.appendChild(arabicP);
                    ayahDiv.appendChild(urduP);
                    tilawatContent.appendChild(ayahDiv);
                });
                
                applyTilawatVisualSettings();

                // Update state for next/prev operations
                currentTilawatState.nextChunkSurahId = data.next_surah_id;
                currentTilawatState.nextChunkAyahId = data.next_ayah_id;

                // For paginated mode, calculate where "previous" would start
                // This is a simplified previous logic; true previous needs careful calculation across surah boundaries backward
                if (data.ayahs.length > 0) {
                    const firstAyahOfCurrentPage = data.ayahs[0];
                    // This would be more complex to calculate accurately backwards. For now, previous button logic will just use page--.
                    // A robust solution for prev_page_start_... would involve an API endpoint or complex client-side calculation.
                }


                updateTilawatNavigationControls(data.ayahs.length);
                
                if (IS_LOGGED_IN && data.ayahs.length > 0) {
                    // Update last read to the first ayah of the currently displayed set
                    updateUserLastReadOnServer(data.ayahs[0].surah_id, data.ayahs[0].ayah_id);
                }

            } catch (error) {
                console.error("Error in loadTilawatData:", error);
                tilawatContent.innerHTML = "<p class='error'>Error loading content. Please check console and try again.</p>";
            } finally {
                currentTilawatState.isLoading = false;
            }
        }

        function updateTilawatNavigationControls(numAyahsFetched) {
            if (currentTilawatState.viewMode === 'paginated') {
                pageInfoSpan.textContent = `Page ${currentTilawatState.page}`;
                prevPageBtn.disabled = (currentTilawatState.page <= 1 && currentTilawatState.surah_id === 1 && currentTilawatState.ayah_id ===1); // Simplistic
                nextPageBtn.disabled = (currentTilawatState.nextChunkSurahId === null || numAyahsFetched < currentTilawatState.linesPerPage);
            }
        }
        
        function updateUserLastReadOnServer(surahId, ayahId) {
            if (!IS_LOGGED_IN) return;
            fetch('?action=update_last_read', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: `csrf_token=${encodeURIComponent(CSRF_TOKEN)}&surah_id=${surahId}&ayah_id=${ayahId}`
            })
            .then(response => response.json())
            .then(data => { 
                if(data.status !== 'success') console.warn("Failed to update last read on server:", data.message);
                else { // Update local copy of user settings if successful
                    if (initialUserSettings) { // Check if the global var exists
                        initialUserSettings.last_read_surah = surahId;
                        initialUserSettings.last_read_ayah = ayahId;
                    }
                }
            })
            .catch(error => console.error("Error updating last read on server:", error));
        }
        
        function saveCurrentTilawatSettingsToServer() {
            if (!IS_LOGGED_IN) return;
            const settingsPayload = {
                lines_per_page: currentTilawatState.linesPerPage,
                font_size: currentTilawatState.fontSize,
                show_translation: currentTilawatState.showTranslation ? 1 : 0,
                view_mode: currentTilawatState.viewMode,
                csrf_token: CSRF_TOKEN
            };
            fetch('?action=save_user_tilawat_settings', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: new URLSearchParams(settingsPayload).toString()
            })
            .then(response => response.json())
            .then(data => { 
                if(data.status !== 'success') console.warn('Failed to save Tilawat settings:', data.message);
                else { // Update local copy of user settings if successful
                     if (initialUserSettings) {
                        initialUserSettings.tilawat_lines_per_page = currentTilawatState.linesPerPage;
                        initialUserSettings.tilawat_font_size = currentTilawatState.fontSize;
                        initialUserSettings.tilawat_show_translation = currentTilawatState.showTranslation;
                        initialUserSettings.tilawat_view_mode = currentTilawatState.viewMode;
                     }
                }
            })
            .catch(error => console.error('Error saving Tilawat settings:', error));
        }

        // Event Listeners for Tilawat Mode Activation and Controls
        if (toggleTilawatModeMainIcon) {
            toggleTilawatModeMainIcon.addEventListener('click', async () => {
                if (!IS_LOGGED_IN) {
                     alert("Please log in to use Tilawat mode.");
                     window.location.href = "?action=login";
                     return;
                }
                await fetchAllSurahDataOnce(); // Ensure surah data is loaded before proceeding
                
                // Restore user settings or defaults into currentTilawatState
                currentTilawatState.linesPerPage = (initialUserSettings && initialUserSettings.tilawat_lines_per_page) || 10;
                currentTilawatState.fontSize = (initialUserSettings && initialUserSettings.tilawat_font_size) || 36;
                currentTilawatState.showTranslation = !!(initialUserSettings && initialUserSettings.tilawat_show_translation);
                currentTilawatState.viewMode = (initialUserSettings && initialUserSettings.tilawat_view_mode) || 'paginated';
                currentTilawatState.surah_id = (initialUserSettings && initialUserSettings.last_read_surah) || 1;
                currentTilawatState.ayah_id = (initialUserSettings && initialUserSettings.last_read_ayah) || 1;
                currentTilawatState.page = 1; // Always start on page 1 when entering

                // Update UI controls to reflect currentTilawatState
                linesPerPageInput.value = currentTilawatState.linesPerPage;
                fontSizeSlider.value = currentTilawatState.fontSize;
                fontSizeValueSpan.textContent = `${currentTilawatState.fontSize}px`;
                showTranslationCheckbox.checked = currentTilawatState.showTranslation;
                viewModeSelect.value = currentTilawatState.viewMode;
                tilawatSurahSelect.value = currentTilawatState.surah_id;
                populateAyahSelectForSurah(currentTilawatState.surah_id); // This will also set ayah select value

                tilawatOverlay.style.display = 'block';
                toggleTilawatControlsBtn.style.display = 'block';
                document.body.style.overflow = 'hidden'; // Prevent main page scroll
                loadTilawatData(true);
            });
        }

        if (exitTilawatModeBtn) {
            exitTilawatModeBtn.addEventListener('click', () => {
                tilawatOverlay.style.display = 'none';
                toggleTilawatControlsBtn.style.display = 'none';
                tilawatControlsContainer.classList.remove('open'); // Close panel if open
                document.body.style.overflow = 'auto';
                saveCurrentTilawatSettingsToServer();
            });
        }
        
        if (toggleTilawatControlsBtn) {
            toggleTilawatControlsBtn.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent closing if clicked inside panel later
                tilawatControlsContainer.classList.toggle('open');
            });
        }
        // Close control panel if clicking outside of it
        document.addEventListener('click', function(event) {
            if (tilawatControlsContainer.classList.contains('open') && 
                !tilawatControlsContainer.contains(event.target) && 
                event.target !== toggleTilawatControlsBtn) {
                tilawatControlsContainer.classList.remove('open');
            }
        });


        // Tilawat Control Panel Interactions
        if (tilawatSurahSelect) {
            tilawatSurahSelect.addEventListener('change', () => {
                const newSurahId = parseInt(tilawatSurahSelect.value);
                populateAyahSelectForSurah(newSurahId);
                // Don't auto-load, user clicks "Go"
            });
        }
        
        if (tilawatGoToAyahBtn) {
            tilawatGoToAyahBtn.addEventListener('click', () => {
                currentTilawatState.surah_id = parseInt(tilawatSurahSelect.value);
                currentTilawatState.ayah_id = parseInt(tilawatAyahSelect.value) || 1; // Default to 1 if not selected
                currentTilawatState.page = 1; // Reset to page 1
                loadTilawatData(true);
                tilawatControlsContainer.classList.remove('open'); // Hide controls panel
            });
        }

        if (linesPerPageInput) {
            linesPerPageInput.addEventListener('change', () => {
                currentTilawatState.linesPerPage = parseInt(linesPerPageInput.value) || 10;
                currentTilawatState.page = 1; // Reset page as content per page changes
                loadTilawatData(true);
                // No need to explicitly save here, will save on exit or other major changes
            });
        }

        if (fontSizeSlider) {
            fontSizeSlider.addEventListener('input', () => { // Update visuals immediately
                currentTilawatState.fontSize = parseInt(fontSizeSlider.value);
                applyTilawatVisualSettings();
            });
            fontSizeSlider.addEventListener('change', () => { // Save on final change (e.g. mouse up)
                 saveCurrentTilawatSettingsToServer(); // Save this specific setting
            });
        }
        
        if (showTranslationCheckbox) {
            showTranslationCheckbox.addEventListener('change', () => {
                currentTilawatState.showTranslation = showTranslationCheckbox.checked;
                applyTilawatVisualSettings();
                 saveCurrentTilawatSettingsToServer(); // Save this specific setting
            });
        }

        if (viewModeSelect) {
            viewModeSelect.addEventListener('change', () => {
                currentTilawatState.viewMode = viewModeSelect.value;
                currentTilawatState.page = 1; // Reset page
                if (currentTilawatState.viewMode === 'continuous') {
                    tilawatContent.innerHTML = ''; // Clear for fresh continuous scroll
                }
                loadTilawatData(true);
                 saveCurrentTilawatSettingsToServer(); // Save this specific setting
            });
        }

        // Tilawat Navigation Button Listeners
        if (prevPageBtn) {
            prevPageBtn.addEventListener('click', () => {
                if (currentTilawatState.page > 1) {
                    currentTilawatState.page--;
                    // To correctly go to the previous page, we need to determine the start ayah of that page.
                    // This is complex if ayahs per page varies or crosses Surah boundaries.
                    // Simplification: assume API will fetch the "previous block" of N lines if we just give it
                    // the first ayah of the *current* page, and ask for N lines *before* it.
                    // OR, the API could take `page` and `linesPerPage` and calculate.
                    // Current API fetches *from* surah_id, ayah_id. So, we need to calculate where previous page *started*.
                    // This is a non-trivial calculation to do robustly client-side backwards across surahs.
                    // A simpler (but less accurate "page") model:
                    // Just set currentTilawatState.ayah_id to the first ayah of what *should* be the previous page.
                    // This requires a more stateful client or smarter API.
                    // For now, this will re-fetch using decremented page number, but API needs to honor it.
                    // The current get_quran_data_for_tilawat doesn't use 'page' param from URL.
                    // Let's adjust loadTilawatData call for prev/next:
                    // We must set currentTilawatState.surah_id and ayah_id to the start of the desired previous page.
                    // This is the hard part. For now, previous button is mostly conceptual until robust backward nav logic is added.
                    // For now, let prev simply try to reload with page-1. The API needs to be designed for this.
                    // The current API is designed for forward fetching. A true paginated prev requires a different API or more client logic.
                    console.warn("Previous page logic is simplified and may not work perfectly across Surah boundaries yet.");
                    // Attempt to load, hoping API could handle it or client-side state `prevPageStartSurahId` exists.
                    if (currentTilawatState.prevPageStartSurahId && currentTilawatState.prevPageStartAyahId) {
                        currentTilawatState.surah_id = currentTilawatState.prevPageStartSurahId;
                        currentTilawatState.ayah_id = currentTilawatState.prevPageStartAyahId;
                        loadTilawatData(true);
                    } else {
                        // Fallback or error, as we don't have enough info for robust previous.
                        // Perhaps disable prev button if page is 1.
                    }
                }
            });
        }

        if (nextPageBtn) {
            nextPageBtn.addEventListener('click', () => {
                if (currentTilawatState.nextChunkSurahId && currentTilawatState.nextChunkAyahId) {
                    currentTilawatState.surah_id = currentTilawatState.nextChunkSurahId;
                    currentTilawatState.ayah_id = currentTilawatState.nextChunkAyahId;
                    currentTilawatState.page++;
                    loadTilawatData(true);
                } else {
                    console.log("End of Quran or no next page data.");
                    nextPageBtn.disabled = true;
                }
            });
        }

        // Intersection Observer for Continuous Scroll
        let observer;
        if (loadMoreTrigger) {
            const observerOptions = { root: null, rootMargin: '0px', threshold: 0.1 };
            observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && 
                        currentTilawatState.viewMode === 'continuous' && 
                        !currentTilawatState.isLoading &&
                        currentTilawatState.nextChunkSurahId !== null) { // Check if there's more to load
                        
                        currentTilawatState.surah_id = currentTilawatState.nextChunkSurahId;
                        currentTilawatState.ayah_id = currentTilawatState.nextChunkAyahId;
                        loadTilawatData(false); // Append content
                    }
                });
            }, observerOptions);
            observer.observe(loadMoreTrigger);
        }

        // Keyboard navigation for Tilawat Mode
        document.addEventListener('keydown', (e) => {
            if (tilawatOverlay.style.display !== 'block' || e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' || e.target.tagName === 'TEXTAREA') return;

            if (e.key === 'ArrowRight' || e.key === 'PageDown') {
                e.preventDefault();
                if (currentTilawatState.viewMode === 'paginated' && !nextPageBtn.disabled) nextPageBtn.click();
                // For continuous, scrolling down naturally triggers IntersectionObserver
            } else if (e.key === 'ArrowLeft' || e.key === 'PageUp') {
                e.preventDefault();
                if (currentTilawatState.viewMode === 'paginated' && !prevPageBtn.disabled) prevPageBtn.click();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                exitTilawatModeBtn.click();
            }
        });

        // Bookmark buttons in main Surah view
        document.querySelectorAll('.bookmark-btn').forEach(button => {
            button.addEventListener('click', function() {
                if (!IS_LOGGED_IN) { alert("Please login to use bookmarks."); return; }
                const surahId = this.dataset.surah;
                const ayahId = this.dataset.ayah;
                
                fetch('?action=toggle_bookmark', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: `csrf_token=${encodeURIComponent(CSRF_TOKEN)}&surah_id=${surahId}&ayah_id=${ayahId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        this.textContent = data.action === 'added' ? 'Bookmarked' : 'Bookmark';
                        this.classList.toggle('active', data.action === 'added');
                        // Could add a small notification/toast message here.
                    } else {
                        alert("Error: " + data.message);
                    }
                })
                .catch(error => {
                    console.error("Bookmark error:", error);
                    alert("An error occurred while updating bookmark.");
                });
            });
        });

        // Initial call to fetch Surah data if not already fetched (e.g. if user directly lands on a page needing it)
        // This is more for robustness, typically it's called when Tilawat mode is entered.
        // fetchAllSurahDataOnce();
    });
    </script>
</body>
</html>
<?php
// End of file.
?>

<script>
const fontUrl = "https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu&display=swap";

const link = document.createElement("link");
link.rel = "stylesheet";
link.href = fontUrl;
document.head.appendChild(link);

const style = document.createElement("style");
style.innerHTML = `
  * {
    font-family: 'Noto Nastaliq Urdu', serif !important;
  }
  input, textarea, select, button {
    font-family: 'Noto Nastaliq Urdu', serif !important;
  }
`;
document.head.appendChild(style);
</script>