<?php
// ak/aab.php - معالج العملاء v2.0

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

$dbFile = 'users_db.txt';

/**
 * قراءة بيانات المستخدمين من الملف
 */
function getUsers($file) {
    if (!file_exists($file)) return [];
    $json = file_get_contents($file);
    if ($json === false || empty(trim($json))) return [];
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

/**
 * حفظ بيانات المستخدمين في الملف
 */
function saveUsers($file, $users) {
    $json = json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) return false;
    $result = file_put_contents($file, $json, LOCK_EX);
    return $result !== false;
}

/**
 * التحقق من صحة رقم الجوال السعودي
 */
function validatePhone($phone) {
    // يقبل: 05xxxxxxxx
    return preg_match('/^05[0-9]{8}$/', $phone);
}

/**
 * تنظيف رقم الجوال
 */
function cleanPhone($phone) {
    return preg_replace('/[^0-9]/', '', trim($phone));
}

// =============== معالجة الطلبات ===============
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'طريقة طلب غير صالحة']);
    exit;
}

$action = $_POST['action'] ?? '';

// ===== 1. تسجيل مستخدم جديد =====
if ($action === 'register') {
    $name = trim($_POST['name'] ?? '');
    $phone = cleanPhone($_POST['phone'] ?? '');

    // التحقق من الاسم
    if (empty($name)) {
        echo json_encode(['status' => 'error', 'message' => 'يرجى إدخال الاسم']);
        exit;
    }
    
    if (mb_strlen($name, 'UTF-8') < 3) {
        echo json_encode(['status' => 'error', 'message' => 'الاسم يجب أن يكون 3 أحرف على الأقل']);
        exit;
    }
    
    if (mb_strlen($name, 'UTF-8') > 50) {
        echo json_encode(['status' => 'error', 'message' => 'الاسم طويل جداً']);
        exit;
    }

    // التحقق من رقم الجوال
    if (empty($phone)) {
        echo json_encode(['status' => 'error', 'message' => 'يرجى إدخال رقم الجوال']);
        exit;
    }
    
    if (!validatePhone($phone)) {
        echo json_encode(['status' => 'error', 'message' => 'رقم الجوال يجب أن يبدأ بـ 05 ويتكون من 10 أرقام']);
        exit;
    }

    // قراءة المستخدمين الحاليين
    $users = getUsers($dbFile);
    
    // التحقق من عدم وجود الرقم مسبقاً
    foreach ($users as $u) {
        if (($u['phone'] ?? '') === $phone) {
            echo json_encode(['status' => 'error', 'message' => 'هذا الرقم مسجل مسبقاً، يمكنك تسجيل الدخول مباشرة']);
            exit;
        }
    }

    // إضافة المستخدم الجديد
    $newUser = [
        'name' => $name,
        'phone' => $phone,
        'date' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    $users[] = $newUser;

    // حفظ البيانات
    if (saveUsers($dbFile, $users)) {
        // تسجيل الدخول تلقائياً بعد التسجيل
        $_SESSION['user_phone'] = $phone;
        $_SESSION['user_name'] = $name;
        
        echo json_encode([
            'status' => 'success',
            'message' => '🎉 تم التسجيل بنجاح! جاري تحويلك...',
            'redirect' => 'indexx.php'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'فشل في حفظ البيانات، حاول مرة أخرى']);
    }
} 

// ===== 2. تسجيل الدخول =====
elseif ($action === 'login') {
    $phone = cleanPhone($_POST['phone'] ?? '');

    if (!validatePhone($phone)) {
        echo json_encode(['status' => 'error', 'message' => 'صيغة رقم الجوال غير صحيحة']);
        exit;
    }

    $users = getUsers($dbFile);
    $foundUser = null;

    foreach ($users as $u) {
        if (($u['phone'] ?? '') === $phone) {
            $foundUser = $u;
            break;
        }
    }

    if ($foundUser) {
        $_SESSION['user_phone'] = $foundUser['phone'];
        $_SESSION['user_name'] = $foundUser['name'];
        
        echo json_encode([
            'status' => 'success',
            'message' => '👋 مرحباً ' . $foundUser['name'] . '! جاري تحويلك...',
            'redirect' => 'indexx.php'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'لم يتم العثور على هذا الرقم، قم بالتسجيل أولاً']);
    }
}

// ===== 3. حذف مستخدم محدد (للأدمن) =====
elseif ($action === 'delete_user') {
    $phoneToDelete = cleanPhone($_POST['phone'] ?? '');
    
    if (empty($phoneToDelete)) {
        echo json_encode(['status' => 'error', 'message' => 'رقم الجوال غير محدد']);
        exit;
    }
    
    $users = getUsers($dbFile);
    $originalCount = count($users);
    
    $newUsers = array_values(array_filter($users, function($u) use ($phoneToDelete) {
        return ($u['phone'] ?? '') !== $phoneToDelete;
    }));
    
    $deletedCount = $originalCount - count($newUsers);
    
    if ($deletedCount > 0) {
        if (saveUsers($dbFile, $newUsers)) {
            echo json_encode(['status' => 'success', 'message' => "تم حذف المستخدم بنجاح"]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'فشل في حفظ البيانات']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'لم يتم العثور على المستخدم']);
    }
}

// ===== 4. حذف جميع المستخدمين (للأدمن) =====
elseif ($action === 'delete_all') {
    if (saveUsers($dbFile, [])) {
        echo json_encode(['status' => 'success', 'message' => 'تم حذف جميع السجلات بنجاح']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'فشل في حذف البيانات']);
    }
}

// ===== 5. إجراء غير معروف =====
else {
    echo json_encode(['status' => 'error', 'message' => 'إجراء غير معروف']);
}