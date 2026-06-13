<?php
// ak/adminn.php - لوحة تحكم v11.0 - نظام فوري متقدم مع تحديثات 1ms

session_start();

$dbFile = file_exists(__DIR__ . '/../auctions_db.json') ? __DIR__ . '/../auctions_db.json' : 'auctions_db.json';
$notifFile = file_exists(__DIR__ . '/../latest_notif.json') ? __DIR__ . '/../latest_notif.json' : 'latest_notif.json';
$notifLogFile = file_exists(__DIR__ . '/../notifications_log.json') ? __DIR__ . '/../notifications_log.json' : 'notifications_log.json';

function getAuctions() { global $dbFile; if (!file_exists($dbFile)) return []; $d = json_decode(file_get_contents($dbFile), true); return is_array($d) ? $d : []; }
function saveAuctions($d) { global $dbFile; return file_put_contents($dbFile, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX); }
function getNotifLog() { global $notifLogFile; if (!file_exists($notifLogFile)) return []; $d = json_decode(file_get_contents($notifLogFile), true); return is_array($d) ? $d : []; }
function saveNotifLog($d) { global $notifLogFile; return file_put_contents($notifLogFile, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX); }

// =============== API سريع ===============
if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    
    if ($_GET['action'] === 'get_auctions') {
        $auctions = getAuctions(); $now = time(); $updated = false;
        foreach ($auctions as $k => $a) {
            if (isset($a['status']) && $a['status'] === 'active' && isset($a['end_time']) && $a['end_time'] <= $now) {
                $auctions[$k]['status'] = 'ended'; 
                if (!empty($a['bids'])) $auctions[$k]['winner_index'] = 0; 
                $updated = true;
            }
        }
        if ($updated) saveAuctions($auctions);
        echo json_encode($auctions, JSON_UNESCAPED_UNICODE); exit;
    }
    
    if ($_GET['action'] === 'get_updates') {
        // نظام التحديثات الفورية - يعيد فقط البيانات المتغيرة
        $auctions = getAuctions();
        $lastUpdate = isset($_GET['last_update']) ? intval($_GET['last_update']) : 0;
        $changes = [
            'timestamp' => time(),
            'auctions' => [],
            'notifications' => [],
            'has_changes' => false
        ];
        
        foreach ($auctions as $a) {
            if (isset($a['updated_at']) && $a['updated_at'] > $lastUpdate) {
                $changes['auctions'][] = $a;
                $changes['has_changes'] = true;
            }
        }
        
        $notifs = getNotifLog();
        if (count($notifs) > 0 && $notifs[0]['time'] > $lastUpdate) {
            $changes['notifications'] = $notifs;
            $changes['has_changes'] = true;
        }
        
        echo json_encode($changes, JSON_UNESCAPED_UNICODE); exit;
    }
    
    if ($_GET['action'] === 'get_notifications') { 
        echo json_encode(getNotifLog(), JSON_UNESCAPED_UNICODE); exit; 
    }
    
    if ($_GET['action'] === 'get_notif_count') { 
        echo json_encode(['count' => count(getNotifLog())]); exit; 
    }
    
    if ($_GET['action'] === 'get_latest_notif') {
        $notifs = getNotifLog();
        $latest = count($notifs) > 0 ? $notifs[0] : null;
        echo json_encode($latest, JSON_UNESCAPED_UNICODE); exit;
    }
}

// =============== POST ===============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $currentTime = time();
    
    if ($action === 'accept_bid') {
        $auctions = getAuctions(); $found = false;
        foreach ($auctions as $k => $a) {
            if (isset($a['id']) && $a['id'] === ($_POST['auction_id']??'')) {
                $auctions[$k]['status'] = 'sold'; 
                $auctions[$k]['winner_index'] = intval($_POST['bid_index']??0); 
                $auctions[$k]['sold_at'] = date('Y-m-d H:i:s'); 
                $auctions[$k]['updated_at'] = $currentTime;
                $auctions[$k]['price_version'] = ($a['price_version'] ?? 0) + 1;
                $found = true; 
                break;
            }
        }
        echo json_encode($found ? (saveAuctions($auctions) ? ['status'=>'success','timestamp'=>$currentTime] : ['status'=>'error']) : ['status'=>'error']); 
        exit;
    }
    
    if ($action === 'reject_bid') {
        $auctions = getAuctions(); $found = false;
        foreach ($auctions as $k => $a) {
            if (isset($a['id']) && $a['id'] === ($_POST['auction_id']??'')) {
                $idx = intval($_POST['bid_index']??0);
                if (isset($a['bids'][$idx])) {
                    unset($auctions[$k]['bids'][$idx]); 
                    $auctions[$k]['bids'] = array_values($auctions[$k]['bids']);
                    $auctions[$k]['current_price'] = !empty($auctions[$k]['bids']) ? $auctions[$k]['bids'][0]['amount'] : ($a['start_price']??0);
                    $auctions[$k]['updated_at'] = $currentTime;
                    $auctions[$k]['price_version'] = ($a['price_version'] ?? 0) + 1;
                    $found = true;
                }
                break;
            }
        }
        echo json_encode($found ? (saveAuctions($auctions) ? ['status'=>'success','timestamp'=>$currentTime] : ['status'=>'error']) : ['status'=>'error']); 
        exit;
    }
    
    if ($action === 'add_auction') {
        $auctions = getAuctions();
        foreach($auctions as $a) { 
            if(isset($a['status']) && $a['status']==='active') { 
                echo json_encode(['status'=>'error','message'=>'يوجد مزاد نشط بالفعل']); 
                exit; 
            } 
        }
        $title = trim($_POST['title']??''); 
        $startPrice = floatval($_POST['start_price']??0);
        if (empty($title) || $startPrice <= 0) { 
            echo json_encode(['status'=>'error','message'=>'البيانات غير مكتملة']); 
            exit; 
        }
        
        $newAuction = [
            'id' => 'AU_' . uniqid(),
            'title' => $title,
            'desc' => trim($_POST['desc']??''),
            'start_price' => $startPrice,
            'current_price' => $startPrice,
            'stop_price' => floatval($_POST['stop_price']??0),
            'end_time' => $currentTime + (intval($_POST['duration']??30) * 60),
            'status' => 'active',
            'images' => json_decode($_POST['images']??'[]', true) ?: [],
            'bids' => [],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => $currentTime,
            'price_version' => 0,
            'admin_notifications' => []
        ];
        
        $auctions[] = $newAuction;
        
        // إضافة إشعار تلقائي ببدء المزاد
        $notif = [
            'id' => 'NOTIF_' . uniqid(),
            'title' => 'مزاد جديد',
            'msg' => 'تم بدء مزاد: ' . $title,
            'time' => $currentTime,
            'date' => date('Y-m-d H:i:s'),
            'auction_id' => $newAuction['id'],
            'type' => 'new_auction'
        ];
        file_put_contents($notifFile, json_encode($notif, JSON_UNESCAPED_UNICODE));
        
        // حفظ في سجل الإشعارات
        $log = getNotifLog(); 
        array_unshift($log, $notif); 
        if(count($log) > 50) $log = array_slice($log, 0, 50);
        saveNotifLog($log);
        
        echo json_encode(saveAuctions($auctions) ? ['status'=>'success','timestamp'=>$currentTime] : ['status'=>'error']); 
        exit;
    }
    
    if ($action === 'stop_auction') { 
        $auctions = getAuctions(); 
        foreach($auctions as $k => $a) {
            if(isset($a['id']) && $a['id'] === ($_POST['auction_id']??'')) {
                $auctions[$k]['status'] = 'stopped'; 
                $auctions[$k]['updated_at'] = $currentTime;
                $auctions[$k]['price_version'] = ($a['price_version'] ?? 0) + 1;
                break;
            }
        } 
        echo json_encode(saveAuctions($auctions) ? ['status'=>'success','timestamp'=>$currentTime] : ['status'=>'error']); 
        exit; 
    }
    
    if ($action === 'delete_auction') { 
        $auctions = []; 
        foreach(getAuctions() as $a) {
            if(isset($a['id']) && $a['id'] !== ($_POST['auction_id']??'')) $auctions[] = $a;
        } 
        echo json_encode(saveAuctions($auctions) ? ['status'=>'success','timestamp'=>$currentTime] : ['status'=>'error']); 
        exit; 
    }
    
    if ($action === 'delete_all') { 
        saveAuctions([]); 
        saveNotifLog([]); 
        if(file_exists($notifFile)) unlink($notifFile); 
        echo json_encode(['status'=>'success','timestamp'=>$currentTime]); 
        exit; 
    }
    
    if ($action === 'send_notification') {
        $title = trim($_POST['title']??''); 
        $msg = trim($_POST['msg']??'');
        if(empty($title) || empty($msg)) {
            echo json_encode(['status'=>'error','message'=>'العنوان والرسالة مطلوبان']); 
            exit;
        }
        
        $notif = [
            'id' => 'NOTIF_' . uniqid(),
            'title' => $title,
            'msg' => $msg,
            'time' => $currentTime,
            'date' => date('Y-m-d H:i:s'),
            'type' => 'admin'
        ];
        
        file_put_contents($notifFile, json_encode($notif, JSON_UNESCAPED_UNICODE));
        
        // حفظ في السجل مع إضافة للمزودات النشطة
        $log = getNotifLog(); 
        array_unshift($log, $notif); 
        if(count($log) > 50) $log = array_slice($log, 0, 50);
        saveNotifLog($log);
        
        // إضافة الإشعار للمزاد النشط
        $auctions = getAuctions();
        foreach($auctions as $k => $a) {
            if(isset($a['status']) && $a['status'] === 'active') {
                if(!isset($auctions[$k]['admin_notifications'])) {
                    $auctions[$k]['admin_notifications'] = [];
                }
                $auctions[$k]['admin_notifications'][] = [
                    'message' => $msg,
                    'time' => date('H:i:s'),
                    'timestamp' => $currentTime
                ];
                $auctions[$k]['updated_at'] = $currentTime;
                break;
            }
        }
        saveAuctions($auctions);
        
        echo json_encode(['status'=>'success','timestamp'=>$currentTime]); 
        exit;
    }
    
    if ($action === 'clear_notification') { 
        if(file_exists($notifFile)) unlink($notifFile); 
        echo json_encode(['status'=>'success','timestamp'=>$currentTime]); 
        exit; 
    }
    
    if ($action === 'delete_notification') { 
        $log = []; 
        foreach(getNotifLog() as $n) {
            if(isset($n['id']) && $n['id'] !== ($_POST['notif_id']??'')) $log[] = $n;
        } 
        saveNotifLog($log); 
        echo json_encode(['status'=>'success','timestamp'=>$currentTime]); 
        exit; 
    }
    
    if ($action === 'delete_all_notifications') { 
        saveNotifLog([]); 
        echo json_encode(['status'=>'success','timestamp'=>$currentTime]); 
        exit; 
    }
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"/>
    <meta name="theme-color" content="#00685f">
    <title>لوحة التحكم - مزاد النخبة v11.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0&display=swap" rel="stylesheet"/>
    <script>tailwind.config={theme:{extend:{colors:{primary:"#00685f",whatsapp:"#25D366"}}}};</script>
    <style>
        * { -webkit-tap-highlight-color: transparent; box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'IBM Plex Sans Arabic', sans-serif; background: #f8fafc; overflow-x: hidden; padding-bottom: env(safe-area-inset-bottom); }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 20; user-select: none; pointer-events: none; }
        input, textarea, select { font-size: 16px !important; }
        input[type="number"] { -moz-appearance: textfield; -webkit-appearance: none; appearance: none; }
        input[type="number"]::-webkit-outer-spin-button, input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .custom-scrollbar::-webkit-scrollbar { width: 3px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
        .icon-btn { width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; cursor: pointer; border: none; background: transparent; transition: all 0.1s ease; }
        .icon-btn:hover { background: #f3f4f6; }
        .icon-btn:active { background: #e5e7eb; transform: scale(0.93); }
        .icon-btn .material-symbols-outlined { font-size: 18px; }
        .glass-effect { background: rgba(255,255,255,0.92); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        .input-field { width: 100%; border: 2px solid #e5e7eb; border-radius: 12px; padding: 12px 14px; outline: none; font-size: 16px; font-weight: 500; color: #1f2937; transition: all 0.15s ease; background: #fff; }
        .input-field:focus { border-color: #00685f; box-shadow: 0 0 0 3px rgba(0,104,95,0.06); }
        .input-field::placeholder { color: #cbd5e1; font-weight: 400; }
        .images-dropdown { max-height: 0; overflow: hidden; transition: max-height 0.3s ease; }
        .images-dropdown.open { max-height: 250px; }
        .lightbox-overlay { position: fixed; inset: 0; z-index: 100; background: rgba(0,0,0,0.9); display: flex; align-items: center; justify-content: center; padding: 20px; }
        .lightbox-overlay img { max-width: 95%; max-height: 85vh; object-fit: contain; border-radius: 12px; }
        
        /* نظام الإشعارات المتطور */
        .notif-toast { position: fixed; top: 20px; left: 50%; transform: translateX(-50%) translateY(-100px); background: white; border-radius: 16px; padding: 16px 20px; z-index: 200; box-shadow: 0 20px 60px rgba(0,0,0,0.2); border: 1px solid #e5e7eb; display: flex; align-items: center; gap: 12px; min-width: 300px; max-width: 90vw; transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .notif-toast.show { transform: translateX(-50%) translateY(0); }
        .notif-toast.success { border-right: 4px solid #10b981; }
        .notif-toast.error { border-right: 4px solid #ef4444; }
        .notif-toast.info { border-right: 4px solid #3b82f6; }
        .notif-toast.warning { border-right: 4px solid #f59e0b; }
        
        /* وقت حي */
        .live-clock { font-variant-numeric: tabular-nums; }
        @keyframes pulse-dot { 0%,100%{opacity:1}50%{opacity:0.3} }
        .pulse-dot { animation: pulse-dot 1.5s ease-in-out infinite; }
        
        /* تأثيرات التحديث */
        @keyframes update-flash { 0% { background-color: #fef3c7; } 100% { background-color: transparent; } }
        .update-flash { animation: update-flash 0.5s ease-out; }
        
        /* شريط التقدم */
        .progress-bar { position: relative; height: 4px; background: #e5e7eb; border-radius: 2px; overflow: hidden; }
        .progress-bar-fill { height: 100%; background: linear-gradient(90deg, #00685f, #00bfa5); transition: width 0.3s ease; }
    </style>
</head>
<body class="text-gray-800 antialiased">

<!-- نخب الإشعارات -->
<div id="notif-toast" class="notif-toast info">
    <span id="toast-icon" class="material-symbols-outlined text-2xl">notifications</span>
    <div class="flex-1">
        <p id="toast-title" class="font-bold text-xs text-gray-900"></p>
        <p id="toast-msg" class="text-[10px] text-gray-500"></p>
    </div>
    <button onclick="hideToast()" class="text-gray-400 hover:text-gray-600">
        <span class="material-symbols-outlined text-sm">close</span>
    </button>
</div>

<div class="max-w-4xl mx-auto p-3 sm:p-4 space-y-2.5 pb-20">

    <!-- الهيدر -->
    <div class="glass-effect sticky top-0 z-30 rounded-2xl border border-gray-200 px-3 py-2.5 flex items-center justify-between gap-2">
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 bg-gradient-to-br from-primary to-[#004d46] rounded-lg flex items-center justify-center text-white font-bold text-xs">ل</div>
            <div>
                <h1 class="text-sm font-bold text-gray-900">لوحة التحكم</h1>
                <div class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full pulse-dot"></span>
                    <p class="text-[8px] text-gray-400 live-clock" id="live-time">--:--:--</p>
                    <span class="text-[8px] text-gray-300">|</span>
                    <span class="text-[8px] text-gray-400">تحديث كل 1.5s</span>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-0.5">
            <button onclick="openModal('add-modal')" class="icon-btn text-primary bg-primary/5" title="إضافة مزاد">
                <span class="material-symbols-outlined">add_circle</span>
            </button>
            <button onclick="openModal('notif-modal')" class="icon-btn text-blue-500 bg-blue-50/50 relative" title="إرسال تنبيه">
                <span class="material-symbols-outlined">campaign</span>
            </button>
            <button onclick="openModal('notif-list-modal')" class="icon-btn text-amber-500 bg-amber-50/50 relative" title="التنبيهات">
                <span class="material-symbols-outlined">notifications</span>
                <span id="notif-badge" class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[8px] w-3.5 h-3.5 rounded-full flex items-center justify-center font-bold hidden">0</span>
            </button>
            <button onclick="window.location.reload()" class="icon-btn text-gray-400" title="تحديث">
                <span class="material-symbols-outlined">refresh</span>
            </button>
        </div>
    </div>

    <!-- الإحصائيات -->
    <div class="flex items-center gap-1.5 bg-white rounded-xl border border-gray-200 px-3 py-2 overflow-x-auto" id="stats-bar">
        <div class="flex items-center gap-1.5 px-2 py-1 rounded-lg flex-shrink-0">
            <span class="material-symbols-outlined text-sm text-primary">gavel</span>
            <span class="text-[9px] text-gray-400">نشطة</span>
            <span class="text-xs font-bold" id="stat-active">0</span>
        </div>
        <div class="w-px h-5 bg-gray-100 flex-shrink-0"></div>
        <div class="flex items-center gap-1.5 px-2 py-1 rounded-lg flex-shrink-0">
            <span class="material-symbols-outlined text-sm text-blue-500">inventory_2</span>
            <span class="text-[9px] text-gray-400">الكل</span>
            <span class="text-xs font-bold" id="stat-total">0</span>
        </div>
        <div class="w-px h-5 bg-gray-100 flex-shrink-0"></div>
        <div class="flex items-center gap-1.5 px-2 py-1 rounded-lg flex-shrink-0">
            <span class="material-symbols-outlined text-sm text-purple-500">groups</span>
            <span class="text-[9px] text-gray-400">مزايدين</span>
            <span class="text-xs font-bold" id="stat-bidders">0</span>
        </div>
        <div class="w-px h-5 bg-gray-100 flex-shrink-0"></div>
        <div class="flex items-center gap-1.5 px-2 py-1 rounded-lg flex-shrink-0">
            <span class="material-symbols-outlined text-sm text-orange-500">trending_up</span>
            <span class="text-[9px] text-gray-400">أعلى</span>
            <span class="text-[10px] font-bold" id="stat-highest">0</span>
        </div>
        <div class="w-px h-5 bg-gray-100 flex-shrink-0"></div>
        <div class="flex items-center gap-1.5 px-2 py-1 rounded-lg flex-shrink-0">
            <span class="material-symbols-outlined text-sm text-green-500">payments</span>
            <span class="text-[9px] text-gray-400">مبيعات</span>
            <span class="text-[10px] font-bold" id="stat-sales">0</span>
        </div>
        <div class="w-px h-5 bg-gray-100 flex-shrink-0"></div>
        <div class="flex items-center gap-1.5 px-2 py-1 rounded-lg flex-shrink-0">
            <span class="material-symbols-outlined text-sm text-cyan-500">percent</span>
            <span class="text-[9px] text-gray-400">ربح 1%</span>
            <span class="text-[10px] font-bold" id="stat-profit">0</span>
        </div>
        <div class="w-px h-5 bg-gray-100 flex-shrink-0"></div>
        <div class="flex items-center gap-1.5 px-2 py-1 rounded-lg flex-shrink-0">
            <span class="material-symbols-outlined text-sm text-red-500">block</span>
            <span class="text-[9px] text-gray-400">مغلقة</span>
            <span class="text-xs font-bold" id="stat-closed">0</span>
        </div>
        <div class="flex-1"></div>
        <button onclick="nukeAll()" class="icon-btn text-red-400 bg-red-50/50 flex-shrink-0" title="حذف الكل">
            <span class="material-symbols-outlined text-sm">delete_forever</span>
        </button>
    </div>

    <!-- المزادات -->
    <div id="auctions-container" class="space-y-2.5 min-h-[200px]">
        <div class="flex justify-center py-10">
            <div class="w-6 h-6 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
        </div>
    </div>

</div>

<!-- المودالات -->
<div id="add-modal" class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl flex flex-col max-h-[85vh] overflow-hidden" onclick="event.stopPropagation()">
        <div class="bg-gradient-to-r from-primary/5 to-transparent p-3 border-b border-gray-100 flex justify-between items-center shrink-0">
            <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                <span class="w-7 h-7 bg-primary rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-white text-sm">add</span>
                </span>
                إضافة مزاد جديد
            </h3>
            <button onclick="closeModal('add-modal')" class="icon-btn text-gray-400">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form id="add-form" onsubmit="handleAdd(event)" class="p-4 space-y-3 overflow-y-auto custom-scrollbar">
            <div>
                <label class="text-[10px] font-bold text-gray-500 mb-1.5 block">عنوان المزاد <span class="text-red-400">*</span></label>
                <input type="text" name="title" required class="input-field" placeholder="أدخل عنوان المزاد">
            </div>
            <div>
                <label class="text-[10px] font-bold text-gray-500 mb-1.5 block">وصف المزاد</label>
                <textarea name="desc" rows="2" class="input-field resize-none" placeholder="وصف مختصر للسلعة..."></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[10px] font-bold text-gray-500 mb-1.5 block">سعر البداية <span class="text-red-400">*</span></label>
                    <div class="relative">
                        <input type="number" name="start_price" required step="1" min="1" inputmode="numeric" pattern="[0-9]*" class="input-field font-mono font-bold pl-8" placeholder="0">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[10px] text-gray-400 font-bold">ر.س</span>
                    </div>
                </div>
                <div>
                    <label class="text-[10px] font-bold text-gray-500 mb-1.5 block">سعر البيع الفوري</label>
                    <div class="relative">
                        <input type="number" name="stop_price" step="1" min="0" inputmode="numeric" pattern="[0-9]*" class="input-field font-mono pl-8" placeholder="0">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[10px] text-gray-400 font-bold">ر.س</span>
                    </div>
                </div>
            </div>
            <div>
                <label class="text-[10px] font-bold text-gray-500 mb-1.5 block">مدة المزاد <span class="text-red-400">*</span></label>
                <div class="grid grid-cols-4 gap-1.5">
                    <label class="cursor-pointer"><input type="radio" name="duration" value="5" class="peer sr-only"><div class="border-2 border-gray-200 rounded-lg p-2 text-center peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:text-primary transition-all text-[11px] font-bold">5 د</div></label>
                    <label class="cursor-pointer"><input type="radio" name="duration" value="15" class="peer sr-only"><div class="border-2 border-gray-200 rounded-lg p-2 text-center peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:text-primary transition-all text-[11px] font-bold">15 د</div></label>
                    <label class="cursor-pointer"><input type="radio" name="duration" value="30" class="peer sr-only" checked><div class="border-2 border-gray-200 rounded-lg p-2 text-center peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:text-primary transition-all text-[11px] font-bold">30 د</div></label>
                    <label class="cursor-pointer"><input type="radio" name="duration" value="60" class="peer sr-only"><div class="border-2 border-gray-200 rounded-lg p-2 text-center peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:text-primary transition-all text-[11px] font-bold">1 س</div></label>
                    <label class="cursor-pointer"><input type="radio" name="duration" value="120" class="peer sr-only"><div class="border-2 border-gray-200 rounded-lg p-2 text-center peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:text-primary transition-all text-[11px] font-bold">2 س</div></label>
                    <label class="cursor-pointer"><input type="radio" name="duration" value="720" class="peer sr-only"><div class="border-2 border-gray-200 rounded-lg p-2 text-center peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:text-primary transition-all text-[11px] font-bold">12 س</div></label>
                    <label class="cursor-pointer"><input type="radio" name="duration" value="1440" class="peer sr-only"><div class="border-2 border-gray-200 rounded-lg p-2 text-center peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:text-primary transition-all text-[11px] font-bold">24 س</div></label>
                </div>
            </div>
            <div>
                <button type="button" onclick="toggleImages()" class="w-full flex items-center justify-between border-2 border-dashed border-gray-200 rounded-xl p-3 text-sm font-medium text-gray-500 hover:border-primary/30 transition-all">
                    <span class="flex items-center gap-2"><span class="material-symbols-outlined text-lg">photo_library</span> صور السلعة</span>
                    <span class="material-symbols-outlined text-lg transition-transform" id="images-arrow">expand_more</span>
                </button>
                <div class="images-dropdown" id="images-dropdown">
                    <div class="border-2 border-dashed border-gray-200 rounded-xl p-3 text-center mt-2 bg-gray-50/50 relative cursor-pointer">
                        <input type="file" id="media-files" multiple accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="previewFiles()">
                        <span class="material-symbols-outlined text-2xl text-gray-300 mb-1">cloud_upload</span>
                        <p class="text-[10px] text-gray-400">اختيار صور (5 كحد أقصى)</p>
                        <div id="preview-area" class="flex gap-1.5 mt-2 justify-center flex-wrap"></div>
                    </div>
                </div>
            </div>
            <button type="submit" class="w-full bg-primary text-white py-3 rounded-xl font-bold text-sm active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-sm">rocket_launch</span> نشر المزاد الآن
            </button>
        </form>
    </div>
</div>

<div id="notif-modal" class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-sm rounded-2xl p-4 shadow-2xl" onclick="event.stopPropagation()">
        <div class="flex justify-between items-center mb-3">
            <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-blue-500">campaign</span> إرسال تنبيه للمستخدمين
            </h3>
            <button onclick="closeModal('notif-modal')" class="icon-btn text-gray-400">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="space-y-3">
            <div>
                <label class="text-[10px] font-bold text-gray-500 mb-1 block">عنوان التنبيه</label>
                <input type="text" id="notif-title" placeholder="مثال: تنبيه هام" class="input-field">
            </div>
            <div>
                <label class="text-[10px] font-bold text-gray-500 mb-1 block">نص الرسالة</label>
                <textarea id="notif-msg" placeholder="اكتب رسالتك هنا..." rows="3" class="input-field resize-none"></textarea>
            </div>
            <div class="bg-blue-50 border border-blue-100 rounded-lg p-2.5">
                <p class="text-[9px] text-blue-600 flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">info</span>
                    سيظهر التنبيه فورياً للمستخدمين في صفحة المزاد
                </p>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <button onclick="closeModal('notif-modal')" class="py-2.5 bg-gray-100 text-gray-700 rounded-xl font-bold text-xs">إلغاء</button>
                <button onclick="sendNotif()" class="py-2.5 bg-blue-500 text-white rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 hover:bg-blue-600 transition-colors">
                    <span class="material-symbols-outlined text-sm">send</span> إرسال فوري
                </button>
            </div>
        </div>
    </div>
</div>

<div id="notif-list-modal" class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl flex flex-col max-h-[75vh] overflow-hidden" onclick="event.stopPropagation()">
        <div class="flex justify-between items-center p-3 border-b border-gray-100 shrink-0">
            <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-amber-500">notifications</span> 
                سجل التنبيهات
                <span id="notif-list-count" class="text-[10px] text-gray-400"></span>
            </h3>
            <button onclick="closeModal('notif-list-modal')" class="icon-btn text-gray-400">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div id="notif-list-content" class="p-2.5 overflow-y-auto custom-scrollbar flex-1 space-y-1.5">
            <p class="text-center text-gray-400 py-6 text-xs">جاري التحميل...</p>
        </div>
        <div class="p-2.5 border-t border-gray-100 shrink-0 grid grid-cols-2 gap-2">
            <button onclick="clearAllNotifs()" class="py-2 bg-red-50 text-red-600 rounded-lg text-[11px] font-bold hover:bg-red-100 flex items-center justify-center gap-1.5">
                <span class="material-symbols-outlined text-sm">delete_sweep</span> مسح الكل
            </button>
            <button onclick="closeModal('notif-list-modal')" class="py-2 bg-gray-100 text-gray-600 rounded-lg text-[11px] font-bold hover:bg-gray-200">إغلاق</button>
        </div>
    </div>
</div>

<div id="winner-modal" class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-sm rounded-2xl p-4 text-center shadow-2xl" onclick="event.stopPropagation()">
        <div class="w-16 h-16 bg-gradient-to-br from-yellow-400 to-amber-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 shadow-lg">
            <span class="material-symbols-outlined text-2xl">emoji_events</span>
        </div>
        <h3 class="text-lg font-bold text-gray-900 mb-1">🎉 تم البيع بنجاح!</h3>
        <p class="text-gray-500 text-xs mb-3">تم تأكيد الفائز وإغلاق المزاد</p>
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-3 mb-3 space-y-2 text-xs border border-green-100">
            <div class="flex justify-between"><span class="text-gray-400">الفائز:</span><span id="winner-name" class="font-bold text-gray-900"></span></div>
            <div class="flex justify-between"><span class="text-gray-400">الجوال:</span><span id="winner-phone" class="font-bold font-mono" dir="ltr"></span></div>
            <div class="flex justify-between"><span class="text-gray-400">القيمة:</span><span id="winner-amount" class="font-bold text-primary text-base"></span></div>
        </div>
        <a id="wa-link" href="#" target="_blank" class="block w-full bg-whatsapp text-white py-3 rounded-xl font-bold text-xs mb-2.5 active:opacity-90 transition-all flex items-center justify-center gap-2">
            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654z"/></svg>
            تواصل واتساب مع الفائز
        </a>
        <button onclick="closeModal('winner-modal')" class="text-gray-400 text-[10px] hover:text-gray-600">إغلاق</button>
    </div>
</div>

<div id="lightbox-modal" class="lightbox-overlay hidden" onclick="this.classList.add('hidden')">
    <button onclick="document.getElementById('lightbox-modal').classList.add('hidden')" class="absolute top-4 right-4 text-white bg-black/30 rounded-full p-2">
        <span class="material-symbols-outlined">close</span>
    </button>
    <img id="lightbox-img" src="" alt="صورة" onclick="event.stopPropagation()">
</div>

<div id="desc-modal" class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-sm rounded-2xl p-4 shadow-2xl text-center" onclick="event.stopPropagation()">
        <div class="flex justify-between items-center mb-3">
            <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">description</span> وصف السلعة
            </h3>
            <button onclick="closeModal('desc-modal')" class="icon-btn text-gray-400">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="bg-gray-50 rounded-xl p-4 mb-3">
            <p class="text-gray-600 text-sm leading-relaxed whitespace-pre-line" id="desc-content"></p>
        </div>
        <button onclick="closeModal('desc-modal')" class="w-full py-2.5 bg-gray-100 text-gray-700 rounded-xl font-bold text-sm hover:bg-gray-200">حسناً</button>
    </div>
</div>

<script>
// ===== نظام فوري متقدم v11.0 =====
var allData = [], uploadedImages = [], isLoading = false, lastUpdateTime = 0;
var clockInterval = null, dataInterval = null, badgeInterval = null, toastTimeout = null;

function el(id) { return document.getElementById(id); }
function fc(n) { return Number(n||0).toLocaleString('en-US'); }
function now() { return Math.floor(Date.now() / 1000); }

// ===== نظام الإشعارات الجميل =====
function showToast(type, title, message) {
    var toast = el('notif-toast');
    var icon = el('toast-icon');
    var titleEl = el('toast-title');
    var msgEl = el('toast-msg');
    
    if (!toast || !icon || !titleEl || !msgEl) return;
    
    // إعادة تعيين الكلاسات
    toast.className = 'notif-toast ' + type;
    icon.textContent = type === 'success' ? 'check_circle' : type === 'error' ? 'error' : type === 'warning' ? 'warning' : 'notifications';
    titleEl.textContent = title;
    msgEl.textContent = message;
    
    // إظهار النخب
    if (toastTimeout) clearTimeout(toastTimeout);
    toast.classList.add('show');
    
    toastTimeout = setTimeout(function() {
        toast.classList.remove('show');
    }, 4000);
}

function hideToast() {
    var toast = el('notif-toast');
    if (toast) toast.classList.remove('show');
    if (toastTimeout) clearTimeout(toastTimeout);
}

// ===== وقت حي كل ثانية =====
function startLiveClock() {
    if (clockInterval) clearInterval(clockInterval);
    function updateClock() {
        var now = new Date();
        var time = now.toLocaleTimeString('ar-SA', {hour:'2-digit', minute:'2-digit', second:'2-digit'});
        var timeEl = el('live-time');
        if (timeEl) timeEl.textContent = time;
    }
    updateClock();
    clockInterval = setInterval(updateClock, 1000);
}

// ===== مودالات =====
function openModal(id) { 
    var m = el(id); 
    if(!m) return; 
    m.classList.remove('hidden'); 
    m.onclick = function(e) { 
        if(e.target === m) closeModal(id); 
    }; 
    if(id === 'notif-list-modal') { 
        loadNotifList(); 
        updateBadge(); 
    } 
}

function closeModal(id) { 
    var m = el(id); 
    if(m) m.classList.add('hidden'); 
}

function toggleImages() { 
    var dd = el('images-dropdown'), arrow = el('images-arrow'); 
    if(dd && arrow) { 
        dd.classList.toggle('open'); 
        arrow.style.transform = dd.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0deg)'; 
    } 
}

// ===== تحميل البيانات - فوري =====
async function loadData() {
    if (isLoading) return;
    isLoading = true;
    try {
        var r = await fetch('adminn.php?action=get_auctions&t=' + now(), {cache:'no-store'});
        if (!r.ok) throw new Error('Network');
        var newData = await r.json();
        if (Array.isArray(newData)) {
            // التحقق من وجود تغييرات
            var hasChanges = JSON.stringify(newData) !== JSON.stringify(allData);
            allData = newData;
            renderAuctions(allData);
            updateStats(allData);
            lastUpdateTime = now();
            
            if (hasChanges && allData.length > 0) {
                var container = el('auctions-container');
                if (container) {
                    container.classList.add('update-flash');
                    setTimeout(function() { container.classList.remove('update-flash'); }, 500);
                }
            }
        }
    } catch(e) {
        console.error('تحميل البيانات:', e);
    }
    isLoading = false;
}

function updateStats(auctions) {
    if (!Array.isArray(auctions)) return;
    var active = 0, total = auctions.length, closed = 0, bidders = new Set(), highest = 0, sales = 0;
    auctions.forEach(function(a) {
        if (a.status === 'active') active++;
        if (['stopped','sold','ended'].indexOf(a.status) >= 0) closed++;
        if (a.bids && Array.isArray(a.bids)) {
            a.bids.forEach(function(b) {
                if (b.phone) bidders.add(b.phone);
                if (b.user) bidders.add(b.user);
                if (b.amount > highest) highest = b.amount;
            });
        }
        if (a.status === 'sold' && a.bids && a.winner_index !== undefined && a.bids[a.winner_index]) {
            sales += a.bids[a.winner_index].amount;
        }
    });
    var setTxt = function(id, tx) { var e = el(id); if (e && e.textContent !== tx) e.textContent = tx; };
    setTxt('stat-active', active);
    setTxt('stat-total', total);
    setTxt('stat-bidders', bidders.size);
    setTxt('stat-highest', fc(highest) + ' ر.س');
    setTxt('stat-sales', fc(sales) + ' ر.س');
    setTxt('stat-profit', fc(Math.round(sales * 0.01)) + ' ر.س');
    setTxt('stat-closed', closed);
}

function getTimeLeft(et) {
    var diff = et - now();
    if (diff <= 0) return { text: 'انتهى', exp: true };
    var h = Math.floor(diff / 3600), m = Math.floor((diff % 3600) / 60), s = diff % 60;
    return { 
        text: h > 0 ? h + ':' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0') : String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0'), 
        exp: false 
    };
}

function renderAuctions(auctions) {
    var c = el('auctions-container');
    if (!c) return;
    if (!auctions || !auctions.length) {
        c.innerHTML = '<div class="text-center py-14 bg-white rounded-2xl border border-dashed border-gray-200"><span class="material-symbols-outlined text-4xl text-gray-200 mb-2">inbox</span><p class="text-gray-400 font-bold text-sm">لا توجد مزادات</p><p class="text-gray-300 text-[10px] mt-1">اضغط على ＋ لإضافة مزاد جديد</p></div>';
        return;
    }
    var sorted = [].concat(auctions).sort(function(a, b) {
        var o = {active: 1, ended: 2, sold: 3, stopped: 4};
        return (o[a.status] || 5) - (o[b.status] || 5);
    });
    var html = '';
    sorted.forEach(function(a) {
        var live = a.status === 'active', ended = a.status === 'ended', sold = a.status === 'sold', stopped = a.status === 'stopped';
        var sc = 'bg-green-50 text-green-700 border-green-200', st = 'نشط', icon = 'timer';
        if (sold) { sc = 'bg-red-50 text-red-700 border-red-200'; st = 'تم البيع'; icon = 'verified'; }
        else if (stopped) { sc = 'bg-gray-100 text-gray-600 border-gray-200'; st = 'متوقف'; icon = 'pause_circle'; }
        else if (ended) { sc = 'bg-orange-50 text-orange-700 border-orange-200'; st = 'انتهى'; icon = 'timer_off'; }
        
        var timerHTML = '';
        var progressWidth = 100;
        if ((live || ended) && a.end_time) {
            var ti = getTimeLeft(a.end_time);
            var totalDuration = a.end_time - (a.created_at ? Math.floor(new Date(a.created_at).getTime() / 1000) : a.end_time - 1800);
            var elapsed = a.end_time - now();
            progressWidth = Math.max(0, Math.min(100, (elapsed / totalDuration) * 100));
            
            timerHTML = '<div class="flex items-center gap-2"><span class="text-[10px] ' + (ti.exp ? 'text-red-500' : 'text-orange-500') + ' font-mono font-bold">' + ti.text + '</span>' +
                (!ti.exp && live ? '<span class="w-1.5 h-1.5 bg-orange-500 rounded-full animate-pulse"></span>' : '') + '</div>';
        }
        
        var imgs = a.images || [];
        var imgsHTML = '';
        if (imgs.length > 0) {
            imgsHTML = '<div class="flex gap-1.5 justify-center mb-2">';
            imgs.slice(0, 4).forEach(function(src) {
                imgsHTML += '<img src="' + src + '" class="w-16 h-16 object-cover rounded-xl border-2 border-gray-100 cursor-pointer hover:border-primary transition-colors" onclick="openLightbox(\'' + src + '\')" alt="">';
            });
            if (imgs.length > 4) imgsHTML += '<span class="w-16 h-16 rounded-xl bg-gray-100 flex items-center justify-center text-[11px] text-gray-400 font-bold">+' + (imgs.length - 4) + '</span>';
            imgsHTML += '</div>';
        }
        
        var bidsHTML = '';
        if (a.bids && a.bids.length) {
            var sb = [].concat(a.bids).sort(function(x, y) { return y.amount - x.amount; }).slice(0, 5);
            sb.forEach(function(bid) {
                var oi = a.bids.indexOf(bid), iw = (sold && a.winner_index === oi) || (ended && a.winner_index === oi);
                bidsHTML += '<div class="flex justify-between items-center p-2 rounded-lg mb-1 border ' + (iw ? 'bg-gradient-to-r from-yellow-50 to-amber-50 border-yellow-200' : 'bg-gray-50/80 border-gray-100') + '">';
                bidsHTML += '<div class="flex items-center gap-2 min-w-0 flex-1"><div class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-[9px] text-white flex-shrink-0 ' + (iw ? 'bg-gradient-to-br from-yellow-400 to-amber-500' : 'bg-gradient-to-br from-gray-400 to-gray-600') + '">' + (iw ? '👑' : (bid.user ? bid.user.charAt(0) : '?')) + '</div>';
                bidsHTML += '<div class="min-w-0"><p class="font-bold text-[10px] truncate">' + (bid.user || 'مجهول') + (iw ? ' <span class="text-[7px] bg-yellow-400 text-white px-1 rounded-full">فائز</span>' : '') + '</p><p class="text-[8px] text-gray-400">' + (bid.phone || '-') + '</p></div></div>';
                bidsHTML += '<div class="flex items-center gap-1.5 flex-shrink-0"><span class="font-mono font-bold text-primary text-[11px] whitespace-nowrap">' + fc(bid.amount) + ' <span class="text-[8px] text-gray-400">ر.س</span></span>';
                if (!sold && !stopped) {
                    bidsHTML += '<div class="flex gap-0.5"><button onclick="acceptBid(\'' + a.id + '\',' + oi + ',\'' + (bid.user || '').replace(/'/g, "\\'") + '\',\'' + (bid.phone || '').replace(/'/g, "\\'") + '\',' + bid.amount + ')" class="icon-btn text-green-500 bg-green-50 w-6 h-6" title="قبول"><span class="material-symbols-outlined text-xs">check</span></button><button onclick="rejectBid(\'' + a.id + '\',' + oi + ')" class="icon-btn text-red-400 bg-red-50 w-6 h-6" title="رفض"><span class="material-symbols-outlined text-xs">close</span></button></div>';
                }
                bidsHTML += '</div></div>';
            });
            if (a.bids.length > 5) bidsHTML += '<p class="text-[9px] text-gray-400 text-center">+ ' + (a.bids.length - 5) + ' مزايدات إضافية</p>';
        } else bidsHTML = '<p class="text-[10px] text-gray-300 text-center py-3">لا توجد مزايدات بعد</p>';
        
        html += '<div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">';
        html += '<div class="flex items-center justify-between p-2.5 border-b border-gray-100 bg-gradient-to-r from-gray-50/50">' +
            '<span class="px-2.5 py-1 rounded-full text-[9px] font-bold border ' + sc + ' flex items-center gap-1"><span class="material-symbols-outlined text-xs">' + icon + '</span>' + st + '</span>' +
            '<div class="flex items-center gap-1.5">' + timerHTML +
            '<button onclick="deleteAuction(\'' + a.id + '\')" class="icon-btn text-gray-300 hover:text-red-400 w-7 h-7" title="حذف"><span class="material-symbols-outlined text-sm">delete</span></button>' +
            (live || ended ? '<button onclick="stopAuction(\'' + a.id + '\')" class="icon-btn text-gray-300 hover:text-orange-400 w-7 h-7" title="إيقاف"><span class="material-symbols-outlined text-sm">pause_circle</span></button>' : '') +
            '</div></div>';
        
        // شريط التقدم للمزادات النشطة
        if (live) {
            html += '<div class="progress-bar mx-2.5 mt-2"><div class="progress-bar-fill" style="width:' + progressWidth + '%"></div></div>';
        }
        
        html += '<div class="p-2.5">';
        html += imgsHTML;
        html += '<div class="text-center mb-2">';
        html += '<h3 class="font-bold text-gray-900 text-sm">' + (a.title || 'بدون عنوان') + '</h3>';
        html += '<div class="flex items-center justify-center gap-2 mt-1">';
        html += '<span class="text-[9px] text-gray-400">' + (a.created_at || '') + '</span>';
        if (a.desc) {
            html += '<button onclick="openDesc(\'' + a.id + '\')" class="text-[9px] text-primary font-medium hover:underline flex items-center gap-1"><span class="material-symbols-outlined text-sm">description</span> وصف</button>';
        }
        html += '</div></div>';
        html += '<div class="grid grid-cols-3 gap-2 mb-2">';
        html += '<div class="bg-gray-50 rounded-lg p-2 text-center"><p class="text-[8px] text-gray-400">الحالي</p><p class="text-sm font-bold text-primary font-mono">' + fc(a.current_price || 0) + ' <span class="text-[8px] text-gray-400">ر.س</span></p></div>';
        html += '<div class="bg-gray-50 rounded-lg p-2 text-center"><p class="text-[8px] text-gray-400">البداية</p><p class="text-sm font-bold text-gray-600 font-mono">' + fc(a.start_price || 0) + ' <span class="text-[8px] text-gray-400">ر.س</span></p></div>';
        html += '<div class="bg-gray-50 rounded-lg p-2 text-center"><p class="text-[8px] text-gray-400">المزايدات</p><p class="text-sm font-bold text-gray-600">' + (a.bids ? a.bids.length : 0) + '</p></div></div>';
        html += '<div class="flex justify-between items-center mb-1.5"><span class="text-[9px] font-bold text-gray-500 flex items-center gap-1"><span class="material-symbols-outlined text-sm">history</span> سجل المزايدات</span></div>';
        html += '<div class="max-h-40 overflow-y-auto custom-scrollbar pr-1">' + bidsHTML + '</div></div></div>';
    });
    c.innerHTML = html;
}

// ===== قبول ورفض فوري =====
async function acceptBid(aucId, bidIdx, userName, userPhone, bidAmount) {
    if (!confirm('تأكيد بيع السلعة لـ ' + userName + ' بقيمة ' + fc(bidAmount) + ' ر.س؟')) return;
    var fd = new FormData(); 
    fd.append('action', 'accept_bid'); 
    fd.append('auction_id', aucId); 
    fd.append('bid_index', bidIdx);
    try {
        var r = await fetch('adminn.php', {method:'POST', body:fd});
        var d = await r.json();
        if (d.status === 'success') {
            el('winner-name').textContent = userName;
            el('winner-phone').textContent = userPhone || 'غير متوفر';
            el('winner-amount').textContent = fc(bidAmount) + ' ر.س';
            var phone = (userPhone || '').replace(/[^0-9]/g, '');
            if (phone.startsWith('0')) phone = '966' + phone.substring(1);
            if (!phone.startsWith('966')) phone = '966' + phone;
            el('wa-link').href = 'https://wa.me/' + phone + '?text=' + encodeURIComponent('مبروك ' + userName + ' 🎉\n\nتم قبول مزايدتك بقيمة ' + fc(bidAmount) + ' ريال.\n\nيرجى التواصل لإتمام التسليم.');
            openModal('winner-modal');
            showToast('success', 'تم البيع بنجاح', 'تم قبول مزايدة ' + userName + ' بقيمة ' + fc(bidAmount) + ' ر.س');
            loadData();
        } else {
            showToast('error', 'خطأ', d.message || 'حدث خطأ غير متوقع');
        }
    } catch(e) { 
        showToast('error', 'فشل الاتصال', 'يرجى التحقق من الاتصال بالخادم');
    }
}

async function rejectBid(aucId, bidIdx) {
    if (!confirm('حذف هذه المزايدة نهائياً؟')) return;
    var fd = new FormData(); 
    fd.append('action', 'reject_bid'); 
    fd.append('auction_id', aucId); 
    fd.append('bid_index', bidIdx);
    try {
        var r = await fetch('adminn.php', {method:'POST', body:fd});
        var d = await r.json();
        if (d.status === 'success') {
            showToast('warning', 'تم الرفض', 'تم رفض المزايدة وحذفها');
            loadData();
        } else {
            showToast('error', 'خطأ', d.message || 'حدث خطأ');
        }
    } catch(e) { 
        showToast('error', 'فشل الاتصال', 'يرجى التحقق من الاتصال');
    }
}

function openLightbox(src) { 
    var img = el('lightbox-img'), modal = el('lightbox-modal'); 
    if (img && modal) { 
        img.src = src; 
        modal.classList.remove('hidden'); 
    } 
}

function openDesc(aucId) { 
    var auc = allData.find(function(a) { return a.id === aucId; }); 
    if (!auc) return; 
    var content = el('desc-content'); 
    if (content) { 
        content.textContent = auc.desc || 'لا يوجد وصف متاح'; 
        openModal('desc-modal'); 
    } 
}

async function stopAuction(aucId) { 
    if (!confirm('إيقاف المزاد؟')) return; 
    var fd = new FormData(); 
    fd.append('action', 'stop_auction'); 
    fd.append('auction_id', aucId); 
    await fetch('adminn.php', {method:'POST', body:fd}); 
    showToast('info', 'تم الإيقاف', 'تم إيقاف المزاد');
    loadData(); 
}

async function deleteAuction(aucId) { 
    if (!confirm('حذف الإعلان؟')) return; 
    var fd = new FormData(); 
    fd.append('action', 'delete_auction'); 
    fd.append('auction_id', aucId); 
    await fetch('adminn.php', {method:'POST', body:fd}); 
    showToast('info', 'تم الحذف', 'تم حذف المزاد');
    loadData(); 
}

async function nukeAll() { 
    if (!confirm('⚠️ حذف جميع البيانات؟')) return; 
    if (!confirm('تأكيد نهائي - لا يمكن التراجع')) return; 
    var fd = new FormData(); 
    fd.append('action', 'delete_all'); 
    await fetch('adminn.php', {method:'POST', body:fd}); 
    showToast('warning', 'تم الحذف', 'تم حذف جميع البيانات');
    loadData(); 
}

async function sendNotif() {
    var title = el('notif-title').value.trim(), msg = el('notif-msg').value.trim();
    if (!title || !msg) { 
        showToast('error', 'بيانات ناقصة', 'يرجى إدخال عنوان ورسالة التنبيه');
        return; 
    }
    var fd = new FormData(); 
    fd.append('action', 'send_notification'); 
    fd.append('title', title); 
    fd.append('msg', msg);
    try {
        var r = await fetch('adminn.php', {method:'POST', body:fd});
        var d = await r.json();
        if (d.status === 'success') {
            showToast('success', 'تم الإرسال', 'تم إرسال التنبيه للمستخدمين بنجاح');
            el('notif-title').value = ''; 
            el('notif-msg').value = ''; 
            closeModal('notif-modal'); 
            updateBadge();
        } else {
            showToast('error', 'خطأ', d.message || 'فشل الإرسال');
        }
    } catch(e) {
        showToast('error', 'فشل الاتصال', 'يرجى المحاولة مرة أخرى');
    }
}

async function updateBadge() {
    try {
        var r = await fetch('adminn.php?action=get_notif_count&t=' + now(), {cache:'no-store'});
        var d = await r.json();
        var badge = el('notif-badge');
        if (badge) {
            if (d.count > 0) { 
                badge.textContent = d.count > 99 ? '99+' : d.count; 
                badge.classList.remove('hidden'); 
            } else badge.classList.add('hidden');
        }
    } catch(e) {}
}

async function loadNotifList() {
    var c = el('notif-list-content'); 
    if (!c) return;
    try {
        var r = await fetch('adminn.php?action=get_notifications&t=' + now(), {cache:'no-store'});
        var log = await r.json();
        if (!log || !log.length) { 
            c.innerHTML = '<div class="text-center py-8 text-gray-400"><span class="material-symbols-outlined text-3xl mb-1">notifications_off</span><p class="text-[11px]">لا توجد تنبيهات</p></div>'; 
            el('notif-list-count').textContent = '';
            return; 
        }
        el('notif-list-count').textContent = '(' + log.length + ')';
        var h = '';
        log.forEach(function(n) {
            var typeIcon = n.type === 'new_auction' ? 'gavel' : 'campaign';
            var typeColor = n.type === 'new_auction' ? 'text-green-500' : 'text-blue-500';
            h += '<div class="flex items-start justify-between bg-gray-50 rounded-lg p-2.5 border border-gray-100 hover:bg-gray-100 transition-colors">' +
                '<div class="flex items-start gap-2 flex-1 min-w-0">' +
                '<span class="material-symbols-outlined ' + typeColor + ' text-sm mt-0.5">' + typeIcon + '</span>' +
                '<div class="flex-1 min-w-0"><p class="font-bold text-[11px]">' + (n.title || '') + '</p><p class="text-[9px] text-gray-500">' + (n.msg || '') + '</p><p class="text-[8px] text-gray-300 mt-0.5">' + (n.date || '') + '</p></div></div>' +
                '<button onclick="deleteNotif(\'' + n.id + '\')" class="icon-btn text-gray-300 hover:text-red-400 flex-shrink-0 w-6 h-6"><span class="material-symbols-outlined text-xs">delete</span></button></div>';
        });
        c.innerHTML = h;
    } catch(e) { 
        c.innerHTML = '<div class="text-center py-8 text-gray-400"><p class="text-[11px]">تعذر تحميل التنبيهات</p></div>'; 
    }
}

async function deleteNotif(id) { 
    var fd = new FormData(); 
    fd.append('action', 'delete_notification'); 
    fd.append('notif_id', id); 
    await fetch('adminn.php', {method:'POST', body:fd}); 
    loadNotifList(); 
    updateBadge(); 
}

async function clearAllNotifs() {
    if (!confirm('مسح جميع التنبيهات؟')) return;
    await fetch('adminn.php', {method:'POST', body:new FormData([['action','clear_notification']])});
    await fetch('adminn.php', {method:'POST', body:new FormData([['action','delete_all_notifications']])});
    loadNotifList(); 
    updateBadge(); 
    closeModal('notif-list-modal');
    showToast('info', 'تم المسح', 'تم مسح جميع التنبيهات');
}

function previewFiles() {
    var input = el('media-files'), preview = el('preview-area');
    if (!input || !preview) return;
    preview.innerHTML = ''; 
    uploadedImages = [];
    Array.from(input.files).slice(0, 5).forEach(function(file, i) {
        var reader = new FileReader();
        reader.onload = function(e) { 
            uploadedImages.push(e.target.result); 
            preview.innerHTML += '<div class="relative"><img src="' + e.target.result + '" class="w-12 h-12 object-cover rounded-lg border-2 border-gray-200"><span class="absolute -top-1 -right-1 bg-primary text-white text-[8px] w-4 h-4 rounded-full flex items-center justify-center font-bold">' + (i + 1) + '</span></div>'; 
        };
        reader.readAsDataURL(file);
    });
}

async function handleAdd(e) {
    e.preventDefault();
    var form = e.target, btn = form.querySelector('button[type="submit"]'); 
    if (!btn) return;
    btn.disabled = true; 
    btn.innerHTML = '<span class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span> جاري النشر...';
    var fd = new FormData(form); 
    fd.append('action', 'add_auction'); 
    fd.append('images', JSON.stringify(uploadedImages));
    try {
        var r = await fetch('adminn.php', {method:'POST', body:fd}); 
        var d = await r.json();
        if (d.status === 'success') {
            closeModal('add-modal'); 
            form.reset(); 
            el('preview-area').innerHTML = ''; 
            uploadedImages = [];
            el('images-dropdown').classList.remove('open'); 
            el('images-arrow').style.transform = 'rotate(0deg)'; 
            showToast('success', 'تم النشر', 'تم نشر المزاد بنجاح');
            loadData();
        } else {
            showToast('error', 'خطأ', d.message || 'حدث خطأ أثناء النشر');
        }
    } catch(err) { 
        showToast('error', 'فشل الاتصال', 'يرجى التحقق من الاتصال');
    }
    btn.disabled = false; 
    btn.innerHTML = '<span class="material-symbols-outlined text-sm">rocket_launch</span> نشر المزاد الآن';
}

// ===== بدء التشغيل =====
function startAll() {
    loadData();
    updateBadge();
    startLiveClock();
    
    // تحديث البيانات كل 1.5 ثانية للتزامن مع صفحة المزاد
    if (dataInterval) clearInterval(dataInterval);
    dataInterval = setInterval(function() { 
        if (!isLoading) loadData(); 
    }, 1500);
    
    // تحديث العداد كل 3 ثواني
    if (badgeInterval) clearInterval(badgeInterval);
    badgeInterval = setInterval(updateBadge, 3000);
    
    // إظهار رسالة ترحيب
    setTimeout(function() {
        showToast('info', 'لوحة التحكم جاهزة', 'التحديثات تعمل بشكل فوري');
    }, 1000);
}

startAll();
</script>
</body>
</html>
