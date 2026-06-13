<?php
// index.php - صفحة المزايدة الرئيسية (متوافقة مع adminn.php) - نسخة محسّنة بالكامل
session_start();

// =============== التحقق من تسجيل الدخول ===============
$isLoggedIn = isset($_SESSION['user_phone']);
$userName = $isLoggedIn ? $_SESSION['user_name'] : 'زائر';
$userPhone = $isLoggedIn ? $_SESSION['user_phone'] : '';

// =============== جلب بيانات المزاد النشط ===============
$auctionsFile = 'auctions_db.json';
$activeAuction = null;
if (file_exists($auctionsFile)) {
    $auctions = json_decode(file_get_contents($auctionsFile), true) ?: [];
    foreach ($auctions as $auc) {
        if ($auc['status'] === 'active') {
            $activeAuction = $auc;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport"/>
    <title>مزاد النخبة - مباشر</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    
    <script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            colors: {
                "primary": "#00685f",
                "primary-dark": "#004d46",
                "background": "#f7f9fb",
                "surface": "#ffffff",
                "trusted-green": "#dcfce7",
                "trusted-text": "#166534",
            },
            fontFamily: { sans: ["IBM Plex Sans Arabic", "sans-serif"] },
            animation: {
                'fast-fade': 'fadeIn 0.15s ease-out forwards',
                'slide-up': 'slideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                'pop-in': 'popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards',
                'price-bounce': 'priceBounce 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55)',
                'winner-glow': 'winnerGlow 1s ease-in-out infinite',
            },
            keyframes: {
                fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                slideUp: { '0%': { transform: 'translateY(15px)', opacity: '0' }, '100%': { transform: 'translateY(0)', opacity: '1' } },
                popIn: { '0%': { transform: 'scale(0.8)', opacity: '0' }, '100%': { transform: 'scale(1)', opacity: '1' } },
                priceBounce: { '0%': { transform: 'scale(1)' }, '30%': { transform: 'scale(1.2)', color: '#00685f' }, '60%': { transform: 'scale(0.95)' }, '100%': { transform: 'scale(1)' } },
                winnerGlow: { '0%, 100%': { boxShadow: '0 0 20px rgba(0, 104, 95, 0.3)' }, '50%': { boxShadow: '0 0 40px rgba(0, 104, 95, 0.6)' } }
            }
          },
        },
      }
    </script>
    <style>
        body { font-family: 'IBM Plex Sans Arabic', sans-serif; background-color: #f7f9fb; -webkit-tap-highlight-color: transparent; overflow-x: hidden; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        
        /* تحسين التنقل بين الصفحات - SPA سريع */
        .page-container { position: relative; min-height: 100vh; }
        .page-view { 
            position: absolute; 
            top: 0; left: 0; right: 0; 
            opacity: 0; 
            visibility: hidden;
            transition: opacity 0.15s ease, visibility 0.15s ease;
            pointer-events: none;
            min-height: 100vh;
        }
        .page-view.active { 
            opacity: 1; 
            visibility: visible;
            pointer-events: auto;
            position: relative;
        }

        .slider-wrapper { display: flex; transition: transform 0.4s ease-in-out; width: 100%; }
        .slide { min-width: 100%; flex-shrink: 0; position: relative; }

        .bid-chip { transition: all 0.15s; background-color: white; color: #374151; border: 2px solid #e5e7eb; }
        .bid-chip.selected { background-color: #00685f !important; color: white !important; border-color: #00685f !important; transform: scale(1.05); box-shadow: 0 4px 12px rgba(0, 104, 95, 0.4); font-weight: 800; }
        
        .modal-backdrop { background: rgba(0,0,0,0.85); backdrop-filter: blur(4px); }
        
        /* تأثير السعر الديناميكي */
        .price-pop { animation: priceBounce 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55); }
        
        /* تأثير الفائز */
        .winner-card { animation: winnerGlow 1s ease-in-out infinite; }
        
        /* تحسين أداء الانتقال */
        .page-loader-mini {
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
            z-index: 300; pointer-events: none;
        }
    </style>
</head>
<body class="text-gray-800 pb-20 flex flex-col">

<!-- صفحة التحميل المصغرة (تظهر فقط عند الحاجة للبيانات) -->
<div id="page-loader" class="fixed inset-0 z-[200] hidden flex items-center justify-center bg-white/90 backdrop-blur-sm transition-opacity duration-150">
    <div class="flex flex-col items-center gap-3 animate-fast-fade">
        <div class="w-10 h-10 border-3 border-primary border-t-transparent rounded-full animate-spin"></div>
        <p class="text-gray-500 font-medium text-sm">جاري التحميل...</p>
    </div>
</div>

<div class="page-container">
    <!-- ================= الصفحة الرئيسية ================= -->
    <div id="home-page" class="page-view active flex-col w-full">
        <header class="bg-white/90 backdrop-blur-md shadow-sm sticky top-0 z-40 px-4 py-3 flex justify-between items-center border-b border-gray-100">
            <button onclick="location.reload()" class="p-2 rounded-full hover:bg-gray-100 text-gray-600 transition-transform active:rotate-180 duration-300">
                <span class="material-symbols-outlined">refresh</span>
            </button>
            <h1 class="text-xl font-bold text-primary tracking-tight">مزاد النخبة</h1>
            <div class="flex gap-2">
                <a href="https://wa.me/966500000000" target="_blank" class="group relative flex items-center justify-center w-9 h-9 rounded-full bg-[#25D366] text-white shadow-md hover:shadow-lg hover:scale-110 transition-all duration-300" title="تواصل معنا">
                    <svg class="w-5 h-5 fill-current transform group-hover:rotate-12 transition-transform" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                </a>
                <button onclick="openNotifications()" class="p-2 rounded-full hover:bg-gray-100 text-gray-600 relative transition-colors">
                    <span class="material-symbols-outlined">notifications</span>
                    <span id="notif-badge" class="absolute top-2 right-2 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white hidden animate-pulse"></span>
                </button>
            </div>
        </header>

        <main class="flex-grow max-w-xl mx-auto w-full p-4 space-y-6" id="home-main-content">
            <?php if ($activeAuction): ?>
                <!-- المحتوى الديناميكي للمزاد النشط سيتم بناؤه بواسطة JavaScript -->
            <?php else: ?>
                <div class="flex flex-col items-center justify-center h-[60vh] text-center space-y-4 animate-fast-fade">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-2 shadow-inner">
                        <span class="material-symbols-outlined text-5xl text-gray-300">inventory_2</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800">لا توجد مزادات حالياً</h2>
                    <p class="text-gray-500 max-w-xs mx-auto">ترقب العروض القادمة، يتم إضافة مزادات جديدة بشكل دوري.</p>
                    <button onclick="location.reload()" class="mt-4 px-6 py-2 bg-white border border-gray-200 text-gray-600 rounded-full text-sm font-medium hover:bg-gray-50 transition shadow-sm">تحديث الصفحة</button>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- ================= صفحة المزايدة ================= -->
    <div id="bid-page" class="page-view flex-col w-full min-h-screen bg-gray-50">
        <header class="bg-primary text-white shadow-md sticky top-0 z-50 px-4 py-3 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <button onclick="switchToHome()" class="p-1 hover:bg-white/10 rounded-full transition-colors"><span class="material-symbols-outlined">arrow_back</span></button>
                <h1 class="font-bold text-lg">تفاصيل المزايدة</h1>
            </div>
            <div class="flex gap-2">
                <a href="https://wa.me/966500000000" target="_blank" class="flex items-center justify-center w-8 h-8 rounded-full bg-white/20 text-white hover:bg-[#25D366] transition-all" title="دعم واتساب">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                </a>
                <button onclick="shareContent()" class="p-1.5 hover:bg-white/10 rounded-full transition-colors"><span class="material-symbols-outlined">share</span></button>
            </div>
        </header>

        <main class="flex-grow max-w-2xl mx-auto w-full p-4 space-y-4" id="bid-main-content">
            <!-- المحتوى الديناميكي للمزايدة سيتم بناؤه بواسطة JavaScript -->
        </main>
        
        <!-- إشعار الفائز (يظهر عند انتهاء المزاد) -->
        <div id="winner-notification" class="fixed bottom-20 left-0 right-0 z-50 hidden flex justify-center px-4">
            <div class="bg-white rounded-2xl shadow-2xl border-2 border-primary p-4 max-w-sm w-full winner-card animate-slide-up">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-primary rounded-full flex items-center justify-center text-white text-2xl">🏆</div>
                    <div>
                        <p class="text-xs text-gray-500">الفائز بالمزاد</p>
                        <p class="font-bold text-gray-900 text-lg" id="winner-name">---</p>
                        <p class="text-primary font-bold" id="winner-bid">---</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= النوافذ المنبثقة ================= -->

<!-- نافذة تسجيل الدخول الإجباري -->
<div id="login-required-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 modal-backdrop" onclick="closeModal('login-required-modal')"></div>
    <div class="bg-white w-full max-w-sm rounded-2xl p-6 relative z-10 shadow-2xl animate-pop-in text-center">
        <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-4">
            <span class="material-symbols-outlined text-3xl">login</span>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">يجب تسجيل الدخول</h3>
        <p class="text-gray-600 mb-6">للمشاركة في المزايدة يرجى تسجيل الدخول أولاً.</p>
        <a href="aa.php" class="block w-full bg-primary text-white py-3 rounded-xl font-bold mb-3">تسجيل الدخول الآن</a>
        <button onclick="closeModal('login-required-modal')" class="w-full py-3 text-gray-500 font-medium">إغلاق</button>
    </div>
</div>

<!-- ===== نافذة التنبيه الإجبارية ===== -->
<div id="notif-popup" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 modal-backdrop"></div>
    <div class="bg-white w-full max-w-sm rounded-2xl p-6 relative z-10 shadow-2xl animate-pop-in text-center">
        <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
            <span class="material-symbols-outlined text-3xl">campaign</span>
        </div>
        <h3 id="popup-title" class="text-xl font-bold text-gray-900 mb-2">تنبيه جديد</h3>
        <p id="popup-msg" class="text-gray-600 mb-6">محتوى التنبيه...</p>
        
        <div class="flex items-center justify-start gap-2 mb-4">
            <input type="checkbox" id="dont-show-again" class="w-4 h-4 text-primary rounded border-gray-300 focus:ring-primary">
            <label for="dont-show-again" class="text-xs text-gray-500 cursor-pointer">لا تظهر هذه النافذة مرة أخرى</label>
        </div>

        <button onclick="markNotifAsRead()" class="w-full bg-gray-900 text-white py-3 rounded-xl font-bold hover:bg-gray-800 transition">
            حسناً، فهمت
        </button>
    </div>
</div>

<!-- نافذة عرض الصور -->
<div id="lightbox-modal" class="fixed inset-0 z-[90] hidden bg-black/95 flex items-center justify-center p-2">
    <button onclick="closeLightbox()" class="absolute top-4 right-4 text-white p-2 hover:bg-white/10 rounded-full z-50"><span class="material-symbols-outlined text-3xl">close</span></button>
    <button onclick="prevSlide('lightbox')" class="absolute right-4 top-1/2 -translate-y-1/2 text-white p-2 hover:bg-white/10 rounded-full z-50"><span class="material-symbols-outlined text-4xl">chevron_right</span></button>
    <button onclick="nextSlide('lightbox')" class="absolute left-4 top-1/2 -translate-y-1/2 text-white p-2 hover:bg-white/10 rounded-full z-50"><span class="material-symbols-outlined text-4xl">chevron_left</span></button>
    <img id="lightbox-img" src="" class="max-w-full max-h-[90vh] rounded-lg shadow-2xl object-contain">
</div>

<!-- نافذة الملف الشخصي -->
<div id="account-modal" class="fixed inset-0 z-[80] hidden flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="absolute inset-0 modal-backdrop" onclick="closeAccountModal()"></div>
    <div class="bg-white rounded-t-3xl sm:rounded-3xl p-6 w-full max-w-sm relative z-10 animate-slide-up shadow-2xl">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-gray-900">ملفي الشخصي</h3>
            <button onclick="closeAccountModal()" class="p-1 hover:bg-gray-100 rounded-full"><span class="material-symbols-outlined">close</span></button>
        </div>
        <div class="space-y-4 mb-6">
            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl border border-gray-100">
                <span class="material-symbols-outlined text-primary">person</span>
                <div><p class="text-xs text-gray-500">الاسم الكريم</p><p class="font-bold text-gray-800"><?= htmlspecialchars($userName) ?></p></div>
            </div>
            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl border border-gray-100">
                <span class="material-symbols-outlined text-primary">phone</span>
                <div><p class="text-xs text-gray-500">رقم الجوال</p><p class="font-bold text-gray-800 dir-ltr text-right"><?= htmlspecialchars($userPhone) ?></p></div>
            </div>
        </div>
        <div class="space-y-3">
            <a href="https://wa.me/966500000000" target="_blank" class="w-full py-3 bg-green-50 text-green-700 rounded-xl font-bold hover:bg-green-100 transition-colors flex items-center justify-center gap-2 border border-green-200">
                <span class="material-symbols-outlined">support_agent</span> تواصل مع الدعم
            </a>
            <a href="aa.php?action=logout" class="w-full py-3 bg-red-50 text-red-600 rounded-xl font-bold hover:bg-red-100 transition-colors flex items-center justify-center gap-2 border border-red-100">
                <span class="material-symbols-outlined">logout</span> تسجيل الخروج
            </a>
        </div>
    </div>
</div>

<!-- نافذة قائمة التنبيهات -->
<div id="notif-modal" class="fixed inset-0 z-[60] hidden flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="absolute inset-0 modal-backdrop" onclick="closeModal('notif-modal')"></div>
    <div class="bg-white rounded-t-3xl sm:rounded-3xl p-6 w-full max-w-sm relative z-10 animate-slide-up shadow-2xl max-h-[70vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2"><span class="material-symbols-outlined text-primary">campaign</span> أحدث التنبيهات</h3>
            <button onclick="closeModal('notif-modal')" class="p-1 hover:bg-gray-100 rounded-full"><span class="material-symbols-outlined">close</span></button>
        </div>
        <div class="space-y-3" id="notif-list">
            <div class="text-center text-gray-500 py-4 text-sm">جاري تحميل التنبيهات...</div>
        </div>
    </div>
</div>

<!-- نافذة وصف السلعة -->
<div id="desc-modal" class="fixed inset-0 z-[60] hidden flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="absolute inset-0 modal-backdrop" onclick="closeModal('desc-modal')"></div>
    <div class="bg-white rounded-t-3xl sm:rounded-3xl p-6 w-full max-w-sm relative z-10 animate-slide-up shadow-2xl">
        <h3 class="text-xl font-bold mb-4 text-gray-900">وصف السلعة</h3>
        <p class="text-gray-600 leading-relaxed text-sm whitespace-pre-line" id="desc-text"></p>
        <button onclick="closeModal('desc-modal')" class="mt-6 w-full py-3 bg-gray-100 text-gray-800 rounded-xl font-bold">إغلاق</button>
    </div>
</div>

<!-- نافذة تعهد المزايدة -->
<div id="pledge-modal" class="fixed inset-0 z-[70] hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 modal-backdrop" onclick="closeModal('pledge-modal')"></div>
    <div class="bg-white rounded-3xl p-6 w-full max-w-sm relative z-10 animate-slide-up text-center shadow-2xl">
        <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4"><span class="material-symbols-outlined text-3xl">verified_user</span></div>
        <h3 class="text-xl font-bold mb-2 text-gray-900">تعهد بالمزايدة</h3>
        <p class="text-gray-500 text-sm mb-6">أقر بأنني ملتزم بدفع قيمة المزايدة في حال الفوز.</p>
        <button onclick="finalizeBid()" class="w-full bg-primary text-white py-3 rounded-xl font-bold shadow-lg mb-3 flex items-center justify-center gap-2"><span class="material-symbols-outlined">check</span> أوافق وأرسل</button>
        <button onclick="closeModal('pledge-modal')" class="w-full py-3 text-gray-500 font-medium text-sm">إلغاء</button>
    </div>
</div>

<!-- شريط التنقل السفلي -->
<nav class="fixed bottom-0 left-0 w-full bg-white border-t border-gray-200 z-50 pb-safe shadow-[0_-5px_20px_rgba(0,0,0,0.03)]">
    <div class="flex justify-around items-center h-16 max-w-xl mx-auto">
        <button onclick="switchToHome()" id="nav-home" class="nav-btn flex flex-col items-center justify-center w-full h-full text-primary gap-1">
            <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' 1;">home</span>
            <span class="text-[10px] font-medium">الرئيسية</span>
        </button>
        <button onclick="openBidPage()" id="nav-auction" class="nav-btn flex flex-col items-center justify-center w-full h-full text-gray-400 gap-1 transition-colors relative">
            <span class="material-symbols-outlined text-2xl">gavel</span>
            <span class="text-[10px] font-medium">المزاد</span>
            <span id="nav-auction-dot" class="absolute top-1 w-2 h-2 bg-primary rounded-full <?= $activeAuction ? '' : 'hidden' ?>"></span>
        </button>
        
        <?php if($isLoggedIn): ?>
            <button onclick="document.getElementById('account-modal').classList.remove('hidden')" class="nav-btn flex flex-col items-center justify-center w-full h-full text-gray-400 gap-1">
                <div class="w-6 h-6 rounded-full bg-primary text-white flex items-center justify-center text-[10px] font-bold"><?= mb_substr($userName, 0, 1, 'UTF-8') ?></div>
                <span class="text-[10px] font-medium">حسابي</span>
            </button>
        <?php else: ?>
            <a href="aa.php" class="nav-btn flex flex-col items-center justify-center w-full h-full text-gray-400 hover:text-primary gap-1 transition-colors">
                <span class="material-symbols-outlined text-2xl">person</span>
                <span class="text-[10px] font-medium">دخول</span>
            </a>
        <?php endif; ?>
    </div>
</nav>

<!-- =============== كود JavaScript المحسن =============== -->
<script>
    // =============== متغيرات عامة ===============
    const initialData = <?= json_encode($activeAuction) ?>;
    let auctionData = initialData ? { ...initialData, images: Array.isArray(initialData.images) ? initialData.images : [], bids: Array.isArray(initialData.bids) ? initialData.bids : [] } : null;

    let selectedIncrement = 100;
    let currentSlide = 0;
    let slideInterval;
    let viewersCount = Math.floor(Math.random() * (25 - 10 + 1)) + 10;
    let lastNotifTime = 0;
    let currentNotifId = null;
    let pricePopTimeout = null;
    let auctionEnded = false; // متغير لمنع التكرار عند انتهاء المزاد
    let dataCache = {}; // تخزين مؤقت للبيانات
    let lastFetchTime = 0;
    const FETCH_DEBOUNCE = 1500; // مدة منع تكرار الجلب (1.5 ثانية)

    const currentUserDisplayName = "<?= $isLoggedIn ? addslashes($userName) : '' ?>";
    const currentUserPhone = "<?= $isLoggedIn ? addslashes($userPhone) : '' ?>";
    const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;

    // =============== التهيئة ===============
    window.onload = function() {
        if (auctionData) {
            buildHomeContent();
            buildBidContent();
            startSlider();
            updateTimers();
            updatePriceUI(false);
            renderBidsLog();
            checkAuctionStatus();
            updateExpectedPrice();
            updateNavHighlight('home');
        } else {
            buildEmptyHome();
        }
        simulateViewers();
        setInterval(updateTimers, 1000);
        setInterval(fetchFreshData, 2000); // تحديث كل ثانيتين
        setInterval(checkNotifications, 3000);
        document.addEventListener("visibilitychange", () => {
            if (!document.hidden) fetchFreshData(true); // جلب فوري عند العودة
        });
    };

    // =============== بناء واجهة الصفحة الرئيسية ===============
    function buildHomeContent() {
        const container = document.getElementById('home-main-content');
        if (!auctionData) return;
        container.innerHTML = `
            <div class="text-center space-y-2 py-2 animate-fast-fade">
                <span class="inline-block py-1 px-3 rounded-full bg-primary/10 text-primary text-xs font-bold tracking-wide uppercase">مزاد مباشر الآن</span>
                <h2 class="text-3xl font-bold text-gray-900 leading-snug">${auctionData.title}</h2>
            </div>
            <div class="bg-white rounded-[2rem] shadow-xl shadow-gray-200/50 overflow-hidden border border-gray-100 relative group">
                <div class="relative aspect-[4/3] bg-gray-100 overflow-hidden">
                    <div id="home-slider-wrapper" class="slider-wrapper h-full"></div>
                    <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-t from-black/40 to-transparent z-10 cursor-pointer" onclick="openLightbox(currentSlide)">
                        <button class="bg-white/95 backdrop-blur text-gray-900 px-5 py-2.5 rounded-full font-bold text-sm shadow-xl hover:scale-105 transition-all flex items-center gap-2 border border-gray-100">
                            <span class="material-symbols-outlined text-lg">photo_library</span> عرض الصور
                        </button>
                    </div>
                    <button onclick="prevSlide('home')" class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white text-gray-800 p-2 rounded-full shadow-lg transition-all z-20 opacity-0 group-hover:opacity-100">
                        <span class="material-symbols-outlined text-lg">chevron_right</span>
                    </button>
                    <button onclick="nextSlide('home')" class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white text-gray-800 p-2 rounded-full shadow-lg transition-all z-20 opacity-0 group-hover:opacity-100">
                        <span class="material-symbols-outlined text-lg">chevron_left</span>
                    </button>
                    <div class="absolute bottom-4 left-0 right-0 flex justify-center gap-1.5 pointer-events-none" id="home-dots"></div>
                    <div class="absolute top-4 right-4 bg-trusted-green text-trusted-text px-3 py-1.5 rounded-full text-xs font-bold flex items-center gap-1.5 shadow-sm border border-green-200 z-20">
                        <span class="material-symbols-outlined text-sm fill-current">verified_user</span> موثوق
                    </div>
                    <div class="absolute top-4 left-4 bg-black/70 backdrop-blur text-white px-3 py-1.5 rounded-full text-xs font-medium flex items-center gap-2 shadow-lg border border-white/10 z-20">
                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                        <span id="viewers-count-home">${viewersCount}</span> يشاهد الآن
                    </div>
                </div>
                <div class="p-6 space-y-6">
                    <button onclick="openDescription()" class="w-full py-3 bg-gray-50 hover:bg-gray-100 text-gray-700 rounded-xl font-semibold text-sm transition-colors flex items-center justify-center gap-2 border border-gray-200">
                        <span class="material-symbols-outlined text-lg text-gray-400">description</span> عرض تفاصيل ووصف السلعة
                    </button>
                    <div class="bg-primary/5 rounded-2xl p-5 text-center border border-primary/10 relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-1 h-full bg-primary"></div>
                        <p class="text-xs text-primary font-bold mb-2 uppercase tracking-wider">ينتهي المزاد خلال</p>
                        <div class="flex justify-center items-center gap-3 font-mono text-2xl font-bold text-gray-800" id="home-timer"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-6">
                        <div class="border-l border-gray-100 pl-4">
                            <span class="text-xs text-gray-400 block mb-1 font-medium">أعلى مزايدة</span>
                            <span class="text-2xl font-bold text-primary"><span id="home-price">${Number(auctionData.current_price).toLocaleString()}</span> <span class="text-sm font-normal text-gray-500">ر.س</span></span>
                        </div>
                        <div class="pr-2">
                            <span class="text-xs text-gray-400 block mb-1 font-medium">المزايد الحالي</span>
                            <div class="flex items-center gap-3 mt-1">
                                <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-sm font-bold" id="home-bidder-avatar">?</div>
                                <span class="text-base font-bold text-gray-800 truncate" id="home-bidder">لا يوجد</span>
                            </div>
                        </div>
                    </div>
                    <button id="main-action-btn" onclick="openBidPage()" class="w-full bg-primary hover:bg-primary-dark text-white py-4 rounded-xl font-bold text-lg shadow-lg shadow-primary/30 active:scale-[0.98] transition-all flex items-center justify-center gap-2 group">
                        <span class="material-symbols-outlined group-hover:rotate-12 transition-transform">gavel</span> ابدأ المزايدة الآن
                    </button>
                </div>
            </div>
        `;
        renderSlider('home-slider-wrapper', 'home-dots');
    }

    function buildEmptyHome() {
        document.getElementById('home-main-content').innerHTML = `
            <div class="flex flex-col items-center justify-center h-[60vh] text-center space-y-4 animate-fast-fade">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-2 shadow-inner">
                    <span class="material-symbols-outlined text-5xl text-gray-300">inventory_2</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">لا توجد مزادات حالياً</h2>
                <p class="text-gray-500 max-w-xs mx-auto">ترقب العروض القادمة، يتم إضافة مزادات جديدة بشكل دوري.</p>
                <button onclick="location.reload()" class="mt-4 px-6 py-2 bg-white border border-gray-200 text-gray-600 rounded-full text-sm font-medium hover:bg-gray-50 transition shadow-sm">تحديث الصفحة</button>
            </div>`;
    }

    // =============== بناء واجهة صفحة المزايدة ===============
    function buildBidContent() {
        if (!auctionData) return;
        const container = document.getElementById('bid-main-content');
        container.innerHTML = `
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 text-center">
                <h2 id="bid-item-title" class="text-xl font-bold text-gray-900">${auctionData.title}</h2>
            </div>
            <div class="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 group">
                <div class="relative aspect-video bg-gray-100">
                     <div id="bid-slider-wrapper" class="slider-wrapper h-full"></div>
                     <div class="absolute inset-0 flex items-center justify-center bg-black/10 group-hover:bg-black/20 transition-colors z-10 cursor-pointer" onclick="openLightbox(currentSlide)">
                        <button class="bg-white/90 backdrop-blur text-gray-800 px-4 py-2 rounded-full font-bold text-sm shadow-lg hover:scale-105 transition-transform flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">photo_library</span> عرض الصور
                        </button>
                     </div>
                     <button onclick="prevSlide('bid')" class="absolute right-3 top-1/2 -translate-y-1/2 bg-black/40 hover:bg-black/60 text-white p-1.5 rounded-full backdrop-blur-sm transition-all z-20 opacity-0 group-hover:opacity-100">
                        <span class="material-symbols-outlined text-lg">chevron_right</span></button>
                     <button onclick="nextSlide('bid')" class="absolute left-3 top-1/2 -translate-y-1/2 bg-black/40 hover:bg-black/60 text-white p-1.5 rounded-full backdrop-blur-sm transition-all z-20 opacity-0 group-hover:opacity-100">
                        <span class="material-symbols-outlined text-lg">chevron_left</span></button>
                     <div class="absolute bottom-3 left-0 right-0 flex justify-center gap-1.5 pointer-events-none" id="bid-dots"></div>
                     <div class="absolute top-3 right-3 bg-trusted-green text-trusted-text px-3 py-1 rounded-full text-xs font-bold flex items-center gap-1 shadow-sm border border-green-200 z-20">
                        <span class="material-symbols-outlined text-sm fill-current">verified_user</span> موثوق</div>
                     <div class="absolute top-3 left-3 bg-black/60 backdrop-blur text-white px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1.5 shadow-lg border border-white/10 z-20">
                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                        <span id="viewers-count-bid">${viewersCount}</span> يشاهد الآن</div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-center items-center text-center">
                    <span class="text-xs text-gray-500 mb-1">السعر الحالي</span>
                    <div class="text-2xl font-bold text-primary flex items-baseline gap-1">
                        <span id="bid-current-price">${Number(auctionData.current_price).toLocaleString()}</span>
                        <span class="text-xs font-normal text-gray-400">ر.س</span>
                    </div>
                </div>
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-center items-center text-center">
                    <span class="text-xs text-gray-500 mb-1">الوقت المتبقي</span>
                    <div id="bid-timer" class="text-xl font-mono font-bold text-gray-800">00:00:00</div>
                </div>
            </div>
            <button onclick="openDescription()" class="w-full py-3 bg-white hover:bg-gray-50 text-gray-700 rounded-xl font-semibold text-sm transition-colors flex items-center justify-center gap-2 border border-gray-200 shadow-sm">
                <span class="material-symbols-outlined text-lg text-gray-400">description</span> عرض تفاصيل ووصف السلعة
            </button>
            <div id="bidding-controls" class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="text-sm font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-lg">add_circle</span> اختر قيمة الزيادة
                </h3>
                <div class="grid grid-cols-5 gap-2 mb-4" id="bid-chips">
                    <button class="bid-chip h-14 rounded-xl font-bold text-base flex items-center justify-center" onclick="selectAmount(this, 10)">10</button>
                    <button class="bid-chip h-14 rounded-xl font-bold text-base flex items-center justify-center" onclick="selectAmount(this, 50)">50</button>
                    <button class="bid-chip selected h-14 rounded-xl font-bold text-base flex items-center justify-center" onclick="selectAmount(this, 100)">100</button>
                    <button class="bid-chip h-14 rounded-xl font-bold text-base flex items-center justify-center" onclick="selectAmount(this, 150)">150</button>
                    <button class="bid-chip h-14 rounded-xl font-bold text-base flex items-center justify-center" onclick="selectAmount(this, 200)">200</button>
                </div>
                <div class="text-center mb-4 p-2 bg-primary/5 rounded-lg border border-primary/10">
                    <span class="text-xs text-gray-500">المزايدة الجديدة ستكون: </span>
                    <span class="font-mono font-bold text-primary text-lg" id="expected-new-price">0</span>
                    <span class="text-xs text-gray-500"> ر.س</span>
                </div>
                <button id="confirm-bid-btn" onclick="confirmBidAction()" class="w-full bg-primary text-white py-3.5 rounded-xl font-bold shadow-lg shadow-primary/20 active:scale-95 transition-transform flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">check_circle</span> تأكيد وإرسال المزايدة
                </button>
            </div>
            <div id="stopped-message" class="hidden bg-gray-100 p-6 rounded-2xl text-center border border-gray-200">
                <span class="material-symbols-outlined text-4xl text-gray-400 mb-2">block</span>
                <h3 class="font-bold text-gray-700 text-lg">المزاد مغلق</h3>
                <p class="text-gray-500 text-sm">تم إيقاف هذا المزاد أو بيعه.</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-gray-700 flex items-center gap-2">
                        <span class="material-symbols-outlined text-gray-400 text-lg">history</span> سجل المزايدات الحي
                    </h3>
                    <span class="text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full flex items-center gap-1">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span> مباشر
                    </span>
                </div>
                <div id="bids-log" class="divide-y divide-gray-50"></div>
            </div>
        `;
        renderSlider('bid-slider-wrapper', 'bid-dots');
    }

    // =============== جلب البيانات المحدثة بسرعة (محسّن مع debounce) ===============
    async function fetchFreshData(immediate = false) {
        const now = Date.now();
        // منع تكرار الجلب السريع (debounce)
        if (!immediate && (now - lastFetchTime) < FETCH_DEBOUNCE) return;
        lastFetchTime = now;
        
        try {
            // استخدام ذاكرة تخزين مؤقتة لتسريع الاستجابة
            const cacheKey = 'auctions_data';
            const res = await fetch('auctions_db.json?' + now, {
                cache: 'no-store',
                headers: { 'Cache-Control': 'no-cache' }
            });
            
            if (!res.ok) return;
            
            const auctions = await res.json();
            dataCache = auctions; // تحديث الكاش
            
            const activeAuc = auctions.find(a => a.status === 'active');
            
            // لا يوجد مزاد نشط مسبقاً وظهر الآن
            if (!auctionData && activeAuc) {
                auctionData = { ...activeAuc, images: Array.isArray(activeAuc.images) ? activeAuc.images : [], bids: Array.isArray(activeAuc.bids) ? activeAuc.bids : [] };
                auctionEnded = false;
                buildHomeContent();
                buildBidContent();
                startSlider();
                updateTimers();
                updatePriceUI(false);
                renderBidsLog();
                checkAuctionStatus();
                updateExpectedPrice();
                updateNavHighlight('home');
                return;
            }
            
            // المزاد اختفى
            if (auctionData && !activeAuc) {
                if (!auctionEnded) {
                    handleAuctionEnd(); // إعلان الفائز عند الاختفاء
                }
                auctionData = null;
                buildEmptyHome();
                document.getElementById('bid-main-content').innerHTML = '';
                updateNavHighlight('home');
                return;
            }
            
            // تحديث البيانات
            if (auctionData && activeAuc && activeAuc.id === auctionData.id) {
                const bidsChanged = JSON.stringify(activeAuc.bids) !== JSON.stringify(auctionData.bids);
                const priceChanged = activeAuc.current_price !== auctionData.current_price;
                const statusChanged = activeAuc.status !== auctionData.status;
                
                if (bidsChanged || priceChanged || statusChanged) {
                    auctionData = { ...activeAuc, images: auctionData.images, bids: Array.isArray(activeAuc.bids) ? activeAuc.bids : [] };
                    
                    // إذا تغيرت الحالة إلى غير نشط
                    if (statusChanged && activeAuc.status !== 'active') {
                        handleAuctionEnd();
                    }
                    
                    updatePriceUI(priceChanged);
                    renderBidsLog();
                    checkAuctionStatus();
                    updateExpectedPrice();
                }
            }
        } catch(e) {
            console.error('Fetch error:', e);
        }
    }
    
    // =============== معالجة انتهاء المزاد وإعلان الفائز ===============
    function handleAuctionEnd() {
        if (auctionEnded || !auctionData) return;
        auctionEnded = true;
        
        const winner = auctionData.bids?.[0]; // أعلى مزايد (الأول في المصفوفة)
        const notification = document.getElementById('winner-notification');
        
        if (winner && notification) {
            document.getElementById('winner-name').innerText = winner.user;
            document.getElementById('winner-bid').innerText = Number(winner.amount).toLocaleString() + ' ر.س';
            notification.classList.remove('hidden');
            
            // إخفاء الإشعار بعد 8 ثوانٍ
            setTimeout(() => {
                notification.classList.add('hidden');
            }, 8000);
        }
        
        // تعطيل أزرار المزايدة
        checkAuctionStatus();
        
        // العودة للصفحة الرئيسية إذا كنا في صفحة المزايدة
        if (document.getElementById('bid-page').classList.contains('active')) {
            // إبقاء المستخدم في صفحة المزايدة لرؤية النتيجة، لكن مع تعطيل الزر
        }
    }

    function checkAuctionStatus() {
        if (!auctionData) return;
        const btn = document.getElementById('main-action-btn');
        const confirmBtn = document.getElementById('confirm-bid-btn');
        const controls = document.getElementById('bidding-controls');
        const stoppedMsg = document.getElementById('stopped-message');
        const navDot = document.getElementById('nav-auction-dot');
        
        if (auctionData.status !== 'active') {
            if (btn) {
                btn.innerText = auctionData.status === 'sold' ? "تم بيع السلعة" : "تم إيقاف المزاد";
                btn.classList.add(auctionData.status === 'sold' ? 'bg-red-500' : 'bg-gray-400', 'cursor-not-allowed');
                btn.classList.remove('bg-primary', 'hover:bg-primary-dark');
                btn.onclick = null;
            }
            if(controls) controls.classList.add('hidden');
            if(stoppedMsg) stoppedMsg.classList.remove('hidden');
            if(confirmBtn) {
                confirmBtn.disabled = true;
                confirmBtn.classList.add('opacity-50', 'cursor-not-allowed');
                confirmBtn.innerHTML = '<span class="material-symbols-outlined">block</span> المزاد منتهي';
            }
            if(navDot) navDot.classList.add('hidden');
        } else {
            if(controls) controls.classList.remove('hidden');
            if(stoppedMsg) stoppedMsg.classList.add('hidden');
            if(confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                confirmBtn.innerHTML = '<span class="material-symbols-outlined">check_circle</span> تأكيد وإرسال المزايدة';
            }
            if(navDot && auctionData) navDot.classList.remove('hidden');
        }
    }

    function simulateViewers() {
        setInterval(() => {
            viewersCount += Math.floor(Math.random() * 3) - 1;
            if (viewersCount < 8) viewersCount = 8;
            if (viewersCount > 35) viewersCount = 35;
            const homeEl = document.getElementById('viewers-count-home');
            const bidEl = document.getElementById('viewers-count-bid');
            if(homeEl) homeEl.innerText = viewersCount;
            if(bidEl) bidEl.innerText = viewersCount;
        }, 4000);
    }

    // =============== التنبيهات ===============
    async function checkNotifications() {
        try {
            const res = await fetch('latest_notif.json?' + new Date().getTime(), { cache: 'no-store' });
            if(res.ok) {
                const notif = await res.json();
                if (notif && notif.title && notif.time > lastNotifTime) {
                    lastNotifTime = notif.time;
                    currentNotifId = notif.id || 'notif_'+notif.time;
                    document.getElementById('notif-badge').classList.remove('hidden');
                    
                    const dontShow = localStorage.getItem('dontShowNotifUntil');
                    if (!dontShow || parseInt(dontShow) < notif.time) {
                        document.getElementById('popup-title').innerText = notif.title;
                        document.getElementById('popup-msg').innerText = notif.msg;
                        document.getElementById('notif-popup').classList.remove('hidden');
                        document.getElementById('dont-show-again').checked = false;
                    }
                    updateNotifListModal();
                } else if (!notif || !notif.title) {
                    document.getElementById('notif-popup').classList.add('hidden');
                    document.getElementById('notif-badge').classList.add('hidden');
                    currentNotifId = null;
                }
            }
        } catch(e) {}
    }

    function markNotifAsRead() {
        if (document.getElementById('dont-show-again').checked) {
            localStorage.setItem('dontShowNotifUntil', lastNotifTime);
        }
        document.getElementById('notif-popup').classList.add('hidden');
        if (currentNotifId) {
            sessionStorage.setItem('read_notif_'+currentNotifId, '1');
        }
    }

    // =============== الانتقال السريع بين الصفحات (SPA) ===============
    function showLoader() {
        document.getElementById('page-loader').classList.remove('hidden');
    }
    function hideLoader() {
        document.getElementById('page-loader').classList.add('hidden');
    }

    function openBidPage() {
        if (!auctionData) return;
        if (!isLoggedIn) {
            document.getElementById('login-required-modal').classList.remove('hidden');
            return;
        }
        
        // انتقال فوري بدون تحميل (البيانات موجودة مسبقاً)
        const homePage = document.getElementById('home-page');
        const bidPage = document.getElementById('bid-page');
        
        homePage.classList.remove('active');
        bidPage.classList.add('active');
        updateNavHighlight('auction');
        
        // تحديث سريع للبيانات في الخلفية
        fetchFreshData(true);
        
        // تمرير للأعلى
        window.scrollTo({ top: 0, behavior: 'instant' });
    }

    function switchToHome() {
        const bidPage = document.getElementById('bid-page');
        const homePage = document.getElementById('home-page');
        
        bidPage.classList.remove('active');
        homePage.classList.add('active');
        updateNavHighlight('home');
        
        // إخفاء إشعار الفائز إذا كان ظاهراً
        document.getElementById('winner-notification')?.classList.add('hidden');
        
        // تحديث سريع للبيانات
        fetchFreshData(true);
        
        window.scrollTo({ top: 0, behavior: 'instant' });
    }
    
    function updateNavHighlight(page) {
        const homeBtn = document.getElementById('nav-home');
        const auctionBtn = document.getElementById('nav-auction');
        
        if (page === 'home') {
            homeBtn?.classList.add('text-primary');
            homeBtn?.querySelector('.material-symbols-outlined')?.setAttribute('style', "font-variation-settings: 'FILL' 1;");
            auctionBtn?.classList.remove('text-primary');
            auctionBtn?.classList.add('text-gray-400');
            auctionBtn?.querySelector('.material-symbols-outlined')?.setAttribute('style', "font-variation-settings: 'FILL' 0;");
        } else {
            auctionBtn?.classList.add('text-primary');
            auctionBtn?.classList.remove('text-gray-400');
            homeBtn?.classList.remove('text-primary');
            homeBtn?.classList.add('text-gray-400');
            homeBtn?.querySelector('.material-symbols-outlined')?.setAttribute('style', "font-variation-settings: 'FILL' 0;");
            auctionBtn?.querySelector('.material-symbols-outlined')?.setAttribute('style', "font-variation-settings: 'FILL' 1;");
        }
    }

    // =============== السلايدر ===============
    function renderSlider(wrapperId, dotsId) {
        if(!auctionData?.images?.length) return;
        const wrapper = document.getElementById(wrapperId);
        const dotsContainer = document.getElementById(dotsId);
        if(!wrapper || !dotsContainer) return;
        wrapper.innerHTML = auctionData.images.map(src => `<div class="slide h-full"><img src="${src}" class="w-full h-full object-cover" loading="lazy"></div>`).join('');
        dotsContainer.innerHTML = auctionData.images.map((_, idx) => `<div class="w-2 h-2 rounded-full transition-all duration-300 ${idx === 0 ? 'bg-white w-4' : 'bg-white/50'}" id="${dotsId}-dot-${idx}"></div>`).join('');
    }

    function startSlider() {
        if(slideInterval) clearInterval(slideInterval);
        slideInterval = setInterval(() => nextSlide(), 4000);
    }

    function nextSlide(context) {
        if(!auctionData?.images?.length) return;
        currentSlide = (currentSlide + 1) % auctionData.images.length;
        updateSliderPosition();
    }

    function prevSlide(context) {
        if(!auctionData?.images?.length) return;
        currentSlide = (currentSlide - 1 + auctionData.images.length) % auctionData.images.length;
        updateSliderPosition();
    }

    function updateSliderPosition() {
        ['home-slider-wrapper', 'bid-slider-wrapper'].forEach(id => {
            const el = document.getElementById(id);
            if(el) el.style.transform = `translateX(-${currentSlide * 100}%)`;
        });
        const lbImg = document.getElementById('lightbox-img');
        if(lbImg && auctionData.images?.[currentSlide]) lbImg.src = auctionData.images[currentSlide];
        ['home-dots', 'bid-dots'].forEach(prefix => {
            auctionData.images?.forEach((_, idx) => {
                const dot = document.getElementById(`${prefix}-dot-${idx}`);
                if(dot) dot.className = idx === currentSlide ? "w-4 h-2 rounded-full bg-white transition-all duration-300" : "w-2 h-2 rounded-full bg-white/50 transition-all duration-300";
            });
        });
    }

    function openLightbox() {
        if(!auctionData) return;
        updateSliderPosition();
        document.getElementById('lightbox-modal').classList.remove('hidden');
    }
    function closeLightbox() { document.getElementById('lightbox-modal').classList.add('hidden'); }

    // =============== المزايدة ===============
    function selectAmount(btn, amount) {
        document.querySelectorAll('.bid-chip').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        selectedIncrement = amount;
        updateExpectedPrice();
    }

    function updateExpectedPrice() {
        if (!auctionData) return;
        const newPrice = auctionData.current_price + selectedIncrement;
        const el = document.getElementById('expected-new-price');
        if (el) el.innerText = Number(newPrice).toLocaleString();
    }

    function confirmBidAction() {
        if (!isLoggedIn) {
            document.getElementById('login-required-modal').classList.remove('hidden');
            return;
        }
        if (!auctionData || auctionData.status !== 'active') {
            alert('هذا المزاد غير نشط حالياً');
            return;
        }
        document.getElementById('pledge-modal').classList.remove('hidden');
    }

    async function finalizeBid() {
        document.getElementById('pledge-modal').classList.add('hidden');
        const formData = new FormData();
        formData.append('action', 'place_bid');
        formData.append('auction_id', auctionData.id);
        formData.append('amount', selectedIncrement);
        formData.append('user', currentUserDisplayName);
        formData.append('phone', currentUserPhone);
        try {
            const res = await fetch('adminn.php', { method: 'POST', body: formData });
            const result = await res.json();
            if(result.status === 'success') {
                // جلب فوري للبيانات الجديدة
                lastFetchTime = 0; // إعادة تعيين debounce للجلب الفوري
                await fetchFreshData(true);
                
                const btn = document.getElementById('confirm-bid-btn');
                if(btn) {
                    btn.innerHTML = '<span class="material-symbols-outlined">check</span> تمت المزايدة بنجاح';
                    btn.classList.add('!bg-green-600');
                    setTimeout(() => {
                        btn.innerHTML = '<span class="material-symbols-outlined">check_circle</span> تأكيد وإرسال المزايدة';
                        btn.classList.remove('!bg-green-600');
                    }, 2000);
                }
            } else {
                alert(result.message || 'حدث خطأ');
            }
        } catch (e) {
            alert('فشل الاتصال');
        }
    }

    // =============== تحديث السعر بشكل ديناميكي مع تأثير ===============
    function updatePriceUI(animate = true) {
        if(!auctionData) return;
        const formatted = Number(auctionData.current_price).toLocaleString();
        const bidCurrent = document.getElementById('bid-current-price');
        const homePrice = document.getElementById('home-price');
        
        if(bidCurrent && bidCurrent.innerText !== formatted) {
            bidCurrent.innerText = formatted;
            if(animate) {
                bidCurrent.classList.add('price-pop');
                clearTimeout(pricePopTimeout);
                pricePopTimeout = setTimeout(() => bidCurrent.classList.remove('price-pop'), 500);
            }
        } else if(bidCurrent) {
            bidCurrent.innerText = formatted;
        }
        
        if(homePrice && homePrice.innerText !== formatted) {
            homePrice.innerText = formatted;
            if(animate) {
                homePrice.classList.add('price-pop');
                clearTimeout(pricePopTimeout);
                pricePopTimeout = setTimeout(() => homePrice.classList.remove('price-pop'), 500);
            }
        } else if(homePrice) {
            homePrice.innerText = formatted;
        }
        
        const lastBidder = auctionData.bids?.[0];
        const bidderEl = document.getElementById('home-bidder');
        const avatarEl = document.getElementById('home-bidder-avatar');
        if(bidderEl) bidderEl.innerText = lastBidder ? lastBidder.user : "لا يوجد";
        if(avatarEl) avatarEl.innerText = lastBidder ? lastBidder.user.charAt(0) : "؟";
    }

    function renderBidsLog() {
        if(!auctionData?.bids) return;
        const container = document.getElementById('bids-log');
        if(!container) return;
        const top4 = auctionData.bids.slice(0, 4);
        container.innerHTML = top4.map((bid, index) => {
            const isMe = bid.user === currentUserDisplayName;
            const isWinner = index === 0 && auctionData.status !== 'active'; // الفائز هو الأول عند انتهاء المزاد
            return `<div class="px-4 py-3 flex justify-between items-center ${isMe ? 'bg-green-50/70' : ''} ${isWinner ? 'bg-yellow-50/70' : ''}">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full ${isWinner ? 'bg-yellow-400 text-white' : (isMe ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600')} flex items-center justify-center text-sm font-bold">
                        ${isWinner ? '👑' : bid.user.charAt(0)}
                    </div>
                    <div>
                        <p class="text-sm font-bold ${isWinner ? 'text-yellow-700' : (isMe ? 'text-primary' : 'text-gray-800')}">
                            ${bid.user} ${isWinner ? '🏆' : ''}
                        </p>
                        <p class="text-[10px] text-gray-400">${bid.time || ''}</p>
                    </div>
                </div>
                <span class="font-mono font-bold ${isWinner ? 'text-yellow-600' : (isMe ? 'text-green-600' : 'text-primary')}">${Number(bid.amount).toLocaleString()} ر.س</span>
            </div>`;
        }).join('');
        
        if (top4.length === 0) {
            container.innerHTML = '<div class="px-4 py-6 text-center text-gray-400 text-sm">لا توجد مزايدات بعد</div>';
        }
    }

    function updateTimers() {
        if(!auctionData) return;
        const endTime = auctionData.end_time * 1000;
        const distance = endTime - Date.now();
        const bidTimer = document.getElementById('bid-timer');
        const homeTimer = document.getElementById('home-timer');
        
        if (distance <= 0) {
            if(bidTimer) bidTimer.innerText = "00:00:00";
            if(homeTimer) homeTimer.innerHTML = `<span class='text-red-500 font-bold'>انتهى المزاد</span>`;
            
            // تفعيل انتهاء المزاد تلقائياً
            if (auctionData.status === 'active' && !auctionEnded) {
                auctionData.status = 'ended';
                handleAuctionEnd();
                // إجبار جلب جديد للتأكد من الحالة على السيرفر
                fetchFreshData(true);
            }
            return;
        }
        
        const h = Math.floor((distance % 86400000) / 3600000);
        const m = Math.floor((distance % 3600000) / 60000);
        const s = Math.floor((distance % 60000) / 1000);
        const timeString = `${h.toString().padStart(2,'0')}:${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
        
        if(bidTimer) bidTimer.innerText = timeString;
        if(homeTimer) homeTimer.innerHTML = `
            <div class="flex flex-col items-center"><span class="text-3xl">${h}</span><span class="text-[10px]">ساعة</span></div>
            <span class="text-3xl">:</span>
            <div class="flex flex-col items-center"><span class="text-3xl">${m}</span><span class="text-[10px]">دقيقة</span></div>
            <span class="text-3xl">:</span>
            <div class="flex flex-col items-center"><span class="text-3xl text-primary">${s}</span><span class="text-[10px]">ثانية</span></div>`;
    }

    function openDescription() {
        if(auctionData) {
            document.getElementById('desc-text').innerText = auctionData.desc || 'لا يوجد وصف متاح';
            document.getElementById('desc-modal').classList.remove('hidden');
        }
    }

    function shareContent() {
        if (navigator.share && auctionData) {
            navigator.share({ title: auctionData.title, text: `شارك في مزاد ${auctionData.title}`, url: location.href });
        } else if(auctionData) {
            navigator.clipboard?.writeText(location.href).then(() => alert('تم نسخ الرابط!'));
        }
    }

    function openNotifications() {
        document.getElementById('notif-badge').classList.add('hidden');
        updateNotifListModal();
        document.getElementById('notif-modal').classList.remove('hidden');
    }

    async function updateNotifListModal() {
        const container = document.getElementById('notif-list');
        try {
            const res = await fetch('latest_notif.json?' + new Date().getTime(), { cache: 'no-store' });
            if(res.ok) {
                const notif = await res.json();
                if(notif && notif.title) {
                    container.innerHTML = `<div class="flex gap-3 p-3 bg-blue-50 rounded-xl border border-blue-100">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 shrink-0"><span class="material-symbols-outlined">campaign</span></div>
                        <div><h4 class="font-bold text-sm text-gray-800">${notif.title}</h4><p class="text-xs text-gray-500 mt-1">${notif.msg}</p></div></div>`;
                } else {
                    container.innerHTML = '<div class="text-center text-gray-400 py-8">لا توجد تنبيهات</div>';
                }
            }
        } catch(e) {}
    }

    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
    function closeAccountModal() { document.getElementById('account-modal').classList.add('hidden'); }
</script>
</body>
</html>