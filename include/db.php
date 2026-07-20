<?php
// db.php
$host = 'localhost';
$dbname = 'sams_shop';
$user = 'sams_shop';
$pass = 'Mohammed7134';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

session_start();

// التحقق من الجلسة أو إنشاؤها
if (!isset($_COOKIE['user_session_id'])) {
    $sessionId = bin2hex(random_bytes(16));
    setcookie('user_session_id', $sessionId, time() + (86400 * 30), "/"); // 30 يوم
    
    // إدخال مستخدم جديد
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (session_id, last_active) VALUES (?, NOW())");
    $stmt->execute([$sessionId]);
    
    $_COOKIE['user_session_id'] = $sessionId;
} else {
    // === الإضافة الجديدة: تحديث وقت آخر ظهور للمستخدم القديم ===
    $sessionId = $_COOKIE['user_session_id'];
    // نحدث الوقت لنعرف أنه "نشط" اليوم
    $stmt = $pdo->prepare("UPDATE users SET last_active = NOW() WHERE session_id = ?");
    $stmt->execute([$sessionId]);
    
    // (احتياط) إذا كان الكوكيز موجوداً لكنه غير مسجل في القاعدة (بسبب الحذف السابق) نعيد تسجيله
    if ($stmt->rowCount() == 0) {
        $pdo->prepare("INSERT IGNORE INTO users (session_id, last_active) VALUES (?, NOW())")->execute([$sessionId]);
    }
}

$user_session = $_COOKIE['user_session_id'];

// === الإصلاح: تأكد دائماً أن المستخدم موجود في قاعدة البيانات ===
// نستخدم INSERT IGNORE لنتجاهل الخطأ إذا كان موجوداً مسبقاً، وننشئه إذا لم يكن موجوداً (بسبب الحذف)
try {
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (session_id) VALUES (?)");
    $stmt->execute([$sessionId]);
} catch (Exception $e) {
    // تجاهل الخطأ في حال وجود مشاكل أخرى
}

$user_session = $sessionId;

// دالة لجلب عدد عناصر السلة
function getCartCount($pdo, $session) {
    $stmt = $pdo->prepare("SELECT SUM(qty) as total FROM cart WHERE session_id = ?");
    $stmt->execute([$session]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    return $res['total'] ? $res['total'] : 0;
}
// جلب إعدادات الموقع العامة
// جلب إعدادات الموقع العامة
$settingsStmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);

// تعريف المتغيرات
$siteName1 = $settings['store_name'];   // الكلمة الأولى (SAM)
$siteName2 = $settings['store_name_2']; // الكلمة الثانية (STORE)
$sitePhone = $settings['whatsapp_number'];
$isMaintenance = $settings['maintenance_mode'];
?>