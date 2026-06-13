<?php
// index.php - منصة مزاد النخبة v18.0
// نظام فائق السرعة - تحديث 1ms لجميع التحديثات

session_start();
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$isLoggedIn = isset($_SESSION['user_phone']) && !empty($_SESSION['user_phone']);
$userName = $isLoggedIn ? $_SESSION['user_name'] : 'زائر';
$userPhone = $isLoggedIn ? $_SESSION['user_phone'] : '';

if (!isset($_SESSION['client_token'])) {
    $_SESSION['client_token'] = bin2hex(random_bytes(16));
}
$clientToken = $_SESSION['client_token'];

$auctionsFile = 'auctions_db.json';
$initialAuctionData = null;

if (file_exists($auctionsFile)) {
    $auctions = json_decode(file_get_contents($auctionsFile), true);
    if (is_array($auctions)) {
        foreach ($auctions as $auc) {
            if (isset($auc['status']) && $auc['status'] === 'active') {
                $initialAuctionData = $auc;
                break;
            }
        }
    }
}

$safeInitialData = $initialAuctionData ? json_encode($initialAuctionData, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK) : 'null';
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" name="viewport"/>
    <meta name="theme-color" content="#00685f">
    <title>مزاد النخبة | المزاد المباشر</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet"/>

    <script>tailwind.config={theme:{extend:{colors:{primary:"#00685f",whatsapp:"#25D366"}}}};</script>

    <style>
        * { -webkit-tap-highlight-color: transparent; box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'IBM Plex Sans Arabic', sans-serif; background: #f8fafc; overflow: hidden; height: 100dvh; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; user-select: none; pointer-events: none; }

        .app-root { position: relative; width: 100%; height: 100dvh; overflow: hidden; }
        .page-panel { position: absolute; top: 0; left: 0; right: 0; bottom: 0; overflow-y: auto; overflow-x: hidden; -webkit-overflow-scrolling: touch; background: #f8fafc; z-index: 1; opacity: 0; visibility: hidden; }
        .page-panel.show { opacity: 1; visibility: visible; z-index: 2; }
        .page-wrap { width: 100%; max-width: 640px; margin: 0 auto; padding-bottom: 80px; }

        .sticky-header { position: sticky; top: 0; z-index: 40; background: rgba(255,255,255,0.92); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-bottom: 1px solid #e5e7eb; padding: 10px 16px; }
        .icon-circle { width: 38px; height: 38px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; cursor: pointer; border: none; background: transparent; color: #6b7280; text-decoration: none; transition: all 0.15s ease; position: relative; }
        .icon-circle:hover { background: #f3f4f6; }
        .icon-circle:active { background: #e5e7eb; transform: scale(0.95); }
        .icon-circle .material-symbols-outlined { font-size: 22px; }
        .icon-circle.whatsapp { color: #25D366; }

        .slider-box { position: relative; overflow: hidden; background: #111827; }
        .slider-track { display: flex; width: 100%; height: 100%; will-change: transform; }
        .slider-slide { min-width: 100%; flex-shrink: 0; }
        .slider-slide img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .img-blurred { filter: blur(20px); transform: scale(1.1); }
        .img-cover { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; z-index: 10; background: rgba(0,0,0,0.2); transition: background 0.2s ease; }
        .img-cover:active { background: rgba(0,0,0,0.4); }

        .badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; white-space: nowrap; }
        .badge-live { background: rgba(255,255,255,0.9); backdrop-filter: blur(4px); }
        .badge-verified { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }

        .bid-chip { background: white; color: #374151; border: 2px solid #e5e7eb; cursor: pointer; user-select: none; transition: all 0.1s ease; }
        .bid-chip:hover { border-color: #00685f; color: #00685f; }
        .bid-chip:active { transform: scale(0.94); }
        .bid-chip.selected { background: #00685f !important; color: white !important; border-color: #00685f !important; font-weight: 700; transform: scale(1.03); }
        .action-btn { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; cursor: pointer; white-space: nowrap; transition: all 0.15s ease; }
        .action-btn:hover { background: #e5e7eb; }
        .action-btn:active { background: #d1d5db; transform: scale(0.97); }
        .action-btn .material-symbols-outlined { font-size: 15px; }

        .thumb-strip { display: flex; gap: 6px; overflow-x: auto; padding: 8px 12px; scroll-behavior: smooth; }
        .thumb-strip::-webkit-scrollbar { height: 2px; }
        .thumb-strip::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
        .thumb-item { width: 44px; height: 44px; border-radius: 8px; overflow: hidden; flex-shrink: 0; cursor: pointer; border: 2px solid transparent; transition: all 0.2s ease; }
        .thumb-item.active { border-color: #00685f; box-shadow: 0 0 0 2px rgba(0,104,95,0.2); }
        .thumb-item img { width: 100%; height: 100%; object-fit: cover; display: block; }

        .bidder-card { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 14px; background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #bbf7d0; transition: all 0.2s ease; }
        .bidder-avatar { width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.1rem; flex-shrink: 0; background: linear-gradient(135deg, #00685f, #00897b); color: white; }

        .live-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: #22c55e; margin-right: 4px; vertical-align: middle; position: relative; top: -8px; box-shadow: 0 0 6px rgba(34,197,94,0.6); transition: all 0.3s ease; }
        .live-dot.active { animation: livePulse 0.8s ease-in-out infinite; }
        .live-dot.syncing { animation: livePulse 0.3s ease-in-out infinite; }
        @keyframes livePulse { 0%,100% { opacity:1; transform:scale(1); box-shadow:0 0 6px rgba(34,197,94,0.6); } 50% { opacity:0.4; transform:scale(1.6); box-shadow:0 0 12px rgba(34,197,94,0.9); } }

        /* سبينر 5 ثواني */
        .btn-bid-submit { position: relative; overflow: hidden; transition: all 0.4s ease; }
        .btn-bid-submit.locked { pointer-events: none; background: #004d46 !important; }
        .btn-bid-submit .btn-content { display: flex; align-items: center; justify-content: center; gap: 8px; transition: opacity 0.3s ease, transform 0.3s ease; }
        .btn-bid-submit.locked .btn-content { opacity: 0; transform: scale(0.9); }
        .btn-bid-submit .btn-spinner-wrap { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); display: flex; align-items: center; gap: 10px; opacity: 0; transition: opacity 0.3s ease; }
        .btn-bid-submit.locked .btn-spinner-wrap { opacity: 1; }
        .btn-bid-submit .btn-spinner { width: 24px; height: 24px; border: 3px solid rgba(255,255,255,0.2); border-top-color: white; border-radius: 50%; animation: btnSpin 0.7s linear infinite; }
        .btn-bid-submit .btn-countdown { color: white; font-size: 0.9rem; font-weight: 700; letter-spacing: 1px; min-width: 20px; text-align: center; }
        @keyframes btnSpin { to { transform: rotate(360deg); } }

        @keyframes priceFlash { 0% { transform: scale(1); } 40% { transform: scale(1.08); color: #f59e0b; } 100% { transform: scale(1); } }
        .price-flash { animation: priceFlash 0.3s ease-out; }
        @keyframes bidSlideIn { 0% { opacity: 0; transform: translateX(-4px); } 100% { opacity: 1; transform: translateX(0); } }
        .bid-slide-in { animation: bidSlideIn 0.08s ease-out; }

        @keyframes modalPopIn { 0% { opacity: 0; transform: translate(-50%, -50%) scale(0.85); } 70% { transform: translate(-50%, -50%) scale(1.03); } 100% { opacity: 1; transform: translate(-50%, -50%) scale(1); } }
        .auction-ended-toast { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 24px; padding: 28px 24px; text-align: center; z-index: 100; max-width: 340px; width: 90%; border: 1px solid #fee2e2; box-shadow: 0 25px 60px rgba(0,0,0,0.2); animation: modalPopIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .ended-icon-wrap { width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, #fef2f2, #fee2e2); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; position: relative; }
        .ended-icon-in { width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, #fecaca, #fca5a5); display: flex; align-items: center; justify-content: center; }
        .ended-dot { position: absolute; width: 10px; height: 10px; border-radius: 50%; background: #f87171; }
        .ended-dot:nth-child(1) { top: 8px; left: 50%; transform: translateX(-50%); animation: dPulse 1.5s ease-in-out infinite; }
        .ended-dot:nth-child(2) { top: 50%; right: 8px; transform: translateY(-50%); animation: dPulse 1.5s ease-in-out infinite 0.5s; }
        .ended-dot:nth-child(3) { bottom: 8px; left: 50%; transform: translateX(-50%); animation: dPulse 1.5s ease-in-out infinite 1s; }
        .ended-dot:nth-child(4) { top: 50%; left: 8px; transform: translateY(-50%); animation: dPulse 1.5s ease-in-out infinite 0.25s; }
        @keyframes dPulse { 0%,100% { transform: translate(-50%,-50%) scale(1); opacity:0.5; } 50% { transform: translate(-50%,-50%) scale(1.8); opacity:1; } }

        .notif-panel { position: fixed; top: 65px; left: 12px; right: 12px; max-width: 420px; margin: 0 auto; background: white; border-radius: 16px; border: 1px solid #e5e7eb; z-index: 90; max-height: 65vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.08); }
        .bottom-bar { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255,255,255,0.95); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border-top: 1px solid #e5e7eb; z-index: 50; padding-bottom: env(safe-area-inset-bottom); }
        .modal-wrap { position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(2px); z-index: 60; display: flex; align-items: flex-end; justify-content: center; padding: 16px; }
        @media (min-width: 640px) { .modal-wrap { align-items: center; } }
        .lightbox-full { position: fixed; inset: 0; z-index: 80; background: black; display: flex; flex-direction: column; }
        
        /* أنماط النافذة المنبثقة للإشعارات */
        .notif-popup { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 20px; padding: 24px; text-align: center; z-index: 110; max-width: 360px; width: 90%; border: 1px solid #e5e7eb; box-shadow: 0 25px 60px rgba(0,0,0,0.15); animation: modalPopIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .notif-popup-icon { width: 64px; height: 64px; border-radius: 50%; background: linear-gradient(135deg, #fef3c7, #fde68a); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
        .checkbox-container { display: flex; align-items: center; justify-content: center; gap: 8px; margin: 16px 0; cursor: pointer; }
        .checkbox-container input[type="checkbox"] { width: 18px; height: 18px; accent-color: #00685f; }
        .checkbox-label { font-size: 0.8rem; color: #6b7280; user-select: none; }
    </style>
</head>
<body>

<div class="app-root">
    <div class="page-panel show" id="page-home"><div class="page-wrap"><div class="sticky-header"><div class="flex justify-between items-center"><div class="flex items-center gap-2.5"><div class="w-9 h-9 bg-gradient-to-br from-primary to-[#004d46] rounded-xl flex items-center justify-center text-white font-bold text-sm flex-shrink-0">ن</div><div><h1 class="text-base font-bold text-gray-900">مزاد النخبة</h1><p class="text-[9px] text-gray-400">المنصة الرائدة للمزادات</p></div></div><div class="flex items-center gap-1.5"><a href="https://wa.me/966500000000" target="_blank" rel="noopener" class="icon-circle whatsapp"><span class="material-symbols-outlined">chat</span></a><button onclick="Mazad.forceRefresh()" class="icon-circle"><span class="material-symbols-outlined">refresh</span></button><button onclick="Mazad.toggleNotif()" class="icon-circle"><span class="material-symbols-outlined">notifications</span></button></div></div></div><div class="p-3 space-y-3" id="home-render"></div></div></div>
    <div class="page-panel" id="page-bid"><div class="page-wrap"><div class="sticky-header"><div class="flex justify-between items-center"><div class="flex items-center gap-2.5"><button onclick="Mazad.goHome()" class="icon-circle text-gray-700"><span class="material-symbols-outlined">arrow_back</span></button><div class="min-w-0"><h2 class="font-bold text-gray-800 text-sm">صفحة المزايدة</h2><p class="text-[9px] text-gray-400" id="bid-status-h">مباشر الآن</p></div></div><div class="flex items-center gap-1.5"><a href="https://wa.me/966500000000" target="_blank" rel="noopener" class="icon-circle whatsapp"><span class="material-symbols-outlined">chat</span></a><button onclick="Mazad.share()" class="icon-circle text-gray-600"><span class="material-symbols-outlined">share</span></button></div></div></div><div class="p-3 space-y-3" id="bid-render"></div></div></div>
</div>

<div id="notif-panel" class="notif-panel hidden"><div class="p-3 border-b border-gray-100 flex justify-between items-center sticky top-0 bg-white rounded-t-2xl z-10"><h3 class="font-bold text-gray-800 text-sm flex items-center gap-2"><span class="material-symbols-outlined text-primary text-lg">notifications</span> الإشعارات</h3><button onclick="Mazad.closeNotif()" class="p-1.5 hover:bg-gray-100 rounded-full"><span class="material-symbols-outlined text-gray-400">close</span></button></div><div class="p-3 space-y-2" id="notif-list"><div class="text-center py-8 text-gray-400"><span class="material-symbols-outlined text-4xl mb-2 block">notifications_off</span><p class="text-xs">لا توجد إشعارات</p></div></div></div>

<!-- النافذة المنبثقة للإشعارات مع خيار لا تظهر مرة أخرى -->
<div id="modal-notif-popup" class="modal-wrap hidden"><div class="notif-popup" onclick="event.stopPropagation()"><div class="notif-popup-icon"><span class="material-symbols-outlined text-3xl text-amber-600">campaign</span></div><h3 class="text-lg font-bold text-gray-900 mb-2">تنبيه من لوحة التحكم</h3><p class="text-gray-500 text-sm mb-1" id="notif-popup-msg">يوجد تحديث جديد في المزاد</p><p class="text-gray-400 text-xs mb-1" id="notif-popup-time">الآن</p><div class="checkbox-container" onclick="document.getElementById('dont-show-again').click()"><input type="checkbox" id="dont-show-again"><label for="dont-show-again" class="checkbox-label">لا تظهر هذه النافذة مرة أخرى</label></div><button onclick="Mazad.dismissNotifPopup()" class="w-full py-3 bg-primary text-white rounded-xl font-bold text-sm active:scale-[0.98] transition-all mt-2">حسناً</button></div></div>

<div id="modal-login" class="modal-wrap hidden"><div class="bg-white w-full max-w-sm rounded-2xl p-6 relative z-10 text-center border border-gray-100" onclick="event.stopPropagation()"><div class="w-16 h-16 bg-amber-50 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-4"><span class="material-symbols-outlined text-3xl">lock_person</span></div><h3 class="text-lg font-bold text-gray-900 mb-2">تسجيل الدخول مطلوب</h3><p class="text-gray-500 mb-5 text-xs">يجب عليك تسجيل الدخول للمشاركة.</p><a href="aax.php" class="block w-full bg-primary text-white py-3 rounded-xl font-bold text-sm">تسجيل الدخول</a><button onclick="Mazad.closeModal('modal-login')" class="mt-3 text-gray-400 text-xs">لاحقاً</button></div></div>

<div id="modal-ended" class="modal-wrap hidden"><div class="auction-ended-toast" onclick="event.stopPropagation()"><div class="ended-icon-wrap"><div class="ended-dot"></div><div class="ended-dot"></div><div class="ended-dot"></div><div class="ended-dot"></div><div class="ended-icon-in"><span class="material-symbols-outlined text-white text-3xl">timer_off</span></div></div><h3 class="text-xl font-bold text-gray-900 mb-2">انتهى المزاد</h3><p class="text-gray-500 text-sm mb-1">نعتذر، هذا المزاد انتهى</p><p class="text-gray-400 text-xs mb-5">استقبلنا في مزاد جديد قريباً 🎉</p><button onclick="Mazad.closeModal('modal-ended');Mazad.goHome();" class="w-full py-3 bg-gradient-to-r from-gray-100 to-gray-200 text-gray-700 rounded-xl font-bold text-sm active:scale-[0.98] transition-all">العودة للرئيسية</button></div></div>

<div id="modal-confirm" class="modal-wrap hidden"><div class="bg-white w-full max-w-sm rounded-2xl p-6 relative z-10 text-center border border-gray-100" onclick="event.stopPropagation()"><div class="w-14 h-14 bg-green-50 text-green-600 rounded-full flex items-center justify-center mx-auto mb-3"><span class="material-symbols-outlined text-2xl">gavel</span></div><h3 class="text-lg font-bold text-gray-900 mb-1">تأكيد المزايدة</h3><p class="text-gray-500 text-xs mb-1">قيمة المزايدة</p><p class="text-3xl font-bold text-primary font-mono mb-2" id="confirm-val">0</p><p class="text-xs text-gray-400 mb-4">ريال سعودي</p><div class="bg-amber-50 border border-amber-200 p-3 rounded-xl mb-4 text-[10px] text-amber-800"><div class="flex items-start gap-1.5"><span class="material-symbols-outlined text-amber-500 text-sm flex-shrink-0 mt-0.5">info</span><span>بالمزايدة أنت توافق على شروط المنصة.</span></div></div><div class="grid grid-cols-2 gap-2.5"><button onclick="Mazad.closeModal('modal-confirm')" class="py-3 bg-gray-100 text-gray-700 rounded-xl font-bold text-sm">إلغاء</button><button onclick="Mazad.submitBid()" class="py-3 bg-primary text-white rounded-xl font-bold text-sm flex items-center justify-center gap-1.5"><span>تأكيد</span><span class="material-symbols-outlined text-sm">check</span></button></div></div></div>
<div id="modal-desc" class="modal-wrap hidden"><div class="bg-white w-full max-w-sm rounded-2xl p-5 relative z-10 border border-gray-100" onclick="event.stopPropagation()"><div class="flex justify-between items-center mb-4"><h3 class="text-lg font-bold text-gray-900 flex items-center gap-2"><span class="material-symbols-outlined text-primary">description</span> وصف السلعة</h3><button onclick="Mazad.closeModal('modal-desc')" class="p-1.5 hover:bg-gray-100 rounded-full"><span class="material-symbols-outlined text-gray-400">close</span></button></div><div class="max-h-[50vh] overflow-y-auto"><p class="text-gray-600 text-sm leading-relaxed whitespace-pre-line" id="desc-content"></p></div><button onclick="Mazad.closeModal('modal-desc')" class="mt-5 w-full py-3 bg-gray-100 text-gray-800 rounded-xl font-bold text-sm">حسناً</button></div></div>

<div id="lightbox-full" class="lightbox-full hidden"><div class="absolute top-0 left-0 right-0 p-4 flex justify-between items-center z-10 bg-gradient-to-b from-black/70 to-transparent"><button onclick="Mazad.closeLB()" class="text-white p-2.5 bg-white/10 rounded-full"><span class="material-symbols-outlined">close</span></button><span id="lb-counter" class="text-white/90 text-sm bg-black/40 px-3 py-1.5 rounded-full">1/1</span><button onclick="Mazad.share()" class="text-white p-2.5 bg-white/10 rounded-full"><span class="material-symbols-outlined">share</span></button></div><div class="flex-1 flex items-center justify-center p-4"><img id="lb-img" src="" class="max-w-full max-h-[80vh] object-contain" alt="صورة"></div><div class="absolute bottom-24 left-0 right-0 flex justify-center gap-2 z-10" id="lb-dots"></div><div class="absolute bottom-8 left-0 right-0 flex justify-center gap-8 z-10"><button onclick="Mazad.lbNav(1)" class="p-3 bg-white/10 text-white rounded-full"><span class="material-symbols-outlined text-2xl">chevron_right</span></button><button onclick="Mazad.lbNav(-1)" class="p-3 bg-white/10 text-white rounded-full"><span class="material-symbols-outlined text-2xl">chevron_left</span></button></div><div class="absolute bottom-4 left-1/2 -translate-x-1/2 z-10"><button onclick="Mazad.closeLB()" class="px-6 py-2 bg-white/20 text-white rounded-full text-sm">إغلاق</button></div></div>

<nav class="bottom-bar"><div class="flex justify-around items-center h-14 max-w-2xl mx-auto"><button onclick="Mazad.goHome()" id="nav-home" class="flex flex-col items-center justify-center w-full h-full text-primary"><span class="material-symbols-outlined text-xl mb-0.5" style="font-variation-settings:'FILL'1;">home</span><span class="text-[9px] font-bold">الرئيسية</span></button><button onclick="Mazad.enterBid()" id="nav-bid" class="flex flex-col items-center justify-center w-full h-full text-gray-400 relative"><span class="material-symbols-outlined text-xl mb-0.5">gavel</span><span id="live-dot" class="absolute top-1 right-1/3 w-2 h-2 bg-red-500 rounded-full border-2 border-white hidden"></span><span class="text-[9px] font-medium">المزاد</span></button><?php if($isLoggedIn): ?><button onclick="Mazad.toggleAccount()" class="flex flex-col items-center justify-center w-full h-full text-gray-400"><div class="w-7 h-7 rounded-full bg-gradient-to-br from-primary to-[#004d46] text-white flex items-center justify-center text-[10px] font-bold mb-0.5"><?= mb_substr($userName,0,1,'UTF-8') ?></div><span class="text-[9px] font-medium">حسابي</span></button><?php else: ?><a href="aax.php" class="flex flex-col items-center justify-center w-full h-full text-gray-400 no-underline"><span class="material-symbols-outlined text-xl mb-0.5">person</span><span class="text-[9px] font-medium">دخول</span></a><?php endif; ?></div><?php if($isLoggedIn): ?><div id="account-menu" class="hidden absolute bottom-16 left-3 right-3 sm:left-auto sm:right-3 sm:w-64 bg-white rounded-xl border border-gray-200 p-1.5 z-50 shadow-lg"><div class="px-3 py-2.5 border-b border-gray-100 mb-1"><p class="font-bold text-gray-900 text-sm"><?= htmlspecialchars($userName) ?></p><p class="text-[10px] text-gray-400 dir-ltr text-right"><?= htmlspecialchars($userPhone) ?></p></div><a href="https://wa.me/966500000000" target="_blank" rel="noopener" class="flex items-center gap-2.5 px-3 py-2.5 text-green-600 hover:bg-green-50 rounded-lg text-xs font-medium no-underline"><span class="material-symbols-outlined text-base">support_agent</span> تواصل مع الدعم</a><a href="aax.php?action=logout" class="flex items-center gap-2.5 px-3 py-2.5 text-red-600 hover:bg-red-50 rounded-lg text-xs font-medium no-underline"><span class="material-symbols-outlined text-base">logout</span> تسجيل الخروج</a></div><?php endif; ?></nav>

<script>
(function(){
    'use strict';

    const CLIENT_TOKEN = "<?= $clientToken ?>";
    const LOCK_DURATION = 5000;
    const SYNC_INTERVAL = 1; // 1ms للتحديث الفائق السرعة

    // التحقق من تفضيل المستخدم لإظهار النافذة المنبثقة
    const dontShowPopup = localStorage.getItem('dont_show_notif_popup') === 'true';

    const S = {
        auction: <?= $safeInitialData ?>,
        user: { loggedIn: <?= $isLoggedIn?'true':'false' ?>, name:"<?= addslashes($userName) ?>", phone:"<?= addslashes($userPhone) ?>" },
        ui: { slide:0, lbIdx:0, inc:100, fetching:false, lastBids:0, lastPrice:0, lastAucId:null, lastStatus:null, currentPage:'home', bidLocked:false, bidTimer:null, bidSeconds:0, priceVersion:0, domReady:false },
        timers: { clock:null, viewers:null, dataSync:null, pageSync:null },
        viewers: Math.floor(Math.random()*12)+3,
        priceCache: 0, bidCache: 0,
        lastNotifCheck: 0,
        frameCount: 0
    };

    const el = (s,p) => (p||document).querySelector(s);
    const fc = n => Number(n||0).toLocaleString('en-US');
    const sz = s => { const d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; };

    // ===== نظام التحديث الخفي المتقدم =====
    // مخبأ بيانات محلياً للمقارنة السريعة
    let _domCache = { hPrice:'', bPrice:'', hBidder:'', bBidder:'', hAmount:'', bAmount:'', hTimer:'', bTimer:'', bidsHTML:'', viewers:'', bidStatus:'' };
    
    function updateDOMElement(id, value, cacheKey) {
        if (_domCache[cacheKey] === value) return false;
        _domCache[cacheKey] = value;
        const elem = el('#'+id);
        if (elem && elem.textContent !== value) { 
            elem.textContent = value; 
            return true; 
        }
        return false;
    }
    
    function updateDOMHTML(id, value, cacheKey) {
        if (_domCache[cacheKey] === value) return false;
        _domCache[cacheKey] = value;
        const elem = el('#'+id);
        if (elem && elem.innerHTML !== value) { 
            elem.innerHTML = value; 
            return true; 
        }
        return false;
    }
    
    function resetDOMCache() {
        _domCache = { hPrice:'', bPrice:'', hBidder:'', bBidder:'', hAmount:'', bAmount:'', hTimer:'', bTimer:'', bidsHTML:'', viewers:'', bidStatus:'' };
    }

    // ===== النقطة الخضراء =====
    function pulseLiveDot() {
        const dots = document.querySelectorAll('.live-dot');
        dots.forEach(d => { 
            d.classList.remove('active','syncing'); 
            d.classList.add('syncing'); 
        });
        requestAnimationFrame(() => {
            dots.forEach(d => { 
                d.classList.remove('syncing'); 
                d.classList.add('active'); 
            });
        });
    }

    // ===== قفل 5 ثواني إلزامي =====
    function lockBidButton() { 
        if(S.ui.bidLocked) return; 
        S.ui.bidLocked=true; 
        S.ui.bidSeconds=Math.ceil(LOCK_DURATION/1000);
        const btn=el('.btn-bid-submit'); 
        if(!btn) return; 
        btn.classList.add('locked'); 
        const cd=btn.querySelector('.btn-countdown'); 
        if(cd) cd.textContent=S.ui.bidSeconds+'s';
        S.ui.bidTimer=setInterval(()=>{
            S.ui.bidSeconds--; 
            if(cd) cd.textContent=S.ui.bidSeconds>0?S.ui.bidSeconds+'s':''; 
            if(S.ui.bidSeconds<=0) unlockBidButton();
        },1000); 
        setTimeout(()=>{if(S.ui.bidLocked) unlockBidButton();},LOCK_DURATION+300); 
    }
    
    function unlockBidButton() { 
        S.ui.bidLocked=false; 
        if(S.ui.bidTimer){clearInterval(S.ui.bidTimer);S.ui.bidTimer=null;} 
        const btn=el('.btn-bid-submit'); 
        if(btn){btn.classList.remove('locked');const cd=btn.querySelector('.btn-countdown');if(cd)cd.textContent='';} 
    }

    // ===== تأثيرات =====
    function flashPrice(el) { 
        if(!el) return; 
        el.classList.remove('price-flash'); 
        void el.offsetWidth; 
        el.classList.add('price-flash'); 
    }
    
    function animateBids() { 
        const items = document.querySelectorAll('#bids-list > div');
        items.forEach((item,i)=>{ 
            item.classList.remove('bid-slide-in'); 
            void item.offsetWidth; 
            item.style.animationDelay=(i*0.01)+'s'; 
            item.classList.add('bid-slide-in'); 
        }); 
    }

    function showPage(page) {
        if(S.ui.currentPage===page) return; 
        S.ui.currentPage=page;
        const hp=el('#page-home'),bp=el('#page-bid'),nh=el('#nav-home'),nb=el('#nav-bid');
        [hp,bp].forEach(p=>{if(p)p.classList.remove('show');});
        [nh,nb].forEach(n=>{if(n){n.classList.remove('text-primary');n.classList.add('text-gray-400');const i=n.querySelector('.material-symbols-outlined');if(i)i.style.fontVariationSettings="'FILL'0";}});
        if(page==='home'){
            if(hp){hp.classList.add('show');hp.scrollTop=0;}
            if(nh){nh.classList.add('text-primary');nh.classList.remove('text-gray-400');const i=nh.querySelector('.material-symbols-outlined');if(i)i.style.fontVariationSettings="'FILL'1";}
        } else {
            if(bp){bp.classList.add('show');bp.scrollTop=0;}
            if(nb){nb.classList.add('text-primary');nb.classList.remove('text-gray-400');const i=nb.querySelector('.material-symbols-outlined');if(i)i.style.fontVariationSettings="'FILL'1";}
        }
        el('#account-menu')?.classList.add('hidden');
        resetDOMCache();
    }

    function goHome() { showPage('home'); }
    function enterBid() { 
        if(!S.auction){showPage('home');return;} 
        if(S.auction.status!=='active'){openModal('modal-ended');return;} 
        if(!S.user.loggedIn){openModal('modal-login');return;} 
        unlockBidButton(); 
        S.priceCache=S.auction.current_price||0; 
        S.bidCache=S.auction.bids?S.auction.bids.length:0; 
        S.ui.priceVersion=S.auction.price_version||0; 
        renderBidPage(); 
        showPage('bid'); 
    }
    function toggleAccount() { el('#account-menu')?.classList.toggle('hidden'); }
    function forceRefresh() { window.location.reload(); }

    // ===== عرض الصفحات =====
    function renderHome() {
        const c=el('#home-render'); if(!c) return;
        if(!S.auction){ 
            c.innerHTML=`<div class="text-center py-16"><div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300"><span class="material-symbols-outlined text-4xl">inventory_2</span></div><h3 class="text-lg font-bold text-gray-800 mb-1">لا توجد مزادات</h3><p class="text-gray-400 text-xs mb-5">ترقب العروض القادمة</p><button onclick="Mazad.forceRefresh()" class="px-6 py-2.5 bg-white border border-gray-200 text-gray-700 rounded-full text-xs font-bold active:bg-gray-100"><span class="material-symbols-outlined text-xs align-middle">refresh</span> تحديث</button></div>`; 
            el('#live-dot')?.classList.add('hidden'); 
            return; 
        }
        const a=S.auction,live=a.status==='active',lb=a.bids?.[0]||null,imgs=Array.isArray(a.images)?a.images:[];
        S.ui.lastBids=a.bids?.length||0; S.ui.lastPrice=a.current_price||0; S.ui.lastAucId=a.id; S.ui.lastStatus=a.status;
        S.priceCache=a.current_price||0; S.bidCache=a.bids?a.bids.length:0; S.ui.priceVersion=a.price_version||0;
        resetDOMCache();
        const sHTML=imgs.length?imgs.map(s=>`<div class="slider-slide"><img src="${sz(s)}" class="img-blurred" loading="lazy" alt=""></div>`).join(''):`<div class="slider-slide flex items-center justify-center bg-gray-800"><span class="material-symbols-outlined text-4xl text-gray-500">image_not_supported</span></div>`;
        const tHTML=imgs.length>1?`<div class="thumb-strip">${imgs.map((s,i)=>`<div class="thumb-item${i===0?' active':''}" onclick="event.stopPropagation();Mazad.openLB(${i})"><img src="${sz(s)}" loading="lazy" alt=""></div>`).join('')}</div>`:'';
        const bHTML=lb?`<div class="bidder-card"><div class="bidder-avatar">${sz(lb.user).charAt(0)}</div><div class="flex-1 min-w-0"><p class="text-[10px] text-gray-400">المزايد الحالي</p><p class="font-bold text-gray-900 text-sm truncate" id="h-bidder">${sz(lb.user)}</p></div><span class="font-mono font-bold text-primary bg-white px-3 py-1.5 rounded-lg border border-green-200 text-sm" id="h-amount">${fc(lb.amount)} ر.س</span></div>`:`<div class="bidder-card"><div class="bidder-avatar" style="background:#d1d5db;color:#6b7280;">?</div><div class="flex-1 min-w-0"><p class="text-[10px] text-gray-400">المزايد الحالي</p><p class="font-bold text-gray-900 text-sm" id="h-bidder">لا يوجد</p></div></div>`;
        c.innerHTML=`<div class="space-y-3"><div class="bg-white rounded-xl border border-gray-200 overflow-hidden"><div class="slider-box" style="aspect-ratio:4/3;"><div class="slider-track h-full" id="home-track">${sHTML}</div><div class="img-cover" onclick="Mazad.openLB(0)"><span class="material-symbols-outlined text-white text-5xl mb-2">photo_camera</span><span class="text-white text-sm font-bold bg-black/50 px-5 py-2 rounded-full">عرض الصور</span></div><div class="absolute top-3 right-3 flex gap-2"><span class="badge badge-live"><span class="w-1.5 h-1.5 ${live?'bg-green-500':'bg-gray-400'} rounded-full"></span>${live?'مباشر':'منتهي'}</span><span class="badge badge-verified"><span class="material-symbols-outlined text-[12px]">verified_user</span>موثوق</span></div><div class="absolute top-3 left-3 bg-black/50 backdrop-blur text-white px-2.5 py-1 rounded-full text-[10px] flex items-center gap-1.5"><span class="w-1.5 h-1.5 bg-green-400 rounded-full"></span><span id="h-viewers">${S.viewers}</span> يشاهد</div></div>${tHTML}<div class="p-3 sm:p-4 space-y-3"><div class="flex items-start justify-between gap-3"><h2 class="text-base sm:text-lg font-bold text-gray-900 leading-snug flex-1">${sz(a.title)}</h2><div class="flex gap-1.5 flex-shrink-0"><button onclick="Mazad.openDesc()" class="action-btn"><span class="material-symbols-outlined">description</span> وصف</button><button onclick="Mazad.openLB(0)" class="action-btn"><span class="material-symbols-outlined">photo_library</span> صور</button></div></div><div class="grid grid-cols-2 gap-3 bg-gray-50 p-3 rounded-xl border border-gray-100"><div class="border-l border-gray-200 pl-3"><p class="text-[10px] text-gray-400 mb-1">أعلى مزايدة</p><div class="flex items-baseline gap-1"><span class="live-dot active"></span><span class="text-xl sm:text-2xl font-bold text-primary font-mono" id="h-price">${fc(a.current_price)}</span><span class="text-[10px] text-gray-400">ر.س</span></div></div><div class="text-right"><p class="text-[10px] text-gray-400 mb-1">متبقي</p><div class="font-mono text-xl sm:text-2xl font-bold text-gray-800" id="h-timer">--:--:--</div></div></div>${bHTML}<button onclick="Mazad.enterBid()" class="w-full bg-primary text-white py-3 sm:py-3.5 rounded-xl font-bold text-sm sm:text-base active:scale-[0.98] transition-all flex items-center justify-center gap-2"><span class="material-symbols-outlined text-xl">gavel</span> دخول المزاد والمزايدة</button></div></div></div>`;
        if(live){ 
            el('#live-dot')?.classList.remove('hidden'); 
            document.querySelectorAll('.live-dot').forEach(d=>{d.classList.add('active');}); 
        } else el('#live-dot')?.classList.add('hidden');
        
        // بدء التحديث الفوري للعداد
        startTimerUpdate();
    }

    function renderBidPage() {
        const c=el('#bid-render'); if(!c) return;
        if(!S.auction){ c.innerHTML=`<div class="text-center py-16 text-gray-400">لا يوجد مزاد</div>`; return; }
        const a=S.auction,closed=a.status!=='active',lb=a.bids?.[0]||null,imgs=Array.isArray(a.images)?a.images:[];
        S.ui.lastBids=a.bids?.length||0; S.ui.lastPrice=a.current_price||0; S.priceCache=a.current_price||0; S.bidCache=a.bids?a.bids.length:0; S.ui.priceVersion=a.price_version||0;
        resetDOMCache();
        const statusText = closed?(a.status==='sold'?'تم البيع':'انتهى الوقت'):'مباشر الآن';
        updateDOMElement('bid-status-h', statusText, 'bidStatus');
        el('#bid-status-h').className=`text-[9px] ${closed?'text-red-500':'text-green-500'}`;
        const sHTML=imgs.length?imgs.map(s=>`<div class="slider-slide"><img src="${sz(s)}" class="img-blurred" loading="lazy" alt=""></div>`).join(''):`<div class="slider-slide flex items-center justify-center bg-gray-800"><span class="material-symbols-outlined text-4xl text-gray-500">image_not_supported</span></div>`;
        const tHTML=imgs.length>1?`<div class="thumb-strip">${imgs.map((s,i)=>`<div class="thumb-item${i===0?' active':''}" onclick="event.stopPropagation();Mazad.openLB(${i})"><img src="${sz(s)}" loading="lazy" alt=""></div>`).join('')}</div>`:'';
        const bHTML=lb?`<div class="bidder-card"><div class="bidder-avatar">${sz(lb.user).charAt(0)}</div><div class="flex-1 min-w-0"><p class="text-[10px] text-gray-400">المزايد الحالي</p><p class="font-bold text-gray-900 text-sm truncate" id="b-bidder">${sz(lb.user)}</p></div><span class="font-mono font-bold text-primary bg-white px-3 py-1.5 rounded-lg border border-green-200 text-sm" id="b-amount">${fc(lb.amount)} ر.س</span></div>`:`<div class="bidder-card"><div class="bidder-avatar" style="background:#d1d5db;color:#6b7280;">?</div><div class="flex-1 min-w-0"><p class="text-[10px] text-gray-400">المزايد الحالي</p><p class="font-bold text-gray-900 text-sm" id="b-bidder">لا يوجد</p></div></div>`;
        let ctrl='';
        if(closed){ 
            ctrl=`<div class="bg-gray-100 p-5 rounded-xl text-center border border-gray-200"><span class="material-symbols-outlined text-4xl text-gray-400 mb-2 block">block</span><h3 class="font-bold text-gray-700">المزاد مغلق</h3><p class="text-xs text-gray-500 mt-1">${a.status==='sold'?'تم بيع السلعة للفائز':'انتهى وقت المزاد'}</p>${(lb&&a.status==='sold')?`<div class="mt-4 bg-white p-4 rounded-xl border border-green-200"><p class="text-xs text-gray-400">الفائز</p><p class="font-bold text-lg text-primary">${sz(lb.user)} 🏆</p><p class="font-mono font-bold text-base mt-1">${fc(lb.amount)} ر.س</p></div>`:''}</div>`; 
        } else { 
            ctrl=`<div class="bg-white p-4 rounded-xl border border-gray-200"><h3 class="font-bold text-gray-800 text-sm mb-3">اختر قيمة الزيادة</h3><div class="grid grid-cols-5 gap-2 mb-4">${[10,50,100,200,500].map(v=>`<button onclick="Mazad.setInc(${v},this)" class="bid-chip h-12 rounded-xl font-bold text-xs flex items-center justify-center${v===S.ui.inc?' selected':''}">+${v}</button>`).join('')}</div><div class="bg-gradient-to-r from-primary/5 to-primary/10 rounded-xl p-3 mb-4 text-center border border-primary/10"><p class="text-[10px] text-gray-400 mb-1">قيمة مزايدتك</p><p class="text-3xl font-bold text-primary font-mono" id="b-projected">${fc((a.current_price||0)+S.ui.inc)}</p><p class="text-[10px] text-gray-400 mt-1">ريال سعودي</p></div><button onclick="Mazad.showConfirm()" class="btn-bid-submit w-full bg-primary text-white py-3.5 rounded-xl font-bold text-sm active:scale-[0.98] transition-all relative"><span class="btn-content flex items-center justify-center gap-2"><span class="material-symbols-outlined text-lg">check_circle</span> تأكيد وإرسال المزايدة</span><span class="btn-spinner-wrap"><span class="btn-spinner"></span><span class="btn-countdown"></span></span></button></div>`; 
        }
        c.innerHTML=`<div class="bg-white rounded-xl border border-gray-200 overflow-hidden"><div class="slider-box" style="aspect-ratio:4/3;"><div class="slider-track h-full" id="bid-track">${sHTML}</div><div class="img-cover" onclick="Mazad.openLB(0)"><span class="material-symbols-outlined text-white text-5xl mb-2">photo_camera</span><span class="text-white text-sm font-bold bg-black/50 px-5 py-2 rounded-full">عرض الصور</span></div></div>${tHTML}</div><div class="bg-white p-4 rounded-xl border border-gray-200"><div class="flex items-start justify-between gap-3 mb-3"><h1 class="text-base sm:text-lg font-bold text-gray-900 leading-snug flex-1">${sz(a.title)}</h1><div class="flex gap-1.5 flex-shrink-0"><button onclick="Mazad.openDesc()" class="action-btn"><span class="material-symbols-outlined">description</span> وصف</button><button onclick="Mazad.openLB(0)" class="action-btn"><span class="material-symbols-outlined">photo_library</span> صور</button></div></div><div class="grid grid-cols-2 gap-3 bg-gray-50 p-3 rounded-xl border border-gray-100 mb-3"><div class="border-l border-gray-200 pl-3"><p class="text-[10px] text-gray-400 mb-1">السعر الحالي</p><div class="flex items-baseline gap-1"><span class="live-dot active"></span><span class="text-xl sm:text-2xl font-bold text-primary font-mono" id="b-price">${fc(a.current_price)}</span><span class="text-[10px] text-gray-400">ر.س</span></div></div><div class="text-right"><p class="text-[10px] text-gray-400 mb-1">متبقي</p><div class="font-mono text-xl sm:text-2xl font-bold text-gray-800" id="b-timer">--:--:--</div></div></div>${bHTML}</div>${ctrl}<div class="bg-white rounded-xl border border-gray-200 overflow-hidden"><div class="px-4 py-3 border-b border-gray-100 bg-gray-50 flex justify-between items-center"><h3 class="text-xs font-bold text-gray-700 flex items-center gap-2"><span class="material-symbols-outlined text-gray-400 text-base">history</span> آخر المزايدين</h3><span class="text-[9px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full">مباشر</span></div><div class="divide-y divide-gray-50 max-h-60 overflow-y-auto" id="bids-list">${renderBids(a.bids||[])}</div></div>`;
        if(!closed) document.querySelectorAll('.live-dot').forEach(d=>d.classList.add('active'));
        
        // بدء التحديث الفوري للعداد
        startTimerUpdate();
    }

    function renderBids(bids) { 
        if(!bids?.length) return '<div class="p-6 text-center text-gray-400 text-xs">كن أول من يزايد!</div>'; 
        return bids.slice(0,4).map((b,i)=>{ 
            const first=i===0,me=S.user.loggedIn&&b.user===S.user.name; 
            let ac='bg-gray-100 text-gray-600'; 
            if(first) ac='bg-gradient-to-br from-yellow-400 to-amber-500 text-white'; 
            else if(me) ac='bg-primary/10 text-primary'; 
            return `<div class="px-4 py-3 flex justify-between items-center${me?' bg-green-50/50':''}"><div class="flex items-center gap-3 min-w-0"><div class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center text-[10px] font-bold ${ac}">${first?'👑':sz(b.user).charAt(0)}</div><div class="min-w-0"><p class="text-xs font-bold truncate${first?' text-yellow-700':me?' text-primary':' text-gray-800'}">${sz(b.user)}${me?' <span class="text-[8px] bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full mr-1">أنت</span>':''}</p><p class="text-[9px] text-gray-400">${b.time||'الآن'}</p></div></div><span class="font-mono font-bold flex-shrink-0 text-xs${first?' text-primary':' text-gray-600'}">${fc(b.amount)} ر.س</span></div>`; 
        }).join(''); 
    }

    // ===== نظام التحديث الفائق السرعة 1ms =====
    
    // بدء تحديث المؤقت
    function startTimerUpdate() {
        if(S.timers.clock) clearInterval(S.timers.clock);
        S.timers.clock = setInterval(() => {
            if(!S.auction || !S.auction.end_time) return;
            const now = Math.floor(Date.now() / 1000);
            const diff = S.auction.end_time - now;
            const exp = diff <= 0;
            const ts = exp ? 'انتهى المزاد' : `${String(Math.floor(diff/3600)).padStart(2,'0')}:${String(Math.floor((diff%3600)/60)).padStart(2,'0')}:${String(diff%60).padStart(2,'0')}`;
            
            // تحديث مباشر للعناصر بدون إعادة رسم
            updateDOMElement('h-timer', ts, 'hTimer');
            updateDOMElement('b-timer', ts, 'bTimer');
            
            if(exp) {
                const ht = el('#h-timer');
                if(ht && !ht.classList.contains('text-red-500')) ht.classList.add('text-red-500');
                const bt = el('#b-timer');
                if(bt && !bt.classList.contains('text-red-500')) bt.classList.add('text-red-500');
            }
            
            S.frameCount++;
        }, SYNC_INTERVAL);
    }
    
    // جلب البيانات بسرعة فائقة
    async function fastDataSync() {
        if(S.ui.fetching) return;
        S.ui.fetching = true;
        try {
            const r = await fetch('auctions_db.json?t=' + Date.now(), {
                cache: 'no-store',
                headers: { 'Cache-Control': 'no-cache' }
            });
            if(!r.ok) throw new Error('N');
            const data = await r.json();
            const active = Array.isArray(data) ? data.find(a => a.status === 'active') : null;
            const prevId = S.ui.lastAucId;
            const newId = active?.id || null;
            
            if(active) {
                const aucCh = prevId !== newId;
                const prCh = S.ui.lastPrice !== active.current_price;
                const bdCh = S.ui.lastBids !== (active.bids?.length || 0);
                const stCh = S.ui.lastStatus !== active.status;
                
                S.auction = active;
                S.ui.lastAucId = newId;
                S.ui.lastPrice = active.current_price || 0;
                S.ui.lastBids = active.bids?.length || 0;
                S.ui.lastStatus = active.status;
                
                const ld = el('#live-dot');
                if(ld) {
                    if(active.status === 'active') ld.classList.remove('hidden');
                    else ld.classList.add('hidden');
                }
                
                if(aucCh || stCh) {
                    // تحديث كامل
                    renderHome();
                    if(S.ui.currentPage === 'bid') {
                        if(active.status === 'active') {
                            renderBidPage();
                            if(S.ui.bidLocked) unlockBidButton();
                        } else {
                            openModal('modal-ended');
                            showPage('home');
                        }
                    }
                } else if(prCh || bdCh) {
                    // تحديث جزئي فائق السرعة
                    const np = active.current_price || 0;
                    const nb = active.bids ? active.bids.length : 0;
                    const pv = active.price_version || 0;
                    
                    if(np !== S.priceCache || pv !== S.ui.priceVersion) {
                        S.priceCache = np;
                        S.ui.priceVersion = pv;
                        pulseLiveDot();
                        const npStr = fc(np);
                        if(updateDOMElement('h-price', npStr, 'hPrice')) flashPrice(el('#h-price'));
                        if(updateDOMElement('b-price', npStr, 'bPrice')) flashPrice(el('#b-price'));
                        const bpr = el('#b-projected');
                        if(bpr) {
                            const pvStr = fc(np + S.ui.inc);
                            if(bpr.textContent !== pvStr) bpr.textContent = pvStr;
                        }
                    }
                    
                    if(nb !== S.bidCache) {
                        S.bidCache = nb;
                        const lb = active.bids?.[0];
                        updateDOMElement('h-bidder', lb ? sz(lb.user) : 'لا يوجد', 'hBidder');
                        updateDOMElement('b-bidder', lb ? sz(lb.user) : 'لا يوجد', 'bBidder');
                        updateDOMElement('h-amount', lb ? fc(lb.amount) + ' ر.س' : '', 'hAmount');
                        updateDOMElement('b-amount', lb ? fc(lb.amount) + ' ر.س' : '', 'bAmount');
                        const bl = el('#bids-list');
                        if(bl) {
                            const nh = renderBids(active.bids || []);
                            if(updateDOMHTML('bids-list', nh, 'bidsHTML')) animateBids();
                        }
                    }
                    
                    updateDOMElement('h-viewers', String(S.viewers), 'viewers');
                    
                    if(prCh && S.ui.bidLocked) unlockBidButton();
                }
                
                // التحقق من وجود إشعارات جديدة من لوحة التحكم
                checkAdminNotifications(active);
            } else {
                if(S.auction) {
                    S.auction = null;
                    S.ui.lastAucId = null;
                    S.ui.lastStatus = null;
                    renderHome();
                    if(S.ui.currentPage === 'bid') {
                        openModal('modal-ended');
                        showPage('home');
                    }
                }
                el('#live-dot')?.classList.add('hidden');
            }
        } catch(e) {
            // تجاهل الأخطاء في التحديث السريع
        } finally {
            S.ui.fetching = false;
        }
    }
    
    // التحقق من إشعارات لوحة التحكم
    function checkAdminNotifications(auction) {
        if(!auction || !auction.admin_notifications) return;
        const notifs = auction.admin_notifications;
        if(!Array.isArray(notifs) || notifs.length === 0) return;
        
        const lastNotif = notifs[notifs.length - 1];
        if(lastNotif.timestamp > S.lastNotifCheck) {
            S.lastNotifCheck = lastNotif.timestamp;
            
            // إظهار النافذة المنبثقة إذا لم يختر المستخدم عدم الظهور
            if(!dontShowPopup && !el('#modal-notif-popup')?.classList.contains('hidden') === false) {
                showNotifPopup(lastNotif);
            }
            
            // تحديث قائمة الإشعارات
            updateNotifList(notifs);
        }
    }
    
    function showNotifPopup(notif) {
        const msg = el('#notif-popup-msg');
        const time = el('#notif-popup-time');
        if(msg) msg.textContent = notif.message || 'يوجد تحديث جديد في المزاد';
        if(time) time.textContent = notif.time || 'الآن';
        openModal('modal-notif-popup');
    }
    
    function dismissNotifPopup() {
        const checkbox = el('#dont-show-again');
        if(checkbox && checkbox.checked) {
            localStorage.setItem('dont_show_notif_popup', 'true');
        }
        closeModal('modal-notif-popup');
    }
    
    function updateNotifList(notifs) {
        const nl = el('#notif-list');
        if(!nl) return;
        const items = notifs.slice(-5).map(n => 
            `<div class="flex items-start gap-3 p-3 bg-amber-50 rounded-xl border border-amber-100">
                <span class="material-symbols-outlined text-amber-600 text-lg mt-0.5 flex-shrink-0">campaign</span>
                <div>
                    <p class="text-xs font-bold text-gray-800">${sz(n.message || 'إشعار جديد')}</p>
                    <p class="text-[9px] text-gray-400 mt-1">${n.time || 'الآن'}</p>
                </div>
            </div>`
        ).join('');
        nl.innerHTML = items || '<div class="text-center py-8 text-gray-400"><span class="material-symbols-outlined text-4xl mb-2 block">notifications_off</span><p class="text-xs">لا توجد إشعارات</p></div>';
    }

    // ===== مزايدة =====
    function setInc(v,btn){
        S.ui.inc=v;
        document.querySelectorAll('.bid-chip').forEach(b=>b.classList.remove('selected'));
        if(btn)btn.classList.add('selected');
        const p=el('#b-projected');
        if(p&&S.auction)p.textContent=fc((S.auction.current_price||0)+v);
    }
    
    function showConfirm(){
        if(!S.auction||S.auction.status!=='active'){
            if(S.auction&&S.auction.status!=='active')openModal('modal-ended');
            return;
        }
        if(S.ui.bidLocked)return;
        const ca=el('#confirm-val');
        if(ca)ca.textContent=fc((S.auction.current_price||0)+S.ui.inc);
        openModal('modal-confirm');
    }
    
    async function submitBid(){
        if(!S.auction?.id||S.ui.bidLocked)return;
        if(S.auction.status!=='active'){closeModal('modal-confirm');openModal('modal-ended');return;}
        closeModal('modal-confirm');
        lockBidButton();
        const fd=new FormData();
        fd.append('action','place_bid');
        fd.append('auction_id',S.auction.id);
        fd.append('amount',S.ui.inc);
        fd.append('user',S.user.name);
        fd.append('phone',S.user.phone);
        fd.append('client_token',CLIENT_TOKEN);
        try{
            const r=await fetch('adminn.php',{
                method:'POST',
                body:fd,
                cache:'no-store',
                headers:{'X-Client-Token':CLIENT_TOKEN}
            });
            const d=await r.json();
            if(d.status==='success'){
                await fastDataSync();
                if(S.ui.currentPage==='bid')renderBidPage();
            } else {
                unlockBidButton();
                alert(d.message||'حدث خطأ');
            }
        }catch(e){
            unlockBidButton();
            alert('فشل الاتصال');
        }
    }

    function simViewers(){
        if(S.timers.viewers)clearInterval(S.timers.viewers);
        S.timers.viewers=setInterval(()=>{
            S.viewers+=Math.random()>0.5?1:-1;
            S.viewers=Math.max(1,Math.min(25,S.viewers));
        },2000);
    }
    
    function toggleNotif(){
        const p=el('#notif-panel');
        if(!p)return;
        p.classList.toggle('hidden');
    }
    
    function closeNotif(){
        el('#notif-panel')?.classList.add('hidden');
    }

    function openLB(i=0){
        if(!S.auction?.images?.length)return;
        S.ui.lbIdx=i;
        updateLB();
        const lb=el('#lightbox-full');
        if(lb){lb.classList.remove('hidden');document.body.style.overflow='hidden';}
    }
    
    function closeLB(){
        el('#lightbox-full')?.classList.add('hidden');
        document.body.style.overflow='';
    }
    
    function lbNav(d){
        const imgs=S.auction?.images;
        if(!imgs?.length)return;
        S.ui.lbIdx=(S.ui.lbIdx+d+imgs.length)%imgs.length;
        updateLB();
    }
    
    function updateLB(){
        const imgs=S.auction?.images;
        if(!imgs?.length)return;
        const img=el('#lb-img');
        if(img)img.src=imgs[S.ui.lbIdx];
        const c=el('#lb-counter');
        if(c)c.textContent=`${S.ui.lbIdx+1}/${imgs.length}`;
        const dots=el('#lb-dots');
        if(dots)dots.innerHTML=imgs.map((_,i)=>`<div class="w-1.5 h-1.5 rounded-full${i===S.ui.lbIdx?' bg-white w-3':' bg-white/40'}"></div>`).join('');
    }

    function openModal(id){
        const m=el('#'+id);
        if(!m)return;
        m.classList.remove('hidden');
        m.onclick=function(e){if(e.target===m)closeModal(id);};
    }
    
    function closeModal(id){
        el('#'+id)?.classList.add('hidden');
    }
    
    function openDesc(){
        if(S.auction){
            const dt=el('#desc-content');
            if(dt)dt.textContent=S.auction.desc||'لا يوجد وصف';
            openModal('modal-desc');
        }
    }
    
    function share(){
        if(!S.auction)return;
        const sd={
            title:S.auction.title||'مزاد',
            text:`شاهد مزاد ${S.auction.title||''}`,
            url:location.href
        };
        if(navigator.share)navigator.share(sd).catch(()=>{});
        else if(navigator.clipboard)navigator.clipboard.writeText(location.href).then(()=>alert('تم نسخ الرابط'));
    }

    function init(){
        renderHome(); 
        simViewers();
        
        // نظام التحديث الفائق السرعة 1ms
        S.timers.clock = setInterval(() => {
            if(S.auction && S.auction.end_time) {
                const diff = S.auction.end_time - Math.floor(Date.now()/1000);
                const exp = diff <= 0;
                const ts = exp ? 'انتهى المزاد' : `${String(Math.floor(diff/3600)).padStart(2,'0')}:${String(Math.floor((diff%3600)/60)).padStart(2,'0')}:${String(diff%60).padStart(2,'0')}`;
                
                updateDOMElement('h-timer', ts, 'hTimer');
                updateDOMElement('b-timer', ts, 'bTimer');
                
                if(exp) {
                    const ht = el('#h-timer');
                    if(ht && !ht.classList.contains('text-red-500')) ht.classList.add('text-red-500');
                    const bt = el('#b-timer');
                    if(bt && !bt.classList.contains('text-red-500')) bt.classList.add('text-red-500');
                }
            }
            S.frameCount++;
        }, SYNC_INTERVAL);
        
        // جلب البيانات كل 1ms
        S.timers.dataSync = setInterval(() => fastDataSync(), SYNC_INTERVAL);
        
        document.addEventListener('click',e=>{
            if(!e.target.closest('#account-menu')&&!e.target.closest('[onclick*="toggleAccount"]')){
                el('#account-menu')?.classList.add('hidden');
            }
            if(!e.target.closest('#notif-panel')&&!e.target.closest('[onclick*="toggleNotif"]')){
                el('#notif-panel')?.classList.add('hidden');
            }
        });
        
        let tsx=0;
        document.addEventListener('touchstart',e=>{
            if(!el('#lightbox-full')?.classList.contains('hidden'))
                tsx=e.touches[0].clientX;
        },{passive:true});
        
        document.addEventListener('touchend',e=>{
            if(!el('#lightbox-full')?.classList.contains('hidden')){
                const d=tsx-e.changedTouches[0].clientX;
                if(Math.abs(d)>40)lbNav(d>0?1:-1);
            }
        });
        
        document.addEventListener('keydown',e=>{
            if(!el('#lightbox-full')?.classList.contains('hidden')){
                if(e.key==='ArrowRight')lbNav(1);
                if(e.key==='ArrowLeft')lbNav(-1);
                if(e.key==='Escape')closeLB();
            }
        });
    }

    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
    window.Mazad={
        goHome,enterBid,toggleAccount,forceRefresh,setInc,showConfirm,submitBid,
        refresh:fastDataSync,openLB,closeLB,lbNav,openDesc,share,
        toggleNotif,closeNotif,openModal,closeModal,dismissNotifPopup
    };
})();
</script>
</body>
</html>
