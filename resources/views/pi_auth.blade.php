<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login with Pi</title>
</head>

<body>
    <h2>Login with Pi</h2>
    <button id="btnLogin" disabled>جاري التهيئة...</button>
    <div id="status" style="margin-top: 10px;"></div>
    <div id="debug" style="margin-top: 10px; font-size: 12px; color: #666;"></div>

    <script src="https://sdk.minepi.com/pi-sdk.js"></script>
    <script>
        // تهيئة Pi SDK قبل أي استخدام آخر
        let piInitialized = false;
        let piAuth = null; // تعريف متغير auth بشكل صريح

        // دالة مساعدة لإضافة رسائل التصحيح
        function addDebugMessage(message) {
            const debugDiv = document.getElementById('debug');
            debugDiv.innerHTML += '<div>' + new Date().toLocaleTimeString() + ': ' + message + '</div>';
            console.log(message);
        }

        async function initPiSDK() {
            try {
                addDebugMessage('جاري تهيئة Pi SDK...');

                // التحقق من وجود Pi SDK
                if (typeof Pi === 'undefined') {
                    throw new Error('Pi SDK غير متاح');
                }

                // تهيئة Pi SDK
                await Pi.init({
                    version: '2.0', // استخدام أحدث نسخة متاحة
                    sandbox: true, // أو false للإنتاج
                });

                piInitialized = true;
                document.getElementById('btnLogin').disabled = false;
                document.getElementById('btnLogin').textContent = 'Login with Pi';
                document.getElementById('status').textContent = 'Pi SDK تم تهيئته بنجاح';
                addDebugMessage('تم تهيئة Pi SDK بنجاح');
            } catch (e) {
                console.error('فشل تهيئة Pi SDK:', e);
                document.getElementById('status').textContent = 'فشل تهيئة Pi SDK: ' + e.message;
                addDebugMessage('خطأ في التهيئة: ' + e.message);
            }
        }

        // تهيئة Pi SDK عند تحميل الصفحة
        window.addEventListener('load', initPiSDK);

        // التحقق من وجود Pi SDK بشكل دوري
        let checkCount = 0;
        const checkPiSDK = setInterval(() => {
            if (typeof Pi !== 'undefined' && !piInitialized && checkCount < 10) {
                addDebugMessage('تم العثور على Pi SDK، جاري بدء التهيئة...');
                initPiSDK();
                clearInterval(checkPiSDK);
            }
            checkCount++;
        }, 500);

        // مثال عام، عدّل حسب توثيق Pi SDK الفعلي
        async function piLogin() {
            if (!piInitialized) {
                addDebugMessage('Pi SDK لم يتم تهيئته بعد');
                alert('Pi SDK لم يتم تهيئته بعد');
                return;
            }

            try {
                document.getElementById('status').textContent = 'جاري محاولة تسجيل الدخول...';
                document.getElementById('btnLogin').disabled = true;
                addDebugMessage('بدء عملية تسجيل الدخول...');

                // scopes حسب توثيق Pi
                const scopes = ['username', 'payments'];
                addDebugMessage('جاري طلب المصادقة مع النطاقات: ' + scopes.join(', '));

                piAuth = await Pi.authenticate(scopes);
                addDebugMessage('تمت المصادقة بنجاح. بيانات المصادقة: ' + JSON.stringify(piAuth));

                // piAuth قد يحتوي على accessToken, username, publicKey
                const payload = {
                    accessToken: piAuth.accessToken || piAuth.token || '',
                    username: piAuth.username || '',
                    publicKey: piAuth.publicKey || piAuth.pubKey || ''
                };

                addDebugMessage 'جاري إرسال البيانات إلى الخادم... Payload: ' + JSON.stringify(payload));

                // إرسال إلى Laravel API
                const res = await fetch('/api/pi/auth', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                addDebugMessage('تم استلام استجابة من الخادم. الحالة: ' + res.status);
                const data = await res.json();
                addDebugMessage('بيانات الاستجابة: ' + JSON.stringify(data));

                if (res.ok) {
                    // إذا تعمل داخل WebView: نرسل رسالة إلى Flutter أو نجري redirect بخيار token
                    // طريقتان: 1) window.flutter_inappwebview.postMessage  2) redirect with token in URL hash
                    // سنستخدم تغيير window.location إلى custom-scheme مع token
                    const token = data.token;
                    document.getElementById('status').textContent = 'تم تسجيل الدخول بنجاح! جاري التوجيه...';
                    addDebugMessage('تم تسجيل الدخول بنجاح! جاري التوجيه... التوكن: ' + token);
                    // طريقة آمنة: نعيد التوكن في عنوان URL (hash)
                    window.location.href = 'https://callback.local/#token=' + token;
                } else {
                    document.getElementById('status').textContent = 'فشل المصادقة';
                    addDebugMessage('فشل المصادقة: ' + JSON.stringify(data));
                    alert('Auth failed: ' + JSON.stringify(data));
                    document.getElementById('btnLogin').disabled = false;
                }
            } catch (e) {
                console.error(e);
                document.getElementById('status').textContent = 'حدث خطأ: ' + e.message;
                addDebugMessage('حدث خطأ: ' + e.message + ' - Stack: ' + e.stack);
                alert('Error: ' + e.message);
                document.getElementById('btnLogin').disabled = false;
            }
        }

        document.getElementById('btnLogin').addEventListener('click', piLogin);

        // إضافة زر لعرض معلومات التصحيح
        const debugButton = document.createElement('button');
        debugButton.textContent = 'عرض معلومات التصحيح';
        debugButton.style.marginTop = '10px';
        debugButton.onclick = function() {
            const debugDiv = document.getElementById('debug');
            debugDiv.style.display = debugDiv.style.display === 'none' ? 'block' : 'none';
        };
        document.body.appendChild(debugButton);
    </script>
</body>

</html>
