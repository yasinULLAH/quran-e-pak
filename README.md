# 🕌 Quran Vision | قرآن وژن 📖

**Author: Yasin Ullah (Pakistani)** | **مصنف: یاسین اللہ (پاکستانی)**

An offline-first Quran web application designed for an immersive, accessible, and personal Quran study experience.
|
ایک آف لائن فرسٹ قرآن ویب ایپلیکیشن جو قرآن کے مطالعہ کے لئے ایک عمیق، قابل رسائی، اور ذاتی تجربہ فراہم کرنے کے لئے ڈیزائن کی گئی ہے۔

---

## ✨ Features | خصوصیات ✨

**📥 Data Handling | ڈیٹا ہینڈلنگ:**
*   **Automatic Primary Data Load:** Loads Quran text and primary Urdu translation from `data.AM` (UTF-8) into IndexedDB on first launch. | **بنیادی ڈیٹا کی خودکار لوڈنگ:** پہلی بار لانچ پر قرآن متن اور بنیادی اردو ترجمہ `data.AM` (UTF-8) فائل سے IndexedDB میں لوڈ کرتا ہے۔
*   **Optional Secondary Translation:** Allows users to load an additional translation (e.g., English) from a local text file. | **اختیاری اضافی ترجمہ:** صارفین کو مقامی ٹیکسٹ فائل سے ایک اضافی ترجمہ (مثلاً انگریزی) لوڈ کرنے کی اجازت دیتا ہے۔

**📖 Core Reading & Navigation | بنیادی پڑھائی اور نیویگیشن:**
*   **Rich Text Display:** Clear Arabic text (Amiri font) with primary Urdu translation (Noto Nastaliq Urdu). Secondary translation shown if loaded. | **واضح متن ڈسپلے:** واضح عربی متن (امیری فونٹ) بنیادی اردو ترجمہ (نوٹو نستعلیق اردو) کے ساتھ۔ اگر اضافی ترجمہ لوڈ ہو تو وہ بھی دکھایا جاتا ہے۔
*   **Versatile Navigation:** Navigate by Surah/Ayah selectors, Juz list, clickable Surah index, and a 'Go to Last Read' feature. | **متعدد نیویگیشن:** سورہ/آیت سلیکٹرز، پارہ فہرست، قابل کلک سورہ انڈیکس، اور 'آخری پڑھی گئی جگہ پر جائیں' فیچر کے ذریعے۔
*   **Personalization:** Locally store and manage bookmarks and per-Ayah user notes (IndexedDB/localStorage). | **ذاتی نوعیت:** مقامی طور پر بک مارکس اور ہر آیت پر صارف کے نوٹس کو محفوظ اور منظم کریں (IndexedDB/localStorage)۔

**🔍 Advanced Search | جدید تلاش:**
*   **Multi-Lingual Search:** Search across Arabic, primary Urdu, and secondary translation texts. | **کثیر اللسانی تلاش:** عربی، بنیادی اردو، اور اضافی ترجمہ کے متون میں تلاش کریں۔
*   **Smart Search:** Robust keyword search. Phonetic/root-based Arabic search (if feasible) and "Did you mean?" suggestions using fuzzy search (Levenshtein distance). | **سمارٹ تلاش:** مضبوط کلیدی لفظ کی تلاش۔ عربی کے لئے صوتی/لفظی بنیاد پر تلاش (اگر قابل عمل ہو) اور "کیا آپ کا مطلب یہ تھا؟" فزی سرچ (لیونشٹین فاصلہ) کا استعمال کرتے ہوئے تجاویز۔
*   **Highlighting:** Search terms are highlighted in the results for easy identification. | **نمایاں کرنا:** تلاش کی اصطلاحات کو نتائج میں آسانی سے شناخت کے لئے نمایاں کیا جاتا ہے۔

**🕋 Tilawat (Recitation) Mode | تلاوت موڈ  immersive:**
*   **Dedicated Mode:** Activated via a header icon for a focused recitation experience. | **مخصوص موڈ:** تلاوت کے مرکوز تجربے کے لئے ہیڈر آئیکن کے ذریعے فعال کیا جاتا ہے۔
*   **Immersive Display:** Fullscreen view with a black background and white text, hiding main app UI. | **عمیق ڈسپلے:** سیاہ پس منظر اور سفید متن کے ساتھ مکمل اسکرین منظر، مرکزی ایپ UI کو چھپاتا ہے۔
*   **Content Focus:** Primarily Arabic text. Translations can be optionally displayed based on main app settings. | **مواد پر توجہ:** بنیادی طور پر عربی متن۔ مرکزی ایپ کی ترتیبات کی بنیاد پر ترجمے اختیاری طور پر دکھائے جا سکتے ہیں۔
*   **Flexible Layouts:**
    *   📜 **Paginated View:** User-defined "lines per page." Navigate with on-screen buttons, keyboard arrows, and mobile swipes. | **صفحہ بند منظر:** صارف کی طرف سے مقرر کردہ "لائن فی صفحہ"۔ آن اسکرین بٹن، کی بورڈ تیر، اور موبائل سوائپ کے ساتھ نیویگیٹ کریں۔
    *   🌊 **Continuous Scroll View:** Dynamically loads more content using IntersectionObserver. Keyboard and swipe navigation. | **مسلسل اسکرول منظر:** IntersectionObserver کا استعمال کرتے ہوئے متحرک طور پر مزید مواد لوڈ کرتا ہے۔ کی بورڈ اور سوائپ نیویگیشن۔
*   **Proper Start:** New Surahs (except At-Tawbah) always start on a new line/page with Bismillah. | **درست آغاز:** نئی سورتیں (سوائے التوبہ کے) ہمیشہ بسم اللہ کے ساتھ نئی لائن/صفحہ پر شروع ہوتی ہیں۔
*   **🎛️ Dynamic Controls (Floating Box):** Surah/Ayah selectors, lines per page/chunk input, Tilawat mode font size slider, and view mode toggle. | **متحرک کنٹرولز (فلوٹنگ باکس):** سورہ/آیت سلیکٹرز، لائن فی صفحہ/چنک ان پٹ، تلاوت موڈ فونٹ سائز سلائیڈر، اور منظر موڈ ٹوگل۔
*   **Persistence:** Remembers Tilawat mode's last position, font size, lines per page, and view mode in localStorage. | **یادداشت:** تلاوت موڈ کی آخری پوزیشن، فونٹ سائز، لائن فی صفحہ، اور منظر موڈ کو localStorage میں یاد رکھتا ہے۔

**🔊 Other Key Features | دیگر اہم خصوصیات:**
*   **Audio Playback:** Per-Ayah recitation (e.g., via everyayah.com or configurable local path). Features reciter selection, playback controls, continuous play, playback rate adjustment, and highlights the currently playing Ayah. | **آڈیو پلے بیک:** فی آیت تلاوت (مثلاً everyayah.com یا قابل ترتیب مقامی پاتھ کے ذریعے)۔ قاری کا انتخاب، پلے بیک کنٹرولز، مسلسل پلے، پلے بیک کی رفتار میں ایڈجسٹمنٹ، اور موجودہ چل رہی آیت کو نمایاں کرتا ہے۔
*   **🎨 Customization:** Multiple UI themes (Light, Dark, Green, Sepia) and adjustable font sizes for Arabic and translations in the main view. | **حسب ضرورت:** متعدد UI تھیمز (لائٹ، ڈارک، گرین، سیپیا) اور مرکزی منظر میں عربی اور تراجم کے لئے قابل ترتیب فونٹ سائز۔
*   **🔄 Data Management:** Robust Backup and Restore functionality for all user data (bookmarks, notes, settings) via a JSON file. | **ڈیٹا مینجمنٹ:** JSON فائل کے ذریعے تمام صارف ڈیٹا (بک مارکس، نوٹس، سیٹنگز) کے لئے مضبوط بیک اپ اور بحالی کی فعالیت۔

---

## 🛠️ Technical Requirements | تکنیکی ضروریات

*   **Offline First 🌐:** All core reading, navigation, Tilawat mode, notes, and bookmarks work perfectly offline after the initial data load. Audio playback may require an internet connection. | **آف لائن فرسٹ:** تمام بنیادی پڑھائی، نیویگیشن، تلاوت موڈ، نوٹس، اور بک مارکس ابتدائی ڈیٹا لوڈ کے بعد بالکل آف لائن کام کرتے ہیں۔ آڈیو پلے بیک کے لئے انٹرنیٹ کنکشن کی ضرورت ہو سکتی ہے۔
*   **Data Storage 💾:** IndexedDB for Quran text, notes, and bookmarks for efficiency; localStorage for user settings. | **ڈیٹا اسٹوریج:** قرآن متن، نوٹس، اور بک مارکس کے لئے IndexedDB؛ صارف کی ترتیبات کے لئے localStorage۔
*   **UI/UX 📱:** Modern, intuitive, responsive, focused, and a "digital prayer mat" aesthetic where appropriate. Aims for WCAG 2.1 AA accessibility. | **UI/UX:** جدید، بدیہی، ریسپانسیو، مرکوز، اور جہاں مناسب ہو "ڈیجیٹل جائے نماز" کی جمالیات۔ WCAG 2.1 AA رسائی کا ہدف۔
*   **JS Driven Tilawat Mode:** The Tilawat mode UI and CSS are dynamically created and managed by JavaScript using unique prefixes for isolation. | **JS سے چلنے والا تلاوت موڈ:** تلاوت موڈ UI اور CSS متحرک طور پر جاوا اسکرپٹ کے ذریعے بنائے اور منظم کیے جاتے ہیں، علیحدگی کے لئے منفرد سابقے استعمال کرتے ہوئے۔

---

## 🚀 Getting Started | شروع کرنے کا طریقہ

1.  Download `index.html`. | `index.html` فائل ڈاؤن لوڈ کریں۔
2.  Place `data.AM` in the same directory as `QuranVision.html` for automatic data loading on first launch. Alternatively, you will be prompted to select the `data.AM` file. | (اختیاری) پہلی بار لانچ پر خودکار ڈیٹا لوڈنگ کے لئے `data.AM` کو `QuranVision.html` کی ڈائرکٹری میں رکھیں۔ بصورت دیگر، آپ کو `data.AM` فائل منتخب کرنے کا کہا جائے گا۔
3.  Open `index.html` in any modern web browser (Chrome, Firefox, Edge, Safari). | `QuranVision.html` کو کسی بھی جدید ویب براؤزر (کروم، فائر فاکس، ایج، سفاری) میں کھولیں۔

---

## 🤝 Contributing | شمولیت

Contributions, issues, and feature requests are welcome! Feel free to check the [issues page](https://github.com/yasin-ullah/quran-vision/issues) (replace with your actual link if you create the repo).
|
شمولیت، مسائل، اور فیچر کی درخواستیں خوش آئند ہیں! بلا جھجھک [مسائل کا صفحہ](https://github.com/yasin-ullah/quran-vision/issues) (اگر آپ ریپو بناتے ہیں تو اپنے اصل لنک سے تبدیل کریں) دیکھیں۔

---

## 👤 Author | مصنف

**Yasin Ullah (Pakistani Developer)**
|
**یاسین اللہ (پاکستانی ڈویلپر)**

---

## 📜 License | لائسنس

This project is licensed under the MIT License. 
|
یہ پروجیکٹ MIT لائسنس کے تحت لائسنس یافتہ ہے۔ تفصیلات کے لئے `LICENSE` فائل دیکھیں۔

---
**Acknowledgements | شکریہ**
*   Fonts: Amiri, Noto Nastaliq Urdu, Roboto. | فونٹس: امیری، نوٹو نستعلیق اردو، روبوٹو۔
*   Ayah Audio Source: [everyayah.com](https://everyayah.com) | آیات کا آڈیو ماخذ: [everyayah.com](https://everyayah.com)
