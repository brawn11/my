// m.js - مزاد النخبة - المحرك الاحترافي الكامل (تحديث حي، تخزين مؤقت، SPA)
(function(){
    'use strict';

    // ==================== الثوابت والمتغيرات ====================
    const initialData = window._AUCTION_DATA;
    let auctionData = initialData ? { ...initialData, images: Array.isArray(initialData.images) ? initialData.images : [], bids: Array.isArray(initialData.bids) ? initialData.bids : [] } : null;

    let selectedIncrement = 100;
    let currentSlide = 0;
    let slideInterval;
    let viewersCount = Math.floor(Math.random() * (25 - 10 + 1)) + 10;
    let lastNotifTime = 0;
    let currentNotifId = null;
    let pricePopTimeout = null;
    let auctionEnded = false;
    let lastFetchTime = 0;
    let lastJsonHash = '';               // بصمة البيانات لتجنب إعادة البناء
    const FETCH_DEBOUNCE = 80;           // مللي ثانية
    const REFRESH_INTERVAL = 100;        // مللي ثانية (10 مرات في الثانية)

    const currentUserDisplayName = window._USER_NAME;
    const currentUserPhone = window._USER_PHONE;
    let isLoggedIn = window._IS_LOGGED_IN;

    // ==================== التهيئة ====================
    window.onload = function() {
        if (auctionData) {
            buildAllViews();
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
        setInterval(fetchFreshData, REFRESH_INTERVAL);
        setInterval(checkNotifications, 3000);
        document.addEventListener("visibilitychange", () => {
            if (!document.hidden) fetchFreshData(true);
        });
        // الاستماع لتسجيل الخروج من نافذة أخرى
        window.addEventListener('storage', (e) => {
            if (e.key === 'logout') updateLoginState(false);
        });
    };

    // ==================== بناء الواجهات ====================
    function buildAllViews() {
        buildHomeContent();
        buildBidContent();
    }

    function buildHomeContent() {
        const container = document.getElementById('home-main-content');
        if (!auctionData) return;
        container.innerHTML = `
            <div class="text-center space-y-2 py-2 animate-fast-fade">
                <span class="inline-block py-1 px-3 rounded-full bg-primary/10 text-primary text-xs font-bold tracking-wide uppercase">مزاد مباشر الآن</span>
                <h2 class="text-3xl font-bold text-gray-900 leading-snug" id="home-title">${auctionData.title}</h2>
            </div>
            <div class="bg-white rounded-[2rem] shadow-xl shadow-gray-200/50 overflow-hidden border border-gray-100 relative group">
                <div class="relative aspect-[4/3] bg-gray-100 overflow-hidden">
                    <div id="home-slider-wrapper" class="slider-wrapper h-full"></div>
                    <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-t from-black/40 to-transparent z-10 cursor-pointer" onclick="openLightbox(currentSlide)">
                        <button class="bg-white/95 backdrop-blur text-gray-900 px-5 py-2.5 rounded-full font-bold text-sm shadow-xl hover:scale-105 transition-all flex items-center gap-2 border border-gray-100">
                            <span class="material-symbols-outlined text-lg">photo_library</span> عرض الصور
                        </button>
                    </div>
                    <button onclick="prevSlide('home')" class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white text-gray-800 p-2 rounded-full shadow-lg transition-all z-20 opacity-0 group-hover:opacity-100"><span class="material-symbols-outlined text-lg">chevron_right</span></button>
                    <button onclick="nextSlide('home')" class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white text-gray-800 p-2 rounded-full shadow-lg transition-all z-20 opacity-0 group-hover:opacity-100"><span class="material-symbols-outlined text-lg">chevron_left</span></button>
                    <div class="absolute bottom-4 left-0 right-0 flex justify-center gap-1.5 pointer-events-none" id="home-dots"></div>
                    <div class="absolute top-4 right-4 bg-trusted-green text-trusted-text px-3 py-1.5 rounded-full text-xs font-bold flex items-center gap-1.5 shadow-sm border border-green-200 z-20"><span class="material-symbols-outlined text-sm fill-current">verified_user</span> موثوق</div>
                    <div class="absolute top-4 left-4 bg-black/70 backdrop-blur text-white px-3 py-1.5 rounded-full text-xs font-medium flex items-center gap-2 shadow-lg border border-white/10 z-20"><span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span><span id="viewers-count-home">${viewersCount}</span> يشاهد الآن</div>
                </div>
                <div class="p-6 space-y-6">
                    <button onclick="openDescription()" class="w-full py-3 bg-gray-50 hover:bg-gray-100 text-gray-700 rounded-xl font-semibold text-sm transition-colors flex items-center justify-center gap-2 border border-gray-200"><span class="material-symbols-outlined text-lg text-gray-400">description</span> عرض تفاصيل ووصف السلعة</button>
                    <div class="bg-primary/5 rounded-2xl p-5 text-center border border-primary/10 relative overflow-hidden"><div class="absolute top-0 left-0 w-1 h-full bg-primary"></div><p class="text-xs text-primary font-bold mb-2 uppercase tracking-wider">ينتهي المزاد خلال</p><div class="flex justify-center items-center gap-3 font-mono text-2xl font-bold text-gray-800" id="home-timer"></div></div>
                    <div class="grid grid-cols-2 gap-6">
                        <div class="border-l border-gray-100 pl-4"><span class="text-xs text-gray-400 block mb-1 font-medium">أعلى مزايدة</span><span class="text-2xl font-bold text-primary"><span id="home-price">${Number(auctionData.current_price).toLocaleString()}</span> <span class="text-sm font-normal text-gray-500">ر.س</span></span></div>
                        <div class="pr-2"><span class="text-xs text-gray-400 block mb-1 font-medium">المزايد الحالي</span><div class="flex items-center gap-3 mt-1"><div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-sm font-bold" id="home-bidder-avatar">?</div><span class="text-base font-bold text-gray-800 truncate" id="home-bidder">لا يوجد</span></div></div>
                    </div>
                    <button id="main-action-btn" onclick="openBidPage()" class="w-full bg-primary hover:bg-primary-dark text-white py-4 rounded-xl font-bold text-lg shadow-lg shadow-primary/30 active:scale-[0.98] transition-all flex items-center justify-center gap-2 group"><span class="material-symbols-outlined group-hover:rotate-12 transition-transform">gavel</span> ابدأ المزايدة الآن</button>
                </div>
            </div>`;
        renderSlider('home-slider-wrapper', 'home-dots');
    }

    function buildBidContent() {
        if (!auctionData) return;
        const container = document.getElementById('bid-main-content');
        container.innerHTML = `
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 text-center"><h2 id="bid-item-title" class="text-xl font-bold text-gray-900">${auctionData.title}</h2></div>
            <div class="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 group">
                <div class="relative aspect-video bg-gray-100"><div id="bid-slider-wrapper" class="slider-wrapper h-full"></div><div class="absolute inset-0 flex items-center justify-center bg-black/10 group-hover:bg-black/20 transition-colors z-10 cursor-pointer" onclick="openLightbox(currentSlide)"><button class="bg-white/90 backdrop-blur text-gray-800 px-4 py-2 rounded-full font-bold text-sm shadow-lg hover:scale-105 transition-transform flex items-center gap-2"><span class="material-symbols-outlined text-lg">photo_library</span> عرض الصور</button></div><button onclick="prevSlide('bid')" class="absolute right-3 top-1/2 -translate-y-1/2 bg-black/40 hover:bg-black/60 text-white p-1.5 rounded-full backdrop-blur-sm transition-all z-20 opacity-0 group-hover:opacity-100"><span class="material-symbols-outlined text-lg">chevron_right</span></button><button onclick="nextSlide('bid')" class="absolute left-3 top-1/2 -translate-y-1/2 bg-black/40 hover:bg-black/60 text-white p-1.5 rounded-full backdrop-blur-sm transition-all z-20 opacity-0 group-hover:opacity-100"><span class="material-symbols-outlined text-lg">chevron_left</span></button><div class="absolute bottom-3 left-0 right-0 flex justify-center gap-1.5 pointer-events-none" id="bid-dots"></div><div class="absolute top-3 right-3 bg-trusted-green text-trusted-text px-3 py-1 rounded-full text-xs font-bold flex items-center gap-1 shadow-sm border border-green-200 z-20"><span class="material-symbols-outlined text-sm fill-current">verified_user</span> موثوق</div><div class="absolute top-3 left-3 bg-black/60 backdrop-blur text-white px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1.5 shadow-lg border border-white/10 z-20"><span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span><span id="viewers-count-bid">${viewersCount}</span> يشاهد الآن</div></div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-center items-center text-center"><span class="text-xs text-gray-500 mb-1">السعر الحالي</span><div class="text-2xl font-bold text-primary flex items-baseline gap-1"><span id="bid-current-price">${Number(auctionData.current_price).toLocaleString()}</span><span class="text-xs font-normal text-gray-400">ر.س</span></div></div>
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-center items-center text-center"><span class="text-xs text-gray-500 mb-1">الوقت المتبقي</span><div id="bid-timer" class="text-xl font-mono font-bold text-gray-800">00:00:00</div></div>
            </div>
            <button onclick="openDescription()" class="w-full py-3 bg-white hover:bg-gray-50 text-gray-700 rounded-xl font-semibold text-sm transition-colors flex items-center justify-center gap-2 border border-gray-200 shadow-sm"><span class="material-symbols-outlined text-lg text-gray-400">description</span> عرض تفاصيل ووصف السلعة</button>
            <div id="bidding-controls" class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100"><h3 class="text-sm font-bold text-gray-800 mb-4 flex items-center gap-2"><span class="material-symbols-outlined text-primary text-lg">add_circle</span> اختر قيمة الزيادة</h3><div class="grid grid-cols-5 gap-2 mb-4" id="bid-chips"><button class="bid-chip h-14 rounded-xl font-bold text-base flex items-center justify-center" onclick="selectAmount(this, 10)">10</button><button class="bid-chip h-14 rounded-xl font-bold text-base flex items-center justify-center" onclick="selectAmount(this, 50)">50</button><button class="bid-chip selected h-14 rounded-xl font-bold text-base flex items-center justify-center" onclick="selectAmount(this, 100)">100</button><button class="bid-chip h-14 rounded-xl font-bold text-base flex items-center justify-center" onclick="selectAmount(this, 150)">150</button><button class="bid-chip h-14 rounded-xl font-bold text-base flex items-center justify-center" onclick="selectAmount(this, 200)">200</button></div><div class="text-center mb-4 p-2 bg-primary/5 rounded-lg border border-primary/10"><span class="text-xs text-gray-500">المزايدة الجديدة ستكون: </span><span class="font-mono font-bold text-primary text-lg" id="expected-new-price">0</span><span class="text-xs text-gray-500"> ر.س</span></div><button id="confirm-bid-btn" onclick="confirmBidAction()" class="w-full bg-primary text-white py-3.5 rounded-xl font-bold shadow-lg shadow-primary/20 active:scale-95 transition-transform flex items-center justify-center gap-2"><span class="material-symbols-outlined">check_circle</span> تأكيد وإرسال المزايدة</button></div>
            <div id="stopped-message" class="hidden bg-gray-100 p-6 rounded-2xl text-center border border-gray-200"><span class="material-symbols-outlined text-4xl text-gray-400 mb-2">block</span><h3 class="font-bold text-gray-700 text-lg">المزاد مغلق</h3><p class="text-gray-500 text-sm">تم إيقاف هذا المزاد أو بيعه.</p></div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"><div class="px-4 py-3 border-b border-gray-100 bg-gray-50 flex justify-between items-center"><h3 class="text-sm font-bold text-gray-700 flex items-center gap-2"><span class="material-symbols-outlined text-gray-400 text-lg">history</span> سجل المزايدات الحي</h3><span class="text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full flex items-center gap-1"><span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span> مباشر</span></div><div id="bids-log" class="divide-y divide-gray-50"></div></div>`;
        renderSlider('bid-slider-wrapper', 'bid-dots');
    }

    function buildEmptyHome() {
        document.getElementById('home-main-content').innerHTML = `
            <div class="flex flex-col items-center justify-center h-[60vh] text-center space-y-4 animate-fast-fade">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-2 shadow-inner"><span class="material-symbols-outlined text-5xl text-gray-300">inventory_2</span></div>
                <h2 class="text-2xl font-bold text-gray-800">لا توجد مزادات حالياً</h2>
                <p class="text-gray-500 max-w-xs mx-auto">ترقب العروض القادمة، يتم إضافة مزادات جديدة بشكل دوري.</p>
                <button onclick="location.reload()" class="mt-4 px-6 py-2 bg-white border border-gray-200 text-gray-600 rounded-full text-sm font-medium hover:bg-gray-50 transition shadow-sm">تحديث الصفحة</button>
            </div>`;
    }

    // ==================== جلب البيانات (تخزين مؤقت وتحديث جزئي) ====================
    async function fetchFreshData(immediate = false) {
        const now = Date.now();
        if (!immediate && (now - lastFetchTime) < FETCH_DEBOUNCE) return;
        lastFetchTime = now;

        try {
            const res = await fetch('auctions_db.json?' + now, {
                cache: 'no-store',
                headers: { 'Cache-Control': 'no-cache' }
            });
            if (!res.ok) return;
            const auctions = await res.json();
            const newHash = JSON.stringify(auctions);

            // لا تغيير في البيانات
            if (newHash === lastJsonHash) return;
            lastJsonHash = newHash;

            const activeAuc = auctions.find(a => a.status === 'active');

            // مزاد جديد
            if (!auctionData && activeAuc) {
                auctionData = { ...activeAuc, images: Array.isArray(activeAuc.images) ? activeAuc.images : [], bids: Array.isArray(activeAuc.bids) ? activeAuc.bids : [] };
                auctionEnded = false;
                buildAllViews();
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
                if (!auctionEnded) handleAuctionEnd();
                auctionData = null;
                buildEmptyHome();
                document.getElementById('bid-main-content').innerHTML = '';
                updateNavHighlight('home');
                return;
            }

            // تحديث جزئي لنفس المزاد
            if (auctionData && activeAuc && activeAuc.id === auctionData.id) {
                const bidsChanged = JSON.stringify(activeAuc.bids) !== JSON.stringify(auctionData.bids);
                const priceChanged = activeAuc.current_price !== auctionData.current_price;
                const statusChanged = activeAuc.status !== auctionData.status;

                if (bidsChanged || priceChanged || statusChanged) {
                    auctionData = { ...activeAuc, images: auctionData.images, bids: Array.isArray(activeAuc.bids) ? activeAuc.bids : [] };
                    if (statusChanged && activeAuc.status !== 'active') handleAuctionEnd();
                    // تحديث جزئي فقط للعناصر المتغيرة
                    updatePriceUI(priceChanged);
                    renderBidsLog();
                    checkAuctionStatus();
                    updateExpectedPrice();
                }
            }
        } catch(e) {}
    }

    // ==================== إدارة انتهاء المزاد ====================
    function handleAuctionEnd() {
        if (auctionEnded || !auctionData) return;
        auctionEnded = true;
        const winner = auctionData.bids?.[0];
        const notification = document.getElementById('winner-notification');
        if (winner && notification) {
            document.getElementById('winner-name').innerText = winner.user;
            document.getElementById('winner-bid').innerText = Number(winner.amount).toLocaleString() + ' ر.س';
            notification.classList.remove('hidden');
            setTimeout(() => notification.classList.add('hidden'), 8000);
        }
        checkAuctionStatus();
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

    // ==================== التحديثات الجزئية للواجهة ====================
    function updatePriceUI(animate = true) {
        if(!auctionData) return;
        const formatted = Number(auctionData.current_price).toLocaleString();
        const bidCurrent = document.getElementById('bid-current-price');
        const homePrice = document.getElementById('home-price');

        if(bidCurrent && bidCurrent.innerText !== formatted) {
            bidCurrent.innerText = formatted;
            if(animate) { bidCurrent.classList.add('price-pop'); clearTimeout(pricePopTimeout); pricePopTimeout = setTimeout(() => bidCurrent.classList.remove('price-pop'), 500); }
        } else if(bidCurrent) bidCurrent.innerText = formatted;

        if(homePrice && homePrice.innerText !== formatted) {
            homePrice.innerText = formatted;
            if(animate) { homePrice.classList.add('price-pop'); clearTimeout(pricePopTimeout); pricePopTimeout = setTimeout(() => homePrice.classList.remove('price-pop'), 500); }
        } else if(homePrice) homePrice.innerText = formatted;

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
            const isWinner = index === 0 && auctionData.status !== 'active';
            return `<div class="px-4 py-3 flex justify-between items-center ${isMe ? 'bg-green-50/70' : ''} ${isWinner ? 'bg-yellow-50/70' : ''}">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full ${isWinner ? 'bg-yellow-400 text-white' : (isMe ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600')} flex items-center justify-center text-sm font-bold">${isWinner ? '👑' : bid.user.charAt(0)}</div>
                    <div><p class="text-sm font-bold ${isWinner ? 'text-yellow-700' : (isMe ? 'text-primary' : 'text-gray-800')}">${bid.user} ${isWinner ? '🏆' : ''}</p><p class="text-[10px] text-gray-400">${bid.time || ''}</p></div>
                </div>
                <span class="font-mono font-bold ${isWinner ? 'text-yellow-600' : (isMe ? 'text-green-600' : 'text-primary')}">${Number(bid.amount).toLocaleString()} ر.س</span>
            </div>`;
        }).join('');
        if (top4.length === 0) container.innerHTML = '<div class="px-4 py-6 text-center text-gray-400 text-sm">لا توجد مزايدات بعد</div>';
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
            if (auctionData.status === 'active' && !auctionEnded) {
                auctionData.status = 'ended';
                handleAuctionEnd();
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

    // ==================== المشاهدين الوهميين ====================
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

    // ==================== التنبيهات ====================
    async function checkNotifications() {
        try {
            const res = await fetch('latest_notif.json?' + Date.now(), { cache: 'no-store' });
            if(!res.ok) return;
            const notif = await res.json();
            if (notif?.title && notif.time > lastNotifTime) {
                lastNotifTime = notif.time;
                currentNotifId = notif.id || 'notif_'+notif.time;
                document.getElementById('notif-badge').classList.remove('hidden');
                if (!localStorage.getItem('dontShowNotifUntil') || parseInt(localStorage.getItem('dontShowNotifUntil')) < notif.time) {
                    document.getElementById('popup-title').innerText = notif.title;
                    document.getElementById('popup-msg').innerText = notif.msg;
                    document.getElementById('notif-popup').classList.remove('hidden');
                    document.getElementById('dont-show-again').checked = false;
                }
                updateNotifListModal();
            } else if (!notif?.title) {
                document.getElementById('notif-popup').classList.add('hidden');
                document.getElementById('notif-badge').classList.add('hidden');
                currentNotifId = null;
            }
        } catch(e) {}
    }

    function markNotifAsRead() {
        if (document.getElementById('dont-show-again').checked) localStorage.setItem('dontShowNotifUntil', lastNotifTime);
        document.getElementById('notif-popup').classList.add('hidden');
        if (currentNotifId) sessionStorage.setItem('read_notif_'+currentNotifId, '1');
    }

    // ==================== السلايدر ====================
    function renderSlider(wrapperId, dotsId) {
        if(!auctionData?.images?.length) return;
        const wrapper = document.getElementById(wrapperId);
        const dotsContainer = document.getElementById(dotsId);
        if(!wrapper || !dotsContainer) return;
        wrapper.innerHTML = auctionData.images.map(src => `<div class="slide h-full"><img src="${src}" class="w-full h-full object-cover" loading="lazy"></div>`).join('');
        dotsContainer.innerHTML = auctionData.images.map((_, idx) => `<div class="w-2 h-2 rounded-full transition-all duration-300 ${idx === 0 ? 'bg-white w-4' : 'bg-white/50'}" id="${dotsId}-dot-${idx}"></div>`).join('');
    }

    function startSlider() { if(slideInterval) clearInterval(slideInterval); slideInterval = setInterval(() => nextSlide(), 4000); }
    function nextSlide() { if(!auctionData?.images?.length) return; currentSlide = (currentSlide + 1) % auctionData.images.length; updateSliderPosition(); }
    function prevSlide() { if(!auctionData?.images?.length) return; currentSlide = (currentSlide - 1 + auctionData.images.length) % auctionData.images.length; updateSliderPosition(); }

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

    function openLightbox() { if(!auctionData) return; updateSliderPosition(); document.getElementById('lightbox-modal').classList.remove('hidden'); }
    function closeLightbox() { document.getElementById('lightbox-modal').classList.add('hidden'); }

    // ==================== المزايدة ====================
    function selectAmount(btn, amount) {
        document.querySelectorAll('.bid-chip').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        selectedIncrement = amount;
        updateExpectedPrice();
    }

    function updateExpectedPrice() {
        if (!auctionData) return;
        const el = document.getElementById('expected-new-price');
        if (el) el.innerText = Number(auctionData.current_price + selectedIncrement).toLocaleString();
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
                lastFetchTime = 0;
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

    // ==================== التنقل وإدارة الجلسة ====================
    function openBidPage() {
        if (!auctionData) return;
        if (!isLoggedIn) {
            document.getElementById('login-required-modal').classList.remove('hidden');
            return;
        }
        document.getElementById('home-page').classList.remove('active');
        document.getElementById('bid-page').classList.add('active');
        updateNavHighlight('auction');
        fetchFreshData(true);
        window.scrollTo({ top: 0, behavior: 'instant' });
    }

    function switchToHome() {
        document.getElementById('bid-page').classList.remove('active');
        document.getElementById('home-page').classList.add('active');
        updateNavHighlight('home');
        const wn = document.getElementById('winner-notification');
        if(wn) wn.classList.add('hidden');
        fetchFreshData(true);
        window.scrollTo({ top: 0, behavior: 'instant' });
    }

    function updateLoginState(loggedIn) {
        isLoggedIn = loggedIn;
        const navAccount = document.querySelector('nav .nav-btn:nth-child(3)');
        if (navAccount) {
            if (loggedIn) {
                navAccount.outerHTML = `<button onclick="document.getElementById('account-modal').classList.remove('hidden')" class="nav-btn flex flex-col items-center justify-center w-full h-full text-gray-400 gap-1"><div class="w-6 h-6 rounded-full bg-primary text-white flex items-center justify-center text-[10px] font-bold">${currentUserDisplayName.charAt(0)}</div><span class="text-[10px] font-medium">حسابي</span></button>`;
            } else {
                navAccount.outerHTML = `<a href="aa.php" class="nav-btn flex flex-col items-center justify-center w-full h-full text-gray-400 hover:text-primary gap-1 transition-colors"><span class="material-symbols-outlined text-2xl">person</span><span class="text-[10px] font-medium">دخول</span></a>`;
            }
        }
        if (!loggedIn && document.getElementById('bid-page').classList.contains('active')) {
            switchToHome();
        }
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

    // ==================== خدمات مساعدة ====================
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
            const res = await fetch('latest_notif.json?' + Date.now(), { cache: 'no-store' });
            if(!res.ok) return;
            const notif = await res.json();
            container.innerHTML = notif?.title
                ? `<div class="flex gap-3 p-3 bg-blue-50 rounded-xl border border-blue-100">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 shrink-0"><span class="material-symbols-outlined">campaign</span></div>
                    <div><h4 class="font-bold text-sm text-gray-800">${notif.title}</h4><p class="text-xs text-gray-500 mt-1">${notif.msg}</p></div></div>`
                : '<div class="text-center text-gray-400 py-8">لا توجد تنبيهات</div>';
        } catch(e) {}
    }

    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
    function closeAccountModal() { document.getElementById('account-modal').classList.add('hidden'); }

    // ==================== تعريض الدوال العامة ====================
    window.openBidPage = openBidPage;
    window.switchToHome = switchToHome;
    window.openLightbox = openLightbox;
    window.closeLightbox = closeLightbox;
    window.nextSlide = nextSlide;
    window.prevSlide = prevSlide;
    window.selectAmount = selectAmount;
    window.confirmBidAction = confirmBidAction;
    window.finalizeBid = finalizeBid;
    window.openDescription = openDescription;
    window.shareContent = shareContent;
    window.openNotifications = openNotifications;
    window.markNotifAsRead = markNotifAsRead;
    window.closeModal = closeModal;
    window.closeAccountModal = closeAccountModal;
})();