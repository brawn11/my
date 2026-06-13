<?php
// ak/aab.php
session_start();
header('Content-Type: application/json; charset=utf-8');

$dbFile = 'users_db.txt';

// دالة مساعدة لقراءة الملف
function getUsers($file) {
    if (!file_exists($file)) return [];
    $json = file_get_contents($file);
    return json_decode($json, true) ?: [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $action = $_POST['action'] ?? '';
    
    // --- 1. تسجيل مستخدم جديد ---
    if ($action === 'register') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($name) || empty($phone)) {
            echo json_encode(['status' => 'error', 'message' => 'يرجى تعبئة جميع الحقول']);
            exit;
        }
        
        if (!preg_match('/^05\d{8}$/', $phone)) {
            echo json_encode(['status' => 'error', 'message' => 'رقم الجوال يجب أن يكون 10 أرقام ويبدأ بـ 05']);
            exit;
        }

        $users = getUsers($dbFile);
        
        foreach ($users as $u) {
            if ($u['phone'] === $phone) {
                echo json_encode(['status' => 'error', 'message' => 'هذا الرقم مسجل مسبقاً']);
                exit;
            }
        }

        $users[] = ['name' => $name, 'phone' => $phone, 'date' => date('Y-m-d H:i:s')];
        file_put_contents($dbFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        echo json_encode(['status' => 'success', 'message' => 'تم التسجيل بنجاح']);
    } 
    
    // --- 2. تسجيل الدخول ---
    elseif ($action === 'login') {
        $phone = trim($_POST['phone'] ?? '');

        if (!preg_match('/^05\d{8}$/', $phone)) {
            echo json_encode(['status' => 'error', 'message' => 'صيغة الرقم غير صحيحة']);
            exit;
        }

        $users = getUsers($dbFile);
        $foundUser = null;

        foreach ($users as $u) {
            if ($u['phone'] === $phone) {
                $foundUser = $u;
                break;
            }
        }

        if ($foundUser) {
            $_SESSION['user_phone'] = $foundUser['phone'];
            $_SESSION['user_name'] = $foundUser['name'];
            echo json_encode(['status' => 'success', 'message' => 'تم تسجيل الدخول بنجاح', 'redirect' => 'index.php']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'لم يتم تسجيل الرقم، قم بالتسجيل أولاً']);
        }
    }

    // --- 3. حذف مستخدم محدد (للأدمن) ---
    elseif ($action === 'delete_user') {
        $phoneToDelete = $_POST['phone'] ?? '';
        $users = getUsers($dbFile);
        $newUsers = array_filter($users, function($u) use ($phoneToDelete) {
            return $u['phone'] !== $phoneToDelete;
        });
        
        // إعادة ترتيب المفاتيح
        $newUsers = array_values($newUsers);
        file_put_contents($dbFile, json_encode($newUsers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        echo json_encode(['status' => 'success', 'message' => 'تم حذف المستخدم']);
    }

    // --- 4. حذف جميع المستخدمين (للأدمن) ---
    elseif ($action === 'delete_all') {
        file_put_contents($dbFile, json_encode([], JSON_PRETTY_PRINT));
        echo json_encode(['status' => 'success', 'message' => 'تم حذف جميع السجلات']);
    }
}
?>

