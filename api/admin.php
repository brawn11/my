<?php

session_start();

// ---------- إعدادات الأمان ----------
define('ADMIN_PASSWORD', 'Aa123'); // كلمة المرور (يمكن تغييرها)

// ---------- تسجيل الخروج ----------
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_authenticated']);
    header('Location: admin.php');
    exit;
}

// ---------- التحقق من كلمة المرور ----------
$errorMsg = '';
if (isset($_POST['admin_password'])) {
    if ($_POST['admin_password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_authenticated'] = true;
        header('Location: admin.php');
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
    <title>لوحة التحكم - العملاء | مزاد النخبة</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <style>
        body { font-family: 'IBM Plex Sans Arabic', sans-serif; background: #f8fafc; }
        .card { transition: all 0.15s ease; }
        .card:hover { background: #f9fafb; }
        .btn { transition: all 0.15s ease; }
        .btn:active { transform: scale(0.96); }
        .btn-whatsapp { background: #25D366; color: white; }
        .btn-whatsapp:hover { background: #1fad52; }
        @media print {
            body { background: white !important; }
            .no-print { display: none !important; }
            .print-only { display: block !important; }
        }
        .print-only { display: none; }
    </style>
</head>
<body class="min-h-screen p-3 md:p-5">

    <?php if (!$isAuthenticated): ?>
    <!-- ===== شاشة إدخال كلمة المرور ===== -->
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-6 w-full max-w-sm text-center">
            <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-3xl">lock</span>
            </div>
            <h2 class="text-xl font-bold text-gray-800 mb-2">دخول آمن</h2>
            <p class="text-xs text-gray-500 mb-5">أدخل كلمة المرور للوصول إلى لوحة التحكم</p>
            
            <form method="POST" class="space-y-3">
                <input type="password" name="admin_password" placeholder="كلمة المرور" required autofocus
                       class="w-full py-3 px-4 rounded-xl text-center border border-gray-200 outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-100 transition-all text-lg tracking-wider">
                
                <?php if ($errorMsg): ?>
                    <p class="text-red-500 text-xs flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-sm">error</span> <?= $errorMsg ?>
                    </p>
                <?php endif; ?>
                
                <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-white py-3 rounded-xl font-semibold transition-all active:scale-95 flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">login</span> دخول
                </button>
            </form>
        </div>
    </div>

    <?php else: ?>
    <!-- ===== المحتوى الرئيسي (بعد التحقق) ===== -->
    <div class="max-w-6xl mx-auto space-y-3">

        <!-- ===== الرأس ===== -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 flex flex-col sm:flex-row justify-between items-center gap-2 no-print">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-2xl text-emerald-600">groups</span>
                <div>
                    <h1 class="text-lg font-bold text-gray-700">سجل العملاء</h1>
                    <p class="text-[10px] text-gray-400">تحديث مباشر</p>
                </div>
            </div>
            <div class="flex items-center gap-1.5 flex-wrap">
                <!-- مجموعة الأزرار مرتبة -->
                <button onclick="printTable()" class="btn bg-violet-100 hover:bg-violet-200 text-violet-700 px-3 py-1.5 rounded-lg font-medium text-xs flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">print</span> طباعة
                </button>
                <button onclick="loadUsers()" class="btn bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-1.5 rounded-lg font-medium text-xs flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">refresh</span> تحديث
                </button>
                <a href="index.php" class="btn bg-gray-100 hover:bg-gray-200 text-gray-600 px-3 py-1.5 rounded-lg font-medium text-xs flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">home</span> الرئيسية
                </a>
                <button onclick="deleteAllUsers()" class="btn bg-red-100 hover:bg-red-200 text-red-600 px-3 py-1.5 rounded-lg font-medium text-xs flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">delete</span> حذف الكل
                </button>
                <a href="?logout=1" class="btn bg-amber-100 hover:bg-amber-200 text-amber-700 px-3 py-1.5 rounded-lg font-medium text-xs flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">logout</span> خروج
                </a>
            </div>
        </div>

        <!-- ===== الإحصائيات ===== -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 no-print">
            <div class="bg-white rounded-lg p-2 shadow-sm border border-gray-200 text-center">
                <span class="material-symbols-outlined text-lg text-blue-500">people</span>
                <p class="text-lg font-bold text-gray-700" id="stat-total">0</p>
                <p class="text-[9px] text-gray-400 uppercase font-bold">إجمالي العملاء</p>
            </div>
            <div class="bg-white rounded-lg p-2 shadow-sm border border-gray-200 text-center">
                <span class="material-symbols-outlined text-lg text-emerald-500">today</span>
                <p class="text-lg font-bold text-gray-700" id="stat-today">0</p>
                <p class="text-[9px] text-gray-400 uppercase font-bold">مسجل اليوم</p>
            </div>
            <div class="bg-white rounded-lg p-2 shadow-sm border border-gray-200 text-center">
                <span class="material-symbols-outlined text-lg text-purple-500">badge</span>
                <p class="text-lg font-bold text-gray-700" id="stat-latest">-</p>
                <p class="text-[9px] text-gray-400 uppercase font-bold">آخر مسجل</p>
            </div>
            <div class="bg-white rounded-lg p-2 shadow-sm border border-gray-200 text-center">
                <span class="material-symbols-outlined text-lg text-orange-500">phone_iphone</span>
                <p class="text-lg font-bold text-gray-700" id="stat-unique">0</p>
                <p class="text-[9px] text-gray-400 uppercase font-bold">أرقام فريدة</p>
            </div>
        </div>

        <!-- ===== عرض الجدول (سطح المكتب) ===== -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hidden md:block no-print">
            <div class="px-3 py-2 border-b border-gray-100 bg-gray-50/70 flex justify-between items-center">
                <span class="font-bold text-gray-600 text-xs flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span> مباشر
                </span>
                <span class="text-[10px] text-gray-400" id="last-update">قبل لحظات</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-right text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="p-2 text-xs font-bold text-gray-500">#</th>
                            <th class="p-2 text-xs font-bold text-gray-500">الاسم</th>
                            <th class="p-2 text-xs font-bold text-gray-500">رقم الجوال</th>
                            <th class="p-2 text-xs font-bold text-gray-500">التاريخ</th>
                            <th class="p-2 text-xs font-bold text-gray-500 text-center">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="users-table-body" class="divide-y divide-gray-50"></tbody>
                </table>
            </div>
        </div>

        <!-- ===== عرض البطاقات (الجوال) ===== -->
        <div class="md:hidden space-y-2 no-print" id="users-cards"></div>

        <!-- ===== للطباعة فقط ===== -->
        <div class="print-only" id="print-area">
            <div class="text-center mb-3">
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
                document.getElementById('last-update').innerText = 'الآن';
            } catch (e) {
                usersData = [];
                renderUsers([]);
                updateStats([]);
            }
        }

        function formatPhoneForWhatsApp(phone) {
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
                const emptyHtml = '<div class="text-center py-6 text-gray-400 text-sm">لا يوجد عملاء مسجلين</div>';
                if (tbody) tbody.innerHTML = `<tr><td colspan="5" class="p-6 text-center text-gray-400 text-sm">لا يوجد عملاء مسجلين</td></tr>`;
                if (cards) cards.innerHTML = emptyHtml;
                if (printBody) printBody.innerHTML = '<tr><td colspan="4" class="p-4 text-center">لا يوجد بيانات</td></tr>';
                return;
            }

            const reversed = [...users].reverse();

            reversed.forEach((user, i) => {
                const realIndex = users.length - i;
                const date = user.date ? user.date.split(' ')[0] : '-';
                const time = user.date ? user.date.split(' ')[1]?.substring(0, 5) : '';
                const waNumber = formatPhoneForWhatsApp(user.phone);
                const waLink = `https://wa.me/${waNumber}`;

                if (tbody) {
                    const row = `
                        <tr class="hover:bg-gray-50/60 transition-colors">
                            <td class="p-2 text-gray-400 text-xs">${realIndex}</td>
                            <td class="p-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-bold">
                                        ${user.name.charAt(0)}
                                    </div>
                                    <span class="font-semibold text-gray-700 text-xs">${user.name}</span>
                                </div>
                            </td>
                            <td class="p-2 text-emerald-700 font-mono dir-ltr text-right text-xs">${user.phone}</td>
                            <td class="p-2 text-xs text-gray-500">
                                <div>${date}</div>
                                <div class="text-gray-400">${time}</div>
                            </td>
                            <td class="p-2 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="${waLink}" target="_blank" class="btn-whatsapp w-7 h-7 rounded-full flex items-center justify-center" title="واتساب">
                                        <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                    </a>
                                    <button onclick="deleteUser('${user.phone}')" class="text-gray-300 hover:text-red-500 p-1 rounded transition" title="حذف">
                                        <span class="material-symbols-outlined text-sm">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>`;
                    tbody.innerHTML += row;
                }

                if (cards) {
                    const card = `
                        <div class="card bg-white rounded-lg p-2.5 shadow-sm border border-gray-200 flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold shrink-0">
                                    ${user.name.charAt(0)}
                                </div>
                                <div>
                                    <p class="font-bold text-gray-700 text-xs">${user.name}</p>
                                    <p class="text-[10px] text-gray-500 dir-ltr text-right font-mono">${user.phone}</p>
                                    <p class="text-[9px] text-gray-400">${user.date || '-'}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <a href="${waLink}" target="_blank" class="btn-whatsapp w-7 h-7 rounded-full flex items-center justify-center shrink-0">
                                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                </a>
                                <button onclick="deleteUser('${user.phone}')" class="text-gray-300 hover:text-red-500 p-1 rounded transition">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                </button>
                            </div>
                        </div>`;
                    cards.innerHTML += card;
                }

                if (printBody) {
                    printBody.innerHTML += `
                        <tr>
                            <td class="border border-gray-300 p-1.5 text-xs">${realIndex}</td>
                            <td class="border border-gray-300 p-1.5 text-xs">${user.name}</td>
                            <td class="border border-gray-300 p-1.5 text-xs font-mono dir-ltr text-right">${user.phone}</td>
                            <td class="border border-gray-300 p-1.5 text-xs">${user.date || '-'}</td>
                        </tr>`;
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
            const formData = new FormData();
            formData.append('action', 'delete_user');
            formData.append('phone', phone);
            try {
                const res = await fetch('aab.php', { method: 'POST', body: formData });
                const result = await res.json();
                if (result.status === 'success') loadUsers();
            } catch (e) {
                alert('فشل الاتصال');
            }
        }

        async function deleteAllUsers() {
            if (!confirm('⚠️ حذف جميع العملاء نهائياً؟')) return;
            const formData = new FormData();
            formData.append('action', 'delete_all');
            try {
                const res = await fetch('aab.php', { method: 'POST', body: formData });
                const result = await res.json();
                if (result.status === 'success') loadUsers();
            } catch (e) {
                alert('فشل الاتصال');
            }
        }

        function printTable() {
            document.getElementById('print-date').innerText = new Date().toLocaleString('ar-SA');
            window.print();
        }

        setInterval(loadUsers, 10000);
        <?php endif; ?>
    </script>
</body>
</html>