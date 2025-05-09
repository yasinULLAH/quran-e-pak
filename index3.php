<?php
ob_start();
/*
Quranic Study Platform
Author: Yasin Ullah
Pakistani
Single PHP File Web Application
*/

// --- CONFIGURATION ---
define('DB_FILE', __DIR__ . '/quran_study.db');
define('DATA_AM_FILE', __DIR__ . '/data/data.AM'); // Create 'data' subdir and place data.AM here
define('SITE_TITLE', 'Quranic Study Platform');
define('CSRF_TOKEN_NAME', 'csrf_token');
define('RECORDS_PER_PAGE', 20); // For pagination

// User Roles
define('ROLE_PUBLIC', 'public');
define('ROLE_USER', 'user');
define('ROLE_ULAMA', 'ulama');
define('ROLE_ADMIN', 'admin');

// --- INITIALIZATION ---
session_start();
// error_reporting(E_ALL); // For development
// ini_set('display_errors', 1); // For development
error_reporting(0); // Production setting
ini_set('display_errors', 0); // Production setting

// Globals for passing messages to page templates for login/register
$GLOBALS['page_message'] = '';
$GLOBALS['page_message_type'] = ''; // 'success' or 'error'


// --- GLOBAL DATA ---

$SURAH_DATA = [
    1 => ['id' => 1, 'name_arabic' => 'ٱلْفَاتِحَة', 'name_english' => 'Al-Fatiha', 'revelation_type' => 'Makki', 'total_ayahs' => 7],
    2 => ['id' => 2, 'name_arabic' => 'ٱلْبَقَرَة', 'name_english' => 'Al-Baqara', 'revelation_type' => 'Madani', 'total_ayahs' => 286],
    3 => ['id' => 3, 'name_arabic' => 'آلِ عِمْرَان', 'name_english' => 'Aal-i-Imran', 'revelation_type' => 'Madani', 'total_ayahs' => 200],
    4 => ['id' => 4, 'name_arabic' => 'ٱلنِّسَاء', 'name_english' => 'An-Nisa', 'revelation_type' => 'Madani', 'total_ayahs' => 176],
    5 => ['id' => 5, 'name_arabic' => 'ٱلْمَائِدَة', 'name_english' => 'Al-Ma idah', 'revelation_type' => 'Madani', 'total_ayahs' => 120],
    6 => ['id' => 6, 'name_arabic' => 'ٱلْأَنْعَام', 'name_english' => 'Al-An am', 'revelation_type' => 'Makki', 'total_ayahs' => 165],
    7 => ['id' => 7, 'name_arabic' => 'ٱلْأَعْرَاف', 'name_english' => 'Al-A raf', 'revelation_type' => 'Makki', 'total_ayahs' => 206],
    8 => ['id' => 8, 'name_arabic' => 'ٱلْأَنْفَال', 'name_english' => 'Al-Anfal', 'revelation_type' => 'Madani', 'total_ayahs' => 75],
    9 => ['id' => 9, 'name_arabic' => 'ٱلتَّوْبَة', 'name_english' => 'At-Tawbah', 'revelation_type' => 'Madani', 'total_ayahs' => 129],
    10 => ['id' => 10, 'name_arabic' => 'يُونُس', 'name_english' => 'Yunus', 'revelation_type' => 'Makki', 'total_ayahs' => 109],
    11 => ['id' => 11, 'name_arabic' => 'هُود', 'name_english' => 'Hud', 'revelation_type' => 'Makki', 'total_ayahs' => 123],
    12 => ['id' => 12, 'name_arabic' => 'يُوسُف', 'name_english' => 'Yusuf', 'revelation_type' => 'Makki', 'total_ayahs' => 111],
    13 => ['id' => 13, 'name_arabic' => 'ٱلرَّعْد', 'name_english' => 'Ar-Ra d', 'revelation_type' => 'Madani', 'total_ayahs' => 43],
    14 => ['id' => 14, 'name_arabic' => 'إِبْرَاهِيم', 'name_english' => 'Ibrahim', 'revelation_type' => 'Makki', 'total_ayahs' => 52],
    15 => ['id' => 15, 'name_arabic' => 'ٱلْحِجْر', 'name_english' => 'Al-Hijr', 'revelation_type' => 'Makki', 'total_ayahs' => 99],
    16 => ['id' => 16, 'name_arabic' => 'ٱلنَّحْل', 'name_english' => 'An-Nahl', 'revelation_type' => 'Makki', 'total_ayahs' => 128],
    17 => ['id' => 17, 'name_arabic' => 'ٱلْإِسْرَاء', 'name_english' => 'Al-Isra', 'revelation_type' => 'Makki', 'total_ayahs' => 111],
    18 => ['id' => 18, 'name_arabic' => 'ٱلْكَهْف', 'name_english' => 'Al-Kahf', 'revelation_type' => 'Makki', 'total_ayahs' => 110],
    19 => ['id' => 19, 'name_arabic' => 'مَرْيَم', 'name_english' => 'Maryam', 'revelation_type' => 'Makki', 'total_ayahs' => 98],
    20 => ['id' => 20, 'name_arabic' => 'طه', 'name_english' => 'Taha', 'revelation_type' => 'Makki', 'total_ayahs' => 135],
    21 => ['id' => 21, 'name_arabic' => 'ٱلْأَنْبِيَاء', 'name_english' => 'Al-Anbiya', 'revelation_type' => 'Makki', 'total_ayahs' => 112],
    22 => ['id' => 22, 'name_arabic' => 'ٱلْحَجّ', 'name_english' => 'Al-Hajj', 'revelation_type' => 'Madani', 'total_ayahs' => 78],
    23 => ['id' => 23, 'name_arabic' => 'ٱلْمُؤْمِنُون', 'name_english' => 'Al-Mu minun', 'revelation_type' => 'Makki', 'total_ayahs' => 118],
    24 => ['id' => 24, 'name_arabic' => 'ٱلنُّور', 'name_english' => 'An-Nur', 'revelation_type' => 'Madani', 'total_ayahs' => 64],
    25 => ['id' => 25, 'name_arabic' => 'ٱلْفُرْقَان', 'name_english' => 'Al-Furqan', 'revelation_type' => 'Makki', 'total_ayahs' => 77],
    26 => ['id' => 26, 'name_arabic' => 'ٱلشُّعَرَاء', 'name_english' => 'Ash-Shu ara', 'revelation_type' => 'Makki', 'total_ayahs' => 227],
    27 => ['id' => 27, 'name_arabic' => 'ٱلنَّمْل', 'name_english' => 'An-Naml', 'revelation_type' => 'Makki', 'total_ayahs' => 93],
    28 => ['id' => 28, 'name_arabic' => 'ٱلْقَصَص', 'name_english' => 'Al-Qasas', 'revelation_type' => 'Makki', 'total_ayahs' => 88],
    29 => ['id' => 29, 'name_arabic' => 'ٱلْعَنْكَبُوت', 'name_english' => 'Al-Ankabut', 'revelation_type' => 'Makki', 'total_ayahs' => 69],
    30 => ['id' => 30, 'name_arabic' => 'ٱلرُّوم', 'name_english' => 'Ar-Rum', 'revelation_type' => 'Makki', 'total_ayahs' => 60],
    31 => ['id' => 31, 'name_arabic' => 'لُقْمَان', 'name_english' => 'Luqman', 'revelation_type' => 'Makki', 'total_ayahs' => 34],
    32 => ['id' => 32, 'name_arabic' => 'ٱلسَّجْدَة', 'name_english' => 'As-Sajdah', 'revelation_type' => 'Makki', 'total_ayahs' => 30],
    33 => ['id' => 33, 'name_arabic' => 'ٱلْأَحْزَاب', 'name_english' => 'Al-Ahzab', 'revelation_type' => 'Madani', 'total_ayahs' => 73],
    34 => ['id' => 34, 'name_arabic' => 'سَبَأ', 'name_english' => 'Saba', 'revelation_type' => 'Makki', 'total_ayahs' => 54],
    35 => ['id' => 35, 'name_arabic' => 'فَاطِر', 'name_english' => 'Fatir', 'revelation_type' => 'Makki', 'total_ayahs' => 45],
    36 => ['id' => 36, 'name_arabic' => 'يس', 'name_english' => 'Ya-Sin', 'revelation_type' => 'Makki', 'total_ayahs' => 83],
    37 => ['id' => 37, 'name_arabic' => 'ٱلصَّافَّات', 'name_english' => 'As-Saffat', 'revelation_type' => 'Makki', 'total_ayahs' => 182],
    38 => ['id' => 38, 'name_arabic' => 'ص', 'name_english' => 'Sad', 'revelation_type' => 'Makki', 'total_ayahs' => 88],
    39 => ['id' => 39, 'name_arabic' => 'ٱلزُّمَر', 'name_english' => 'Az-Zumar', 'revelation_type' => 'Makki', 'total_ayahs' => 75],
    40 => ['id' => 40, 'name_arabic' => 'غَافِر', 'name_english' => 'Ghafir', 'revelation_type' => 'Makki', 'total_ayahs' => 85],
    41 => ['id' => 41, 'name_arabic' => 'فُصِّلَت', 'name_english' => 'Fussilat', 'revelation_type' => 'Makki', 'total_ayahs' => 54],
    42 => ['id' => 42, 'name_arabic' => 'ٱلشُّورَىٰ', 'name_english' => 'Ash-Shuraa', 'revelation_type' => 'Makki', 'total_ayahs' => 53],
    43 => ['id' => 43, 'name_arabic' => 'ٱلزُّخْرُف', 'name_english' => 'Az-Zukhruf', 'revelation_type' => 'Makki', 'total_ayahs' => 89],
    44 => ['id' => 44, 'name_arabic' => 'ٱلدُّخَان', 'name_english' => 'Ad-Dukhan', 'revelation_type' => 'Makki', 'total_ayahs' => 59],
    45 => ['id' => 45, 'name_arabic' => 'ٱلْجَاثِيَة', 'name_english' => 'Al-Jathiyah', 'revelation_type' => 'Makki', 'total_ayahs' => 37],
    46 => ['id' => 46, 'name_arabic' => 'ٱلْأَحْقَاف', 'name_english' => 'Al-Ahqaf', 'revelation_type' => 'Makki', 'total_ayahs' => 35],
    47 => ['id' => 47, 'name_arabic' => 'مُحَمَّد', 'name_english' => 'Muhammad', 'revelation_type' => 'Madani', 'total_ayahs' => 38],
    48 => ['id' => 48, 'name_arabic' => 'ٱلْفَتْح', 'name_english' => 'Al-Fath', 'revelation_type' => 'Madani', 'total_ayahs' => 29],
    49 => ['id' => 49, 'name_arabic' => 'ٱلْحُجُرَات', 'name_english' => 'Al-Hujurat', 'revelation_type' => 'Madani', 'total_ayahs' => 18],
    50 => ['id' => 50, 'name_arabic' => 'ق', 'name_english' => 'Qaf', 'revelation_type' => 'Makki', 'total_ayahs' => 45],
    51 => ['id' => 51, 'name_arabic' => 'ٱلذَّارِيَات', 'name_english' => 'Adh-Dhariyat', 'revelation_type' => 'Makki', 'total_ayahs' => 60],
    52 => ['id' => 52, 'name_arabic' => 'ٱلطُّور', 'name_english' => 'At-Tur', 'revelation_type' => 'Makki', 'total_ayahs' => 49],
    53 => ['id' => 53, 'name_arabic' => 'ٱلنَّجْم', 'name_english' => 'An-Najm', 'revelation_type' => 'Makki', 'total_ayahs' => 62],
    54 => ['id' => 54, 'name_arabic' => 'ٱلْقَمَر', 'name_english' => 'Al-Qamar', 'revelation_type' => 'Makki', 'total_ayahs' => 55],
    55 => ['id' => 55, 'name_arabic' => 'ٱلرَّحْمَٰن', 'name_english' => 'Ar-Rahman', 'revelation_type' => 'Madani', 'total_ayahs' => 78],
    56 => ['id' => 56, 'name_arabic' => 'ٱلْوَاقِعَة', 'name_english' => 'Al-Waqi ah', 'revelation_type' => 'Makki', 'total_ayahs' => 96],
    57 => ['id' => 57, 'name_arabic' => 'ٱلْحَدِيد', 'name_english' => 'Al-Hadid', 'revelation_type' => 'Madani', 'total_ayahs' => 29],
    58 => ['id' => 58, 'name_arabic' => 'ٱلْمُجَادِلَة', 'name_english' => 'Al-Mujadila', 'revelation_type' => 'Madani', 'total_ayahs' => 22],
    59 => ['id' => 59, 'name_arabic' => 'ٱلْحَشْر', 'name_english' => 'Al-Hashr', 'revelation_type' => 'Madani', 'total_ayahs' => 24],
    60 => ['id' => 60, 'name_arabic' => 'ٱلُْمْتَحَنَة', 'name_english' => 'Al-Mumtahanah', 'revelation_type' => 'Madani', 'total_ayahs' => 13],
    61 => ['id' => 61, 'name_arabic' => 'ٱلصَّفّ', 'name_english' => 'As-Saf', 'revelation_type' => 'Madani', 'total_ayahs' => 14],
    62 => ['id' => 62, 'name_arabic' => 'ٱلْجُمُعَة', 'name_english' => 'Al-Jumu ah', 'revelation_type' => 'Madani', 'total_ayahs' => 11],
    63 => ['id' => 63, 'name_arabic' => 'ٱلْمُنَافِقُون', 'name_english' => 'Al-Munafiqun', 'revelation_type' => 'Madani', 'total_ayahs' => 11],
    64 => ['id' => 64, 'name_arabic' => 'ٱلتَّغَابُن', 'name_english' => 'At-Taghabun', 'revelation_type' => 'Madani', 'total_ayahs' => 18],
    65 => ['id' => 65, 'name_arabic' => 'ٱلطَّلَاق', 'name_english' => 'At-Talaq', 'revelation_type' => 'Madani', 'total_ayahs' => 12],
    66 => ['id' => 66, 'name_arabic' => 'ٱلتَّحْرِيم', 'name_english' => 'At-Tahrim', 'revelation_type' => 'Madani', 'total_ayahs' => 12],
    67 => ['id' => 67, 'name_arabic' => 'ٱلْمُلْك', 'name_english' => 'Al-Mulk', 'revelation_type' => 'Makki', 'total_ayahs' => 30],
    68 => ['id' => 68, 'name_arabic' => 'ٱلْقَلَم', 'name_english' => 'Al-Qalam', 'revelation_type' => 'Makki', 'total_ayahs' => 52],
    69 => ['id' => 69, 'name_arabic' => 'ٱلْحَاقَّة', 'name_english' => 'Al-Haqqah', 'revelation_type' => 'Makki', 'total_ayahs' => 52],
    70 => ['id' => 70, 'name_arabic' => 'ٱلْمَعَارِج', 'name_english' => 'Al-Ma arij', 'revelation_type' => 'Makki', 'total_ayahs' => 44],
    71 => ['id' => 71, 'name_arabic' => 'نُوح', 'name_english' => 'Nuh', 'revelation_type' => 'Makki', 'total_ayahs' => 28],
    72 => ['id' => 72, 'name_arabic' => 'ٱلْجِنّ', 'name_english' => 'Al-Jinn', 'revelation_type' => 'Makki', 'total_ayahs' => 28],
    73 => ['id' => 73, 'name_arabic' => 'ٱلْمُزَّمِّل', 'name_english' => 'Al-Muzzammil', 'revelation_type' => 'Makki', 'total_ayahs' => 20],
    74 => ['id' => 74, 'name_arabic' => 'ٱلْمُدَّثِّر', 'name_english' => 'Al-Muddaththir', 'revelation_type' => 'Makki', 'total_ayahs' => 56],
    75 => ['id' => 75, 'name_arabic' => 'ٱلْقِيَامَة', 'name_english' => 'Al-Qiyamah', 'revelation_type' => 'Makki', 'total_ayahs' => 40],
    76 => ['id' => 76, 'name_arabic' => 'ٱلْإِنْسَان', 'name_english' => 'Al-Insan', 'revelation_type' => 'Madani', 'total_ayahs' => 31],
    77 => ['id' => 77, 'name_arabic' => 'ٱلْمُرْسَلَات', 'name_english' => 'Al-Mursalat', 'revelation_type' => 'Makki', 'total_ayahs' => 50],
    78 => ['id' => 78, 'name_arabic' => 'ٱلنَّبَأ', 'name_english' => 'An-Naba', 'revelation_type' => 'Makki', 'total_ayahs' => 40],
    79 => ['id' => 79, 'name_arabic' => 'ٱلنَّازِعَات', 'name_english' => 'An-Nazi at', 'revelation_type' => 'Makki', 'total_ayahs' => 46],
    80 => ['id' => 80, 'name_arabic' => 'عَبَسَ', 'name_english' => 'Abasa', 'revelation_type' => 'Makki', 'total_ayahs' => 42],
    81 => ['id' => 81, 'name_arabic' => 'ٱلتَّكْوِير', 'name_english' => 'At-Takwir', 'revelation_type' => 'Makki', 'total_ayahs' => 29],
    82 => ['id' => 82, 'name_arabic' => 'ٱلْإِنْفِطَار', 'name_english' => 'Al-Infitar', 'revelation_type' => 'Makki', 'total_ayahs' => 19],
    83 => ['id' => 83, 'name_arabic' => 'ٱلْمُطَفِّفِين', 'name_english' => 'Al-Mutaffifin', 'revelation_type' => 'Makki', 'total_ayahs' => 36],
    84 => ['id' => 84, 'name_arabic' => 'ٱلْإِنْشِقَاق', 'name_english' => 'Al-Inshiqaq', 'revelation_type' => 'Makki', 'total_ayahs' => 25],
    85 => ['id' => 85, 'name_arabic' => 'ٱلْبُرُوج', 'name_english' => 'Al-Buruj', 'revelation_type' => 'Makki', 'total_ayahs' => 22],
    86 => ['id' => 86, 'name_arabic' => 'ٱلطَّارِق', 'name_english' => 'At-Tariq', 'revelation_type' => 'Makki', 'total_ayahs' => 17],
    87 => ['id' => 87, 'name_arabic' => 'ٱلْأَعْلَىٰ', 'name_english' => 'Al-Ala', 'revelation_type' => 'Makki', 'total_ayahs' => 19],
    88 => ['id' => 88, 'name_arabic' => 'ٱلْغَاشِيَة', 'name_english' => 'Al-Ghashiyah', 'revelation_type' => 'Makki', 'total_ayahs' => 26],
    89 => ['id' => 89, 'name_arabic' => 'ٱلْفَجْر', 'name_english' => 'Al-Fajr', 'revelation_type' => 'Makki', 'total_ayahs' => 30],
    90 => ['id' => 90, 'name_arabic' => 'ٱلْبَلَد', 'name_english' => 'Al-Balad', 'revelation_type' => 'Makki', 'total_ayahs' => 20],
    91 => ['id' => 91, 'name_arabic' => 'ٱلشَّمْس', 'name_english' => 'Ash-Shams', 'revelation_type' => 'Makki', 'total_ayahs' => 15],
    92 => ['id' => 92, 'name_arabic' => 'ٱللَّيْل', 'name_english' => 'Al-Layl', 'revelation_type' => 'Makki', 'total_ayahs' => 21],
    93 => ['id' => 93, 'name_arabic' => 'ٱلضُّحَىٰ', 'name_english' => 'Ad-Duhaa', 'revelation_type' => 'Makki', 'total_ayahs' => 11],
    94 => ['id' => 94, 'name_arabic' => 'ٱلشَّرْح', 'name_english' => 'Ash-Sharh', 'revelation_type' => 'Makki', 'total_ayahs' => 8],
    95 => ['id' => 95, 'name_arabic' => 'ٱلتِّين', 'name_english' => 'At-Tin', 'revelation_type' => 'Makki', 'total_ayahs' => 8],
    96 => ['id' => 96, 'name_arabic' => 'ٱلْعَلَق', 'name_english' => 'Al-Alaq', 'revelation_type' => 'Makki', 'total_ayahs' => 19],
    97 => ['id' => 97, 'name_arabic' => 'ٱلْقَدْر', 'name_english' => 'Al-Qadr', 'revelation_type' => 'Makki', 'total_ayahs' => 5],
    98 => ['id' => 98, 'name_arabic' => 'ٱلْبَيِّنَة', 'name_english' => 'Al-Bayyinah', 'revelation_type' => 'Madani', 'total_ayahs' => 8],
    99 => ['id' => 99, 'name_arabic' => 'ٱلزَّلْزَلَة', 'name_english' => 'Az-Zalzalah', 'revelation_type' => 'Madani', 'total_ayahs' => 8],
    100 => ['id' => 100, 'name_arabic' => 'ٱلْعَادِيَات', 'name_english' => 'Al-Adiyat', 'revelation_type' => 'Makki', 'total_ayahs' => 11],
    101 => ['id' => 101, 'name_arabic' => 'ٱلْقَارِعَة', 'name_english' => 'Al-Qari ah', 'revelation_type' => 'Makki', 'total_ayahs' => 11],
    102 => ['id' => 102, 'name_arabic' => 'ٱلتَّكَاثُر', 'name_english' => 'At-Takathur', 'revelation_type' => 'Makki', 'total_ayahs' => 8],
    103 => ['id' => 103, 'name_arabic' => 'ٱلْعَصْر', 'name_english' => 'Al-Asr', 'revelation_type' => 'Makki', 'total_ayahs' => 3],
    104 => ['id' => 104, 'name_arabic' => 'ٱلْهُمَزَة', 'name_english' => 'Al-Humazah', 'revelation_type' => 'Makki', 'total_ayahs' => 9],
    105 => ['id' => 105, 'name_arabic' => 'ٱلْفِيل', 'name_english' => 'Al-Fil', 'revelation_type' => 'Makki', 'total_ayahs' => 5],
    106 => ['id' => 106, 'name_arabic' => 'قُرَيْش', 'name_english' => 'Quraysh', 'revelation_type' => 'Makki', 'total_ayahs' => 4],
    107 => ['id' => 107, 'name_arabic' => 'ٱلْمَاعُون', 'name_english' => 'Al-Ma un', 'revelation_type' => 'Makki', 'total_ayahs' => 7],
    108 => ['id' => 108, 'name_arabic' => 'ٱلْكَوْثَر', 'name_english' => 'Al-Kawthar', 'revelation_type' => 'Makki', 'total_ayahs' => 3],
    109 => ['id' => 109, 'name_arabic' => 'ٱلْكَافِرُون', 'name_english' => 'Al-Kafirun', 'revelation_type' => 'Makki', 'total_ayahs' => 6],
    110 => ['id' => 110, 'name_arabic' => 'ٱلنَّصْر', 'name_english' => 'An-Nasr', 'revelation_type' => 'Madani', 'total_ayahs' => 3],
    111 => ['id' => 111, 'name_arabic' => 'ٱلْمَسَد', 'name_english' => 'Al-Masad', 'revelation_type' => 'Makki', 'total_ayahs' => 5],
    112 => ['id' => 112, 'name_arabic' => 'ٱلْإِخْلَاص', 'name_english' => 'Al-Ikhlas', 'revelation_type' => 'Makki', 'total_ayahs' => 4],
    113 => ['id' => 113, 'name_arabic' => 'ٱلْفَلَق', 'name_english' => 'Al-Falaq', 'revelation_type' => 'Makki', 'total_ayahs' => 5],
    114 => ['id' => 114, 'name_arabic' => 'ٱلنَّاس', 'name_english' => 'An-Nas', 'revelation_type' => 'Makki', 'total_ayahs' => 6],
];







// Juz starting points (Surah, Ayah) - Standard 114 Surah division
$JUZ_STARTS = [
    1 => [1,1], 2 => [2,142], 3 => [2,253], 4 => [3,93], 5 => [4,24],
    6 => [4,148], 7 => [5,83], 8 => [6,111], 9 => [7,88], 10 => [8,41],
    11 => [9,93], 12 => [11,6], 13 => [12,53], 14 => [15,1], 15 => [17,1],
    16 => [18,75], 17 => [21,1], 18 => [23,1], 19 => [25,21], 20 => [27,56],
    21 => [29,46], 22 => [33,31], 23 => [36,28], 24 => [39,32], 25 => [41,47],
    26 => [46,1], 27 => [51,31], 28 => [58,1], 29 => [67,1], 30 => [78,1]
];


// --- DATABASE ---
function get_db() {
    static $db = null;
    if ($db === null) {
        try {
            $db = new PDO('sqlite:' . DB_FILE);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $db->exec('PRAGMA foreign_keys = ON;');
        } catch (PDOException $e) {
            log_error("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please check error logs or contact administrator.");
        }
    }
    return $db;
}

function initialize_database() {
    $db = get_db();
    global $SURAH_DATA;

    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        role TEXT NOT NULL DEFAULT 'user', -- 'user', 'ulama', 'admin'
        is_verified_ulama INTEGER DEFAULT 0,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        settings TEXT -- JSON for user-specific settings
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS surahs (
        id INTEGER PRIMARY KEY, -- Surah number (1-114)
        name_arabic TEXT NOT NULL,
        name_english TEXT NOT NULL,
        revelation_type TEXT, -- Makki/Madani
        total_ayahs INTEGER NOT NULL
    )");

    // Populate surahs table if empty
    $stmt = $db->query("SELECT COUNT(*) as count FROM surahs");
    $count = $stmt->fetchColumn();
    if ($count == 0) {
        $stmt = $db->prepare("INSERT INTO surahs (id, name_arabic, name_english, revelation_type, total_ayahs) VALUES (?, ?, ?, ?, ?)");
        foreach ($SURAH_DATA as $s_id => $s_data) {
            $stmt->execute([$s_id, $s_data['name_arabic'], $s_data['name_english'], $s_data['revelation_type'], $s_data['total_ayahs']]);
        }
    }

    $db->exec("CREATE TABLE IF NOT EXISTS ayahs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        surah_id INTEGER NOT NULL,
        ayah_number_in_surah INTEGER NOT NULL,
        ayah_number_in_quran INTEGER UNIQUE NOT NULL,
        text_arabic TEXT NOT NULL,
        juz_number INTEGER,
        hizb_quarter_number INTEGER, -- Placeholder, complex to calculate accurately without more data
        FOREIGN KEY (surah_id) REFERENCES surahs(id),
        UNIQUE (surah_id, ayah_number_in_surah)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS translations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ayah_id INTEGER NOT NULL,
        language_code TEXT NOT NULL, -- e.g., 'ur', 'en'
        translator_name TEXT,
        text TEXT NOT NULL,
        version_number INTEGER DEFAULT 1,
        is_default INTEGER DEFAULT 0,
        status TEXT NOT NULL DEFAULT 'approved', -- 'pending', 'approved', 'rejected'
        contributor_id INTEGER,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        approved_by INTEGER,
        approved_at TEXT,
        FOREIGN KEY (ayah_id) REFERENCES ayahs(id),
        FOREIGN KEY (contributor_id) REFERENCES users(id),
        FOREIGN KEY (approved_by) REFERENCES users(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS tafasir (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ayah_id INTEGER NOT NULL,
        tafsir_name TEXT NOT NULL,
        language_code TEXT NOT NULL,
        text TEXT NOT NULL,
        version_number INTEGER DEFAULT 1,
        is_default INTEGER DEFAULT 0,
        status TEXT NOT NULL DEFAULT 'approved',
        contributor_id INTEGER,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        approved_by INTEGER,
        approved_at TEXT,
        FOREIGN KEY (ayah_id) REFERENCES ayahs(id),
        FOREIGN KEY (contributor_id) REFERENCES users(id),
        FOREIGN KEY (approved_by) REFERENCES users(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS word_meanings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ayah_id INTEGER NOT NULL,
        word_arabic TEXT NOT NULL,
        word_position_in_ayah INTEGER NOT NULL,
        meaning TEXT NOT NULL,
        language_code TEXT NOT NULL,
        grammatical_notes TEXT,
        transliteration TEXT,
        version_number INTEGER DEFAULT 1,
        is_default INTEGER DEFAULT 0,
        status TEXT NOT NULL DEFAULT 'approved',
        contributor_id INTEGER,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        approved_by INTEGER,
        approved_at TEXT,
        FOREIGN KEY (ayah_id) REFERENCES ayahs(id),
        FOREIGN KEY (contributor_id) REFERENCES users(id),
        FOREIGN KEY (approved_by) REFERENCES users(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS bookmarks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        ayah_id INTEGER NOT NULL,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (ayah_id) REFERENCES ayahs(id),
        UNIQUE (user_id, ayah_id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS notes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        ayah_id INTEGER NOT NULL,
        note_text TEXT NOT NULL,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (ayah_id) REFERENCES ayahs(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS hifz_progress (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        surah_id INTEGER NOT NULL,
        ayah_start INTEGER NOT NULL,
        ayah_end INTEGER NOT NULL,
        status TEXT NOT NULL, -- 'memorizing', 'reviewing', 'mastered'
        last_reviewed_at TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (surah_id) REFERENCES surahs(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS site_settings (
        setting_key TEXT PRIMARY KEY,
        setting_value TEXT
    )");
    
    // Default site settings
    $stmt = $db->prepare("INSERT OR IGNORE INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
    $stmt->execute(['ulama_contribution_approval', 'admin_review']); // 'direct_publish' or 'admin_review'
    $stmt->execute(['default_reciter', 'Abdul_Basit_Murattal_192kbps']); // Example from everyayah.com

    $db->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL, 
        message TEXT NOT NULL,
        type TEXT, -- 'pending_approval', 'hifz_reminder', 'read_reminder'
        related_content_id INTEGER,
        link TEXT, -- Link to the relevant page
        is_read INTEGER DEFAULT 0,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");

    // Create an admin user if one doesn't exist
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = '" . ROLE_ADMIN . "'");
    if ($stmt->fetchColumn() == 0) {
        $username = 'admin';
        $password = 'adminpassword'; // Change this immediately after first login
        $email = 'admin@example.com';
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password_hash, email, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $password_hash, $email, ROLE_ADMIN]);
        if (php_sapi_name() !== 'cli') {
             $_SESSION['first_time_admin_message'] = "IMPORTANT: Default admin account created. Username: admin, Password: adminpassword. Please change this password immediately after logging in.";
        }
    }
}

function import_data_am() {
    global $SURAH_DATA, $JUZ_STARTS;
    $db = get_db();

    if (!file_exists(DATA_AM_FILE)) {
        return "Error: data.AM file not found at " . DATA_AM_FILE;
    }

    $stmt_check = $db->query("SELECT COUNT(*) as count FROM ayahs");
    if ($stmt_check->fetchColumn() > 0) {
        return "Data already imported. To re-import, clear the 'ayahs' and 'translations' tables first (manual operation for safety).";
    }

    $lines = file(DATA_AM_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return "Error: Could not read data.AM file.";
    }

    $db->beginTransaction();
    try {
        $ayah_insert_stmt = $db->prepare("INSERT INTO ayahs (surah_id, ayah_number_in_surah, ayah_number_in_quran, text_arabic, juz_number) VALUES (?, ?, ?, ?, ?)");
        $translation_insert_stmt = $db->prepare("INSERT INTO translations (ayah_id, language_code, translator_name, text, status, is_default) VALUES (?, 'ur', 'Primary Urdu Translation', ?, 'approved', 1)");

        $ayah_number_in_quran_counter = 0;
        $current_juz = 0;

        foreach ($lines as $line_num => $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if (preg_match('/^(.*?) ترجمہ: (.*?)<br\s*\/?>(?:س|سورة)\s*(\d{1,3})\s*(?:آ|آیت)\s*(\d{1,3})$/u', $line, $matches)) {
                $arabic_text = trim($matches[1]);
                $urdu_translation = trim($matches[2]);
                $surah_num = (int)$matches[3];
                $ayah_num_in_surah = (int)$matches[4];

                if (!isset($SURAH_DATA[$surah_num])) {
                    log_error("Skipping line " . ($line_num + 1) . ": Invalid Surah number $surah_num.");
                    continue;
                }

                $ayah_number_in_quran_counter++;
                foreach ($JUZ_STARTS as $juz => $start_ayah_ref) {
                    if ($surah_num > $start_ayah_ref[0] || ($surah_num == $start_ayah_ref[0] && $ayah_num_in_surah >= $start_ayah_ref[1])) {
                        $current_juz = $juz;
                    } else {
                        break; 
                    }
                }
                
                $ayah_insert_stmt->execute([$surah_num, $ayah_num_in_surah, $ayah_number_in_quran_counter, $arabic_text, $current_juz]);
                $ayah_id = $db->lastInsertId();
                $translation_insert_stmt->execute([$ayah_id, $urdu_translation]);

            } else {
                log_error("Skipping line " . ($line_num + 1) . ": Format mismatch. Line content: " . htmlspecialchars(substr($line, 0, 100)) . "...");
            }
        }
        $db->commit();
        return "Data imported successfully. Total ayahs processed: " . $ayah_number_in_quran_counter;
    } catch (Exception $e) {
        $db->rollBack();
        log_error("Data import failed: " . $e->getMessage());
        return "Data import failed: " . $e->getMessage();
    }
}


// --- AUTHENTICATION & USER MANAGEMENT ---
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function current_user_role() {
    if (!is_logged_in()) return ROLE_PUBLIC;
    return $_SESSION['user_role'] ?? ROLE_PUBLIC;
}

function current_user() {
    if (!is_logged_in()) return null;
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([current_user_id()]);
    return $stmt->fetch();
}

function user_can($permission_level_required) {
    $current_role = current_user_role();
    $roles_hierarchy = [
        ROLE_PUBLIC => 0,
        ROLE_USER => 1,
        ROLE_ULAMA => 2,
        ROLE_ADMIN => 3
    ];
    return ($roles_hierarchy[$current_role] ?? -1) >= ($roles_hierarchy[$permission_level_required] ?? 99);
}

function generate_csrf_token() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verify_csrf_token() {
    if (empty($_POST[CSRF_TOKEN_NAME]) || empty($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $_POST[CSRF_TOKEN_NAME]);
}

function handle_register_action() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // CSRF is globally checked for POST requests
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            $GLOBALS['page_message'] = "All fields are required.";
        } elseif (strlen($password) < 6) {
            $GLOBALS['page_message'] = "Password must be at least 6 characters long.";
        } elseif ($password !== $confirm_password) {
            $GLOBALS['page_message'] = "Passwords do not match.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $GLOBALS['page_message'] = "Invalid email format.";
        } else {
            $db = get_db();
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $GLOBALS['page_message'] = "Username or email already exists.";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$username, $email, $password_hash, ROLE_USER])) {
                    redirect_with_message('?action=login', "Registration successful! You can now login.", "success");
                    return; 
                } else {
                    $GLOBALS['page_message'] = "Registration failed. Please try again.";
                }
            }
        }
        $GLOBALS['page_message_type'] = 'error'; // Default to error if not success redirect
    }
    // If GET, or POST with errors, the main router will call display_page_register
}

function handle_login_action() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // CSRF is globally checked for POST requests
        $username_or_email = trim($_POST['username_or_email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username_or_email) || empty($password)) {
            $GLOBALS['page_message'] = "Username/Email and password are required.";
        } else {
            $db = get_db();
            $stmt = $db->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username_or_email, $username_or_email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['username'] = $user['username'];
                unset($_SESSION[CSRF_TOKEN_NAME]); // Regenerate CSRF token after login
                redirect('?action=dashboard');
                return;
            } else {
                $GLOBALS['page_message'] = "Invalid login credentials.";
            }
        }
        $GLOBALS['page_message_type'] = 'error';
    }
    // If GET, or POST with errors, the main router will call display_page_login
}

function handle_logout_action() {
    session_unset();
    session_destroy();
    redirect('?action=login');
}

// --- HELPERS ---
function redirect($url) {
    header("Location: " . $url);
    exit;
}

function e($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

function get_site_setting($key, $default = null) {
    $db = get_db();
    $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetchColumn();
    return $result !== false ? $result : $default;
}

function update_site_setting($key, $value) {
    $db = get_db();
    $stmt = $db->prepare("REPLACE INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
    return $stmt->execute([$key, $value]);
}

function get_user_setting($user_id, $key, $default = null) {
    $db = get_db();
    $stmt = $db->prepare("SELECT settings FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $settings_json = $stmt->fetchColumn();
    if ($settings_json) {
        $settings = json_decode($settings_json, true);
        return $settings[$key] ?? $default;
    }
    return $default;
}

function update_user_setting($user_id, $key, $value) {
    $db = get_db();
    $stmt = $db->prepare("SELECT settings FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $settings_json = $stmt->fetchColumn();
    $settings = $settings_json ? json_decode($settings_json, true) : [];
    $settings[$key] = $value;
    $stmt_update = $db->prepare("UPDATE users SET settings = ? WHERE id = ?");
    return $stmt_update->execute([json_encode($settings), $user_id]);
}

function log_error($message) {
    $log_file = __DIR__ . '/error.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

function add_notification($user_id, $message, $type = 'info', $link = '#', $related_content_id = null) {
    $db = get_db();
    $stmt = $db->prepare("INSERT INTO notifications (user_id, message, type, link, related_content_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $message, $type, $link, $related_content_id]);
}

function get_ayah_details($surah_num, $ayah_num) {
    $db = get_db();
    $stmt = $db->prepare("SELECT a.*, s.name_arabic as surah_name_arabic, s.name_english as surah_name_english 
                          FROM ayahs a 
                          JOIN surahs s ON a.surah_id = s.id 
                          WHERE a.surah_id = ? AND a.ayah_number_in_surah = ?");
    $stmt->execute([$surah_num, $ayah_num]);
    return $stmt->fetch();
}

function get_ayah_by_id($ayah_id) {
    $db = get_db();
    $stmt = $db->prepare("SELECT a.*, s.name_arabic as surah_name_arabic, s.name_english as surah_name_english 
                          FROM ayahs a 
                          JOIN surahs s ON a.surah_id = s.id 
                          WHERE a.id = ?");
    $stmt->execute([$ayah_id]);
    return $stmt->fetch();
}

function get_ayah_s_a_from_id($ayah_id) {
    $ayah = get_ayah_by_id($ayah_id);
    return $ayah ? $ayah['surah_id'] . '_' . $ayah['ayah_number_in_surah'] : '0_0';
}

// --- CORE QURAN LOGIC & DISPLAY ---
function display_quran_reader() {
    global $SURAH_DATA;
    $db = get_db();

    $current_surah_num = (int)($_GET['surah'] ?? get_user_setting(current_user_id(), 'last_read_surah', 1));
    $current_ayah_num = (int)($_GET['ayah'] ?? get_user_setting(current_user_id(), 'last_read_ayah', 1));
    $view_mode = $_GET['view'] ?? 'surah'; // 'surah', 'juz'
    $juz_num = (int)($_GET['juz'] ?? 1);

    if ($current_surah_num < 1 || $current_surah_num > 114) $current_surah_num = 1;
    if ($current_ayah_num < 1) $current_ayah_num = 1;
    if (isset($SURAH_DATA[$current_surah_num]) && $current_ayah_num > $SURAH_DATA[$current_surah_num]['total_ayahs']) {
        $current_ayah_num = $SURAH_DATA[$current_surah_num]['total_ayahs'];
    }
    
    if (is_logged_in()) {
        update_user_setting(current_user_id(), 'last_read_surah', $current_surah_num);
        update_user_setting(current_user_id(), 'last_read_ayah', $current_ayah_num);
    }

    $ayahs_to_display = [];
    $page_title = "";

    if ($view_mode === 'juz') {
        $page_title = "Juz " . e($juz_num);
        $stmt = $db->prepare("SELECT a.*, s.name_arabic as surah_name_arabic 
                              FROM ayahs a JOIN surahs s ON a.surah_id = s.id 
                              WHERE a.juz_number = ? ORDER BY a.ayah_number_in_quran ASC");
        $stmt->execute([$juz_num]);
        $ayahs_to_display = $stmt->fetchAll();
    } else { 
        $page_title = "Surah " . e($SURAH_DATA[$current_surah_num]['name_english']) . " (" . e($SURAH_DATA[$current_surah_num]['name_arabic']) . ")";
        $stmt = $db->prepare("SELECT a.*, s.name_arabic as surah_name_arabic 
                              FROM ayahs a JOIN surahs s ON a.surah_id = s.id 
                              WHERE a.surah_id = ? ORDER BY a.ayah_number_in_surah ASC");
        $stmt->execute([$current_surah_num]);
        $ayahs_to_display = $stmt->fetchAll();
    }
    
    $user_id = current_user_id();
    $preferred_translation_id = $user_id ? get_user_setting($user_id, 'preferred_translation_id') : null;
    $preferred_tafsir_id = $user_id ? get_user_setting($user_id, 'preferred_tafsir_id') : null;

    $translations_list = $db->query("SELECT id, language_code, translator_name FROM translations WHERE status='approved' GROUP BY language_code, translator_name ORDER BY language_code, translator_name")->fetchAll();
    $tafasir_list = $db->query("SELECT id, tafsir_name, language_code FROM tafasir WHERE status='approved' GROUP BY tafsir_name, language_code ORDER BY language_code, tafsir_name")->fetchAll();

    if (!$preferred_translation_id) {
        $default_ur_trans = $db->query("SELECT id FROM translations WHERE language_code='ur' AND status='approved' AND is_default=1 LIMIT 1")->fetchColumn();
        if (!$default_ur_trans) {
             $default_ur_trans = $db->query("SELECT id FROM translations WHERE language_code='ur' AND status='approved' LIMIT 1")->fetchColumn();
        }
        $preferred_translation_id = $default_ur_trans;
    }

    ?>
    <div class="quran-reader-controls">
        <h2><?php echo e($page_title); ?></h2>
        <form method="get" action="">
            <input type="hidden" name="action" value="read">
            <label for="surah_select">Surah:</label>
            <select name="surah" id="surah_select" onchange="this.form.submit()">
                <?php foreach ($SURAH_DATA as $s_id => $s_data): ?>
                    <option value="<?php echo $s_id; ?>" <?php if ($s_id == $current_surah_num && $view_mode !== 'juz') echo 'selected'; ?>>
                        <?php echo $s_id; ?>. <?php echo e($s_data['name_english']); ?> (<?php echo e($s_data['name_arabic']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            
            <label for="juz_select">Juz:</label>
            <select name="juz" id="juz_select" onchange="document.getElementById('view_mode_input').value='juz'; this.form.submit()">
                <?php for ($j = 1; $j <= 30; $j++): ?>
                    <option value="<?php echo $j; ?>" <?php if ($j == $juz_num && $view_mode === 'juz') echo 'selected'; ?>>
                        Juz <?php echo $j; ?>
                    </option>
                <?php endfor; ?>
            </select>
            <input type="hidden" name="view" id="view_mode_input" value="<?php echo e($view_mode); ?>">
            <button type="submit" onclick="document.getElementById('view_mode_input').value='surah';">View Surah</button>
            <button type="submit" onclick="document.getElementById('view_mode_input').value='juz';">View Juz</button>
        </form>
        
        <form method="post" action="?action=update_preferences">
             <?php echo csrf_input(); ?>
             <input type="hidden" name="redirect_url" value="<?php echo e($_SERVER['REQUEST_URI']); ?>">
            <label for="translation_select">Translation:</label>
            <select name="preferred_translation_id" id="translation_select" onchange="this.form.submit()">
                <option value="">None</option>
                <?php foreach ($translations_list as $trans): ?>
                    <option value="<?php echo $trans['id']; ?>" <?php if ($trans['id'] == $preferred_translation_id) echo 'selected'; ?>>
                        <?php echo e($trans['translator_name'] . ' (' . $trans['language_code'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="tafsir_select">Tafsir:</label>
            <select name="preferred_tafsir_id" id="tafsir_select" onchange="this.form.submit()">
                 <option value="">None</option>
                <?php foreach ($tafasir_list as $taf): ?>
                    <option value="<?php echo $taf['id']; ?>" <?php if ($taf['id'] == $preferred_tafsir_id) echo 'selected'; ?>>
                        <?php echo e($taf['tafsir_name'] . ' (' . $taf['language_code'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <button id="tilawatModeToggleBtn"><i class="fas fa-book-open"></i> Tilawat Mode</button>
    </div>

    <div class="ayah-container">
        <?php if ($view_mode === 'surah' && $current_surah_num == 1 && $SURAH_DATA[1]['total_ayahs'] > 0): ?>
        <?php elseif ($view_mode === 'surah' && $current_surah_num != 9 && $SURAH_DATA[$current_surah_num]['total_ayahs'] > 0): ?>
            <div class="ayah bismillah">بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</div>
        <?php endif; ?>

        <?php foreach ($ayahs_to_display as $ayah): ?>
            <?php
            if ($view_mode === 'juz') {
                static $displayed_surah_in_juz = null;
                if ($displayed_surah_in_juz !== $ayah['surah_id']) {
                    if ($ayah['ayah_number_in_surah'] == 1 && $ayah['surah_id'] != 9 && $ayah['surah_id'] != 1) {
                         echo '<div class="ayah bismillah">بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</div>';
                    }
                    echo '<h3 class="surah-title-in-juz">Surah ' . e($SURAH_DATA[$ayah['surah_id']]['name_english']) . ' (' . e($SURAH_DATA[$ayah['surah_id']]['name_arabic']) . ')</h3>';
                    $displayed_surah_in_juz = $ayah['surah_id'];
                }
            }
            ?>
            <div class="ayah" id="ayah_<?php echo $ayah['surah_id']; ?>_<?php echo $ayah['ayah_number_in_surah']; ?>" data-ayah-id="<?php echo $ayah['id']; ?>" data-surah-id="<?php echo $ayah['surah_id']; ?>" data-ayah-num="<?php echo $ayah['ayah_number_in_surah']; ?>">
                <div class="ayah-header">
                    <span class="ayah-number"><?php echo e($ayah['surah_id'] . ':' . $ayah['ayah_number_in_surah']); ?></span>
                    <div class="ayah-actions">
                        <?php if (is_logged_in()): ?>
                        <?php
                            $is_bookmarked_stmt = $db->prepare("SELECT id FROM bookmarks WHERE user_id = ? AND ayah_id = ?");
                            $is_bookmarked_stmt->execute([current_user_id(), $ayah['id']]);
                            $is_bookmarked = $is_bookmarked_stmt->fetch();
                        ?>
                        <form method="post" action="?action=toggle_bookmark" style="display:inline;" class="bookmark-form">
                            <?php echo csrf_input(); ?>
                            <input type="hidden" name="ayah_id" value="<?php echo $ayah['id']; ?>">
                            <input type="hidden" name="redirect_url" value="<?php echo e($_SERVER['REQUEST_URI']); ?>">
                            <button type="submit" class="bookmark-btn-submit">
                                <?php echo $is_bookmarked ? '<i class="fas fa-bookmark"></i>' : '<i class="far fa-bookmark"></i>'; ?>
                            </button>
                        </form>
                        <button class="add-note-btn" data-ayah-id="<?php echo $ayah['id']; ?>"><i class="fas fa-edit"></i> Note</button>
                        <?php endif; ?>
                        <button class="play-audio-btn" data-surah-id="<?php echo $ayah['surah_id']; ?>" data-ayah-num="<?php echo $ayah['ayah_number_in_surah']; ?>"><i class="fas fa-play"></i></button>
                        <button class="suggest-content-btn" data-ayah-id="<?php echo $ayah['id']; ?>"><i class="fas fa-plus-circle"></i> Suggest</button>
                    </div>
                </div>
                <p class="arabic-text amiri-font"><?php echo e($ayah['text_arabic']); ?></p>
                <?php
                if ($preferred_translation_id) {
                    $translation_text = '';
                    $translator_name = '';
                    $pref_trans_stmt = $db->prepare("SELECT t.text, t.translator_name FROM translations t 
                                                    JOIN translations pref_t ON t.language_code = pref_t.language_code AND t.translator_name = pref_t.translator_name
                                                    WHERE t.ayah_id = ? AND pref_t.id = ? AND t.status='approved' LIMIT 1");
                    $pref_trans_stmt->execute([$ayah['id'], $preferred_translation_id]);
                    $pref_trans = $pref_trans_stmt->fetch();

                    if ($pref_trans) {
                        $translation_text = $pref_trans['text'];
                        $translator_name = $pref_trans['translator_name'];
                    } else {
                        $default_trans_stmt = $db->prepare("SELECT text, translator_name FROM translations WHERE ayah_id = ? AND language_code='ur' AND status='approved' AND is_default=1 LIMIT 1");
                        $default_trans_stmt->execute([$ayah['id']]);
                        $default_trans = $default_trans_stmt->fetch();
                        if ($default_trans) {
                             $translation_text = $default_trans['text'];
                             $translator_name = $default_trans['translator_name'];
                        }
                    }
                    if ($translation_text) {
                        echo '<p class="translation-text noto-nastaliq-urdu-font"><strong>' . e($translator_name) . ':</strong> ' . e($translation_text) . '</p>';
                    }
                }

                if ($preferred_tafsir_id) {
                    $tafsir_text = '';
                    $tafsir_name = '';
                    $pref_tafsir_stmt = $db->prepare("SELECT t.text, t.tafsir_name FROM tafasir t
                                                    JOIN tafasir pref_t ON t.language_code = pref_t.language_code AND t.tafsir_name = pref_t.tafsir_name
                                                    WHERE t.ayah_id = ? AND pref_t.id = ? AND t.status='approved' LIMIT 1");
                    $pref_tafsir_stmt->execute([$ayah['id'], $preferred_tafsir_id]);
                    $pref_taf = $pref_tafsir_stmt->fetch();
                    if ($pref_taf) {
                        $tafsir_text = $pref_taf['text'];
                        $tafsir_name = $pref_taf['tafsir_name'];
                         echo '<div class="tafsir-text"><strong>Tafsir (' . e($tafsir_name) . '):</strong> <p>' . nl2br(e($tafsir_text)) . '</p></div>';
                    }
                }
                ?>
                <div class="word-meanings-container" data-ayah-id="<?php echo $ayah['id']; ?>"></div>
                 <?php if (is_logged_in()): ?>
                <div class="user-note" id="note_ayah_<?php echo $ayah['id']; ?>">
                    <?php
                    $note_stmt = $db->prepare("SELECT note_text FROM notes WHERE user_id = ? AND ayah_id = ?");
                    $note_stmt->execute([current_user_id(), $ayah['id']]);
                    $note = $note_stmt->fetchColumn();
                    if ($note) {
                        echo '<p><strong>Your Note:</strong> ' . e($note) . '</p>';
                    }
                    ?>
                </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($ayahs_to_display)): ?>
            <p>No ayahs found for this selection.</p>
        <?php endif; ?>
    </div>
    
    <div id="noteModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="document.getElementById('noteModal').style.display='none'">&times;</span>
            <h3>Add/Edit Note</h3>
            <form id="noteForm" method="post" action="?action=save_note">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="ayah_id" id="noteAyahId">
                <input type="hidden" name="redirect_url" id="noteRedirectUrl" value="">
                <textarea name="note_text" id="noteText" rows="5" required></textarea>
                <button type="submit">Save Note</button>
            </form>
        </div>
    </div>

    <div id="suggestContentModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="document.getElementById('suggestContentModal').style.display='none'">&times;</span>
            <h3>Suggest Content for Ayah <span id="suggestAyahRef"></span></h3>
            <form id="suggestContentForm" method="post" action="?action=suggest_content">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="ayah_id" id="suggestAyahId">
                <input type="hidden" name="redirect_url" id="suggestRedirectUrl" value="">
                <div>
                    <label for="suggestion_type">Type:</label>
                    <select name="suggestion_type" id="suggestion_type" required>
                        <option value="translation">Translation</option>
                        <option value="tafsir">Tafsir</option>
                        <option value="word_meaning">Word Meaning</option>
                    </select>
                </div>
                <div id="translation_fields">
                    <label for="translation_language">Language Code (e.g., en, ur):</label>
                    <input type="text" name="translation_language" id="translation_language" value="ur">
                    <label for="translator_name">Your Name/Translator Name:</label>
                    <input type="text" name="translator_name" id="translator_name" value="<?php echo e($_SESSION['username'] ?? 'User'); ?>">
                    <label for="translation_text">Translation Text:</label>
                    <textarea name="translation_text" id="translation_text" rows="3"></textarea>
                </div>
                <div id="tafsir_fields" style="display:none;">
                    <label for="tafsir_language">Language Code (e.g., en, ur):</label>
                    <input type="text" name="tafsir_language" id="tafsir_language" value="ur">
                    <label for="tafsir_name">Tafsir Name (e.g., My Tafsir Notes):</label>
                    <input type="text" name="tafsir_name" id="tafsir_name" value="User Suggestion">
                    <label for="tafsir_text">Tafsir Text:</label>
                    <textarea name="tafsir_text" id="tafsir_text" rows="5"></textarea>
                </div>
                <div id="word_meaning_fields" style="display:none;">
                    <label for="word_arabic">Arabic Word:</label>
                    <input type="text" name="word_arabic" id="word_arabic" dir="rtl" class="amiri-font">
                    <label for="word_position">Word Position (1-based):</label>
                    <input type="number" name="word_position" id="word_position" min="1" value="1">
                    <label for="word_meaning_language">Language Code (e.g., en, ur):</label>
                    <input type="text" name="word_meaning_language" id="word_meaning_language" value="ur">
                    <label for="word_meaning_text">Meaning:</label>
                    <textarea name="word_meaning_text" id="word_meaning_text" rows="2"></textarea>
                    <label for="word_transliteration">Transliteration (optional):</label>
                    <input type="text" name="word_transliteration" id="word_transliteration">
                    <label for="word_grammar">Grammatical Notes (optional):</label>
                    <textarea name="word_grammar" id="word_grammar" rows="2"></textarea>
                </div>
                <button type="submit">Submit Suggestion</button>
            </form>
        </div>
    </div>
    <?php
}

function handle_toggle_bookmark_action() {
    if (!is_logged_in() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect_with_message($_POST['redirect_url'] ?? '?action=read', 'Invalid request.', 'error');
        return;
    }
    // CSRF already checked globally

    $ayah_id = (int)($_POST['ayah_id'] ?? 0);
    $redirect_url = $_POST['redirect_url'] ?? '?action=read';
    $ayah_s_a = get_ayah_s_a_from_id($ayah_id); // Get surah_ayah string for anchor

    if (!$ayah_id) {
        redirect_with_message($redirect_url, 'Ayah ID required.', 'error');
        return;
    }

    $db = get_db();
    $user_id = current_user_id();

    $stmt = $db->prepare("SELECT id FROM bookmarks WHERE user_id = ? AND ayah_id = ?");
    $stmt->execute([$user_id, $ayah_id]);
    if ($stmt->fetch()) {
        $delete_stmt = $db->prepare("DELETE FROM bookmarks WHERE user_id = ? AND ayah_id = ?");
        $delete_stmt->execute([$user_id, $ayah_id]);
        redirect_with_message($redirect_url . '#ayah_' . $ayah_s_a, 'Bookmark removed.', 'success');
    } else {
        $insert_stmt = $db->prepare("INSERT INTO bookmarks (user_id, ayah_id) VALUES (?, ?)");
        $insert_stmt->execute([$user_id, $ayah_id]);
        redirect_with_message($redirect_url . '#ayah_' . $ayah_s_a, 'Bookmarked.', 'success');
    }
}


function handle_update_preferences_action() {
    if (!is_logged_in() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('?action=read');
    }
    // CSRF already checked globally

    $user_id = current_user_id();
    if (isset($_POST['preferred_translation_id'])) {
        update_user_setting($user_id, 'preferred_translation_id', (int)$_POST['preferred_translation_id']);
    }
    if (isset($_POST['preferred_tafsir_id'])) {
        update_user_setting($user_id, 'preferred_tafsir_id', (int)$_POST['preferred_tafsir_id']);
    }
    if (isset($_POST['tilawat_font_size'])) { // From user_settings page
        update_user_setting($user_id, 'tilawat_font_size', (int)$_POST['tilawat_font_size']);
    }
    if (isset($_POST['tilawat_lines_per_page'])) { // From user_settings page
        update_user_setting($user_id, 'tilawat_lines_per_page', (int)$_POST['tilawat_lines_per_page']);
    }
     if (isset($_POST['tilawat_view_mode'])) { // From user_settings page
        update_user_setting($user_id, 'tilawat_view_mode', $_POST['tilawat_view_mode']); 
    }
    if (isset($_POST['tilawat_show_translation'])) { // From user_settings page
        update_user_setting($user_id, 'tilawat_show_translation', (bool)$_POST['tilawat_show_translation']);
    }
    if (isset($_POST['audio_reciter'])) {
        update_user_setting($user_id, 'audio_reciter', $_POST['audio_reciter']);
    }

    $redirect_url = $_POST['redirect_url'] ?? $_SERVER['HTTP_REFERER'] ?? '?action=read';
    redirect_with_message($redirect_url, 'Preferences updated.', 'success');
}


function handle_save_note_action() {
    if (!is_logged_in() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect_with_message('?action=read', 'Invalid request.', 'error');
        return;
    }
    // CSRF already checked globally

    $ayah_id = (int)($_POST['ayah_id'] ?? 0);
    $note_text = trim($_POST['note_text'] ?? '');
    $redirect_url = $_POST['redirect_url'] ?? get_ayah_link($ayah_id);


    if (!$ayah_id) {
        redirect_with_message($redirect_url, 'Ayah ID required for note.', 'error');
        return;
    }
    
    $db = get_db();
    $user_id = current_user_id();

    if (empty($note_text)) { 
        $stmt = $db->prepare("DELETE FROM notes WHERE user_id = ? AND ayah_id = ?");
        $stmt->execute([$user_id, $ayah_id]);
        redirect_with_message($redirect_url, 'Note deleted.', 'success');
    } else {
        $stmt = $db->prepare("REPLACE INTO notes (user_id, ayah_id, note_text, updated_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
        if ($stmt->execute([$user_id, $ayah_id, $note_text])) {
            redirect_with_message($redirect_url, 'Note saved.', 'success');
        } else {
            redirect_with_message($redirect_url, 'Failed to save note.', 'error');
        }
    }
}

function handle_suggest_content_action() {
    if (!user_can(ROLE_USER) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect_with_message('?action=read', 'Permission denied or invalid request.', 'error');
        return;
    }
    // CSRF already checked globally

    $db = get_db();
    $user_id = current_user_id();
    $ayah_id = (int)($_POST['ayah_id'] ?? 0);
    $suggestion_type = $_POST['suggestion_type'] ?? '';
    $redirect_url = $_POST['redirect_url'] ?? get_ayah_link($ayah_id);


    if (!$ayah_id) {
        redirect_with_message($redirect_url, 'Ayah ID required for suggestion.', 'error');
        return;
    }
    
    $status = 'pending'; 
    $message = '';

    try {
        if ($suggestion_type === 'translation') {
            $lang = trim($_POST['translation_language']);
            $translator = trim($_POST['translator_name']);
            $text = trim($_POST['translation_text']);
            if (!empty($lang) && !empty($translator) && !empty($text)) {
                $stmt = $db->prepare("INSERT INTO translations (ayah_id, language_code, translator_name, text, status, contributor_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$ayah_id, $lang, $translator, $text, $status, $user_id]);
                $message = 'Translation suggestion submitted for approval.';
            } else { $message = 'Translation: All fields required.'; }
        } elseif ($suggestion_type === 'tafsir') {
            $lang = trim($_POST['tafsir_language']);
            $name = trim($_POST['tafsir_name']);
            $text = trim($_POST['tafsir_text']);
            if (!empty($lang) && !empty($name) && !empty($text)) {
                $stmt = $db->prepare("INSERT INTO tafasir (ayah_id, tafsir_name, language_code, text, status, contributor_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$ayah_id, $name, $lang, $text, $status, $user_id]);
                $message = 'Tafsir suggestion submitted for approval.';
            } else { $message = 'Tafsir: All fields required.'; }
        } elseif ($suggestion_type === 'word_meaning') {
            $word_ar = trim($_POST['word_arabic']);
            $word_pos = (int)$_POST['word_position'];
            $lang = trim($_POST['word_meaning_language']);
            $meaning = trim($_POST['word_meaning_text']);
            $translit = trim($_POST['word_transliteration']);
            $grammar = trim($_POST['word_grammar']);
            if (!empty($word_ar) && $word_pos > 0 && !empty($lang) && !empty($meaning)) {
                $stmt = $db->prepare("INSERT INTO word_meanings (ayah_id, word_arabic, word_position_in_ayah, language_code, meaning, transliteration, grammatical_notes, status, contributor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$ayah_id, $word_ar, $word_pos, $lang, $meaning, $translit, $grammar, $status, $user_id]);
                $message = 'Word meaning suggestion submitted for approval.';
            } else { $message = 'Word Meaning: Arabic word, position, language, and meaning are required.'; }
        } else {
            $message = 'Invalid suggestion type.';
        }

        $admins_ulama = $db->query("SELECT id FROM users WHERE role = '".ROLE_ADMIN."' OR role = '".ROLE_ULAMA."'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($admins_ulama as $mod_id) {
            add_notification($mod_id, "New content suggestion pending approval for Ayah ID $ayah_id.", 'pending_approval', '?action=admin_content_approval');
        }
        redirect_with_message($redirect_url, $message, 'success');

    } catch (PDOException $e) {
        log_error("Suggestion submission error: " . $e->getMessage());
        redirect_with_message($redirect_url, 'Error submitting suggestion: ' . $e->getMessage(), 'error');
    }
}

function get_ayah_link($ayah_id) {
    $ayah = get_ayah_by_id($ayah_id);
    if ($ayah) {
        return "?action=read&surah={$ayah['surah_id']}&ayah={$ayah['ayah_number_in_surah']}#ayah_{$ayah['surah_id']}_{$ayah['ayah_number_in_surah']}";
    }
    return '?action=read';
}

function redirect_with_message($url, $message, $type = 'info') {
    $_SESSION['flash_message'] = ['text' => $message, 'type' => $type];
    redirect($url);
}

function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        echo '<div class="flash-message ' . e($message['type']) . '">' . e($message['text']) . '</div>';
        unset($_SESSION['flash_message']);
    }
     if (isset($_SESSION['first_time_admin_message'])) { // Special message for first admin login
        echo '<div class="flash-message error">' . e($_SESSION['first_time_admin_message']) . '</div>';
        unset($_SESSION['first_time_admin_message']);
    }
}

// --- USER DASHBOARD & PERSONALIZATION ---
function display_dashboard() {
    if (!is_logged_in()) { redirect('?action=login'); }
    $user = current_user();
    global $SURAH_DATA;
    $db = get_db();
    ?>
    <h2>Welcome, <?php echo e($user['username']); ?>!</h2>
    <p>This is your dashboard. From here you can manage your personalization settings, track progress, and more.</p>

    <h3><i class="fas fa-bookmark"></i> Your Bookmarks</h3>
    <?php
    $bookmarks_stmt = $db->prepare("SELECT b.id as bookmark_id, a.id as ayah_id, a.surah_id, a.ayah_number_in_surah, a.text_arabic, s.name_english 
                                    FROM bookmarks b 
                                    JOIN ayahs a ON b.ayah_id = a.id 
                                    JOIN surahs s ON a.surah_id = s.id
                                    WHERE b.user_id = ? ORDER BY b.created_at DESC LIMIT 10");
    $bookmarks_stmt->execute([$user['id']]);
    $bookmarks = $bookmarks_stmt->fetchAll();
    if ($bookmarks): ?>
        <ul>
        <?php foreach ($bookmarks as $bookmark): ?>
            <li>
                <a href="<?php echo get_ayah_link($bookmark['ayah_id']); ?>">
                    Surah <?php echo e($bookmark['name_english']); ?> (<?php echo e($bookmark['surah_id']); ?>), Ayah <?php echo e($bookmark['ayah_number_in_surah']); ?>
                </a> - <span class="amiri-font"><?php echo e(mb_substr($bookmark['text_arabic'], 0, 50)); ?>...</span>
                 <form method="post" action="?action=remove_bookmark_dashboard" style="display:inline;">
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="bookmark_id" value="<?php echo $bookmark['bookmark_id']; ?>">
                    <input type="hidden" name="redirect_url" value="?action=dashboard">
                    <button type="submit" class="small-btn danger-btn">Remove</button>
                </form>
            </li>
        <?php endforeach; ?>
        </ul>
        <?php if (count($bookmarks) >=10): ?> <p><a href="?action=my_bookmarks">View all bookmarks...</a></p> <?php endif; ?>
    <?php else: ?>
        <p>You have no bookmarks yet.</p>
    <?php endif; ?>

    <h3><i class="fas fa-sticky-note"></i> Your Recent Notes</h3>
     <?php
    $notes_stmt = $db->prepare("SELECT n.id as note_id, a.id as ayah_id, a.surah_id, a.ayah_number_in_surah, n.note_text, s.name_english 
                                    FROM notes n
                                    JOIN ayahs a ON n.ayah_id = a.id 
                                    JOIN surahs s ON a.surah_id = s.id
                                    WHERE n.user_id = ? ORDER BY n.updated_at DESC LIMIT 5");
    $notes_stmt->execute([$user['id']]);
    $notes = $notes_stmt->fetchAll();
    if ($notes): ?>
        <ul>
        <?php foreach ($notes as $note): ?>
            <li>
                <a href="<?php echo get_ayah_link($note['ayah_id']); ?>">
                    Note on Surah <?php echo e($note['name_english']); ?> (<?php echo e($note['surah_id']); ?>), Ayah <?php echo e($note['ayah_number_in_surah']); ?>
                </a>: <?php echo e(mb_substr($note['note_text'], 0, 70)); ?>...
            </li>
        <?php endforeach; ?>
        </ul>
         <?php if (count($notes) >=5): ?> <p><a href="?action=my_notes">View all notes...</a></p> <?php endif; ?>
    <?php else: ?>
        <p>You have no notes yet.</p>
    <?php endif; ?>

    <h3><i class="fas fa-quran"></i> Hifz Progress</h3>
    <p><a href="?action=hifz_progress">Manage Hifz Progress</a></p>
    <?php
    $hifz_stmt = $db->prepare("SELECT hp.*, s.name_english FROM hifz_progress hp JOIN surahs s ON hp.surah_id = s.id WHERE hp.user_id = ? ORDER BY hp.surah_id, hp.ayah_start");
    $hifz_stmt->execute([$user['id']]);
    $hifz_items = $hifz_stmt->fetchAll();
    if ($hifz_items) {
        echo "<ul>";
        foreach ($hifz_items as $item) {
            echo "<li>Surah " . e($item['name_english']) . " (Ayahs " . e($item['ayah_start']) . "-" . e($item['ayah_end']) . ") - Status: " . e(ucfirst($item['status'])) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No Hifz progress tracked yet.</p>";
    }
    ?>
    
    <h3><i class="fas fa-cog"></i> Settings</h3>
    <p><a href="?action=user_settings">Update your preferences and profile</a></p>

    <?php
}

function display_user_settings() {
    if (!is_logged_in()) { redirect('?action=login'); }
    $user = current_user();
    $db = get_db();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
        // CSRF already checked
        $new_email = trim($_POST['email']);
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        $current_password_for_change = $_POST['current_password']; 

        $errors = [];
        if (!password_verify($current_password_for_change, $user['password_hash'])) {
            $errors[] = "Incorrect current password.";
        } else {
            if ($new_email !== $user['email']) {
                if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Invalid new email format.";
                } else {
                    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $stmt->execute([$new_email, $user['id']]);
                    if ($stmt->fetch()) {
                        $errors[] = "New email address is already in use.";
                    } else {
                        $update_stmt = $db->prepare("UPDATE users SET email = ? WHERE id = ?");
                        $update_stmt->execute([$new_email, $user['id']]);
                        $_SESSION['flash_message'] = ['text' => 'Email updated successfully.', 'type' => 'success']; // Temp flash
                    }
                }
            }
            if (!empty($new_password)) {
                if (strlen($new_password) < 6) {
                     $errors[] = "New password must be at least 6 characters long.";
                } elseif ($new_password !== $confirm_password) {
                    $errors[] = "New passwords do not match.";
                } else {
                    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $update_stmt->execute([$new_password_hash, $user['id']]);
                    if (isset($_SESSION['flash_message'])) $_SESSION['flash_message']['text'] .= '<br>Password updated successfully.';
                    else $_SESSION['flash_message'] = ['text' => 'Password updated successfully.', 'type' => 'success'];
                }
            }
        }
        if (!empty($errors)) {
             $_SESSION['flash_message'] = ['text' => implode("<br>", $errors), 'type' => 'error'];
        }
        redirect('?action=user_settings'); 
    }
    
    $translations_list = $db->query("SELECT id, language_code, translator_name FROM translations WHERE status='approved' GROUP BY language_code, translator_name ORDER BY language_code, translator_name")->fetchAll();
    $tafasir_list = $db->query("SELECT id, tafsir_name, language_code FROM tafasir WHERE status='approved' GROUP BY tafsir_name, language_code ORDER BY language_code, tafsir_name")->fetchAll();
    $reciters = [ 
        "Abdul_Basit_Murattal_192kbps" => "Abdul Basit (Murattal)",
        "Abdurrahmaan_As-Sudais_192kbps" => "Abdurrahman As-Sudais",
        "Hudhaify_128kbps" => "Ali Al-Hudhaify",
        "Mishary_Rashid_Alafasy_128kbps" => "Mishary Rashid Alafasy", // Corrected key for everyayah.com
    ];
    $preferred_translation_id = get_user_setting($user['id'], 'preferred_translation_id');
    $preferred_tafsir_id = get_user_setting($user['id'], 'preferred_tafsir_id');
    $preferred_reciter = get_user_setting($user['id'], 'audio_reciter', get_site_setting('default_reciter'));
    
    $tilawat_font_size = get_user_setting($user['id'], 'tilawat_font_size', 32);
    $tilawat_lines_per_page = get_user_setting($user['id'], 'tilawat_lines_per_page', 10);
    $tilawat_view_mode = get_user_setting($user['id'], 'tilawat_view_mode', 'paginated');
    $tilawat_show_translation = get_user_setting($user['id'], 'tilawat_show_translation', false);
    ?>
    <h3>User Settings</h3>
    <h4>Profile Information</h4>
    <form method="post" action="?action=user_settings">
        <?php echo csrf_input(); ?>
        <input type="hidden" name="update_profile" value="1">
        <div>
            <label for="username">Username:</label>
            <input type="text" id="username" value="<?php echo e($user['username']); ?>" disabled>
        </div>
        <div>
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" value="<?php echo e($user['email']); ?>" required>
        </div>
        <hr>
        <h4>Change Password</h4>
        <div>
            <label for="new_password">New Password (leave blank to keep current):</label>
            <input type="password" name="new_password" id="new_password">
        </div>
        <div>
            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" name="confirm_password" id="confirm_password">
        </div>
        <hr>
        <div>
            <label for="current_password">Current Password (required to save changes):</label>
            <input type="password" name="current_password" id="current_password" required>
        </div>
        <button type="submit">Update Profile</button>
    </form>

    <h4>Preferences</h4>
    <form method="post" action="?action=update_preferences">
        <?php echo csrf_input(); ?>
        <input type="hidden" name="redirect_url" value="?action=user_settings">
        <div>
            <label for="translation_select_settings">Preferred Translation:</label>
            <select name="preferred_translation_id" id="translation_select_settings">
                <option value="">None</option>
                <?php foreach ($translations_list as $trans): ?>
                    <option value="<?php echo $trans['id']; ?>" <?php if ($trans['id'] == $preferred_translation_id) echo 'selected'; ?>>
                        <?php echo e($trans['translator_name'] . ' (' . $trans['language_code'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="tafsir_select_settings">Preferred Tafsir:</label>
            <select name="preferred_tafsir_id" id="tafsir_select_settings">
                 <option value="">None</option>
                <?php foreach ($tafasir_list as $taf): ?>
                    <option value="<?php echo $taf['id']; ?>" <?php if ($taf['id'] == $preferred_tafsir_id) echo 'selected'; ?>>
                        <?php echo e($taf['tafsir_name'] . ' (' . $taf['language_code'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="audio_reciter_settings">Preferred Reciter:</label>
            <select name="audio_reciter" id="audio_reciter_settings">
                <?php foreach ($reciters as $value => $name): ?>
                    <option value="<?php echo e($value); ?>" <?php if ($value == $preferred_reciter) echo 'selected'; ?>>
                        <?php echo e($name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <hr>
        <h4>Tilawat Mode Preferences</h4>
        <div>
            <label for="tilawat_font_size_settings">Font Size (px):</label>
            <input type="number" name="tilawat_font_size" id="tilawat_font_size_settings" value="<?php echo e($tilawat_font_size); ?>" min="16" max="72">
        </div>
        <div>
            <label for="tilawat_lines_per_page_settings">Lines Per Page:</label>
            <input type="number" name="tilawat_lines_per_page" id="tilawat_lines_per_page_settings" value="<?php echo e($tilawat_lines_per_page); ?>" min="1" max="50">
        </div>
        <div>
            <label for="tilawat_view_mode_settings">View Mode:</label>
            <select name="tilawat_view_mode" id="tilawat_view_mode_settings">
                <option value="paginated" <?php if($tilawat_view_mode == 'paginated') echo 'selected'; ?>>Paginated</option>
                <option value="scroll" <?php if($tilawat_view_mode == 'scroll') echo 'selected'; ?>>Continuous Scroll</option>
            </select>
        </div>
         <div>
            <label for="tilawat_show_translation_settings">Show Translation in Tilawat Mode:</label>
            <input type="checkbox" name="tilawat_show_translation" id="tilawat_show_translation_settings" value="1" <?php if($tilawat_show_translation) echo 'checked'; ?>>
        </div>
        <button type="submit">Save Preferences</button>
    </form>
    <?php
}

function display_hifz_progress() {
    if (!is_logged_in()) { redirect('?action=login'); }
    $user_id = current_user_id();
    $db = get_db();
    global $SURAH_DATA;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // CSRF already checked
        if (isset($_POST['add_hifz'])) {
            $surah_id = (int)$_POST['surah_id'];
            $ayah_start = (int)$_POST['ayah_start'];
            $ayah_end = (int)$_POST['ayah_end'];
            $status = $_POST['status'];

            if ($surah_id > 0 && $ayah_start > 0 && $ayah_end >= $ayah_start && !empty($status) &&
                isset($SURAH_DATA[$surah_id]) && // Check if surah_id is valid
                $ayah_start <= $SURAH_DATA[$surah_id]['total_ayahs'] && $ayah_end <= $SURAH_DATA[$surah_id]['total_ayahs']) {
                $stmt = $db->prepare("INSERT INTO hifz_progress (user_id, surah_id, ayah_start, ayah_end, status, last_reviewed_at) VALUES (?, ?, ?, ?, ?, date('now'))");
                $stmt->execute([$user_id, $surah_id, $ayah_start, $ayah_end, $status]);
                redirect_with_message('?action=hifz_progress', 'Hifz range added.', 'success');
            } else {
                redirect_with_message('?action=hifz_progress', 'Invalid Hifz range data. Ensure Surah exists and Ayah numbers are within range.', 'error');
            }
        } elseif (isset($_POST['update_hifz_status'])) {
            $hifz_id = (int)$_POST['hifz_id'];
            $new_status = $_POST['new_status'];
            $stmt = $db->prepare("UPDATE hifz_progress SET status = ?, updated_at = CURRENT_TIMESTAMP, last_reviewed_at = CASE WHEN ? = 'reviewing' OR ? = 'mastered' THEN date('now') ELSE last_reviewed_at END WHERE id = ? AND user_id = ?");
            $stmt->execute([$new_status, $new_status, $new_status, $hifz_id, $user_id]);
            redirect_with_message('?action=hifz_progress', 'Hifz status updated.', 'success');
        } elseif (isset($_POST['delete_hifz'])) {
            $hifz_id = (int)$_POST['hifz_id'];
            $stmt = $db->prepare("DELETE FROM hifz_progress WHERE id = ? AND user_id = ?");
            $stmt->execute([$hifz_id, $user_id]);
            redirect_with_message('?action=hifz_progress', 'Hifz range deleted.', 'success');
        }
    }

    $hifz_items = $db->prepare("SELECT hp.*, s.name_english FROM hifz_progress hp JOIN surahs s ON hp.surah_id = s.id WHERE hp.user_id = ? ORDER BY hp.surah_id, hp.ayah_start");
    $hifz_items->execute([$user_id]);
    ?>
    <h3>Manage Hifz Progress</h3>
    <form method="post" action="?action=hifz_progress">
        <?php echo csrf_input(); ?>
        <h4>Add New Hifz Range</h4>
        <label for="surah_id_hifz">Surah:</label>
        <select name="surah_id" id="surah_id_hifz" required>
            <?php foreach ($SURAH_DATA as $id => $data): ?>
            <option value="<?php echo $id; ?>"><?php echo $id . ". " . e($data['name_english']); ?></option>
            <?php endforeach; ?>
        </select>
        <label for="ayah_start_hifz">Ayah Start:</label>
        <input type="number" name="ayah_start" id="ayah_start_hifz" min="1" required>
        <label for="ayah_end_hifz">Ayah End:</label>
        <input type="number" name="ayah_end" id="ayah_end_hifz" min="1" required>
        <label for="status_hifz">Status:</label>
        <select name="status" id="status_hifz" required>
            <option value="memorizing">Memorizing</option>
            <option value="reviewing">Reviewing</option>
            <option value="mastered">Mastered</option>
        </select>
        <button type="submit" name="add_hifz">Add Range</button>
    </form>

    <h4>Your Hifz Log</h4>
    <?php if ($items = $hifz_items->fetchAll()): ?>
    <table>
        <thead><tr><th>Surah</th><th>Range</th><th>Status</th><th>Last Reviewed</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo e($item['name_english']); ?></td>
                <td><?php echo e($item['ayah_start']); ?> - <?php echo e($item['ayah_end']); ?></td>
                <td>
                    <form method="post" action="?action=hifz_progress" style="display:inline;">
                        <?php echo csrf_input(); ?>
                        <input type="hidden" name="hifz_id" value="<?php echo $item['id']; ?>">
                        <select name="new_status" onchange="this.form.submit()">
                            <option value="memorizing" <?php if($item['status']=='memorizing') echo 'selected'; ?>>Memorizing</option>
                            <option value="reviewing" <?php if($item['status']=='reviewing') echo 'selected'; ?>>Reviewing</option>
                            <option value="mastered" <?php if($item['status']=='mastered') echo 'selected'; ?>>Mastered</option>
                        </select>
                        <input type="hidden" name="update_hifz_status" value="1">
                    </form>
                </td>
                <td><?php echo e($item['last_reviewed_at'] ? date('Y-m-d', strtotime($item['last_reviewed_at'])) : 'N/A'); ?></td>
                <td>
                    <form method="post" action="?action=hifz_progress" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this range?');">
                        <?php echo csrf_input(); ?>
                        <input type="hidden" name="hifz_id" value="<?php echo $item['id']; ?>">
                        <button type="submit" name="delete_hifz" class="small-btn danger-btn">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>No Hifz progress tracked yet.</p>
    <?php endif; ?>
    <?php
}

function display_my_bookmarks() {
    if (!is_logged_in()) { redirect('?action=login'); }
    $user_id = current_user_id();
    $db = get_db();
    
    $bookmarks_stmt = $db->prepare("SELECT b.id as bookmark_id, a.id as ayah_id, a.surah_id, a.ayah_number_in_surah, a.text_arabic, s.name_english 
                                    FROM bookmarks b 
                                    JOIN ayahs a ON b.ayah_id = a.id 
                                    JOIN surahs s ON a.surah_id = s.id
                                    WHERE b.user_id = ? ORDER BY s.id, a.ayah_number_in_surah");
    $bookmarks_stmt->execute([$user_id]);
    $bookmarks = $bookmarks_stmt->fetchAll();
    ?>
    <h3><i class="fas fa-bookmark"></i> All Your Bookmarks</h3>
    <?php if ($bookmarks): ?>
        <ul>
        <?php foreach ($bookmarks as $bookmark): ?>
            <li>
                <a href="<?php echo get_ayah_link($bookmark['ayah_id']); ?>">
                    Surah <?php echo e($bookmark['name_english']); ?> (<?php echo e($bookmark['surah_id']); ?>), Ayah <?php echo e($bookmark['ayah_number_in_surah']); ?>
                </a> - <span class="amiri-font"><?php echo e(mb_substr($bookmark['text_arabic'], 0, 50)); ?>...</span>
                 <form method="post" action="?action=remove_bookmark_dashboard" style="display:inline;">
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="bookmark_id" value="<?php echo $bookmark['bookmark_id']; ?>">
                    <input type="hidden" name="redirect_url" value="?action=my_bookmarks">
                    <button type="submit" class="small-btn danger-btn">Remove</button>
                </form>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>You have no bookmarks yet.</p>
    <?php endif; ?>
    <?php
}

function handle_remove_bookmark_dashboard_action() { // Used by dashboard and my_bookmarks
    if (!is_logged_in() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('?action=dashboard');
    }
    // CSRF already checked

    $bookmark_id = (int)($_POST['bookmark_id'] ?? 0);
    $redirect_url = $_POST['redirect_url'] ?? '?action=dashboard';

    if ($bookmark_id > 0) {
        $db = get_db();
        $stmt = $db->prepare("DELETE FROM bookmarks WHERE id = ? AND user_id = ?");
        $stmt->execute([$bookmark_id, current_user_id()]);
        redirect_with_message($redirect_url, 'Bookmark removed.', 'success');
    } else {
        redirect_with_message($redirect_url, 'Invalid bookmark ID.', 'error');
    }
}

function display_my_notes() {
    if (!is_logged_in()) { redirect('?action=login'); }
    $user_id = current_user_id();
    $db = get_db();
    
    $notes_stmt = $db->prepare("SELECT n.id as note_id, a.id as ayah_id, a.surah_id, a.ayah_number_in_surah, n.note_text, s.name_english, n.updated_at 
                                    FROM notes n
                                    JOIN ayahs a ON n.ayah_id = a.id 
                                    JOIN surahs s ON a.surah_id = s.id
                                    WHERE n.user_id = ? ORDER BY n.updated_at DESC");
    $notes_stmt->execute([$user_id]);
    $notes = $notes_stmt->fetchAll();
    ?>
    <h3><i class="fas fa-sticky-note"></i> All Your Notes</h3>
    <?php if ($notes): ?>
        <div class="notes-list">
        <?php foreach ($notes as $note): ?>
            <div class="note-item">
                <h4>
                    <a href="<?php echo get_ayah_link($note['ayah_id']); ?>">
                        Note on Surah <?php echo e($note['name_english']); ?> (<?php echo e($note['surah_id']); ?>), Ayah <?php echo e($note['ayah_number_in_surah']); ?>
                    </a>
                </h4>
                <p class="note-text-full"><?php echo nl2br(e($note['note_text'])); ?></p>
                <p class="note-meta">Last updated: <?php echo e(date('Y-m-d H:i', strtotime($note['updated_at']))); ?></p>
                <form method="post" action="?action=delete_note_page" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this note?');">
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="note_id" value="<?php echo $note['note_id']; ?>">
                    <button type="submit" class="small-btn danger-btn">Delete Note</button>
                </form>
            </div>
        <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>You have no notes yet.</p>
    <?php endif; ?>
    <?php
}

function handle_delete_note_page_action() {
    if (!is_logged_in() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('?action=my_notes');
    }
    // CSRF already checked

    $note_id = (int)($_POST['note_id'] ?? 0);
    if ($note_id > 0) {
        $db = get_db();
        $stmt = $db->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
        $stmt->execute([$note_id, current_user_id()]);
        redirect_with_message('?action=my_notes', 'Note deleted.', 'success');
    } else {
        redirect_with_message('?action=my_notes', 'Invalid note ID.', 'error');
    }
}

// --- ADMIN FEATURES ---
function display_admin_dashboard() {
    if (!user_can(ROLE_ADMIN)) { redirect('?action=dashboard'); }
    ?>
    <h2>Admin Dashboard</h2>
    <p>Welcome, Administrator!</p>
    <ul>
        <li><a href="?action=admin_users">User Management</a></li>
        <li><a href="?action=admin_content_approval">Content Approval Queue</a></li>
        <li><a href="?action=admin_manage_content">Manage Content (Translations, Tafasir, Word Meanings)</a></li>
        <li><a href="?action=admin_site_settings">Site Settings</a></li>
        <li><a href="?action=admin_data_import">Data Import (Quran Text)</a></li>
        <li><a href="?action=admin_backup_restore">Database Backup & Restore</a></li>
        <li><a href="?action=admin_platform_stats">Platform Statistics</a></li>
    </ul>
    <?php
}

function display_admin_users() {
    if (!user_can(ROLE_ADMIN)) { redirect('?action=dashboard'); }
    $db = get_db();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user_role'])) {
        // CSRF already checked
        $user_id_to_update = (int)$_POST['user_id'];
        $new_role = $_POST['role'];
        $is_verified_ulama = isset($_POST['is_verified_ulama']) ? 1 : 0;

        if (in_array($new_role, [ROLE_USER, ROLE_ULAMA, ROLE_ADMIN])) {
            if ($user_id_to_update == current_user_id() && $new_role != ROLE_ADMIN) {
                $admin_count_stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = '".ROLE_ADMIN."'");
                if ($admin_count_stmt->fetchColumn() <= 1) {
                    redirect_with_message('?action=admin_users', 'Cannot demote the only administrator.', 'error');
                    return;
                }
            }
            
            $stmt = $db->prepare("UPDATE users SET role = ?, is_verified_ulama = ? WHERE id = ?");
            $stmt->execute([$new_role, ($new_role === ROLE_ULAMA ? $is_verified_ulama : 0), $user_id_to_update]);
            redirect_with_message('?action=admin_users', 'User role updated.', 'success');
        } else {
            redirect_with_message('?action=admin_users', 'Invalid role specified.', 'error');
        }
    }
    
    $users = $db->query("SELECT id, username, email, role, is_verified_ulama, created_at FROM users ORDER BY id ASC")->fetchAll();
    ?>
    <h3>User Management</h3>
    <table>
        <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Verified Ulama</th><th>Created</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo e($user['id']); ?></td>
                <td><?php echo e($user['username']); ?></td>
                <td><?php echo e($user['email']); ?></td>
                <form method="post" action="?action=admin_users">
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <td>
                        <select name="role">
                            <option value="<?php echo ROLE_USER; ?>" <?php if($user['role']==ROLE_USER) echo 'selected'; ?>>User</option>
                            <option value="<?php echo ROLE_ULAMA; ?>" <?php if($user['role']==ROLE_ULAMA) echo 'selected'; ?>>Ulama</option>
                            <option value="<?php echo ROLE_ADMIN; ?>" <?php if($user['role']==ROLE_ADMIN) echo 'selected'; ?>>Admin</option>
                        </select>
                    </td>
                    <td>
                        <?php if ($user['role'] == ROLE_ULAMA): ?>
                        <input type="checkbox" name="is_verified_ulama" value="1" <?php if($user['is_verified_ulama']) echo 'checked'; ?>>
                        <?php else: echo 'N/A'; endif; ?>
                    </td>
                    <td><?php echo e(date('Y-m-d', strtotime($user['created_at']))); ?></td>
                    <td>
                        <button type="submit" name="update_user_role">Save</button>
                    </td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function display_admin_content_approval() {
    if (!user_can(ROLE_ULAMA)) { redirect('?action=dashboard'); } 
    $db = get_db();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_suggestion'])) {
        // CSRF already checked
        $content_type = $_POST['content_type'];
        $content_id = (int)$_POST['content_id'];
        $action_taken = $_POST['action_taken']; 

        if (in_array($action_taken, ['approve', 'reject'])) {
            $table_name = '';
            if ($content_type === 'translation') $table_name = 'translations';
            elseif ($content_type === 'tafsir') $table_name = 'tafasir';
            elseif ($content_type === 'word_meaning') $table_name = 'word_meanings';

            if ($table_name) {
                $new_status = ($action_taken === 'approve') ? 'approved' : 'rejected';
                $stmt = $db->prepare("UPDATE $table_name SET status = ?, approved_by = ?, approved_at = CURRENT_TIMESTAMP WHERE id = ? AND status = 'pending'");
                $stmt->execute([$new_status, current_user_id(), $content_id]);
                
                $contrib_stmt = $db->prepare("SELECT contributor_id FROM $table_name WHERE id = ?");
                $contrib_stmt->execute([$content_id]);
                $contributor_id = $contrib_stmt->fetchColumn();
                if ($contributor_id) {
                    add_notification($contributor_id, "Your $content_type suggestion (ID: $content_id) has been $new_status.", 'content_status');
                }
                redirect_with_message('?action=admin_content_approval', ucfirst($content_type) . " suggestion $action_taken.", 'success');
            } else {
                redirect_with_message('?action=admin_content_approval', 'Invalid content type.', 'error');
            }
        }
    }

    $pending_translations = $db->query("SELECT t.*, u.username as contributor, a.surah_id, a.ayah_number_in_surah FROM translations t JOIN users u ON t.contributor_id = u.id JOIN ayahs a ON t.ayah_id = a.id WHERE t.status = 'pending'")->fetchAll();
    $pending_tafasir = $db->query("SELECT tf.*, u.username as contributor, a.surah_id, a.ayah_number_in_surah FROM tafasir tf JOIN users u ON tf.contributor_id = u.id JOIN ayahs a ON tf.ayah_id = a.id WHERE tf.status = 'pending'")->fetchAll();
    $pending_word_meanings = $db->query("SELECT wm.*, u.username as contributor, a.surah_id, a.ayah_number_in_surah FROM word_meanings wm JOIN users u ON wm.contributor_id = u.id JOIN ayahs a ON wm.ayah_id = a.id WHERE wm.status = 'pending'")->fetchAll();
    ?>
    <h3>Content Approval Queue</h3>

    <h4>Pending Translations</h4>
    <?php if ($pending_translations): ?>
    <table><thead><tr><th>Ayah</th><th>Lang</th><th>Translator</th><th>Text</th><th>Contributor</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($pending_translations as $item): ?>
        <tr>
            <td><a href="<?php echo get_ayah_link($item['ayah_id']); ?>"><?php echo e($item['surah_id'].':'.$item['ayah_number_in_surah']); ?></a></td>
            <td><?php echo e($item['language_code']); ?></td>
            <td><?php echo e($item['translator_name']); ?></td>
            <td><?php echo e(mb_substr($item['text'], 0, 100)); ?>...</td>
            <td><?php echo e($item['contributor']); ?></td>
            <td><?php echo approval_form('translation', $item['id']); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody></table>
    <?php else: ?><p>No pending translations.</p><?php endif; ?>

    <h4>Pending Tafasir</h4>
    <?php if ($pending_tafasir): ?>
    <table><thead><tr><th>Ayah</th><th>Lang</th><th>Tafsir Name</th><th>Text</th><th>Contributor</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($pending_tafasir as $item): ?>
        <tr>
            <td><a href="<?php echo get_ayah_link($item['ayah_id']); ?>"><?php echo e($item['surah_id'].':'.$item['ayah_number_in_surah']); ?></a></td>
            <td><?php echo e($item['language_code']); ?></td>
            <td><?php echo e($item['tafsir_name']); ?></td>
            <td><?php echo e(mb_substr($item['text'], 0, 100)); ?>...</td>
            <td><?php echo e($item['contributor']); ?></td>
            <td><?php echo approval_form('tafsir', $item['id']); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody></table>
    <?php else: ?><p>No pending tafasir.</p><?php endif; ?>

    <h4>Pending Word Meanings</h4>
    <?php if ($pending_word_meanings): ?>
    <table><thead><tr><th>Ayah</th><th>Word</th><th>Lang</th><th>Meaning</th><th>Contributor</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($pending_word_meanings as $item): ?>
        <tr>
            <td><a href="<?php echo get_ayah_link($item['ayah_id']); ?>"><?php echo e($item['surah_id'].':'.$item['ayah_number_in_surah']); ?></a></td>
            <td class="amiri-font"><?php echo e($item['word_arabic']); ?> (Pos: <?php echo e($item['word_position_in_ayah']); ?>)</td>
            <td><?php echo e($item['language_code']); ?></td>
            <td><?php echo e(mb_substr($item['meaning'], 0, 50)); ?>...</td>
            <td><?php echo e($item['contributor']); ?></td>
            <td><?php echo approval_form('word_meaning', $item['id']); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody></table>
    <?php else: ?><p>No pending word meanings.</p><?php endif; ?>
    <?php
}

function approval_form($type, $id) {
    ob_start();
    ?>
    <form method="post" action="?action=admin_content_approval" style="display:inline;">
        <?php echo csrf_input(); ?>
        <input type="hidden" name="content_type" value="<?php echo e($type); ?>">
        <input type="hidden" name="content_id" value="<?php echo e($id); ?>">
        <button type="submit" name="action_taken" value="approve" class="small-btn success-btn">Approve</button>
        <button type="submit" name="action_taken" value="reject" class="small-btn danger-btn">Reject</button>
        <input type="hidden" name="process_suggestion" value="1"> <!-- To identify this form submission -->
    </form>
    <?php
    return ob_get_clean();
}

function display_admin_manage_content() {
    if (!user_can(ROLE_ADMIN)) { redirect('?action=dashboard'); }
    echo "<h3>Manage Content (Translations, Tafasir, Word Meanings)</h3>";
    echo "<p>This section allows admins to directly edit, delete, and manage versions of all content. (Implementation is extensive and simplified here)</p>";
    
    $db = get_db();
    $translations = $db->query("SELECT t.*, a.surah_id, a.ayah_number_in_surah FROM translations t JOIN ayahs a ON t.ayah_id = a.id ORDER BY t.ayah_id, t.language_code, t.translator_name, t.version_number DESC LIMIT 20")->fetchAll();

    echo "<h4>Translations (Recent 20)</h4>";
    if ($translations) {
        echo "<table><thead><tr><th>Ayah</th><th>Lang</th><th>Translator</th><th>Ver</th><th>Default</th><th>Status</th><th>Actions</th></tr></thead><tbody>";
        foreach ($translations as $t) {
            echo "<tr>";
            echo "<td>" . e($t['surah_id'].':'.$t['ayah_number_in_surah']) . "</td>";
            echo "<td>" . e($t['language_code']) . "</td>";
            echo "<td>" . e($t['translator_name']) . "</td>";
            echo "<td>" . e($t['version_number']) . "</td>";
            echo "<td>" . ($t['is_default'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . e($t['status']) . "</td>";
            echo "<td><a href='?action=admin_edit_content&type=translation&id=".$t['id']."'>Edit</a> | <a href='?action=admin_delete_content&type=translation&id=".$t['id']."' onclick='return confirm(\"Sure?\")'>Delete</a></td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p>No translations found.</p>";
    }
}

function display_admin_site_settings() {
    if (!user_can(ROLE_ADMIN)) { redirect('?action=dashboard'); }
    $db = get_db();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
        // CSRF already checked
        
        $ulama_approval = $_POST['ulama_contribution_approval'];
        if (in_array($ulama_approval, ['direct_publish', 'admin_review'])) {
            update_site_setting('ulama_contribution_approval', $ulama_approval);
        }
        
        $default_reciter = $_POST['default_reciter'];
        update_site_setting('default_reciter', $default_reciter);

        redirect_with_message('?action=admin_site_settings', 'Site settings updated.', 'success');
    }

    $ulama_approval_setting = get_site_setting('ulama_contribution_approval', 'admin_review');
    $current_default_reciter = get_site_setting('default_reciter', 'Abdul_Basit_Murattal_192kbps');
    $reciters = [ 
        "Abdul_Basit_Murattal_192kbps" => "Abdul Basit (Murattal)",
        "Abdurrahmaan_As-Sudais_192kbps" => "Abdurrahman As-Sudais",
        "Hudhaify_128kbps" => "Ali Al-Hudhaify",
        "Mishary_Rashid_Alafasy_128kbps" => "Mishary Rashid Alafasy",
    ];
    ?>
    <h3>Site Settings</h3>
    <form method="post" action="?action=admin_site_settings">
        <?php echo csrf_input(); ?>
        <div>
            <label for="ulama_contribution_approval">Ulama Content Contribution:</label>
            <select name="ulama_contribution_approval" id="ulama_contribution_approval">
                <option value="admin_review" <?php if($ulama_approval_setting == 'admin_review') echo 'selected'; ?>>Requires Admin Review</option>
                <option value="direct_publish" <?php if($ulama_approval_setting == 'direct_publish') echo 'selected'; ?>>Direct Publish</option>
            </select>
            <p><small>Determines if content submitted by Ulama is published directly or needs admin approval.</small></p>
        </div>
        <div>
            <label for="default_reciter">Default Audio Reciter:</label>
            <select name="default_reciter" id="default_reciter">
                <?php foreach ($reciters as $value => $name): ?>
                    <option value="<?php echo e($value); ?>" <?php if ($value == $current_default_reciter) echo 'selected'; ?>>
                        <?php echo e($name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
             <p><small>Default reciter for audio playback (uses everyayah.com format).</small></p>
        </div>
        <button type="submit" name="save_settings">Save Settings</button>
    </form>
    <?php
}

function display_admin_data_import() {
    if (!user_can(ROLE_ADMIN)) { redirect('?action=dashboard'); }
    $message = '';
    if (isset($_GET['import_run']) && $_GET['import_run'] == '1') {
        // Basic CSRF for GET action, not ideal but better than nothing for sensitive GETs
        if (!isset($_GET['token']) || !hash_equals($_SESSION['csrf_get_token'] ?? '', $_GET['token'])) {
             die_with_message("CSRF token mismatch for import action.");
        }
        $message = import_data_am();
        unset($_SESSION['csrf_get_token']); // One-time token
    }
    // Generate a token for the GET link
    $_SESSION['csrf_get_token'] = bin2hex(random_bytes(16));
    ?>
    <h3>Data Import (Quran Text from data.AM)</h3>
    <?php if ($message): ?>
        <p class="flash-message <?php echo strpos($message, 'Error') !== false || strpos($message, 'failed') !== false ? 'error' : 'success'; ?>"><?php echo e($message); ?></p>
    <?php endif; ?>
    <p>This will import Quranic text and primary Urdu translation from the <code>data.AM</code> file.</p>
    <p><strong>Warning:</strong> If data already exists, this might be skipped or cause issues if not handled carefully. The current implementation skips if ayahs table has data.</p>
    <p>Ensure <code><?php echo e(DATA_AM_FILE); ?></code> exists and is readable.</p>
    <form method="get" action="">
        <input type="hidden" name="action" value="admin_data_import">
        <input type="hidden" name="import_run" value="1">
        <input type="hidden" name="token" value="<?php echo e($_SESSION['csrf_get_token']); ?>">
        <button type="submit" onclick="return confirm('Are you sure you want to run the import? This can take some time and should only be done once for initial setup or after clearing relevant tables.');">Run Import</button>
    </form>
    <?php
}

function display_admin_backup_restore() {
    if (!user_can(ROLE_ADMIN)) { redirect('?action=dashboard'); }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // CSRF already checked
        if (isset($_POST['backup_db'])) {
            $backup_dir = __DIR__ . '/backups';
            if (!is_dir($backup_dir)) @mkdir($backup_dir, 0755, true);
            if (!is_dir($backup_dir) || !is_writable($backup_dir)) {
                 redirect_with_message('?action=admin_backup_restore', 'Backup directory is not writable or cannot be created.', 'error');
                 return;
            }
            $backup_file = $backup_dir . '/quran_study_backup_' . date('Y-m-d_H-i-s') . '.db';
            if (copy(DB_FILE, $backup_file)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="'.basename($backup_file).'"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($backup_file));
                readfile($backup_file);
                exit;
            } else {
                redirect_with_message('?action=admin_backup_restore', 'Database backup failed.', 'error');
            }
        } elseif (isset($_POST['restore_db']) && isset($_FILES['db_restore_file'])) {
            $uploaded_file = $_FILES['db_restore_file'];
            if ($uploaded_file['error'] === UPLOAD_ERR_OK && $uploaded_file['size'] > 0) {
                $file_ext = strtolower(pathinfo($uploaded_file['name'], PATHINFO_EXTENSION));
                if (in_array($file_ext, ['db', 'sqlite', 'sqlite3'])) {
                    $backup_dir = __DIR__ . '/backups';
                    if (!is_dir($backup_dir)) @mkdir($backup_dir, 0755, true);
                     if (!is_dir($backup_dir) || !is_writable($backup_dir)) {
                         redirect_with_message('?action=admin_backup_restore', 'Backup directory for pre-restore is not writable or cannot be created.', 'error');
                         return;
                    }
                    $current_db_backup_file = $backup_dir . '/quran_study_prerestore_' . date('Y-m-d_H-i-s') . '.db';

                    if (copy(DB_FILE, $current_db_backup_file)) {
                        if (move_uploaded_file($uploaded_file['tmp_name'], DB_FILE)) {
                            redirect_with_message('?action=admin_backup_restore', 'Database restored successfully. Current database was backed up to ' . basename($current_db_backup_file), 'success');
                        } else {
                            copy($current_db_backup_file, DB_FILE); 
                            redirect_with_message('?action=admin_backup_restore', 'Database restore failed. Could not move uploaded file. Original DB might be intact or restored from pre-restore backup.', 'error');
                        }
                    } else {
                         redirect_with_message('?action=admin_backup_restore', 'Database restore failed. Could not backup current database before restoring.', 'error');
                    }
                } else {
                    redirect_with_message('?action=admin_backup_restore', 'Invalid file type for restore. Please upload a .db, .sqlite, or .sqlite3 file.', 'error');
                }
            } else {
                redirect_with_message('?action=admin_backup_restore', 'File upload error or empty file. Error code: '.$uploaded_file['error'], 'error');
            }
        }
    }
    ?>
    <h3>Database Backup & Restore</h3>
    <h4>Backup</h4>
    <form method="post" action="?action=admin_backup_restore">
        <?php echo csrf_input(); ?>
        <p>Click to download a backup of the current database.</p>
        <button type="submit" name="backup_db">Backup & Download Database</button>
    </form>

    <h4>Restore</h4>
    <p><strong>Warning:</strong> Restoring will overwrite the current database. A backup of the current database will be attempted before restoring.</p>
    <form method="post" action="?action=admin_backup_restore" enctype="multipart/form-data">
        <?php echo csrf_input(); ?>
        <div>
            <label for="db_restore_file">Select SQLite database file to restore:</label>
            <input type="file" name="db_restore_file" id="db_restore_file" accept=".db,.sqlite,.sqlite3" required>
        </div>
        <button type="submit" name="restore_db" onclick="return confirm('ARE YOU ABSOLUTELY SURE? This will replace the current database with the uploaded file. A backup of the current DB will be attempted first.');">Restore Database</button>
    </form>
    <?php
    $backup_dir = __DIR__ . '/backups';
    if (is_dir($backup_dir)) {
        $backup_files = array_diff(scandir($backup_dir), ['.', '..']);
        if (!empty($backup_files)) {
            echo "<h4>Available Backups (on server)</h4><ul>";
            foreach ($backup_files as $file) {
                if (is_file($backup_dir . '/' . $file)) { // Ensure it's a file
                    echo "<li>" . e($file) . " (Size: " . round(filesize($backup_dir . '/' . $file) / 1024, 2) . " KB)</li>";
                }
            }
            echo "</ul><p><small>These files are stored on the server. Manage them manually if needed.</small></p>";
        }
    }
}

function display_admin_platform_stats() {
    if (!user_can(ROLE_ADMIN)) { redirect('?action=dashboard'); }
    $db = get_db();

    $total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_ayahs = $db->query("SELECT COUNT(*) FROM ayahs")->fetchColumn();
    $total_translations = $db->query("SELECT COUNT(*) FROM translations WHERE status='approved'")->fetchColumn();
    $total_tafasir = $db->query("SELECT COUNT(*) FROM tafasir WHERE status='approved'")->fetchColumn();
    $pending_suggestions = $db->query("SELECT 
        (SELECT COUNT(*) FROM translations WHERE status='pending') + 
        (SELECT COUNT(*) FROM tafasir WHERE status='pending') + 
        (SELECT COUNT(*) FROM word_meanings WHERE status='pending')
    ")->fetchColumn();
    ?>
    <h3>Platform Statistics</h3>
    <ul>
        <li>Total Users: <?php echo e($total_users); ?></li>
        <li>Total Ayahs Imported: <?php echo e($total_ayahs); ?></li>
        <li>Total Approved Translations: <?php echo e($total_translations); ?></li>
        <li>Total Approved Tafasir: <?php echo e($total_tafasir); ?></li>
        <li>Pending Content Suggestions: <?php echo e($pending_suggestions); ?></li>
    </ul>
    <p>More detailed analytics (user activity, content contribution logs) can be developed further.</p>
    <?php
}

// --- SEARCH ---
function display_search_page() {
    global $SURAH_DATA;
    $db = get_db();
    $search_term = trim($_GET['q'] ?? '');
    $filter_surah = (int)($_GET['filter_surah'] ?? 0);
    $filter_juz = (int)($_GET['filter_juz'] ?? 0);
    $results = [];

    if (!empty($search_term)) {
        $search_param = '%' . $search_term . '%';
        
        $sql_ayah = "SELECT a.id as ayah_id, a.surah_id, a.ayah_number_in_surah, a.text_arabic, 'ayah' as type 
                     FROM ayahs a WHERE a.text_arabic LIKE ?";
        if ($filter_surah) $sql_ayah .= " AND a.surah_id = " . $filter_surah;
        if ($filter_juz) $sql_ayah .= " AND a.juz_number = " . $filter_juz;
        $stmt_ayah = $db->prepare($sql_ayah);
        $stmt_ayah->execute([$search_param]);
        $results = array_merge($results, $stmt_ayah->fetchAll());

        $sql_trans = "SELECT t.id as content_id, t.text, a.id as ayah_id, a.surah_id, a.ayah_number_in_surah, 'translation' as type, t.translator_name 
                      FROM translations t JOIN ayahs a ON t.ayah_id = a.id 
                      WHERE t.text LIKE ? AND t.status='approved'";
        if ($filter_surah) $sql_trans .= " AND a.surah_id = " . $filter_surah;
        if ($filter_juz) $sql_trans .= " AND a.juz_number = " . $filter_juz;
        $stmt_trans = $db->prepare($sql_trans);
        $stmt_trans->execute([$search_param]);
        $results = array_merge($results, $stmt_trans->fetchAll());
        
        $sql_tafsir = "SELECT tf.id as content_id, tf.text, a.id as ayah_id, a.surah_id, a.ayah_number_in_surah, 'tafsir' as type, tf.tafsir_name 
                       FROM tafasir tf JOIN ayahs a ON tf.ayah_id = a.id 
                       WHERE tf.text LIKE ? AND tf.status='approved'";
        if ($filter_surah) $sql_tafsir .= " AND a.surah_id = " . $filter_surah;
        if ($filter_juz) $sql_tafsir .= " AND a.juz_number = " . $filter_juz;
        $stmt_tafsir = $db->prepare($sql_tafsir);
        $stmt_tafsir->execute([$search_param]);
        $results = array_merge($results, $stmt_tafsir->fetchAll());

        $sql_wm = "SELECT wm.id as content_id, wm.meaning, wm.word_arabic, a.id as ayah_id, a.surah_id, a.ayah_number_in_surah, 'word_meaning' as type 
                   FROM word_meanings wm JOIN ayahs a ON wm.ayah_id = a.id 
                   WHERE wm.meaning LIKE ? AND wm.status='approved'";
        if ($filter_surah) $sql_wm .= " AND a.surah_id = " . $filter_surah;
        if ($filter_juz) $sql_wm .= " AND a.juz_number = " . $filter_juz;
        $stmt_wm = $db->prepare($sql_wm);
        $stmt_wm->execute([$search_param]);
        $results = array_merge($results, $stmt_wm->fetchAll());

        if (is_logged_in()) {
            $sql_notes = "SELECT n.id as content_id, n.note_text, a.id as ayah_id, a.surah_id, a.ayah_number_in_surah, 'user_note' as type 
                          FROM notes n JOIN ayahs a ON n.ayah_id = a.id 
                          WHERE n.user_id = ? AND n.note_text LIKE ?";
            if ($filter_surah) $sql_notes .= " AND a.surah_id = " . $filter_surah;
            if ($filter_juz) $sql_notes .= " AND a.juz_number = " . $filter_juz;
            $stmt_notes = $db->prepare($sql_notes);
            $stmt_notes->execute([current_user_id(), $search_param]);
            $results = array_merge($results, $stmt_notes->fetchAll());
        }
    }
    ?>
    <h3>Search Quran & Content</h3>
    <form method="get" action="">
        <input type="hidden" name="action" value="search">
        <input type="text" name="q" value="<?php echo e($search_term); ?>" placeholder="Enter search term..." style="width:50%;">
        <label for="filter_surah_search">Surah:</label>
        <select name="filter_surah" id="filter_surah_search">
            <option value="0">All Surahs</option>
            <?php foreach ($SURAH_DATA as $s_id => $s_data): ?>
                <option value="<?php echo $s_id; ?>" <?php if ($s_id == $filter_surah) echo 'selected'; ?>>
                    <?php echo $s_id; ?>. <?php echo e($s_data['name_english']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label for="filter_juz_search">Juz:</label>
        <select name="filter_juz" id="filter_juz_search">
            <option value="0">All Juz</option>
            <?php for ($j = 1; $j <= 30; $j++): ?>
                <option value="<?php echo $j; ?>" <?php if ($j == $filter_juz) echo 'selected'; ?>>
                    Juz <?php echo $j; ?>
                </option>
            <?php endfor; ?>
        </select>
        <button type="submit">Search</button>
    </form>

    <?php if (!empty($search_term)): ?>
        <h4>Results for "<?php echo e($search_term); ?>" (<?php echo count($results); ?> found)</h4>
        <?php if (!empty($results)): ?>
            <ul class="search-results">
            <?php foreach ($results as $res): ?>
                <li>
                    <a href="<?php echo get_ayah_link($res['ayah_id']); ?>">
                        Surah <?php echo e($SURAH_DATA[$res['surah_id']]['name_english']); ?> (<?php echo e($res['surah_id']); ?>), Ayah <?php echo e($res['ayah_number_in_surah']); ?>
                    </a>
                    <div class="result-context">
                        <strong>Type: <?php echo e(ucfirst(str_replace('_', ' ', $res['type']))); ?></strong>
                        <?php if ($res['type'] === 'ayah'): ?>
                            <p class="amiri-font"><?php echo highlight_term($res['text_arabic'], $search_term); ?></p>
                        <?php elseif ($res['type'] === 'translation'): ?>
                            <p><em><?php echo e($res['translator_name']); ?>:</em> <?php echo highlight_term($res['text'], $search_term); ?></p>
                        <?php elseif ($res['type'] === 'tafsir'): ?>
                            <p><em><?php echo e($res['tafsir_name']); ?>:</em> <?php echo highlight_term(mb_substr($res['text'],0,200)."...", $search_term); ?></p>
                        <?php elseif ($res['type'] === 'word_meaning'): ?>
                            <p><em>Word <span class="amiri-font"><?php echo e($res['word_arabic']); ?></span>:</em> <?php echo highlight_term($res['meaning'], $search_term); ?></p>
                        <?php elseif ($res['type'] === 'user_note'): ?>
                            <p><em>Your Note:</em> <?php echo highlight_term(mb_substr($res['note_text'],0,200)."...", $search_term); ?></p>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No results found.</p>
        <?php endif; ?>
    <?php endif; ?>
    <?php
}

function highlight_term($text, $term) {
    if (empty($term)) return e($text);
    return preg_replace('/(' . preg_quote(e($term), '/') . ')/iu', '<mark>$1</mark>', e($text));
}

// --- NOTIFICATIONS ---
function display_notifications() {
    if (!is_logged_in()) return;
    $db = get_db();
    $user_id = current_user_id();
    $notifications = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
    $notifications->execute([$user_id]);
    $unread_notifications = $notifications->fetchAll();

    if (!empty($unread_notifications)) {
        echo '<div class="notifications-dropdown">';
        echo '<button id="notificationsToggle"><i class="fas fa-bell"></i> <span class="badge">' . count($unread_notifications) . '</span></button>';
        echo '<div id="notificationsList" style="display:none;"><ul>';
        foreach ($unread_notifications as $notif) {
            $link = e($notif['link'] ?? '#');
            $query_char = strpos($link, '?') === false ? '?' : '&';
            echo '<li><a href="' . $link . $query_char . 'mark_notif_read=' . $notif['id'] . '">' . e($notif['message']) . ' <small>(' . date('M d', strtotime($notif['created_at'])) . ')</small></a></li>';
        }
        echo '<li><a href="?action=all_notifications">View all</a></li>';
        echo '</ul></div></div>';
    }
}

function handle_mark_notification_read() {
    if (isset($_GET['mark_notif_read']) && is_logged_in()) {
        $notif_id = (int)$_GET['mark_notif_read'];
        $db = get_db();
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notif_id, current_user_id()]);
        // The redirect will happen by the router to the original link, or if no link, no redirect needed.
        // To prevent the mark_notif_read from staying in URL, we might need to redirect.
        // For now, assume the link itself is the target.
    }
}

function display_all_notifications() {
    if (!is_logged_in()) { redirect('?action=login'); }
    $db = get_db();
    $user_id = current_user_id();

    if (isset($_GET['mark_all_read'])) {
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$user_id]);
        redirect_with_message('?action=all_notifications', 'All notifications marked as read.', 'success');
    }

    $notifications = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50"); 
    $notifications->execute([$user_id]);
    $all_notifications = $notifications->fetchAll();
    ?>
    <h3>All Notifications</h3>
    <p><a href="?action=all_notifications&mark_all_read=1">Mark all as read</a></p>
    <?php if ($all_notifications): ?>
    <ul class="notifications-page-list">
        <?php foreach ($all_notifications as $notif): ?>
            <li class="<?php if(!$notif['is_read']) echo 'unread-notification'; ?>">
                 <?php 
                    $link = e($notif['link'] ?? '#');
                    $query_char = strpos($link, '?') === false ? '?' : '&';
                 ?>
                <a href="<?php echo $link . $query_char . 'mark_notif_read=' . $notif['id']; ?>">
                    <?php echo e($notif['message']); ?>
                </a>
                <small>(<?php echo e(date('Y-m-d H:i', strtotime($notif['created_at']))); ?>) - Type: <?php echo e($notif['type']); ?></small>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <p>No notifications.</p>
    <?php endif; ?>
    <?php
}

// --- TILAWAH MODE PAGE ---
function display_tilawat_mode_page() {
    global $SURAH_DATA;
    if (!is_logged_in()) {
        // Public users might be allowed limited access or redirected
        redirect_with_message('?action=login', 'Please login to use Tilawat Mode.', 'info');
        return;
    }

    $db = get_db();
    $user_id = current_user_id();

    $surah_num = (int)($_GET['surah'] ?? get_user_setting($user_id, 'last_tilawat_surah', 1));
    $ayah_num = (int)($_GET['ayah'] ?? get_user_setting($user_id, 'last_tilawat_ayah', 1));

    if ($surah_num < 1 || $surah_num > 114) $surah_num = 1;
    if ($ayah_num < 1) $ayah_num = 1;
    if (isset($SURAH_DATA[$surah_num]) && $ayah_num > $SURAH_DATA[$surah_num]['total_ayahs']) {
        $ayah_num = $SURAH_DATA[$surah_num]['total_ayahs'];
    }
    
    // Save current position for this user
    update_user_setting($user_id, 'last_tilawat_surah', $surah_num);
    update_user_setting($user_id, 'last_tilawat_ayah', $ayah_num);

    // Fetch ayahs for the surah
    $stmt_ayahs = $db->prepare("
        SELECT a.surah_id, a.ayah_number_in_surah, a.text_arabic, t.text as translation_text
        FROM ayahs a
        LEFT JOIN translations t ON a.id = t.ayah_id AND t.language_code = 'ur' AND t.is_default = 1 AND t.status = 'approved' 
        WHERE a.surah_id = ?
        ORDER BY a.ayah_number_in_surah ASC
    ");
    $stmt_ayahs->execute([$surah_num]);
    $tilawat_ayahs_data = $stmt_ayahs->fetchAll(PDO::FETCH_ASSOC);

    // Get user's Tilawat preferences
    $font_size = get_user_setting($user_id, 'tilawat_font_size', 32);
    $lines_per_page = get_user_setting($user_id, 'tilawat_lines_per_page', 10);
    $view_mode = get_user_setting($user_id, 'tilawat_view_mode', 'paginated');
    $show_translation = get_user_setting($user_id, 'tilawat_show_translation', false);

    // Output only Tilawat mode HTML, no main header/footer
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Tilawat Mode - <?php echo e(SITE_TITLE); ?></title>
        <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
        <?php render_css(); // Includes Tilawat mode specific CSS ?>
        <style> body { overflow: hidden; } /* Ensure no scrollbars for the page itself */ </style>
    </head>
    <body>
    <div id="tilawatModeOverlay" style="display:flex;"> <!-- Initially visible -->
        <div id="tilawatControls">
            <button id="closeTilawatMode"><i class="fas fa-times"></i> Close</button>
            <label for="tilawatSurah">S:</label>
            <select id="tilawatSurah">
                <?php foreach ($SURAH_DATA as $s_id => $s_data): ?>
                    <option value="<?php echo $s_id; ?>" <?php if($s_id == $surah_num) echo 'selected'; ?>><?php echo $s_id; ?></option>
                <?php endforeach; ?>
            </select>
            <label for="tilawatAyah">A:</label>
            <input type="number" id="tilawatAyah" min="1" value="<?php echo e($ayah_num); ?>" style="width:50px;">
            <button id="tilawatGo">Go</button>
            <span>Font:</span><input type="range" id="tilawatFontSizeRange" min="16" max="72" value="<?php echo e($font_size); ?>">
            <span id="tilawatFontSizeValue"><?php echo e($font_size); ?>px</span>
            <!-- Lines/Page and View Mode are read from settings, applied by JS, changed on main settings page -->
            <button id="toggleTilawatTranslationBtn">
                <?php echo $show_translation ? 'Hide Translation' : 'Show Translation'; ?>
            </button>
        </div>
        <div id="tilawatContent">
            <!-- JS will populate this -->
        </div>
        <div id="tilawatPaginationControls" style="display:none;">
            <button id="tilawatPrevPage"><i class="fas fa-chevron-left"></i> Prev</button>
            <span id="tilawatPageInfo">Page X of Y</span>
            <button id="tilawatNextPage">Next <i class="fas fa-chevron-right"></i></button>
        </div>
    </div>
    <script>
        // Pass PHP data to JS
        const TILAWAT_SURAH_DATA_JS = <?php echo json_encode($tilawat_ayahs_data); ?>;
        const TILAWAT_CURRENT_SURAH = <?php echo $surah_num; ?>;
        const TILAWAT_CURRENT_AYAH = <?php echo $ayah_num; ?>;
        let tilawatFontSize = <?php echo $font_size; ?>;
        let tilawatLinesPerPage = <?php echo $lines_per_page; ?>;
        let tilawatView = '<?php echo e($view_mode); ?>';
        let tilawatShowTranslation = <?php echo $show_translation ? 'true' : 'false'; ?>;
    </script>
    <?php render_js(); // Includes Tilawat mode JS logic ?>
    </body>
    </html>
    <?php
    exit; // Crucial: stop further script execution to only send Tilawat page
}


// --- RENDERING ---
function render_header($action = '') {
    global $SURAH_DATA;
    $csrf_token = generate_csrf_token(); 
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo e(SITE_TITLE); ?><?php if ($action && $action !== 'home' && $action !== 'read') echo " - " . e(ucfirst(str_replace('_', ' ', $action))); ?></title>
        <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
        <?php render_css(); ?>
    </head>
    <body>
    <div id="mainAppWrapper">
        <header>
            <h1><a href="?action=home"><?php echo e(SITE_TITLE); ?></a></h1>
            <nav>
                <ul>
                    <li><a href="?action=home"><i class="fas fa-home"></i> Home (Read)</a></li>
                    <li><a href="?action=search"><i class="fas fa-search"></i> Search</a></li>
                    <?php if (is_logged_in()): ?>
                        <li><a href="?action=dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <?php if (user_can(ROLE_ADMIN)): ?>
                            <li><a href="?action=admin_dashboard"><i class="fas fa-user-shield"></i> Admin Panel</a></li>
                        <?php endif; ?>
                        <li><?php display_notifications(); ?></li>
                        <li><a href="?action=logout"><i class="fas fa-sign-out-alt"></i> Logout (<?php echo e($_SESSION['username']); ?>)</a></li>
                    <?php else: ?>
                        <li><a href="?action=login"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                        <li><a href="?action=register"><i class="fas fa-user-plus"></i> Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </header>
        <main>
        <?php display_flash_message(); ?>
    <?php
}

function render_footer() {
    ?>
        </main>
        <footer>
            <p>&copy; <?php echo date('Y'); ?> <?php echo e(SITE_TITLE); ?>. Developed by AI based on specifications by Yasin Ullah, Pakistani.</p>
            <p>Data Source for Quran Text & Primary Urdu Translation: data.AM file.</p>
        </footer>
    </div> <!-- end #mainAppWrapper -->
    <audio id="ayahAudioPlayer" controls style="display:none; width:100%; position:fixed; bottom:0; left:0; background:#eee; padding:5px;"></audio>
    <?php render_js(); ?>
    </body>
    </html>
    <?php
}

function csrf_input() {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . e(generate_csrf_token()) . '">';
}

function render_css() {
    echo <<<CSS
    <style>
        body { font-family: sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; color: #333; line-height: 1.6; }
        .container { width: 80%; margin: auto; overflow: hidden; padding: 0 20px; }
        header { background: #333; color: #fff; padding-top: 30px; min-height: 70px; border-bottom: #0779e4 3px solid; }
        header a { color: #fff; text-decoration: none; text-transform: uppercase; font-size: 16px; }
        header ul { padding: 0; margin: 0; list-style: none; float: right; }
        header li { display: inline; padding: 0 20px 0 20px; }
        header h1 { float: left; margin:0; }
        header h1 a { font-size: 24px; }
        nav { margin-top: 5px; }
        main { padding: 20px; background: #fff; min-height: 400px; }
        footer { background: #333; color: #fff; text-align: center; padding: 20px; margin-top: 20px; }
        
        .amiri-font { font-family: 'Amiri', serif; font-size: 1.8em; direction: rtl; text-align: right; }
        .noto-nastaliq-urdu-font { font-family: 'Noto Nastaliq Urdu', serif; font-size: 1.4em; direction: rtl; text-align: right; }

        .quran-reader-controls { margin-bottom: 20px; padding: 15px; background: #eee; border-radius: 5px; }
        .quran-reader-controls label { margin-right: 5px; }
        .quran-reader-controls select, .quran-reader-controls input[type="number"], .quran-reader-controls button { padding: 8px; margin-right: 10px; border-radius: 3px; border: 1px solid #ccc; }
        .quran-reader-controls button { background-color: #007bff; color:white; cursor:pointer; }
        .quran-reader-controls button:hover { background-color: #0056b3; }
        #tilawatModeToggleBtn { background-color: #28a745; }
        #tilawatModeToggleBtn:hover { background-color: #218838; }


        .ayah-container { margin-top: 20px; }
        .ayah { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px; background: #fff; }
        .ayah.bismillah { text-align: center; font-size: 1.5em; border: none; padding: 5px 0 15px 0; font-weight: bold; }
        .surah-title-in-juz { text-align: center; color: #555; margin: 20px 0; border-bottom: 1px solid #eee; padding-bottom: 10px;}
        .ayah-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .ayah-number { font-weight: bold; color: #0779e4; }
        .ayah-actions button, .bookmark-btn-submit { background: none; border: none; cursor: pointer; font-size: 1em; margin-left: 8px; color: #555; padding:0; }
        .ayah-actions button:hover, .bookmark-btn-submit:hover { color: #0779e4; }
        .bookmark-btn-submit .fa-bookmark { color: #ffc107; } /* Filled bookmark */
        .arabic-text { margin-bottom: 10px; }
        .translation-text { margin-top: 10px; padding-top: 10px; border-top: 1px dashed #eee; }
        .tafsir-text { margin-top: 10px; padding-top: 10px; border-top: 1px dashed #eee; background: #f9f9f9; padding:10px; border-radius:4px; }
        .tafsir-text p { margin-top: 5px; }
        .user-note { margin-top:10px; padding:8px; background-color:#fffacd; border-left:3px solid #ffd700; font-size:0.9em; }

        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 60%; border-radius: 5px; }
        .close-btn { color: #aaa; float: right; font-size: 28px; font-weight: bold; }
        .close-btn:hover, .close-btn:focus { color: black; text-decoration: none; cursor: pointer; }
        .modal textarea, .modal input[type=text], .modal input[type=number], .modal select { width: 95%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 3px; }
        .modal button[type=submit] { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 3px; cursor: pointer; }
        .modal button[type=submit]:hover { background-color: #0056b3; }

        .flash-message { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .flash-message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .flash-message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .flash-message.info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .small-btn { padding: 4px 8px; font-size: 0.8em; }
        .danger-btn { background-color: #dc3545; color: white; border:none; border-radius:3px; cursor:pointer; }
        .danger-btn:hover { background-color: #c82333; }
        .success-btn { background-color: #28a745; color: white; border:none; border-radius:3px; cursor:pointer; }
        .success-btn:hover { background-color: #218838; }

        /* Tilawat Mode Specific CSS (from render_css in display_tilawat_mode_page) */
        #tilawatModeOverlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #000; color: #fff; z-index: 2000; display: flex; flex-direction: column; font-family: 'Amiri', serif; }
        #tilawatControls { position: absolute; top: 10px; right: 10px; background: rgba(50,50,50,0.8); padding: 10px; border-radius: 5px; z-index: 2001; display:flex; align-items:center; flex-wrap: wrap; }
        #tilawatControls label, #tilawatControls input, #tilawatControls select, #tilawatControls button, #tilawatControls span { margin: 2px 5px; color: #333; } /* Adjusted for wrapping */
        #tilawatControls button { color: #fff; background: #555; border: none; padding: 5px 10px; cursor: pointer; }
        #tilawatControls button#closeTilawatMode { background: #c00; }
        #tilawatControls input[type=range] { vertical-align: middle; }
        #tilawatFontSizeValue { color: #fff; background: transparent; margin-left:0; }
        #tilawatContent { flex-grow: 1; overflow-y: auto; padding: 80px 20px 20px 20px; text-align: right; direction: rtl; } /* Increased top padding */
        .tilawat-ayah { font-size: 32px; /* Default, JS will control */ line-height: 1.8; margin-bottom: 1em; }
        .tilawat-ayah .arabic { display: block; }
        .tilawat-ayah .translation { font-size: 0.6em; color: #ccc; font-family: 'Noto Nastaliq Urdu', serif; display: block; margin-top: 0.5em; }
        .tilawat-ayah.playing { background-color: rgba(255,255,0,0.2); }
        .ayah-marker-tilawat { color: #0779e4; font-size: 0.8em; margin-left: 5px; }
        #tilawatPaginationControls { text-align: center; padding: 10px; background: rgba(50,50,50,0.8); }
        #tilawatPaginationControls button { color: #fff; background: #555; border: none; padding: 8px 15px; cursor: pointer; margin: 0 10px; }
        #tilawatPageInfo { font-size: 1.2em; color: #fff;}


        /* Search */
        .search-results li { list-style-type: none; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        .search-results .result-context { margin-left: 20px; font-size: 0.9em; color: #555; }
        .search-results mark { background-color: #fff3cd; padding: 0.2em; }

        /* Notifications */
        .notifications-dropdown { position: relative; display: inline-block; }
        #notificationsToggle { background: none; border: none; color: #fff; font-size: 1.2em; cursor: pointer; position:relative; }
        #notificationsToggle .badge { position: absolute; top: -5px; right: -10px; background: red; color: white; border-radius: 50%; padding: 2px 5px; font-size: 0.7em; }
        #notificationsList { display: none; position: absolute; right: 0; background-color: #f9f9f9; min-width: 250px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); z-index: 1; border-radius: 4px; }
        #notificationsList ul { list-style-type: none; padding: 0; margin: 0; }
        #notificationsList li a { color: black; padding: 12px 16px; text-decoration: none; display: block; font-size:0.9em; border-bottom:1px solid #eee; text-transform:none; }
        #notificationsList li a:hover { background-color: #f1f1f1; }
        #notificationsList li:last-child a { border-bottom:none; text-align:center; font-weight:bold; }
        .notifications-page-list li { padding: 10px; border-bottom: 1px solid #eee; }
        .notifications-page-list li.unread-notification { font-weight: bold; }
        .notifications-page-list li a { text-decoration: none; color: #333; }
        .notifications-page-list li small { display:block; color:#777; font-size:0.8em; font-weight:normal; }

        /* Responsive */
        @media(max-width: 768px){
            header h1, header nav, header nav li { float: none; text-align: center; width: 100%; }
            header nav ul { margin-top:10px; }
            header nav li { display: block; padding: 10px 0; }
            .container { width: 95%; }
            .modal-content { width: 90%; margin: 20% auto; }
            #tilawatControls { flex-direction: column; align-items: flex-end; top:5px; right:5px; }
            #tilawatControls > * { margin-bottom: 5px; }
            #tilawatContent { padding-top: 150px; } /* Adjust if controls take more space */
        }
    </style>
CSS;
}

function render_js() {
    global $SURAH_DATA; // Needed for Tilawat mode JS
    $user_id = current_user_id();
    $user_settings_js = '{}'; // Default to empty object string
    if ($user_id) {
        $db = get_db();
        $stmt = $db->prepare("SELECT settings FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $settings_json = $stmt->fetchColumn();
        if ($settings_json) {
            $user_settings_js = $settings_json; // Already a JSON string
        }
    }
    $site_default_reciter = get_site_setting('default_reciter', 'Abdul_Basit_Murattal_192kbps');
    $current_url_js = e($_SERVER['REQUEST_URI']);

    // Check if we are in Tilawat mode page to include specific JS data
    $is_tilawat_page = (isset($_GET['action']) && $_GET['action'] === 'tilawat');

    echo "<script>\n";
    echo "const CURRENT_URL = '" . $current_url_js . "';\n";
    echo "const USER_SETTINGS = JSON.parse(" . json_encode($user_settings_js) . "); // Parse the string here\n"; // Parse the JSON string
    echo "const SITE_DEFAULT_RECITER = '" . e($site_default_reciter) . "';\n";
    echo "const ALL_SURAH_DATA_JS = JSON.parse('" . json_encode($SURAH_DATA) . "');\n";


    // Tilawat mode specific JS is now part of the main JS block,
    // but it will only execute its setup if the relevant HTML elements are present (i.e., on the Tilawat page).
    // The data TILAWAT_SURAH_DATA_JS, TILAWAT_CURRENT_SURAH etc. are defined in display_tilawat_mode_page()
    
    echo <<<JS

    document.addEventListener('DOMContentLoaded', function() {
        // Add Note buttons
        const noteModal = document.getElementById('noteModal');
        const noteForm = document.getElementById('noteForm');
        const noteAyahIdInput = document.getElementById('noteAyahId');
        const noteTextInput = document.getElementById('noteText');
        const noteRedirectUrlInput = document.getElementById('noteRedirectUrl');

        document.querySelectorAll('.add-note-btn').forEach(button => {
            button.addEventListener('click', function() {
                const ayahId = this.dataset.ayahId;
                noteAyahIdInput.value = ayahId;
                noteRedirectUrlInput.value = CURRENT_URL;
                const existingNoteP = document.querySelector('#note_ayah_' + ayahId + ' p');
                noteTextInput.value = existingNoteP ? existingNoteP.innerText.replace('Your Note:', '').trim() : '';
                if (noteModal) noteModal.style.display = 'block';
            });
        });
        if (noteModal) {
            const closeBtn = noteModal.querySelector('.close-btn');
            if(closeBtn) closeBtn.onclick = function() { noteModal.style.display = 'none'; }
        }
        
        // Suggest Content Modal
        const suggestContentModal = document.getElementById('suggestContentModal');
        const suggestAyahIdInput = document.getElementById('suggestAyahId');
        const suggestAyahRefSpan = document.getElementById('suggestAyahRef');
        const suggestionTypeSelect = document.getElementById('suggestion_type');
        const suggestRedirectUrlInput = document.getElementById('suggestRedirectUrl');
        
        if (suggestContentModal) {
            document.querySelectorAll('.suggest-content-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const ayahId = this.dataset.ayahId;
                    const ayahDiv = this.closest('.ayah');
                    if (!ayahDiv) return;
                    const ayahRef = ayahDiv.querySelector('.ayah-number').innerText;
                    
                    suggestAyahIdInput.value = ayahId;
                    if(suggestAyahRefSpan) suggestAyahRefSpan.innerText = ayahRef;
                    if(suggestRedirectUrlInput) suggestRedirectUrlInput.value = CURRENT_URL;
                    suggestContentModal.style.display = 'block';
                    toggleSuggestionFields(); 
                });
            });
            const closeBtn = suggestContentModal.querySelector('.close-btn');
            if(closeBtn) closeBtn.onclick = function() { suggestContentModal.style.display = 'none'; }
            
            if (suggestionTypeSelect) {
                suggestionTypeSelect.addEventListener('change', toggleSuggestionFields);
            }
        }
        
        function toggleSuggestionFields() {
            if (!suggestionTypeSelect) return;
            const type = suggestionTypeSelect.value;
            const transFields = document.getElementById('translation_fields');
            const tafsirFields = document.getElementById('tafsir_fields');
            const wmFields = document.getElementById('word_meaning_fields');

            if(transFields) transFields.style.display = (type === 'translation') ? 'block' : 'none';
            if(tafsirFields) tafsirFields.style.display = (type === 'tafsir') ? 'block' : 'none';
            if(wmFields) wmFields.style.display = (type === 'word_meaning') ? 'block' : 'none';
        }
        if (suggestionTypeSelect) toggleSuggestionFields(); // Initial call

        window.onclick = function(event) {
            if (noteModal && event.target == noteModal) { noteModal.style.display = "none"; }
            if (suggestContentModal && event.target == suggestContentModal) { suggestContentModal.style.display = "none"; }
        }

        // Audio Playback
        const audioPlayer = document.getElementById('ayahAudioPlayer');
        let currentPlayingAyahDiv = null;
        let continuousPlay = false; 
        let reciter = typeof USER_SETTINGS === 'object' && USER_SETTINGS.audio_reciter ? USER_SETTINGS.audio_reciter : SITE_DEFAULT_RECITER;
        if (typeof USER_SETTINGS === 'string') { // If USER_SETTINGS was stringified JSON
             try { 
                const parsedSettings = JSON.parse(USER_SETTINGS);
                reciter = parsedSettings.audio_reciter || SITE_DEFAULT_RECITER;
             } catch(e) { /* use default */ }
        }


        document.querySelectorAll('.play-audio-btn').forEach(button => {
            button.addEventListener('click', function() {
                const surahId = this.dataset.surahId.padStart(3, '0');
                const ayahNum = this.dataset.ayahNum.padStart(3, '0');
                const audioUrl = `https://everyayah.com/data/\${reciter}/\${surahId}\${ayahNum}.mp3`;
                
                if (currentPlayingAyahDiv) {
                    currentPlayingAyahDiv.classList.remove('playing');
                }
                currentPlayingAyahDiv = this.closest('.ayah');
                if (currentPlayingAyahDiv) currentPlayingAyahDiv.classList.add('playing');

                if(audioPlayer) {
                    audioPlayer.src = audioUrl;
                    audioPlayer.style.display = 'block';
                    audioPlayer.play();
                }
            });
        });

        if (audioPlayer) {
            audioPlayer.onended = function() {
                if (currentPlayingAyahDiv) {
                    currentPlayingAyahDiv.classList.remove('playing');
                }
                if (continuousPlay) {
                    let nextAyahDiv = currentPlayingAyahDiv ? currentPlayingAyahDiv.nextElementSibling : null;
                    while(nextAyahDiv && (!nextAyahDiv.classList.contains('ayah') || nextAyahDiv.classList.contains('bismillah'))) {
                        nextAyahDiv = nextAyahDiv.nextElementSibling;
                    }
                    if (nextAyahDiv && nextAyahDiv.querySelector('.play-audio-btn')) {
                        nextAyahDiv.querySelector('.play-audio-btn').click();
                    } else {
                        audioPlayer.style.display = 'none'; 
                    }
                } else {
                     audioPlayer.style.display = 'none';
                }
            };
        }

        // Notifications Dropdown
        const notificationsToggle = document.getElementById('notificationsToggle');
        const notificationsList = document.getElementById('notificationsList');
        if (notificationsToggle && notificationsList) {
            notificationsToggle.addEventListener('click', function(event) {
                event.stopPropagation();
                notificationsList.style.display = notificationsList.style.display === 'none' ? 'block' : 'none';
            });
            document.addEventListener('click', function() { 
                 if (notificationsList.style.display === 'block') {
                    notificationsList.style.display = 'none';
                 }
            });
        }

        // Tilawat Mode Logic (Only if on Tilawat page or main page with toggle button)
        const tilawatModeToggleBtnMain = document.getElementById('tilawatModeToggleBtn'); // Button on main page
        const tilawatModeOverlay = document.getElementById('tilawatModeOverlay'); // The overlay itself

        if (tilawatModeToggleBtnMain) { // If on main page
            tilawatModeToggleBtnMain.addEventListener('click', () => {
                const currentSurahEl = document.getElementById('surah_select');
                const currentAyah = 1; // Default to 1st ayah of current surah
                const surahToLoad = currentSurahEl ? currentSurahEl.value : (typeof USER_SETTINGS === 'object' && USER_SETTINGS.last_tilawat_surah ? USER_SETTINGS.last_tilawat_surah : 1);
                window.location.href = `?action=tilawat&surah=\${surahToLoad}&ayah=\${currentAyah}`;
            });
        }
        
        // Following JS is for when INSIDE Tilawat Mode page (i.e., action=tilawat)
        if (document.getElementById('tilawatContent') && typeof TILAWAT_SURAH_DATA_JS !== 'undefined') {
            const tilawatContent = document.getElementById('tilawatContent');
            const tilawatControls = document.getElementById('tilawatControls');
            const tilawatPaginationControls = document.getElementById('tilawatPaginationControls');
            const closeTilawatModeBtn = document.getElementById('closeTilawatMode');
            const tilawatSurahSelect = document.getElementById('tilawatSurah');
            const tilawatAyahInput = document.getElementById('tilawatAyah');
            const tilawatGoBtn = document.getElementById('tilawatGo');
            const tilawatFontSizeRange = document.getElementById('tilawatFontSizeRange');
            const tilawatFontSizeValue = document.getElementById('tilawatFontSizeValue');
            const toggleTilawatTranslationBtn = document.getElementById('toggleTilawatTranslationBtn');
            
            let tilawatCurrentPage = 1;
            let tilawatTotalPages = 1;

            function applyTilawatStylesToContent() {
                const ayahsInTilawat = tilawatContent.querySelectorAll('.tilawat-ayah .arabic, .tilawat-ayah .translation');
                ayahsInTilawat.forEach(el => {
                    if (el.classList.contains('arabic')) {
                        el.style.fontSize = tilawatFontSize + 'px';
                    } else if (el.classList.contains('translation')) {
                        el.style.fontSize = (tilawatFontSize * 0.6) + 'px';
                    }
                });
                 const bismillahs = tilawatContent.querySelectorAll('.tilawat-ayah.bismillah-tilawat .arabic');
                 bismillahs.forEach(el => el.style.fontSize = (tilawatFontSize * 1.1) + 'px'); // Slightly larger for Bismillah
            }

            function createTilawatAyahDiv(ayahData) {
                const div = document.createElement('div');
                div.className = 'tilawat-ayah amiri-font';
                div.dataset.surah = ayahData.surah_id;
                div.dataset.ayah = ayahData.ayah_number_in_surah;
                let html = `<span class="arabic">\${ayahData.text_arabic} <span class="ayah-marker-tilawat">﴿\${ayahData.ayah_number_in_surah}﴾</span></span>`;
                if (tilawatShowTranslation && ayahData.translation_text) {
                    html += `<span class="translation noto-nastaliq-urdu-font">\${ayahData.translation_text}</span>`;
                }
                div.innerHTML = html;
                return div;
            }

            function renderTilawatViewForPage() {
                tilawatContent.innerHTML = ''; 
                if (tilawatView === 'paginated') {
                    if(tilawatPaginationControls) tilawatPaginationControls.style.display = 'flex';
                    
                    let linesCount = 0;
                    let pageContentData = [];
                    const pagesData = [];

                    if (TILAWAT_CURRENT_SURAH != 9 && TILAWAT_CURRENT_SURAH != 1) {
                         pageContentData.push({text_arabic: 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ', translation_text: '', isBismillah: true});
                         linesCount += 1; 
                    }

                    TILAWAT_SURAH_DATA_JS.forEach(ayah => {
                        pageContentData.push(ayah);
                        linesCount += 1; 
                        if (tilawatShowTranslation && ayah.translation_text) linesCount += 1; 

                        if (linesCount >= tilawatLinesPerPage) {
                            pagesData.push(pageContentData);
                            pageContentData = [];
                            linesCount = 0;
                        }
                    });
                    if (pageContentData.length > 0) pagesData.push(pageContentData); 

                    tilawatTotalPages = pagesData.length > 0 ? pagesData.length : 1;
                    
                    let foundPageForCurrentAyah = false;
                    for(let i=0; i < pagesData.length; i++) {
                        for(let j=0; j < pagesData[i].length; j++) {
                            if (!pagesData[i][j].isBismillah && pagesData[i][j].surah_id == TILAWAT_CURRENT_SURAH && pagesData[i][j].ayah_number_in_surah == TILAWAT_CURRENT_AYAH) {
                                tilawatCurrentPage = i + 1;
                                foundPageForCurrentAyah = true;
                                break;
                            }
                        }
                        if (foundPageForCurrentAyah) break;
                    }
                    if (!foundPageForCurrentAyah && pagesData.length > 0) tilawatCurrentPage = 1;
                    if (tilawatCurrentPage > tilawatTotalPages) tilawatCurrentPage = tilawatTotalPages;


                    const ayahsForCurrentPage = pagesData[tilawatCurrentPage - 1] || [];
                    ayahsForCurrentPage.forEach(ad => {
                        if (ad.isBismillah) {
                            const bismillahDiv = document.createElement('div');
                            bismillahDiv.className = 'tilawat-ayah amiri-font bismillah-tilawat';
                            bismillahDiv.style.textAlign = 'center';
                            bismillahDiv.innerHTML = `<span class="arabic">\${ad.text_arabic}</span>`;
                            tilawatContent.appendChild(bismillahDiv);
                        } else {
                            tilawatContent.appendChild(createTilawatAyahDiv(ad));
                        }
                    });
                    
                } else { // Scroll mode
                    if(tilawatPaginationControls) tilawatPaginationControls.style.display = 'none';
                    if (TILAWAT_CURRENT_SURAH != 9 && TILAWAT_CURRENT_SURAH != 1) {
                         const bismillahDiv = document.createElement('div');
                         bismillahDiv.className = 'tilawat-ayah amiri-font bismillah-tilawat';
                         bismillahDiv.style.textAlign = 'center';
                         bismillahDiv.innerHTML = `<span class="arabic">بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</span>`;
                         tilawatContent.appendChild(bismillahDiv);
                    }
                    TILAWAT_SURAH_DATA_JS.forEach(ayah => {
                        tilawatContent.appendChild(createTilawatAyahDiv(ayah));
                    });
                    const targetAyahEl = tilawatContent.querySelector(`[data-surah="\${TILAWAT_CURRENT_SURAH}"][data-ayah="\${TILAWAT_CURRENT_AYAH}"]`);
                    if (targetAyahEl) targetAyahEl.scrollIntoView({behavior: "smooth", block: "center"});
                }
                applyTilawatStylesToContent();
                updateTilawatPageInfoDisplay();
            }
            
            function updateTilawatPageInfoDisplay() {
                if (tilawatView === 'paginated') {
                    const pageInfoEl = document.getElementById('tilawatPageInfo');
                    if(pageInfoEl) pageInfoEl.textContent = `Page \${tilawatCurrentPage} of \${tilawatTotalPages}`;
                }
            }

            if(closeTilawatModeBtn) {
                closeTilawatModeBtn.addEventListener('click', () => {
                    // Save last read S/A from Tilawat mode to user settings via a redirect to home
                    // This is a GET request, not ideal for saving, but simplest without AJAX
                    // A better way would be a small form POST or saving via main settings page.
                    // For now, we assume settings are saved via main page, and this just closes.
                    window.location.href = '?action=home'; 
                });
            }
            if(tilawatGoBtn) {
                tilawatGoBtn.addEventListener('click', () => {
                    const s = tilawatSurahSelect.value;
                    const a = tilawatAyahInput.value;
                    window.location.href = `?action=tilawat&surah=\${s}&ayah=\${a}`;
                });
            }
            if(tilawatFontSizeRange && tilawatFontSizeValue) {
                tilawatFontSizeRange.addEventListener('input', (e) => {
                    tilawatFontSize = e.target.value;
                    tilawatFontSizeValue.textContent = tilawatFontSize + 'px';
                    applyTilawatStylesToContent();
                    // Note: This change is visual only, not saved to DB here. Saved on main settings page.
                });
            }
            if(toggleTilawatTranslationBtn) {
                 toggleTilawatTranslationBtn.addEventListener('click', () => {
                    tilawatShowTranslation = !tilawatShowTranslation;
                    toggleTilawatTranslationBtn.textContent = tilawatShowTranslation ? 'Hide Translation' : 'Show Translation';
                    renderTilawatViewForPage(); 
                    // Note: Visual only, not saved to DB here.
                });
            }

            if(document.getElementById('tilawatPrevPage')) {
                document.getElementById('tilawatPrevPage').addEventListener('click', () => {
                    if (tilawatCurrentPage > 1) {
                        tilawatCurrentPage--;
                        renderTilawatViewForPage(); 
                    }
                });
            }
            if(document.getElementById('tilawatNextPage')) {
                document.getElementById('tilawatNextPage').addEventListener('click', () => {
                    if (tilawatCurrentPage < tilawatTotalPages) {
                        tilawatCurrentPage++;
                        renderTilawatViewForPage(); 
                    } else { 
                        if (TILAWAT_CURRENT_SURAH < 114) {
                             window.location.href = `?action=tilawat&surah=\${TILAWAT_CURRENT_SURAH + 1}&ayah=1`;
                        }
                    }
                });
            }
            
            // Keyboard navigation for Tilawat mode
            if(tilawatModeOverlay) {
                tilawatModeOverlay.addEventListener('keydown', function(e) {
                    if (tilawatView === 'paginated') {
                        if (e.key === 'ArrowRight' || e.key === 'PageDown') {
                            if(document.getElementById('tilawatNextPage')) document.getElementById('tilawatNextPage').click();
                            e.preventDefault();
                        } else if (e.key === 'ArrowLeft' || e.key === 'PageUp') {
                            if(document.getElementById('tilawatPrevPage')) document.getElementById('tilawatPrevPage').click();
                            e.preventDefault();
                        }
                    } else { // Scroll mode
                         if (e.key === 'ArrowDown' || e.key === 'PageDown') {
                            tilawatContent.scrollTop += window.innerHeight * 0.8;
                            e.preventDefault();
                        } else if (e.key === 'ArrowUp' || e.key === 'PageUp') {
                            tilawatContent.scrollTop -= window.innerHeight * 0.8;
                            e.preventDefault();
                        }
                    }
                    if (e.key === 'Escape') {
                        if(closeTilawatModeBtn) closeTilawatModeBtn.click();
                        e.preventDefault();
                    }
                });
            }
            // Initial render for Tilawat Mode
            renderTilawatViewForPage();
        } // End of Tilawat Mode specific JS
    }); // End DOMContentLoaded
    </script>
JS;
}


// --- ROUTER & MAIN EXECUTION ---
initialize_database(); 
handle_mark_notification_read(); 

$action = $_GET['action'] ?? 'home';

// CSRF Protection for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        die_with_message('CSRF token validation failed. Please try again.');
        exit;
    }
}

// Special case for Tilawat mode page - it renders a full, separate HTML document
if ($action === 'tilawat') {
    display_tilawat_mode_page(); // This function calls exit()
}

// Render main page structure
render_header($action);

switch ($action) {
    case 'home':
    case 'read':
        display_quran_reader();
        break;
    case 'register':
        handle_register_action(); 
        display_page_register();  
        break;
    case 'login':
        handle_login_action();    
        display_page_login();     
        break;
    case 'logout':
        handle_logout_action(); 
        break;
    case 'dashboard':
        display_dashboard();
        break;
    case 'user_settings':
        display_user_settings();
        break;
    case 'hifz_progress':
        display_hifz_progress();
        break;
    case 'my_bookmarks':
        display_my_bookmarks();
        break;
    case 'my_notes':
        display_my_notes();
        break;
    case 'all_notifications':
        display_all_notifications();
        break;
    case 'toggle_bookmark':
        handle_toggle_bookmark_action();
        break;
    case 'save_note': 
        handle_save_note_action(); 
        break;
    case 'remove_bookmark_dashboard': 
        handle_remove_bookmark_dashboard_action(); 
        break;
    case 'delete_note_page': 
        handle_delete_note_page_action(); 
        break;
    case 'suggest_content': 
        handle_suggest_content_action(); 
        break;
    case 'update_preferences': 
        handle_update_preferences_action(); 
        break;
    case 'search':
        display_search_page();
        break;
    // Admin actions
    case 'admin_dashboard':
        display_admin_dashboard();
        break;
    case 'admin_users':
        display_admin_users();
        break;
    case 'admin_content_approval':
        display_admin_content_approval();
        break;
    case 'admin_manage_content':
        display_admin_manage_content();
        break;
    case 'admin_site_settings':
        display_admin_site_settings();
        break;
    case 'admin_data_import':
        display_admin_data_import();
        break;
    case 'admin_backup_restore':
        display_admin_backup_restore();
        break;
    case 'admin_platform_stats':
        display_admin_platform_stats();
        break;
    default:
        echo "<p>Page not found.</p>";
        break;
}

render_footer();


// --- LOGIN/REGISTER PAGE DISPLAY FUNCTIONS ---
function display_page_login() {
    ?>
    <h3>Login</h3>
    <?php if (!empty($GLOBALS['page_message'])): ?>
        <p class="flash-message <?php echo ($GLOBALS['page_message_type'] === 'success') ? 'success' : 'error'; ?>"><?php echo e($GLOBALS['page_message']); ?></p>
    <?php endif; ?>
    <form method="post" action="?action=login">
        <?php echo csrf_input(); ?>
        <div>
            <label for="username_or_email">Username or Email:</label>
            <input type="text" name="username_or_email" id="username_or_email" value="<?php echo e($_POST['username_or_email'] ?? ''); ?>" required>
        </div>
        <div>
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>
        </div>
        <button type="submit">Login</button>
    </form>
    <p>Don't have an account? <a href="?action=register">Register here</a>.</p>
    <?php
}

function display_page_register() {
    ?>
    <h3>Register</h3>
    <?php if (!empty($GLOBALS['page_message'])): ?>
        <p class="flash-message <?php echo ($GLOBALS['page_message_type'] === 'success') ? 'success' : 'error'; ?>"><?php echo e($GLOBALS['page_message']); ?></p>
    <?php endif; ?>
    <form method="post" action="?action=register">
        <?php echo csrf_input(); ?>
        <div>
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" value="<?php echo e($_POST['username'] ?? ''); ?>" required>
        </div>
        <div>
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
        </div>
        <div>
            <label for="password">Password (min 6 chars):</label>
            <input type="password" name="password" id="password" required>
        </div>
        <div>
            <label for="confirm_password">Confirm Password:</label>
            <input type="password" name="confirm_password" id="confirm_password" required>
        </div>
        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="?action=login">Login here</a>.</p>
    <?php
}


function die_with_message($message, $title = "Error") {
    // Check if headers already sent before trying to render full page
    if (!headers_sent()) {
        render_header('error');
    } else { // Fallback if headers sent, e.g. error during content rendering
        echo "</main><footer></footer></div><div id='mainAppWrapper'><main>"; // Try to close open tags and provide a somewhat clean error
    }
    echo "<h2>" . e($title) . "</h2>";
    echo "<p class='flash-message error'>" . e($message) . "</p>";
    echo "<p><a href='javascript:history.back()'>Go Back</a> or <a href='?action=home'>Go Home</a></p>";
    if (!headers_sent()) {
        render_footer();
    } else {
        echo "</main></div></body></html>";
    }
    exit;
}

?>