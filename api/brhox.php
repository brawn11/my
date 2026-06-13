<?php
// ak/brhox.php - لوحة تحكم العملاء v4.0 - تصميم مصغر ومرتب

session_start();

define('ADMIN_PASSWORD', 'Aa123');

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_authenticated']);
    header('Location: brhox.php');
    exit;
}

$errorMsg = '';
if (isset($_POST['admin_password'])) {
    if ($_POST['admin_password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_authenticated'] = true;
        header('Location: brhox.php');
        exit;
    } else {
        $errorMsg = 'كلمة المرور غير صحيحة';
    }
}

$isAuthenticated = isset($_SESSION['admin_authenticated']);
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"/>
    <meta name="theme-color" content="#00685f">
    <title>سجل العملاء - مزاد النخبة</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0&display=swap" rel="stylesheet"/>

    <script>tailwind.config={theme:{extend:{colors:{primary:"#00685f",primaryLight:"#e0f2f1",primaryDark:"#004d46",whatsapp:"#25D366"}}}};</script>

    <style>
        * { -webkit-tap-highlight-color: transparent; box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'IBM Plex Sans Arabic', sans-serif; background: #f8fafc; padding-bottom: env(safe-area-inset-bottom); overflow-x: hidden; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 20; user-select: none; pointer-events: none; }
        
        .glass-effect { background: rgba(255,255,255,0.92); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        
        .icon-btn { width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; cursor: pointer; border: none; background: transparent; transition: all 0.15s ease; }
        .icon-btn:hover { background: #f3f4f6; }
        .icon-btn:active { background: #e5e7eb; transform: scale(0.93); }
        .icon-btn .material-symbols-outlined { font-size: 18px; }
        
        .input-field {
            width: 100%; border: 2px solid #e5e7eb; border-radius: 14px; padding: 14px 16px;
            outline: none; font-size: 16px; font-weight: 500; color: #1f2937;
            transition: all 0.2s ease; background: #fff; text-align: center;
        }
        .input-field:focus { border-color: #00685f; box-shadow: 0 0 0 4px rgba(0,104,95,0.06); }
        
        .stat-item { transition: all 0.15s ease; }
        .stat-item:hover { background: #f9fafb; }
        
        .user-row { transition: all 0.1s ease; }
        .user-row:hover { background: #fafdfc; }
        
        @media print { body { background: white !important; } .no-print { display: none !important; } .print-only { display: block !important; } }
        .print-only { display: none; }
    </style>
</head>
<body class="text-gray-800 antialiased">

    <?php if (!$isAuthenticated): ?>
    <!-- شاشة كلمة المرور -->
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-sm rounded-2xl border border-gray-200 overflow-hidden">
            <div class="border-b border-gray-200 px-4 py-3 text-center">
                <div class="w-8 h-8 bg-gradient-to-br from-primary to-primaryDark rounded-lg flex items-center justify-center text-white font-bold text-xs mx-auto mb-1.5">ل</div>
                <h1 class="text-sm font-bold text-gray-900">لوحة التحكم</h1>
                <p class="text-[9px] text-gray-400">دخول آمن</p>
            </div>
            <div class="p-5 space-y-4">
                <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-full flex items-center justify-center mx-auto">
                    <span class="material-symbols-outlined text-xl">lock</span>
                </div>
                <h2 class="text-base font-bold text-gray-900 text-center">الوصول للوحة التحكم</h2>
                <p class="text-xs text-gray-400 text-center">أدخل كلمة المرور للمتابعة</p>
                <form method="POST" class="space-y-3">
                    <input type="password" name="admin_password" placeholder="كلمة المرور" required autofocus class="input-field tracking-widest">
                    <?php if ($errorMsg): ?>
                        <p class="text-red-500 text-[10px] flex items-center justify-center gap-1 bg-red-50 p-2 rounded-xl">
                            <span class="material-symbols-outlined text-xs">error</span> <?= $errorMsg ?>
                        </p>
                    <?php endif; ?>
                    <button type="submit" class="w-full bg-primary text-white py-3 rounded-xl font-bold text-sm active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined">login</span> دخول
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- المحتوى الرئيسي -->
    <div class="max-w-4xl mx-auto p-3 sm:p-4 space-y-2.5 pb-16">

        <!-- الهيدر -->
        <div class="glass-effect sticky top-0 z-30 rounded-2xl border border-gray-200 px-3 py-2.5 flex items-center justify-between gap-2 no-print">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 bg-gradient-to-br from-primary to-primaryDark rounded-lg flex items-center justify-center text-white font-bold text-xs flex-shrink-0">
                    <span class="material-symbols-outlined text-base">groups</span>
                </div>
                <div>
                    <h1 class="text-sm font-bold text-gray-900">سجل العملاء</h1>
                    <p class="text-[8px] text-gray-400">إدارة العملاء المسجلين</p>
                </div>
            </div>
            <div class="flex items-center gap-0.5">
                <button onclick="printTable()" class="icon-btn text-violet-500 bg-violet-50/50" title="طباعة"><span class="material-symbols-outlined">print</span></button>
                <button onclick="window.location.reload()" class="icon-btn text-blue-500 bg-blue-50/50" title="تحديث الصفحة"><span class="material-symbols-outlined">refresh</span></button>
                <a href="indexx.php" class="icon-btn text-gray-400" title="الرئيسية"><span class="material-symbols-outlined">home</span></a>
                <button onclick="deleteAllUsers()" class="icon-btn text-red-400 bg-red-50/50" title="حذف الكل"><span class="material-symbols-outlined">delete</span></button>
                <a href="?logout=1" class="icon-btn text-amber-500 bg-amber-50/50" title="خروج"><span class="material-symbols-outlined">logout</span></a>
            </div>
        </div>

        <!-- شريط الإحصائيات المصغر -->
        <div class="flex items-center gap-1.5 bg-white rounded-xl border border-gray-200 px-3 py-2 no-print overflow-x-auto">
            <div class="stat-item flex items-center gap-1.5 px-2 py-1 rounded-lg flex-shrink-0">
                <span class="material-symbols-outlined text-sm text-blue-500">people</span>
                <span class="text-[9px] text-gray-400">العملاء</span>
                <span class="text-xs font-bold text-gray-800" id="stat-total">0</span>
            </div>
            <div class="w-px h-5 bg-gray-100 flex-shrink-0"></div>
            <div class="stat-item flex items-center gap-1.5 px-2 py-1 rounded-lg flex-shrink-0">
                <span class="material-symbols-outlined text-sm text-primary">today</span>
                <span class="text-[9px] text-gray-400">اليوم</span>
                <span class="text-xs font-bold text-gray-800" id="stat-today">0</span>
            </div>
            <div class="w-px h-5 bg-gray-100 flex-shrink-0"></div>
            <div class="stat-item flex items-center gap-1.5 px-2 py-1 rounded-lg flex-shrink-0">
                <span class="material-symbols-outlined text-sm text-purple-500">badge</span>
                <span class="text-[9px] text-gray-400">آخر</span>
                <span class="text-[10px] font-bold text-gray-800 truncate max-w-[60px]" id="stat-latest">-</span>
            </div>
            <div class="w-px h-5 bg-gray-100 flex-shrink-0"></div>
            <div class="stat-item flex items-center gap-1.5 px-2 py-1 rounded-lg flex-shrink-0">
                <span class="material-symbols-outlined text-sm text-orange-500">phone_iphone</span>
                <span class="text-[9px] text-gray-400">فريدة</span>
                <span class="text-xs font-bold text-gray-800" id="stat-unique">0</span>
            </div>
            <div class="flex-1"></div>
            <span class="text-[8px] text-gray-300 flex-shrink-0" id="last-update">قبل لحظات</span>
        </div>

        <!-- جدول سطح المكتب -->
        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden hidden md:block no-print">
            <div class="overflow-x-auto">
                <table class="w-full text-right text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/30">
                            <th class="p-2.5 text-[9px] font-bold text-gray-400 uppercase tracking-wider w-8">#</th>
                            <th class="p-2.5 text-[9px] font-bold text-gray-400 uppercase tracking-wider">الاسم</th>
                            <th class="p-2.5 text-[9px] font-bold text-gray-400 uppercase tracking-wider">رقم الجوال</th>
                            <th class="p-2.5 text-[9px] font-bold text-gray-400 uppercase tracking-wider">التاريخ</th>
                            <th class="p-2.5 text-[9px] font-bold text-gray-400 uppercase tracking-wider text-center w-20">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="users-table-body" class="divide-y divide-gray-50"></tbody>
                </table>
            </div>
        </div>

        <!-- بطاقات الجوال -->
        <div class="md:hidden space-y-1.5 no-print" id="users-cards"></div>

        <!-- طباعة -->
        <div class="print-only" id="print-area">
            <div class="text-center mb-4">
                <h2 class="text-lg font-bold">مزاد النخبة - سجل العملاء</h2>
                <p class="text-xs text-gray-500">تاريخ الطباعة: <span id="print-date"></span></p>
            </div>
            <table class="w-full border-collapse border border-gray-300 text-sm">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border border-gray-300 p-2 text-right">#</th>
                        <th class="border border-gray-300 p-2 text-right">الاسم</th>
                        <th class="border border-gray-300 p-2 text-right">رقم الجوال</th>
                        <th class="border border-gray-300 p-2 text-right">تاريخ التسجيل</th>
                    </tr>
                </thead>
                <tbody id="print-table-body"></tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <script>
        <?php if ($isAuthenticated): ?>
        let usersData = [];
        document.addEventListener('DOMContentLoaded', loadUsers);

        async function loadUsers() {
            try {
                const response = await fetch('users_db.txt?' + Date.now());
                usersData = await response.json();
                renderUsers(usersData);
                updateStats(usersData);
                document.getElementById('last-update').innerText = '';
            } catch (e) { usersData = []; renderUsers([]); updateStats([]); }
        }

        function formatPhone(phone) {
            let cleaned = phone.replace(/[^0-9]/g, '');
            if (cleaned.startsWith('0')) cleaned = '966' + cleaned.substring(1);
            if (!cleaned.startsWith('966')) cleaned = '966' + cleaned;
            return cleaned;
        }

        function renderUsers(users) {
            const tbody = document.getElementById('users-table-body');
            const cards = document.getElementById('users-cards');
            const printBody = document.getElementById('print-table-body');
            if (tbody) tbody.innerHTML = '';
            if (cards) cards.innerHTML = '';
            if (printBody) printBody.innerHTML = '';

            if (!users || users.length === 0) {
                const empty = '<div class="text-center py-10 text-gray-400"><span class="material-symbols-outlined text-4xl mb-2 block">people_outline</span><p class="text-xs font-medium">لا يوجد عملاء مسجلين</p><p class="text-[9px] text-gray-300 mt-0.5">سيظهر العملاء هنا بعد التسجيل</p></div>';
                if (tbody) tbody.innerHTML = '<tr><td colspan="5" class="p-10 text-center text-gray-400"><span class="material-symbols-outlined text-3xl mb-2 block">people_outline</span><span class="text-xs">لا يوجد عملاء</span></td></tr>';
                if (cards) cards.innerHTML = empty;
                return;
            }

            const reversed = [...users].reverse();
            reversed.forEach((user, i) => {
                const realIndex = users.length - i;
                const date = user.date ? user.date.split(' ')[0] : '-';
                const time = user.date ? user.date.split(' ')[1]?.substring(0, 5) : '';
                const waNumber = formatPhone(user.phone);

                if (tbody) {
                    tbody.innerHTML += `
                        <tr class="user-row">
                            <td class="p-2.5 text-gray-400 text-[10px] w-8">${realIndex}</td>
                            <td class="p-2.5">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center text-[9px] font-bold flex-shrink-0">${user.name.charAt(0)}</div>
                                    <span class="font-bold text-gray-700 text-[11px]">${user.name}</span>
                                </div>
                            </td>
                            <td class="p-2.5 text-primary font-mono dir-ltr text-right text-[11px]">${user.phone}</td>
                            <td class="p-2.5 text-[10px] text-gray-500">${date} <span class="text-gray-300">${time}</span></td>
                            <td class="p-2.5 w-20">
                                <div class="flex items-center justify-center gap-0.5">
                                    <a href="https://wa.me/${waNumber}" target="_blank" class="icon-btn text-whatsapp bg-green-50/50 w-6 h-6" title="واتساب">
                                        <svg class="w-3 h-3 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654z"/></svg>
                                    </a>
                                    <button onclick="deleteUser('${user.phone}')" class="icon-btn text-red-400 bg-red-50/50 w-6 h-6" title="حذف">
                                        <span class="material-symbols-outlined text-xs">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>`;
                }

                if (cards) {
                    cards.innerHTML += `
                        <div class="user-row bg-white rounded-xl border border-gray-200 p-2.5 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 min-w-0 flex-1">
                                <div class="w-7 h-7 rounded-full bg-primary/10 text-primary flex items-center justify-center text-[9px] font-bold flex-shrink-0">${user.name.charAt(0)}</div>
                                <div class="min-w-0">
                                    <p class="font-bold text-gray-700 text-[11px] truncate">${user.name}</p>
                                    <p class="text-[9px] text-gray-400 font-mono dir-ltr text-right">${user.phone}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <span class="text-[8px] text-gray-300">${date}</span>
                                <a href="https://wa.me/${waNumber}" target="_blank" class="icon-btn text-whatsapp bg-green-50/50 w-6 h-6"><svg class="w-3 h-3 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654z"/></svg></a>
                                <button onclick="deleteUser('${user.phone}')" class="icon-btn text-red-400 bg-red-50/50 w-6 h-6"><span class="material-symbols-outlined text-xs">delete</span></button>
                            </div>
                        </div>`;
                }

                if (printBody) {
                    printBody.innerHTML += `<tr><td class="border border-gray-300 p-1.5 text-xs">${realIndex}</td><td class="border border-gray-300 p-1.5 text-xs">${user.name}</td><td class="border border-gray-300 p-1.5 text-xs font-mono dir-ltr text-right">${user.phone}</td><td class="border border-gray-300 p-1.5 text-xs">${user.date || '-'}</td></tr>`;
                }
            });
        }

        function updateStats(users) {
            const total = users.length;
            const today = new Date().toISOString().split('T')[0];
            const todayUsers = users.filter(u => (u.date || '').startsWith(today)).length;
            const latest = users.length > 0 ? users[users.length - 1].name : '-';
            const uniquePhones = new Set(users.map(u => u.phone)).size;
            document.getElementById('stat-total').innerText = total;
            document.getElementById('stat-today').innerText = todayUsers;
            document.getElementById('stat-latest').innerText = latest;
            document.getElementById('stat-unique').innerText = uniquePhones;
        }

        async function deleteUser(phone) {
            if (!confirm('حذف هذا المستخدم؟')) return;
            const formData = new FormData(); formData.append('action', 'delete_user'); formData.append('phone', phone);
            try { const res = await fetch('aab.php', { method: 'POST', body: formData }); const result = await res.json(); if (result.status === 'success') loadUsers(); } catch (e) { alert('فشل الاتصال'); }
        }

        async function deleteAllUsers() {
            if (!confirm('⚠️ حذف جميع العملاء نهائياً؟')) return;
            const formData = new FormData(); formData.append('action', 'delete_all');
            try { const res = await fetch('aab.php', { method: 'POST', body: formData }); const result = await res.json(); if (result.status === 'success') loadUsers(); } catch (e) { alert('فشل الاتصال'); }
        }

        function printTable() { document.getElementById('print-date').innerText = new Date().toLocaleString('ar-SA'); window.print(); }
        setInterval(loadUsers, 8000);
        <?php endif; ?>
    </script>
</body>
</html>