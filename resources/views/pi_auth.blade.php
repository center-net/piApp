<!doctype html>
<html lang="ar">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login with Pi</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            direction: rtl;
        }

        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 500px;
            width: 90%;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }

        #btnLogin {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            background-color: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: bold;
        }

        #btnLogin:hover:not(:disabled) {
            background-color: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        #btnLogin:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        #status {
            margin-top: 20px;
            padding: 12px;
            background-color: #f0f0f0;
            border-left: 4px solid #667eea;
            border-radius: 3px;
            color: #333;
            min-height: 20px;
        }

        #status.success {
            background-color: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }

        #status.error {
            background-color: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }

        #status.loading {
            background-color: #d1ecf1;
            border-left-color: #17a2b8;
            color: #0c5460;
        }

        #debug {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            font-size: 12px;
            color: #666;
            max-height: 300px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            display: none;
        }

        .debug-toggle {
            margin-top: 15px;
            padding: 8px 16px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .debug-toggle:hover {
            background-color: #5a6268;
        }

        .debug-message {
            padding: 5px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .debug-message:last-child {
            border-bottom: none;
        }

        .timestamp {
            color: #999;
            font-weight: bold;
        }

        @media (max-width: 600px) {
            .container {
                padding: 20px;
            }

            h2 {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>🔐 تسجيل الدخول مع Pi</h2>
        <button id="btnLogin" disabled>⏳ جاري التهيئة...</button>
        <div id="status"></div>
        <button class="debug-toggle" onclick="toggleDebug()">📋 عرض السجلات</button>
        <div id="debug"></div>
    </div>

    <script src="https://sdk.minepi.com/pi-sdk.js"></script>
    <script>
        // ===== متغيرات عامة =====
        let piInitialized = false;
        let piAuth = null;
        let isProcessing = false;
        const MAX_DEBUG_MESSAGES = 50;

        // التحقق من توفر Pi SDK
        window.addEventListener('error', function(event) {
            if (event.filename && event.filename.includes('pi-sdk')) {
                addDebugMessage(`❌ فشل تحميل Pi SDK من CDN: ${event.message}`);
                setStatus('❌ فشل تحميل Pi SDK - تحقق من الاتصال بالإنترنت', 'error');
            }
        });

        // ===== الدوال المساعدة =====
        function addDebugMessage(message) {
            const debugDiv = document.getElementById('debug');
            const timestamp = new Date().toLocaleTimeString('ar-SA');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'debug-message';
            messageDiv.innerHTML = `<span class="timestamp">[${timestamp}]</span> ${escapeHtml(message)}`;

            debugDiv.appendChild(messageDiv);

            // تحديد عدد الرسائل
            const messages = debugDiv.querySelectorAll('.debug-message');
            if (messages.length > MAX_DEBUG_MESSAGES) {
                messages[0].remove();
            }

            console.log(`[${timestamp}] ${message}`);
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        function setStatus(message, type = 'info') {
            const statusDiv = document.getElementById('status');
            statusDiv.textContent = message;
            statusDiv.className = type;
            addDebugMessage(`[${type.toUpperCase()}] ${message}`);
        }

        function toggleDebug() {
            const debugDiv = document.getElementById('debug');
            debugDiv.style.display = debugDiv.style.display === 'none' ? 'block' : 'none';
        }

        // ===== تهيئة Pi SDK =====
        async function initPiSDK() {
            try {
                addDebugMessage('🚀 جاري تهيئة Pi SDK...');

                if (typeof Pi === 'undefined') {
                    throw new Error('Pi SDK غير متاح في الصفحة');
                }

                await Pi.init({
                    version: '2.0',
                    sandbox: true,
                });

                piInitialized = true;
                document.getElementById('btnLogin').disabled = false;
                document.getElementById('btnLogin').textContent = '🔓 تسجيل الدخول مع Pi';
                setStatus('✅ تم تهيئة Pi SDK بنجاح', 'success');
                addDebugMessage('✅ تم تهيئة Pi SDK بنجاح');
            } catch (error) {
                console.error('❌ فشل تهيئة Pi SDK:', error);
                setStatus(`❌ فشل تهيئة Pi SDK: ${error.message}`, 'error');
                addDebugMessage(`❌ خطأ في التهيئة: ${error.message}`);
                document.getElementById('btnLogin').textContent = '❌ فشل التهيئة';
            }
        }

        // التحقق الدوري من Pi SDK
        window.addEventListener('load', initPiSDK);

        let checkCount = 0;
        const checkPiSDK = setInterval(() => {
            if (typeof Pi !== 'undefined' && !piInitialized && checkCount < 20) {
                addDebugMessage('🔍 تم العثور على Pi SDK، جاري بدء التهيئة...');
                initPiSDK();
                clearInterval(checkPiSDK);
            }
            checkCount++;
            if (checkCount >= 20) {
                clearInterval(checkPiSDK);
                if (!piInitialized) {
                    setStatus('❌ لم يتمكن من تحميل Pi SDK', 'error');
                    addDebugMessage('❌ انتهت محاولات تحميل Pi SDK (20 محاولة)');
                }
            }
        }, 500);

        // ===== دالة تسجيل الدخول الرئيسية =====
        async function piLogin() {
            if (!piInitialized) {
                setStatus('⚠️ Pi SDK لم يتم تهيئته بعد', 'error');
                return;
            }

            if (isProcessing) {
                setStatus('⏳ جاري معالجة طلب سابق...', 'loading');
                return;
            }

            isProcessing = true;
            document.getElementById('btnLogin').disabled = true;

            try {
                setStatus('🔄 جاري تسجيل الدخول...', 'loading');
                addDebugMessage('📤 بدء عملية تسجيل الدخول...');

                const scopes = ['username', 'payments'];
                addDebugMessage(`📋 النطاقات المطلوبة: ${scopes.join(', ')}`);

                // إنشء timeout للمصادقة (دقيقة واحدة)
                addDebugMessage('⏳ في انتظار نافذة تسجيل الدخول من Pi SDK...');
                setStatus('⏳ يرجى التوافق مع طلب المصادقة', 'loading');

                const authPromise = Pi.authenticate(scopes);
                const timeoutPromise = new Promise((_, reject) =>
                    setTimeout(() => reject(new Error('انتهت مهلة المصادقة - لم تستجب خادم Pi')), 60000)
                );

                piAuth = await Promise.race([authPromise, timeoutPromise]);
                addDebugMessage(`✅ تمت المصادقة بنجاح!`);
                addDebugMessage(`📊 البيانات المستقبلة: accessToken=${piAuth.accessToken ? '✓' : '✗'}, uid=${piAuth.user?.uid || 'بدون'}, username=${piAuth.user?.username || 'بدون'}`);

                // استخراج البيانات من الـ response الصحيح
                const payload = {
                    accessToken: piAuth.accessToken || '',
                    username: piAuth.user?.username || '',
                    uid: piAuth.user?.uid || ''
                };

                if (!payload.accessToken) {
                    throw new Error('لم يتم الحصول على access token من Pi');
                }

                addDebugMessage(`📤 إرسال البيانات: ${JSON.stringify(payload)}`);

                const res = await fetch('/api/pi/auth', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                addDebugMessage(`📨 الرد من الخادم: الحالة = ${res.status}`);

                if (!res.ok) {
                    const errorData = await res.json();
                    throw new Error(errorData.message || errorData.error || `خطأ من الخادم: ${res.status}`);
                }

                const data = await res.json();
                addDebugMessage(`✅ بيانات الاستجابة: ${JSON.stringify(data)}`);

                if (!data.token) {
                    throw new Error('لم يتم استلام token من الخادم');
                }

                setStatus('✅ تم تسجيل الدخول بنجاح! جاري التوجيه...', 'success');
                addDebugMessage(`🔐 التوكن: ${data.token.substring(0, 20)}...`);

                // توجيه آمن
                setTimeout(() => {
                    window.location.href = `callback://${data.token}`;
                }, 500);

            } catch (error) {
                console.error('❌ خطأ كامل:', error);
                const errorMessage = error.message || String(error);

                // رسائل مخصصة حسب نوع الخطأ
                let displayMessage = errorMessage;

                if (errorMessage.includes('انتهت مهلة')) {
                    displayMessage = '⏱️ انتهت مهلة المصادقة - لم يرد Pi SDK في الوقت المحدد';
                } else if (errorMessage.includes('Pi SDK غير متاح')) {
                    displayMessage = '❌ Pi SDK غير متاح - تحقق من الاتصال بالإنترنت أو حاول لاحقاً';
                } else if (errorMessage.includes('لم يتم الحصول على access token')) {
                    displayMessage = '⚠️ لم يتم الحصول على access token - قد تكون الموافقة تم رفضها';
                }

                setStatus(`❌ خطأ: ${displayMessage}`, 'error');
                addDebugMessage(`❌ خطأ في العملية: ${displayMessage}`);
                addDebugMessage(`📍 التفاصيل: ${errorMessage}`);

                document.getElementById('btnLogin').disabled = false;
                isProcessing = false;
            }
        }

        document.getElementById('btnLogin').addEventListener('click', piLogin);
    </script>
</body>

</html>
