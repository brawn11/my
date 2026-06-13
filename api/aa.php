<?php
// ak/aa.php - صفحة تسجيل الدخول والتسجيل (نسخة محسنة للجوال)
session_start();

// --- تسجيل الخروج ---
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: aa.php');
    exit;
}

// --- إذا كان المستخدم مسجلاً الدخول بالفعل ---
if (isset($_SESSION['user_phone'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover"/>
    <title>تسجيل الدخول - مزاد النخبة</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0" rel="stylesheet"/>
    <style>
        body { 
            font-family: 'IBM Plex Sans Arabic', sans-serif; 
            background: linear-gradient(135deg, #f0f4f8 0%, #d9e2e8 100%);
            height: 100dvh; /* Dynamic Viewport Height for Mobile */
            overflow: hidden; /* Prevent body scroll */
        }
        .tab-btn {
            position: relative;
            transition: all 0.3s ease;
        }
        .tab-btn.active {
            color: #00685f;
            font-weight: 700;
        }
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 25%;
            width: 50%;
            height: 3px;
            background: #00685f;
            border-radius: 3px 3px 0 0;
        }
        .input-field {
            transition: all 0.2s ease;
            border: 2px solid #e5e7eb;
            -webkit-appearance: none; /* Remove iOS styling */
        }
        .input-field:focus {
            border-color: #00685f;
            box-shadow: 0 0 0 3px rgba(0,104,95,0.1);
            outline: none;
        }
        .input-field.error {
            border-color: #ef4444;
            background-color: #fef2f2;
        }
        .submit-btn {
            transition: transform 0.1s ease;
            position: relative;
            overflow: hidden;
        }
        .submit-btn:active {
            transform: scale(0.98);
        }
        .submit-btn.loading {
            pointer-events: none;
            opacity: 0.8;
        }
        .spinner {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.6s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        
        /* Custom Scrollbar for the card if needed on very small screens */
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    </style>
</head>
<body class="flex items-center justify-center p-3 sm:p-4">

    <!-- Main Card -->
    <div class="bg-white w-full max-w-md rounded-2xl shadow-xl overflow-hidden flex flex-col max-h-[98dvh] transform transition-all fade-in">
        
        <!-- Header (Compact) -->
        <div class="bg-gradient-to-l from-[#00685f] to-[#004d46] text-white p-5 text-center shrink-0">
            <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-2 backdrop-blur-sm">
                <span class="material-symbols-outlined text-3xl">gavel</span>
            </div>
            <h1 class="text-xl font-bold">مزاد النخبة</h1>
            <p class="text-xs opacity-90 mt-0.5">منصة المزادات العلنية</p>
        </div>

        <!-- Tabs -->
        <div class="flex border-b bg-gray-50/80 shrink-0">
            <button onclick="switchTab('login')" id="btn-login" class="tab-btn active w-1/2 py-3 text-sm text-center text-gray-600">
                <span class="material-symbols-outlined text-base align-middle ml-1">login</span> دخول
            </button>
            <button onclick="switchTab('register')" id="btn-register" class="tab-btn w-1/2 py-3 text-sm text-center text-gray-400">
                <span class="material-symbols-outlined text-base align-middle ml-1">person_add</span> حساب جديد
            </button>
        </div>

        <!-- Content Area (Scrollable if screen is too small) -->
        <div class="p-4 sm:p-6 overflow-y-auto custom-scroll flex-grow">
            
            <!-- Login Form -->
            <form id="form-login" onsubmit="handleSubmit(event, 'login')" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5 mr-1">رقم الجوال</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                            <span class="material-symbols-outlined text-xl">phone_iphone</span>
                        </span>
                        <input type="tel" name="phone" id="login-phone" placeholder="05xxxxxxxx" maxlength="10" autocomplete="tel" inputmode="numeric"
                               class="input-field w-full pl-10 pr-4 py-3 rounded-xl text-left dir-ltr font-mono text-base tracking-wide bg-gray-50 focus:bg-white"
                               oninput="this.value = this.value.replace(/[^0-9]/g, ''); clearFieldError(this);">
                    </div>
                    <p id="login-phone-error" class="text-red-500 text-[10px] mt-1 hidden mr-1">رقم الجوال يجب أن يبدأ بـ 05 ويتكون من 10 أرقام</p>
                </div>
                
                <button type="submit" id="btn-submit-login" class="submit-btn w-full bg-[#00685f] hover:bg-[#004d46] text-white py-3 rounded-xl font-bold shadow-md shadow-[#00685f]/20 flex items-center justify-center gap-2 text-sm">
                    <span class="material-symbols-outlined text-lg">arrow_forward</span> دخول
                </button>
            </form>

            <!-- Register Form -->
            <form id="form-register" onsubmit="handleSubmit(event, 'register')" class="space-y-4 hidden">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5 mr-1">الاسم الكريم</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                            <span class="material-symbols-outlined text-xl">person</span>
                        </span>
                        <input type="text" name="name" id="reg-name" placeholder="الاسم الثلاثي" autocomplete="name"
                               class="input-field w-full pl-10 pr-4 py-3 rounded-xl bg-gray-50 focus:bg-white"
                               oninput="clearFieldError(this);">
                    </div>
                    <p id="reg-name-error" class="text-red-500 text-[10px] mt-1 hidden mr-1">الاسم يجب أن يتكون من 3 أحرف على الأقل</p>
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5 mr-1">رقم الجوال</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                            <span class="material-symbols-outlined text-xl">phone_iphone</span>
                        </span>
                        <input type="tel" name="phone" id="reg-phone" placeholder="05xxxxxxxx" maxlength="10" autocomplete="tel" inputmode="numeric"
                               class="input-field w-full pl-10 pr-4 py-3 rounded-xl text-left dir-ltr font-mono text-base tracking-wide bg-gray-50 focus:bg-white"
                               oninput="this.value = this.value.replace(/[^0-9]/g, ''); clearFieldError(this);">
                    </div>
                    <p id="reg-phone-error" class="text-red-500 text-[10px] mt-1 hidden mr-1">رقم الجوال يجب أن يبدأ بـ 05 ويتكون من 10 أرقام</p>
                    
                    <div class="text-[10px] text-amber-700 mt-2 bg-amber-50 p-2 rounded-lg border border-amber-100 flex items-start gap-2">
                        <span class="material-symbols-outlined text-sm shrink-0 mt-0.5">warning</span>
                        <span>تأكد من صحة الرقم، فهو وسيلة التواصل الوحيدة عند الفوز.</span>
                    </div>
                </div>
                
                <button type="submit" id="btn-submit-reg" class="submit-btn w-full bg-gray-800 hover:bg-gray-900 text-white py-3 rounded-xl font-bold shadow-md shadow-gray-800/10 flex items-center justify-center gap-2 text-sm">
                    <span class="material-symbols-outlined text-lg">how_to_reg</span> إنشاء حساب
                </button>
            </form>

            <!-- Message Area -->
            <div id="msg-area" class="mt-4 text-center text-xs font-medium min-h-[20px] transition-all duration-300"></div>
            
            <!-- Back Link -->
            <div class="mt-3 text-center pb-1">
                <a href="index.php" class="text-gray-400 hover:text-gray-600 text-[10px] transition-colors inline-flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">arrow_back</span> الدخول كـ ضيف
                </a>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            const loginForm = document.getElementById('form-login');
            const regForm = document.getElementById('form-register');
            const btnLogin = document.getElementById('btn-login');
            const btnReg = document.getElementById('btn-register');
            const msgArea = document.getElementById('msg-area');

            msgArea.innerHTML = '';
            msgArea.className = "mt-4 text-center text-xs font-medium min-h-[20px] transition-all duration-300";
            
            document.querySelectorAll('[id$="-error"]').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.input-field').forEach(el => el.classList.remove('error'));

            if (tab === 'login') {
                loginForm.classList.remove('hidden');
                regForm.classList.add('hidden');
                btnLogin.classList.add('active', 'text-gray-600');
                btnLogin.classList.remove('text-gray-400');
                btnReg.classList.remove('active', 'text-gray-600');
                btnReg.classList.add('text-gray-400');
            } else {
                loginForm.classList.add('hidden');
                regForm.classList.remove('hidden');
                btnReg.classList.add('active', 'text-gray-600');
                btnReg.classList.remove('text-gray-400');
                btnLogin.classList.remove('active', 'text-gray-600');
                btnLogin.classList.add('text-gray-400');
            }
        }

        function clearFieldError(input) {
            input.classList.remove('error');
            const errorEl = document.getElementById(input.id + '-error');
            if (errorEl) errorEl.classList.add('hidden');
        }

        function validatePhone(phone) {
            return /^05[0-9]{8}$/.test(phone);
        }

        function showFieldError(inputId, errorId) {
            document.getElementById(inputId).classList.add('error');
            document.getElementById(errorId).classList.remove('hidden');
        }

        function setButtonLoading(btn, isLoading) {
            const originalHTML = btn.getAttribute('data-original-html') || btn.innerHTML;
            if (isLoading) {
                btn.setAttribute('data-original-html', originalHTML);
                btn.innerHTML = '<span class="spinner"></span> جاري...';
                btn.classList.add('loading');
            } else {
                btn.innerHTML = originalHTML;
                btn.classList.remove('loading');
            }
        }

        async function handleSubmit(e, action) {
            e.preventDefault();
            const form = e.target;
            const msgArea = document.getElementById('msg-area');
            msgArea.innerHTML = '';
            
            let valid = true;
            if (action === 'login') {
                const phone = document.getElementById('login-phone').value.trim();
                if (!validatePhone(phone)) {
                    showFieldError('login-phone', 'login-phone-error');
                    valid = false;
                }
            } else {
                const name = document.getElementById('reg-name').value.trim();
                const phone = document.getElementById('reg-phone').value.trim();
                if (name.length < 3) { showFieldError('reg-name', 'reg-name-error'); valid = false; }
                if (!validatePhone(phone)) { showFieldError('reg-phone', 'reg-phone-error'); valid = false; }
            }
            
            if (!valid) {
                msgArea.innerHTML = '<span class="text-red-500 flex items-center justify-center gap-1"><span class="material-symbols-outlined text-sm">error</span> يرجى تصحيح الأخطاء</span>';
                return;
            }

            const btn = form.querySelector('button[type="submit"]');
            setButtonLoading(btn, true);

            const formData = new FormData(form);
            formData.append('action', action);

            try {
                const response = await fetch('aab.php', { method: 'POST', body: formData, headers: { 'Accept': 'application/json' } });
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) throw new Error('Server Error');
                
                const result = await response.json();

                if (result.status === 'success') {
                    msgArea.className = "mt-4 text-center text-xs font-medium text-green-600 flex items-center justify-center gap-1 bg-green-50 p-2 rounded-lg";
                    msgArea.innerHTML = '<span class="material-symbols-outlined text-sm">check_circle</span> ' + result.message;
                    
                    if (result.redirect) {
                        setTimeout(() => window.location.href = result.redirect, 1000);
                    } else {
                        setTimeout(() => {
                            const regPhone = document.getElementById('reg-phone').value;
                            switchTab('login');
                            document.getElementById('login-phone').value = regPhone;
                            msgArea.innerHTML = '<span class="text-green-600 flex items-center justify-center gap-1"><span class="material-symbols-outlined text-sm">check_circle</span> تم التسجيل! يمكنك الدخول الآن</span>';
                        }, 1500);
                    }
                } else {
                    msgArea.className = "mt-4 text-center text-xs font-medium text-red-600 flex items-center justify-center gap-1 bg-red-50 p-2 rounded-lg";
                    msgArea.innerHTML = '<span class="material-symbols-outlined text-sm">error</span> ' + (result.message || 'حدث خطأ');
                }
            } catch (error) {
                console.error('Error:', error);
                msgArea.className = "mt-4 text-center text-xs font-medium text-red-600 flex items-center justify-center gap-1 bg-red-50 p-2 rounded-lg";
                msgArea.innerHTML = '<span class="material-symbols-outlined text-sm">wifi_off</span> فشل الاتصال';
            } finally {
                setButtonLoading(btn, false);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('login-phone').focus();
        });
    </script>
</body>
</html>

