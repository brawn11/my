<?php
// ak/aa.php - صفحة تسجيل العملاء v6.0 - تصميم أخضر احترافي

session_start();

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: aax.php');
    exit;
}

if (isset($_SESSION['user_phone'])) {
    header('Location: indexx.php');
    exit;
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"/>
    <meta name="theme-color" content="#00685f">
    <title>تسجيل الدخول - مزاد النخبة</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet"/>

    <script>tailwind.config={theme:{extend:{colors:{primary:"#00685f",primaryLight:"#e0f2f1",primaryDark:"#004d46",whatsapp:"#25D366"}}}};</script>

    <style>
        * { -webkit-tap-highlight-color: transparent; box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'IBM Plex Sans Arabic', sans-serif; background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 40%, #f0fdf4 100%); min-height: 100dvh; display: flex; align-items: center; justify-content: center; padding: 16px; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; user-select: none; pointer-events: none; }
        
        input { font-size: 16px !important; }
        input[type="tel"] { -webkit-appearance: none; appearance: none; }
        
        /* حقل إدخال بتدرج أخضر */
        .input-wrap {
            position: relative; background: #fff; border: 2px solid #d1fae5;
            border-radius: 16px; transition: all 0.3s ease; overflow: hidden;
        }
        .input-wrap:focus-within { 
            border-color: #00685f; 
            box-shadow: 0 0 0 4px rgba(0,104,95,0.08);
            background: #fafefc;
        }
        .input-wrap.error { border-color: #ef4444; background: #fef2f2; box-shadow: 0 0 0 4px rgba(239,68,68,0.06); }
        .input-wrap .input-icon {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            color: #86c5b8; transition: color 0.3s ease; z-index: 1;
        }
        .input-wrap:focus-within .input-icon { color: #00685f; }
        .input-wrap.error .input-icon { color: #ef4444; }
        .input-wrap input {
            width: 100%; border: none; background: transparent; padding: 15px 44px 15px 16px;
            outline: none; font-size: 16px; font-weight: 500; color: #1f2937;
            font-family: 'IBM Plex Sans Arabic', sans-serif;
        }
        .input-wrap input::placeholder { color: #b8d9cf; font-weight: 400; }
        .input-wrap input.tel-input { font-family: monospace; letter-spacing: 2px; }
        
        /* تبويبات بتدرج */
        .tab-btn {
            position: relative; transition: all 0.3s ease; cursor: pointer;
            border: none; background: transparent; outline: none; padding: 13px;
            color: #6b7280; font-weight: 500;
        }
        .tab-btn.active { color: #00685f; font-weight: 700; background: linear-gradient(180deg, rgba(0,104,95,0.04) 0%, transparent 100%); }
        .tab-btn .tab-indicator {
            position: absolute; bottom: 0; left: 25%; width: 50%; height: 3px;
            background: linear-gradient(90deg, #00685f, #00a896);
            border-radius: 3px 3px 0 0; transform: scaleX(0); transition: transform 0.3s ease;
        }
        .tab-btn.active .tab-indicator { transform: scaleX(1); }
        
        /* زر بتدرج */
        .submit-btn {
            position: relative; overflow: hidden; transition: all 0.2s ease;
            border: none; cursor: pointer; outline: none;
            background: linear-gradient(135deg, #00685f 0%, #00897b 50%, #00685f 100%);
            background-size: 200% 100%; background-position: 0% 0%;
        }
        .submit-btn:hover { background-position: 100% 0%; }
        .submit-btn:active { transform: scale(0.97); }
        .submit-btn.loading { pointer-events: none; opacity: 0.8; }
        .submit-btn .btn-content { position: relative; z-index: 1; transition: opacity 0.2s ease; }
        .submit-btn.loading .btn-content { opacity: 0; }
        .submit-btn .btn-loader {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            z-index: 1; opacity: 0; transition: opacity 0.2s ease;
            display: flex; align-items: center; gap: 10px;
        }
        .submit-btn.loading .btn-loader { opacity: 1; }
        .btn-spinner {
            width: 22px; height: 22px; border: 2.5px solid rgba(255,255,255,0.3);
            border-top-color: white; border-radius: 50%; animation: spin 0.6s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        
        /* شريط تقدم */
        .step-dots { display: flex; align-items: center; justify-content: center; gap: 6px; }
        .step-dot { width: 8px; height: 8px; border-radius: 50%; background: #d1fae5; transition: all 0.3s ease; }
        .step-dot.active { background: #00685f; width: 24px; border-radius: 4px; }
        
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(10px); } 100% { opacity: 1; transform: translateY(0); } }
        .animate-fadeUp { animation: fadeUp 0.4s ease-out; }
    </style>
</head>
<body>

    <!-- البطاقة -->
    <div class="w-full max-w-[390px] animate-fadeUp">
        

        
        <!-- البطاقة الرئيسية -->
        <div class="bg-white rounded-2xl border-2 border-[#d1fae5] overflow-hidden">
            
            <!-- هيدر -->
            <div class="relative overflow-hidden bg-gradient-to-br from-primary via-[#007d72] to-primaryDark text-white p-5 text-center">
                <div class="absolute -top-6 -right-6 w-20 h-20 bg-white/10 rounded-full"></div>
                <div class="absolute -bottom-4 -left-4 w-14 h-14 bg-white/5 rounded-full"></div>
                
                <div class="relative z-10">
                    <div class="w-12 h-12 bg-white/15 rounded-xl flex items-center justify-center mx-auto mb-2.5">
                        <span class="material-symbols-outlined text-2xl">gavel</span>
                    </div>
                    <h1 class="text-lg font-bold tracking-tight">مزاد النخبة</h1>
                    <p class="text-[11px] opacity-80 mt-0.5">تسجيل العملاء</p>
                </div>
            </div>

            <!-- تبويبات -->
            <div class="flex border-b border-[#d1fae5] bg-[#f0fdf4]/50">
                <button onclick="switchTab('login')" id="btn-login" class="tab-btn active flex-1 flex items-center justify-center gap-2 text-sm">
                    <span class="material-symbols-outlined text-lg">login</span> دخول
                    <span class="tab-indicator"></span>
                </button>
                <button onclick="switchTab('register')" id="btn-register" class="tab-btn flex-1 flex items-center justify-center gap-2 text-sm">
                    <span class="material-symbols-outlined text-lg">person_add</span> تسجيل
                    <span class="tab-indicator"></span>
                </button>
            </div>

            <!-- المحتوى -->
            <div class="p-4 space-y-3.5">
                
                <!-- نموذج الدخول -->
                <form id="form-login" onsubmit="handleSubmit(event, 'login')" class="space-y-3.5">
                    <div>
                        <label class="flex items-center gap-1.5 text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                            رقم الجوال
                        </label>
                        <div class="input-wrap" id="login-phone-wrap">
                            <span class="input-icon material-symbols-outlined">phone_iphone</span>
                            <input type="tel" name="phone" id="login-phone" placeholder="05xxxxxxxx" maxlength="10" autocomplete="tel" inputmode="numeric"
                                   class="tel-input" oninput="this.value=this.value.replace(/[^0-9]/g,'');clearError('login-phone');">
                        </div>
                        <p id="login-phone-error" class="text-red-500 text-[10px] mt-1.5 mr-1 hidden flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">error</span> يجب أن يبدأ بـ 05 ويتكون من 10 أرقام
                        </p>
                    </div>
                    
                    <button type="submit" id="btn-submit-login" class="submit-btn w-full text-white py-3.5 rounded-xl font-bold text-sm">
                        <span class="btn-content flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-lg">arrow_forward</span> دخول
                        </span>
                        <span class="btn-loader">
                            <span class="btn-spinner"></span>
                            <span class="text-white text-sm">جاري الدخول...</span>
                        </span>
                    </button>
                </form>

                <!-- نموذج التسجيل -->
                <form id="form-register" onsubmit="handleSubmit(event, 'register')" class="space-y-3.5 hidden">
                    <div>
                        <label class="flex items-center gap-1.5 text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                            الاسم الكريم
                        </label>
                        <div class="input-wrap" id="reg-name-wrap">
                            <span class="input-icon material-symbols-outlined">person</span>
                            <input type="text" name="name" id="reg-name" placeholder="الاسم الثلاثي" autocomplete="name"
                                   oninput="clearError('reg-name');">
                        </div>
                        <p id="reg-name-error" class="text-red-500 text-[10px] mt-1.5 mr-1 hidden flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">error</span> الاسم يجب أن يكون 3 أحرف على الأقل
                        </p>
                    </div>
                    
                    <div>
                        <label class="flex items-center gap-1.5 text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                            رقم الجوال
                        </label>
                        <div class="input-wrap" id="reg-phone-wrap">
                            <span class="input-icon material-symbols-outlined">phone_iphone</span>
                            <input type="tel" name="phone" id="reg-phone" placeholder="05xxxxxxxx" maxlength="10" autocomplete="tel" inputmode="numeric"
                                   class="tel-input" oninput="this.value=this.value.replace(/[^0-9]/g,'');clearError('reg-phone');">
                        </div>
                        <p id="reg-phone-error" class="text-red-500 text-[10px] mt-1.5 mr-1 hidden flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">error</span> يجب أن يبدأ بـ 05 ويتكون من 10 أرقام
                        </p>
                    </div>
                    
                    <div class="bg-[#f0fdf4] border border-[#d1fae5] p-3 rounded-xl text-[10px] text-primaryDark flex items-start gap-2">
                        <span class="material-symbols-outlined text-sm flex-shrink-0 mt-0.5 text-primary">verified_user</span>
                        <span>تأكد من صحة الرقم، فهو وسيلة التواصل عند الفوز بالمزاد.</span>
                    </div>
                    
                    <button type="submit" id="btn-submit-reg" class="submit-btn w-full text-white py-3.5 rounded-xl font-bold text-sm">
                        <span class="btn-content flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-lg">how_to_reg</span> إنشاء حساب
                        </span>
                        <span class="btn-loader">
                            <span class="btn-spinner"></span>
                            <span class="text-white text-sm">جاري التسجيل...</span>
                        </span>
                    </button>
                </form>

                <!-- الرسائل -->
                <div id="msg-area"></div>
                
                <!-- فاصل -->
                <div class="relative py-1">
                    <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-[#d1fae5]"></div></div>
                    <div class="relative flex justify-center">
                        <a href="indexx.php" class="bg-white px-4 text-gray-400 hover:text-primary transition-colors text-[11px] inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sm">arrow_back</span>
                            الدخول كـ زائر
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- نص سفلي -->
        <p class="text-center text-[10px] text-gray-300 mt-3">
            مزاد النخبة © جميع الحقوق محفوظة
        </p>
    </div>

    <script>
        function switchTab(tab) {
            const loginForm = document.getElementById('form-login');
            const regForm = document.getElementById('form-register');
            const btnLogin = document.getElementById('btn-login');
            const btnReg = document.getElementById('btn-register');
            const msgArea = document.getElementById('msg-area');

            msgArea.innerHTML = '';
            msgArea.className = '';
            document.querySelectorAll('[id$="-error"]').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.input-wrap').forEach(el => el.classList.remove('error'));

            if (tab === 'login') {
                loginForm.classList.remove('hidden'); regForm.classList.add('hidden');
                btnLogin.classList.add('active'); btnLogin.classList.remove('text-gray-400');
                btnReg.classList.remove('active'); btnReg.classList.add('text-gray-400');
                setTimeout(() => document.getElementById('login-phone').focus(), 100);
            } else {
                loginForm.classList.add('hidden'); regForm.classList.remove('hidden');
                btnReg.classList.add('active'); btnReg.classList.remove('text-gray-400');
                btnLogin.classList.remove('active'); btnLogin.classList.add('text-gray-400');
                setTimeout(() => document.getElementById('reg-name').focus(), 100);
            }
        }

        function clearError(inputId) {
            const wrap = document.getElementById(inputId + '-wrap');
            const errorEl = document.getElementById(inputId + '-error');
            if (wrap) wrap.classList.remove('error');
            if (errorEl) errorEl.classList.add('hidden');
        }

        function validatePhone(phone) { return /^05[0-9]{8}$/.test(phone); }

        function showFieldError(inputId) {
            const wrap = document.getElementById(inputId + '-wrap');
            const errorEl = document.getElementById(inputId + '-error');
            if (wrap) wrap.classList.add('error');
            if (errorEl) errorEl.classList.remove('hidden');
        }

        function setButtonLoading(btn, isLoading) {
            if (isLoading) { btn.classList.add('loading'); btn.disabled = true; }
            else { btn.classList.remove('loading'); btn.disabled = false; }
        }

        function showMessage(type, text) {
            const area = document.getElementById('msg-area');
            const styles = {
                success: 'text-primary bg-[#f0fdf4] border border-[#d1fae5]',
                error: 'text-red-600 bg-red-50 border border-red-200'
            };
            area.className = `text-xs font-medium p-2.5 rounded-xl flex items-center justify-center gap-1.5 ${styles[type] || ''}`;
            const icons = { success: 'check_circle', error: 'error' };
            area.innerHTML = `<span class="material-symbols-outlined text-sm">${icons[type]}</span> ${text}`;
        }

        async function handleSubmit(e, action) {
            e.preventDefault();
            const form = e.target;
            const msgArea = document.getElementById('msg-area');
            msgArea.innerHTML = '';
            msgArea.className = '';
            
            let valid = true;
            if (action === 'login') {
                const phone = document.getElementById('login-phone').value.trim();
                if (!validatePhone(phone)) { showFieldError('login-phone'); valid = false; }
            } else {
                const name = document.getElementById('reg-name').value.trim();
                const phone = document.getElementById('reg-phone').value.trim();
                if (name.length < 3) { showFieldError('reg-name'); valid = false; }
                if (!validatePhone(phone)) { showFieldError('reg-phone'); valid = false; }
            }
            
            if (!valid) { showMessage('error', 'يرجى تصحيح الأخطاء'); return; }

            const btn = form.querySelector('button[type="submit"]');
            setButtonLoading(btn, true);

            const formData = new FormData(form);
            formData.append('action', action);

            try {
                const response = await fetch('aabx.php', { method: 'POST', body: formData, headers: { 'Accept': 'application/json' }, cache: 'no-store' });
                const result = await response.json();

                if (result.status === 'success') {
                    showMessage('success', result.message);
                    if (result.redirect) {
                        setTimeout(() => window.location.href = result.redirect, 500);
                    } else {
                        setTimeout(() => {
                            const regPhone = document.getElementById('reg-phone')?.value || '';
                            switchTab('login');
                            if (regPhone) document.getElementById('login-phone').value = regPhone;
                            showMessage('success', '🎉 تم التسجيل بنجاح! يمكنك الدخول الآن');
                        }, 1000);
                    }
                } else {
                    showMessage('error', result.message || 'حدث خطأ');
                }
            } catch (error) {
                showMessage('error', 'فشل الاتصال بالخادم');
            } finally {
                setButtonLoading(btn, false);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('login-phone').focus();
            document.getElementById('btn-login').classList.add('active');
        });
    </script>
</body>
</html>