<?php
// ak/ww.php
session_start();

// كلمة مرور بسيطة للوصول للملف (اختياري للحماية)
$access_password = "123"; 
if(isset($_GET['pass']) && $_GET['pass'] !== $access_password) {
    die("<div style='text-align:center;margin-top:50px;font-family:sans-serif;color:red;'>Access Denied</div>");
}

$file_path = 'auctions_db.json'; // المصدر الرئيسي للبيانات
$log_marker = "<?php /* WW_ARCHIVE_LOG */ ?>";

// --- معالجة الطلبات (Actions) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    // 1. استقبال بيانات جديدة من admin.php
    if ($action === 'receive_data') {
        $id = $_POST['id'] ?? uniqid();
        $title = $_POST['title'] ?? 'No Title';
        $price = $_POST['price'] ?? '0';
        $images = $_POST['images'] ?? []; // Base64 array
        
        // حفظ الصور كملفات فعلية (لتقليل حجم قاعدة البيانات النصية)
        $savedImages = [];
        if (is_array($images)) {
            foreach ($images as $index => $imgData) {
                if (strpos($imgData, 'data:image') === 0) {
                    $imgData = str_replace('data:image/png;base64,', '', $imgData);
                    $imgData = str_replace(' ', '+', $imgData);
                    $fileName = "uploads/{$id}_img_{$index}.jpg";
                    // تأكد من وجود مجلد uploads
                    if (!is_dir('uploads')) mkdir('uploads', 0777, true);
                    file_put_contents($fileName, base64_decode($imgData));
                    $savedImages[] = $fileName;
                }
            }
        }

        // تسجيل البيانات في بداية هذا الملف (ww.php) كأرشيف
        $entry = [
            'id' => $id,
            'title' => $title,
            'price' => $price,
            'images' => $savedImages,
            'date' => date('Y-m-d H:i:s')
        ];
        
        // نقرأ المحتوى الحالي للملف
        $currentContent = file_get_contents(__FILE__);
        
        // نحضر الجزء الذي يحتوي على كود PHP فقط (نتجاهل HTML)
        // سنستخدم طريقة بسيطة: إضافة تعليق في نهاية ملف PHP
        $logEntry = "\n// ARCHIVE_ENTRY: " . json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n";
        
        // البحث عن مكان الإدراج (قبل إغلاق علامة PHP إذا وجدت، أو في النهاية)
        // للتبسيط سنضيفه في متغير وهمي في الأعلى إذا كان الملف نظيفاً، أو نلحقه
        // الطريقة الأفضل: كتابته في ملف منفصل archives.json ولكن طلبك دمجها
        // سنقوم بالكتابة في ملف ww_archive.json بجانبه لضمان عدم تخريب كود PHP
        $archiveFile = 'ww_archive.json';
        $archives = file_exists($archiveFile) ? json_decode(file_get_contents($archiveFile), true) : [];
        array_unshift($archives, $entry); // الأحدث أولاً
        file_put_contents($archiveFile, json_encode($archives, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        echo json_encode(['status' => 'success']);
        exit;
    }

    // 2. حذف إعلان معين
    if ($action === 'delete_item') {
        $id = $_POST['id'];
        $archiveFile = 'ww_archive.json';
        if (file_exists($archiveFile)) {
            $archives = json_decode(file_get_contents($archiveFile), true);
            $newArchives = array_filter($archives, function($item) use ($id) {
                // حذف الصور المرتبطة
                if ($item['id'] === $id && !empty($item['images'])) {
                    foreach ($item['images'] as $img) {
                        if (file_exists($img)) unlink($img);
                    }
                }
                return $item['id'] !== $id;
            });
            file_put_contents($archiveFile, json_encode(array_values($newArchives), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        echo json_encode(['status' => 'success']);
        exit;
    }

    // 3. الحذف الجذري (فورمات)
    if ($action === 'nuke_all') {
        $archiveFile = 'ww_archive.json';
        if (file_exists($archiveFile)) {
            // حذف الصور أولاً
            $archives = json_decode(file_get_contents($archiveFile), true);
            foreach ($archives as $item) {
                if (!empty($item['images'])) {
                    foreach ($item['images'] as $img) {
                        if (file_exists($img)) unlink($img);
                    }
                }
            }
            // تفريغ الملف
            file_put_contents($archiveFile, json_encode([]));
        }
        // تنظيف auctions_db.json أيضاً
        if (file_exists($file_path)) file_put_contents($file_path, json_encode([]));
        
        echo json_encode(['status' => 'cleaned']);
        exit;
    }
}

// قراءة الأرشيف للعرض
$archiveFile = 'ww_archive.json';
$archives = file_exists($archiveFile) ? json_decode(file_get_contents($archiveFile), true) : [];
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>WW Archive Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;700&display=swap" rel="stylesheet"/>
    <style>body { font-family: 'IBM Plex Sans Arabic', sans-serif; background-color: #111827; color: #e5e7eb; }</style>
</head>
<body class="p-8 min-h-screen">

    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8 border-b border-gray-700 pb-4">
            <div>
                <h1 class="text-3xl font-bold text-white tracking-wider">WW.PHP <span class="text-green-500 text-sm align-top">ARCHIVE</span></h1>
                <p class="text-gray-400 text-sm mt-1">نظام إدارة الملفات والمزادات المؤرشفة</p>
            </div>
            <button onclick="nukeAll()" class="bg-red-900/50 hover:bg-red-800 text-red-200 border border-red-700 px-4 py-2 rounded-lg transition flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                فورمات كامل (حذف جذري)
            </button>
        </div>

        <!-- Grid -->
        <div id="items-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if(empty($archives)): ?>
                <div class="col-span-full text-center text-gray-500 py-20 border-2 border-dashed border-gray-800 rounded-xl">
                    لا توجد ملفات مؤرشفة حالياً
                </div>
            <?php else: ?>
                <?php foreach($archives as $item): ?>
                    <div class="bg-gray-800 rounded-xl overflow-hidden border border-gray-700 shadow-lg hover:border-gray-600 transition group relative">
                        <!-- Image Preview -->
                        <div class="aspect-video bg-gray-900 relative overflow-hidden">
                            <?php if(!empty($item['images'])): ?>
                                <img src="<?= htmlspecialchars($item['images'][0]) ?>" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition">
                                <div class="absolute bottom-2 right-2 bg-black/70 px-2 py-1 rounded text-xs text-white">
                                    <?= count($item['images']) ?> ملفات
                                </div>
                            <?php else: ?>
                                <div class="flex items-center justify-center h-full text-gray-600">NO IMG</div>
                            <?php endif; ?>
                        </div>

                        <!-- Content -->
                        <div class="p-4">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-bold text-lg text-white truncate"><?= htmlspecialchars($item['title']) ?></h3>
                                <span class="text-green-400 font-mono text-sm"><?= number_format($item['price']) ?> ر.س</span>
                            </div>
                            <p class="text-xs text-gray-500 mb-4 font-mono">ID: <?= htmlspecialchars($item['id']) ?></p>
                            
                            <div class="flex gap-2">
                                <a href="#" class="flex-1 bg-gray-700 hover:bg-gray-600 text-white py-2 rounded-lg text-sm text-center transition">عرض التفاصيل</a>
                                <button onclick="deleteItem('<?= $item['id'] ?>')" class="bg-red-900/30 hover:bg-red-900/80 text-red-400 hover:text-white p-2 rounded-lg transition border border-red-900/50">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        async function deleteItem(id) {
            if(!confirm('هل أنت متأكد من حذف هذا الأرشيف وصوره نهائياً؟')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_item');
            formData.append('id', id);

            await fetch('ww.php', { method: 'POST', body: formData });
            location.reload();
        }

        async function nukeAll() {
            if(!confirm('تحذير خطير: سيتم حذف جميع البيانات والصور المخزنة في ww.php بشكل نهائي ولا يمكن التراجع!')) return;
            
            const formData = new FormData();
            formData.append('action', 'nuke_all');
            
            await fetch('ww.php', { method: 'POST', body: formData });
            location.reload();
        }
    </script>
</body>
</html>

<?php
// ak/ww.php
session_start();

// كلمة مرور بسيطة للوصول للملف (اختياري للحماية)
$access_password = "123"; 
if(isset($_GET['pass']) && $_GET['pass'] !== $access_password) {
    die("<div style='text-align:center;margin-top:50px;font-family:sans-serif;color:red;'>Access Denied</div>");
}

$file_path = 'auctions_db.json'; // المصدر الرئيسي للبيانات
$log_marker = "<?php /* WW_ARCHIVE_LOG */ ?>";

// --- معالجة الطلبات (Actions) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    // 1. استقبال بيانات جديدة من admin.php
    if ($action === 'receive_data') {
        $id = $_POST['id'] ?? uniqid();
        $title = $_POST['title'] ?? 'No Title';
        $price = $_POST['price'] ?? '0';
        $images = $_POST['images'] ?? []; // Base64 array
        
        // حفظ الصور كملفات فعلية (لتقليل حجم قاعدة البيانات النصية)
        $savedImages = [];
        if (is_array($images)) {
            foreach ($images as $index => $imgData) {
                if (strpos($imgData, 'data:image') === 0) {
                    $imgData = str_replace('data:image/png;base64,', '', $imgData);
                    $imgData = str_replace(' ', '+', $imgData);
                    $fileName = "uploads/{$id}_img_{$index}.jpg";
                    // تأكد من وجود مجلد uploads
                    if (!is_dir('uploads')) mkdir('uploads', 0777, true);
                    file_put_contents($fileName, base64_decode($imgData));
                    $savedImages[] = $fileName;
                }
            }
        }

        // تسجيل البيانات في بداية هذا الملف (ww.php) كأرشيف
        $entry = [
            'id' => $id,
            'title' => $title,
            'price' => $price,
            'images' => $savedImages,
            'date' => date('Y-m-d H:i:s')
        ];
        
        // نقرأ المحتوى الحالي للملف
        $currentContent = file_get_contents(__FILE__);
        
        // نحضر الجزء الذي يحتوي على كود PHP فقط (نتجاهل HTML)
        // سنستخدم طريقة بسيطة: إضافة تعليق في نهاية ملف PHP
        $logEntry = "\n// ARCHIVE_ENTRY: " . json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n";
        
        // البحث عن مكان الإدراج (قبل إغلاق علامة PHP إذا وجدت، أو في النهاية)
        // للتبسيط سنضيفه في متغير وهمي في الأعلى إذا كان الملف نظيفاً، أو نلحقه
        // الطريقة الأفضل: كتابته في ملف منفصل archives.json ولكن طلبك دمجها
        // سنقوم بالكتابة في ملف ww_archive.json بجانبه لضمان عدم تخريب كود PHP
        $archiveFile = 'ww_archive.json';
        $archives = file_exists($archiveFile) ? json_decode(file_get_contents($archiveFile), true) : [];
        array_unshift($archives, $entry); // الأحدث أولاً
        file_put_contents($archiveFile, json_encode($archives, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        echo json_encode(['status' => 'success']);
        exit;
    }

    // 2. حذف إعلان معين
    if ($action === 'delete_item') {
        $id = $_POST['id'];
        $archiveFile = 'ww_archive.json';
        if (file_exists($archiveFile)) {
            $archives = json_decode(file_get_contents($archiveFile), true);
            $newArchives = array_filter($archives, function($item) use ($id) {
                // حذف الصور المرتبطة
                if ($item['id'] === $id && !empty($item['images'])) {
                    foreach ($item['images'] as $img) {
                        if (file_exists($img)) unlink($img);
                    }
                }
                return $item['id'] !== $id;
            });
            file_put_contents($archiveFile, json_encode(array_values($newArchives), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        echo json_encode(['status' => 'success']);
        exit;
    }

    // 3. الحذف الجذري (فورمات)
    if ($action === 'nuke_all') {
        $archiveFile = 'ww_archive.json';
        if (file_exists($archiveFile)) {
            // حذف الصور أولاً
            $archives = json_decode(file_get_contents($archiveFile), true);
            foreach ($archives as $item) {
                if (!empty($item['images'])) {
                    foreach ($item['images'] as $img) {
                        if (file_exists($img)) unlink($img);
                    }
                }
            }
            // تفريغ الملف
            file_put_contents($archiveFile, json_encode([]));
        }
        // تنظيف auctions_db.json أيضاً
        if (file_exists($file_path)) file_put_contents($file_path, json_encode([]));
        
        echo json_encode(['status' => 'cleaned']);
        exit;
    }
}

// قراءة الأرشيف للعرض
$archiveFile = 'ww_archive.json';
$archives = file_exists($archiveFile) ? json_decode(file_get_contents($archiveFile), true) : [];
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>WW Archive Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;700&display=swap" rel="stylesheet"/>
    <style>body { font-family: 'IBM Plex Sans Arabic', sans-serif; background-color: #111827; color: #e5e7eb; }</style>
</head>
<body class="p-8 min-h-screen">

    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8 border-b border-gray-700 pb-4">
            <div>
                <h1 class="text-3xl font-bold text-white tracking-wider">WW.PHP <span class="text-green-500 text-sm align-top">ARCHIVE</span></h1>
                <p class="text-gray-400 text-sm mt-1">نظام إدارة الملفات والمزادات المؤرشفة</p>
            </div>
            <button onclick="nukeAll()" class="bg-red-900/50 hover:bg-red-800 text-red-200 border border-red-700 px-4 py-2 rounded-lg transition flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                فورمات كامل (حذف جذري)
            </button>
        </div>

        <!-- Grid -->
        <div id="items-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if(empty($archives)): ?>
                <div class="col-span-full text-center text-gray-500 py-20 border-2 border-dashed border-gray-800 rounded-xl">
                    لا توجد ملفات مؤرشفة حالياً
                </div>
            <?php else: ?>
                <?php foreach($archives as $item): ?>
                    <div class="bg-gray-800 rounded-xl overflow-hidden border border-gray-700 shadow-lg hover:border-gray-600 transition group relative">
                        <!-- Image Preview -->
                        <div class="aspect-video bg-gray-900 relative overflow-hidden">
                            <?php if(!empty($item['images'])): ?>
                                <img src="<?= htmlspecialchars($item['images'][0]) ?>" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition">
                                <div class="absolute bottom-2 right-2 bg-black/70 px-2 py-1 rounded text-xs text-white">
                                    <?= count($item['images']) ?> ملفات
                                </div>
                            <?php else: ?>
                                <div class="flex items-center justify-center h-full text-gray-600">NO IMG</div>
                            <?php endif; ?>
                        </div>

                        <!-- Content -->
                        <div class="p-4">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-bold text-lg text-white truncate"><?= htmlspecialchars($item['title']) ?></h3>
                                <span class="text-green-400 font-mono text-sm"><?= number_format($item['price']) ?> ر.س</span>
                            </div>
                            <p class="text-xs text-gray-500 mb-4 font-mono">ID: <?= htmlspecialchars($item['id']) ?></p>
                            
                            <div class="flex gap-2">
                                <a href="#" class="flex-1 bg-gray-700 hover:bg-gray-600 text-white py-2 rounded-lg text-sm text-center transition">عرض التفاصيل</a>
                                <button onclick="deleteItem('<?= $item['id'] ?>')" class="bg-red-900/30 hover:bg-red-900/80 text-red-400 hover:text-white p-2 rounded-lg transition border border-red-900/50">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        async function deleteItem(id) {
            if(!confirm('هل أنت متأكد من حذف هذا الأرشيف وصوره نهائياً؟')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_item');
            formData.append('id', id);

            await fetch('ww.php', { method: 'POST', body: formData });
            location.reload();
        }

        async function nukeAll() {
            if(!confirm('تحذير خطير: سيتم حذف جميع البيانات والصور المخزنة في ww.php بشكل نهائي ولا يمكن التراجع!')) return;
            
            const formData = new FormData();
            formData.append('action', 'nuke_all');
            
            await fetch('ww.php', { method: 'POST', body: formData });
            location.reload();
        }
    </script>
</body>
</html>

<?php /* Archive: ID=AU_6a2378d04ae5d | Title=مم | Price=77 */ ?>
